# Admin Panel Features

## Complete Admin Capabilities

### 1. Dashboard (Home Page)
**Route:** `/dashboard`
**Features:**
- View total revenue from paid orders
- View total customers count
- View total orders count
- View wallet balance
- View sales chart (last 10 days)
- View last 5 orders with customer info

---

### 2. Orders Management
**Route:** `/orders`

**Features:**
- ✅ View all orders from mobile app
- ✅ Search orders by order number or customer name
- ✅ Filter by status (Pending, Processing, Packed, Ready, Completed, Cancelled)
- ✅ Filter by payment status (Unpaid, Partial, Paid, Refunded)
- ✅ View order details with customer info
- ✅ View all items in each order
- ✅ View payment history for each order
- ✅ Update order status (tracks preparation progress)
- ✅ View status history (who changed, when, notes)
- ✅ Delete orders (if no payments made)

**Order Status Flow:**
1. **Pending** - Order received from mobile app
2. **Processing** - Admin starts preparing order
3. **Packed** - Items packed and ready
4. **Ready** - Order ready for pickup
5. **Completed** - Order delivered to customer
6. **Cancelled** - Order cancelled

**When admin updates status:**
- Saves in `order_status_history` table
- Records admin who made change
- Records timestamp
- Can add notes
- **Customer can see status update in mobile app**

---

### 3. Products Management (CRUD)
**Route:** `/products`

**Features:**
- ✅ View all products
- ✅ Create new product
  - Set category
  - Set SKU (unique code)
  - Set name and description
  - Set price
  - Set stock quantity
  - Set minimum stock level (alert threshold)
  - Upload product image
  - Set active/inactive status
- ✅ Edit product details
  - Update all fields
  - Change product image
- ✅ Delete product
- ✅ View low stock alerts (when stock < min_stock_level)

---

### 4. Categories Management
**Route:** `/categories`

**Features:**
- ✅ View all categories
- ✅ Create new category
- ✅ Edit category name and description
- ✅ Delete category (only if no products)
- ✅ Set category active/inactive

---

### 5. Customers Management
**Route:** `/customers`

**Features:**
- ✅ View all customers (students)
- ✅ Search by name, student ID, or email
- ✅ View customer profile
- ✅ View customer's order history
- ✅ View total orders per customer
- ✅ View all payments made by customer

---

### 6. Transactions Management
**Route:** `/transactions`

**Features:**
- ✅ View all payments
- ✅ Search by payment reference or order number
- ✅ Filter by payment status (Pending, Completed, Failed, Refunded)
- ✅ Filter by payment method (Cash, Card, Online Banking, E-Wallet, Cheque)
- ✅ View payment details
- ✅ View linked order information
- ✅ View customer who made payment

---

## Key Admin Actions

### Update Order Status & Track Preparation

**Process:**
1. Customer places order via mobile app → Status: **Pending**
2. Admin views order at `/orders`
3. Admin clicks "Update Status" → Status: **Processing**
4. Admin packs items → Status: **Packed**
5. Admin marks ready for pickup → Status: **Ready**
6. Customer picks up → Status: **Completed**

**Every status change:**
- Records in `order_status_history` table
- Saves admin ID, timestamp, and notes
- **Customer sees real-time status in mobile app via API**

---

## Customer Status Tracking (Mobile App Side)

Mobile app calls:
```
GET /api/v1/orders/{id}
```

Response includes:
```json
{
  "order_number": "KS-20250101-0001",
  "status": "Packed",
  "statusHistory": [
    {
      "status": "Pending",
      "created_at": "2025-01-01 10:00:00",
      "user": "Admin Name",
      "notes": "Order received"
    },
    {
      "status": "Processing",
      "created_at": "2025-01-01 10:30:00",
      "user": "Admin Name",
      "notes": "Started preparation"
    },
    {
      "status": "Packed",
      "created_at": "2025-01-01 11:00:00",
      "user": "Admin Name",
      "notes": "Items packed and ready"
    }
  ]
}
```

Customer sees:
- Current order status
- Status history with timestamps
- Who updated the status
- Admin notes for each status change

---

## Complete Data Flow

### Order Creation Flow
```
Customer (Mobile App) 
  → POST /api/v1/orders 
  → Database (orders, order_items) 
  → Admin sees in /orders (Status: Pending)
```

### Order Processing Flow
```
Admin updates status at /orders 
  → Database (orders.status, order_status_history) 
  → Customer checks mobile app 
  → GET /api/v1/orders/{id} 
  → Customer sees updated status
```

### Product Management Flow
```
Admin adds product at /products 
  → Database (products) 
  → Mobile app calls GET /api/v1/products 
  → Customer sees new product
```

### Transaction Tracking Flow
```
Customer pays via mobile app 
  → POST /api/v1/payments 
  → Database (payments, orders.payment_status) 
  → Admin sees in /transactions
```

---

## Admin Routes Summary

| Route | Method | Purpose |
|-------|--------|---------|
| `/dashboard` | GET | View dashboard statistics |
| `/orders` | GET | View all orders |
| `/orders/{id}` | GET | View order details |
| `/orders/{id}/status` | PATCH | Update order status |
| `/orders/{id}` | DELETE | Delete order |
| `/products` | GET | View all products |
| `/products/create` | GET | Show create product form |
| `/products` | POST | Store new product |
| `/products/{id}/edit` | GET | Show edit product form |
| `/products/{id}` | PATCH | Update product |
| `/products/{id}` | DELETE | Delete product |
| `/categories` | GET | View all categories |
| `/categories` | POST | Create category |
| `/categories/{id}` | PATCH | Update category |
| `/categories/{id}` | DELETE | Delete category |
| `/customers` | GET | View all customers |
| `/customers/{id}` | GET | View customer details |
| `/transactions` | GET | View all transactions |

---

## Database Tables Used by Admin

| Table | Admin Actions |
|-------|---------------|
| `orders` | View, Update Status, Delete |
| `order_items` | View Only |
| `order_status_history` | Create (auto), View |
| `products` | Create, Read, Update, Delete |
| `categories` | Create, Read, Update, Delete |
| `customers` | Read Only |
| `payments` | Read Only |

---

## Security Notes

- All admin routes protected by `auth` and `verified` middleware
- Only authenticated admins can access
- Order deletion blocked if payments exist
- Category deletion blocked if products exist
- All status changes logged with admin ID and timestamp
