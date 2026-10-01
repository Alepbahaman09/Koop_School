using System.Text;
using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class SuppliersView : UserControl
    {
        // Stats
        private Label lblTotalSuppliers;
        private Label lblTotalPurchases;
        private Label lblTotalSpent;

        private TextBox txtSearch;
        private DataGridView dgvSuppliers;
        private Button btnAdd;
        private Button btnEdit;
        private Button btnDelete;
        private Button btnCreatePurchase;
        private Button btnExport;
        private Button btnRefresh;

        private List<Supplier> _suppliers = new();


        public SuppliersView()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            // Stats Banner Panel
            var pnlStats = new Panel
            {
                Dock = DockStyle.Top,
                Height = 84,
                BackColor = UITheme.Canvas,
                Padding = new Padding(16, 12, 16, 6)
            };

            int statW = 250;
            int gap = 14;
            int x = 16;

            pnlStats.Controls.Add(CreateStatBadge("🚚 Total Registered Suppliers", "0", UITheme.Primary, x, out lblTotalSuppliers));
            pnlStats.Controls.Add(CreateStatBadge("📋 Total Restock Purchases", "0", UITheme.SuccessText, x + (statW + gap), out lblTotalPurchases));
            pnlStats.Controls.Add(CreateStatBadge("💳 Total Supplier Spending", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.WarningText, x + (statW + gap) * 2, out lblTotalSpent));

            this.Controls.Add(pnlStats);

            // Toolbar
            var pnlToolbar = new Panel
            {
                Dock = DockStyle.Top,
                Height = 62,
                Padding = new Padding(16, 12, 16, 12),
                BackColor = UITheme.Surface
            };
            pnlToolbar.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlToolbar.Height - 1, pnlToolbar.Width, pnlToolbar.Height - 1);
            };

            txtSearch = new TextBox
            {
                Location = new Point(16, 15),
                Size = new Size(290, 30),
                PlaceholderText = "🔍 Search company, contact or code..."
            };
            txtSearch.TextChanged += (s, e) => LoadSuppliersData();
            pnlToolbar.Controls.Add(txtSearch);

            btnAdd = new Button
            {
                Text = "➕ New Supplier",
                Location = new Point(320, 13),
                Size = new Size(135, 34)
            };
            UITheme.StylePrimaryButton(btnAdd);
            btnAdd.Click += BtnAdd_Click;
            pnlToolbar.Controls.Add(btnAdd);

            btnCreatePurchase = new Button
            {
                Text = "📥 Restock Order",
                Location = new Point(465, 13),
                Size = new Size(135, 34)
            };
            UITheme.StyleSuccessButton(btnCreatePurchase);
            btnCreatePurchase.Click += BtnCreatePurchase_Click;
            pnlToolbar.Controls.Add(btnCreatePurchase);

            btnEdit = new Button
            {
                Text = "✏️ Edit",
                Location = new Point(610, 13),
                Size = new Size(80, 34)
            };
            UITheme.StyleSecondaryButton(btnEdit);
            btnEdit.Click += BtnEdit_Click;
            pnlToolbar.Controls.Add(btnEdit);

            btnDelete = new Button
            {
                Text = "🗑️ Delete",
                Location = new Point(700, 13),
                Size = new Size(90, 34)
            };
            UITheme.StyleDangerButton(btnDelete);
            btnDelete.Click += BtnDelete_Click;
            pnlToolbar.Controls.Add(btnDelete);

            btnExport = new Button
            {
                Text = "📊 Export CSV",
                Location = new Point(800, 13),
                Size = new Size(115, 34)
            };
            UITheme.StyleSecondaryButton(btnExport);
            btnExport.Click += BtnExport_Click;
            pnlToolbar.Controls.Add(btnExport);

            btnRefresh = new Button
            {
                Text = "🔄",
                Location = new Point(925, 13),
                Size = new Size(40, 34)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadSuppliersData();
            pnlToolbar.Controls.Add(btnRefresh);

            this.Controls.Add(pnlToolbar);

            // DataGridView Container Card
            var pnlGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(16, 12, 16, 16),
                BackColor = UITheme.Canvas
            };

            var pnlInnerCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface
            };
            UITheme.ApplyCardBorder(pnlInnerCard);

            dgvSuppliers = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvSuppliers);
            dgvSuppliers.Columns.Add("Code", "Supplier Code");
            dgvSuppliers.Columns["Code"].Width = 120;
            dgvSuppliers.Columns.Add("Company", "Company Name");
            dgvSuppliers.Columns.Add("Contact", "Contact Person");
            dgvSuppliers.Columns.Add("Phone", "Phone Number");
            dgvSuppliers.Columns.Add("Email", "Email");
            dgvSuppliers.Columns.Add("Purchases", "Purchases Count");
            dgvSuppliers.Columns["Purchases"].Width = 110;
            dgvSuppliers.Columns.Add("TotalSpent", $"Total Spent ({AppConfig.Current.CurrencySymbol})");
            dgvSuppliers.Columns["TotalSpent"].Width = 130;
            dgvSuppliers.Columns.Add("Address", "Address");

            dgvSuppliers.CellDoubleClick += (s, e) => BtnEdit_Click(s, e);
            pnlInnerCard.Controls.Add(dgvSuppliers);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            this.Load += (s, e) => LoadSuppliersData();
        }

        private Panel CreateStatBadge(string title, string initialValue, Color color, int x, out Label valLabel)
        {
            var pnl = new Panel
            {
                Location = new Point(x, 4),
                Size = new Size(250, 66),
                BackColor = UITheme.Surface,
                Padding = new Padding(14, 10, 14, 10)
            };
            UITheme.ApplyCardBorder(pnl);

            var lblT = new Label
            {
                Text = title.ToUpperInvariant(),
                Font = UITheme.FontSection,
                ForeColor = UITheme.TextMuted,
                Location = new Point(12, 10),
                AutoSize = true
            };
            pnl.Controls.Add(lblT);

            valLabel = new Label
            {
                Text = initialValue,
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
                ForeColor = color,
                Location = new Point(12, 32),
                AutoSize = true
            };
            pnl.Controls.Add(valLabel);

            return pnl;
        }

        public void LoadSuppliersData()
        {
            try
            {
                // Stats
                var stats = DatabaseService.Instance.GetSupplierStats();
                lblTotalSuppliers.Text = stats.TotalSuppliers.ToString();
                lblTotalPurchases.Text = stats.TotalPurchases.ToString();
                lblTotalSpent.Text = $"{AppConfig.Current.CurrencySymbol} {stats.TotalSpent:F2}";

                string search = txtSearch.Text.Trim();
                _suppliers = DatabaseService.Instance.GetSuppliers(search);

                dgvSuppliers.Rows.Clear();
                foreach (var s in _suppliers)
                {
                    dgvSuppliers.Rows.Add(
                        s.SupplierId,
                        s.CompanyName,
                        s.ContactPerson,
                        s.Phone,
                        s.Email,
                        s.PurchasesCount,
                        $"{AppConfig.Current.CurrencySymbol} {s.TotalSpent:F2}",
                        s.Address
                    );
                }
            }
            catch { }
        }

        private Supplier? GetSelectedSupplier()
        {
            if (dgvSuppliers.CurrentRow == null || dgvSuppliers.CurrentRow.Index < 0) return null;
            string code = dgvSuppliers.CurrentRow.Cells["Code"].Value?.ToString() ?? "";
            return _suppliers.FirstOrDefault(s => s.SupplierId == code);
        }

        private void BtnAdd_Click(object? sender, EventArgs e)
        {
            using var form = new SupplierEditForm();
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadSuppliersData();
            }
        }

        private void BtnEdit_Click(object? sender, EventArgs e)
        {
            var s = GetSelectedSupplier();
            if (s == null)
            {
                MessageBox.Show("Please select a supplier to edit.", "Selection", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            using var form = new SupplierEditForm(s);
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadSuppliersData();
            }
        }

        private void BtnCreatePurchase_Click(object? sender, EventArgs e)
        {
            using var form = new StockPurchaseCreateForm();
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadSuppliersData();
            }
        }

        private void BtnDelete_Click(object? sender, EventArgs e)
        {
            var s = GetSelectedSupplier();
            if (s == null) return;

            var res = MessageBox.Show($"Are you sure you want to delete supplier '{s.CompanyName}'?", "Confirm Delete", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (res == DialogResult.Yes)
            {
                DatabaseService.Instance.DeleteSupplier(s.Id);
                LoadSuppliersData();
            }
        }

        private void BtnExport_Click(object? sender, EventArgs e)
        {
            if (_suppliers.Count == 0) return;

            using var sfd = new SaveFileDialog
            {
                Filter = "CSV File (*.csv)|*.csv",
                FileName = $"Suppliers_Report_{DateTime.Now:yyyyMMdd_HHmm}.csv"
            };

            if (sfd.ShowDialog() == DialogResult.OK)
            {
                var sb = new StringBuilder();
                sb.AppendLine("SupplierCode,CompanyName,ContactPerson,Phone,Email,PurchasesCount,TotalSpent,Address");

                foreach (var s in _suppliers)
                {
                    sb.AppendLine($"\"{s.SupplierId}\",\"{s.CompanyName}\",\"{s.ContactPerson}\",\"{s.Phone}\",\"{s.Email}\",{s.PurchasesCount},{s.TotalSpent},\"{s.Address}\"");
                }

                File.WriteAllText(sfd.FileName, sb.ToString(), Encoding.UTF8);
                MessageBox.Show($"Suppliers directory report exported to:\n{sfd.FileName}", "Export Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }
    }
}
