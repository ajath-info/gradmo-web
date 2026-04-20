# Razorpay backend API (CodeIgniter)

This project exposes server-side Razorpay helpers: **create order**, **verify payment**, **fetch payment/order**, and **webhook**. Keys are read from **Admin → Payment settings** (`general_settings`) or optional **environment / config overrides**.

**Implementation files**

- Controller: `application/controllers/api/payment/Razorpay.php`
- Library (REST + signatures): `application/libraries/Razorpay_api.php`
- Optional config overrides: `application/config/razorpay.php`
- Routes: `application/config/routes.php` (search for `api/payment/razorpay`)

**Requirements**

- PHP **cURL** extension enabled
- Razorpay **Key ID** and **Key Secret** configured (test or live)
- For webhooks: **Webhook signing secret** (different from Key Secret) — Admin field `razorpay_webhook_secret` or env `RAZORPAY_WEBHOOK_SECRET`

---

## Base URL

Replace `{BASE}` with your site root (include path if the app is in a subdirectory).

Examples:

- `https://example.com/` → `https://example.com/index.php/api/payment/razorpay/...`  
  (or without `index.php` if URL rewriting is enabled)

All API paths below are relative to `{BASE}`.

---

## Authentication (except webhook)

Protected endpoints use the same **access token** as other app APIs (`MY_Controller`):

1. **HTTP header (preferred)**  
   `Authorization: Bearer <access_token>`

2. **Form / query / JSON body** (merged with request)  
   - `access_token`  
   - or `token`  

Allowed token user types for Razorpay routes: **`student`**, **`teacher`**, **`institute`** (`ut` in token payload).

If the token is missing, invalid, or expired, the response is:

```json
{
  "status": "false",
  "msg": "Authentication failed. Please log in again."
}
```

---

## Common response shape

- `status`: string `"true"` or `"false"` (matches other project APIs)
- `msg`: human-readable message
- Additional fields per endpoint below

---

## 1. Create order

Creates a Razorpay **Order** (server-side). Use the returned `order.id` and `keyId` on the client with [Razorpay Checkout](https://razorpay.com/docs/payments/payment-gateway/web-integration/hosted/build-integration/).

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/index.php/api/payment/razorpay/create-order` |
| **Auth** | Required (Bearer / token) |
| **Content-Type** | `application/json` (recommended) or `application/x-www-form-urlencoded` |

### Request body (JSON or form fields)

| Field | Required | Description |
|--------|----------|-------------|
| `amount_in_paise` | One of amount fields | Integer, smallest currency unit. **INR: paise.** Minimum **100** (₹1.00). |
| `amount_in_rupees` | One of amount fields | Decimal rupees; server converts × 100 to paise. Minimum **1.00** (100 paise). |
| `currency` | No | Default `INR`. |
| `receipt` | No | Your reference; max 40 chars after sanitizing (alphanumeric, `_`, `-`). |
| `notes` | No | Object; merged with server-added `uid` and `ut` from the token. |

**Note:** Send either `amount_in_paise` **or** `amount_in_rupees`, not both required; if both are sent, `amount_in_paise` wins when numeric.

### Example: cURL

```bash
curl -sS -X POST "{BASE}/index.php/api/payment/razorpay/create-order" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"amount_in_paise\":50000,\"currency\":\"INR\",\"receipt\":\"enroll_123\",\"notes\":{\"purpose\":\"batch_fee\"}}"
```

### Example: JSON body (minimal)

```json
{
  "amount_in_paise": 50000,
  "currency": "INR"
}
```

### Success response (200)

```json
{
  "status": "true",
  "msg": "Fetch Successfully.",
  "order": {
    "id": "order_xxxxxxxxxxxxxx",
    "amount": 50000,
    "currency": "INR",
    "receipt": "",
    "status": "created"
  },
  "keyId": "rzp_test_xxxx"
}
```

### Error examples

- Missing / invalid amount (`amount_in_paise` &lt; 100 and no valid rupees):

```json
{
  "status": "false",
  "msg": "Invalid amount: send amount_in_paise (min 100) or amount_in_rupees (min 1.00)."
}
```

- Keys not configured:

```json
{
  "status": "false",
  "msg": "Razorpay is not configured (key id / secret)."
}
```

- Razorpay API error (example):

```json
{
  "status": "false",
  "msg": "…description from Razorpay…",
  "httpCode": 400
}
```

---

## 2. Verify payment

Verifies the Checkout **signature** (`order_id|payment_id` HMAC with **Key Secret**). Optionally loads payment from Razorpay and may insert **`student_payment_history`** for the logged-in student.

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/index.php/api/payment/razorpay/verify-payment` |
| **Auth** | Required |

### Request body

| Field | Required | Description |
|--------|----------|-------------|
| `razorpay_order_id` | Yes | From Checkout success (`order_id`). |
| `razorpay_payment_id` | Yes | From Checkout success (`payment_id`). |
| `razorpay_signature` | Yes | From Checkout success (`signature`). |
| `student_id` | No | Must equal token `uid` when `ut` is `student`. |
| `batch_id` | No | Batch to attach payment to (with `student_id`). |

**Recording payment history:** If `student_id` + `batch_id` are sent, token `ut` must be **`student`**, `student_id` must match token `uid`, and there must be no existing row with the same `student_id`, `batch_id`, and `transaction_id` (= `razorpay_payment_id`). Amount stored is **rounded rupees** from the captured payment amount in paise (minimum 1).

### Example: cURL

```bash
curl -sS -X POST "{BASE}/index.php/api/payment/razorpay/verify-payment" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"razorpay_order_id\":\"order_xxx\",\"razorpay_payment_id\":\"pay_xxx\",\"razorpay_signature\":\"SIGNATURE\",\"student_id\":42,\"batch_id\":7}"
```

### Example: JSON body

```json
{
  "razorpay_order_id": "order_xxxxxxxxxxxxxx",
  "razorpay_payment_id": "pay_xxxxxxxxxxxxxx",
  "razorpay_signature": "hex_signature_from_checkout"
}
```

### Success response

```json
{
  "status": "true",
  "msg": "Payment verified.",
  "payment": {
    "id": "pay_xxxxxxxxxxxxxx",
    "orderId": "order_xxxxxxxxxxxxxx",
    "amountPaise": 50000,
    "currency": "INR",
    "gatewayStatus": "captured"
  },
  "recordedInHistory": false
}
```

`recordedInHistory` is `true` only when a new `student_payment_history` row was inserted.

### Error examples

```json
{
  "status": "false",
  "msg": "razorpay_order_id, razorpay_payment_id, and razorpay_signature are required."
}
```

```json
{
  "status": "false",
  "msg": "Invalid payment signature."
}
```

---

## 3. Fetch payment

Loads a single payment from Razorpay by id (debug / reconciliation).

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/index.php/api/payment/razorpay/fetch-payment` |
| **Auth** | Required |

### Request body / query

| Field | Required | Description |
|--------|----------|-------------|
| `payment_id` | Yes | Razorpay payment id, e.g. `pay_xxx`. |

### Example: cURL

```bash
curl -sS -X POST "{BASE}/index.php/api/payment/razorpay/fetch-payment" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"payment_id\":\"pay_xxxxxxxxxxxxxx\"}"
```

### Success response (shape)

```json
{
  "status": "true",
  "msg": "Fetch Successfully.",
  "httpCode": 200,
  "payment": { }
}
```

`payment` is the JSON object returned by Razorpay (fields vary). On failure, `status` is `"false"` and `payment` may be `null`.

---

## 4. Order status

Loads a Razorpay **order** by id.

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/index.php/api/payment/razorpay/order-status` |
| **Auth** | Required |

### Request body / query

| Field | Required | Description |
|--------|----------|-------------|
| `order_id` | Yes | Razorpay order id, e.g. `order_xxx`. |

### Example: cURL

```bash
curl -sS -X POST "{BASE}/index.php/api/payment/razorpay/order-status" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"order_id\":\"order_xxxxxxxxxxxxxx\"}"
```

### Success response (shape)

```json
{
  "status": "true",
  "msg": "Fetch Successfully.",
  "httpCode": 200,
  "order": { }
}
```

---

## 5. Webhook (Razorpay → your server)

Razorpay calls your URL **without** your app Bearer token. The handler validates **`X-Razorpay-Signature`** using the **webhook signing secret** (not the API Key Secret).

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/index.php/api/payment/razorpay/webhook` |
| **Auth** | None (server-to-server) |
| **Body** | Raw JSON event (as sent by Razorpay) |
| **Header** | `X-Razorpay-Signature`: HMAC SHA256 hex of raw body with webhook secret |

### Setup

1. Razorpay Dashboard → **Webhooks** → Add URL:  
   `{BASE}/index.php/api/payment/razorpay/webhook`
2. Copy the **signing secret** into Admin → **Payment settings** → **Razorpay webhook secret**, **or** set env `RAZORPAY_WEBHOOK_SECRET`, **or** `razorpay_webhook_secret` in `application/config/razorpay.php`.

### Behaviour

- Returns **400** if webhook secret is empty, body is empty, or signature is invalid.
- Returns **200** and echoes acknowledgment JSON after verification.  
  **You can extend** `webhook()` in `Razorpay.php` to handle `payment.captured`, `order.paid`, etc., using the parsed `$event`.

### Example response (200)

```json
{
  "status": "true",
  "msg": "Webhook received",
  "event": "payment.captured"
}
```

---

## Configuration reference

### Database (`general_settings.key_text`)

| key_text | Purpose |
|----------|---------|
| `razorpay_key_id` | Key ID (safe to expose to client for Checkout) |
| `razorpay_secret_key` | Key Secret (server only; used for Orders API + payment signature verify) |
| `razorpay_webhook_secret` | Webhook signing secret (server only) |

New installs: see `installer/default.sql` (row id `18` for webhook secret).  
Existing DB: run `installer/alter_general_settings_razorpay_webhook_secret.sql` if the row is missing.

### Optional: `application/config/razorpay.php` / environment

If a value in config is **non-empty**, it overrides `general_settings` for that value:

| Config key | Environment variable (if used in your deployment) |
|------------|-----------------------------------------------------|
| `razorpay_key_id` | `RAZORPAY_KEY_ID` |
| `razorpay_key_secret` | `RAZORPAY_KEY_SECRET` |
| `razorpay_webhook_secret` | `RAZORPAY_WEBHOOK_SECRET` |

---

## Suggested mobile / web client flow

1. User logs in → receive `access_token` (existing login API).
2. **POST** `create-order` with amount → receive `order.id` and `keyId`.
3. Open Razorpay Checkout with `key: keyId`, `order_id`, `amount`, `currency`, `name`, `description`, `prefill`, etc. (per Razorpay Web docs).
4. On success handler, receive `razorpay_payment_id`, `razorpay_order_id`, `razorpay_signature`.
5. **POST** `verify-payment` with those three fields (and optional `student_id` / `batch_id` for history).
6. Treat payment as complete only when `verify-payment` returns `"status":"true"`.

---

## Official Razorpay documentation

- [Orders API](https://razorpay.com/docs/api/orders/)  
- [Payments API](https://razorpay.com/docs/api/payments/)  
- [Webhooks](https://razorpay.com/docs/webhooks/)  
- [Checkout](https://razorpay.com/docs/payments/payment-gateway/web-integration/hosted/build-integration/)

---

## Support notes

- Amounts for **INR** must be in **paise** when using `amount_in_paise` (integer).
- **Never** send Key Secret or webhook secret to the client app.
- Razorpay errors are proxied in `msg` / `httpCode` where applicable; see Razorpay dashboard logs for full detail.
