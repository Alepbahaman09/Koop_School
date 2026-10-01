using System;
using System.Collections.Generic;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class NotificationsView : UserControl
    {
        private Label lblTotalCount;
        private Label lblUnreadCount;
        private Label lblOrdersCount;
        private Label lblStockCount;

        private Button btnFilterAll;
        private Button btnFilterUnread;
        private Button btnFilterOrders;
        private Button btnFilterStock;

        private Button btnMarkAllRead;
        private Button btnRefresh;

        private DataGridView dgvNotifications;
        private Label lblFooterStatus;

        private string _currentFilter = ""; // "", "unread", "orders", "stock"
        private List<AdminNotification> _notifications = new();

        public event Action? OnNotificationCountChanged;

        public NotificationsView()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            // 1. Top Header & Action Panel
            var pnlHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 88,
                Padding = new Padding(20, 14, 20, 10),
                BackColor = UITheme.Surface
            };
            pnlHeader.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlHeader.Height - 1, pnlHeader.Width, pnlHeader.Height - 1);
            };

            var lblTitle = new Label
            {
                Text = "Notifications & Live Alerts",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 12),
                AutoSize = true
            };

            var lblSub = new Label
            {
                Text = "Live alerts for student orders, POS cashier purchases, and inventory stock thresholds",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(22, 42),
                AutoSize = true
            };

            btnMarkAllRead = new Button
            {
                Text = "✓ Mark All as Read",
                Size = new Size(165, 36),
                Location = new Point(760, 24)
            };
            UITheme.StyleSuccessButton(btnMarkAllRead);
            btnMarkAllRead.Click += BtnMarkAllRead_Click;

            btnRefresh = new Button
            {
                Text = "🔄 Refresh",
                Size = new Size(110, 36),
                Location = new Point(940, 24)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadNotifications();

            pnlHeader.Controls.AddRange(new Control[] { lblTitle, lblSub, btnMarkAllRead, btnRefresh });
            this.Controls.Add(pnlHeader);

            // 2. Summary KPI Banner
            var pnlKpis = new Panel
            {
                Dock = DockStyle.Top,
                Height = 92,
                Padding = new Padding(20, 12, 20, 8),
                BackColor = UITheme.Canvas
            };

            int cardWidth = 240;
            int cardGap = 16;
            int cardX = 20;

            var (cardTotal, lblTot) = CreateKpiCard("TOTAL ALERTS", "0", UITheme.Primary, cardX, cardWidth);
            lblTotalCount = lblTot;
            cardX += cardWidth + cardGap;

            var (cardUnread, lblUnr) = CreateKpiCard("UNREAD ALERTS", "0", UITheme.DangerText, cardX, cardWidth);
            lblUnreadCount = lblUnr;
            cardX += cardWidth + cardGap;

            var (cardOrders, lblOrd) = CreateKpiCard("ORDERS & SALES", "0", UITheme.PrimaryHover, cardX, cardWidth);
            lblOrdersCount = lblOrd;
            cardX += cardWidth + cardGap;

            var (cardStock, lblStk) = CreateKpiCard("STOCK ALERTS", "0", UITheme.WarningText, cardX, cardWidth);
            lblStockCount = lblStk;

            pnlKpis.Controls.AddRange(new Control[] { cardTotal, cardUnread, cardOrders, cardStock });
            this.Controls.Add(pnlKpis);

            // 3. Filter Tabs Bar
            var pnlFilters = new Panel
            {
                Dock = DockStyle.Top,
                Height = 52,
                Padding = new Padding(20, 8, 20, 8),
                BackColor = UITheme.Surface
            };
            pnlFilters.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlFilters.Height - 1, pnlFilters.Width, pnlFilters.Height - 1);
            };

            btnFilterAll = CreateFilterTabButton("All Notifications", "", 20);
            btnFilterUnread = CreateFilterTabButton("Unread Only", "unread", 180);
            btnFilterOrders = CreateFilterTabButton("Orders & Sales", "orders", 330);
            btnFilterStock = CreateFilterTabButton("Stock Alerts", "stock", 490);

            pnlFilters.Controls.AddRange(new Control[] { btnFilterAll, btnFilterUnread, btnFilterOrders, btnFilterStock });
            this.Controls.Add(pnlFilters);

            // 4. Notifications Grid Container Card
            var pnlGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(20, 12, 20, 12),
                BackColor = UITheme.Canvas
            };

            var pnlInnerCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface
            };
            UITheme.ApplyCardBorder(pnlInnerCard);

            dgvNotifications = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvNotifications);
            dgvNotifications.AutoSizeRowsMode = DataGridViewAutoSizeRowsMode.AllCells;

            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { Name = "Id", Visible = false });
            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Status", Name = "Status", Width = 110 });
            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Type", Name = "Type", Width = 140 });
            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Title", Name = "Title", Width = 230 });
            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Message", Name = "Message", Width = 380, AutoSizeMode = DataGridViewAutoSizeColumnMode.Fill });
            dgvNotifications.Columns.Add(new DataGridViewTextBoxColumn { HeaderText = "Time", Name = "Time", Width = 160 });

            var btnCol = new DataGridViewButtonColumn
            {
                HeaderText = "Action",
                Name = "Action",
                Text = "Mark Read",
                UseColumnTextForButtonValue = false,
                Width = 110,
                FlatStyle = FlatStyle.Flat
            };
            dgvNotifications.Columns.Add(btnCol);

            dgvNotifications.CellContentClick += DgvNotifications_CellContentClick;
            dgvNotifications.CellDoubleClick += DgvNotifications_CellDoubleClick;

            pnlInnerCard.Controls.Add(dgvNotifications);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            // 5. Footer Status Bar
            var pnlFooter = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 36,
                BackColor = UITheme.Surface,
                Padding = new Padding(20, 8, 20, 8)
            };
            pnlFooter.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, 0, pnlFooter.Width, 0);
            };

            lblFooterStatus = new Label
            {
                Text = "● Auto-syncing live alerts from Supabase PostgreSQL Cloud every 5 seconds",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Dock = DockStyle.Left,
                AutoSize = true
            };
            pnlFooter.Controls.Add(lblFooterStatus);
            this.Controls.Add(pnlFooter);

            // Bring in proper z-order
            pnlFooter.BringToFront();
            pnlGridCard.BringToFront();
            pnlFilters.BringToFront();
            pnlKpis.BringToFront();
            pnlHeader.BringToFront();

            UpdateFilterButtonStyles();
        }

        private (Panel card, Label count) CreateKpiCard(string title, string initialValue, Color accentColor, int x, int width)
        {
            var pnl = new Panel
            {
                Location = new Point(x, 6),
                Size = new Size(width, 74),
                BackColor = UITheme.Surface,
                Padding = new Padding(16, 10, 16, 10)
            };
            UITheme.ApplyCardBorder(pnl);

            var bar = new Panel
            {
                Dock = DockStyle.Left,
                Width = 4,
                BackColor = accentColor
            };
            pnl.Controls.Add(bar);

            var lblT = new Label
            {
                Text = title,
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                Location = new Point(16, 10),
                AutoSize = true
            };

            var lblV = new Label
            {
                Text = initialValue,
                Font = new Font("Segoe UI", 15, FontStyle.Bold),
                ForeColor = accentColor,
                Location = new Point(14, 32),
                AutoSize = true
            };

            pnl.Controls.AddRange(new Control[] { lblT, lblV });
            return (pnl, lblV);
        }

        private Button CreateFilterTabButton(string text, string filterKey, int x)
        {
            var btn = new Button
            {
                Text = text,
                Location = new Point(x, 8),
                Size = new Size(145, 34),
                FlatStyle = FlatStyle.Flat,
                Font = UITheme.FontBody,
                Cursor = Cursors.Hand
            };
            btn.FlatAppearance.BorderSize = 0;
            btn.Click += (s, e) =>
            {
                _currentFilter = filterKey;
                UpdateFilterButtonStyles();
                LoadNotifications();
            };
            return btn;
        }

        private void UpdateFilterButtonStyles()
        {
            SetFilterStyle(btnFilterAll, _currentFilter == "");
            SetFilterStyle(btnFilterUnread, _currentFilter == "unread");
            SetFilterStyle(btnFilterOrders, _currentFilter == "orders");
            SetFilterStyle(btnFilterStock, _currentFilter == "stock");
        }

        private void SetFilterStyle(Button btn, bool isActive)
        {
            if (isActive)
            {
                btn.BackColor = UITheme.Primary;
                btn.ForeColor = Color.White;
                btn.Font = UITheme.FontBodyBold;
            }
            else
            {
                btn.BackColor = UITheme.Canvas;
                btn.ForeColor = UITheme.TextMedium;
                btn.Font = UITheme.FontBody;
            }
        }

        public void LoadNotifications()
        {
            try
            {
                // 1. Load Stats
                var stats = DatabaseService.Instance.GetNotificationStats();
                lblTotalCount.Text = stats.Total.ToString();
                lblUnreadCount.Text = stats.Unread.ToString();
                lblOrdersCount.Text = stats.Orders.ToString();
                lblStockCount.Text = stats.Stock.ToString();

                // 2. Load Filtered Notifications
                _notifications = DatabaseService.Instance.GetNotifications(_currentFilter, 100);

                dgvNotifications.Rows.Clear();
                foreach (var notif in _notifications)
                {
                    string statusText = notif.IsRead ? "✓ Read" : "● NEW";
                    string typeLabel = FormatType(notif.Type);
                    string actionText = notif.IsRead ? "Done" : "Mark Read";

                    int rowIndex = dgvNotifications.Rows.Add(
                        notif.Id,
                        statusText,
                        typeLabel,
                        notif.Title,
                        notif.Message,
                        notif.CreatedAt.ToString("dd MMM yyyy, HH:mm"),
                        actionText
                    );

                    var row = dgvNotifications.Rows[rowIndex];

                    // Style row based on read / unread status
                    if (!notif.IsRead)
                    {
                        row.DefaultCellStyle.Font = new Font("Segoe UI", 9.5f, FontStyle.Bold);
                        row.DefaultCellStyle.BackColor = Color.FromArgb(254, 242, 242); // Soft light pink/coral
                        row.Cells["Status"].Style.ForeColor = Color.FromArgb(220, 38, 38); // Bold red dot
                    }
                    else
                    {
                        row.DefaultCellStyle.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);
                        row.DefaultCellStyle.ForeColor = Color.FromArgb(71, 85, 105);
                        row.Cells["Status"].Style.ForeColor = Color.FromArgb(148, 163, 184);
                    }

                    // Style Type badge cell
                    if (notif.IsStock)
                    {
                        row.Cells["Type"].Style.ForeColor = Color.FromArgb(180, 83, 9);
                    }
                    else if (notif.IsOrder)
                    {
                        row.Cells["Type"].Style.ForeColor = Color.FromArgb(29, 78, 216);
                    }
                }

                lblFooterStatus.Text = $"Showing {_notifications.Count} notifications • Filter: {(string.IsNullOrEmpty(_currentFilter) ? "All" : _currentFilter)} • Last updated: {DateTime.Now:HH:mm:ss}";
                OnNotificationCountChanged?.Invoke();
            }
            catch (Exception ex)
            {
                lblFooterStatus.Text = $"Error loading notifications: {ex.Message}";
            }
        }

        private static string FormatType(string type)
        {
            return type switch
            {
                "purchase" => "POS Purchase",
                "order_created" => "New Order",
                "order_received" => "Order Received",
                "stock_low" => "Low Stock",
                "stock_out" => "Out of Stock",
                _ => type
            };
        }

        private void DgvNotifications_CellContentClick(object? sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex < 0 || e.ColumnIndex != dgvNotifications.Columns["Action"].Index) return;

            long id = Convert.ToInt64(dgvNotifications.Rows[e.RowIndex].Cells["Id"].Value);
            var notif = _notifications.FirstOrDefault(n => n.Id == id);
            if (notif != null && !notif.IsRead)
            {
                DatabaseService.Instance.MarkNotificationAsRead(id);
                LoadNotifications();
            }
        }

        private void DgvNotifications_CellDoubleClick(object? sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex < 0) return;

            long id = Convert.ToInt64(dgvNotifications.Rows[e.RowIndex].Cells["Id"].Value);
            var notif = _notifications.FirstOrDefault(n => n.Id == id);
            if (notif != null)
            {
                if (!notif.IsRead)
                {
                    DatabaseService.Instance.MarkNotificationAsRead(id);
                }

                MessageBox.Show(
                    $"Type: {FormatType(notif.Type)}\n" +
                    $"Title: {notif.Title}\n" +
                    $"Date: {notif.CreatedAt:dd MMM yyyy, HH:mm:ss}\n\n" +
                    $"{notif.Message}\n\n" +
                    (string.IsNullOrEmpty(notif.Data) ? "" : $"Details: {notif.Data}"),
                    notif.Title,
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);

                LoadNotifications();
            }
        }

        private void BtnMarkAllRead_Click(object? sender, EventArgs e)
        {
            int updated = DatabaseService.Instance.MarkAllNotificationsAsRead();
            MessageBox.Show($"Marked {updated} notification(s) as read.", "Notifications", MessageBoxButtons.OK, MessageBoxIcon.Information);
            LoadNotifications();
        }
    }
}
