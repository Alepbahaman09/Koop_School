using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class PosView : UserControl
    {
        // Search & Product Catalog
        private TextBox txtBarcode;
        private TextBox txtSearch;
        private ComboBox cmbCategory;
        private DataGridView dgvCatalog;

        // Cart
        private DataGridView dgvCart;
        private Button btnPlus;
        private Button btnMinus;
        private Button btnRemove;
        private Button btnClearCart;

        // Totals & Discount
        private Label lblSubtotal;
        private NumericUpDown numDiscount;
        private Label lblGrandTotal;

        // Payment Panel
        private TabControl tabPayment;
        // Cash tab
        private NumericUpDown numCashTendered;
        private Label lblChange;
        private Button btnPayCash;
        // NFC Card tab
        private TextBox txtCardUid;
        private Label lblCardHolder;
        private Label lblCardBalance;
        private Button btnCheckCard;
        private Button btnPayNfc;

        private readonly List<CartItem> _cart = new();
        private List<Product> _allProducts = new();
        private StudentCard? _selectedCard = null;
        private NfcReaderClient? _nfcClient;

        public PosView()
        {
            InitializeComponent();
            InitNfcReader();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            // Left Section: Product Search & Catalog (56% width)
            var pnlLeft = new Panel
            {
                Dock = DockStyle.Left,
                Width = 640,
                Padding = new Padding(16),
                BackColor = Color.Transparent
            };

            // Barcode Scanner Box (Prominent card at top)
            var pnlScan = new Panel
            {
                Dock = DockStyle.Top,
                Height = 72,
                BackColor = UITheme.Surface,
                Padding = new Padding(12)
            };
            UITheme.ApplyCardBorder(pnlScan);

            var lblScan = new Label
            {
                Text = "⚡ FAST SCAN / SKU (Auto-adds item on Enter):",
                Font = UITheme.FontSmallBold,
                ForeColor = UITheme.Primary,
                Location = new Point(12, 10),
                AutoSize = true
            };
            pnlScan.Controls.Add(lblScan);

            txtBarcode = new TextBox
            {
                Location = new Point(12, 32),
                Size = new Size(470, 30),
                Font = new Font("Segoe UI", 11, FontStyle.Regular),
                PlaceholderText = "Scan barcode with handheld scanner or type SKU and press Enter..."
            };
            txtBarcode.KeyDown += TxtBarcode_KeyDown;
            pnlScan.Controls.Add(txtBarcode);

            var btnScanAdd = new Button
            {
                Text = "Add Item",
                Location = new Point(492, 30),
                Size = new Size(110, 32)
            };
            UITheme.StylePrimaryButton(btnScanAdd);
            btnScanAdd.Click += (s, e) => ProcessBarcode(txtBarcode.Text);
            pnlScan.Controls.Add(btnScanAdd);
            pnlLeft.Controls.Add(pnlScan);

            // Search & Category Filter Bar
            var pnlFilter = new Panel
            {
                Dock = DockStyle.Top,
                Height = 52,
                Padding = new Padding(0, 10, 0, 8),
                BackColor = Color.Transparent
            };

            txtSearch = new TextBox
            {
                Location = new Point(0, 10),
                Size = new Size(350, 30),
                PlaceholderText = "🔍 Search product name or SKU..."
            };
            txtSearch.TextChanged += (s, e) => FilterCatalog();
            pnlFilter.Controls.Add(txtSearch);

            cmbCategory = new ComboBox
            {
                Location = new Point(362, 10),
                Size = new Size(245, 30),
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            cmbCategory.SelectedIndexChanged += (s, e) => FilterCatalog();
            pnlFilter.Controls.Add(cmbCategory);
            pnlLeft.Controls.Add(pnlFilter);

            // Catalog Grid Container Card
            var pnlCatalogGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface,
                Padding = new Padding(1)
            };
            UITheme.ApplyCardBorder(pnlCatalogGridCard);

            dgvCatalog = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvCatalog);
            dgvCatalog.Columns.Add("Name", "Product Name");
            dgvCatalog.Columns.Add("Sku", "SKU / Barcode");
            dgvCatalog.Columns["Sku"].Width = 130;
            dgvCatalog.Columns.Add("Price", $"Price ({AppConfig.Current.CurrencySymbol})");
            dgvCatalog.Columns["Price"].Width = 95;
            dgvCatalog.Columns.Add("Stock", "Stock");
            dgvCatalog.Columns["Stock"].Width = 85;
            dgvCatalog.CellDoubleClick += (s, e) => AddSelectedCatalogItemToCart();
            pnlCatalogGridCard.Controls.Add(dgvCatalog);
            pnlLeft.Controls.Add(pnlCatalogGridCard);

            this.Controls.Add(pnlLeft);

            // Right Section: Cart, Totals & Payment (Dock Fill)
            var pnlRight = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(6, 16, 16, 16),
                BackColor = Color.Transparent
            };

            // Cart Card
            var pnlCart = new Panel
            {
                Dock = DockStyle.Top,
                Height = 270,
                BackColor = UITheme.Surface,
                Padding = new Padding(12)
            };
            UITheme.ApplyCardBorder(pnlCart);

            var lblCartTitle = new Label
            {
                Text = "Current Cart Items",
                Font = UITheme.FontSubheading,
                ForeColor = UITheme.TextDark,
                Location = new Point(12, 10),
                AutoSize = true
            };
            pnlCart.Controls.Add(lblCartTitle);

            btnClearCart = new Button
            {
                Text = "Clear Cart",
                Location = new Point(460, 8),
                Size = new Size(100, 28)
            };
            UITheme.StyleDangerButton(btnClearCart);
            btnClearCart.Click += (s, e) => { _cart.Clear(); RefreshCartGrid(); };
            pnlCart.Controls.Add(btnClearCart);

            dgvCart = new DataGridView
            {
                Location = new Point(12, 42),
                Size = new Size(550, 175),
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right
            };
            UITheme.StyleTable(dgvCart);
            dgvCart.Columns.Add("Name", "Item");
            dgvCart.Columns.Add("Size", "Size");
            dgvCart.Columns["Size"].Width = 55;
            dgvCart.Columns.Add("Price", "Price");
            dgvCart.Columns["Price"].Width = 80;
            dgvCart.Columns.Add("Qty", "Qty");
            dgvCart.Columns["Qty"].Width = 55;
            dgvCart.Columns.Add("Subtotal", "Subtotal");
            dgvCart.Columns["Subtotal"].Width = 90;
            pnlCart.Controls.Add(dgvCart);

            // Cart Controls
            btnPlus = new Button { Text = "➕ Qty", Location = new Point(12, 226), Size = new Size(85, 32) };
            UITheme.StyleSecondaryButton(btnPlus);
            btnPlus.Click += (s, e) => AdjustCartQty(1);

            btnMinus = new Button { Text = "➖ Qty", Location = new Point(105, 226), Size = new Size(85, 32) };
            UITheme.StyleSecondaryButton(btnMinus);
            btnMinus.Click += (s, e) => AdjustCartQty(-1);

            btnRemove = new Button { Text = "🗑️ Remove", Location = new Point(198, 226), Size = new Size(105, 32) };
            UITheme.StyleDangerButton(btnRemove);
            btnRemove.Click += (s, e) => RemoveSelectedCartItem();

            pnlCart.Controls.AddRange(new Control[] { btnPlus, btnMinus, btnRemove });
            pnlRight.Controls.Add(pnlCart);

            // Spacing
            var pnlGap = new Panel { Dock = DockStyle.Top, Height = 12, BackColor = Color.Transparent };
            pnlRight.Controls.Add(pnlGap);

            // Totals Card (Clean White SaaS Design with Accent)
            var pnlTotals = new Panel
            {
                Dock = DockStyle.Top,
                Height = 84,
                BackColor = UITheme.Surface,
                Padding = new Padding(16, 12, 16, 12)
            };
            UITheme.ApplyCardBorder(pnlTotals);

            lblSubtotal = new Label
            {
                Text = $"Subtotal: {AppConfig.Current.CurrencySymbol} 0.00",
                Font = UITheme.FontBodyBold,
                ForeColor = UITheme.TextMedium,
                Location = new Point(16, 14),
                AutoSize = true
            };
            pnlTotals.Controls.Add(lblSubtotal);

            var lblDisc = new Label { Text = "Discount:", Font = UITheme.FontSmallBold, Location = new Point(16, 42), AutoSize = true, ForeColor = UITheme.TextMuted };
            pnlTotals.Controls.Add(lblDisc);

            numDiscount = new NumericUpDown
            {
                Location = new Point(88, 39),
                Size = new Size(80, 26),
                DecimalPlaces = 2,
                Maximum = 9999,
                Value = 0
            };
            numDiscount.ValueChanged += (s, e) => UpdateCalculations();
            pnlTotals.Controls.Add(numDiscount);

            var lblTotalBadge = new Label
            {
                Text = "TOTAL PAYABLE",
                Font = UITheme.FontSection,
                ForeColor = UITheme.Primary,
                Location = new Point(320, 12),
                Size = new Size(250, 18),
                TextAlign = ContentAlignment.MiddleRight
            };
            pnlTotals.Controls.Add(lblTotalBadge);

            lblGrandTotal = new Label
            {
                Text = $"{AppConfig.Current.CurrencySymbol} 0.00",
                Font = new Font("Segoe UI", 20, FontStyle.Bold),
                ForeColor = UITheme.Primary,
                Location = new Point(280, 28),
                Size = new Size(290, 42),
                TextAlign = ContentAlignment.MiddleRight
            };
            pnlTotals.Controls.Add(lblGrandTotal);
            pnlRight.Controls.Add(pnlTotals);

            // Spacing
            var pnlGap2 = new Panel { Dock = DockStyle.Top, Height = 12, BackColor = Color.Transparent };
            pnlRight.Controls.Add(pnlGap2);

            // Payment Panel Tabs Container Card
            var pnlPaymentCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface,
                Padding = new Padding(1)
            };
            UITheme.ApplyCardBorder(pnlPaymentCard);

            tabPayment = new TabControl
            {
                Dock = DockStyle.Fill,
                Font = UITheme.FontBodyBold
            };

            // Cash Tab
            var tabCash = new TabPage("💵 Cash Payment") { BackColor = UITheme.Surface, Padding = new Padding(16) };
            tabCash.Controls.Add(new Label { Text = $"Cash Tendered ({AppConfig.Current.CurrencySymbol}):", Location = new Point(16, 18), AutoSize = true, Font = UITheme.FontBodyBold, ForeColor = UITheme.TextDark });

            numCashTendered = new NumericUpDown
            {
                Location = new Point(190, 14),
                Size = new Size(160, 30),
                DecimalPlaces = 2,
                Maximum = 10000,
                Font = new Font("Segoe UI", 12, FontStyle.Bold)
            };
            numCashTendered.ValueChanged += (s, e) => CalculateChange();
            tabCash.Controls.Add(numCashTendered);

            // Quick cash buttons
            var pnlQuickCash = new FlowLayoutPanel { Location = new Point(16, 54), Size = new Size(530, 40) };
            decimal[] cashShortcuts = { 5, 10, 20, 50, 100 };
            foreach (var sc in cashShortcuts)
            {
                var btnSc = new Button
                {
                    Text = $"{AppConfig.Current.CurrencySymbol}{sc:0}",
                    Size = new Size(68, 32)
                };
                UITheme.StyleSecondaryButton(btnSc);
                btnSc.Click += (s, e) => { numCashTendered.Value = sc; };
                pnlQuickCash.Controls.Add(btnSc);
            }
            var btnExact = new Button
            {
                Text = "Exact",
                Size = new Size(72, 32)
            };
            UITheme.StyleSecondaryButton(btnExact);
            btnExact.Click += (s, e) => { numCashTendered.Value = GetPayableAmount(); };
            pnlQuickCash.Controls.Add(btnExact);
            tabCash.Controls.Add(pnlQuickCash);

            lblChange = new Label
            {
                Text = $"Change: {AppConfig.Current.CurrencySymbol} 0.00",
                Font = new Font("Segoe UI", 14, FontStyle.Bold),
                ForeColor = UITheme.SuccessText,
                Location = new Point(16, 102),
                AutoSize = true
            };
            tabCash.Controls.Add(lblChange);

            btnPayCash = new Button
            {
                Text = "✅ Complete Cash Sale",
                Location = new Point(16, 144),
                Size = new Size(320, 48)
            };
            UITheme.StyleSuccessButton(btnPayCash);
            btnPayCash.Font = new Font("Segoe UI", 11, FontStyle.Bold);
            btnPayCash.Click += BtnPayCash_Click;
            tabCash.Controls.Add(btnPayCash);
            tabPayment.TabPages.Add(tabCash);

            // NFC Tab
            var tabNfc = new TabPage("💳 NFC Card Payment") { BackColor = UITheme.Surface, Padding = new Padding(16) };
            tabNfc.Controls.Add(new Label { Text = "Tap NFC Card or Enter UID:", Location = new Point(16, 16), AutoSize = true, Font = UITheme.FontBodyBold, ForeColor = UITheme.TextDark });

            txtCardUid = new TextBox
            {
                Location = new Point(16, 42),
                Size = new Size(240, 30),
                Font = new Font("Consolas", 11),
                PlaceholderText = "Tap NFC Card on reader..."
            };
            txtCardUid.KeyDown += (s, e) => { if (e.KeyCode == Keys.Enter) CheckCard(txtCardUid.Text); };
            tabNfc.Controls.Add(txtCardUid);

            btnCheckCard = new Button
            {
                Text = "Lookup Card",
                Location = new Point(268, 40),
                Size = new Size(110, 32)
            };
            UITheme.StyleSecondaryButton(btnCheckCard);
            btnCheckCard.Click += (s, e) => CheckCard(txtCardUid.Text);
            tabNfc.Controls.Add(btnCheckCard);

            lblCardHolder = new Label
            {
                Text = "Cardholder: Not scanned",
                Font = UITheme.FontBodyBold,
                ForeColor = UITheme.TextMedium,
                Location = new Point(16, 84),
                AutoSize = true
            };
            tabNfc.Controls.Add(lblCardHolder);

            lblCardBalance = new Label
            {
                Text = $"Card Balance: {AppConfig.Current.CurrencySymbol} 0.00",
                Font = UITheme.FontBodyBold,
                ForeColor = UITheme.Primary,
                Location = new Point(16, 108),
                AutoSize = true
            };
            tabNfc.Controls.Add(lblCardBalance);

            btnPayNfc = new Button
            {
                Text = "⚡ Pay with NFC Card",
                Location = new Point(16, 144),
                Size = new Size(320, 48)
            };
            UITheme.StylePrimaryButton(btnPayNfc);
            btnPayNfc.Font = new Font("Segoe UI", 11, FontStyle.Bold);
            btnPayNfc.Click += BtnPayNfc_Click;
            tabNfc.Controls.Add(btnPayNfc);

            tabPayment.TabPages.Add(tabNfc);
            pnlPaymentCard.Controls.Add(tabPayment);
            pnlRight.Controls.Add(pnlPaymentCard);

            this.Controls.Add(pnlRight);

            this.Load += (s, e) => LoadCatalog();
        }

        private void InitNfcReader()
        {
            try
            {
                _nfcClient = new NfcReaderClient();
                _nfcClient.OnCardTapped += (uid) =>
                {
                    this.BeginInvoke(new Action(() =>
                    {
                        tabPayment.SelectedIndex = 1;
                        txtCardUid.Text = uid;
                        CheckCard(uid);
                    }));
                };
                _nfcClient.Start();
            }
            catch { }
        }

        public void LoadCatalog()
        {
            try
            {
                var categories = DatabaseService.Instance.GetCategories();
                cmbCategory.Items.Clear();
                cmbCategory.Items.Add(new Category { Id = 0, Name = "All Categories" });
                foreach (var c in categories) cmbCategory.Items.Add(c);
                cmbCategory.SelectedIndex = 0;

                _allProducts = DatabaseService.Instance.GetProducts(activeOnly: true);
                FilterCatalog();
            }
            catch { }
        }

        private void FilterCatalog()
        {
            string search = txtSearch.Text.Trim().ToLower();
            long? selectedCatId = null;
            if (cmbCategory.SelectedItem is Category c && c.Id > 0)
            {
                selectedCatId = c.Id;
            }

            var filtered = _allProducts.Where(p =>
                (string.IsNullOrEmpty(search) || p.Name.ToLower().Contains(search) || p.Sku.ToLower().Contains(search)) &&
                (!selectedCatId.HasValue || p.CategoryId == selectedCatId)
            ).ToList();

            dgvCatalog.Rows.Clear();
            foreach (var p in filtered)
            {
                dgvCatalog.Rows.Add(
                    p.Name,
                    p.Sku,
                    $"{AppConfig.Current.CurrencySymbol} {p.Price:F2}",
                    $"{p.StockQuantity} {p.Unit}"
                );
            }
        }

        private void TxtBarcode_KeyDown(object? sender, KeyEventArgs e)
        {
            if (e.KeyCode == Keys.Enter)
            {
                e.Handled = true;
                e.SuppressKeyPress = true;
                ProcessBarcode(txtBarcode.Text);
            }
        }

        private void ProcessBarcode(string barcode)
        {
            if (string.IsNullOrWhiteSpace(barcode)) return;

            var product = _allProducts.FirstOrDefault(p => p.Sku.Equals(barcode.Trim(), StringComparison.OrdinalIgnoreCase));
            if (product == null)
            {
                product = DatabaseService.Instance.GetProductByBarcode(barcode.Trim());
            }

            if (product != null)
            {
                AddToCart(product);
                txtBarcode.Clear();
                txtBarcode.Focus();
            }
            else
            {
                MessageBox.Show($"Product with Barcode '{barcode}' not found.", "Item Not Found", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtBarcode.SelectAll();
            }
        }

        private void AddSelectedCatalogItemToCart()
        {
            if (dgvCatalog.CurrentRow != null && dgvCatalog.CurrentRow.Index >= 0)
            {
                string sku = dgvCatalog.CurrentRow.Cells["Sku"].Value?.ToString() ?? "";
                var prod = _allProducts.FirstOrDefault(p => p.Sku == sku);
                if (prod != null)
                {
                    AddToCart(prod);
                }
            }
        }

        private void AddToCart(Product p)
        {
            string? selectedSize = null;
            decimal itemPrice = p.Price;

            // If product has sizes, ask cashier for size selection
            if (p.Sizes != null && p.Sizes.Count > 0)
            {
                using var sizeForm = new Form
                {
                    Text = $"Select Size for {p.Name}",
                    Size = new Size(360, 240),
                    StartPosition = FormStartPosition.CenterParent,
                    FormBorderStyle = FormBorderStyle.FixedDialog,
                    MaximizeBox = false,
                    MinimizeBox = false,
                    Font = new Font("Segoe UI", 9.5f)
                };

                sizeForm.Controls.Add(new Label { Text = "Choose size variant:", Location = new Point(25, 20), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
                var cmbSizes = new ComboBox { Location = new Point(25, 50), Size = new Size(290, 30), DropDownStyle = ComboBoxStyle.DropDownList };
                foreach (var sz in p.Sizes)
                {
                    string prcText = sz.Price.HasValue ? $" - {AppConfig.Current.CurrencySymbol} {sz.Price.Value:F2}" : "";
                    cmbSizes.Items.Add($"{sz.Size} (Stock: {sz.StockQuantity}){prcText}");
                }
                if (cmbSizes.Items.Count > 0) cmbSizes.SelectedIndex = 0;
                sizeForm.Controls.Add(cmbSizes);

                var btnOk = new Button { Text = "Add Variant", Location = new Point(25, 110), Size = new Size(160, 38), BackColor = Color.FromArgb(37, 99, 235), ForeColor = Color.White, FlatStyle = FlatStyle.Flat, DialogResult = DialogResult.OK };
                var btnCancel = new Button { Text = "Cancel", Location = new Point(195, 110), Size = new Size(120, 38), BackColor = Color.FromArgb(226, 232, 240), DialogResult = DialogResult.Cancel };
                sizeForm.AcceptButton = btnOk;
                sizeForm.CancelButton = btnCancel;
                sizeForm.Controls.AddRange(new Control[] { btnOk, btnCancel });

                if (sizeForm.ShowDialog(this) == DialogResult.OK && cmbSizes.SelectedIndex >= 0)
                {
                    var chosen = p.Sizes[cmbSizes.SelectedIndex];
                    selectedSize = chosen.Size;
                    if (chosen.Price.HasValue && chosen.Price.Value > 0) itemPrice = chosen.Price.Value;
                }
                else
                {
                    return; // user cancelled
                }
            }

            var existing = _cart.FirstOrDefault(c => c.ProductId == p.Id && c.Size == selectedSize);
            if (existing != null)
            {
                existing.Quantity++;
            }
            else
            {
                _cart.Add(new CartItem
                {
                    ProductId = p.Id,
                    Name = p.Name,
                    Sku = p.Sku,
                    Size = selectedSize,
                    Price = itemPrice,
                    CostPrice = p.CostPrice,
                    Quantity = 1,
                    AvailableStock = p.StockQuantity
                });
            }

            RefreshCartGrid();
        }

        private void AdjustCartQty(int delta)
        {
            if (dgvCart.CurrentRow != null && dgvCart.CurrentRow.Index < _cart.Count)
            {
                var item = _cart[dgvCart.CurrentRow.Index];
                item.Quantity += delta;
                if (item.Quantity <= 0)
                {
                    _cart.Remove(item);
                }
                RefreshCartGrid();
            }
        }

        private void RemoveSelectedCartItem()
        {
            if (dgvCart.CurrentRow != null && dgvCart.CurrentRow.Index < _cart.Count)
            {
                _cart.RemoveAt(dgvCart.CurrentRow.Index);
                RefreshCartGrid();
            }
        }

        private void RefreshCartGrid()
        {
            dgvCart.Rows.Clear();
            foreach (var item in _cart)
            {
                string displayName = string.IsNullOrWhiteSpace(item.Size) ? item.Name : $"{item.Name} ({item.Size})";
                dgvCart.Rows.Add(
                    displayName,
                    item.Size ?? "-",
                    $"{AppConfig.Current.CurrencySymbol} {item.Price:F2}",
                    item.Quantity,
                    $"{AppConfig.Current.CurrencySymbol} {item.Subtotal:F2}"
                );
            }

            UpdateCalculations();
        }

        private decimal GetPayableAmount()
        {
            decimal subtotal = _cart.Sum(i => i.Subtotal);
            decimal discount = numDiscount.Value;
            return Math.Max(0, subtotal - discount);
        }

        private void UpdateCalculations()
        {
            decimal subtotal = _cart.Sum(i => i.Subtotal);
            decimal discount = numDiscount.Value;
            decimal total = Math.Max(0, subtotal - discount);

            lblSubtotal.Text = $"Subtotal: {AppConfig.Current.CurrencySymbol} {subtotal:F2}";
            lblGrandTotal.Text = $"{AppConfig.Current.CurrencySymbol} {total:F2}";

            CalculateChange();
        }

        private void CalculateChange()
        {
            decimal total = GetPayableAmount();
            decimal tendered = numCashTendered.Value;
            decimal change = tendered - total;

            if (tendered >= total)
            {
                lblChange.Text = $"Change: {AppConfig.Current.CurrencySymbol} {change:F2}";
                lblChange.ForeColor = Color.FromArgb(22, 163, 74);
            }
            else
            {
                lblChange.Text = $"Need: {AppConfig.Current.CurrencySymbol} {Math.Abs(change):F2} more";
                lblChange.ForeColor = Color.FromArgb(220, 38, 38);
            }
        }

        private void CheckCard(string uid)
        {
            if (string.IsNullOrWhiteSpace(uid)) return;

            var card = DatabaseService.Instance.GetStudentCardByUid(uid);
            if (card != null)
            {
                _selectedCard = card;
                lblCardHolder.Text = $"Cardholder: {card.Owner} ({(card.IsFrozen ? "FROZEN" : "Active")})";
                lblCardHolder.ForeColor = card.IsFrozen ? Color.Red : Color.FromArgb(30, 41, 59);
                lblCardBalance.Text = $"Card Balance: {AppConfig.Current.CurrencySymbol} {card.Balance:F2}";
            }
            else
            {
                _selectedCard = null;
                lblCardHolder.Text = "Cardholder: Card not registered!";
                lblCardHolder.ForeColor = Color.Red;
                lblCardBalance.Text = $"Card Balance: {AppConfig.Current.CurrencySymbol} 0.00";
            }
        }

        private void BtnPayCash_Click(object? sender, EventArgs e)
        {
            if (_cart.Count == 0)
            {
                MessageBox.Show("Cart is empty. Add products before completing sale.", "Empty Cart", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            decimal total = GetPayableAmount();
            decimal tendered = numCashTendered.Value;

            if (tendered < total)
            {
                MessageBox.Show($"Cash received ({AppConfig.Current.CurrencySymbol} {tendered:F2}) is less than total payable ({AppConfig.Current.CurrencySymbol} {total:F2}).", "Insufficient Cash", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                numCashTendered.Focus();
                return;
            }

            decimal change = tendered - total;

            var result = DatabaseService.Instance.CreateSaleOrder(
                _cart,
                numDiscount.Value,
                "Cash",
                null,
                tendered,
                "Walk-in Customer"
            );

            if (result.success)
            {
                var order = new Order
                {
                    OrderNumber = result.orderNumber,
                    CustomerName = "Walk-in Customer",
                    Subtotal = _cart.Sum(i => i.Subtotal),
                    Discount = numDiscount.Value,
                    TotalAmount = total,
                    PaymentMethod = "Cash",
                    CreatedAt = DateTime.Now
                };

                using var receiptDlg = new ReceiptDialog(order, new List<CartItem>(_cart), tendered, change);
                receiptDlg.ShowDialog(this);

                ResetPosSale();
            }
            else
            {
                MessageBox.Show($"POS Sale Failed: {result.errorMessage}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void BtnPayNfc_Click(object? sender, EventArgs e)
        {
            if (_cart.Count == 0)
            {
                MessageBox.Show("Cart is empty.", "Warning", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            string uid = txtCardUid.Text.Trim();
            if (string.IsNullOrWhiteSpace(uid))
            {
                MessageBox.Show("Please tap or enter an NFC Card UID.", "Warning", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtCardUid.Focus();
                return;
            }

            decimal total = GetPayableAmount();

            var result = DatabaseService.Instance.CreateSaleOrder(
                _cart,
                numDiscount.Value,
                "NFC Card",
                uid,
                total,
                _selectedCard?.Owner ?? "Cardholder"
            );

            if (result.success)
            {
                var order = new Order
                {
                    OrderNumber = result.orderNumber,
                    CustomerName = _selectedCard?.Owner ?? "Student Card",
                    Subtotal = _cart.Sum(i => i.Subtotal),
                    Discount = numDiscount.Value,
                    TotalAmount = total,
                    PaymentMethod = "NFC Card",
                    CreatedAt = DateTime.Now
                };

                using var receiptDlg = new ReceiptDialog(order, new List<CartItem>(_cart));
                receiptDlg.ShowDialog(this);

                ResetPosSale();
            }
            else
            {
                MessageBox.Show($"NFC Payment failed: {result.errorMessage}", "Payment Failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ResetPosSale()
        {
            _cart.Clear();
            RefreshCartGrid();
            numDiscount.Value = 0;
            numCashTendered.Value = 0;
            txtBarcode.Clear();
            txtCardUid.Clear();
            _selectedCard = null;
            lblCardHolder.Text = "Cardholder: Not scanned";
            lblCardBalance.Text = $"Card Balance: {AppConfig.Current.CurrencySymbol} 0.00";
            LoadCatalog();
            txtBarcode.Focus();
        }
    }
}
