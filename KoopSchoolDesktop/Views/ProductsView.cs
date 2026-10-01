using System.Text;
using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class ProductsView : UserControl
    {
        // Top Stats
        private Label lblTotal;
        private Label lblLow;
        private Label lblOut;
        private Label lblHidden;
        private Label lblValue;
        private Label lblUnits;

        // Filters
        private TextBox txtSearch;
        private ComboBox cmbCategory;
        private ComboBox cmbSupplier;
        private ComboBox cmbStockStatus;

        // Action Buttons
        private Button btnAdd;
        private Button btnEdit;
        private Button btnAdjustStock;
        private Button btnDelete;
        private Button btnExport;
        private Button btnRefresh;

        private DataGridView dgvProducts;
        private List<Product> _products = new();

        public ProductsView()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            // Top Stats Banner Panel
            var pnlStats = new Panel
            {
                Dock = DockStyle.Top,
                Height = 84,
                BackColor = UITheme.Canvas,
                Padding = new Padding(16, 12, 16, 6)
            };

            int statW = 195;
            int gap = 12;
            int x = 16;

            pnlStats.Controls.Add(CreateStatBadge("📦 Total Products", "0", UITheme.Primary, x, out lblTotal));
            pnlStats.Controls.Add(CreateStatBadge("⚠️ Low Stock", "0", UITheme.WarningText, x + (statW + gap), out lblLow));
            pnlStats.Controls.Add(CreateStatBadge("🚫 Out of Stock", "0", UITheme.DangerText, x + (statW + gap) * 2, out lblOut));
            pnlStats.Controls.Add(CreateStatBadge("👁️ Inactive / Hidden", "0", UITheme.TextMuted, x + (statW + gap) * 3, out lblHidden));
            pnlStats.Controls.Add(CreateStatBadge("💰 Inventory Value", $"{AppConfig.Current.CurrencySymbol} 0.00", UITheme.SuccessText, x + (statW + gap) * 4, out lblValue));
            pnlStats.Controls.Add(CreateStatBadge("🔢 Total Stock Units", "0", UITheme.PrimaryHover, x + (statW + gap) * 5, out lblUnits));

            this.Controls.Add(pnlStats);

            // Filters & Actions Bar
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
                Size = new Size(220, 30),
                PlaceholderText = "🔍 Search product or barcode..."
            };
            txtSearch.TextChanged += (s, e) => ApplyFilters();
            pnlToolbar.Controls.Add(txtSearch);

            cmbCategory = new ComboBox
            {
                Location = new Point(246, 15),
                Size = new Size(160, 30),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbCategory.SelectedIndexChanged += (s, e) => ApplyFilters();
            pnlToolbar.Controls.Add(cmbCategory);

            cmbSupplier = new ComboBox
            {
                Location = new Point(416, 15),
                Size = new Size(160, 30),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbSupplier.SelectedIndexChanged += (s, e) => ApplyFilters();
            pnlToolbar.Controls.Add(cmbSupplier);

            cmbStockStatus = new ComboBox
            {
                Location = new Point(586, 15),
                Size = new Size(130, 30),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbStockStatus.Items.AddRange(new object[] { "All Stock", "Low Stock", "Out of Stock", "Active Only", "Hidden Only" });
            cmbStockStatus.SelectedIndex = 0;
            cmbStockStatus.SelectedIndexChanged += (s, e) => ApplyFilters();
            pnlToolbar.Controls.Add(cmbStockStatus);

            // Right action buttons
            btnAdd = new Button
            {
                Text = "➕ New Product",
                Location = new Point(730, 13),
                Size = new Size(130, 34)
            };
            UITheme.StylePrimaryButton(btnAdd);
            btnAdd.Click += BtnAdd_Click;
            pnlToolbar.Controls.Add(btnAdd);

            btnAdjustStock = new Button
            {
                Text = "📦 Adjust Stock",
                Location = new Point(868, 13),
                Size = new Size(125, 34)
            };
            UITheme.StyleSuccessButton(btnAdjustStock);
            btnAdjustStock.Click += BtnAdjustStock_Click;
            pnlToolbar.Controls.Add(btnAdjustStock);

            btnEdit = new Button
            {
                Text = "✏️ Edit",
                Location = new Point(1000, 13),
                Size = new Size(80, 34)
            };
            UITheme.StyleSecondaryButton(btnEdit);
            btnEdit.Click += BtnEdit_Click;
            pnlToolbar.Controls.Add(btnEdit);

            btnDelete = new Button
            {
                Text = "🗑️",
                Location = new Point(1088, 13),
                Size = new Size(42, 34)
            };
            UITheme.StyleDangerButton(btnDelete);
            btnDelete.Click += BtnDelete_Click;
            pnlToolbar.Controls.Add(btnDelete);

            btnExport = new Button
            {
                Text = "📊 Export CSV",
                Location = new Point(1138, 13),
                Size = new Size(115, 34)
            };
            UITheme.StyleSecondaryButton(btnExport);
            btnExport.Click += BtnExport_Click;
            pnlToolbar.Controls.Add(btnExport);

            btnRefresh = new Button
            {
                Text = "🔄",
                Location = new Point(1260, 13),
                Size = new Size(40, 34)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadProductsData();
            pnlToolbar.Controls.Add(btnRefresh);

            this.Controls.Add(pnlToolbar);

            // Grid Container Card
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

            dgvProducts = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvProducts);

            dgvProducts.Columns.Add("Id", "ID");
            dgvProducts.Columns["Id"].Width = 50;
            dgvProducts.Columns.Add("Sku", "Barcode / SKU");
            dgvProducts.Columns["Sku"].Width = 140;
            dgvProducts.Columns.Add("Name", "Product Name");
            dgvProducts.Columns.Add("Category", "Category");
            dgvProducts.Columns.Add("Supplier", "Supplier");
            dgvProducts.Columns.Add("Price", $"Selling ({AppConfig.Current.CurrencySymbol})");
            dgvProducts.Columns["Price"].Width = 110;
            dgvProducts.Columns.Add("CostPrice", $"Cost ({AppConfig.Current.CurrencySymbol})");
            dgvProducts.Columns["CostPrice"].Width = 100;
            dgvProducts.Columns.Add("Stock", "In Stock");
            dgvProducts.Columns["Stock"].Width = 90;
            dgvProducts.Columns.Add("MinStock", "Min Alert");
            dgvProducts.Columns["MinStock"].Width = 85;
            dgvProducts.Columns.Add("Unit", "Unit");
            dgvProducts.Columns["Unit"].Width = 70;
            dgvProducts.Columns.Add("Status", "Status");
            dgvProducts.Columns["Status"].Width = 90;

            dgvProducts.CellDoubleClick += (s, e) => BtnEdit_Click(s, e);
            pnlInnerCard.Controls.Add(dgvProducts);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            this.Load += (s, e) => LoadProductsData();
        }

        private Panel CreateStatBadge(string title, string initialValue, Color color, int x, out Label valLabel)
        {
            var pnl = new Panel
            {
                Location = new Point(x, 4),
                Size = new Size(195, 66),
                BackColor = UITheme.Surface,
                Padding = new Padding(12, 10, 12, 10)
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

        public void LoadProductsData()
        {
            try
            {
                // Categories
                var cats = DatabaseService.Instance.GetCategories();
                cmbCategory.Items.Clear();
                cmbCategory.Items.Add(new Category { Id = 0, Name = "All Categories" });
                foreach (var c in cats) cmbCategory.Items.Add(c);
                cmbCategory.SelectedIndex = 0;

                // Suppliers
                var sups = DatabaseService.Instance.GetSuppliers();
                cmbSupplier.Items.Clear();
                cmbSupplier.Items.Add(new Supplier { Id = 0, CompanyName = "All Suppliers" });
                foreach (var s in sups) cmbSupplier.Items.Add(s);
                cmbSupplier.SelectedIndex = 0;

                // Stats
                var stats = DatabaseService.Instance.GetProductStats();
                lblTotal.Text = stats.Total.ToString();
                lblLow.Text = stats.LowStock.ToString();
                lblOut.Text = stats.OutOfStock.ToString();
                lblHidden.Text = stats.Hidden.ToString();
                lblValue.Text = $"{AppConfig.Current.CurrencySymbol} {stats.InventoryValue:F2}";
                lblUnits.Text = stats.TotalUnits.ToString();

                _products = DatabaseService.Instance.GetProducts(activeOnly: false);
                ApplyFilters();
            }
            catch { }
        }

        private void ApplyFilters()
        {
            string search = txtSearch.Text.Trim().ToLower();
            long? catId = null;
            if (cmbCategory.SelectedItem is Category c && c.Id > 0) catId = c.Id;

            long? supId = null;
            if (cmbSupplier.SelectedItem is Supplier s && s.Id > 0) supId = s.Id;

            int statusIdx = cmbStockStatus.SelectedIndex;

            var filtered = _products.Where(p =>
                (string.IsNullOrEmpty(search) || p.Name.ToLower().Contains(search) || p.Sku.ToLower().Contains(search)) &&
                (!catId.HasValue || p.CategoryId == catId) &&
                (!supId.HasValue || p.SupplierId == supId) &&
                (statusIdx switch
                {
                    1 => p.IsLowStock,
                    2 => p.IsOutOfStock,
                    3 => p.IsActive,
                    4 => !p.IsActive,
                    _ => true
                })
            ).ToList();

            dgvProducts.Rows.Clear();
            foreach (var p in filtered)
            {
                int rowIdx = dgvProducts.Rows.Add(
                    p.Id,
                    p.Sku,
                    p.Name,
                    p.CategoryName,
                    p.SupplierName,
                    $"{AppConfig.Current.CurrencySymbol} {p.Price:F2}",
                    $"{AppConfig.Current.CurrencySymbol} {p.CostPrice:F2}",
                    p.StockQuantity,
                    p.MinStockLevel,
                    p.Unit,
                    p.IsActive ? "● Active" : "○ Hidden"
                );

                if (!p.IsActive)
                {
                    dgvProducts.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.TextMuted;
                }
                else
                {
                    dgvProducts.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.SuccessText;
                    dgvProducts.Rows[rowIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                }

                if (p.IsOutOfStock)
                {
                    dgvProducts.Rows[rowIdx].Cells["Stock"].Style.ForeColor = UITheme.DangerText;
                    dgvProducts.Rows[rowIdx].Cells["Stock"].Style.Font = UITheme.FontBodyBold;
                }
                else if (p.IsLowStock)
                {
                    dgvProducts.Rows[rowIdx].Cells["Stock"].Style.ForeColor = UITheme.WarningText;
                    dgvProducts.Rows[rowIdx].Cells["Stock"].Style.Font = UITheme.FontBodyBold;
                }
                else
                {
                    dgvProducts.Rows[rowIdx].Cells["Stock"].Style.ForeColor = UITheme.SuccessText;
                }
            }
        }

        private Product? GetSelectedProduct()
        {
            if (dgvProducts.CurrentRow == null || dgvProducts.CurrentRow.Index < 0) return null;
            long id = Convert.ToInt64(dgvProducts.CurrentRow.Cells["Id"].Value);
            return _products.FirstOrDefault(p => p.Id == id);
        }

        private void BtnAdd_Click(object? sender, EventArgs e)
        {
            using var form = new ProductEditForm();
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadProductsData();
            }
        }

        private void BtnEdit_Click(object? sender, EventArgs e)
        {
            var p = GetSelectedProduct();
            if (p == null)
            {
                MessageBox.Show("Please select a product to edit.", "Selection", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            using var form = new ProductEditForm(p);
            if (form.ShowDialog(this) == DialogResult.OK)
            {
                LoadProductsData();
            }
        }

        private void BtnAdjustStock_Click(object? sender, EventArgs e)
        {
            var p = GetSelectedProduct();
            if (p == null)
            {
                MessageBox.Show("Please select a product to adjust stock.", "Selection", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            using var dlg = new StockAdjustDialog(p);
            if (dlg.ShowDialog(this) == DialogResult.OK)
            {
                LoadProductsData();
            }
        }

        private void BtnExport_Click(object? sender, EventArgs e)
        {
            if (_products.Count == 0) return;

            using var sfd = new SaveFileDialog
            {
                Filter = "CSV File (*.csv)|*.csv",
                FileName = $"Products_Inventory_Report_{DateTime.Now:yyyyMMdd_HHmm}.csv"
            };

            if (sfd.ShowDialog() == DialogResult.OK)
            {
                var sb = new StringBuilder();
                sb.AppendLine("ID,SKU,Name,Category,Supplier,SellingPrice,CostPrice,StockQuantity,MinStockAlert,Unit,Status");

                foreach (var p in _products)
                {
                    sb.AppendLine($"{p.Id},\"{p.Sku}\",\"{p.Name}\",\"{p.CategoryName}\",\"{p.SupplierName}\",{p.Price},{p.CostPrice},{p.StockQuantity},{p.MinStockLevel},\"{p.Unit}\",\"{(p.IsActive ? "Active" : "Hidden")}\"");
                }

                File.WriteAllText(sfd.FileName, sb.ToString(), Encoding.UTF8);
                MessageBox.Show($"Product catalog report exported to:\n{sfd.FileName}", "Export Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void BtnDelete_Click(object? sender, EventArgs e)
        {
            var p = GetSelectedProduct();
            if (p == null) return;

            var res = MessageBox.Show($"Are you sure you want to hide/deactivate product '{p.Name}'?", "Confirm Deactivate", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (res == DialogResult.Yes)
            {
                DatabaseService.Instance.DeleteProduct(p.Id);
                LoadProductsData();
            }
        }
    }
}
