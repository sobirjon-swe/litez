# LiteZ — CRM + Catalog & Inventory REST API

Ikki moduldan iborat REST API platforma:

1. **CRM-panel** — Vazifalar va eslatmalar (Tasks & Reminders). Menejerlar vazifalar yaratishlari, ularni mijozlarga bog'lashlari, avtomatik eslatmalar olishlari va takrorlanuvchi vazifalarni sozlashlari mumkin.
2. **Catalog & Inventory** — Mahsulotlar katalogi va ombor boshqaruvi. Kategoriya daraxti, mahsulotlar CRUD, atributlar, stock adjust va harakat tarixi.

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

`.env` faylida PostgreSQL ma'lumotlarini to'g'rilang:
```
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=litez_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
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

66 ta test (Unit + Feature) — auth, CRUD, status transitions, recurring, reminders, catalog, inventory.

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
    └── Inventory/
        ├── InventoryServiceProvider.php
        ├── routes.php
        ├── Controllers/    (InventoryController)
        ├── Models/         (Stock, StockMovement)
        ├── Services/       (InventoryService)
        ├── Requests/       (AdjustStockRequest)
        ├── Resources/      (StockResource, StockMovementResource)
        └── DTOs/           (StockMovementReason enum)
```
