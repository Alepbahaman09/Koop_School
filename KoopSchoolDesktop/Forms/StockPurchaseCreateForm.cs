using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class StockPurchaseCreateForm : Form
    {
        private ComboBox cmbSupplier;
        private DateTimePicker dtpPurchaseDate;
        private TextBox txtNotes;
        private CheckBox chkMarkReceived;
        private DataGridView dgvItems;
        private ComboBox cmbProducts;
        private NumericUpDown numQty;
        private NumericUpDown numCostPrice;
        private NumericUpDown numSellPrice;
        private Button btnAddItem;
        private Button btnRemoveItem;
        private Label lblTotal;
        private Button btnSave;
        private Button btnCancel;

        private readonly List<StockPurchaseItem> _items = new();
        private List<Product> _allProducts = new();

        public StockPurchaseCreateForm()
        {
            InitializeComponent();
            LoadSuppliersAndProducts();
        }

        private void InitializeComponent()
        {
            this.Text = "New Stock Purchase / Restock Order";
            this.Size = new Size(860, 640);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            // Header Section
            var pnlTop = new Panel
            {
                Location = new Point(20, 15),
                Size = new Size(805, 120),
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle
            };

            pnlTop.Controls.Add(new Label { Text = "Supplier *:", Location = new Point(15, 18), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            cmbSupplier = new ComboBox { Location = new Point(100, 15), Size = new Size(260, 30), DropDownStyle = ComboBoxStyle.DropDownList };
            pnlTop.Controls.Add(cmbSupplier);

            pnlTop.Controls.Add(new Label { Text = "Date:", Location = new Point(400, 18), AutoSize = true });
            dtpPurchaseDate = new DateTimePicker { Location = new Point(450, 15), Size = new Size(160, 30), Format = DateTimePickerFormat.Short };
            pnlTop.Controls.Add(dtpPurchaseDate);

            pnlTop.Controls.Add(new Label { Text = "Notes:", Location = new Point(15, 58), AutoSize = true });
            txtNotes = new TextBox { Location = new Point(100, 55), Size = new Size(510, 30), PlaceholderText = "e.g. Invoice # / Delivery Order #" };
            pnlTop.Controls.Add(txtNotes);

            chkMarkReceived = new CheckBox
            {
                Text = "Mark as Received (auto-restock inventory quantity)",
                Location = new Point(100, 90),
                AutoSize = true,
                Checked = true,
                Font = new Font("Segoe UI", 9.5f, FontStyle.Bold),
                ForeColor = Color.FromArgb(22, 163, 74)
            };
            pnlTop.Controls.Add(chkMarkReceived);
            this.Controls.Add(pnlTop);

            // Add Item Controls
            var pnlAddItem = new Panel
            {
                Location = new Point(20, 145),
                Size = new Size(805, 65),
                BackColor = Color.FromArgb(241, 245, 249),
                BorderStyle = BorderStyle.FixedSingle
            };

            pnlAddItem.Controls.Add(new Label { Text = "Product:", Location = new Point(10, 8), AutoSize = true });
            cmbProducts = new ComboBox { Location = new Point(10, 28), Size = new Size(260, 30), DropDownStyle = ComboBoxStyle.DropDownList };
            cmbProducts.SelectedIndexChanged += CmbProducts_SelectedIndexChanged;
            pnlAddItem.Controls.Add(cmbProducts);

            pnlAddItem.Controls.Add(new Label { Text = "Qty:", Location = new Point(285, 8), AutoSize = true });
            numQty = new NumericUpDown { Location = new Point(285, 28), Size = new Size(80, 30), Minimum = 1, Maximum = 99999, Value = 10 };
            pnlAddItem.Controls.Add(numQty);

            pnlAddItem.Controls.Add(new Label { Text = $"Buy Cost ({AppConfig.Current.CurrencySymbol}):", Location = new Point(380, 8), AutoSize = true });
            numCostPrice = new NumericUpDown { Location = new Point(380, 28), Size = new Size(110, 30), DecimalPlaces = 2, Maximum = 99999, Value = 1 };
            pnlAddItem.Controls.Add(numCostPrice);

            pnlAddItem.Controls.Add(new Label { Text = $"Selling Price ({AppConfig.Current.CurrencySymbol}):", Location = new Point(505, 8), AutoSize = true });
            numSellPrice = new NumericUpDown { Location = new Point(505, 28), Size = new Size(110, 30), DecimalPlaces = 2, Maximum = 99999, Value = 2 };
            pnlAddItem.Controls.Add(numSellPrice);

            btnAddItem = new Button
            {
                Text = "➕ Add Item",
                Location = new Point(635, 23),
                Size = new Size(100, 35),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnAddItem.FlatAppearance.BorderSize = 0;
            btnAddItem.Click += BtnAddItem_Click;
            pnlAddItem.Controls.Add(btnAddItem);

            btnRemoveItem = new Button
            {
                Text = "🗑️",
                Location = new Point(745, 23),
                Size = new Size(45, 35),
                BackColor = Color.FromArgb(239, 68, 68),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnRemoveItem.FlatAppearance.BorderSize = 0;
            btnRemoveItem.Click += BtnRemoveItem_Click;
            pnlAddItem.Controls.Add(btnRemoveItem);

            this.Controls.Add(pnlAddItem);

            // DataGridView for purchase items
            dgvItems = new DataGridView
            {
                Location = new Point(20, 220),
                Size = new Size(805, 290),
                BackgroundColor = Color.White,
                BorderStyle = BorderStyle.Fixed3D,
                RowHeadersVisible = false,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
                MultiSelect = false,
                AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill
            };

            dgvItems.Columns.Add("ProductName", "Product");
            dgvItems.Columns.Add("Sku", "SKU / Barcode");
            dgvItems.Columns.Add("Quantity", "Qty");
            dgvItems.Columns.Add("CostPrice", $"Cost ({AppConfig.Current.CurrencySymbol})");
            dgvItems.Columns.Add("SellingPrice", $"Selling ({AppConfig.Current.CurrencySymbol})");
            dgvItems.Columns.Add("Subtotal", $"Subtotal ({AppConfig.Current.CurrencySymbol})");
            this.Controls.Add(dgvItems);

            // Bottom bar
            lblTotal = new Label
            {
                Text = $"Grand Total: {AppConfig.Current.CurrencySymbol} 0.00",
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
                ForeColor = Color.FromArgb(30, 41, 59),
                Location = new Point(20, 530),
                AutoSize = true
            };
            this.Controls.Add(lblTotal);

            btnSave = new Button
            {
                Text = "💾 Save Stock Purchase",
                Location = new Point(560, 530),
                Size = new Size(160, 42),
                BackColor = Color.FromArgb(22, 163, 74),
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
                Location = new Point(730, 530),
                Size = new Size(95, 42),
                BackColor = Color.FromArgb(226, 232, 240),
                ForeColor = Color.FromArgb(71, 85, 105),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = 0;
            btnCancel.Click += (s, e) => this.Close();

            this.Controls.AddRange(new Control[] { btnSave, btnCancel });
        }

        private void LoadSuppliersAndProducts()
        {
            try
            {
                var suppliers = DatabaseService.Instance.GetSuppliers();
                cmbSupplier.Items.Clear();
                foreach (var s in suppliers) cmbSupplier.Items.Add(s);
                if (cmbSupplier.Items.Count > 0) cmbSupplier.SelectedIndex = 0;

                _allProducts = DatabaseService.Instance.GetProducts();
                cmbProducts.Items.Clear();
                foreach (var p in _allProducts) cmbProducts.Items.Add(p);
                if (cmbProducts.Items.Count > 0) cmbProducts.SelectedIndex = 0;
            }
            catch { }
        }

        private void CmbProducts_SelectedIndexChanged(object? sender, EventArgs e)
        {
            if (cmbProducts.SelectedItem is Product p)
            {
                numCostPrice.Value = p.CostPrice > 0 ? p.CostPrice : p.Price;
                numSellPrice.Value = p.Price;
            }
        }

        private void BtnAddItem_Click(object? sender, EventArgs e)
        {
            if (cmbProducts.SelectedItem is not Product p) return;

            int qty = (int)numQty.Value;
            decimal cost = numCostPrice.Value;
            decimal sell = numSellPrice.Value;
            decimal sub = qty * cost;

            var item = new StockPurchaseItem
            {
                ProductId = p.Id,
                ItemName = p.Name,
                ItemSku = p.Sku,
                Quantity = qty,
                PurchasePrice = cost,
                SellingPrice = sell,
                Subtotal = sub
            };

            _items.Add(item);
            RefreshItemsGrid();
        }

        private void BtnRemoveItem_Click(object? sender, EventArgs e)
        {
            if (dgvItems.CurrentRow != null && dgvItems.CurrentRow.Index < _items.Count)
            {
                _items.RemoveAt(dgvItems.CurrentRow.Index);
                RefreshItemsGrid();
            }
        }

        private void RefreshItemsGrid()
        {
            dgvItems.Rows.Clear();
            decimal total = 0;

            foreach (var item in _items)
            {
                dgvItems.Rows.Add(
                    item.ItemName,
                    item.ItemSku,
                    item.Quantity,
                    $"{AppConfig.Current.CurrencySymbol} {item.PurchasePrice:F2}",
                    $"{AppConfig.Current.CurrencySymbol} {item.SellingPrice:F2}",
                    $"{AppConfig.Current.CurrencySymbol} {item.Subtotal:F2}"
                );
                total += item.Subtotal;
            }

            lblTotal.Text = $"Grand Total: {AppConfig.Current.CurrencySymbol} {total:F2}";
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            if (cmbSupplier.SelectedItem is not Supplier sup)
            {
                MessageBox.Show("Please select a supplier.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (_items.Count == 0)
            {
                MessageBox.Show("Please add at least one product item to the purchase.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            var purchase = new StockPurchase
            {
                SupplierId = sup.Id,
                SupplierName = sup.CompanyName,
                PurchaseDate = dtpPurchaseDate.Value,
                TotalAmount = _items.Sum(i => i.Subtotal),
                Status = chkMarkReceived.Checked ? "Received" : "Pending",
                Notes = txtNotes.Text.Trim(),
                Items = _items
            };

            bool ok = DatabaseService.Instance.CreateStockPurchase(purchase, autoReceive: chkMarkReceived.Checked);
            if (ok)
            {
                string msg = chkMarkReceived.Checked
                    ? "Stock purchase created and inventory quantities successfully updated!"
                    : "Stock purchase created successfully!";
                MessageBox.Show(msg, "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                MessageBox.Show("Failed to save stock purchase in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
