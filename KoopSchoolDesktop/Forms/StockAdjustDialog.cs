using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class StockAdjustDialog : Form
    {
        private readonly Product _product;
        private Label lblCurrentStock;
        private NumericUpDown numAdjustment;
        private RadioButton rbAdd;
        private RadioButton rbDeduct;
        private ComboBox cmbReason;
        private Label lblNewStock;
        private Button btnConfirm;
        private Button btnCancel;

        public StockAdjustDialog(Product product)
        {
            _product = product;
            InitializeComponent();
            UpdateNewStockLabel();
        }

        private void InitializeComponent()
        {
            this.Text = $"Adjust Stock - {_product.Name}";
            this.Size = new Size(420, 380);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            int y = 25;

            var lblProd = new Label
            {
                Text = _product.Name,
                Font = new Font("Segoe UI", 11, FontStyle.Bold),
                Location = new Point(25, y),
                Size = new Size(350, 25),
                ForeColor = Color.FromArgb(30, 41, 59)
            };
            this.Controls.Add(lblProd);

            y += 28;
            lblCurrentStock = new Label
            {
                Text = $"Current Inventory Stock: {_product.StockQuantity} {_product.Unit}",
                Location = new Point(25, y),
                AutoSize = true,
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            this.Controls.Add(lblCurrentStock);

            y += 35;
            rbAdd = new RadioButton
            {
                Text = "➕ Add Stock",
                Location = new Point(25, y),
                AutoSize = true,
                Checked = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(22, 163, 74)
            };
            rbAdd.CheckedChanged += (s, e) => UpdateNewStockLabel();

            rbDeduct = new RadioButton
            {
                Text = "➖ Deduct Stock",
                Location = new Point(160, y),
                AutoSize = true,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                ForeColor = Color.FromArgb(220, 38, 38)
            };
            rbDeduct.CheckedChanged += (s, e) => UpdateNewStockLabel();

            this.Controls.AddRange(new Control[] { rbAdd, rbDeduct });

            y += 40;
            this.Controls.Add(new Label { Text = "Quantity to change:", Location = new Point(25, y + 4), AutoSize = true });
            numAdjustment = new NumericUpDown
            {
                Location = new Point(160, y),
                Size = new Size(120, 30),
                Minimum = 1,
                Maximum = 999999,
                Value = 1
            };
            numAdjustment.ValueChanged += (s, e) => UpdateNewStockLabel();
            this.Controls.Add(numAdjustment);

            y += 42;
            this.Controls.Add(new Label { Text = "Reason / Notes:", Location = new Point(25, y + 4), AutoSize = true });
            cmbReason = new ComboBox
            {
                Location = new Point(160, y),
                Size = new Size(215, 30),
                DropDownStyle = ComboBoxStyle.DropDown
            };
            cmbReason.Items.AddRange(new object[] {
                "Restock / New delivery",
                "Damaged / Expired item",
                "Inventory recount adjustment",
                "School event use",
                "Return from student/teacher"
            });
            cmbReason.SelectedIndex = 0;
            this.Controls.Add(cmbReason);

            y += 45;
            lblNewStock = new Label
            {
                Text = "Resulting Stock: ...",
                Location = new Point(25, y),
                AutoSize = true,
                Font = new Font("Segoe UI", 11, FontStyle.Bold),
                ForeColor = Color.FromArgb(37, 99, 235)
            };
            this.Controls.Add(lblNewStock);

            y += 50;
            btnConfirm = new Button
            {
                Text = "Save Adjustment",
                Location = new Point(25, y),
                Size = new Size(215, 40),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9.5f, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnConfirm.FlatAppearance.BorderSize = 0;
            btnConfirm.Click += BtnConfirm_Click;

            btnCancel = new Button
            {
                Text = "Cancel",
                Location = new Point(250, y),
                Size = new Size(125, 40),
                BackColor = Color.FromArgb(226, 232, 240),
                ForeColor = Color.FromArgb(71, 85, 105),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnCancel.FlatAppearance.BorderSize = 0;
            btnCancel.Click += (s, e) => this.Close();

            this.AcceptButton = btnConfirm;
            this.CancelButton = btnCancel;
            this.Controls.AddRange(new Control[] { btnConfirm, btnCancel });
        }

        private void UpdateNewStockLabel()
        {
            int change = (int)numAdjustment.Value;
            if (rbDeduct.Checked) change = -change;
            int newTotal = _product.StockQuantity + change;
            lblNewStock.Text = $"Resulting Stock: {newTotal} {_product.Unit}";
            if (newTotal < 0)
            {
                lblNewStock.ForeColor = Color.FromArgb(220, 38, 38);
                btnConfirm.Enabled = false;
            }
            else
            {
                lblNewStock.ForeColor = Color.FromArgb(37, 99, 235);
                btnConfirm.Enabled = true;
            }
        }

        private void BtnConfirm_Click(object? sender, EventArgs e)
        {
            int qty = (int)numAdjustment.Value;
            if (rbDeduct.Checked) qty = -qty;

            string reason = cmbReason.Text.Trim();
            if (string.IsNullOrWhiteSpace(reason)) reason = "Manual stock adjustment";

            bool ok = DatabaseService.Instance.AdjustStock(_product.Id, qty, reason);
            if (ok)
            {
                _product.StockQuantity += qty;
                MessageBox.Show("Inventory stock updated successfully!", "Stock Updated", MessageBoxButtons.OK, MessageBoxIcon.Information);
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                MessageBox.Show("Failed to update stock in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
