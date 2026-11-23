# WooCommerce Fast Admin (woo-php-admin-panel)

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-96588a?logo=woocommerce&logoColor=white)](https://woocommerce.com/)
[![Docker Ready](https://img.shields.io/badge/Docker-Compose%20Ready-2496ed?logo=docker&logoColor=white)](https://www.docker.com/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20MariaDB-4479a1?logo=mysql&logoColor=white)](https://mariadb.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Micropanel administrativo y panel de control de pedidos independiente, rapido y seguro para tiendas **WooCommerce** y **WordPress**.

---

## 📌 ¿Por que existe este proyecto?

El panel oficial de administracion de WordPress (`wp-admin`) suele volverse excesivamente lento y pesado a medida que se instalan plugins, temas complejos, constructores visuales y herramientas de analitica. Para personal operativo como cajeros, bodegueros, operarios de despacho o atencion al cliente, acceder a `wp-admin`:
1. Sobrecarga el servidor web al ejecutar todo el ciclo de vida de plugins en cada peticion.
2. Expone configuraciones criticas del CMS a usuarios que solo necesitan despachar ordenes o ajustar inventario.
3. Ralentiza el flujo de despacho diario con interfaces sobrecargadas.

**`woo-php-admin-panel`** resuelve este problema ofreciendo una interfaz ligera, desacoplada y de alto rendimiento que interactua directamente sobre la base de datos de WooCommerce con consultas optimizadas mediante JOINs indexados y sentencias preparadas (PDO).

---

## 🚀 Caracteristicas Principales

- **Despacho y Gestion de Pedidos en Tiempo Real:**
  - Visualizacion rapida de ordenes con identificador, fecha, datos del cliente, estado y total.
  - Modal interactivo con desglose de productos comprados, cantidades, precios unitarios y subtotales.
  - Actualizacion de estado en un clic: Completado (`wc-completed`), En Proceso (`wc-processing`), En Espera (`wc-on-hold`), Cancelado (`wc-cancelled`) y Papelera (`trash`).
- **Control de Inventario y Catalogo de Productos:**
  - Listado de productos con miniaturas, nombres, categorias, precios y descripciones.
  - Creacion y actualizacion de productos con generacion automatica de slugs y meta-campos (`_price`, `_regular_price`, `_thumbnail_id`).
  - Eliminacion segura en transaccion que limpia entradas en `wp_posts`, `wp_postmeta` y relaciones taxonomicas.
- **Biblioteca de Medios Integrada (Dropzone.js):**
  - Carga arrastrar-y-soltar de imagenes (JPG, PNG, WEBP, GIF).
  - Registro automatico como adjunto (`post_type='attachment'`) en la biblioteca multimedia de WordPress.
- **Autenticacion Nativa Multi-Algoritmo:**
  - Verificacion directa contra la tabla `wp_users`.
  - Soporte nativo para hashes portables de WordPress (algoritmo PHPass `$P$` y `$H$`), `password_verify` (bcrypt) y fallback para entornos de desarrollo.
- **Consultas de Alto Rendimiento:**
  - Elimina el problema de consultas N+1 en bucles gracias a consultas unificadas con `LEFT JOIN` e indices de WordPress.
- **Seguridad Robusta:**
  - PDO con sentencias preparadas al 100%.
  - Tokens CSRF en todos los formularios y llamadas API.
  - Sanitizacion y escape contextual de entradas y salidas HTML (XSS prevention).
  - Control de sesiones seguro con cookies `HttpOnly` y `SameSite`.
- **Doble Modo de Despliegue:**
  - **Modo Standalone:** Ejecutalo en su propio contenedor Docker, VPS o subdominio conectandose remotamente a la base de datos MySQL/MariaDB de la tienda.
  - **Modo Embebido:** Ubicalo en una subcarpeta (por ejemplo `/admin/` o `/panel/`) dentro del arbol de WordPress para heredar automaticamente la conexion de `wp-config.php`.

---

## 🛠️ Arquitectura del Proyecto

```
woo-php-admin-panel/
├── api/                       # Endpoints JSON RESTful para AJAX y servicios externos
│   ├── orders.php             # Consulta de detalle de pedido y actualizacion de estado
│   ├── products.php           # CRUD completo de productos
│   └── upload.php             # Receptor de carga de archivos Dropzone
├── assets/                    # Hojas de estilo, fuentes y scripts del tema Ela Admin
├── Controller/                # Capa de compatibilidad retroactiva para rutas legacy
│   ├── AddProduct.php
│   ├── DeleteProduct.php
│   ├── GetProduct.php
│   ├── GetProductsTx.php
│   ├── GetTransaction.php
│   ├── Login.php
│   ├── Status.php
│   ├── UpdateProduct.php
│   └── Upload.php
├── database/                  # Esquema de base de datos de prueba autocontenido
│   └── schema.sql             # Estructura WooCommerce minima con datos demo
├── images/                    # Logos, avatares y recursos graficos
├── Layouts/                   # Plantillas modulares de encabezado y pie de pagina
│   ├── Header.php             # Barra lateral, topbar, alertas y metas
│   └── Footer.php             # Scripts, DataTables y gestor de notificaciones Toast
├── src/                       # Codigo fuente orientado a objetos (PSR-4 App\)
│   ├── Config/
│   │   └── Database.php       # Fabrica Singleton de conexion PDO (.env / wp-config)
│   ├── Helpers/
│   │   ├── Format.php         # Moneda, fechas legibles y badges de estado
│   │   └── Security.php       # Validacion CSRF, sanitizacion y generador de slugs
│   ├── Services/
│   │   ├── AuthService.php    # Verificacion PHPass/bcrypt y control de sesion
│   │   ├── OrderService.php   # Consultas JOIN de ordenes, metricas y estados
│   │   ├── ProductService.php # CRUD de productos e integracion taxonomica
│   │   └── UploadService.php  # Validacion MIME y registro multimedia en wp_posts
│   └── bootstrap.php          # Inicializador, variables de entorno y helpers globales
├── dashboard.php              # Vista principal: metricas KPI y catalogo de productos
├── transactions.php           # Vista de pedidos: historial de ventas y modal de detalle
├── index.php                  # Pantalla de inicio de sesion moderna con CSRF
├── logout.php                 # Cierre seguro de sesion y destruccion de cookies
├── docker-compose.yml         # Entorno Docker listo para produccion o desarrollo
├── .env.example               # Plantilla de variables de entorno
└── composer.json              # Definicion de dependencias y mapeo de namespaces
```

---

## 🐳 Inicio Rapido con Docker Compose

El repositorio incluye un archivo `docker-compose.yml` que levanta la aplicacion en PHP 8.2 junto con una base de datos MariaDB 10.11 pre-cargada con datos de prueba (productos y ordenes de ejemplo) y phpMyAdmin.

1. **Clonar el repositorio:**
   ```bash
   git clone git@github.com:KevinAndrey96/woo-php-admin-panel.git
   cd woo-php-admin-panel
   ```

2. **Crear archivo de entorno local (opcional):**
   ```bash
   cp .env.example .env
   ```

3. **Iniciar los contenedores:**
   ```bash
   docker compose up -d
   ```

4. **Acceder a los servicios:**
   - **Panel Administrativo:** [http://localhost:8085](http://localhost:8085)
   - **phpMyAdmin:** [http://localhost:8086](http://localhost:8086)

5. **Credenciales de prueba:**
   - **Usuario:** `admin`
   - **Contrasena:** `admin123`

---

## ⚙️ Variables de Entorno

Si utilizas el modo Standalone o despliegas en un servidor PHP independiente, puedes configurar la conexion mediante variables de entorno o en el archivo `.env`:

| Variable | Descripcion | Valor por Defecto |
| :--- | :--- | :--- |
| `APP_NAME` | Nombre visible del panel en la barra superior | `WooCommerce Fast Admin` |
| `APP_ENV` | Entorno de ejecucion (`production` o `development`) | `production` |
| `APP_TIMEZONE` | Zona horaria del servidor | `America/Bogota` |
| `DB_HOST` | Host o IP del servidor MySQL / MariaDB | `db` o `localhost` |
| `DB_PORT` | Puerto de conexion a la base de datos | `3306` |
| `DB_NAME` | Nombre de la base de datos de WordPress | `ufun` |
| `DB_USER` | Usuario de base de datos con permisos SELECT/INSERT/UPDATE | `ufun_user` |
| `DB_PASSWORD` | Contrasena del usuario de base de datos | `ufun_password` |
| `DB_CHARSET` | Codificacion de caracteres de la base de datos | `utf8mb4` |
| `DB_TABLE_PREFIX` | Prefijo de tablas de WordPress | `wp_` |

---

## 🔌 Instalacion Embebida (Dentro de WordPress)

Si deseas alojar este panel directamente dentro de un sitio WordPress existente:

1. Copia la carpeta `woo-php-admin-panel` dentro de la raiz publica de WordPress y renombrala a tu conveniencia (por ejemplo, `tienda-admin`):
   ```bash
   cp -r woo-php-admin-panel /var/www/html/tienda-admin
   ```
2. La clase `App\Config\Database` detectara de forma automatica el archivo `wp-config.php` del directorio superior y adoptara los datos de conexion de la tienda sin necesidad de configurar ningun archivo `.env`.
3. Abre en tu navegador `https://tutienda.com/tienda-admin` e inicia sesion con tu cuenta de administrador o gestor de tienda de WordPress.

---

## 📡 Referencia de la API Interna

Todos los endpoints responden en formato JSON con cabeceras `Content-Type: application/json; charset=utf-8` y codigos de estado HTTP estandar (200, 400, 401, 404, 500). Requieren sesion activa autenticada.

### Productos (`api/products.php`)
- `GET ?id={ID}`: Obtiene el detalle completo del producto (nombre, precio, descripcion corta, contenido, miniatura y categorias).
- `POST action=create`: Crea un nuevo producto recibiendo `NAME`, `PRICE`, `SHORT`, `LONG`, `FOTO`.
- `POST action=update`: Actualiza un producto existente recibiendo `id`, `NAME`, `PRICE`, `SHORT`, `LONG`, `FOTO`.
- `POST action=delete`: Elimina el producto y limpia sus registros dependientes en `wp_postmeta`.

### Pedidos (`api/orders.php`)
- `GET ?id={ID}`: Devuelve los datos del comprador (nombre, email, telefono, direccion, ciudad, IP) y el listado de articulos comprados (`items`).
- `POST action=status`: Actualiza el estado del pedido recibiendo `id` y `status` (valida estados permitidos de WooCommerce).

### Carga de Archivos (`api/upload.php`)
- `POST`: Sube una imagen mediante Dropzone (`multipart/form-data`) y devuelve el ID del adjunto generado en `wp_posts` y la URL publica (`guid`).

---

## 👨‍💻 Autor

- **Kevin Andrey Herrera Silva**
- GitHub: [@KevinAndrey96](https://github.com/KevinAndrey96)
- Correo: `kahs_kevin@hotmail.com`

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Eres libre de usarlo, modificarlo y distribuirlo en proyectos personales o comerciales.
