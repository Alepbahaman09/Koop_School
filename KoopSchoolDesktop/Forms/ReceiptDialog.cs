using KoopSchoolDesktop.Models;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop.Forms
{
    public class ReceiptDialog : Form
    {
        private readonly Order _order;
        private readonly List<CartItem> _items;
        private readonly decimal _cashTendered;
        private readonly decimal _change;
        private RichTextBox rtbReceipt;
        private Button btnPrint;
        private Button btnClose;

        public ReceiptDialog(Order order, List<CartItem> items, decimal cashTendered = 0, decimal change = 0)
        {
            _order = order;
            _items = items;
            _cashTendered = cashTendered;
            _change = change;
            InitializeComponent();
            GenerateReceiptText();
        }

        private void InitializeComponent()
        {
            this.Text = $"Receipt - {_order.OrderNumber}";
            this.Size = new Size(380, 560);
            this.StartPosition = FormStartPosition.CenterParent;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.BackColor = Color.FromArgb(248, 249, 250);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            rtbReceipt = new RichTextBox
            {
                Location = new Point(15, 15),
                Size = new Size(335, 430),
                ReadOnly = true,
                BackColor = Color.White,
                Font = new Font("Consolas", 9.5f, FontStyle.Regular),
                BorderStyle = BorderStyle.FixedSingle
            };
            this.Controls.Add(rtbReceipt);

            btnPrint = new Button
            {
                Text = "🖨️ Print Receipt",
                Location = new Point(15, 460),
                Size = new Size(200, 42),
                BackColor = Color.FromArgb(37, 99, 235),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 9.5f, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
            btnPrint.FlatAppearance.BorderSize = 0;
            btnPrint.Click += (s, e) =>
            {
                var printer = new ReceiptPrinter(_order, _items, _cashTendered, _change);
                printer.PrintPreview(this);
            };

            btnClose = new Button
            {
                Text = "Close",
                Location = new Point(225, 460),
                Size = new Size(125, 42),
                BackColor = Color.FromArgb(226, 232, 240),
                ForeColor = Color.FromArgb(71, 85, 105),
                FlatStyle = FlatStyle.Flat,
                Cursor = Cursors.Hand
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.Click += (s, e) => this.Close();

            this.Controls.AddRange(new Control[] { btnPrint, btnClose });
        }

        private void GenerateReceiptText()
        {
            var sb = new System.Text.StringBuilder();
            sb.AppendLine("========================================");
            sb.AppendLine($"         {AppConfig.Current.SchoolName}");
            foreach (var h in AppConfig.Current.ReceiptHeader.Split('\n'))
            {
                sb.AppendLine($"   {h.Trim()}");
            }
            sb.AppendLine("========================================");
            sb.AppendLine($"Receipt No : {_order.OrderNumber}");
            sb.AppendLine($"Date/Time  : {_order.CreatedAt:dd/MM/yyyy HH:mm:ss}");
            sb.AppendLine($"Customer   : {_order.CustomerName}");
            sb.AppendLine($"Payment    : {_order.PaymentMethod.ToUpper()}");
            sb.AppendLine("----------------------------------------");
            sb.AppendLine(string.Format("{0,-18} {1,5} {2,12}", "ITEM", "QTY", "TOTAL"));
            sb.AppendLine("----------------------------------------");

            foreach (var itm in _items)
            {
                string name = itm.Name.Length > 18 ? itm.Name.Substring(0, 17) + "…" : itm.Name;
                string total = $"{AppConfig.Current.CurrencySymbol} {itm.Subtotal:F2}";
                sb.AppendLine(string.Format("{0,-18} {1,5} {2,12}", name, itm.Quantity, total));
            }

            sb.AppendLine("----------------------------------------");
            sb.AppendLine(string.Format("{0,-20} {1,17}", "Subtotal:", $"{AppConfig.Current.CurrencySymbol} {_order.Subtotal:F2}"));
            if (_order.Discount > 0)
            {
                sb.AppendLine(string.Format("{0,-20} {1,17}", "Discount:", $"-{AppConfig.Current.CurrencySymbol} {_order.Discount:F2}"));
            }
            sb.AppendLine(string.Format("{0,-20} {1,17}", "TOTAL AMOUNT:", $"{AppConfig.Current.CurrencySymbol} {_order.TotalAmount:F2}"));

            if (_order.PaymentMethod.Equals("cash", StringComparison.OrdinalIgnoreCase))
            {
                sb.AppendLine(string.Format("{0,-20} {1,17}", "Cash Tendered:", $"{AppConfig.Current.CurrencySymbol} {_cashTendered:F2}"));
                sb.AppendLine(string.Format("{0,-20} {1,17}", "Change:", $"{AppConfig.Current.CurrencySymbol} {_change:F2}"));
            }

            sb.AppendLine("========================================");
            foreach (var f in AppConfig.Current.ReceiptFooter.Split('\n'))
            {
                sb.AppendLine($"   {f.Trim()}");
            }
            sb.AppendLine("========================================");

            rtbReceipt.Text = sb.ToString();
        }
    }
}
