# LiteZ — CRM + Catalog & Inventory + Delivery REST API

Uchta moduldan iborat REST API platforma:

1. **CRM-panel** — Vazifalar va eslatmalar (Tasks & Reminders). Menejerlar vazifalar yaratishlari, ularni mijozlarga bog'lashlari, avtomatik eslatmalar olishlari va takrorlanuvchi vazifalarni sozlashlari mumkin.
2. **Catalog & Inventory** — Mahsulotlar katalogi va ombor boshqaruvi. Kategoriya daraxti, mahsulotlar CRUD, atributlar, stock adjust va harakat tarixi.
3. **Delivery Service** — Yetkazib berish xizmati. Geokodlash, marshrut hisoblash, narxlash, to'lov (webhook + HMAC), SMS/Email bildirishnomalar va tashqi so'rovlar loglash.

## Texnologiyalar

- PHP 8.2+
- Laravel 12
- PostgreSQL
- Laravel Sanctum (autentifikatsiya)
- Laravel Queue (eslatmalar uchun)

## O'rnatish

### 1. Repozitoriyani klonlash
```bash
git clone <repo-url>
cd litez
```

### 2. Dependencylarni o'rnatish
```bash
composer install
```

### 3. Environment sozlash
```bash
cp .env.example .env
php artisan key:generate
```

`.env` faylida PostgreSQL va boshqa sozlamalarni to'g'rilang:
```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=litez_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Delivery moduli uchun
PAYMENT_SECRET_KEY=your-secret-key-here
```

### 4. Ma'lumotlar bazasini yaratish
```bash
createdb litez_db
```

### 5. Migratsiya va seed
```bash
php artisan migrate --seed
```

Bu quyidagilarni yaratadi:
- **2 ta foydalanuvchi:** admin@example.com (admin), manager@example.com (manager) — parol: `password`
- **5 ta mijoz** (client)
- **Har bir foydalanuvchi uchun:** 5 oddiy, 2 takrorlanuvchi, 2 eslatmali, 1 muddati o'tgan vazifa
- **Katalog:** kategoriyalar va mahsulotlar
- **Yetkazib berish:** 15 ta buyurtma turli statuslarda (pending, paid, in_delivery, delivered, cancelled)

### 6. Serverni ishga tushirish
```bash
php artisan serve
```
Server: `http://127.0.0.1:8000`

### 7. Queue worker (eslatmalar uchun)
```bash
php artisan queue:work
```

### 8. Scheduler (eslatma va overdue tekshiruvi)
```bash
php artisan schedule:run
```

## Testlarni ishga tushirish

Test uchun alohida baza yarating:
```bash
createdb litez_db_test
```

Keyin:
```bash
php artisan test
```

90 ta test (Unit + Feature) — auth, CRUD, status transitions, recurring, reminders, catalog, inventory, delivery, webhook HMAC, pricing.

---

## API Yo'riqnomasi

### Autentifikatsiya

Barcha endpointlar (register/login dan tashqari) **Bearer Token** talab qiladi.

#### Ro'yxatdan o'tish
```
POST /api/auth/register
Content-Type: application/json

{
  "name": "Ivanov A.A.",
  "email": "ivanov@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Javob (201):
```json
{
  "data": {
    "id": 1,
    "name": "Ivanov A.A.",
    "email": "ivanov@example.com",
    "role": "manager"
  },
  "token": "1|abc123..."
}
```

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "ivanov@example.com",
  "password": "password123"
}
```

> Seeder bilan yaratilgan foydalanuvchilar: `admin@example.com` / `manager@example.com` — parol: `password`

#### Token ishlatish
Barcha keyingi so'rovlarda headerga qo'shing:
```
Authorization: Bearer {token}
```
Postman da: **Authorization** tab → Type: **Bearer Token** → Token maydoniga joylashtiring.

---

### Vazifalar (Tasks)

#### Vazifa yaratish
```
POST /api/tasks
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "call",
  "title": "Klientga qo'ng'iroq qilish",
  "description": "Yangi taklifni muhokama qilish",
  "priority": "high",
  "client_id": 1,
  "deadline": "2026-02-20T14:00:00Z",
  "is_recurring": true,
  "recurrence_type": "weekly",
  "remind_before_minutes": 30,
  "remind_via": "email"
}
```

**Maydonlar:**

| Maydon | Turi | Qiymatlar |
|--------|------|-----------|
| type | required | `call`, `meeting`, `email`, `task` |
| title | required | max 255 belgi |
| description | optional | matn |
| priority | optional | `low`, `medium`, `high`, `critical` (default: medium) |
| client_id | optional | mavjud mijoz ID si |
| deadline | required | sana-vaqt (kelajakda) |
| is_recurring | optional | `true`/`false` |
| recurrence_type | required if recurring | `daily`, `weekly` |
| remind_before_minutes | optional | daqiqalar soni |
| remind_via | optional | `email`, `sms` |

#### Vazifalar ro'yxati (filtrlar bilan)
```
GET /api/tasks
GET /api/tasks?status=pending
GET /api/tasks?type=call&priority=high
GET /api/tasks?client_id=1
GET /api/tasks?date_from=2026-02-01&date_to=2026-02-28
```

#### Bugungi vazifalar
```
GET /api/tasks/today
```

#### Muddati o'tgan vazifalar
```
GET /api/tasks/overdue
```

#### Vazifani yangilash
```
PUT /api/tasks/{id}
Content-Type: application/json

{
  "title": "Yangilangan sarlavha",
  "priority": "critical"
}
```

#### Status o'zgartirish
```
PATCH /api/tasks/{id}/status
Content-Type: application/json

{
  "status": "in_progress"
}
```

**Ruxsat etilgan o'tishlar:**

| Joriy status | O'tishi mumkin |
|-------------|---------------|
| pending | `in_progress`, `cancelled` |
| in_progress | `done`, `cancelled` |
| done | — (terminal) |
| cancelled | — (terminal) |

Noto'g'ri o'tish (masalan done → pending):
```json
{
  "message": "Недопустимый переход",
  "errors": {
    "status": ["Переход из done в pending невозможен"]
  }
}
```

#### Vazifani o'chirish (soft delete)
```
DELETE /api/tasks/{id}
```

#### Mijoz vazifalari
```
GET /api/clients/{client_id}/tasks
```

---

### Takrorlanuvchi vazifalar (Recurring)

Agar `is_recurring: true` va `recurrence_type` belgilangan vazifa **done** bo'lsa — avtomatik yangi nusxa yaratiladi:
- `daily` → deadline + 1 kun
- `weekly` → deadline + 7 kun

`cancelled` bo'lsa — nusxa yaratilmaydi.

### Eslatmalar (Reminders)

- `remind_before_minutes` — deadline dan necha daqiqa oldin eslatish
- `remind_via: email` — log driverga email yuboradi (`storage/logs/laravel.log`)
- `remind_via: sms` — `storage/logs/sms.log` fayliga yozadi
- Scheduler har daqiqada tekshiradi (`php artisan schedule:run`)
- Queue worker ishlayotgan bo'lishi kerak (`php artisan queue:work`)

---

## Catalog & Inventory API

### Arxitektura

Modul tuzilishi (`app/Modules/`) — har bir modul o'z controllerlari, modellari, servislari, requestlari va resurslariga ega. Modullar `ServiceProvider` orqali yuklanadi. Autentifikatsiya talab qilinmaydi.

### Kategoriyalar

#### Kategoriya yaratish
```
POST /api/categories
Content-Type: application/json

{
  "name": "Elektronika",
  "description": "Elektron jihozlar",
  "parent_id": null
}
```

Javob (201):
```json
{
  "data": {
    "id": 1,
    "name": "Elektronika",
    "slug": "elektronika",
    "description": "Elektron jihozlar",
    "parent_id": null,
    "is_active": true
  }
}
```

- `slug` — avtomatik `name` dan generatsiya qilinadi. Takrorlanganda increment: `elektronika-1`, `elektronika-2`
- Kategoriya daraxti **maksimal 3 daraja** (parent → child → grandchild)

#### Kategoriya daraxti
```
GET /api/categories
```

Ichma-ich `children` bilan qaytadi (3 darajagacha).

---

### Mahsulotlar

#### Mahsulot yaratish (atributlar bilan)
```
POST /api/products
Content-Type: application/json

{
  "name": "Futbolka Classic",
  "price": 29900,
  "sku": "TSH-001",
  "category_id": 1,
  "is_published": true,
  "attributes": [
    { "name": "rang", "value": "qora" },
    { "name": "o'lcham", "value": "XL" }
  ]
}
```

Javob (201):
```json
{
  "data": {
    "id": 1,
    "name": "Futbolka Classic",
    "slug": "futbolka-classic",
    "price": "29900.00",
    "sku": "TSH-001",
    "is_published": true,
    "category": { "id": 1, "name": "Elektronika", "slug": "elektronika" },
    "attributes": [
      { "id": 1, "name": "rang", "value": "qora" },
      { "id": 2, "name": "o'lcham", "value": "XL" }
    ],
    "stock": { "id": 1, "quantity": 0, "reserved_quantity": 0 }
  }
}
```

- Mahsulot yaratilganda avtomatik `stock` yozuvi ham yaratiladi (quantity: 0)
- Barcha operatsiya tranzaksiyada bajariladi

#### Mahsulotlar ro'yxati (filtrlar bilan)
```
GET /api/products
GET /api/products?category_id=1
GET /api/products?price_min=10000&price_max=50000
GET /api/products?search=futbolka
```

#### Mahsulot slug bo'yicha
```
GET /api/products/{slug}
```

Atributlar va stock bilan qaytadi.

#### Mahsulotni yangilash
```
PUT /api/products/{id}
Content-Type: application/json

{
  "name": "Yangilangan nom",
  "price": 35000,
  "attributes": [
    { "name": "rang", "value": "oq" }
  ]
}
```

---

### Ombor (Inventory)

#### Stock miqdorini o'zgartirish
```
POST /api/inventory/{product_id}/adjust
Content-Type: application/json

{
  "quantity_change": 50,
  "reason": "receipt"
}
```

**Reason qiymatlari:** `receipt` (kirim), `sale` (sotish), `adjustment` (tuzatish), `return` (qaytarish)

- `sale` sababida `reserved_quantity` ortadi, boshqa reasonlar ta'sir qilmaydi
- Omborda yetarli bo'lmasa — **422** qaytadi:

```json
{
  "message": "Omborda yetarli mahsulot yo'q",
  "errors": {
    "quantity_change": ["Omborda 50 dona mavjud, 100 dona chiqarib yuborish mumkin emas"]
  }
}
```

#### Harakat tarixi
```
GET /api/inventory/{product_id}/history
```

---

## Delivery Service API

Yetkazib berish moduli — geokodlash, marshrut hisoblash, narxlash, to'lov va bildirishnomalar. Autentifikatsiya talab qilinmaydi. Barcha tashqi xizmatlar (geocoder, routing, payment, sms) hozircha **mock** rejimda ishlaydi.

### Manzilni geokodlash

```
POST /api/addresses/geocode
Content-Type: application/json

{
  "address": "Toshkent, Amir Temur ko'chasi 10"
}
```

Javob (200):
```json
{
  "lat": 41.3112345,
  "lng": 69.2798765
}
```

> Mock rejimda Toshkent hududidagi tasodifiy koordinatalar qaytariladi.

---

### Marshrut va narxni hisoblash (preview)

Buyurtma yaratmasdan, faqat narxni ko'rish uchun:

```
POST /api/orders/calculate
Content-Type: application/json

{
  "origin_address": "Toshkent, Chorsu bozori",
  "destination_address": "Toshkent, Sergeli tumani"
}
```

Javob (200):
```json
{
  "origin": { "lat": 41.3256, "lng": 69.2312 },
  "destination": { "lat": 41.2845, "lng": 69.2756 },
  "distance_km": 7.85,
  "duration_minutes": 12,
  "estimated_cost": 11280
}
```

**Narx hisoblash formulasi:**
- Bazaviy narx: **5 000 so'm**
- Har bir km: **800 so'm**
- 100 km dan oshsa: **x1.5 koeffitsiyent**

Misol: 10 km = 5000 + (10 × 800) = **13 000 so'm**

---

### Buyurtma yaratish

```
POST /api/orders
Content-Type: application/json

{
  "customer_name": "Ali Valiyev",
  "customer_phone": "+998901234567",
  "customer_email": "ali@example.com",
  "origin_address": "Toshkent, Amir Temur ko'chasi",
  "destination_address": "Toshkent, Navoi ko'chasi"
}
```

Javob (201):
```json
{
  "data": {
    "id": 1,
    "customer_name": "Ali Valiyev",
    "customer_phone": "+998901234567",
    "customer_email": "ali@example.com",
    "origin": {
      "address": "Toshkent, Amir Temur ko'chasi",
      "lat": "41.3112345",
      "lng": "69.2798765"
    },
    "destination": {
      "address": "Toshkent, Navoi ko'chasi",
      "lat": "41.2956789",
      "lng": "69.2534567"
    },
    "distance_km": "5.23",
    "duration_minutes": 8,
    "estimated_cost": "9184.00",
    "status": "pending",
    "paid_at": null,
    "created_at": "2026-02-13T10:00:00.000000Z",
    "updated_at": "2026-02-13T10:00:00.000000Z"
  }
}
```

Buyurtma yaratilganda avtomatik:
- Ikkala manzil geokodlanadi
- Marshrut va narx hisoblanadi
- SMS bildirishnoma jo'natiladi (queue orqali)

**Maydonlar:**

| Maydon | Turi | Tavsif |
|--------|------|--------|
| customer_name | required, string | Mijoz ismi |
| customer_phone | required, string | Telefon raqam |
| customer_email | required, email | Email manzil |
| origin_address | required, string | Jo'natish manzili |
| destination_address | required, string | Yetkazish manzili |

---

### Buyurtmani ko'rish

```
GET /api/orders/{id}
```

---

### Buyurtma statusini o'zgartirish

```
PATCH /api/orders/{id}/status
Content-Type: application/json

{
  "status": "in_delivery"
}
```

**Ruxsat etilgan o'tishlar:**

| Joriy status | O'tishi mumkin |
|-------------|---------------|
| pending | `paid`, `cancelled` |
| paid | `in_delivery`, `cancelled` |
| in_delivery | `delivered`, `cancelled` |
| delivered | — (terminal) |
| cancelled | — (terminal) |

Noto'g'ri o'tish (masalan pending → delivered):
```json
{
  "message": "...",
  "errors": {
    "status": ["Cannot transition from pending to delivered."]
  }
}
```

Status o'zgarganda:
- Har safar SMS bildirishnoma yuboriladi
- `delivered` bo'lganda qo'shimcha **email** yuboriladi (queue orqali)

---

### To'lovni boshlash

```
POST /api/orders/{id}/pay
```

Faqat `pending` statusdagi buyurtmalar uchun ishlaydi.

Javob (200):
```json
{
  "payment_url": "https://mock-payment.example.com/pay/txn_AbCdEfGh12345678",
  "transaction_id": "txn_AbCdEfGh12345678"
}
```

> Mock rejimda soxta payment URL qaytariladi.

---

### Payment Webhook

To'lov tizimi buyurtma to'langanini tasdiqlash uchun webhook yuboradi:

```
POST /api/webhooks/payment
Content-Type: application/json
X-Signature: {hmac_sha256_imzo}

{
  "order_id": 1,
  "amount": 13000
}
```

**HMAC-SHA256 imzo yaratish:**

Imzo `X-Signature` headerda yuboriladi. Uni quyidagicha hisoblash kerak:

```
signature = HMAC-SHA256(JSON.stringify(payload), PAYMENT_SECRET_KEY)
```

PHP da:
```php
$signature = hash_hmac('sha256', json_encode($payload), env('PAYMENT_SECRET_KEY'));
```

curl bilan test qilish:
```bash
# O'zgaruvchilar
SECRET="your-secret-key-here"
PAYLOAD='{"order_id":1,"amount":13000}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

# So'rov
curl -X POST http://127.0.0.1:8000/api/webhooks/payment \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

Muvaffaqiyatli javob (200): buyurtma `paid` statusga o'tadi va `paid_at` belgilanadi.

Noto'g'ri imzo: **403 Forbidden**

---

### Tashqi so'rovlar logi

Barcha tashqi xizmatlarga (geocoder, routing, payment, sms) yuborilgan so'rovlar `external_request_logs` jadvaliga avtomatik yoziladi. Har bir yozuv quyidagilarni saqlaydi:

| Maydon | Tavsif |
|--------|--------|
| service | Xizmat nomi (geocoder, routing, payment, sms) |
| method | HTTP metod (GET, POST) |
| url | So'rov URL |
| request_body | Yuborilgan ma'lumot (JSON) |
| response_body | Qaytgan javob (JSON) |
| status_code | HTTP status kod |
| duration_ms | So'rov davomiyligi (millisekund) |

---

### SMS va Email bildirishnomalar

- **SMS** — buyurtma yaratilganda, status o'zgarganda va to'langanda yuboriladi. Mock rejimda `storage/logs/sms.log` ga yoziladi.
- **Email** — faqat `delivered` statusda yuboriladi. Mail driver `log` rejimda `storage/logs/laravel.log` ga yoziladi.
- Barcha bildirishnomalar **queue** orqali yuboriladi — `php artisan queue:work` ishlayotgan bo'lishi kerak.

---

## Loyiha strukturasi

```
app/
├── Enums/                  # CRM uchun PHP Enums
├── Http/                   # CRM controllerlari
├── Jobs/                   # Queue joblari
├── Models/                 # CRM modellari (User, Client, Task)
├── Notifications/          # Email/SMS eslatmalar
├── Services/               # CRM biznes logika
└── Modules/
    ├── Catalog/
    │   ├── CatalogServiceProvider.php
    │   ├── routes.php
    │   ├── Controllers/    (CategoryController, ProductController)
    │   ├── Models/         (Category, Product, ProductAttribute)
    │   ├── Services/       (CategoryService, ProductService)
    │   ├── Requests/       (StoreCategoryRequest, StoreProductRequest, UpdateProductRequest)
    │   └── Resources/      (CategoryResource, ProductResource, ProductAttributeResource)
    ├── Inventory/
    │   ├── InventoryServiceProvider.php
    │   ├── routes.php
    │   ├── Controllers/    (InventoryController)
    │   ├── Models/         (Stock, StockMovement)
    │   ├── Services/       (InventoryService)
    │   ├── Requests/       (AdjustStockRequest)
    │   ├── Resources/      (StockResource, StockMovementResource)
    │   └── DTOs/           (StockMovementReason enum)
    └── Delivery/
        ├── DeliveryServiceProvider.php
        ├── routes.php
        ├── Enums/          (OrderStatus)
        ├── Contracts/      (GeocoderInterface, RoutingInterface, PaymentInterface, SmsNotificationInterface)
        ├── DTOs/           (GeoPoint, RouteResult, PaymentResult)
        ├── Controllers/    (AddressController, OrderController, PaymentWebhookController)
        ├── Models/         (Order, ExternalRequestLog)
        ├── Services/       (OrderService, PricingService, MockGeocoder/Routing/Payment/Sms, ExternalRequestLogger)
        ├── Requests/       (GeocodeRequest, CalculateRouteRequest, StoreOrderRequest, UpdateOrderStatusRequest)
        ├── Resources/      (OrderResource, ExternalRequestLogResource)
        ├── Jobs/           (SendOrderSmsJob, SendOrderEmailJob)
        └── Notifications/  (OrderDeliveredNotification)
```
