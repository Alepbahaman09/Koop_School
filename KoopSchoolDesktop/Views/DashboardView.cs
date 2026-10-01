using System;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class DashboardView : UserControl
    {
        private Label lblTotalSales;
        private Label lblTodaySales;
        private Label lblTotalOrders;
        private Label lblLowStock;
        private Label lblLastUpdated;

        private DataGridView dgvRecentOrders;
        private DataGridView dgvLowStock;

        public event Action? OnNavigateToPos;
        public event Action? OnNavigateToProducts;
        public event Action? OnNavigateToStockPurchase;

        public DashboardView()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            var pnlMain = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                Padding = new Padding(24)
            };

            // 1. Top Header Section Card
            var pnlHeaderCard = UITheme.CreateCard(24, 16, 1140, 68);
            var lblTitle = new Label
            {
                Text = "Admin Dashboard",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 12),
                AutoSize = true
            };

            var lblSub = new Label
            {
                Text = "Live store analytics, POS cashier transactions, and Supabase database metrics",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(22, 38),
                AutoSize = true
            };

            lblLastUpdated = new Label
            {
                Text = "Updated just now",
                Font = UITheme.FontSmallBold,
                ForeColor = UITheme.TextMuted,
                Location = new Point(960, 24),
                AutoSize = true
            };

            pnlHeaderCard.Controls.AddRange(new Control[] { lblTitle, lblSub, lblLastUpdated });
            pnlMain.Controls.Add(pnlHeaderCard);

            // 2. Modern KPI Metric Cards Row
            int cardY = 96;
            int cardW = 270;
            int cardH = 92;
            int gap = 20;

            var card1 = CreateMetricCard("TOTAL SALES INCOME", "RM 0.00", UITheme.Primary, "💰", out lblTotalSales);
            card1.Location = new Point(24, cardY);
            pnlMain.Controls.Add(card1);

            var card2 = CreateMetricCard("TODAY'S SALES", "RM 0.00", UITheme.Success, "📈", out lblTodaySales);
            card2.Location = new Point(24 + (cardW + gap), cardY);
            pnlMain.Controls.Add(card2);

            var card3 = CreateMetricCard("TOTAL ORDERS", "0", UITheme.Info, "🧾", out lblTotalOrders);
            card3.Location = new Point(24 + (cardW + gap) * 2, cardY);
            pnlMain.Controls.Add(card3);

            var card4 = CreateMetricCard("ATTENTION: LOW STOCK", "0", UITheme.Danger, "⚠️", out lblLowStock);
            card4.Location = new Point(24 + (cardW + gap) * 3, cardY);
            pnlMain.Controls.Add(card4);

            // 3. Quick Action Shortcuts Bar
            int actY = cardY + cardH + 16;
            var pnlActions = UITheme.CreateCard(24, actY, 1140, 56);
            pnlActions.Padding = new Padding(16, 10, 16, 10);

            var lblQuickLabel = new Label
            {
                Text = "QUICK ACTIONS:",
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                Location = new Point(16, 18),
                AutoSize = true
            };
            pnlActions.Controls.Add(lblQuickLabel);

            var btnOpenPos = new Button
            {
                Text = "🛒 Launch Cashier POS",
                Location = new Point(125, 10),
                Size = new Size(180, 36)
            };
            UITheme.StylePrimaryButton(btnOpenPos);
            btnOpenPos.Click += (s, e) => OnNavigateToPos?.Invoke();
            pnlActions.Controls.Add(btnOpenPos);

            var btnAddProduct = new Button
            {
                Text = "📦 Add Product",
                Location = new Point(315, 10),
                Size = new Size(140, 36)
            };
            UITheme.StyleSecondaryButton(btnAddProduct);
            btnAddProduct.Click += (s, e) => OnNavigateToProducts?.Invoke();
            pnlActions.Controls.Add(btnAddProduct);

            var btnRestock = new Button
            {
                Text = "📥 New Stock Purchase",
                Location = new Point(465, 10),
                Size = new Size(180, 36)
            };
            UITheme.StyleSecondaryButton(btnRestock);
            btnRestock.Click += (s, e) => OnNavigateToStockPurchase?.Invoke();
            pnlActions.Controls.Add(btnRestock);

            var btnRefresh = new Button
            {
                Text = "🔄 Refresh Data",
                Location = new Point(1000, 10),
                Size = new Size(125, 36)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadDashboardData();
            pnlActions.Controls.Add(btnRefresh);

            pnlMain.Controls.Add(pnlActions);

            // 4. Split Analytics Area: Recent Orders (Left) & Low Stock Alerts (Right)
            int splitY = actY + 68;
            int splitH = 430;

            // Left Card: Recent Sales
            var pnlOrdersCard = UITheme.CreateCard(24, splitY, 670, splitH);
            pnlOrdersCard.Padding = new Padding(18, 14, 18, 14);

            var lblOrdersHeader = new Label
            {
                Text = "Recent Sales & Orders",
                Font = UITheme.FontSubheading,
                ForeColor = UITheme.TextDark,
                Location = new Point(16, 14),
                AutoSize = true
            };
            var lblOrdersSub = new Label
            {
                Text = "Real-time POS cashier terminal sales and student transactions",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(18, 38),
                AutoSize = true
            };
            pnlOrdersCard.Controls.AddRange(new Control[] { lblOrdersHeader, lblOrdersSub });

            dgvRecentOrders = new DataGridView
            {
                Location = new Point(16, 64),
                Size = new Size(638, splitH - 80)
            };
            UITheme.StyleTable(dgvRecentOrders);
            dgvRecentOrders.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Order #", Name = "OrderNumber", Width = 140 });
            dgvRecentOrders.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Date & Time", Name = "Date", Width = 140 });
            dgvRecentOrders.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Method", Name = "Method", Width = 100 });
            dgvRecentOrders.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Total Amount", Name = "Total", Width = 130 });
            dgvRecentOrders.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Status", Name = "Status", Width = 110 });

            pnlOrdersCard.Controls.Add(dgvRecentOrders);
            pnlMain.Controls.Add(pnlOrdersCard);

            // Right Card: Attention Required: Low Stock
            var pnlStockCard = UITheme.CreateCard(710, splitY, 454, splitH);
            pnlStockCard.Padding = new Padding(18, 14, 18, 14);

            var lblStockHeader = new Label
            {
                Text = "Attention Required: Low Stock",
                Font = UITheme.FontSubheading,
                ForeColor = UITheme.TextDark,
                Location = new Point(16, 14),
                AutoSize = true
            };
            var lblStockSub = new Label
            {
                Text = "Items currently at or below minimum threshold",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(18, 38),
                AutoSize = true
            };
            pnlStockCard.Controls.AddRange(new Control[] { lblStockHeader, lblStockSub });

            dgvLowStock = new DataGridView
            {
                Location = new Point(16, 64),
                Size = new Size(422, splitH - 80)
            };
            UITheme.StyleTable(dgvLowStock);
            dgvLowStock.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Product Name", Name = "Name", Width = 210 });
            dgvLowStock.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Stock", Name = "Stock", Width = 90 });
            dgvLowStock.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Status", Name = "Status", Width = 110 });

            pnlStockCard.Controls.Add(dgvLowStock);
            pnlMain.Controls.Add(pnlStockCard);

            this.Controls.Add(pnlMain);
        }

        private Panel CreateMetricCard(string title, string initialValue, Color accentColor, string icon, out Label valLabel)
        {
            var pnl = UITheme.CreateCard(0, 0, 270, 92);
            pnl.Padding = new Padding(18, 14, 18, 14);

            // Top Row: Title + Icon Badge
            var lblT = new Label
            {
                Text = title,
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                Location = new Point(18, 14),
                AutoSize = true
            };

            var pnlIconBadge = new Panel
            {
                Size = new Size(34, 34),
                Location = new Point(218, 12),
                BackColor = Color.FromArgb(248, 250, 252)
            };
            var lblIcon = new Label
            {
                Text = icon,
                Font = new Font("Segoe UI Emoji", 14),
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleCenter
            };
            pnlIconBadge.Controls.Add(lblIcon);

            valLabel = new Label
            {
                Text = initialValue,
                Font = UITheme.FontMetric,
                ForeColor = UITheme.TextDark,
                Location = new Point(16, 38),
                AutoSize = true
            };

            pnl.Controls.AddRange(new Control[] { lblT, pnlIconBadge, valLabel });
            return pnl;
        }

        public void LoadDashboardData()
        {
            try
            {
                var summary = DatabaseService.Instance.GetDashboardSummary();

                lblTotalSales.Text = $"{AppConfig.Current.CurrencySymbol} {summary.TotalSales:N2}";
                lblTodaySales.Text = $"{AppConfig.Current.CurrencySymbol} {summary.TodaySales:N2}";
                lblTotalOrders.Text = summary.TotalOrdersCount.ToString("N0");
                lblLowStock.Text = summary.LowStockCount.ToString();
                lblLastUpdated.Text = $"Updated {DateTime.Now:hh:mm:ss tt}";

                // Load Recent Orders
                var orders = DatabaseService.Instance.GetOrders().Take(10).ToList();
                dgvRecentOrders.Rows.Clear();
                foreach (var o in orders)
                {
                    string paymentMethod = o.Notes.Contains("NFC") ? "💳 NFC Card" : "💵 Cash";
                    int row = dgvRecentOrders.Rows.Add(
                        o.OrderNumber,
                        o.CreatedAt.ToString("dd MMM, HH:mm"),
                        paymentMethod,
                        $"{AppConfig.Current.CurrencySymbol} {o.TotalAmount:F2}",
                        o.PaymentStatus
                    );

                    var cell = dgvRecentOrders.Rows[row].Cells["Status"];
                    if (o.PaymentStatus == "Paid")
                    {
                        cell.Style.ForeColor = UITheme.SuccessText;
                        cell.Style.Font = UITheme.FontBodyBold;
                    }
                }

                // Load Low Stock Products
                var lowProducts = DatabaseService.Instance.GetProducts().Where(p => p.StockQuantity <= p.MinStockLevel).Take(10).ToList();
                dgvLowStock.Rows.Clear();
                foreach (var p in lowProducts)
                {
                    string status = p.StockQuantity <= 0 ? "🔴 Out of Stock" : "🟡 Low Stock";
                    int row = dgvLowStock.Rows.Add(p.Name, $"{p.StockQuantity} {p.Unit}", status);

                    var cell = dgvLowStock.Rows[row].Cells["Status"];
                    cell.Style.ForeColor = p.StockQuantity <= 0 ? UITheme.DangerText : UITheme.WarningText;
                    cell.Style.Font = UITheme.FontBodyBold;
                }
            }
            catch (Exception ex)
            {
                lblLastUpdated.Text = "Sync error: " + ex.Message;
            }
        }
    }
}
