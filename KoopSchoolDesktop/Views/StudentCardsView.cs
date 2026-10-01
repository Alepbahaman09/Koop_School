using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Views
{
    public class StudentCardsView : UserControl
    {
        private TextBox txtSearch;
        private DataGridView dgvCards;
        private Button btnTopUp;
        private Button btnRegister;
        private Button btnRefresh;
        private Label lblScanNotice;

        private List<StudentCard> _cards = new();
        private NfcReaderClient? _nfcClient;

        public StudentCardsView()
        {
            InitializeComponent();
            InitNfcTapListener();
        }

        private void InitializeComponent()
        {
            this.Dock = DockStyle.Fill;
            this.BackColor = UITheme.Canvas;
            this.Font = UITheme.FontBody;

            var pnlTop = new Panel
            {
                Dock = DockStyle.Top,
                Height = 88,
                Padding = new Padding(20, 14, 20, 10),
                BackColor = UITheme.Surface
            };
            pnlTop.Paint += (s, e) =>
            {
                using var pen = new Pen(UITheme.Border, 1);
                e.Graphics.DrawLine(pen, 0, pnlTop.Height - 1, pnlTop.Width, pnlTop.Height - 1);
            };

            var lblTitle = new Label
            {
                Text = "Student NFC Cards & Wallets",
                Font = UITheme.FontHeading,
                ForeColor = UITheme.TextDark,
                Location = new Point(20, 10),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblTitle);

            txtSearch = new TextBox
            {
                Location = new Point(20, 44),
                Size = new Size(290, 30),
                PlaceholderText = "🔍 Search student name or UID..."
            };
            txtSearch.TextChanged += (s, e) => LoadCards();
            pnlTop.Controls.Add(txtSearch);

            btnTopUp = new Button
            {
                Text = "💳 Reload / Top-Up",
                Location = new Point(325, 42),
                Size = new Size(160, 34)
            };
            UITheme.StyleSuccessButton(btnTopUp);
            btnTopUp.Click += BtnTopUp_Click;
            pnlTop.Controls.Add(btnTopUp);

            btnRegister = new Button
            {
                Text = "➕ Register Card",
                Location = new Point(495, 42),
                Size = new Size(145, 34)
            };
            UITheme.StylePrimaryButton(btnRegister);
            btnRegister.Click += BtnRegister_Click;
            pnlTop.Controls.Add(btnRegister);

            btnRefresh = new Button
            {
                Text = "🔄",
                Location = new Point(650, 42),
                Size = new Size(40, 34)
            };
            UITheme.StyleSecondaryButton(btnRefresh);
            btnRefresh.Click += (s, e) => LoadCards();
            pnlTop.Controls.Add(btnRefresh);

            lblScanNotice = new Label
            {
                Text = "📡 Tap any card on NFC reader to locate it instantly",
                Font = UITheme.FontSmall,
                ForeColor = UITheme.TextMuted,
                Location = new Point(705, 50),
                AutoSize = true
            };
            pnlTop.Controls.Add(lblScanNotice);

            this.Controls.Add(pnlTop);

            // Grid Container Card
            var pnlGridCard = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(16),
                BackColor = UITheme.Canvas
            };

            var pnlInnerCard = new Panel
            {
                Dock = DockStyle.Fill,
                BackColor = UITheme.Surface
            };
            UITheme.ApplyCardBorder(pnlInnerCard);

            dgvCards = new DataGridView
            {
                Dock = DockStyle.Fill
            };
            UITheme.StyleTable(dgvCards);
            dgvCards.Columns.Add("Uid", "NFC Card UID");
            dgvCards.Columns["Uid"].Width = 160;
            dgvCards.Columns.Add("Owner", "Student / Owner Name");
            dgvCards.Columns.Add("Balance", $"Balance ({AppConfig.Current.CurrencySymbol})");
            dgvCards.Columns["Balance"].Width = 140;
            dgvCards.Columns.Add("Status", "Status");
            dgvCards.Columns["Status"].Width = 110;
            dgvCards.Columns.Add("LastUsed", "Last Used");
            dgvCards.Columns["LastUsed"].Width = 160;

            dgvCards.CellDoubleClick += (s, e) => BtnTopUp_Click(s, e);
            pnlInnerCard.Controls.Add(dgvCards);
            pnlGridCard.Controls.Add(pnlInnerCard);
            this.Controls.Add(pnlGridCard);

            this.Load += (s, e) => LoadCards();
        }

        private void InitNfcTapListener()
        {
            try
            {
                _nfcClient = new NfcReaderClient();
                _nfcClient.OnCardTapped += (uid) =>
                {
                    this.BeginInvoke(new Action(() =>
                    {
                        txtSearch.Text = uid;
                        lblScanNotice.Text = $"✅ Card Tapped: {uid}";
                        lblScanNotice.ForeColor = UITheme.SuccessText;
                    }));
                };
                _nfcClient.Start();
            }
            catch { }
        }

        public void LoadCards()
        {
            try
            {
                string search = txtSearch.Text.Trim();
                _cards = DatabaseService.Instance.GetStudentCards(search);

                dgvCards.Rows.Clear();
                foreach (var c in _cards)
                {
                    int rowIdx = dgvCards.Rows.Add(
                        c.CardUid,
                        c.Owner,
                        $"{AppConfig.Current.CurrencySymbol} {c.Balance:F2}",
                        c.IsFrozen ? "● FROZEN" : "● ACTIVE",
                        c.LastUsedAt.HasValue ? c.LastUsedAt.Value.ToString("dd/MM/yyyy HH:mm") : "-"
                    );

                    if (c.IsFrozen)
                    {
                        dgvCards.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.DangerText;
                    }
                    else
                    {
                        dgvCards.Rows[rowIdx].Cells["Status"].Style.ForeColor = UITheme.SuccessText;
                        dgvCards.Rows[rowIdx].Cells["Status"].Style.Font = UITheme.FontBodyBold;
                    }
                }
            }
            catch { }
        }

        private StudentCard? GetSelectedCard()
        {
            if (dgvCards.CurrentRow == null || dgvCards.CurrentRow.Index < 0) return null;
            string uid = dgvCards.CurrentRow.Cells["Uid"].Value?.ToString() ?? "";
            return _cards.FirstOrDefault(c => c.CardUid == uid);
        }

        private void BtnTopUp_Click(object? sender, EventArgs e)
        {
            var c = GetSelectedCard();
            if (c == null)
            {
                MessageBox.Show("Please select a student card to reload.", "Selection", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            using var dlg = new StudentCardTopUpDialog(c);
            if (dlg.ShowDialog(this) == DialogResult.OK)
            {
                LoadCards();
            }
        }

        private void BtnRegister_Click(object? sender, EventArgs e)
        {
            using var dlg = new StudentCardRegisterDialog();
            if (dlg.ShowDialog(this) == DialogResult.OK)
            {
                LoadCards();
            }
        }
    }
}
