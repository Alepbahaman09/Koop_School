# Koop School - C# .NET Windows Desktop System

A native Windows Desktop Application for **Koperasi Sekolah (School Cooperative)** built with **C#** and **.NET (Windows Forms)**.

---

## 🚀 Key Modules & Features

1. **🔐 Authentication & Access**
   - Clean login window with credential check against the PostgreSQL/Supabase database or local offline database.
   - Default master credentials: `admin` / `admin123`.

2. **📊 Dashboard Overview**
   - Live KPI cards: Today's Sales, Today's Orders, Catalog Count, Low Stock Warning, Active Suppliers, Registered NFC Cards.
   - Quick action buttons to launch POS, Restock, or view Catalog.
   - Recent orders feed and critical low stock warnings.

3. **🛒 Cashier Terminal (POS)**
   - **Barcode / SKU scanner support**: Automatically adds scanned products to cart with instant Enter-key handling.
   - Live product search by name and category filtering.
   - Real-time shopping cart with quantity adjustment (`+`, `-`) and item removal.
   - Calculation of Subtotal, Discount, and Grand Total.
   - **Cash Payment Mode**: Quick shortcut cash tender buttons (RM5, RM10, RM20, RM50, RM100, Exact) and automatic Change calculation.
   - **NFC Card Payment Mode**: Integrates with local NFC card readers (or manual UID entry), checks cardholder name and balance, verifies sufficient funds, and deducts the balance in a single safe database transaction.
   - **Receipt Generation & Printing**: Built-in formatted thermal receipt preview dialog with Windows PrintDocument support.

4. **📦 Products Catalog & Inventory Management**
   - View, search, and filter all products by category.
   - Low-stock alerts highlighted in red.
   - Add new products, edit existing products, and deactivate products.
   - **Quick Stock Adjustment**: Add or deduct inventory with reason tracking (damage, recount, restock).

5. **🚚 Suppliers Directory**
   - Manage suppliers with Supplier Codes (`SUP0001`), company names, contact persons, phone numbers, emails, and addresses.
   - Add, edit, or delete suppliers.

6. **📥 Stock Purchases & Restocking**
   - Create restock purchase orders from suppliers.
   - Add multiple items with purchase cost and selling price.
   - Auto-restock inventory option to instantly increment product stock levels upon receipt.

7. **📋 Orders & Transactions History**
   - Filter transactions by date range and search by order number.
   - View detailed line-item breakdowns of past orders.
   - "Reprint Receipt" functionality.

8. **💳 Student NFC Cards & Digital Wallets**
   - NFC card reader tap detection to locate student records instantly.
   - Quick card reload/top-up dialog with preset amounts (+RM5, +RM10, +RM20, +RM50).
   - Card registration dialog for new students and staff.

9. **📈 Financial & Sales Reports**
   - Date range selector (Today, Yesterday, This Week, This Month, All Time, Custom Range).
   - Key financial indicators: Total Revenue, Estimated Cost of Goods Sold (COGS), Gross Profit, Order Count, Average Basket Size.
   - **Export to CSV / Excel**: One-click export for spreadsheet analysis.

10. **⚙️ Settings & Hardware Configuration**
    - Database mode selection: PostgreSQL (Supabase Cloud) or SQLite (Local Offline Storage).
    - Live database connection tester.
    - Store details and customizable receipt headers and footers.
    - Local NFC bridge service tester (`http://127.0.0.1:8765`).

---

## 💻 How to Run the Desktop App

### Option 1: Double-Click the Launcher
Simply double-click the `RunDesktopApp.bat` script located in `c:\Koop_School\RunDesktopApp.bat`.

### Option 2: Run via Command Line
```powershell
cd c:\Koop_School\KoopSchoolDesktop
dotnet run
```

### Option 3: Direct Executable
Launch the compiled release executable directly:
```
c:\Koop_School\KoopSchoolDesktop\bin\Release\net10.0-windows\KoopSchoolDesktop.exe
```

---

## 📁 Source Code Structure (Clean Human-Written C#)

```
KoopSchoolDesktop/
├── Models/
│   └── AppModels.cs          # Clean domain classes: Product, Supplier, Order, StudentCard, etc.
├── Services/
│   ├── AppConfig.cs          # JSON configuration & connection string manager
│   ├── DatabaseService.cs    # Direct, human-written ADO.NET PostgreSQL & SQLite queries
│   ├── NfcReaderClient.cs    # Local PC/SC NFC bridge reader polling client
│   └── ReceiptPrinter.cs     # GDI+ thermal receipt printing & preview engine
├── Forms/
│   ├── LoginForm.cs          # Login window with connection status
│   ├── MainForm.cs           # Main shell with modern sidebar navigation
│   ├── ProductEditForm.cs    # Add/edit product modal
│   ├── StockAdjustDialog.cs  # Quick stock adjustment dialog
│   ├── SupplierEditForm.cs   # Add/edit supplier modal
│   ├── StudentCardTopUpDialog.cs    # Student balance reload dialog
│   ├── StudentCardRegisterDialog.cs # New NFC card registration modal
│   ├── StockPurchaseCreateForm.cs   # Supplier purchase & restock modal
│   └── ReceiptDialog.cs      # On-screen receipt preview & print dialog
├── Views/
│   ├── DashboardView.cs      # KPI summary cards, quick actions & tables
│   ├── PosView.cs            # Cashier terminal (POS), barcode scanner, cart, cash & NFC
│   ├── ProductsView.cs       # Product catalog & inventory management
│   ├── SuppliersView.cs      # Suppliers list & directory
│   ├── StockPurchaseView.cs  # Stock purchases history
│   ├── OrdersHistoryView.cs  # Sales history & itemized breakdown
│   ├── StudentCardsView.cs   # NFC card & student wallet manager
│   ├── FinancialReportView.cs# Revenue, COGS, profit & CSV export
│   └── SettingsView.cs       # Database & receipt configuration
└── Program.cs                # Application bootstrap & entry point
```
