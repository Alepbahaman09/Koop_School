using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class StudentCardRegisterDialog : Form
    {
        private TextBox txtUid;
        private TextBox txtOwner;
        private TextBox txtClass;
        private NumericUpDown numInitialBal;
        private Button btnSave;
        private Button btnCancel;
        private NfcReaderClient? _nfcClient;

        public StudentCardRegisterDialog(string? prefilledUid = null)
        {
            InitializeComponent();
            if (!string.IsNullOrWhiteSpace(prefilledUid))
            {
                txtUid.Text = prefilledUid;
            }
            StartNfcListener();
        }

        private void InitializeComponent()
        {
            this.Text = "Register New NFC Student Card";
            this.Size = new Size(460, 400);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            int labelX = 25;
            int inputX = 150;
            int inputWidth = 260;
            int y = 25;
            int spacing = 45;

            // Card UID
            this.Controls.Add(new Label { Text = "NFC Card UID *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            txtUid = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30), PlaceholderText = "Tap card or type UID" };
            this.Controls.Add(txtUid);

            y += spacing;
            this.Controls.Add(new Label { Text = "Student Name *:", Location = new Point(labelX, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            txtOwner = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30) };
            this.Controls.Add(txtOwner);

            y += spacing;
            this.Controls.Add(new Label { Text = "Class / Form:", Location = new Point(labelX, y + 4), AutoSize = true });
            txtClass = new TextBox { Location = new Point(inputX, y), Size = new Size(inputWidth, 30), PlaceholderText = "e.g. 5 Amanah" };
            this.Controls.Add(txtClass);

            y += spacing;
            this.Controls.Add(new Label { Text = $"Initial Balance ({AppConfig.Current.CurrencySymbol}):", Location = new Point(labelX, y + 4), AutoSize = true });
            numInitialBal = new NumericUpDown
            {
                Location = new Point(inputX, y),
                Size = new Size(inputWidth, 30),
                DecimalPlaces = 2,
                Maximum = 1000,
                Value = 0
            };
            this.Controls.Add(numInitialBal);

            y += spacing + 20;
            btnSave = new Button
            {
                Text = "Register Card",
                Location = new Point(inputX, y),
                Size = new Size(140, 42),
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
                Location = new Point(inputX + 150, y),
                Size = new Size(110, 42),
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

            this.FormClosing += (s, e) => _nfcClient?.Dispose();
        }

        private void StartNfcListener()
        {
            try
            {
                _nfcClient = new NfcReaderClient();
                _nfcClient.OnCardTapped += (uid) =>
                {
                    this.BeginInvoke(new Action(() =>
                    {
                        if (string.IsNullOrWhiteSpace(txtUid.Text))
                        {
                            txtUid.Text = uid;
                            txtOwner.Focus();
                        }
                    }));
                };
                _nfcClient.Start();
            }
            catch { }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            string uid = txtUid.Text.Trim();
            string owner = txtOwner.Text.Trim();
            string cls = txtClass.Text.Trim();
            decimal bal = numInitialBal.Value;

            if (string.IsNullOrWhiteSpace(uid))
            {
                MessageBox.Show("Please tap an NFC card or enter a Card UID.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtUid.Focus();
                return;
            }

            if (string.IsNullOrWhiteSpace(owner))
            {
                MessageBox.Show("Please enter the student or cardholder name.", "Validation", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                txtOwner.Focus();
                return;
            }

            // Check if card exists
            var existing = DatabaseService.Instance.GetStudentCardByUid(uid);
            if (existing != null)
            {
                MessageBox.Show($"Card UID '{uid}' is already registered to '{existing.Owner}'.", "Duplicate Card", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            bool ok = DatabaseService.Instance.RegisterCard(uid, owner, cls, bal);
            if (ok)
            {
                MessageBox.Show($"Card successfully registered to {owner}!", "Registered", MessageBoxButtons.OK, MessageBoxIcon.Information);
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                MessageBox.Show("Failed to register card in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
