using System.Text;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class FinancialReportView : UserControl
    {
        private ComboBox cmbPeriod;
        private DateTimePicker dtpStart;
        private DateTimePicker dtpEnd;
        private Button btnGenerate;
        private Button btnExportCsv;

        private Label lblRevenue;
        private Label lblCost;
        private Label lblProfit;
        private Label lblCount;
        private Label lblAov;

        private DataGridView dgvReport;
        private List<Order> _reportOrders = new();

        public FinancialReportView()
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
                Text = "Financial & Sales Reports",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 10),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblTitle);

            // Period selector
            cmbPeriod = new ComboBox
            {
                Location = new Point(20, 44),
                Size = new Size(150, 30),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbPeriod.Items.AddRange(new object[] { "Today", "Yesterday", "This Week", "This Month", "All Time", "Custom Range" });
            cmbPeriod.SelectedIndex = 3; // default This Month
            cmbPeriod.SelectedIndexChanged += CmbPeriod_SelectedIndexChanged;
            pnlTop.Controls.Add(cmbPeriod);

            dtpStart = new DateTimePicker
            {
                Location = new Point(180, 44),
                Size = new Size(130, 30),
                Format = DateTimePickerFormat.Short,
                Value = new DateTime(DateTime.Today.Year, DateTime.Today.Month, 1)
            };
            pnlTop.Controls.Add(dtpStart);

            dtpEnd = new DateTimePicker
            {
                Location = new Point(320, 44),
                Size = new Size(130, 30),
                Format = DateTimePickerFormat.Short,
                Value = DateTime.Today
            };
            pnlTop.Controls.Add(dtpEnd);

            btnGenerate = new Button
            {
                Text = "Generate Report",
                Location = new Point(460, 42),
                Size = new Size(145, 34)
            };
            UITheme.StylePrimaryButton(btnGenerate);
            btnGenerate.Click += (s, e) => GenerateReport();
            pnlTop.Controls.Add(btnGenerate);

            btnExportCsv = new Button
            {
                Text = "📊 Export to CSV",
                Location = new Point(615, 42),
                Size = new Size(150, 34)
            };
            UITheme.StyleSecondaryButton(btnExportCsv);
            btnExportCsv.Click += BtnExportCsv_Click;
            pnlTop.Controls.Add(btnExportCsv);

            this.Controls.Add(pnlTop);

            // Summary Cards Panel
            var pnlSummary = new Panel
            {
                Dock = DockStyle.Top,
                Height = 100,
                Padding = new Padding(20, 12, 20, 12),
                BackColor = UITheme.Canvas
            };

            int cardW = 210;
            int gap = 14;
            int startX = 20;

            pnlSummary.Controls.Add(CreateStatCard("TOTAL REVENUE", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.Primary, startX, out lblRevenue));
            pnlSummary.Controls.Add(CreateStatCard("EST. COST (COGS)", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.WarningText, startX + (cardW + gap), out lblCost));
            pnlSummary.Controls.Add(CreateStatCard("EST. GROSS PROFIT", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.SuccessText, startX + (cardW + gap) * 2, out lblProfit));
            pnlSummary.Controls.Add(CreateStatCard("TOTAL TRANSACTIONS", "0", UITheme.PrimaryHover, startX + (cardW + gap) * 3, out lblCount));
            pnlSummary.Controls.Add(CreateStatCard("AVG BASKET SIZE", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.TextMedium, startX + (cardW + gap) * 4, out lblAov));

            this.Controls.Add(pnlSummary);

            // Grid Container Card
            var pnlGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(20, 6, 20, 20),
                BackColor = UITheme.Canvas
            };

            var pnlInnerCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface
            };
            UITheme.ApplyCardBorder(pnlInnerCard);

            dgvReport = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvReport);
            dgvReport.Columns.Add("OrderNumber", "Order Number");
            dgvReport.Columns["OrderNumber"].Width = 150;
            dgvReport.Columns.Add("Date", "Date & Time");
            dgvReport.Columns["Date"].Width = 150;
            dgvReport.Columns.Add("Subtotal", $"Subtotal ({AppConfig.Current.CurrencySymbol})");
            dgvReport.Columns["Subtotal"].Width = 120;
            dgvReport.Columns.Add("Discount", $"Discount ({AppConfig.Current.CurrencySymbol})");
            dgvReport.Columns["Discount"].Width = 110;
            dgvReport.Columns.Add("TotalAmount", $"Grand Total ({AppConfig.Current.CurrencySymbol})");
            dgvReport.Columns["TotalAmount"].Width = 130;
            dgvReport.Columns.Add("PaymentMethod", "Payment Method");
            dgvReport.Columns["PaymentMethod"].Width = 140;
            dgvReport.Columns.Add("Status", "Status");
            dgvReport.Columns["Status"].Width = 110;

            pnlInnerCard.Controls.Add(dgvReport);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            this.Load += (s, e) => GenerateReport();
        }

        private Panel CreateStatCard(string title, string initialValue, Color barColor, int x, out Label valLabel)
        {
            var pnl = new Panel
            {
                Location = new Point(x, 8),
                Size = new Size(210, 80),
                BackColor = UITheme.Surface,
                Padding = new Padding(14, 12, 14, 12)
            };
            UITheme.ApplyCardBorder(pnl);

            var topBar = new Panel { Dock = DockStyle.Top, Height = 3, BackColor = barColor };
            pnl.Controls.Add(topBar);

            var lblT = new Label
            {
                Text = title,
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                Location = new Point(12, 12),
                AutoSize = true
            };
            pnl.Controls.Add(lblT);

            valLabel = new Label
            {
                Text = initialValue,
                Font = new Font("Segoe UI", 13.5f, FontStyle.Bold),
                ForeColor = UITheme.TextDark,
                Location = new Point(12, 34),
                AutoSize = true
            };
            pnl.Controls.Add(valLabel);

            return pnl;
        }

        private void CmbPeriod_SelectedIndexChanged(object? sender, EventArgs e)
        {
            DateTime today = DateTime.Today;
            switch (cmbPeriod.SelectedIndex)
            {
                case 0: // Today
                    dtpStart.Value = today;
                    dtpEnd.Value = today;
                    break;
                case 1: // Yesterday
                    dtpStart.Value = today.AddDays(-1);
                    dtpEnd.Value = today.AddDays(-1);
                    break;
                case 2: // This Week
                    dtpStart.Value = today.AddDays(-(int)today.DayOfWeek + (int)DayOfWeek.Monday);
                    dtpEnd.Value = today;
                    break;
                case 3: // This Month
                    dtpStart.Value = new DateTime(today.Year, today.Month, 1);
                    dtpEnd.Value = today;
                    break;
                case 4: // All Time
                    dtpStart.Value = new DateTime(2020, 1, 1);
                    dtpEnd.Value = today;
                    break;
            }
        }

        public void GenerateReport()
        {
            try
            {
                DateTime start = dtpStart.Value.Date;
                DateTime end = dtpEnd.Value.Date;

                _reportOrders = DatabaseService.Instance.GetOrders(start, end);

                decimal totalRevenue = _reportOrders.Sum(o => o.TotalAmount);
                int orderCount = _reportOrders.Count;
                decimal aov = orderCount > 0 ? totalRevenue / orderCount : 0;

                // Estimate COGS based on approx 65% cost or standard margins
                decimal estimatedCost = totalRevenue * 0.60m;
                decimal estimatedProfit = totalRevenue - estimatedCost;

                lblRevenue.Text = $"{AppConfig.Current.CurrencySymbol} {totalRevenue:F2}";
                lblCost.Text = $"{AppConfig.Current.CurrencySymbol} {estimatedCost:F2}";
                lblProfit.Text = $"{AppConfig.Current.CurrencySymbol} {estimatedProfit:F2}";
                lblCount.Text = orderCount.ToString();
                lblAov.Text = $"{AppConfig.Current.CurrencySymbol} {aov:F2}";

                dgvReport.Rows.Clear();
                foreach (var o in _reportOrders)
                {
                    dgvReport.Rows.Add(
                        o.OrderNumber,
                        o.CreatedAt.ToString("dd/MM/yyyy HH:mm"),
                        $"{AppConfig.Current.CurrencySymbol} {o.Subtotal:F2}",
                        $"{AppConfig.Current.CurrencySymbol} {o.Discount:F2}",
                        $"{AppConfig.Current.CurrencySymbol} {o.TotalAmount:F2}",
                        o.Notes.Contains("CASH") ? "CASH" : (o.Notes.Contains("NFC") ? "NFC CARD" : "PAID"),
                        o.Status
                    );
                }
            }
            catch { }
        }

        private void BtnExportCsv_Click(object? sender, EventArgs e)
        {
            if (_reportOrders.Count == 0)
            {
                MessageBox.Show("No records to export.", "Empty Report", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            using var sfd = new SaveFileDialog
            {
                Filter = "CSV File (*.csv)|*.csv",
                FileName = $"KoopSchool_SalesReport_{DateTime.Now:yyyyMMdd_HHmm}.csv"
            };

            if (sfd.ShowDialog() == DialogResult.OK)
            {
                var sb = new StringBuilder();
                sb.AppendLine("OrderNumber,Date,Subtotal,Discount,TotalAmount,PaymentStatus,Notes");

                foreach (var o in _reportOrders)
                {
                    sb.AppendLine($"\"{o.OrderNumber}\",\"{o.CreatedAt:yyyy-MM-dd HH:mm:ss}\",{o.Subtotal},{o.Discount},{o.TotalAmount},\"{o.PaymentStatus}\",\"{o.Notes}\"");
                }

                File.WriteAllText(sfd.FileName, sb.ToString(), Encoding.UTF8);
                MessageBox.Show($"Report successfully exported to:\n{sfd.FileName}", "Export Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }
    }
}
