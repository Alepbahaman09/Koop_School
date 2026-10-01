using System;
using System.Drawing;
using System.IO;
using System.Threading.Tasks;
using System.Windows.Forms;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class LoginForm : Form
    {
        private TextBox txtUsername;
        private TextBox txtPassword;
        private Button btnLogin;
        private Button btnExit;
        private Label lblStatus;
        private CheckBox chkRemember;

        public bool IsAuthenticated { get; private set; } = false;
        public string LoggedInUser { get; private set; } = "Administrator";

        public LoginForm()
        {
            InitializeComponent();
            CheckDatabaseConnectionAsync();
        }

        private void InitializeComponent()
        {
            this.Text = "KooPik - Sign In";
            this.Size = new Size(420, 520);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = UITheme.Surface;
            this.Font = UITheme.FontBody;

            string iconPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app.ico");
            if (File.Exists(iconPath))
            {
                try { this.Icon = new Icon(iconPath); } catch { }
            }

            // Top Brand Header Container
            var pnlHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 120,
                BackColor = UITheme.Surface
            };

            var picLogo = new PictureBox
            {
                Size = new Size(46, 46),
                Location = new Point((this.ClientSize.Width - 46) / 2, 16),
                SizeMode = PictureBoxSizeMode.Zoom
            };
            string logoPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "koopik-logo.png");
            if (File.Exists(logoPath))
            {
                try { picLogo.Image = Image.FromFile(logoPath); } catch { }
            }
            pnlHeader.Controls.Add(picLogo);

            var lblTitle = new Label
            {
                Text = "KooPik",
                Font = new Font("Segoe UI", 16, FontStyle.Bold),
                ForeColor = UITheme.TextDark,
                TextAlign = ContentAlignment.MiddleCenter,
                Size = new Size(this.ClientSize.Width, 26),
                Location = new Point(0, 66)
            };
            pnlHeader.Controls.Add(lblTitle);

            var lblSubtitle = new Label
            {
                Text = "Koperasi Sekolah POS & Management",
                Font = new Font("Segoe UI", 8.5f, FontStyle.Regular),
                ForeColor = UITheme.TextMedium,
                TextAlign = ContentAlignment.MiddleCenter,
                Size = new Size(this.ClientSize.Width, 20),
                Location = new Point(0, 92)
            };
            pnlHeader.Controls.Add(lblSubtitle);

            // Form Fields
            int startY = 135;

            var lblUser = new Label
            {
                Text = "Username or Email",
                Location = new Point(36, startY),
                AutoSize = true,
                ForeColor = UITheme.TextDark,
                Font = UITheme.FontBodyBold
            };

            txtUsername = new TextBox
            {
                Location = new Point(36, startY + 22),
                Size = new Size(332, 30),
                Text = "admin",
                Font = new Font("Segoe UI", 10)
            };

            var lblPass = new Label
            {
                Text = "Password",
                Location = new Point(36, startY + 62),
                AutoSize = true,
                ForeColor = UITheme.TextDark,
                Font = UITheme.FontBodyBold
            };

            txtPassword = new TextBox
            {
                Location = new Point(36, startY + 84),
                Size = new Size(332, 30),
                PasswordChar = '●',
                Text = "admin123",
                Font = new Font("Segoe UI", 10)
            };

            chkRemember = new CheckBox
            {
                Text = "Keep me signed in",
                Location = new Point(36, startY + 126),
                AutoSize = true,
                Checked = true,
                ForeColor = UITheme.TextMedium,
                Font = UITheme.FontSmall
            };

            btnLogin = new Button
            {
                Text = "Sign In to Dashboard",
                Location = new Point(36, startY + 162),
                Size = new Size(332, 42),
                BackColor = UITheme.Primary,
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 10.5f, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnLogin.FlatAppearance.BorderSize = 0;
            btnLogin.Click += BtnLogin_Click;

            btnExit = new Button
            {
                Text = "Exit",
                Location = new Point(36, startY + 212),
                Size = new Size(332, 32),
                BackColor = Color.Transparent,
                ForeColor = UITheme.TextMuted,
                FlatStyle = FlatStyle.Flat,
                Font = UITheme.FontSmallBold,
                Cursor = Cursors.Hand
            };
            btnExit.FlatAppearance.BorderSize = 0;
            btnExit.Click += (s, e) => this.Close();

            lblStatus = new Label
            {
                Text = "Connecting to Supabase Cloud...",
                Location = new Point(36, startY + 252),
                Size = new Size(332, 28),
                ForeColor = UITheme.TextMedium,
                TextAlign = ContentAlignment.MiddleCenter,
                Font = UITheme.FontSmallBold
            };

            this.AcceptButton = btnLogin;
            this.Controls.AddRange(new Control[] {
                pnlHeader, lblUser, txtUsername, lblPass, txtPassword,
                chkRemember, btnLogin, btnExit, lblStatus
            });
        }

        private async void CheckDatabaseConnectionAsync()
        {
            lblStatus.Text = "Connecting to Supabase Cloud...";
            var result = await Task.Run(() => DatabaseService.Instance.TestConnection());
            if (result.success)
            {
                lblStatus.Text = "🟢 Supabase Cloud PostgreSQL Connected";
                lblStatus.ForeColor = UITheme.SuccessText;
            }
            else
            {
                lblStatus.Text = "⚠️ Supabase Cloud: " + result.message;
                lblStatus.ForeColor = UITheme.DangerText;
            }
        }

        private void BtnLogin_Click(object? sender, EventArgs e)
        {
            string user = txtUsername.Text.Trim();
            string pass = txtPassword.Text;

            if (string.IsNullOrWhiteSpace(user) || string.IsNullOrWhiteSpace(pass))
            {
                MessageBox.Show("Please enter both username and password.", "Login Required", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            btnLogin.Enabled = false;
            btnLogin.Text = "Signing In...";

            bool ok = DatabaseService.Instance.ValidateAdminLogin(user, pass);
            if (ok)
            {
                IsAuthenticated = true;
                LoggedInUser = user.Contains("@") ? user.Split('@')[0] : user;
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            else
            {
                btnLogin.Enabled = true;
                btnLogin.Text = "Sign In to Dashboard";
                MessageBox.Show("Invalid username or password.\n\nTip: You can use default 'admin' with password 'admin123'", "Authentication Failed", MessageBoxButtons.OK, MessageBoxIcon.Error);
                txtPassword.Focus();
                txtPassword.SelectAll();
            }
        }
    }
}
