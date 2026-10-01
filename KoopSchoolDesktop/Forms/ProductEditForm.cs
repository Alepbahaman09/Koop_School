using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class ProductEditForm : Form
    {
        private TextBox txtName;
        private TextBox txtSku;
        private ComboBox cmbCategory;
        private ComboBox cmbSupplier;
        private NumericUpDown numPrice;
        private NumericUpDown numCostPrice;
        private NumericUpDown numStock;
        private NumericUpDown numMinStock;
        private TextBox txtUnit;
        private ComboBox cmbPurchaseUnit;
        private NumericUpDown numUnitsPerCarton;
        private TextBox txtDescription;
        private CheckBox chkActive;
        private CheckBox chkHasSizes;
        private Panel pnlSizes;
        private DataGridView dgvSizes;
        private Button btnSave;
        private Button btnCancel;

        public Product Product { get; private set; }

        public ProductEditForm(Product? existing = null)
        {
            Product = existing ?? new Product();
            InitializeComponent();
            LoadDropdownData();
            PopulateFields();
        }

        private void InitializeComponent()
        {
            this.Text = Product.Id == 0 ? "Add New Product" : "Edit Product";
            this.Size = new Size(680, 680);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            int labelX = 25;
            int inputX = 175;
            int inputWidth = 450;
            int y = 20;
            int spacing = 38;

            // Name
            this.Controls.Add(new Label { Text = "Product Name *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            txtName = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 28) };
            this.Controls.Add(txtName);

            // Barcode
            y += spacing;
            this.Controls.Add(new Label { Text = "Barcode / SKU *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            txtSku = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 28) };
            this.Controls.Add(txtSku);

            // Category & Supplier in 1 row
            y += spacing;
            this.Controls.Add(new Label { Text = "Category:", Location = new Point(labelX, y + 4), AutoSize = true });
            cmbCategory = new ComboBox { Location = new Point(inputX, y), Size = new Size(210, 28), DropDownStyle = ComboBoxStyle.DropDownList };
            this.Controls.Add(cmbCategory);

            this.Controls.Add(new Label { Text = "Supplier:", Location = new Point(inputX + 225, y + 4), AutoSize = true });
            cmbSupplier = new ComboBox { Location = new Point(inputX + 285, y), Size = new Size(165, 28), DropDownStyle = ComboBoxStyle.DropDownList };
            this.Controls.Add(cmbSupplier);

            // Price & Cost Price
            y += spacing;
            this.Controls.Add(new Label { Text = $"Selling Price ({AppConfig.Current.CurrencySymbol}) *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            numPrice = new NumericUpDown { Location = new Point(inputX, y), Size = new Size(130, 28), DecimalPlaces = 2, Maximum = 999999, Minimum = 0 };
            this.Controls.Add(numPrice);

            this.Controls.Add(new Label { Text = $"Cost Price ({AppConfig.Current.CurrencySymbol}):", Location = new Point(inputX + 150, y + 4), AutoSize = true });
            numCostPrice = new NumericUpDown { Location = new Point(inputX + 285, y), Size = new Size(165, 28), DecimalPlaces = 2, Maximum = 999999, Minimum = 0 };
            this.Controls.Add(numCostPrice);

            // Stock & Min Stock Alert
            y += spacing;
            this.Controls.Add(new Label { Text = "Total Stock Qty:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            numStock = new NumericUpDown { Location = new Point(inputX, y), Size = new Size(130, 28), Maximum = 999999, Minimum = 0 };
            this.Controls.Add(numStock);

            this.Controls.Add(new Label { Text = "Min Alert Level:", Location = new Point(inputX + 150, y + 4), AutoSize = true });
            numMinStock = new NumericUpDown { Location = new Point(inputX + 285, y), Size = new Size(165, 28), Maximum = 9999, Minimum = 0, Value = 5 };
            this.Controls.Add(numMinStock);

            // Units (Retail unit vs Wholesale Purchase Unit & Pieces Per Carton)
            y += spacing;
            this.Controls.Add(new Label { Text = "Sale Unit:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtUnit = new TextBox { Location = new Point(inputX, y), Size = new Size(90, 28), Text = "pcs" };
            this.Controls.Add(txtUnit);

            this.Controls.Add(new Label { Text = "Purchase Unit:", Location = new Point(inputX + 105, y + 4), AutoSize = true });
            cmbPurchaseUnit = new ComboBox { Location = new Point(inputX + 195, y), Size = new Size(100, 28), DropDownStyle = ComboBoxStyle.DropDownList };
            cmbPurchaseUnit.Items.AddRange(new object[] { "Unit", "Carton", "Pack", "Box", "Dozen", "Bottle", "Bag" });
            cmbPurchaseUnit.SelectedIndex = 0;
            this.Controls.Add(cmbPurchaseUnit);

            this.Controls.Add(new Label { Text = "Pcs/Carton:", Location = new Point(inputX + 310, y + 4), AutoSize = true });
            numUnitsPerCarton = new NumericUpDown { Location = new Point(inputX + 385, y), Size = new Size(65, 28), Minimum = 1, Maximum = 9999, Value = 1 };
            this.Controls.Add(numUnitsPerCarton);

            // Description
            y += spacing;
            this.Controls.Add(new Label { Text = "Description:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtDescription = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 40), Multiline = true };
            this.Controls.Add(txtDescription);

            // Active Checkbox
            y += 48;
            chkActive = new CheckBox { Text = "Published on POS (Active for sale)", Location = new Point(inputX, y), AutoSize = true, Checked = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) };
            this.Controls.Add(chkActive);

            // Has Sizes Checkbox
            y += 28;
            chkHasSizes = new CheckBox { Text = "Product has Size variations (Uniforms / Shoes / Apparel)", Location = new Point(inputX, y), AutoSize = true };
            chkHasSizes.CheckedChanged += (s, e) => ToggleSizesGrid();
            this.Controls.Add(chkHasSizes);

            // Sizes Panel
            y += 28;
            pnlSizes = new Panel
            {
                Location = new Point(inputX, y),
                Size = new Size(inputWidth, 140),
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Visible = false
            };

            dgvSizes = new DataGridView
            {
                Dock = DockStyle.Fill,
                BackgroundColor = Color.White,
                BorderStyle = BorderStyle.None,
                RowHeadersVisible = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill
            };
            dgvSizes.Columns.Add("Size", "Size");
            dgvSizes.Columns["Size"].ReadOnly = true;
            dgvSizes.Columns["Size"].Width = 80;
            dgvSizes.Columns.Add("Stock", "Stock Qty");
            dgvSizes.Columns.Add("Price", "Price Override");
            dgvSizes.CellValueChanged += (s, e) => RecalculateTotalStockFromSizes();

            pnlSizes.Controls.Add(dgvSizes);
            this.Controls.Add(pnlSizes);

            // Action Buttons
            y += 150;
            btnSave = new Button
            {
                Text = "💾 Save Product",
                Location = new Point(inputX, y),
                Size = new Size(180, 42),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnSave.FlatAppearance.BorderSize = 0;
            btnSave.Click += BtnSave_Click;

            btnCancel = new Button
            {
                Text = "Cancel",
                Location = new Point(inputX + 195, y),
                Size = new Size(120, 42),
                BackColor = Color.FromArgb(226, 232, 240),
                ForeColor = Color.FromArgb(71, 85, 105),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = 0;
            btnCancel.Click += (s, e) => this.Close();

            this.AcceptButton = btnSave;
            this.CancelButton = btnCancel;
            this.Controls.AddRange(new Control[] { btnSave, btnCancel });
        }

        private void ToggleSizesGrid()
        {
            pnlSizes.Visible = chkHasSizes.Checked;
            if (chkHasSizes.Checked && dgvSizes.Rows.Count == 0)
            {
                foreach (var sz in ProductSize.AvailableSizes)
                {
                    dgvSizes.Rows.Add(sz, "0", "");
                }
            }
        }

        private void RecalculateTotalStockFromSizes()
        {
            if (!chkHasSizes.Checked) return;
            int total = 0;
            foreach (DataGridViewRow row in dgvSizes.Rows)
            {
                if (int.TryParse(row.Cells["Stock"].Value?.ToString(), out int q))
                {
                    total += q;
                }
            }
            if (total > 0) numStock.Value = total;
        }

        private void LoadDropdownData()
        {
            try
            {
                var categories = DatabaseService.Instance.GetCategories();
                cmbCategory.Items.Clear();
                cmbCategory.Items.Add(new Category { Id = 0, Name = "-- Uncategorized --" });
                foreach (var c in categories) cmbCategory.Items.Add(c);
                cmbCategory.SelectedIndex = 0;

                var suppliers = DatabaseService.Instance.GetSuppliers();
                cmbSupplier.Items.Clear();
                cmbSupplier.Items.Add(new Supplier { Id = 0, CompanyName = "-- None --" });
                foreach (var s in suppliers) cmbSupplier.Items.Add(s);
                cmbSupplier.SelectedIndex = 0;
            }
            catch { }
        }

        private void PopulateFields()
        {
            if (Product.Id != 0)
            {
                txtName.Text = Product.Name;
                txtSku.Text = Product.Sku;
                numPrice.Value = Product.Price;
                numCostPrice.Value = Product.CostPrice;
                numStock.Value = Product.StockQuantity;
                numMinStock.Value = Product.MinStockLevel;
                txtUnit.Text = string.IsNullOrWhiteSpace(Product.Unit) ? "pcs" : Product.Unit;
                txtDescription.Text = Product.Description ?? "";
                chkActive.Checked = Product.IsActive;

                int pIdx = cmbPurchaseUnit.FindStringExact(Product.PurchaseUnit);
                if (pIdx >= 0) cmbPurchaseUnit.SelectedIndex = pIdx;
                numUnitsPerCarton.Value = Math.Max(1, Product.UnitsPerCarton);

                // Select category
                for (int i = 0; i < cmbCategory.Items.Count; i++)
                {
                    if (cmbCategory.Items[i] is Category c && c.Id == Product.CategoryId)
                    {
                        cmbCategory.SelectedIndex = i;
                        break;
                    }
                }

                // Select supplier
                for (int i = 0; i < cmbSupplier.Items.Count; i++)
                {
                    if (cmbSupplier.Items[i] is Supplier s && s.Id == Product.SupplierId)
                    {
                        cmbSupplier.SelectedIndex = i;
                        break;
                    }
                }

                // Load sizes if any
                var sizes = DatabaseService.Instance.GetProductSizes(Product.Id);
                if (sizes.Count > 0)
                {
                    chkHasSizes.Checked = true;
                    dgvSizes.Rows.Clear();
                    foreach (var sz in ProductSize.AvailableSizes)
                    {
                        var matching = sizes.FirstOrDefault(s => s.Size.Equals(sz, StringComparison.OrdinalIgnoreCase));
                        dgvSizes.Rows.Add(sz, matching?.StockQuantity ?? 0, matching?.Price.HasValue == true ? matching.Price.Value.ToString("F2") : "");
                    }
                }
            }
            else
            {
                txtSku.Text = $"888{Random.Shared.Next(1000000, 9999999)}";
            }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            if (string.IsNullOrWhiteSpace(txtName.Text))
            {
                MessageBox.Show("Please enter the product name.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtName.Focus();
                return;
            }

            if (string.IsNullOrWhiteSpace(txtSku.Text))
            {
                MessageBox.Show("Please enter a barcode / SKU.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtSku.Focus();
                return;
            }

            Product.Name = txtName.Text.Trim();
            Product.Sku = txtSku.Text.Trim();
            Product.Price = numPrice.Value;
            Product.CostPrice = numCostPrice.Value;
            Product.StockQuantity = (int)numStock.Value;
            Product.MinStockLevel = (int)numMinStock.Value;
            Product.Unit = txtUnit.Text.Trim();
            Product.PurchaseUnit = cmbPurchaseUnit.Text;
            Product.UnitsPerCarton = (int)numUnitsPerCarton.Value;
            Product.Description = txtDescription.Text.Trim();
            Product.IsActive = chkActive.Checked;

            if (cmbCategory.SelectedItem is Category c && c.Id > 0)
                Product.CategoryId = c.Id;
            else
                Product.CategoryId = null;

            if (cmbSupplier.SelectedItem is Supplier s && s.Id > 0)
                Product.SupplierId = s.Id;
            else
                Product.SupplierId = null;

            // Sizes collection
            Product.Sizes.Clear();
            if (chkHasSizes.Checked)
            {
                foreach (DataGridViewRow row in dgvSizes.Rows)
                {
                    string sz = row.Cells["Size"].Value?.ToString() ?? "";
                    int.TryParse(row.Cells["Stock"].Value?.ToString(), out int q);
                    decimal? prc = null;
                    if (decimal.TryParse(row.Cells["Price"].Value?.ToString(), out decimal p)) prc = p;

                    if (q > 0 || prc.HasValue)
                    {
                        Product.Sizes.Add(new ProductSize
                        {
                            Size = sz,
                            StockQuantity = q,
                            Price = prc
                        });
                    }
                }
            }

            try
            {
                long id = DatabaseService.Instance.SaveProduct(Product);
                Product.Id = id;
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Failed to save product: {ex.Message}", "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
