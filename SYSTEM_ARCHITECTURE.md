# School Cooperative System Architecture

## System Overview

This is an **Admin Dashboard System** that displays data sent from a **Mobile App**.

```
┌─────────────────┐
│   Mobile App    │  (Students/Staff use mobile app to create orders)
└────────┬────────┘
         │
         │ HTTP POST/GET
         │ (API Calls)
         ▼
┌─────────────────────────────────────────────┐
│         Laravel Backend API                  │
│                                             │
│  POST /api/v1/customers   (Register)       │
│  POST /api/v1/orders      (Create Order)   │
│  POST /api/v1/payments    (Pay Order)      │
│  GET  /api/v1/products    (View Products)  │
└────────┬────────────────────────────────────┘
         │
         │ Store/Retrieve Data
         ▼
┌─────────────────────────────────────────────┐
│       PostgreSQL Database                    │
│                                             │
│  Tables:                                    │
│  - customers                                │
│  - orders                                   │
│  - order_items                              │
│  - payments                                 │
│  - products                                 │
│  - categories                               │
│  - inventory_transactions                   │
│  - purchase_orders                          │
│  - suppliers                                │
└────────┬────────────────────────────────────┘
         │
         │ Read Data
         ▼
┌─────────────────────────────────────────────┐
│       Admin Dashboard (Web)                  │
│                                             │
│  /dashboard  (View all data)                │
│  - Revenue statistics                       │
│  - Order counts                             │
│  - Customer counts                          │
│  - Latest orders                            │
│  - Sales charts                             │
└─────────────────────────────────────────────┘
         ▲
         │
    ┌───┴───┐
    │ Admin │ (Views data only)
    └───────┘
```

## Data Flow

### Flow 1: Mobile App Creates Order
```
1. Student opens mobile app
2. Selects products
3. Mobile app sends POST /api/v1/orders
4. API validates and stores in database:
   - orders table (main order)
   - order_items table (products in order)
5. API returns order number to mobile app
```

### Flow 2: Mobile App Makes Payment
```
1. Student makes payment in mobile app
2. Mobile app sends POST /api/v1/payments
3. API stores in database:
   - payments table (payment record)
   - Updates orders.payment_status
4. API returns payment confirmation
```

### Flow 3: Admin Views Dashboard
```
1. Admin opens browser
2. Goes to /dashboard
3. DashboardController queries database:
   - SELECT SUM(total_amount) FROM orders WHERE payment_status='Paid'
   - SELECT COUNT(*) FROM orders
   - SELECT COUNT(*) FROM customers
   - SELECT * FROM orders ORDER BY created_at DESC LIMIT 5
4. Dashboard displays real-time data
```

## Key Points

✅ **NO manual data entry in admin dashboard**
- All data comes from mobile app via API

✅ **Dashboard is READ-ONLY display**
- Shows live data from PostgreSQL database

✅ **Automatic updates**
- When mobile app creates order → Dashboard shows it immediately
- When mobile app makes payment → Dashboard updates revenue instantly

✅ **Complete tracking**
- Every order stored in database
- Every payment recorded
- Every status change tracked

## API Endpoints (For Mobile App)

### Create Data (Mobile App → Database)
- `POST /api/v1/customers` - Register new customer
- `POST /api/v1/orders` - Create new order
- `POST /api/v1/payments` - Record payment

### Read Data (Mobile App ← Database)
- `GET /api/v1/products` - Get available products
- `GET /api/v1/categories` - Get product categories
- `GET /api/v1/orders` - Get order list
- `GET /api/v1/orders/{id}` - Get order details

## Dashboard Display Logic

File: `app/Http/Controllers/DashboardController.php`

```php
// Revenue: Sum of paid orders
$totalRevenue = Order::where('payment_status', 'Paid')->sum('total_amount');

// Members: Count customers
$totalMembers = Customer::count();

// Orders: Count all orders
$totalOrders = Order::count();

// Last 5 Orders: Latest orders with customer and items
$orders = Order::with(['customer', 'orderItems.product'])
    ->latest()
    ->take(5)
    ->get();
```

## Database Tables Used

| Table | Purpose | Created By |
|-------|---------|------------|
| customers | Student information | Mobile App API |
| orders | Order records | Mobile App API |
| order_items | Products in each order | Mobile App API |
| payments | Payment transactions | Mobile App API |
| products | Product catalog | Admin (manual entry) |
| categories | Product categories | Admin (manual entry) |

## Testing

### 1. Check if tables are empty:
```bash
php artisan tinker --execute="echo 'Orders: ' . App\Models\Order::count();"
```

### 2. Mobile app creates order via API:
```bash
curl -X POST http://localhost:8000/api/v1/orders -H "Content-Type: application/json" -d '{...}'
```

### 3. Check dashboard displays order:
```
Open browser: http://localhost:8000/dashboard
```

### 4. Verify data in database:
```bash
php artisan tinker --execute="App\Models\Order::with('customer')->get();"
```

## No Sample Data

- All tables are EMPTY by default
- NO dummy data inserted
- NO test orders created
- System waits for REAL data from mobile app

When mobile app starts sending orders, dashboard will display them immediately.
