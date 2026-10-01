using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class SupplierEditForm : Form
    {
        private TextBox txtCode;
        private TextBox txtCompany;
        private TextBox txtContact;
        private TextBox txtPhone;
        private TextBox txtEmail;
        private TextBox txtAddress;
        private Button btnSave;
        private Button btnCancel;

        public Supplier Supplier { get; private set; }

        public SupplierEditForm(Supplier? existing = null)
        {
            Supplier = existing ?? new Supplier();
            InitializeComponent();
            PopulateFields();
        }

        private void InitializeComponent()
        {
            this.Text = Supplier.Id == 0 ? "Add New Supplier" : "Edit Supplier";
            this.Size = new Size(500, 480);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            int labelX = 25;
            int inputX = 160;
            int inputWidth = 280;
            int y = 25;
            int spacing = 42;

            // Supplier Code
            this.Controls.Add(new Label { Text = "Supplier Code:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtCode = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30), PlaceholderText = "Auto-generated (e.g. SUP0001)" };
            this.Controls.Add(txtCode);

            // Company Name
            y += spacing;
            this.Controls.Add(new Label { Text = "Company Name *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            txtCompany = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30) };
            this.Controls.Add(txtCompany);

            // Contact Person
            y += spacing;
            this.Controls.Add(new Label { Text = "Contact Person:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtContact = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30) };
            this.Controls.Add(txtContact);

            // Phone
            y += spacing;
            this.Controls.Add(new Label { Text = "Phone Number:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtPhone = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30) };
            this.Controls.Add(txtPhone);

            // Email
            y += spacing;
            this.Controls.Add(new Label { Text = "Email Address:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtEmail = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30) };
            this.Controls.Add(txtEmail);

            // Address
            y += spacing;
            this.Controls.Add(new Label { Text = "Address:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtAddress = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 60), Multiline = true };
            this.Controls.Add(txtAddress);

            // Buttons
            y += 75;
            btnSave = new Button
            {
                Text = "Save Supplier",
                Location = new Point(inputX, y),
                Size = new Size(150, 40),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9.5f, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnSave.FlatAppearance.BorderSize = 0;
            btnSave.Click += BtnSave_Click;

            btnCancel = new Button
            {
                Text = "Cancel",
                Location = new Point(inputX + 160, y),
                Size = new Size(120, 40),
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

        private void PopulateFields()
        {
            if (Supplier.Id != 0)
            {
                txtCode.Text = Supplier.SupplierId;
                txtCompany.Text = Supplier.CompanyName;
                txtContact.Text = Supplier.ContactPerson ?? "";
                txtPhone.Text = Supplier.Phone ?? "";
                txtEmail.Text = Supplier.Email ?? "";
                txtAddress.Text = Supplier.Address ?? "";
            }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            if (string.IsNullOrWhiteSpace(txtCompany.Text))
            {
                MessageBox.Show("Please enter the company name.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtCompany.Focus();
                return;
            }

            Supplier.SupplierId = txtCode.Text.Trim();
            Supplier.CompanyName = txtCompany.Text.Trim();
            Supplier.ContactPerson = txtContact.Text.Trim();
            Supplier.Phone = txtPhone.Text.Trim();
            Supplier.Email = txtEmail.Text.Trim();
            Supplier.Address = txtAddress.Text.Trim();

            try
            {
                long id = DatabaseService.Instance.SaveSupplier(Supplier);
                Supplier.Id = id;
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Failed to save supplier: {ex.Message}", "Database Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
