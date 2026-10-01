using System.Drawing;
using System.Windows.Forms;

namespace KoopSchoolDesktop.Services
{
    public static class UITheme
    {
        // ── Brand Colors (Matching Laravel KooPik Theme) ──
        public static readonly Color Primary = Color.FromArgb(79, 70, 229);       // Indigo 600
        public static readonly Color PrimaryHover = Color.FromArgb(67, 56, 202);  // Indigo 700
        public static readonly Color PrimaryLight = Color.FromArgb(238, 242, 255); // Indigo 50
        public static readonly Color PrimaryText = Color.FromArgb(67, 56, 202);   // Indigo 700

        // ── Backgrounds & Canvas ──
        public static readonly Color Canvas = Color.FromArgb(244, 247, 251);       // Light SaaS canvas #f4f7fb
        public static readonly Color Surface = Color.White;
        public static readonly Color Sidebar = Color.White;                       // Clean Crisp White Sidebar
        public static readonly Color Border = Color.FromArgb(226, 232, 240);       // Slate 200
        public static readonly Color BorderSubtle = Color.FromArgb(241, 245, 249); // Slate 100

        // ── Typography Colors ──
        public static readonly Color TextDark = Color.FromArgb(15, 23, 42);        // Slate 900
        public static readonly Color TextMedium = Color.FromArgb(71, 85, 105);     // Slate 600
        public static readonly Color TextMuted = Color.FromArgb(148, 163, 184);    // Slate 400

        // ── Functional Accents ──
        public static readonly Color Success = Color.FromArgb(16, 185, 129);       // Emerald 500
        public static readonly Color SuccessLight = Color.FromArgb(236, 253, 245); // Emerald 50
        public static readonly Color SuccessText = Color.FromArgb(5, 150, 105);    // Emerald 600

        public static readonly Color Warning = Color.FromArgb(245, 158, 11);       // Amber 500
        public static readonly Color WarningLight = Color.FromArgb(254, 243, 199); // Amber 50
        public static readonly Color WarningText = Color.FromArgb(180, 83, 9);     // Amber 700

        public static readonly Color Danger = Color.FromArgb(239, 68, 68);         // Rose 500
        public static readonly Color DangerLight = Color.FromArgb(254, 242, 242);  // Rose 50
        public static readonly Color DangerText = Color.FromArgb(220, 38, 38);     // Rose 600

        public static readonly Color Info = Color.FromArgb(14, 165, 233);          // Sky 500
        public static readonly Color InfoLight = Color.FromArgb(240, 249, 255);    // Sky 50
        public static readonly Color InfoText = Color.FromArgb(3, 105, 161);       // Sky 700

        // ── Typography ──
        public static readonly Font FontHeading = new Font("Segoe UI", 15, FontStyle.Bold);
        public static readonly Font FontSubheading = new Font("Segoe UI", 12, FontStyle.Bold);
        public static readonly Font FontSection = new Font("Segoe UI", 8f, FontStyle.Bold);
        public static readonly Font FontBody = new Font("Segoe UI", 9.5f, FontStyle.Regular);
        public static readonly Font FontBodyBold = new Font("Segoe UI", 9.5f, FontStyle.Bold);
        public static readonly Font FontSmall = new Font("Segoe UI", 8.5f, FontStyle.Regular);
        public static readonly Font FontSmallBold = new Font("Segoe UI", 8.5f, FontStyle.Bold);
        public static readonly Font FontMetric = new Font("Segoe UI", 18, FontStyle.Bold);

        // ── Helper: Style a DataGridView cleanly ──
        public static void StyleTable(DataGridView dgv)
        {
            dgv.BackgroundColor = Surface;
            dgv.BorderStyle = BorderStyle.None;
            dgv.CellBorderStyle = DataGridViewCellBorderStyle.SingleHorizontal;
            dgv.GridColor = Color.FromArgb(241, 245, 249);
            dgv.RowHeadersVisible = false;
            dgv.AllowUserToAddRows = false;
            dgv.AllowUserToDeleteRows = false;
            dgv.AllowUserToResizeRows = false;
            dgv.ReadOnly = true;
            dgv.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            dgv.MultiSelect = false;
            dgv.EnableHeadersVisualStyles = false;

            // Column Header Style
            dgv.ColumnHeadersBorderStyle = DataGridViewHeaderBorderStyle.None;
            dgv.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(248, 250, 252);
            dgv.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(100, 116, 139);
            dgv.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 8.5f, FontStyle.Bold);
            dgv.ColumnHeadersDefaultCellStyle.Padding = new Padding(12, 10, 12, 10);
            dgv.ColumnHeadersHeight = 42;

            // Row Default Style
            dgv.DefaultCellStyle.BackColor = Surface;
            dgv.DefaultCellStyle.ForeColor = TextDark;
            dgv.DefaultCellStyle.Font = FontBody;
            dgv.DefaultCellStyle.SelectionBackColor = PrimaryLight;
            dgv.DefaultCellStyle.SelectionForeColor = TextDark;
            dgv.DefaultCellStyle.Padding = new Padding(12, 6, 12, 6);
            dgv.RowTemplate.Height = 44;

            // Alternating Row Style
            dgv.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(250, 252, 255);
            dgv.AlternatingRowsDefaultCellStyle.ForeColor = TextDark;
            dgv.AlternatingRowsDefaultCellStyle.SelectionBackColor = PrimaryLight;
            dgv.AlternatingRowsDefaultCellStyle.SelectionForeColor = TextDark;
        }

        // ── Helper: Style a Primary Action Button ──
        public static void StylePrimaryButton(Button btn)
        {
            btn.BackColor = Primary;
            btn.ForeColor = Color.White;
            btn.FlatStyle = FlatStyle.Flat;
            btn.FlatAppearance.BorderSize = 0;
            btn.Font = FontBodyBold;
            btn.Cursor = Cursors.Hand;
        }

        // ── Helper: Style a Secondary Outline Button ──
        public static void StyleSecondaryButton(Button btn)
        {
            btn.BackColor = Surface;
            btn.ForeColor = TextMedium;
            btn.FlatStyle = FlatStyle.Flat;
            btn.FlatAppearance.BorderColor = Border;
            btn.FlatAppearance.BorderSize = 1;
            btn.Font = FontBodyBold;
            btn.Cursor = Cursors.Hand;
        }

        // ── Helper: Style a Success Button ──
        public static void StyleSuccessButton(Button btn)
        {
            btn.BackColor = Success;
            btn.ForeColor = Color.White;
            btn.FlatStyle = FlatStyle.Flat;
            btn.FlatAppearance.BorderSize = 0;
            btn.Font = FontBodyBold;
            btn.Cursor = Cursors.Hand;
        }

        // ── Helper: Style a Danger Button ──
        public static void StyleDangerButton(Button btn)
        {
            btn.BackColor = DangerLight;
            btn.ForeColor = DangerText;
            btn.FlatStyle = FlatStyle.Flat;
            btn.FlatAppearance.BorderColor = Color.FromArgb(254, 202, 202);
            btn.FlatAppearance.BorderSize = 1;
            btn.Font = FontBodyBold;
            btn.Cursor = Cursors.Hand;
        }

        // ── Helper: Apply Card Border to Any Panel ──
        public static void ApplyCardBorder(Control ctrl)
        {
            ctrl.BackColor = Surface;
            ctrl.Paint += (s, e) =>
            {
                using var pen = new Pen(Border, 1);
                e.Graphics.DrawRectangle(pen, 0, 0, ctrl.Width - 1, ctrl.Height - 1);
            };
        }

        // ── Helper: Create a Clean Card Container ──
        public static Panel CreateCard(int x, int y, int width, int height)
        {
            var pnl = new Panel
            {
                Location = new Point(x, y),
                Size = new Size(width, height),
                BackColor = Surface,
                Padding = new Padding(16)
            };
            ApplyCardBorder(pnl);
            return pnl;
        }
    }
}
