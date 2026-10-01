using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Text.Json;
using System.Threading;
using System.Threading.Tasks;
using KoopSchoolDesktop.Models;

namespace KoopSchoolDesktop.Services
{
    public class ApiServer
    {
        private static ApiServer? _instance;
        public static ApiServer Instance => _instance ??= new ApiServer();

        private HttpListener? _listener;
        private CancellationTokenSource? _cts;
        private Task? _listenTask;

        public bool IsRunning { get; private set; }
        public int BoundPort { get; private set; } = 8000;
        public string ServerUrl => $"http://localhost:{BoundPort}/";

        public event Action<string>? OnLog;

        public bool Start(int preferredPort = 8000)
        {
            if (IsRunning) return true;

            int[] portsToTry = { preferredPort, 8000, 8080, 8085, 9000 };
            foreach (int port in portsToTry)
            {
                try
                {
                    _listener = new HttpListener();
                    _listener.Prefixes.Add($"http://localhost:{port}/");
                    _listener.Prefixes.Add($"http://127.0.0.1:{port}/");
                    _listener.Start();

                    BoundPort = port;
                    IsRunning = true;
                    _cts = new CancellationTokenSource();
                    _listenTask = Task.Run(() => ListenLoop(_cts.Token));

                    Log($"API Server successfully started on {ServerUrl}");
                    return true;
                }
                catch (Exception ex)
                {
                    Log($"Failed to bind port {port}: {ex.Message}");
                    try { _listener?.Close(); } catch { }
                    _listener = null;
                }
            }

            Log("Could not bind API Server to any available port.");
            return false;
        }

        public void Stop()
        {
            if (!IsRunning) return;

            try
            {
                _cts?.Cancel();
                _listener?.Stop();
                _listener?.Close();
            }
            catch { }
            finally
            {
                _listener = null;
                IsRunning = false;
                Log("API Server stopped.");
            }
        }

        private async Task ListenLoop(CancellationToken token)
        {
            while (!token.IsCancellationRequested && _listener != null && _listener.IsListening)
            {
                try
                {
                    var context = await _listener.GetContextAsync();
                    _ = Task.Run(() => HandleRequest(context));
                }
                catch (HttpListenerException)
                {
                    break;
                }
                catch (Exception ex)
                {
                    if (token.IsCancellationRequested) break;
                    Log($"Listener error: {ex.Message}");
                }
            }
        }

        private async Task HandleRequest(HttpListenerContext context)
        {
            var req = context.Request;
            var res = context.Response;

            // CORS headers for interoperability
            res.Headers.Add("Access-Control-Allow-Origin", "*");
            res.Headers.Add("Access-Control-Allow-Methods", "GET, POST, PATCH, PUT, DELETE, OPTIONS");
            res.Headers.Add("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With");
            res.Headers.Add("Cache-Control", "no-store, no-cache, must-revalidate");

            if (req.HttpMethod.Equals("OPTIONS", StringComparison.OrdinalIgnoreCase))
            {
                res.StatusCode = (int)HttpStatusCode.OK;
                res.Close();
                return;
            }

            string rawPath = req.Url?.AbsolutePath.TrimEnd('/') ?? "";
            string method = req.HttpMethod.ToUpperInvariant();

            try
            {
                // 1. Health check: GET /health or GET /api/health
                if ((rawPath == "/health" || rawPath == "/api/health") && method == "GET")
                {
                    await WriteJson(res, HttpStatusCode.OK, new
                    {
                        status = "ok",
                        app = "Koop School Desktop",
                        version = "1.0.0",
                        timestamp = DateTime.UtcNow.ToString("o"),
                        database = DatabaseService.Instance.IsPostgreSql ? "PostgreSQL Cloud" : "SQLite Local",
                        port = BoundPort
                    });
                    return;
                }

                // 2. POS Sale: POST /cashier/sale
                if (rawPath == "/cashier/sale" && method == "POST")
                {
                    await HandleCashierSale(req, res);
                    return;
                }

                // 3. Card Lookup: POST /cashier/card-lookup
                if (rawPath == "/cashier/card-lookup" && method == "POST")
                {
                    await HandleCardLookup(req, res);
                    return;
                }

                // 4. POS History: GET /cashier/history
                if (rawPath == "/cashier/history" && method == "GET")
                {
                    await HandleCashierHistory(req, res);
                    return;
                }

                // 5. Order Detail: GET /cashier/order/{orderNumber}
                if (rawPath.StartsWith("/cashier/order/") && method == "GET")
                {
                    string orderNum = rawPath.Substring("/cashier/order/".Length);
                    await HandleOrderDetail(orderNum, res);
                    return;
                }

                // 6. Polling Notifications Changes: GET /notifications/changes
                if (rawPath == "/notifications/changes" && method == "GET")
                {
                    await HandleNotificationChanges(req, res);
                    return;
                }

                // 7. Mark All Read: POST /notifications/read-all
                if (rawPath == "/notifications/read-all" && method == "POST")
                {
                    int updated = DatabaseService.Instance.MarkAllNotificationsAsRead();
                    await WriteJson(res, HttpStatusCode.OK, new
                    {
                        success = true,
                        message = "All notifications marked as read.",
                        updated_count = updated
                    });
                    return;
                }

                // 8. Mark Single Read: POST or PATCH /notifications/{id}/read
                if (rawPath.StartsWith("/notifications/") && rawPath.EndsWith("/read") && (method == "POST" || method == "PATCH"))
                {
                    string idStr = rawPath.Replace("/notifications/", "").Replace("/read", "").Trim('/');
                    if (long.TryParse(idStr, out long notifId))
                    {
                        bool ok = DatabaseService.Instance.MarkNotificationAsRead(notifId);
                        await WriteJson(res, HttpStatusCode.OK, new
                        {
                            success = ok,
                            message = ok ? "Notification marked as read." : "Notification not found or already read."
                        });
                        return;
                    }
                }

                // 9. API Products Catalog: GET /api/products
                if (rawPath == "/api/products" && method == "GET")
                {
                    var products = DatabaseService.Instance.GetProducts();
                    await WriteJson(res, HttpStatusCode.OK, new
                    {
                        success = true,
                        count = products.Count,
                        products = products.Select(p => new
                        {
                            id = p.Id,
                            sku = p.Sku,
                            name = p.Name,
                            category_id = p.CategoryId,
                            category_name = p.CategoryName,
                            price = p.Price,
                            cost_price = p.CostPrice,
                            stock_quantity = p.StockQuantity,
                            is_active = p.IsActive,
                            sizes = p.Sizes.Select(s => new { size = s.Size, stock_quantity = s.StockQuantity })
                        })
                    });
                    return;
                }

                // 10. API Cards Catalog: GET /api/cards
                if (rawPath == "/api/cards" && method == "GET")
                {
                    var cards = DatabaseService.Instance.GetStudentCards();
                    await WriteJson(res, HttpStatusCode.OK, new
                    {
                        success = true,
                        count = cards.Count,
                        cards = cards.Select(c => new
                        {
                            id = c.Id,
                            card_uid = c.CardUid,
                            owner = c.Owner,
                            balance = c.Balance,
                            is_frozen = c.IsFrozen
                        })
                    });
                    return;
                }

                // Default 404
                await WriteJson(res, HttpStatusCode.NotFound, new
                {
                    success = false,
                    message = $"Endpoint '{rawPath}' not found."
                });
            }
            catch (Exception ex)
            {
                Log($"Error processing request {method} {rawPath}: {ex.Message}");
                await WriteJson(res, HttpStatusCode.InternalServerError, new
                {
                    success = false,
                    message = ex.Message
                });
            }
        }

        #region Route Handlers

        private async Task HandleCashierSale(HttpListenerRequest req, HttpListenerResponse res)
        {
            string body = await ReadBodyString(req);
            using var doc = JsonDocument.Parse(body);
            var root = doc.RootElement;

            if (!root.TryGetProperty("items", out var itemsElem) || itemsElem.ValueKind != JsonValueKind.Array || itemsElem.GetArrayLength() == 0)
            {
                await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "Cart items are required." });
                return;
            }

            string paymentMethod = root.TryGetProperty("payment_method", out var pmElem) ? pmElem.GetString() ?? "Cash" : "Cash";
            decimal cashReceived = 0;
            if (root.TryGetProperty("cash_received", out var crElem) && crElem.ValueKind == JsonValueKind.Number)
            {
                cashReceived = crElem.GetDecimal();
            }

            string cardUid = root.TryGetProperty("card_uid", out var cdElem) ? cdElem.GetString() ?? "" : "";
            decimal discount = 0;
            if (root.TryGetProperty("discount", out var discElem) && discElem.ValueKind == JsonValueKind.Number)
            {
                discount = discElem.GetDecimal();
            }

            // Convert items
            var cartItems = new List<CartItem>();
            var allProducts = DatabaseService.Instance.GetProducts().ToDictionary(p => p.Id);

            foreach (var itm in itemsElem.EnumerateArray())
            {
                if (!itm.TryGetProperty("product_id", out var pidElem)) continue;
                long pid = pidElem.GetInt64();
                int qty = itm.TryGetProperty("qty", out var qElem) ? qElem.GetInt32() : 1;
                string? size = itm.TryGetProperty("size", out var sElem) ? sElem.GetString() : null;

                if (!allProducts.TryGetValue(pid, out var product))
                {
                    await WriteJson(res, (HttpStatusCode)404, new { success = false, message = $"Product #{pid} not found." });
                    return;
                }

                if (product.StockQuantity < qty)
                {
                    await WriteJson(res, (HttpStatusCode)422, new { success = false, message = $"Insufficient stock for \"{product.Name}\" (available: {product.StockQuantity})." });
                    return;
                }

                cartItems.Add(new CartItem
                {
                    ProductId = product.Id,
                    Sku = product.Sku,
                    Name = product.Name,
                    Price = product.Price,
                    Quantity = qty,
                    Size = size,
                    AvailableStock = product.StockQuantity
                });
            }

            decimal total = Math.Max(0, cartItems.Sum(c => c.Subtotal) - discount);

            // Additional verification for NFC card
            StudentCard? card = null;
            if (paymentMethod.Equals("NFC Card", StringComparison.OrdinalIgnoreCase) || paymentMethod.Equals("nfc", StringComparison.OrdinalIgnoreCase))
            {
                if (string.IsNullOrWhiteSpace(cardUid))
                {
                    await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "Card UID is required for NFC payment." });
                    return;
                }

                card = DatabaseService.Instance.GetStudentCardByUid(cardUid);
                if (card == null)
                {
                    await WriteJson(res, (HttpStatusCode)404, new { success = false, message = "NFC Card not found." });
                    return;
                }

                if (card.IsFrozen)
                {
                    await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "NFC Card is frozen." });
                    return;
                }

                if (card.Balance < total)
                {
                    await WriteJson(res, (HttpStatusCode)422, new
                    {
                        success = false,
                        message = $"Insufficient card balance. Available: {AppConfig.Current.CurrencySymbol} {card.Balance:F2}"
                    });
                    return;
                }
            }
            else
            {
                paymentMethod = "Cash";
                if (cashReceived < total)
                {
                    await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "Cash received is less than total amount." });
                    return;
                }
            }

            // Execute sale order via transaction in DatabaseService
            var (success, orderNumber, paymentRef, errMsg) = DatabaseService.Instance.CreateSaleOrder(
                cartItems,
                discount,
                paymentMethod,
                cardUid,
                cashReceived,
                card != null ? card.Owner : "Walk-in Customer"
            );

            if (!success)
            {
                await WriteJson(res, (HttpStatusCode)500, new { success = false, message = errMsg });
                return;
            }

            // Return JSON matching Laravel response
            if (paymentMethod == "Cash")
            {
                await WriteJson(res, (HttpStatusCode)201, new
                {
                    success = true,
                    order_number = orderNumber,
                    payment_reference = paymentRef,
                    total = total,
                    payment_method = "Cash",
                    cash_received = cashReceived,
                    change = Math.Round(cashReceived - total, 2)
                });
            }
            else
            {
                // Reload fresh balance
                var updatedCard = DatabaseService.Instance.GetStudentCardByUid(cardUid);
                await WriteJson(res, (HttpStatusCode)201, new
                {
                    success = true,
                    order_number = orderNumber,
                    payment_reference = paymentRef,
                    total = total,
                    payment_method = "NFC Card",
                    card_owner = card?.Owner ?? "",
                    remaining_balance = updatedCard != null ? $"{updatedCard.Balance:F2}" : "0.00"
                });
            }
        }

        private async Task HandleCardLookup(HttpListenerRequest req, HttpListenerResponse res)
        {
            string body = await ReadBodyString(req);
            using var doc = JsonDocument.Parse(body);
            var root = doc.RootElement;

            if (!root.TryGetProperty("card_uid", out var cdElem) || string.IsNullOrWhiteSpace(cdElem.GetString()))
            {
                await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "card_uid is required." });
                return;
            }

            string cardUid = cdElem.GetString()!.Trim();
            var card = DatabaseService.Instance.GetStudentCardByUid(cardUid);

            if (card == null)
            {
                await WriteJson(res, (HttpStatusCode)404, new { success = false, message = "Card not found." });
                return;
            }

            if (card.IsFrozen)
            {
                await WriteJson(res, (HttpStatusCode)422, new { success = false, message = "Card is frozen." });
                return;
            }

            if (root.TryGetProperty("amount", out var amtElem) && amtElem.ValueKind == JsonValueKind.Number)
            {
                decimal requiredAmt = amtElem.GetDecimal();
                if (card.Balance < requiredAmt)
                {
                    await WriteJson(res, (HttpStatusCode)422, new
                    {
                        success = false,
                        message = $"Insufficient card balance. Available: {AppConfig.Current.CurrencySymbol} {card.Balance:F2}"
                    });
                    return;
                }
            }

            await WriteJson(res, HttpStatusCode.OK, new
            {
                success = true,
                owner = card.Owner,
                balance = $"{card.Balance:F2}"
            });
        }

        private async Task HandleCashierHistory(HttpListenerRequest req, HttpListenerResponse res)
        {
            var history = DatabaseService.Instance.GetRecentPosHistory(50);
            var list = history.Select(h => new
            {
                id = h.orderNumber,
                date = h.date.ToString("yyyy-MM-dd HH:mm"),
                total = h.total,
                method = h.method,
                status = h.status
            });

            await WriteJson(res, HttpStatusCode.OK, new
            {
                success = true,
                data = list
            });
        }

        private async Task HandleOrderDetail(string orderNumber, HttpListenerResponse res)
        {
            var (order, items, paymentMethod, paymentNotes) = DatabaseService.Instance.GetOrderByNumber(orderNumber);
            if (order == null)
            {
                await WriteJson(res, (HttpStatusCode)404, new { success = false, message = "Order not found." });
                return;
            }

            await WriteJson(res, HttpStatusCode.OK, new
            {
                success = true,
                order_number = order.OrderNumber,
                date = order.CreatedAt.ToString("dd MMM yyyy, hh:mm tt"),
                total = order.TotalAmount,
                payment_method = paymentMethod,
                status = order.PaymentStatus,
                notes = paymentNotes,
                items = items.Select(i => new
                {
                    name = i.ProductName,
                    size = i.Size,
                    qty = i.Quantity,
                    unit_price = i.UnitPrice,
                    subtotal = i.Subtotal
                })
            });
        }

        private async Task HandleNotificationChanges(HttpListenerRequest req, HttpListenerResponse res)
        {
            long afterId = 0;
            string? afterStr = req.QueryString["after_id"];
            if (!string.IsNullOrEmpty(afterStr) && long.TryParse(afterStr, out long parsedId))
            {
                afterId = parsedId;
            }

            var notifs = DatabaseService.Instance.GetLatestNotifications(afterId, 20);
            var stats = DatabaseService.Instance.GetNotificationStats();
            long latestId = notifs.Count > 0 ? notifs.Last().Id : afterId;

            await WriteJson(res, HttpStatusCode.OK, new
            {
                latest_id = latestId,
                stats = new
                {
                    total = stats.Total,
                    unread = stats.Unread,
                    orders = stats.Orders,
                    stock = stats.Stock
                },
                notifications = notifs.Select(n => new
                {
                    id = n.Id,
                    title = n.Title,
                    message = n.Message,
                    category = n.IsOrder ? "orders" : "stock",
                    severity = n.IsStock ? "error" : "success"
                })
            });
        }

        #endregion

        #region Helpers

        private static async Task<string> ReadBodyString(HttpListenerRequest req)
        {
            using var reader = new StreamReader(req.InputStream, req.ContentEncoding ?? Encoding.UTF8);
            return await reader.ReadToEndAsync();
        }

        private static async Task WriteJson(HttpListenerResponse res, HttpStatusCode status, object data)
        {
            res.StatusCode = (int)status;
            res.ContentType = "application/json; charset=utf-8";

            string json = JsonSerializer.Serialize(data, new JsonSerializerOptions
            {
                PropertyNamingPolicy = null, // preserves exact field names
                WriteIndented = true
            });

            byte[] bytes = Encoding.UTF8.GetBytes(json);
            res.ContentLength64 = bytes.Length;
            await res.OutputStream.WriteAsync(bytes, 0, bytes.Length);
            res.OutputStream.Close();
        }

        private void Log(string msg)
        {
            OnLog?.Invoke($"[API Server] {msg}");
        }

        #endregion
    }
}
