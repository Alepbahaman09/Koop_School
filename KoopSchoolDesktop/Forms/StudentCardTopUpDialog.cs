using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class StudentCardTopUpDialog : Form
    {
        private readonly StudentCard _card;
        private Label lblOwner;
        private Label lblUid;
        private Label lblCurrentBal;
        private NumericUpDown numAmount;
        private Label lblNewBal;
        private Button btnConfirm;
        private Button btnCancel;

        public StudentCardTopUpDialog(StudentCard card)
        {
            _card = card;
            InitializeComponent();
            UpdateNewBalance();
        }

        private void InitializeComponent()
        {
            this.Text = "Reload / Top-Up Student Card";
            this.Size = new Size(440, 420);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            int y = 20;

            lblOwner = new Label
            {
                Text = $"👤 {_card.Owner}",
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
                Location = new Point(25, y),
                Size = new Size(370, 25),
                ForeColor = Color.FromArgb(30, 41, 59)
            };
            this.Controls.Add(lblOwner);

            y += 26;
            lblUid = new Label
            {
                Text = $"Card UID: {_card.CardUid}",
                Font = new Font("Consolas", 10),
                Location = new Point(25, y),
                AutoSize = true,
                ForeColor = Color.FromArgb(100, 116, 139)
            };
            this.Controls.Add(lblUid);

            y += 24;
            lblCurrentBal = new Label
            {
                Text = $"Current Balance: {AppConfig.Current.CurrencySymbol} {_card.Balance:F2}",
                Font = new Font("Segoe UI", 10.5f, FontStyle.Bold),
                Location = new Point(25, y),
                AutoSize = true,
                ForeColor = Color.FromArgb(22, 163, 74)
            };
            this.Controls.Add(lblCurrentBal);

            y += 35;
            this.Controls.Add(new Label { Text = "Quick Reload Options:", Location = new Point(25, y), AutoSize = true });

            y += 24;
            var pnlQuick = new FlowLayoutPanel
            {
                Location = new Point(25, y),
                Size = new Size(380, 45)
            };

            decimal[] quickAmounts = { 5, 10, 20, 50, 100 };
            foreach (var amt in quickAmounts)
            {
                var btn = new Button
                {
                    Text = $"+{AppConfig.Current.CurrencySymbol}{amt:0}",
                    Size = new Size(68, 36),
                    BackColor = Color.FromArgb(241, 245, 249),
                    ForeColor = Color.FromArgb(30, 41, 59),
                    FlatStyle = FlatStyle.Flat,
                    Cursor = Cursors.Hand,
                    Font = new Font("Segoe UI", 9, FontStyle.Bold)
                };
                btn.FlatAppearance.BorderColor = Color.FromArgb(203, 213, 225);
                btn.Click += (s, e) => { numAmount.Value = amt; };
                pnlQuick.Controls.Add(btn);
            }
            this.Controls.Add(pnlQuick);

            y += 55;
            this.Controls.Add(new Label { Text = $"Custom Top-Up Amount ({AppConfig.Current.CurrencySymbol}):", Location = new Point(25, y + 4), AutoSize = true, Font = new Font("Segoe UI", 9.5f, FontStyle.Bold) });
            numAmount = new NumericUpDown
            {
                Location = new Point(230, y),
                Size = new Size(165, 30),
                DecimalPlaces = 2,
                Minimum = 1,
                Maximum = 1000,
                Value = 10
            };
            numAmount.ValueChanged += (s, e) => UpdateNewBalance();
            this.Controls.Add(numAmount);

            y += 45;
            lblNewBal = new Label
            {
                Text = "New Balance: ...",
                Font = new Font("Segoe UI", 12, FontStyle.Bold),
                Location = new Point(25, y),
                AutoSize = true,
                ForeColor = Color.FromArgb(37, 99, 235)
            };
            this.Controls.Add(lblNewBal);

            y += 55;
            btnConfirm = new Button
            {
                Text = "Confirm Top-Up",
                Location = new Point(25, y),
                Size = new Size(230, 42),
                BackColor = Color.FromArgb(22, 163, 74),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnConfirm.FlatAppearance.BorderSize = 0;
            btnConfirm.Click += BtnConfirm_Click;

            btnCancel = new Button
            {
                Text = "Cancel",
                Location = new Point(270, y),
                Size = new Size(125, 42),
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

        private void UpdateNewBalance()
        {
            decimal newBal = _card.Balance + numAmount.Value;
            lblNewBal.Text = $"New Balance: {AppConfig.Current.CurrencySymbol} {newBal:F2}";
        }

        private void BtnConfirm_Click(object? sender, EventArgs e)
        {
            decimal topUp = numAmount.Value;
            if (topUp <= 0)
            {
                MessageBox.Show("Please enter a valid amount.", "Warning", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            bool ok = DatabaseService.Instance.TopUpCardBalance(_card.CardUid, topUp);
            if (ok)
            {
                _card.Balance += topUp;
                MessageBox.Show($"Successfully topped up {AppConfig.Current.CurrencySymbol} {topUp:F2} for {_card.Owner}!\nNew Balance: {AppConfig.Current.CurrencySymbol} {_card.Balance:F2}", "Reload Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                MessageBox.Show("Failed to update card balance in database.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}
