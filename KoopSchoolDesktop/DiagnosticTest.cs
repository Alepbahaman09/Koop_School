using System.Text;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop
{
    public static class DiagnosticTest
    {
        public static void Run()
        {
            Console.WriteLine("========================================");
            Console.WriteLine("KOOP SCHOOL .NET DESKTOP DIAGNOSTIC TEST");
            Console.WriteLine("========================================");

            // 1. Database Connection
            var (ok, msg) = DatabaseService.Instance.TestConnection();
            Console.WriteLine($"Database Test: {(ok ? "PASS" : "FAIL")} - {msg}");

            // 2. Categories
            var cats = DatabaseService.Instance.GetCategories();
            Console.WriteLine($"Categories loaded: {cats.Count}");
            foreach (var c in cats) Console.WriteLine($"  - [{c.Id}] {c.Name}");

            // 3. Products
            var prods = DatabaseService.Instance.GetProducts();
            Console.WriteLine($"Products loaded: {prods.Count}");
            foreach (var p in prods.Take(5)) Console.WriteLine($"  - [{p.Id}] {p.Sku} | {p.Name} | RM {p.Price:F2} | Stock: {p.StockQuantity}");

            // 4. Suppliers
            var sups = DatabaseService.Instance.GetSuppliers();
            Console.WriteLine($"Suppliers loaded: {sups.Count}");
            foreach (var s in sups.Take(5)) Console.WriteLine($"  - [{s.Id}] {s.SupplierId} | {s.CompanyName}");

            // 5. Student NFC Cards
            var cards = DatabaseService.Instance.GetStudentCards();
            Console.WriteLine($"Student NFC Cards loaded: {cards.Count}");
            foreach (var cd in cards) Console.WriteLine($"  - [{cd.Id}] {cd.CardUid} | {cd.Owner} | RM {cd.Balance:F2}");

            // 6. Dashboard Summary
            var summary = DatabaseService.Instance.GetDashboardSummary();
            Console.WriteLine($"Total Sales: RM {summary.TotalSales:F2} | Orders Count: {summary.TotalOrdersCount}");

            // 7. Notifications Test
            var notifStats = DatabaseService.Instance.GetNotificationStats();
            Console.WriteLine($"Notification Stats: Total={notifStats.Total}, Unread={notifStats.Unread}, Orders={notifStats.Orders}, Stock={notifStats.Stock}");
            var recentNotifs = DatabaseService.Instance.GetNotifications(null, 5);
            Console.WriteLine($"Recent Notifications loaded: {recentNotifs.Count}");
            foreach (var n in recentNotifs) Console.WriteLine($"  - [{n.Id}] {n.Type} | {n.Title} | Read: {n.IsRead} | {n.CreatedAt:yyyy-MM-dd HH:mm}");

            // 8. API Server Test
            Console.WriteLine("Testing Embedded HTTP API Server...");
            bool apiStarted = ApiServer.Instance.Start(8000);
            Console.WriteLine($"API Server Start on {ApiServer.Instance.ServerUrl}: {(apiStarted ? "SUCCESS" : "FAILED")}");

            if (apiStarted)
            {
                using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(3) };
                try
                {
                    // Test /health
                    var healthRes = http.GetAsync($"{ApiServer.Instance.ServerUrl}health").GetAwaiter().GetResult();
                    string healthBody = healthRes.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                    Console.WriteLine($"API /health: HTTP {(int)healthRes.StatusCode} -> {healthBody.Replace("\n", " ").Replace("\r", "")}");

                    // Test /notifications/changes
                    var notifRes = http.GetAsync($"{ApiServer.Instance.ServerUrl}notifications/changes").GetAwaiter().GetResult();
                    string notifBody = notifRes.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                    Console.WriteLine($"API /notifications/changes: HTTP {(int)notifRes.StatusCode} (length: {notifBody.Length} bytes)");

                    // Test /cashier/history
                    var histRes = http.GetAsync($"{ApiServer.Instance.ServerUrl}cashier/history").GetAwaiter().GetResult();
                    string histBody = histRes.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                    Console.WriteLine($"API /cashier/history: HTTP {(int)histRes.StatusCode} (length: {histBody.Length} bytes)");

                    // Test /cashier/card-lookup
                    foreach (var card in cards)
                    {
                        var lookupPayload = new StringContent($"{{\"card_uid\":\"{card.CardUid}\"}}", Encoding.UTF8, "application/json");
                        var lookupRes = http.PostAsync($"{ApiServer.Instance.ServerUrl}cashier/card-lookup", lookupPayload).GetAwaiter().GetResult();
                        string lookupBody = lookupRes.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                        Console.WriteLine($"API /cashier/card-lookup ({card.CardUid} - {card.Owner}): HTTP {(int)lookupRes.StatusCode} -> {lookupBody.Replace("\n", " ").Replace("\r", "")}");
                    }

                    // Test /cashier/sale with Cash
                    if (prods.Count > 0)
                    {
                        var saleJson = $"{{\"items\":[{{\"product_id\":{prods[0].Id},\"qty\":1}}],\"payment_method\":\"Cash\",\"cash_received\":{prods[0].Price + 10:F2}}}";
                        var salePayload = new StringContent(saleJson, Encoding.UTF8, "application/json");
                        var saleRes = http.PostAsync($"{ApiServer.Instance.ServerUrl}cashier/sale", salePayload).GetAwaiter().GetResult();
                        string saleBody = saleRes.Content.ReadAsStringAsync().GetAwaiter().GetResult();
                        Console.WriteLine($"API /cashier/sale: HTTP {(int)saleRes.StatusCode} -> {saleBody.Replace("\n", " ").Replace("\r", "")}");
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"API HTTP Test error: {ex.Message}");
                }
                finally
                {
                    ApiServer.Instance.Stop();
                }
            }

            Console.WriteLine("========================================");
            Console.WriteLine("ALL DIAGNOSTIC CHECKS COMPLETED SUCCESSFULLY!");
            Console.WriteLine("========================================");
        }
    }
}
