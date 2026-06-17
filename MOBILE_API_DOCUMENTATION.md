# Mobile App API Documentation

## Base URL
```
http://your-domain.com/api/v1
```

## Endpoints for Mobile App to Send Data

### 1. Create Customer (Register Student)
**POST** `/customers`

**Request Body:**
```json
{
  "student_id": "STD001",
  "name": "Ahmad Iskandar",
  "email": "ahmad@example.com",
  "phone": "0123456789",
  "class": "Form 5A",
  "address": "123 Jalan Merdeka"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "student_id": "STD001",
    "name": "Ahmad Iskandar",
    "email": "ahmad@example.com",
    ...
  }
}
```

---

### 2. Create Order (From Mobile App)
**POST** `/orders`

**Request Body:**
```json
{
  "customer_id": 1,
  "user_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ],
  "tax": 5.50,
  "discount": 0,
  "notes": "Urgent order"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "KS-20250101-0001",
    "customer_id": 1,
    "status": "Pending",
    "subtotal": 150.00,
    "tax": 5.50,
    "discount": 0,
    "total_amount": 155.50,
    "payment_status": "Unpaid",
    "customer": {...},
    "orderItems": [...]
  }
}
```

---

### 3. Create Payment (From Mobile App)
**POST** `/payments`

**Request Body:**
```json
{
  "order_id": 1,
  "payment_method": "E-Wallet",
  "amount": 155.50,
  "notes": "Paid via Touch n Go"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_id": 1,
    "payment_reference": "PAY-20250101120000-000001",
    "payment_method": "E-Wallet",
    "amount": 155.50,
    "status": "Completed",
    "paid_at": "2025-01-01 12:00:00"
  }
}
```

---

### 4. Get Products (For Mobile App)
**GET** `/products`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sku": "UNI-001",
      "name": "School Uniform Set",
      "price": 85.00,
      "stock_quantity": 50,
      "category": {
        "id": 1,
        "name": "Uniforms"
      }
    }
  ]
}
```

---

### 5. Get Categories (For Mobile App)
**GET** `/categories`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Uniforms",
      "description": "School uniforms",
      "products_count": 10
    }
  ]
}
```

---

### 6. Update Order Status
**PATCH** `/orders/{id}/status`

**Request Body:**
```json
{
  "status": "Completed"
}
```

**Valid Status Values:**
- Pending
- Processing
- Packed
- Ready
- Completed
- Cancelled

---

## Data Flow: Mobile App → Database → Dashboard

### Step 1: Mobile App Sends Order
```
Mobile App → POST /api/v1/orders → Database (orders, order_items tables)
```

### Step 2: Mobile App Sends Payment
```
Mobile App → POST /api/v1/payments → Database (payments table)
                                   → Update orders.payment_status
```

### Step 3: Admin Dashboard Reads Data
```
Database → DashboardController → Dashboard View (displays all data)
```

---

## Dashboard Display Sources

The admin dashboard automatically displays data from these tables:

1. **Revenue Card**: Sum of `orders.total_amount` where `payment_status = 'Paid'`
2. **Members Card**: Count of `customers` table
3. **Total Orders Card**: Count of `orders` table
4. **Wallet Card**: Sum of `orders.total_amount` where `payment_status = 'Paid'`
5. **Sales Chart**: Daily count from `orders.created_at`
6. **Last Orders Table**: Latest 5 from `orders` with `customer` and `orderItems.product`

---

## Testing the System

### Test 1: Create a customer from mobile app
```bash
curl -X POST https://api.example.com/api/v1/customers \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": "STD001",
    "name": "Test Student",
    "email": "test@example.com",
    "phone": "0123456789",
    "class": "Form 5A"
  }'
```

### Test 2: Create an order from mobile app
```bash
curl -X POST https://api.example.com/api/v1/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "user_id": 1,
    "items": [{"product_id": 1, "quantity": 2}]
  }'
```

### Test 3: View on dashboard
```
Visit: https://admin.example.com/dashboard
```

All data sent from mobile app will immediately appear on the admin dashboard.
