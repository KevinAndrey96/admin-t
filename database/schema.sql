-- Standalone WooCommerce and WordPress Sample Database Schema
-- Provides instant local database setup for testing and production environments

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: wp_options
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES
(1, 'siteurl', 'http://localhost:8085', 'yes'),
(2, 'home', 'http://localhost:8085', 'yes'),
(3, 'blogname', 'Tienda WooCommerce Demo', 'yes')
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- -----------------------------------------------------
-- Table: wp_users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default password is: admin123 (WordPress PHPass and bcrypt compatible)
INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `display_name`) VALUES
(1, 'admin', '$P$B7zD5sX5I8Y4o7V4v0d5T8e5z5c5v0/', 'admin', 'admin@tienda.com', 'http://localhost:8085', NOW(), 'Administrador Tienda')
ON DUPLICATE KEY UPDATE `user_pass` = VALUES(`user_pass`);

-- -----------------------------------------------------
-- Table: wp_usermeta
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(1, 1, 'wp_capabilities', 'a:1:{s:13:"administrator";b:1;}'),
(2, 1, 'wp_user_level', '10')
ON DUPLICATE KEY UPDATE `meta_value` = VALUES(`meta_value`);

-- -----------------------------------------------------
-- Table: wp_posts
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_date_gmt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_modified_gmt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Products & Orders
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `post_name`, `guid`, `post_type`) VALUES
(101, 1, '2026-11-20 10:00:00', 'Zapatillas deportivas para running con tecnologia de amortiguacion avanzada.', 'Zapatillas Running Pro Sport', 'Zapatillas ultraligeras para running y maraton.', 'publish', 'zapatillas-running-pro-sport', 'http://localhost:8085/producto/zapatillas-running-pro-sport', 'product'),
(102, 1, '2026-11-21 11:30:00', 'Camiseta con corte oversized confeccionada en 100% algodon peinado pesado.', 'Camiseta Oversized Urbana', 'Corte moderno relajado y tejido de alta densidad.', 'publish', 'camiseta-oversized-urbana', 'http://localhost:8085/producto/camiseta-oversized-urbana', 'product'),
(103, 1, '2026-11-22 14:15:00', 'Gorra snapback con bordado frontal en alto relieve y broche ajustable.', 'Gorra Snapback Streetwear', 'Accesorio de estilo urbano con broche regulable.', 'publish', 'gorra-snapback-streetwear', 'http://localhost:8085/producto/gorra-snapback-streetwear', 'product'),
(104, 1, '2026-11-23 09:45:00', 'Mochila tecnica impermeable con compartimento acolchado para laptop de hasta 16 pulgadas.', 'Mochila Impermeable Explorer 30L', 'Capacidad 30 litros con proteccion contra lluvia.', 'publish', 'mochila-impermeable-explorer-30l', 'http://localhost:8085/producto/mochila-impermeable-explorer-30l', 'product'),

-- Sample Orders
(201, 1, '2026-11-21 15:20:00', 'Pedido completado via tarjeta de credito.', 'Orden #201', '', 'wc-completed', 'orden-201', 'http://localhost:8085/orden-201', 'shop_order'),
(202, 1, '2026-11-22 18:40:00', 'Pedido en preparacion y alistamiento.', 'Orden #202', '', 'wc-processing', 'orden-202', 'http://localhost:8085/orden-202', 'shop_order'),
(203, 1, '2026-11-23 08:10:00', 'Pedido a la espera de confirmacion de transferencia bancaria.', 'Orden #203', '', 'wc-on-hold', 'orden-203', 'http://localhost:8085/orden-203', 'shop_order')
ON DUPLICATE KEY UPDATE `post_title` = VALUES(`post_title`);

-- -----------------------------------------------------
-- Table: wp_postmeta
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_postmeta` (`post_id`, `meta_key`, `meta_value`) VALUES
-- Product 101 Meta
(101, '_price', '189000'),
(101, '_regular_price', '189000'),
(101, '_thumbnail_id', '0'),
(101, '_stock_status', 'instock'),

-- Product 102 Meta
(102, '_price', '65000'),
(102, '_regular_price', '65000'),
(102, '_thumbnail_id', '0'),
(102, '_stock_status', 'instock'),

-- Product 103 Meta
(103, '_price', '45000'),
(103, '_regular_price', '45000'),
(103, '_thumbnail_id', '0'),
(103, '_stock_status', 'instock'),

-- Product 104 Meta
(104, '_price', '145000'),
(104, '_regular_price', '145000'),
(104, '_thumbnail_id', '0'),
(104, '_stock_status', 'instock'),

-- Order 201 Meta
(201, '_billing_first_name', 'Carlos'),
(201, '_billing_last_name', 'Mendoza'),
(201, '_billing_email', 'carlos.mendoza@email.com'),
(201, '_billing_phone', '+57 310 456 7890'),
(201, '_billing_address_1', 'Calle 72 # 11-45'),
(201, '_billing_city', 'Bogota'),
(201, '_billing_state', 'Cundinamarca'),
(201, '_billing_country', 'CO'),
(201, '_billing_postcode', '110221'),
(201, '_customer_ip_address', '186.84.92.14'),
(201, '_order_total', '234000'),
(201, '_order_currency', 'COP'),
(201, '_payment_method_title', 'Tarjeta de Credito (Wompi)'),

-- Order 202 Meta
(202, '_billing_first_name', 'Valentina'),
(202, '_billing_last_name', 'Gomez'),
(202, '_billing_email', 'valen.gomez@email.com'),
(202, '_billing_phone', '+57 320 987 6543'),
(202, '_billing_address_1', 'Carrera 43A # 1-50 Apt 602'),
(202, '_billing_city', 'Medellin'),
(202, '_billing_state', 'Antioquia'),
(202, '_billing_country', 'CO'),
(202, '_billing_postcode', '050021'),
(202, '_customer_ip_address', '190.158.12.33'),
(202, '_order_total', '130000'),
(202, '_order_currency', 'COP'),
(202, '_payment_method_title', 'PSE Transferencia Bancaria'),

-- Order 203 Meta
(203, '_billing_first_name', 'Andres'),
(203, '_billing_last_name', 'Salazar'),
(203, '_billing_email', 'andres.salazar@email.com'),
(203, '_billing_phone', '+57 301 234 5678'),
(203, '_billing_address_1', 'Avenida Santander # 54-12'),
(203, '_billing_city', 'Manizales'),
(203, '_billing_state', 'Caldas'),
(203, '_billing_country', 'CO'),
(203, '_billing_postcode', '170001'),
(203, '_customer_ip_address', '181.60.201.5'),
(203, '_order_total', '145000'),
(203, '_order_currency', 'COP'),
(203, '_payment_method_title', 'Contra Entrega / Efectivo');

-- -----------------------------------------------------
-- Table: wp_wc_order_product_lookup
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_wc_order_product_lookup` (
  `order_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variation_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `product_qty` int(11) NOT NULL,
  `product_net_revenue` double NOT NULL DEFAULT 0,
  `product_gross_revenue` double NOT NULL DEFAULT 0,
  `coupon_amount` double NOT NULL DEFAULT 0,
  `tax_amount` double NOT NULL DEFAULT 0,
  `shipping_amount` double NOT NULL DEFAULT 0,
  `shipping_tax_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`) VALUES
(1, 201, 101, '2026-11-21 15:20:00', 1, 189000, 189000),
(2, 201, 103, '2026-11-21 15:20:00', 1, 45000, 45000),
(3, 202, 102, '2026-11-22 18:40:00', 2, 130000, 130000),
(4, 203, 104, '2026-11-23 08:10:00', 1, 145000, 145000)
ON DUPLICATE KEY UPDATE `product_net_revenue` = VALUES(`product_net_revenue`);

-- -----------------------------------------------------
-- Table: wp_terms & taxonomies
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wp_terms` (`term_id`, `name`, `slug`) VALUES
(1, 'Calzado', 'calzado'),
(2, 'Ropa', 'ropa'),
(3, 'Accesorios', 'accesorios')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES
(1, 1, 'product_cat', 'Calzado deportivo y urbano', 0, 1),
(2, 2, 'product_cat', 'Prendas de vestir', 0, 1),
(3, 3, 'product_cat', 'Gorras, morrales y complementos', 0, 2)
ON DUPLICATE KEY UPDATE `taxonomy` = VALUES(`taxonomy`);

INSERT INTO `wp_term_relationships` (`object_id`, `term_taxonomy_id`) VALUES
(101, 1),
(102, 2),
(103, 3),
(104, 3)
ON DUPLICATE KEY UPDATE `term_order` = VALUES(`term_order`);

SET FOREIGN_KEY_CHECKS = 1;
