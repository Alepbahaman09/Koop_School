using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class StockPurchaseView : UserControl
    {
        private DataGridView dgvPurchases;
        private Button btnNewPurchase;
        private Button btnMarkReceived;
        private Button btnRefresh;

        private List<StockPurchase> _purchases = new();

        public StockPurchaseView()
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
                Text = "Stock Purchases & Restocking",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 10),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblTitle);

            var lblDesc = new Label
            {
                Text = "Restock items from suppliers. Pending purchases can be marked as received to increment inventory.",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(22, 42),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblDesc);

            btnNewPurchase = new Button
            {
                Text = "➕ New Stock Purchase",
                Location = new Point(480, 24),
                Size = new Size(185, 36)
            };
            UITheme.StylePrimaryButton(btnNewPurchase);
            btnNewPurchase.Click += BtnNewPurchase_Click;
            pnlTop.Controls.Add(btnNewPurchase);

            btnMarkReceived = new Button
            {
                Text = "📥 Mark as Received",
                Location = new Point(675, 24),
                Size = new Size(175, 36)
            };
            UITheme.StyleSuccessButton(btnMarkReceived);
            btnMarkReceived.Click += BtnMarkReceived_Click;
            pnlTop.Controls.Add(btnMarkReceived);

            btnRefresh = new Button
            {
                Text = "🔄",
                Location = new Point(860, 24),
                Size = new Size(40, 36)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadPurchases();
            pnlTop.Controls.Add(btnRefresh);

            this.Controls.Add(pnlTop);

            // Grid Container Card
            var pnlGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(16),
                BackColor = UITheme.Canvas
            };

            var pnlInnerCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface
            };
            UITheme.ApplyCardBorder(pnlInnerCard);

            dgvPurchases = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvPurchases);
            dgvPurchases.Columns.Add("Id", "#");
            dgvPurchases.Columns["Id"].Width = 60;
            dgvPurchases.Columns.Add("Date", "Purchase Date");
            dgvPurchases.Columns["Date"].Width = 140;
            dgvPurchases.Columns.Add("Supplier", "Supplier");
            dgvPurchases.Columns.Add("Amount", $"Total Cost ({AppConfig.Current.CurrencySymbol})");
            dgvPurchases.Columns["Amount"].Width = 140;
            dgvPurchases.Columns.Add("Status", "Status");
            dgvPurchases.Columns["Status"].Width = 120;
            dgvPurchases.Columns.Add("Notes", "Notes / Ref");

            pnlInnerCard.Controls.Add(dgvPurchases);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            this.Load += (s, e) => LoadPurchases();
        }

        public void LoadPurchases()
        {
            try
            {
                _purchases = DatabaseService.Instance.GetStockPurchases();
                dgvPurchases.Rows.Clear();
                foreach (var p in _purchases)
                {
                    int rowIdx = dgvPurchases.Rows.Add(
                        p.Id,
                        p.PurchaseDate.ToString("dd/MM/yyyy"),
                        p.SupplierName,
                        $"{AppConfig.Current.CurrencySymbol} {p.TotalAmount:F2}",
                        p.Status.ToUpper(),
                        p.Notes
                    );

                    if (p.Status.Equals("received", StringComparison.OrdinalIgnoreCase))
                    {
                        dgvPurchases.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.SuccessText;
                        dgvPurchases.Rows[rowIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                    }
                    else
                    {
                        dgvPurchases.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.WarningText;
                        dgvPurchases.Rows[rowIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                    }
                }
            }
            catch { }
        }

        private StockPurchase? GetSelectedPurchase()
        {
            if (dgvPurchases.CurrentRow == null || dgvPurchases.CurrentRow.Index < 0) return null;
            long id = Convert.ToInt64(dgvPurchases.CurrentRow.Cells["Id"].Value);
            return _purchases.FirstOrDefault(p => p.Id == id);
        }

        private void BtnNewPurchase_Click(object? sender, EventArgs e)
        {
            using var form = new StockPurchaseCreateForm();
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadPurchases();
            }
        }

        private void BtnMarkReceived_Click(object? sender, EventArgs e)
        {
            var p = GetSelectedPurchase();
            if (p == null)
            {
                MessageBox.Show("Please select a stock purchase order from the table.", "Selection Required", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            if (p.Status.Equals("received", StringComparison.OrdinalIgnoreCase))
            {
                MessageBox.Show("This stock purchase has already been marked as received.", "Already Received", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            var confirm = MessageBox.Show(
                $"Mark purchase #{p.Id} from '{p.SupplierName}' as RECEIVED?\n\nThis will automatically increment product inventory stocks by the ordered quantities.",
                "Confirm Stock Receipt",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirm == DialogResult.Yes)
            {
                bool ok = DatabaseService.Instance.MarkStockPurchaseReceived(p.Id);
                if (ok)
                {
                    MessageBox.Show("Purchase marked as received! Product inventory stocks have been updated.", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    LoadPurchases();
                }
                else
                {
                    MessageBox.Show("Failed to update purchase status in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
    }
}
