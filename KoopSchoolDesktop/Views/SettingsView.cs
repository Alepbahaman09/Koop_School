using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class SettingsView : UserControl
    {
        private RadioButton rbPostgreSql;
        private RadioButton rbSqlite;

        private TextBox txtPgHost;
        private NumericUpDown numPgPort;
        private TextBox txtPgDb;
        private TextBox txtPgUser;
        private TextBox txtPgPass;
        private Button btnTestDb;
        private Label lblDbStatus;

        private TextBox txtSchoolName;
        private TextBox txtCurrency;
        private TextBox txtReceiptHeader;
        private TextBox txtReceiptFooter;

        private TextBox txtNfcUrl;
        private Button btnTestNfc;
        private Label lblNfcStatus;

        // Embedded API Server
        private CheckBox chkApiEnabled;
        private NumericUpDown numApiPort;
        private Button btnRestartApi;
        private Label lblApiStatus;

        private Button btnSave;

        public SettingsView()
        {
            InitializeComponent();
            LoadCurrentSettings();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            var pnlContainer = new Panel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                Padding = new Padding(24),
                BackColor = UITheme.Canvas
            };

            // Header Banner
            var pnlHeader = new Panel
            {
                Location = new Point(24, 16),
                Size = new Size(820, 60),
                BackColor = Color.Transparent
            };

            var lblTitle = new Label
            {
                Text = "System & Cloud Configuration",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(0, 4),
                AutoSize = true
            };
            pnlHeader.Controls.Add(lblTitle);

            var lblSub = new Label
            {
                Text = "Manage Supabase Cloud PostgreSQL database, receipt layout, NFC hardware bridge, and API server",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(0, 32),
                AutoSize = true
            };
            pnlHeader.Controls.Add(lblSub);
            pnlContainer.Controls.Add(pnlHeader);

            // 1. Database Settings Card (Supabase Cloud PostgreSQL)
            int y = 84;
            var cardDb = new Panel
            {
                Location = new Point(24, y),
                Size = new Size(820, 240),
                BackColor = UITheme.Surface,
                Padding = new Padding(20)
            };
            UITheme.ApplyCardBorder(cardDb);

            var lblDbCardTitle = new Label
            {
                Text = "DATABASE STORAGE CONFIGURATION",
                Font = UITheme.FontSection,
                ForeColor = UITheme.Primary,
                Location = new Point(20, 14),
                AutoSize = true
            };
            cardDb.Controls.Add(lblDbCardTitle);

            var lblCloudBadge = new Label
            {
                Text = "🟢 Cloud Supabase PostgreSQL (Recommended / Primary)",
                Font = UITheme.FontBodyBold,
                ForeColor = UITheme.SuccessText,
                Location = new Point(20, 36),
                AutoSize = true
            };
            cardDb.Controls.Add(lblCloudBadge);

            rbPostgreSql = new RadioButton { Text = "PostgreSQL (Supabase Cloud)", Location = new Point(20, 62), AutoSize = true, Checked = true, Font = UITheme.FontBodyBold, ForeColor = UITheme.TextDark };
            rbSqlite = new RadioButton { Text = "SQLite (Local Offline Storage)", Location = new Point(270, 62), AutoSize = true, Font = UITheme.FontBody, ForeColor = UITheme.TextMedium };
            cardDb.Controls.AddRange(new Control[] { rbPostgreSql, rbSqlite });

            int dY = 94;
            cardDb.Controls.Add(new Label { Text = "Host:", Location = new Point(20, dY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtPgHost = new TextBox { Location = new Point(90, dY), Size = new Size(420, 28) };
            cardDb.Controls.Add(txtPgHost);

            cardDb.Controls.Add(new Label { Text = "Port:", Location = new Point(530, dY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            numPgPort = new NumericUpDown { Location = new Point(570, dY), Size = new Size(80, 28), Maximum = 65535, Value = 5432 };
            cardDb.Controls.Add(numPgPort);

            dY += 36;
            cardDb.Controls.Add(new Label { Text = "Database:", Location = new Point(20, dY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtPgDb = new TextBox { Location = new Point(90, dY), Size = new Size(180, 28) };
            cardDb.Controls.Add(txtPgDb);

            cardDb.Controls.Add(new Label { Text = "User:", Location = new Point(290, dY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtPgUser = new TextBox { Location = new Point(340, dY), Size = new Size(310, 28) };
            cardDb.Controls.Add(txtPgUser);

            dY += 36;
            cardDb.Controls.Add(new Label { Text = "Password:", Location = new Point(20, dY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtPgPass = new TextBox { Location = new Point(90, dY), Size = new Size(420, 28), PasswordChar = '●' };
            cardDb.Controls.Add(txtPgPass);

            btnTestDb = new Button
            {
                Text = "⚡ Test Connection",
                Location = new Point(530, dY - 2),
                Size = new Size(160, 32)
            };
            UITheme.StylePrimaryButton(btnTestDb);
            btnTestDb.Click += BtnTestDb_Click;
            cardDb.Controls.Add(btnTestDb);

            dY += 36;
            lblDbStatus = new Label
            {
                Text = "Ready to connect to Supabase Cloud",
                Location = new Point(90, dY),
                AutoSize = true,
                ForeColor = UITheme.TextMuted,
                Font = UITheme.FontSmall
            };
            cardDb.Controls.Add(lblDbStatus);
            pnlContainer.Controls.Add(cardDb);

            // 2. Store Info & Receipt Format Card
            y += 254;
            var cardStore = new Panel
            {
                Location = new Point(24, y),
                Size = new Size(820, 230),
                BackColor = UITheme.Surface,
                Padding = new Padding(20)
            };
            UITheme.ApplyCardBorder(cardStore);

            var lblStoreTitle = new Label
            {
                Text = "STORE INFORMATION & RECEIPT LAYOUT",
                Font = UITheme.FontSection,
                ForeColor = UITheme.Primary,
                Location = new Point(20, 14),
                AutoSize = true
            };
            cardStore.Controls.Add(lblStoreTitle);

            int sY = 40;
            cardStore.Controls.Add(new Label { Text = "School Name:", Location = new Point(20, sY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtSchoolName = new TextBox { Location = new Point(130, sY), Size = new Size(400, 28) };
            cardStore.Controls.Add(txtSchoolName);

            cardStore.Controls.Add(new Label { Text = "Currency:", Location = new Point(550, sY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtCurrency = new TextBox { Location = new Point(620, sY), Size = new Size(70, 28), Text = "RM" };
            cardStore.Controls.Add(txtCurrency);

            sY += 36;
            cardStore.Controls.Add(new Label { Text = "Receipt Header:", Location = new Point(20, sY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtReceiptHeader = new TextBox { Location = new Point(130, sY), Size = new Size(560, 56), Multiline = true };
            cardStore.Controls.Add(txtReceiptHeader);

            sY += 66;
            cardStore.Controls.Add(new Label { Text = "Receipt Footer:", Location = new Point(20, sY + 4), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtReceiptFooter = new TextBox { Location = new Point(130, sY), Size = new Size(560, 56), Multiline = true };
            cardStore.Controls.Add(txtReceiptFooter);

            pnlContainer.Controls.Add(cardStore);

            // 3. NFC Hardware Reader Card
            y += 244;
            var cardNfc = new Panel
            {
                Location = new Point(24, y),
                Size = new Size(820, 115),
                BackColor = UITheme.Surface,
                Padding = new Padding(20)
            };
            UITheme.ApplyCardBorder(cardNfc);

            var lblNfcCardTitle = new Label
            {
                Text = "NFC CARD READER BRIDGE SERVICE",
                Font = UITheme.FontSection,
                ForeColor = UITheme.Primary,
                Location = new Point(20, 14),
                AutoSize = true
            };
            cardNfc.Controls.Add(lblNfcCardTitle);

            cardNfc.Controls.Add(new Label { Text = "Bridge URL:", Location = new Point(20, 42), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            txtNfcUrl = new TextBox { Location = new Point(130, 38), Size = new Size(360, 28), Text = "http://127.0.0.1:8765" };
            cardNfc.Controls.Add(txtNfcUrl);

            btnTestNfc = new Button
            {
                Text = "📡 Check Reader Status",
                Location = new Point(510, 36),
                Size = new Size(180, 32)
            };
            UITheme.StyleSecondaryButton(btnTestNfc);
            btnTestNfc.Click += BtnTestNfc_Click;
            cardNfc.Controls.Add(btnTestNfc);

            lblNfcStatus = new Label
            {
                Text = "Hardware listener active",
                Location = new Point(130, 76),
                AutoSize = true,
                ForeColor = UITheme.TextMuted,
                Font = UITheme.FontSmall
            };
            cardNfc.Controls.Add(lblNfcStatus);
            pnlContainer.Controls.Add(cardNfc);

            // 4. Embedded API Server Card
            y += 129;
            var cardApi = new Panel
            {
                Location = new Point(24, y),
                Size = new Size(820, 115),
                BackColor = UITheme.Surface,
                Padding = new Padding(20)
            };
            UITheme.ApplyCardBorder(cardApi);

            var lblApiCardTitle = new Label
            {
                Text = "EMBEDDED HTTP API SERVER (KASHIER & APP INTEGRATION)",
                Font = UITheme.FontSection,
                ForeColor = UITheme.Primary,
                Location = new Point(20, 14),
                AutoSize = true
            };
            cardApi.Controls.Add(lblApiCardTitle);

            chkApiEnabled = new CheckBox
            {
                Text = "Enable HTTP API Server (Serves /cashier/sale, /cashier/history, /notifications/changes)",
                Location = new Point(20, 38),
                AutoSize = true,
                Font = UITheme.FontBodyBold,
                ForeColor = UITheme.TextDark
            };
            cardApi.Controls.Add(chkApiEnabled);

            cardApi.Controls.Add(new Label { Text = "Port:", Location = new Point(20, 72), AutoSize = true, Font = UITheme.FontSmallBold, ForeColor = UITheme.TextMedium });
            numApiPort = new NumericUpDown { Location = new Point(60, 68), Size = new Size(80, 28), Maximum = 65535, Value = 8000 };
            cardApi.Controls.Add(numApiPort);

            btnRestartApi = new Button
            {
                Text = "🔄 Restart API Server",
                Location = new Point(160, 66),
                Size = new Size(160, 32)
            };
            UITheme.StyleSecondaryButton(btnRestartApi);
            btnRestartApi.Click += BtnRestartApi_Click;
            cardApi.Controls.Add(btnRestartApi);

            lblApiStatus = new Label
            {
                Text = ApiServer.Instance.IsRunning ? $"✅ Active on {ApiServer.Instance.ServerUrl}" : "⏸️ API Server stopped",
                Location = new Point(340, 72),
                AutoSize = true,
                ForeColor = ApiServer.Instance.IsRunning ? UITheme.SuccessText : UITheme.TextMuted,
                Font = UITheme.FontBodyBold
            };
            cardApi.Controls.Add(lblApiStatus);
            pnlContainer.Controls.Add(cardApi);

            // 5. Save Button
            y += 130;
            btnSave = new Button
            {
                Text = "💾 Save All Settings",
                Location = new Point(24, y),
                Size = new Size(240, 46)
            };
            UITheme.StylePrimaryButton(btnSave);
            btnSave.Font = new Font("Segoe UI", 11, FontStyle.Bold);
            btnSave.Click += BtnSave_Click;
            pnlContainer.Controls.Add(btnSave);

            this.Controls.Add(pnlContainer);
        }

        private void LoadCurrentSettings()
        {
            var s = AppConfig.Current;
            rbPostgreSql.Checked = s.DatabaseType.Equals("PostgreSQL", StringComparison.OrdinalIgnoreCase);
            rbSqlite.Checked = !rbPostgreSql.Checked;

            txtPgHost.Text = s.PgHost;
            numPgPort.Value = s.PgPort;
            txtPgDb.Text = s.PgDatabase;
            txtPgUser.Text = s.PgUsername;
            txtPgPass.Text = s.PgPassword;

            txtSchoolName.Text = s.SchoolName;
            txtCurrency.Text = s.CurrencySymbol;
            txtReceiptHeader.Text = s.ReceiptHeader;
            txtReceiptFooter.Text = s.ReceiptFooter;

            txtNfcUrl.Text = s.NfcReaderUrl;

            chkApiEnabled.Checked = s.ApiEnabled;
            numApiPort.Value = s.ApiPort;
            lblApiStatus.Text = ApiServer.Instance.IsRunning ? $"✅ Active on {ApiServer.Instance.ServerUrl}" : "⏸️ API Server stopped";
            lblApiStatus.ForeColor = ApiServer.Instance.IsRunning ? Color.FromArgb(22, 163, 74) : Color.FromArgb(100, 116, 139);
        }

        private async void BtnTestDb_Click(object? sender, EventArgs e)
        {
            lblDbStatus.Text = "Testing connection...";
            lblDbStatus.ForeColor = Color.FromArgb(100, 116, 139);

            // Apply fields temporarily for test
            AppConfig.Current.DatabaseType = rbPostgreSql.Checked ? "PostgreSQL" : "SQLite";
            AppConfig.Current.PgHost = txtPgHost.Text.Trim();
            AppConfig.Current.PgPort = (int)numPgPort.Value;
            AppConfig.Current.PgDatabase = txtPgDb.Text.Trim();
            AppConfig.Current.PgUsername = txtPgUser.Text.Trim();
            AppConfig.Current.PgPassword = txtPgPass.Text;

            var result = await Task.Run(() => DatabaseService.Instance.TestConnection());
            if (result.success)
            {
                lblDbStatus.Text = "✅ " + result.message;
                lblDbStatus.ForeColor = Color.FromArgb(22, 163, 74);
            }
            else
            {
                lblDbStatus.Text = "❌ " + result.message;
                lblDbStatus.ForeColor = Color.FromArgb(220, 38, 38);
            }
        }

        private async void BtnTestNfc_Click(object? sender, EventArgs e)
        {
            lblNfcStatus.Text = "Checking NFC reader bridge...";
            try
            {
                using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(2) };
                var res = await http.GetAsync($"{txtNfcUrl.Text.Trim()}/health");
                if (res.IsSuccessStatusCode)
                {
                    lblNfcStatus.Text = "✅ NFC reader bridge service is running and ready!";
                    lblNfcStatus.ForeColor = Color.FromArgb(22, 163, 74);
                }
                else
                {
                    lblNfcStatus.Text = $"⚠️ Reader service responded with HTTP {(int)res.StatusCode}";
                    lblNfcStatus.ForeColor = Color.FromArgb(217, 119, 6);
                }
            }
            catch (Exception ex)
            {
                lblNfcStatus.Text = "❌ Could not reach NFC reader service: " + ex.Message;
                lblNfcStatus.ForeColor = Color.FromArgb(220, 38, 38);
            }
        }

        private void BtnRestartApi_Click(object? sender, EventArgs e)
        {
            ApiServer.Instance.Stop();
            if (chkApiEnabled.Checked)
            {
                bool ok = ApiServer.Instance.Start((int)numApiPort.Value);
                if (ok)
                {
                    lblApiStatus.Text = $"✅ Active on {ApiServer.Instance.ServerUrl}";
                    lblApiStatus.ForeColor = Color.FromArgb(22, 163, 74);
                }
                else
                {
                    lblApiStatus.Text = "❌ Failed to start API server.";
                    lblApiStatus.ForeColor = Color.FromArgb(220, 38, 38);
                }
            }
            else
            {
                lblApiStatus.Text = "⏸️ API Server stopped";
                lblApiStatus.ForeColor = Color.FromArgb(100, 116, 139);
            }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            var s = AppConfig.Current;
            s.DatabaseType = rbPostgreSql.Checked ? "PostgreSQL" : "SQLite";
            s.PgHost = txtPgHost.Text.Trim();
            s.PgPort = (int)numPgPort.Value;
            s.PgDatabase = txtPgDb.Text.Trim();
            s.PgUsername = txtPgUser.Text.Trim();
            s.PgPassword = txtPgPass.Text;

            s.SchoolName = txtSchoolName.Text.Trim();
            s.CurrencySymbol = txtCurrency.Text.Trim();
            s.ReceiptHeader = txtReceiptHeader.Text.Trim();
            s.ReceiptFooter = txtReceiptFooter.Text.Trim();

            s.NfcReaderUrl = txtNfcUrl.Text.Trim();

            s.ApiEnabled = chkApiEnabled.Checked;
            s.ApiPort = (int)numApiPort.Value;

            AppConfig.Save();
            MessageBox.Show("Settings saved successfully!", "Saved", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }
    }
}
