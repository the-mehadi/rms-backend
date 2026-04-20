<!-- API Documentation: RMS Merged Billing System -->

# RMS Merged Billing API Documentation

## Overview

The billing system now supports merging multiple unpaid orders from a table into a single bill. This allows customers to add orders incrementally and pay for all orders at once.

## New & Updated Endpoints

### 1. Get Table Bill Summary (NEW)

**Endpoint:** `GET /api/tables/{table_id}/bill`  
**Auth:** Required (auth:sanctum, role:admin,cashier)

**Description:** Get a summary of all unpaid orders for a table (merged totals)

**Response:**

```json
{
    "success": true,
    "message": "Bill summary retrieved successfully.",
    "data": {
        "table_id": 8,
        "order_ids": [1, 2, 3],
        "subtotal": 1500.0,
        "vat": 75.0,
        "discount": 0,
        "grand_total": 1575.0,
        "order_count": 3
    }
}
```

---

### 2. Get All Unpaid Orders for Table (UPDATED)

**Endpoint:** `GET /api/orders/table/{table_id}`  
**Auth:** Required (auth:sanctum, role:cashier,admin)

**Description:** Returns ALL unpaid orders for a table (not just active)

**Response:**

```json
{
    "success": true,
    "message": "Unpaid orders retrieved successfully.",
    "data": [
        {
            "id": 1,
            "table": {
                "id": 8,
                "table_number": 8,
                "capacity": 4,
                "status": "occupied"
            },
            "user": {
                "id": 2,
                "name": "Cashier 1",
                "email": "cashier@example.com"
            },
            "items": [
                {
                    "id": 1,
                    "quantity": 2,
                    "price": 250.0,
                    "menu_item": { "id": 5, "name": "Biryani", "price": 250.0 }
                }
            ],
            "status": "served",
            "special_note": null,
            "created_at": "2026-04-21T10:30:00Z"
        },
        {
            "id": 2,
            "table": {
                "id": 8,
                "table_number": 8,
                "capacity": 4,
                "status": "occupied"
            },
            "user": {
                "id": 2,
                "name": "Cashier 1",
                "email": "cashier@example.com"
            },
            "items": [
                {
                    "id": 3,
                    "quantity": 1,
                    "price": 350.0,
                    "menu_item": {
                        "id": 7,
                        "name": "Tandoori Chicken",
                        "price": 350.0
                    }
                }
            ],
            "status": "served",
            "special_note": null,
            "created_at": "2026-04-21T10:45:00Z"
        }
    ]
}
```

---

### 3. Create Merged Bill (UPDATED)

**Endpoint:** `POST /api/bills`  
**Auth:** Required (auth:sanctum, role:admin,cashier)

**Description:** Create a bill by merging all unpaid orders for a table

**Request Body:**

```json
{
    "table_id": 8,
    "discount": 100.0,
    "vat": 5
}
```

**Parameters:**

- `table_id` (required): Table ID
- `discount` (optional, default: 0): Discount amount
- `vat` (optional, default: 5): VAT percentage (5 = 5%)

**Response:**

```json
{
    "success": true,
    "message": "Bill created successfully.",
    "data": {
        "id": 15,
        "table_id": 8,
        "table": {
            "id": 8,
            "table_number": 8,
            "capacity": 4,
            "status": "occupied"
        },
        "orders": [
            {
                "id": 1,
                "table": {
                    "id": 8,
                    "table_number": 8,
                    "capacity": 4,
                    "status": "occupied"
                },
                "user": { "id": 2, "name": "Cashier 1" },
                "items": [
                    {
                        "id": 1,
                        "quantity": 2,
                        "price": 250.0,
                        "menu_item": {
                            "id": 5,
                            "name": "Biryani",
                            "price": 250.0
                        }
                    }
                ],
                "status": "served"
            },
            {
                "id": 2,
                "table": {
                    "id": 8,
                    "table_number": 8,
                    "capacity": 4,
                    "status": "occupied"
                },
                "user": { "id": 2, "name": "Cashier 1" },
                "items": [
                    {
                        "id": 3,
                        "quantity": 1,
                        "price": 350.0,
                        "menu_item": {
                            "id": 7,
                            "name": "Tandoori Chicken",
                            "price": 350.0
                        }
                    }
                ],
                "status": "served"
            }
        ],
        "user_id": 2,
        "cashier": { "id": 2, "name": "Cashier 1" },
        "subtotal": 1500.0,
        "discount": 100.0,
        "vat": 70.0,
        "total_amount": 1470.0,
        "status": "unpaid",
        "payments": [],
        "created_at": "2026-04-21T11:00:00Z"
    }
}
```

**Error Responses:**

- `400`: No unpaid orders found for table
- `404`: Table not found

---

### 4. Get Bill Details (UPDATED)

**Endpoint:** `GET /api/bills/{id}`  
**Auth:** Required (auth:sanctum, role:admin,cashier)

**Response:** (Same structure as bill creation response above)

---

### 5. Record Payment (UPDATED)

**Endpoint:** `POST /api/payments`  
**Auth:** Required (auth:sanctum, role:admin,cashier)

**Description:** Record a payment for a bill. When fully paid, table is automatically freed.

**Request Body:**

```json
{
    "bill_id": 15,
    "payment_method": "cash",
    "amount": 1470.0
}
```

**Parameters:**

- `bill_id` (required): Bill ID
- `payment_method` (required): One of `cash`, `bkash`, `card`
- `amount` (required): Payment amount

**Response:**

```json
{
    "success": true,
    "message": "Payment recorded successfully.",
    "data": {
        "id": 25,
        "bill_id": 15,
        "payment_method": "cash",
        "amount": 1470.0,
        "paid_at": "2026-04-21T11:05:00Z",
        "created_at": "2026-04-21T11:05:00Z"
    }
}
```

**Automatic Actions:**

- If payment equals remaining balance, bill status → "paid"
- When bill becomes "paid", table status → "free"
- Table can now accept new orders

**Error Responses:**

- `400`: Payment amount exceeds remaining balance
- `400`: Payment amount must be > 0
- `404`: Bill not found

---

### 6. Get Receipt (UPDATED)

**Endpoint:** `GET /api/bills/{id}/receipt`  
**Auth:** Required (auth:sanctum, role:admin,cashier)

**Response:**

```json
{
  "success": true,
  "message": "Receipt retrieved successfully.",
  "data": {
    "bill": {
      "id": 15,
      "table_id": 8,
      "table": { "id": 8, "table_number": 8, "capacity": 4, "status": "free" },
      "orders": [...],
      "subtotal": 1500.00,
      "discount": 100.00,
      "vat": 70.00,
      "total_amount": 1470.00,
      "status": "paid"
    },
    "orders": [...],
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "menu_item_id": 5,
        "quantity": 2,
        "price": 250.00,
        "menu_item": { "id": 5, "name": "Biryani", "price": 250.00 }
      }
    ],
    "payments": [
      {
        "id": 25,
        "bill_id": 15,
        "payment_method": "cash",
        "amount": 1470.00,
        "paid_at": "2026-04-21T11:05:00Z"
      }
    ]
  }
}
```

---

## Business Flow

### Typical Workflow

1. **Customer places first order**

    ```
    POST /api/orders
    table_id: 8
    ```

    → Order 1 created with status "pending"

2. **Order is prepared and served**

    ```
    PATCH /api/orders/1/status
    status: "served"
    ```

3. **Customer places another order**

    ```
    POST /api/orders
    table_id: 8
    ```

    → Order 2 created (table already occupied)

4. **Second order is also served**

    ```
    PATCH /api/orders/2/status
    status: "served"
    ```

5. **Check current bill before creating**

    ```
    GET /api/tables/8/bill
    ```

    → Returns summary: subtotal 1500, vat 75, total 1575

6. **Create merged bill (combines orders 1 & 2)**

    ```
    POST /api/bills
    table_id: 8
    discount: 100
    ```

    → Bill 15 created with orders 1 & 2, total: 1470

7. **Process payment**
    ```
    POST /api/payments
    bill_id: 15
    payment_method: "cash"
    amount: 1470
    ```
    → Table 8 status automatically set to "free"

---

## Key Changes from Previous System

| Feature             | Before                | After                           |
| ------------------- | --------------------- | ------------------------------- |
| **Bill Creation**   | Single order per bill | Multiple orders merged per bill |
| **Bill Input**      | `order_id`            | `table_id`                      |
| **Orders Endpoint** | Single active order   | All unpaid orders               |
| **Table Status**    | Manual management     | Automatic (freed on payment)    |
| **Bill Totals**     | Single order items    | All orders merged               |
| **Payment Link**    | Payment → Order       | Payment → Bill → Orders         |

---

## Status Values

### Table Status

- `free` - Available for new orders
- `occupied` - Has unpaid/pending orders
- `reserved` - Reserved but no active orders

### Order Status

- `pending` - Just created, awaiting kitchen
- `preparing` - Being prepared in kitchen
- `ready` - Ready to serve
- `served` - Served to customer
- `cancelled` - Cancelled order

### Bill Status

- `unpaid` - Created but not fully paid
- `paid` - Fully paid, ready for new orders

---

## Examples

### Example 1: Viewing Unpaid Orders

```bash
curl -X GET "http://localhost/api/orders/table/8" \
  -H "Authorization: Bearer {token}"
```

### Example 2: Creating a Merged Bill with Discount

```bash
curl -X POST "http://localhost/api/bills" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "table_id": 8,
    "discount": 100,
    "vat": 5
  }'
```

### Example 3: Full Payment

```bash
curl -X POST "http://localhost/api/payments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bill_id": 15,
    "payment_method": "cash",
    "amount": 1470
  }'
```

### Example 4: Partial Payment

```bash
curl -X POST "http://localhost/api/payments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bill_id": 15,
    "payment_method": "card",
    "amount": 735
  }'

# Bill status remains "unpaid"
# Table status remains "occupied"

# Second payment
curl -X POST "http://localhost/api/payments" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "bill_id": 15,
    "payment_method": "cash",
    "amount": 735
  }'

# Now bill status → "paid" and table status → "free"
```
