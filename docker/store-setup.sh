#!/bin/sh
# Installs WordPress + WooCommerce with demo products and orders (first run only)
# and creates a fresh application password for the panel on every run.
set -eu
cd /var/www/html
export WP_CLI_CACHE_DIR=/tmp/wp-cli-cache

until wp db query 'SELECT 1' >/dev/null 2>&1; do
    echo 'waiting for the database...'
    sleep 2
done

if ! wp core is-installed 2>/dev/null; then
    admin_password=$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)
    wp core install --url="$STORE_URL" --title="$STORE_TITLE" --admin_user=admin \
        --admin_password="$admin_password" --admin_email=admin@example.com --skip-email

    # The demo store has no mail server: skip outgoing emails instead of timing out on sendmail.
    mkdir -p wp-content/mu-plugins
    printf "<?php\nadd_filter('pre_wp_mail', '__return_true');\n" > wp-content/mu-plugins/demo-no-mail.php

    wp language core install es_CO --activate

    printf 'apache_modules:\n  - mod_rewrite\n' > /tmp/wp-cli.yml
    WP_CLI_CONFIG_PATH=/tmp/wp-cli.yml wp rewrite structure '/%postname%/' --hard

    wp plugin install woocommerce --activate
    wp language plugin install woocommerce es_CO
    wp option update woocommerce_currency COP
    wp option update woocommerce_default_country 'CO:DC'
    wp option update woocommerce_price_num_decimals 0
    wp option update woocommerce_coming_soon no

    product() {
        wp wc product create --user=admin --porcelain --type=simple --status=publish \
            --name="$1" --sku="$2" --regular_price="$3" --manage_stock=true --stock_quantity="$4"
    }
    mug=$(product 'Taza de cerámica artesanal' TAZA-001 35000 24)
    tote=$(product 'Bolso de lona estampado' BOLSO-002 59000 12)
    coffee=$(product 'Café de origen Huila 500 g' CAFE-003 42000 40)
    product 'Libreta reciclada A5' LIBRETA-004 18000 0 >/dev/null

    order() {
        wp wc shop_order create --user=admin --porcelain --status="$1" --set_paid="$2" \
            --payment_method_title='Transferencia bancaria' \
            --billing="{\"first_name\":\"$3\",\"last_name\":\"$4\",\"email\":\"$5\",\"phone\":\"3001234567\",\"address_1\":\"Calle 45 # 12-30\",\"city\":\"Bogotá\",\"state\":\"DC\",\"country\":\"CO\"}" \
            --shipping="{\"first_name\":\"$3\",\"last_name\":\"$4\",\"address_1\":\"Calle 45 # 12-30\",\"city\":\"Bogotá\",\"state\":\"DC\",\"country\":\"CO\"}" \
            --line_items="$6"
    }
    order processing true Laura Gómez laura@example.com "[{\"product_id\":$mug,\"quantity\":2},{\"product_id\":$coffee,\"quantity\":1}]" >/dev/null
    order completed true Andrés Rojas andres@example.com "[{\"product_id\":$tote,\"quantity\":1}]" >/dev/null
    order on-hold false Camila Díaz camila@example.com "[{\"product_id\":$coffee,\"quantity\":3}]" >/dev/null
    order pending false Julián Torres julian@example.com "[{\"product_id\":$mug,\"quantity\":1}]" >/dev/null

    echo "WordPress admin: http://localhost:8081/wp-admin  user: admin  password: $admin_password"
fi

for uuid in $(wp user application-password list admin --name='Panel local' --field=uuid); do
    wp user application-password delete admin "$uuid" >/dev/null
done
app_password=$(wp user application-password create admin 'Panel local' --porcelain)
echo "Panel: http://localhost:8080  user: admin  application password: $app_password"
