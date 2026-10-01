using System.Text;
using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class OrdersHistoryView : UserControl
    {
        private DateTimePicker dtpFrom;
        private DateTimePicker dtpTo;
        private TextBox txtSearch;
        private ComboBox cmbStatusFilter;
        private ComboBox cmbPaymentFilter;
        private Button btnFilter;

        private DataGridView dgvOrders;
        private DataGridView dgvItems;
        private Label lblItemsTitle;

        // Status change controls
        private ComboBox cmbNewStatus;
        private Button btnChangeStatus;
        private Button btnReprintReceipt;
        private Button btnExport;

        private List<Order> _orders = new();
        private Order? _selectedOrder = null;

        public OrdersHistoryView()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            var pnlTop = new Panel
            {
                Dock = DockStyle.Top,
                Height = 88,
                Padding = new Padding(20, 14, 20, 10),
                BackColor = UITheme.Surface
            };
            pnlTop.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlTop.Height - 1, pnlTop.Width, pnlTop.Height - 1);
            };

            var lblTitle = new Label
            {
                Text = "Sales & Orders History",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 10),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblTitle);

            // Filter Bar
            pnlTop.Controls.Add(new Label { Text = "From:", Location = new Point(20, 48), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            dtpFrom = new DateTimePicker { Location = new Point(64, 44), Size = new Size(125, 28), Format = DateTimePickerFormat.Short, Value = DateTime.Today.AddDays(-14) };
            pnlTop.Controls.Add(dtpFrom);

            pnlTop.Controls.Add(new Label { Text = "To:", Location = new Point(200, 48), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            dtpTo = new DateTimePicker { Location = new Point(228, 44), Size = new Size(125, 28), Format = DateTimePickerFormat.Short, Value = DateTime.Today };
            pnlTop.Controls.Add(dtpTo);

            txtSearch = new TextBox { Location = new Point(365, 44), Size = new Size(180, 28), PlaceholderText = "🔍 Search order #..." };
            pnlTop.Controls.Add(txtSearch);

            cmbStatusFilter = new ComboBox { Location = new Point(555, 44), Size = new Size(140, 28), DropDownStyle = ComboBoxStyle.DropDownList };
            cmbStatusFilter.Items.AddRange(new object[] { "All Statuses", "Processing", "Ready", "Completed", "Cancelled" });
            cmbStatusFilter.SelectedIndex = 0;
            pnlTop.Controls.Add(cmbStatusFilter);

            cmbPaymentFilter = new ComboBox { Location = new Point(705, 44), Size = new Size(130, 28), DropDownStyle = ComboBoxStyle.DropDownList };
            cmbPaymentFilter.Items.AddRange(new object[] { "All Payments", "Cash", "NFC Card", "Online" });
            cmbPaymentFilter.SelectedIndex = 0;
            pnlTop.Controls.Add(cmbPaymentFilter);

            btnFilter = new Button
            {
                Text = "Filter",
                Location = new Point(845, 42),
                Size = new Size(80, 32)
            };
            UITheme.StylePrimaryButton(btnFilter);
            btnFilter.Click += (s, e) => LoadOrders();
            pnlTop.Controls.Add(btnFilter);

            btnExport = new Button
            {
                Text = "📊 Export CSV",
                Location = new Point(935, 42),
                Size = new Size(120, 32)
            };
            UITheme.StyleSecondaryButton(btnExport);
            btnExport.Click += BtnExport_Click;
            pnlTop.Controls.Add(btnExport);

            btnReprintReceipt = new Button
            {
                Text = "🖨️ View Receipt",
                Location = new Point(1065, 42),
                Size = new Size(135, 32)
            };
            UITheme.StyleSecondaryButton(btnReprintReceipt);
            btnReprintReceipt.Click += BtnReprintReceipt_Click;
            pnlTop.Controls.Add(btnReprintReceipt);

            this.Controls.Add(pnlTop);

            // Container Panel with Canvas padding
            var pnlBody = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(16),
                BackColor = UITheme.Canvas
            };

            // Split Container
            var split = new SplitContainer
            {
                Dock = DockStyle.Fill,
                Orientation = Orientation.Vertical,
                SplitterDistance = 680,
                BackColor = Color.Transparent
            };

            // Orders List Card (Left)
            var pnlLeftCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface,
                Padding = new Padding(1)
            };
            UITheme.ApplyCardBorder(pnlLeftCard);

            dgvOrders = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvOrders);
            dgvOrders.Columns.Add("OrderNumber", "Order #");
            dgvOrders.Columns["OrderNumber"].Width = 140;
            dgvOrders.Columns.Add("Date", "Date/Time");
            dgvOrders.Columns["Date"].Width = 140;
            dgvOrders.Columns.Add("Amount", $"Total ({AppConfig.Current.CurrencySymbol})");
            dgvOrders.Columns["Amount"].Width = 110;
            dgvOrders.Columns.Add("Payment", "Payment");
            dgvOrders.Columns["Payment"].Width = 110;
            dgvOrders.Columns.Add("Status", "Status");
            dgvOrders.SelectionChanged += DgvOrders_SelectionChanged;
            pnlLeftCard.Controls.Add(dgvOrders);
            split.Panel1.Controls.Add(pnlLeftCard);

            // Details Card (Right)
            var pnlRightCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface,
                Padding = new Padding(16)
            };
            UITheme.ApplyCardBorder(pnlRightCard);

            lblItemsTitle = new Label
            {
                Text = "Order Items Details",
                Font = UITheme.FontSubheading,
                ForeColor = UITheme.TextDark,
                Location = new Point(16, 14),
                AutoSize = true
            };
            pnlRightCard.Controls.Add(lblItemsTitle);

            dgvItems = new DataGridView
            {
                Location = new Point(16, 46),
                Size = new Size(split.Panel2.Width - 32, 420),
                Anchor = AnchorStyles.Top | AnchorStyles.Bottom | AnchorStyles.Left | AnchorStyles.Right
            };
            UITheme.StyleTable(dgvItems);
            dgvItems.Columns.Add("Product", "Product Name");
            dgvItems.Columns.Add("Size", "Size");
            dgvItems.Columns["Size"].Width = 60;
            dgvItems.Columns.Add("Quantity", "Qty");
            dgvItems.Columns["Quantity"].Width = 60;
            dgvItems.Columns.Add("Price", "Unit Price");
            dgvItems.Columns["Price"].Width = 90;
            dgvItems.Columns.Add("Subtotal", "Subtotal");
            dgvItems.Columns["Subtotal"].Width = 90;
            pnlRightCard.Controls.Add(dgvItems);

            // Change Status Box
            var pnlStatusAction = new Panel
            {
                Dock = DockStyle.Bottom,
                Height = 60,
                BackColor = UITheme.BorderSubtle,
                Padding = new Padding(12)
            };
            pnlStatusAction.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, 0, pnlStatusAction.Width, 0);
            };

            pnlStatusAction.Controls.Add(new Label { Text = "Update Status:", Location = new Point(12, 18), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });

            cmbNewStatus = new ComboBox
            {
                Location = new Point(115, 14),
                Size = new Size(160, 28),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbNewStatus.Items.AddRange(Order.Statuses);
            cmbNewStatus.SelectedIndex = 2; // Completed
            pnlStatusAction.Controls.Add(cmbNewStatus);

            btnChangeStatus = new Button
            {
                Text = "Change Status",
                Location = new Point(285, 12),
                Size = new Size(130, 32)
            };
            UITheme.StylePrimaryButton(btnChangeStatus);
            btnChangeStatus.Click += BtnChangeStatus_Click;
            pnlStatusAction.Controls.Add(btnChangeStatus);

            pnlRightCard.Controls.Add(pnlStatusAction);
            split.Panel2.Controls.Add(pnlRightCard);

            pnlBody.Controls.Add(split);
            this.Controls.Add(pnlBody);

            this.Load += (s, e) => LoadOrders();
        }

        public void LoadOrders()
        {
            try
            {
                DateTime from = dtpFrom.Value.Date;
                DateTime to = dtpTo.Value.Date;
                string search = txtSearch.Text.Trim();

                string? status = cmbStatusFilter.SelectedIndex > 0 ? cmbStatusFilter.Text : null;
                string? payment = cmbPaymentFilter.SelectedIndex > 0 ? cmbPaymentFilter.Text : null;

                _orders = DatabaseService.Instance.GetOrders(from, to, search, status, payment);
                dgvOrders.Rows.Clear();
                foreach (var o in _orders)
                {
                    string payMeth = o.Notes.Contains("CASH", StringComparison.OrdinalIgnoreCase)
                        ? "Cash"
                        : (o.Notes.Contains("NFC", StringComparison.OrdinalIgnoreCase) ? "NFC Card" : "Online");

                    int rIdx = dgvOrders.Rows.Add(
                        o.OrderNumber,
                        o.CreatedAt.ToString("dd/MM/yyyy HH:mm"),
                        $"{AppConfig.Current.CurrencySymbol} {o.TotalAmount:F2}",
                        payMeth,
                        o.Status
                    );

                    if (o.Status.Equals("Completed", StringComparison.OrdinalIgnoreCase))
                    {
                        dgvOrders.Rows[rIdx].Cells["Status"].Style.ForeColor = UITheme.SuccessText;
                        dgvOrders.Rows[rIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                    }
                    else if (o.Status.Equals("Cancelled", StringComparison.OrdinalIgnoreCase))
                    {
                        dgvOrders.Rows[rIdx].Cells["Status"].Style.ForeColor = UITheme.DangerText;
                    }
                    else
                    {
                        dgvOrders.Rows[rIdx].Cells["Status"].Style.ForeColor = UITheme.WarningText;
                        dgvOrders.Rows[rIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                    }
                }

                if (dgvOrders.Rows.Count > 0)
                {
                    dgvOrders.Rows[0].Selected = true;
                }
            }
            catch { }
        }

        private void DgvOrders_SelectionChanged(object? sender, EventArgs e)
        {
            if (dgvOrders.CurrentRow == null || dgvOrders.CurrentRow.Index >= _orders.Count) return;

            string ordNum = dgvOrders.CurrentRow.Cells["OrderNumber"].Value?.ToString() ?? "";
            _selectedOrder = _orders.FirstOrDefault(o => o.OrderNumber == ordNum);

            if (_selectedOrder != null)
            {
                lblItemsTitle.Text = $"Items in {_selectedOrder.OrderNumber} ({AppConfig.Current.CurrencySymbol} {_selectedOrder.TotalAmount:F2})";
                var items = DatabaseService.Instance.GetOrderItems(_selectedOrder.Id);
                _selectedOrder.Items = items;

                dgvItems.Rows.Clear();
                foreach (var itm in items)
                {
                    dgvItems.Rows.Add(
                        itm.ProductName,
                        string.IsNullOrWhiteSpace(itm.Size) ? "-" : itm.Size,
                        itm.Quantity,
                        $"{AppConfig.Current.CurrencySymbol} {itm.UnitPrice:F2}",
                        $"{AppConfig.Current.CurrencySymbol} {itm.Subtotal:F2}"
                    );
                }

                int sIdx = cmbNewStatus.FindStringExact(_selectedOrder.Status);
                if (sIdx >= 0) cmbNewStatus.SelectedIndex = sIdx;
            }
        }

        private void BtnChangeStatus_Click(object? sender, EventArgs e)
        {
            if (_selectedOrder == null)
            {
                MessageBox.Show("Please select an order.", "Selection Required", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            string newStatus = cmbNewStatus.Text;
            if (newStatus == _selectedOrder.Status)
            {
                MessageBox.Show($"Order is already {newStatus}.", "Status", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            bool ok = DatabaseService.Instance.UpdateOrderStatus(_selectedOrder.Id, newStatus, $"Status changed to {newStatus} via Desktop App");
            if (ok)
            {
                _selectedOrder.Status = newStatus;
                MessageBox.Show($"Order status updated to {newStatus}!", "Updated", MessageBoxButtons.OK, MessageBoxIcon.Information);
                LoadOrders();
            }
            else
            {
                MessageBox.Show("Failed to update status in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void BtnReprintReceipt_Click(object? sender, EventArgs e)
        {
            if (_selectedOrder == null)
            {
                MessageBox.Show("Please select an order from the list.", "Selection Required", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            var cartItems = _selectedOrder.Items.Select(i => new CartItem
            {
                ProductId = i.ProductId ?? 0,
                Name = i.ProductName,
                Sku = i.Sku ?? "",
                Size = i.Size,
                Price = i.UnitPrice,
                Quantity = i.Quantity
            }).ToList();

            using var dlg = new ReceiptDialog(_selectedOrder, cartItems);
            dlg.ShowDialog(this);
        }

        private void BtnExport_Click(object? sender, EventArgs e)
        {
            if (_orders.Count == 0) return;

            using var sfd = new SaveFileDialog
            {
                Filter = "CSV File (*.csv)|*.csv",
                FileName = $"Orders_History_{DateTime.Now:yyyyMMdd_HHmm}.csv"
            };

            if (sfd.ShowDialog() == DialogResult.OK)
            {
                var sb = new StringBuilder();
                sb.AppendLine("OrderNumber,DateTime,Subtotal,Discount,TotalAmount,Status,PaymentStatus,Notes");

                foreach (var o in _orders)
                {
                    sb.AppendLine($"\"{o.OrderNumber}\",\"{o.CreatedAt:yyyy-MM-dd HH:mm:ss}\",{o.Subtotal},{o.Discount},{o.TotalAmount},\"{o.Status}\",\"{o.PaymentStatus}\",\"{o.Notes}\"");
                }

                File.WriteAllText(sfd.FileName, sb.ToString(), Encoding.UTF8);
                MessageBox.Show($"Orders report exported to:\n{sfd.FileName}", "Export Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }
    }
}
