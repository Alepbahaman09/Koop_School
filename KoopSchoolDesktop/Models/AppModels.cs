namespace KoopSchoolDesktop.Models
{
    public class Product
    {
        public long Id { get; set; }
        public long? CategoryId { get; set; }
        public string? CategoryName { get; set; }
        public long? SupplierId { get; set; }
        public string? SupplierName { get; set; }
        public string Sku { get; set; } = string.Empty;
        public string Name { get; set; } = string.Empty;
        public string? Description { get; set; }
        public decimal Price { get; set; }
        public decimal CostPrice { get; set; }
        public string Unit { get; set; } = "pcs";
        public string PurchaseUnit { get; set; } = "Unit";
        public int UnitsPerCarton { get; set; } = 1;
        public int StockQuantity { get; set; }
        public int MinStockLevel { get; set; } = 5;
        public bool IsActive { get; set; } = true;
        public string? Image { get; set; }

        public List<ProductSize> Sizes { get; set; } = new();

        public bool IsLowStock => StockQuantity > 0 && StockQuantity <= MinStockLevel;
        public bool IsOutOfStock => StockQuantity <= 0;
    }

    public class ProductSize
    {
        public long Id { get; set; }
        public long ProductId { get; set; }
        public string Size { get; set; } = string.Empty; // XS, S, M, L, XL, 2XL, 3XL, 4XL
        public int StockQuantity { get; set; }
        public decimal? Price { get; set; }

        public static readonly string[] AvailableSizes = { "XS", "S", "M", "L", "XL", "2XL", "3XL", "4XL" };
    }

    public class Category
    {
        public long Id { get; set; }
        public string Name { get; set; } = string.Empty;
        public bool IsActive { get; set; } = true;

        public override string ToString() => Name;
    }

    public class Supplier
    {
        public long Id { get; set; }
        public string SupplierId { get; set; } = string.Empty;
        public string CompanyName { get; set; } = string.Empty;
        public string? ContactPerson { get; set; }
        public string? Email { get; set; }
        public string? Phone { get; set; }
        public string? Address { get; set; }

        public int PurchasesCount { get; set; }
        public decimal TotalSpent { get; set; }

        public override string ToString() => $"{SupplierId} - {CompanyName}";
    }

    public class StudentCard
    {
        public long Id { get; set; }
        public long? UserId { get; set; }
        public string CardUid { get; set; } = string.Empty;
        public string Owner { get; set; } = string.Empty;
        public string? StudentClass { get; set; }
        public decimal Balance { get; set; }
        public bool IsFrozen { get; set; }
        public DateTime? LastUsedAt { get; set; }
    }

    public class Order
    {
        public long Id { get; set; }
        public string OrderNumber { get; set; } = string.Empty;
        public long? CustomerId { get; set; }
        public long? UserId { get; set; }
        public long? StudentId { get; set; }
        public string CustomerName { get; set; } = "Walk-in Customer";
        public string Status { get; set; } = "Completed"; // Processing, Ready, Completed, Cancelled
        public decimal Subtotal { get; set; }
        public decimal Tax { get; set; }
        public decimal Discount { get; set; }
        public decimal TotalAmount { get; set; }
        public string PaymentStatus { get; set; } = "Paid";
        public string PaymentMethod { get; set; } = "Cash"; // Cash, NFC Card, Online
        public string? Notes { get; set; }
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        public List<OrderItem> Items { get; set; } = new();

        public static readonly string[] Statuses = { "Processing", "Ready", "Completed", "Cancelled" };
    }

    public class OrderItem
    {
        public long Id { get; set; }
        public long OrderId { get; set; }
        public long? ProductId { get; set; }
        public string ProductName { get; set; } = string.Empty;
        public string? Sku { get; set; }
        public string? Size { get; set; }
        public int Quantity { get; set; }
        public decimal UnitPrice { get; set; }
        public decimal Subtotal { get; set; }
    }

    public class StockPurchase
    {
        public long Id { get; set; }
        public long SupplierId { get; set; }
        public string SupplierName { get; set; } = string.Empty;
        public DateTime PurchaseDate { get; set; } = DateTime.Today;
        public decimal TotalAmount { get; set; }
        public string Status { get; set; } = "pending"; // pending, received
        public string? Notes { get; set; }
        public string? CreatedByName { get; set; }
        public List<StockPurchaseItem> Items { get; set; } = new();
    }

    public class StockPurchaseItem
    {
        public long Id { get; set; }
        public long StockPurchaseId { get; set; }
        public long? ProductId { get; set; }
        public string ItemName { get; set; } = string.Empty;
        public string? ItemSku { get; set; }
        public int Quantity { get; set; }
        public decimal PurchasePrice { get; set; }
        public decimal SellingPrice { get; set; }
        public decimal Subtotal { get; set; }
        public string PurchaseUnit { get; set; } = "Unit";
        public int UnitsPerPurchase { get; set; } = 1;
    }

    public class CartItem
    {
        public long ProductId { get; set; }
        public string Name { get; set; } = string.Empty;
        public string Sku { get; set; } = string.Empty;
        public string? Size { get; set; }
        public decimal Price { get; set; }
        public decimal CostPrice { get; set; }
        public int Quantity { get; set; } = 1;
        public int AvailableStock { get; set; }
        public decimal Subtotal => Price * Quantity;
    }

    public class InventoryLog
    {
        public long Id { get; set; }
        public long ProductId { get; set; }
        public string ProductName { get; set; } = string.Empty;
        public string Type { get; set; } = string.Empty; // In, Out, Adjustment
        public int Quantity { get; set; }
        public int StockBefore { get; set; }
        public int StockAfter { get; set; }
        public string? ReferenceType { get; set; }
        public string? Notes { get; set; }
        public DateTime CreatedAt { get; set; }
    }

    public class ProductStats
    {
        public int Total { get; set; }
        public int LowStock { get; set; }
        public int OutOfStock { get; set; }
        public int Hidden { get; set; }
        public decimal InventoryValue { get; set; }
        public int TotalUnits { get; set; }
    }

    public class SupplierStats
    {
        public int TotalSuppliers { get; set; }
        public int TotalPurchases { get; set; }
        public decimal TotalSpent { get; set; }
    }

    public class DashboardSummary
    {
        public decimal TodaySales { get; set; }
        public int TodayOrdersCount { get; set; }
        public decimal TotalSales { get; set; }
        public int TotalOrdersCount { get; set; }
        public int TotalProducts { get; set; }
        public int LowStockCount { get; set; }
        public int TotalSuppliers { get; set; }
        public int TotalCards { get; set; }
    }

    public class AdminNotification
    {
        public long Id { get; set; }
        public string Type { get; set; } = string.Empty; // order_created, order_received, purchase, stock_low, stock_out
        public string Title { get; set; } = string.Empty;
        public string Message { get; set; } = string.Empty;
        public string? Link { get; set; }
        public string? Data { get; set; }
        public DateTime? ReadAt { get; set; }
        public DateTime CreatedAt { get; set; } = DateTime.Now;

        public bool IsRead => ReadAt.HasValue;
        public bool IsOrder => Type.Contains("order") || Type.Contains("purchase");
        public bool IsStock => Type.Contains("stock");
    }

    public class NotificationStats
    {
        public int Total { get; set; }
        public int Unread { get; set; }
        public int Orders { get; set; }
        public int Stock { get; set; }
    }
}
