using System.Drawing;
using System.Drawing.Printing;
using KoopSchoolDesktop.Models;

namespace KoopSchoolDesktop.Services
{
    public class ReceiptPrinter
    {
        private readonly Order _order;
        private readonly List<CartItem> _items;
        private readonly decimal _cashTendered;
        private readonly decimal _change;

        public ReceiptPrinter(Order order, List<CartItem> items, decimal cashTendered = 0, decimal change = 0)
        {
            _order = order;
            _items = items;
            _cashTendered = cashTendered;
            _change = change;
        }

        public void PrintPreview(IWin32Window owner)
        {
            try
            {
                var doc = CreatePrintDocument();
                using var preview = new PrintPreviewDialog
                {
                    Document = doc,
                    Width = 450,
                    Height = 650,
                    StartPosition = FormStartPosition.CenterParent
                };
                preview.ShowDialog(owner);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Unable to display receipt preview: {ex.Message}", "Receipt Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        public void PrintDirect()
        {
            try
            {
                var doc = CreatePrintDocument();
                doc.Print();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Unable to print receipt: {ex.Message}", "Printer Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }

        private PrintDocument CreatePrintDocument()
        {
            var doc = new PrintDocument();
            doc.DefaultPageSettings.PaperSize = new PaperSize("Receipt", 300, 800);
            doc.DefaultPageSettings.Margins = new Margins(10, 10, 10, 10);
            doc.PrintPage += Doc_PrintPage;
            return doc;
        }

        private void Doc_PrintPage(object sender, PrintPageEventArgs e)
        {
            var g = e.Graphics;
            if (g == null) return;

            using var fontTitle = new Font("Courier New", 11, FontStyle.Bold);
            using var fontBold = new Font("Courier New", 9, FontStyle.Bold);
            using var fontRegular = new Font("Courier New", 8.5f, FontStyle.Regular);
            using var brush = new SolidBrush(Color.Black);

            float y = 15;
            float left = 15;
            float right = 265;
            float width = right - left;

            // Header
            var titleFormat = new StringFormat { Alignment = StringAlignment.Center };
            g.DrawString(AppConfig.Current.SchoolName, fontTitle, brush, new RectangleF(left, y, width, 20), titleFormat);
            y += 22;

            string[] headerLines = AppConfig.Current.ReceiptHeader.Split('\n');
            foreach (var line in headerLines)
            {
                g.DrawString(line.Trim(), fontRegular, brush, new RectangleF(left, y, width, 16), titleFormat);
                y += 16;
            }

            y += 5;
            g.DrawString(new string('-', 34), fontRegular, brush, left, y);
            y += 15;

            // Order Metadata
            g.DrawString($"Receipt No: {_order.OrderNumber}", fontBold, brush, left, y);
            y += 16;
            g.DrawString($"Date/Time : {_order.CreatedAt:dd/MM/yyyy HH:mm:ss}", fontRegular, brush, left, y);
            y += 16;
            g.DrawString($"Customer  : {_order.CustomerName}", fontRegular, brush, left, y);
            y += 16;
            g.DrawString($"Payment   : {_order.PaymentMethod.ToUpper()}", fontRegular, brush, left, y);
            y += 16;

            g.DrawString(new string('-', 34), fontRegular, brush, left, y);
            y += 15;

            // Table Header
            g.DrawString("ITEM", fontBold, brush, left, y);
            g.DrawString("QTY x PRICE", fontBold, brush, left + 100, y);
            var rightAlign = new StringFormat { Alignment = StringAlignment.Far };
            g.DrawString("TOTAL", fontBold, brush, right, y, rightAlign);
            y += 18;

            // Items
            foreach (var item in _items)
            {
                // Item name
                string itemName = item.Name.Length > 20 ? item.Name.Substring(0, 19) + "…" : item.Name;
                g.DrawString(itemName, fontRegular, brush, left, y);
                y += 15;

                // Qty x Price
                string qtyPrice = $"{item.Quantity} x {AppConfig.Current.CurrencySymbol} {item.Price:F2}";
                g.DrawString(qtyPrice, fontRegular, brush, left + 15, y);

                // Total
                string itemTotal = $"{AppConfig.Current.CurrencySymbol} {item.Subtotal:F2}";
                g.DrawString(itemTotal, fontRegular, brush, right, y, rightAlign);
                y += 17;
            }

            g.DrawString(new string('-', 34), fontRegular, brush, left, y);
            y += 15;

            // Totals
            g.DrawString("Subtotal:", fontRegular, brush, left, y);
            g.DrawString($"{AppConfig.Current.CurrencySymbol} {_order.Subtotal:F2}", fontRegular, brush, right, y, rightAlign);
            y += 16;

            if (_order.Discount > 0)
            {
                g.DrawString("Discount:", fontRegular, brush, left, y);
                g.DrawString($"-{AppConfig.Current.CurrencySymbol} {_order.Discount:F2}", fontRegular, brush, right, y, rightAlign);
                y += 16;
            }

            g.DrawString("GRAND TOTAL:", fontBold, brush, left, y);
            g.DrawString($"{AppConfig.Current.CurrencySymbol} {_order.TotalAmount:F2}", fontBold, brush, right, y, rightAlign);
            y += 20;

            if (_order.PaymentMethod.Equals("cash", StringComparison.OrdinalIgnoreCase))
            {
                g.DrawString("Cash Tendered:", fontRegular, brush, left, y);
                g.DrawString($"{AppConfig.Current.CurrencySymbol} {_cashTendered:F2}", fontRegular, brush, right, y, rightAlign);
                y += 16;
                g.DrawString("Change Returned:", fontBold, brush, left, y);
                g.DrawString($"{AppConfig.Current.CurrencySymbol} {_change:F2}", fontBold, brush, right, y, rightAlign);
                y += 20;
            }

            g.DrawString(new string('=', 34), fontRegular, brush, left, y);
            y += 15;

            // Footer
            string[] footerLines = AppConfig.Current.ReceiptFooter.Split('\n');
            foreach (var line in footerLines)
            {
                g.DrawString(line.Trim(), fontRegular, brush, new RectangleF(left, y, width, 16), titleFormat);
                y += 16;
            }
        }
    }
}
