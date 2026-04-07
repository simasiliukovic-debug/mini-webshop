# ModStore — Game Mods & Assets Webshop

A full-featured PHP/MySQL webshop for game mods and digital assets.
Inspired by the Unity Asset Store Publisher Portal.

---

## Stack
- **Backend:** PHP 8.1+ with PDO (prepared statements)
- **Database:** MySQL 5.7+ / MariaDB 10.3+ (XAMPP compatible)
- **Frontend:** Bootstrap 5.3 + custom CSS (Syne + Outfit fonts)
- **IDE:** PhpStorm (any version supporting PHP 8)

---

## Quick Setup (XAMPP)

### 1. Place files
Copy the `modstore/` folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\modstore\
```

### 2. Import database
1. Open XAMPP Control Panel → Start **Apache** and **MySQL**
2. Open `http://localhost/phpmyadmin`
3. Create a new database named `modstore`
4. Click the `modstore` database → **Import** tab
5. Select `database/schema.sql` → click **Go**

### 3. Configure DB connection
Open `config/db.php` and update if needed:
```php
define('DB_USER', 'root');   // default XAMPP
define('DB_PASS', '');       // default XAMPP (empty)
```

### 4. Open in browser
```
http://localhost/modstore/
```

---

## PhpStorm Setup
1. Open PhpStorm → **File > Open** → select the `modstore/` folder
2. Go to **Settings > PHP > Servers** → add server:
   - Name: `localhost`
   - Host: `localhost`
   - Port: `80`
3. Set PHP CLI interpreter to your XAMPP PHP:
   - `C:\xampp\php\php.exe`

---

## Project Structure
```
modstore/
├── config/
│   └── db.php              ← PDO connection (edit credentials here)
├── css/
│   └── style.css           ← Full custom dark theme
├── database/
│   └── schema.sql          ← Database schema + 8 sample products
├── includes/
│   ├── functions.php       ← Auth, CSRF, cart, helpers
│   ├── header.php          ← Navigation + flash messages
│   └── footer.php          ← Footer + scripts
├── js/
│   └── main.js             ← Theme toggle, mobile nav, animations
├── process/
│   ├── add-to-cart.php     ← Add product to session cart
│   ├── remove-from-cart.php
│   ├── place-order.php     ← Create order, grant assets, ++downloads
│   ├── add-review.php      ← Submit review + recalculate product rating
│   └── save-product.php    ← Create / edit product
├── index.php               ← Store homepage (grid + filters)
├── product.php             ← Product detail + reviews
├── cart.php                ← Cart page
├── checkout.php            ← Checkout form
├── orders.php              ← Order history + detail
├── my-assets.php           ← User asset library
├── publish.php             ← Publisher Portal (create/edit products)
├── login.php
├── register.php
└── logout.php
```

---

## Features

| Feature | Details |
|---|---|
| Product catalog | Grid, search, category filter, price filter, sort |
| Product detail | Description, metadata, star reviews |
| Cart | Session-based, remove items, summary |
| Checkout | Address form, simulated payment |
| Orders | History table + detail view |
| My Assets | Library of owned products |
| Publish Portal | Create/edit products with live preview |
| Reviews | Only if you own the asset; recalculates rating |
| Downloads | Only increment on purchase |
| Auth | bcrypt passwords, CSRF, session management |
| Themes | Dark (default) / Light, saved in localStorage |
| Responsive | Mobile nav, stacked layouts on small screens |

---

## Security
- PDO prepared statements (no SQL injection)
- CSRF tokens on all POST forms
- `password_hash()` with bcrypt cost 12
- Input sanitization via `strip_tags()` + `htmlspecialchars()`
- Session regeneration on login
- Open redirect prevention in cart handler

---

## Default Test Data
After importing `schema.sql` you'll have:
- **8 products** across 6 categories (free and paid)
- **No users** — register a new account at `/modstore/register.php`

---

## License
For educational use. Not for commercial deployment without review.
