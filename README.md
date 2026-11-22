# WordPress & WooCommerce Standalone Admin Dashboard

[![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![WordPress](https://img.shields.io/badge/WordPress-Compatible-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Direct%20Sync-96588A?style=for-the-badge&logo=woocommerce&logoColor=white)](https://woocommerce.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.1-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

A high-performance, lightweight standalone administration backoffice and point-of-sale catalog management dashboard built for **WordPress** and **WooCommerce** stores. Designed to bypass heavy WP-Admin overhead, providing store managers and warehouse operators with an ultra-responsive interface for managing products, inventory, media attachments, and incoming customer orders.

---

## ⚡ Why Use a Standalone Headless Dashboard?

Standard WordPress `wp-admin` can be resource-heavy, slow under high traffic, and cluttered with third-party plugin notices. This dashboard connects directly to the underlying WordPress relational schema (`wp_posts`, `wp_postmeta`, `wp_users`, `wp_wc_order_product_lookup`) via optimized PDO queries, yielding:

- **Sub-50ms Response Times**: Instant product searches and order status lookups without loading the heavy WordPress plugin stack.
- **Dedicated Warehouse UI**: Tailored for order packing and catalog stock management without exposing administrative WordPress core settings.
- **Dual Deployment Flexibility**: Operates seamlessly as an embedded subfolder inside an existing WordPress hosting root or as an isolated containerized service.
- **Zero Heavy Dependencies**: Pure PHP PDO with responsive Ela Admin Bootstrap styling.

---

## 🏗️ Architecture & Direct Database Mapping

```
+-------------------------------------------------------------+
|             Fast Admin Dashboard Web Interface              |
|        (Dashboard, Orders, Catalog, Media Uploads)          |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
|               PHP 8.x PDO Controller Layer                  |
|  - Tools/PDO.php: Dual-mode connection resolver             |
|  - Controller/AddProduct.php & UpdateProduct.php            |
|  - Controller/GetTransaction.php & Status.php               |
+-------------------------------------------------------------+
                              | Direct SQL Transactions
                              v
+-------------------------------------------------------------+
|             WordPress & WooCommerce Database                |
|  - wp_posts: Products (post_type='product') & Orders        |
|  - wp_postmeta: Prices, SKU, Billing info, Thumbnail IDs   |
|  - wp_wc_order_product_lookup: Order line items             |
|  - wp_users: Secure administrator credentials check         |
+-------------------------------------------------------------+
```

---

## 🚀 Key Functional Modules

### 1. Order & Transaction Management (`transactions.php`)
- Real-time stream of all customer orders (`shop_order`).
- Billing customer name, contact details, total price, and transaction date.
- Instant itemized modal inspection (`Controller/GetProductsTx.php`).
- Direct order status updates (`completed`, `processing`, `cancelled`).

### 2. Fast Product Catalog & Inventory (`dashboard.php`)
- Visual product grid with featured thumbnails.
- Quick inline pricing edits (`_regular_price`, `_sale_price`).
- Publication status toggles (`publish`, `draft`, `trash`).
- Direct product deletion and meta-data cleanup.

### 3. Media & Product Asset Dispatch (`Controller/Upload.php`)
- Multipart image uploader that registers images directly into `wp_posts` with `post_type='attachment'`.
- Automatic thumbnail linking via `_thumbnail_id` meta keys.

---

## 🛠️ Deployment Modes

### Mode A: Docker Compose (Standalone Container)

Run the dashboard independently and connect it to any local or remote WordPress database:

1. Clone the repository:
   ```bash
   git clone git@github.com-personal:KevinAndrey96/admin-t.git
   cd admin-t
   ```
2. Set your database connection parameters in `.env` or pass them directly:
   ```ini
   DB_HOST=your-wordpress-db-host
   DB_NAME=your_wordpress_db
   DB_USER=your_db_user
   DB_PASSWORD=your_db_password
   ```
3. Boot the container:
   ```bash
   docker compose up -d
   ```
4. Access the dashboard at [http://localhost:8085](http://localhost:8085).

---

### Mode B: Embedded Inside WordPress (cPanel / Apache / Nginx)

1. Copy the `admin-t` directory into your WordPress site directory as `admin/`:
   ```bash
   cp -r admin-t /path/to/wordpress/public_html/admin
   ```
2. Because `Tools/PDO.php` automatically searches for `../../wp-config.php`, it will read your live WordPress database configuration without any manual credential configuration.
3. Open your browser at `https://yourstore.com/admin/` and log in with your WordPress administrator credentials.

---

## 🔒 Security Best Practices

- Prepared statements and sanitized PDO queries across all catalog mutations.
- Authentication verification tied to `wp_users` with password verification fallbacks.
- Recommended to restrict access via HTTP Basic Auth or IP whitelisting when deployed on public production servers.
