# School Cooperative Database Schema

## Database: PostgreSQL

## Tables Overview

### 1. **customers** - Student/Customer Information
- `id` - Primary key
- `student_id` - Unique student ID
- `name` - Customer name
- `email` - Customer email (unique)
- `phone` - Contact number
- `class` - Student class
- `address` - Customer address
- `is_active` - Active status
- `created_at`, `updated_at` - Timestamps

### 2. **categories** - Product Categories
- `id` - Primary key
- `name` - Category name
- `is_active` - Active status
- `created_at`, `updated_at` - Timestamps

### 3. **products** - Inventory Products
- `id` - Primary key
- `category_id` - Foreign key to categories
- `sku` - Stock keeping unit (unique)
- `name` - Product name
- `description` - Product description
- `price` - Product price
- `stock_quantity` - Current stock level
- `min_stock_level` - Minimum stock alert level
- `image` - Product image path
- `is_active` - Active status
- `created_at`, `updated_at` - Timestamps

### 4. **orders** - Customer Orders
- `id` - Primary key
- `order_number` - Unique order number
- `customer_id` - Foreign key to customers
- `user_id` - Foreign key to users (staff who created order)
- `status` - Order status (Processing, Ready, Completed, Cancelled)
- `subtotal` - Order subtotal
- `tax` - Tax amount
- `discount` - Discount amount
- `total_amount` - Total order amount
- `payment_status` - Payment status (Unpaid, Partial, Paid, Refunded)
- `notes` - Order notes
- `created_at`, `updated_at` - Timestamps

### 5. **order_items** - Items in Each Order
- `id` - Primary key
- `order_id` - Foreign key to orders
- `product_id` - Foreign key to products
- `quantity` - Quantity ordered
- `unit_price` - Price per unit
- `subtotal` - Line item subtotal
- `created_at`, `updated_at` - Timestamps

### 6. **payments** - Payment Transactions
- `id` - Primary key
- `order_id` - Foreign key to orders
- `payment_reference` - Unique payment reference
- `payment_method` - Payment method (Cash, Card, Online Banking, E-Wallet, Cheque)
- `amount` - Payment amount
- `status` - Payment status (Pending, Completed, Failed, Refunded)
- `paid_at` - Payment datetime
- `notes` - Payment notes
- `created_at`, `updated_at` - Timestamps

### 7. **order_status_history** - Order Status Tracking
- `id` - Primary key
- `order_id` - Foreign key to orders
- `user_id` - Foreign key to users (who changed status)
- `status` - Status value
- `notes` - Status change notes
- `created_at`, `updated_at` - Timestamps

### 8. **inventory_transactions** - Stock Movement Tracking
- `id` - Primary key
- `product_id` - Foreign key to products
- `user_id` - Foreign key to users
- `type` - Transaction type (In, Out, Adjustment, Return)
- `quantity` - Quantity moved
- `stock_before` - Stock level before transaction
- `stock_after` - Stock level after transaction
- `reference_type` - Related model type
- `reference_id` - Related model ID
- `notes` - Transaction notes
- `created_at`, `updated_at` - Timestamps

### 9. **suppliers** - Supplier Information
- `id` - Primary key
- `name` - Supplier name
- `company_name` - Company name
- `email` - Supplier email (unique)
- `phone` - Contact number
- `address` - Supplier address
- `tax_number` - Tax registration number
- `is_active` - Active status
- `created_at`, `updated_at` - Timestamps

### 10. **purchase_orders** - Purchase Orders from Suppliers
- `id` - Primary key
- `po_number` - Unique purchase order number
- `supplier_id` - Foreign key to suppliers
- `user_id` - Foreign key to users (who created PO)
- `status` - PO status (Draft, Sent, Confirmed, Received, Completed, Cancelled)
- `total_amount` - Total PO amount
- `order_date` - Order date
- `expected_delivery_date` - Expected delivery date
- `received_date` - Actual received date
- `notes` - PO notes
- `created_at`, `updated_at` - Timestamps

### 11. **purchase_order_items** - Items in Purchase Orders
- `id` - Primary key
- `purchase_order_id` - Foreign key to purchase_orders
- `product_id` - Foreign key to products
- `quantity_ordered` - Quantity ordered
- `quantity_received` - Quantity received
- `unit_cost` - Cost per unit
- `subtotal` - Line item subtotal
- `created_at`, `updated_at` - Timestamps

## Order Flow Process

1. **Customer Registration** → customers table
2. **Product Management** → categories, products tables
3. **Order Creation** → orders table (status: Processing)
4. **Add Items to Order** → order_items table
5. **Order Processing** → orders table (status: Processing)
6. **Order Ready** → orders table (status: Ready)
7. **Payment Processing** → payments table
8. **Order Completion** → orders table (status: Completed)
9. **Status Tracking** → order_status_history table
10. **Inventory Update** → inventory_transactions table

## Purchase Order Flow

1. **Supplier Registration** → suppliers table
2. **Create Purchase Order** → purchase_orders table
3. **Add Products to PO** → purchase_order_items table
4. **Send to Supplier** → Update PO status
5. **Receive Stock** → Update quantity_received
6. **Update Inventory** → Update products.stock_quantity, create inventory_transactions

## Dashboard Data Sources

- **Revenue**: Sum of orders.total_amount where payment_status = 'Paid'
- **Members**: Count of customers table
- **Total Orders**: Count of orders table
- **Wallet**: Sum of payments.amount where status = 'Completed'
- **Sales Chart**: Daily count from orders.created_at
- **Last Orders**: Latest 5 records from orders with customer and order_items
