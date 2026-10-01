using System;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.IO;
using System.Threading.Tasks;
using System.Windows.Forms;
using KoopSchoolDesktop.Services;
using KoopSchoolDesktop.Views;

namespace KoopSchoolDesktop.Forms
{
    public class MainForm : Form
    {
        // Sidebar controls
        private Panel pnlSidebar;
        private Label lblHeaderTitle;
        private Label lblHeaderSubtitle;
        private Label lblHeaderUser;
        private Label lblLiveClock;
        private Label lblDbIndicator;
        private Label lblApiIndicator;
        private Panel pnlContent;
        private System.Windows.Forms.Timer clockTimer;

        // Views
        private DashboardView dashboardView;
        private PosView posView;
        private ProductsView productsView;
        private SuppliersView suppliersView;
        private StockPurchaseView stockPurchaseView;
        private OrdersHistoryView ordersHistoryView;
        private StudentCardsView studentCardsView;
        private FinancialReportView financialReportView;
        private SettingsView settingsView;
        private NotificationsView notificationsView;

        // Header controls
        private Button btnHeaderBell;
        private Button btnNavNotifications;
        private System.Windows.Forms.Timer notifPollTimer;

        // Nav Buttons & References
        private readonly List<Button> navButtons = new();
        private Button? currentActiveButton = null;
        private readonly string _currentUser;

        public MainForm(string currentUser = "Administrator")
        {
            _currentUser = currentUser;
            InitializeComponent();
            InitializeViews();
            SwitchView("Dashboard", "Overview & Real-time Metrics", dashboardView, navButtons[0]);
        }

        private void InitializeComponent()
        {
            this.Text = "KooPik - Koperasi Sekolah Management & POS System";
            this.Size = new Size(1380, 860);
            this.MinimumSize = new Size(1180, 720);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            string iconPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app.ico");
            if (File.Exists(iconPath))
            {
                try { this.Icon = new Icon(iconPath); } catch { }
            }

            // 1. Modern Left Sidebar (Crisp White with Slate Border)
            pnlSidebar = new Panel
            {
                Dock = DockStyle.Left,
                Width = 250,
                BackColor = UITheme.Sidebar,
                Padding = new Padding(0)
            };
            pnlSidebar.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, pnlSidebar.Width - 1, 0, pnlSidebar.Width - 1, pnlSidebar.Height);
            };

            // Sidebar Brand Header
            var pnlBrand = new Panel
            {
                Dock = DockStyle.Top,
                Height = 74,
                BackColor = UITheme.Sidebar,
                Padding = new Padding(16, 12, 16, 12)
            };
            pnlBrand.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.BorderSubtle, 1);
                e.Graphics.DrawLine(pen, 0, pnlBrand.Height - 1, pnlBrand.Width, pnlBrand.Height - 1);
            };

            var picLogo = new PictureBox
            {
                Location = new Point(16, 16),
                Size = new Size(42, 42),
                SizeMode = PictureBoxSizeMode.Zoom
            };
            string logoPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "koopik-logo.png");
            if (!File.Exists(logoPath)) logoPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "..", "koopik-logo.png");
            if (File.Exists(logoPath))
            {
                try { picLogo.Image = Image.FromFile(logoPath); } catch { }
            }
            if (picLogo.Image == null)
            {
                // Fallback emoji
                var lblEmoji = new Label { Text = "🛒", Font = new Font("Segoe UI Emoji", 20), Location = new Point(16, 16), AutoSize = true };
                pnlBrand.Controls.Add(lblEmoji);
            }
            else
            {
                pnlBrand.Controls.Add(picLogo);
            }

            var lblBrandText = new Label
            {
                Text = "KooPik",
                Font = new Font("Segoe UI", 15, FontStyle.Bold),
                ForeColor = UITheme.TextDark,
                Location = new Point(66, 16),
                AutoSize = true
            };

            var lblBrandSub = new Label
            {
                Text = "KOPERASI SEKOLAH POS",
                Font = new Font("Segoe UI", 7.5f, FontStyle.Bold),
                ForeColor = UITheme.Primary,
                Location = new Point(68, 42),
                AutoSize = true
            };

            pnlBrand.Controls.AddRange(new Control[] { lblBrandText, lblBrandSub });
            pnlSidebar.Controls.Add(pnlBrand);

            // Top Header Bar
            var pnlHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 64,
                BackColor = UITheme.Surface,
                Padding = new Padding(24, 0, 24, 0)
            };
            pnlHeader.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlHeader.Height - 1, pnlHeader.Width, pnlHeader.Height - 1);
            };

            lblHeaderTitle = new Label
            {
                Text = "Dashboard",
                Font = new Font("Segoe UI", 13.5f, FontStyle.Bold),
                ForeColor = UITheme.TextDark,
                Location = new Point(24, 12),
                AutoSize = true
            };

            lblHeaderSubtitle = new Label
            {
                Text = "Overview & Real-time Metrics",
                Font = new Font("Segoe UI", 8.5f, FontStyle.Regular),
                ForeColor = UITheme.TextMuted,
                Location = new Point(25, 36),
                AutoSize = true
            };
            pnlHeader.Controls.AddRange(new Control[] { lblHeaderTitle, lblHeaderSubtitle });

            // Right header controls container (flowed nicely from right)
            var pnlHeaderRight = new Panel
            {
                Dock = DockStyle.Right,
                Width = 780,
                BackColor = Color.Transparent
            };

            int rightX = 770;

            // Live Clock
            lblLiveClock = new Label
            {
                Text = DateTime.Now.ToString("ddd, dd MMM yyyy • HH:mm:ss"),
                Font = new Font("Segoe UI", 9f, FontStyle.Regular),
                ForeColor = UITheme.TextMedium,
                AutoSize = true
            };
            lblLiveClock.Location = new Point(rightX - 220, 22);
            pnlHeaderRight.Controls.Add(lblLiveClock);

            // User Profile Chip
            lblHeaderUser = new Label
            {
                Text = $"👤 {_currentUser}",
                Font = new Font("Segoe UI", 9f, FontStyle.Bold),
                ForeColor = UITheme.TextDark,
                BackColor = Color.FromArgb(241, 245, 249),
                Padding = new Padding(10, 6, 10, 6),
                AutoSize = true
            };
            lblHeaderUser.Location = new Point(rightX - 350, 16);
            pnlHeaderRight.Controls.Add(lblHeaderUser);

            // Notifications Bell Button
            btnHeaderBell = new Button
            {
                Text = "🔔 Alerts",
                Size = new Size(100, 32),
                Location = new Point(rightX - 465, 16),
                BackColor = UITheme.PrimaryLight,
                ForeColor = UITheme.PrimaryText,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 8.5f, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnHeaderBell.FlatAppearance.BorderColor = Color.FromArgb(199, 210, 254);
            btnHeaderBell.Click += (s, e) => SwitchView("Notifications & Alerts", "Live Store and Inventory Feeds", notificationsView, btnNavNotifications);
            pnlHeaderRight.Controls.Add(btnHeaderBell);

            // API Server indicator
            lblApiIndicator = new Label
            {
                Text = "⚡ API :8000",
                Font = new Font("Segoe UI", 8.5f, FontStyle.Bold),
                ForeColor = Color.FromArgb(29, 78, 216),
                BackColor = Color.FromArgb(239, 246, 255),
                Padding = new Padding(8, 6, 8, 6),
                AutoSize = true,
                Location = new Point(rightX - 570, 16)
            };
            pnlHeaderRight.Controls.Add(lblApiIndicator);

            // Supabase Cloud indicator (Always proudly showing Supabase)
            lblDbIndicator = new Label
            {
                Text = "🟢 Supabase Cloud",
                Font = new Font("Segoe UI", 8.5f, FontStyle.Bold),
                ForeColor = Color.FromArgb(21, 128, 61),
                BackColor = Color.FromArgb(220, 252, 231),
                Padding = new Padding(8, 6, 8, 6),
                AutoSize = true,
                Location = new Point(rightX - 740, 16)
            };
            pnlHeaderRight.Controls.Add(lblDbIndicator);

            pnlHeader.Controls.Add(pnlHeaderRight);
            this.Controls.Add(pnlHeader);

            // Main Content Area
            pnlContent = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Canvas
            };
            this.Controls.Add(pnlContent);

            // Navigation Items Container in Sidebar
            var navContainer = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                Padding = new Padding(12, 10, 12, 10)
            };

            int btnY = 10;

            // SECTION 1: OVERVIEW & SALES
            navContainer.Controls.Add(CreateSectionHeader("OVERVIEW & SALES", ref btnY));
            navContainer.Controls.Add(CreateNavButton("📊", "Dashboard", () => SwitchView("Dashboard", "Store Overview & Key Metrics", dashboardView, navButtons[0]), ref btnY));
            navContainer.Controls.Add(CreateNavButton("🛒", "Cashier (POS)", () => SwitchView("Cashier (Point of Sale)", "Fast Barcode Scanning & Card Payment Terminal", posView, navButtons[1]), ref btnY));
            navContainer.Controls.Add(CreateNavButton("📋", "Orders History", () => SwitchView("Sales & Orders History", "Transaction Records & Receipts Archive", ordersHistoryView, navButtons[2]), ref btnY));

            btnY += 10;
            // SECTION 2: CATALOG & STOCK
            navContainer.Controls.Add(CreateSectionHeader("CATALOG & STOCK", ref btnY));
            navContainer.Controls.Add(CreateNavButton("📦", "Products & Sizes", () => SwitchView("Products Catalog & Inventory", "Manage Apparel Sizes, Pricing & Stock", productsView, navButtons[3]), ref btnY));
            navContainer.Controls.Add(CreateNavButton("🚚", "Suppliers", () => SwitchView("Suppliers Management", "Vendors & Purchase Directory", suppliersView, navButtons[4]), ref btnY));
            navContainer.Controls.Add(CreateNavButton("📥", "Stock Restock", () => SwitchView("Stock Purchases & Restock", "Purchase Orders & Inventory Receiving", stockPurchaseView, navButtons[5]), ref btnY));

            btnY += 10;
            // SECTION 3: WALLET & NFC CARDS
            navContainer.Controls.Add(CreateSectionHeader("WALLET & CARDS", ref btnY));
            navContainer.Controls.Add(CreateNavButton("💳", "Student NFC Cards", () => SwitchView("Student NFC Cards & Wallets", "Instant Reload & Smart Card Management", studentCardsView, navButtons[6]), ref btnY));

            btnY += 10;
            // SECTION 4: MANAGEMENT & TOOLS
            navContainer.Controls.Add(CreateSectionHeader("MANAGEMENT & TOOLS", ref btnY));
            btnNavNotifications = CreateNavButton("🔔", "Notifications", () => SwitchView("Notifications & Alerts", "Live Store and Inventory Feeds", notificationsView, btnNavNotifications), ref btnY);
            navContainer.Controls.Add(btnNavNotifications);
            navContainer.Controls.Add(CreateNavButton("📈", "Financial Reports", () => SwitchView("Financial & Sales Reports", "Revenue, COGS & Net Margin Analysis", financialReportView, navButtons[8]), ref btnY));
            navContainer.Controls.Add(CreateNavButton("⚙️", "Settings", () => SwitchView("System Settings & Config", "Database, Receipt Layout & API Server", settingsView, navButtons[9]), ref btnY));

            pnlSidebar.Controls.Add(navContainer);

            // Bottom Logout Button in Sidebar
            var btnLogout = new Button
            {
                Text = "🚪  Sign Out",
                Dock = DockStyle.Bottom,
                Height = 48,
                BackColor = Color.FromArgb(254, 242, 242),
                ForeColor = UITheme.DangerText,
                FlatStyle = FlatStyle.Flat,
                Font = UITheme.FontBodyBold,
                Cursor = Cursors.Hand,
                TextAlign = ContentAlignment.MiddleCenter
            };
            btnLogout.FlatAppearance.BorderSize = 0;
            btnLogout.Click += (s, e) =>
            {
                if (MessageBox.Show("Are you sure you want to sign out of KooPik?", "Sign Out", MessageBoxButtons.YesNo, MessageBoxIcon.Question) == DialogResult.Yes)
                {
                    Application.Restart();
                }
            };
            pnlSidebar.Controls.Add(btnLogout);

            this.Controls.Add(pnlSidebar);

            // Live Clock Timer (Every 1 second)
            clockTimer = new System.Windows.Forms.Timer { Interval = 1000 };
            clockTimer.Tick += (s, e) =>
            {
                lblLiveClock.Text = DateTime.Now.ToString("ddd, dd MMM yyyy • HH:mm:ss");
            };
            clockTimer.Start();

            // Background Notification Poller (Every 5 seconds)
            notifPollTimer = new System.Windows.Forms.Timer { Interval = 5000 };
            notifPollTimer.Tick += (s, e) => CheckUnreadNotificationsAsync();
            notifPollTimer.Start();
        }

        private Label CreateSectionHeader(string title, ref int currentY)
        {
            var lbl = new Label
            {
                Text = title,
                Location = new Point(14, currentY),
                Size = new Size(210, 18),
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                TextAlign = ContentAlignment.MiddleLeft
            };
            currentY += 24;
            return lbl;
        }

        private Button CreateNavButton(string icon, string label, Action onClick, ref int currentY)
        {
            var btn = new Button
            {
                Text = $"  {icon}  {label}",
                Location = new Point(8, currentY),
                Size = new Size(226, 38),
                BackColor = Color.Transparent,
                ForeColor = UITheme.TextMedium,
                FlatStyle = FlatStyle.Flat,
                Font = UITheme.FontBodyBold,
                TextAlign = ContentAlignment.MiddleLeft,
                Padding = new Padding(12, 0, 0, 0),
                Cursor = Cursors.Hand
            };
            btn.FlatAppearance.BorderSize = 0;
            btn.FlatAppearance.MouseOverBackColor = Color.FromArgb(248, 250, 252);
            btn.Click += (s, e) => onClick();

            btn.Paint += (s, e) =>
            {
                if (btn == currentActiveButton)
                {
                    // Draw active left accent bar
                    using var brush = new SolidBrush(UITheme.Primary);
                    e.Graphics.FillRectangle(brush, 0, 4, 4, btn.Height - 8);
                }
            };

            currentY += 42;
            navButtons.Add(btn);
            return btn;
        }

        private void InitializeViews()
        {
            dashboardView = new DashboardView();
            dashboardView.OnNavigateToPos += () => SwitchView("Cashier (Point of Sale)", "Fast Barcode Scanning & Card Payment Terminal", posView, navButtons[1]);
            dashboardView.OnNavigateToProducts += () => SwitchView("Products Catalog & Inventory", "Manage Apparel Sizes, Pricing & Stock", productsView, navButtons[3]);
            dashboardView.OnNavigateToStockPurchase += () => SwitchView("Stock Purchases & Restock", "Purchase Orders & Inventory Receiving", stockPurchaseView, navButtons[5]);

            posView = new PosView();
            ordersHistoryView = new OrdersHistoryView();
            productsView = new ProductsView();
            suppliersView = new SuppliersView();
            stockPurchaseView = new StockPurchaseView();
            studentCardsView = new StudentCardsView();
            notificationsView = new NotificationsView();
            notificationsView.OnNotificationCountChanged += CheckUnreadNotificationsAsync;

            financialReportView = new FinancialReportView();
            settingsView = new SettingsView();

            lblDbIndicator.Text = "🟢 Supabase Cloud";
            lblDbIndicator.ForeColor = Color.FromArgb(21, 128, 61);
            lblDbIndicator.BackColor = Color.FromArgb(220, 252, 231);

            // Initial check for unread alerts
            CheckUnreadNotificationsAsync();
        }

        private void CheckUnreadNotificationsAsync()
        {
            Task.Run(() =>
            {
                try
                {
                    var stats = DatabaseService.Instance.GetNotificationStats();
                    if (!this.IsDisposed && this.IsHandleCreated)
                    {
                        this.BeginInvoke(() =>
                        {
                            UpdateNotificationBadges(stats.Unread);
                        });
                    }
                }
                catch { }
            });
        }

        private void UpdateNotificationBadges(int unreadCount)
        {
            if (unreadCount > 0)
            {
                btnHeaderBell.Text = $"🔔 {unreadCount} New";
                btnHeaderBell.BackColor = UITheme.DangerLight;
                btnHeaderBell.ForeColor = UITheme.DangerText;
                btnHeaderBell.FlatAppearance.BorderColor = Color.FromArgb(254, 202, 202);

                btnNavNotifications.Text = $"  🔔  Notifications ({unreadCount})";
                btnNavNotifications.ForeColor = UITheme.DangerText;
            }
            else
            {
                btnHeaderBell.Text = "🔔 Alerts";
                btnHeaderBell.BackColor = UITheme.PrimaryLight;
                btnHeaderBell.ForeColor = UITheme.PrimaryText;
                btnHeaderBell.FlatAppearance.BorderColor = Color.FromArgb(199, 210, 254);

                btnNavNotifications.Text = "  🔔  Notifications";
                if (btnNavNotifications != currentActiveButton)
                    btnNavNotifications.ForeColor = UITheme.TextMedium;
            }
        }

        private void SwitchView(string title, string subtitle, Control view, Button activeBtn)
        {
            lblHeaderTitle.Text = title;
            lblHeaderSubtitle.Text = subtitle;

            // Highlight active button
            currentActiveButton = activeBtn;
            foreach (var b in navButtons)
            {
                b.BackColor = Color.Transparent;
                b.ForeColor = UITheme.TextMedium;
                b.Invalidate();
            }

            activeBtn.BackColor = UITheme.PrimaryLight;
            activeBtn.ForeColor = UITheme.PrimaryText;
            activeBtn.Invalidate();

            // Switch view in content area
            pnlContent.SuspendLayout();
            pnlContent.Controls.Clear();
            view.Dock = DockStyle.Fill;
            pnlContent.Controls.Add(view);
            pnlContent.ResumeLayout();

            // Auto-refresh data when switching
            if (view == dashboardView) dashboardView.LoadDashboardData();
            else if (view == posView) posView.LoadCatalog();
            else if (view == notificationsView) notificationsView.LoadNotifications();
            else if (view == productsView) productsView.LoadProductsData();
            else if (view == suppliersView) suppliersView.LoadSuppliersData();
            else if (view == stockPurchaseView) stockPurchaseView.LoadPurchases();
            else if (view == ordersHistoryView) ordersHistoryView.LoadOrders();
            else if (view == studentCardsView) studentCardsView.LoadCards();
            else if (view == financialReportView) financialReportView.GenerateReport();
        }
    }
}
