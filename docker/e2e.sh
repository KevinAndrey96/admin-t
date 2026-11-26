#!/usr/bin/env bash
# End-to-end check of the panel against the demo WooCommerce store started with `make up`.
set -euo pipefail

base=${PANEL_URL:-http://localhost:8080}
jar=$(mktemp)
trap 'rm -f "$jar"' EXIT

password=$(docker compose logs --no-log-prefix store-setup | sed -n 's/.*application password: //p' | tail -n 1)
test -n "$password"

token() { sed -n 's/.*name="_token" value="\([a-f0-9]*\)".*/\1/p' | head -n 1; }
status() { curl -s -o /dev/null -w '%{http_code}' -b "$jar" -c "$jar" "$@"; }
step() { printf '  %s\n' "$1"; }

step 'guests are sent to the login page'
test "$(status "$base/pedidos")" = 303

step 'a wrong application password is refused'
csrf=$(curl -s -b "$jar" -c "$jar" "$base/ingresar" | token)
test "$(status --data-urlencode "_token=$csrf" -d 'usuario=admin&clave_aplicacion=equivocada' "$base/ingresar")" = 422

step 'the shop manager signs in'
test "$(status --data-urlencode "_token=$csrf" -d usuario=admin --data-urlencode "clave_aplicacion=$password" "$base/ingresar")" = 303

step 'the dashboard reads the store'
dashboard=$(curl -s -b "$jar" -c "$jar" "$base/")
grep -q 'Ventas del mes' <<<"$dashboard"
grep -q 'Laura Gómez' <<<"$dashboard"
csrf=$(token <<<"$dashboard")

step 'an order changes status'
order=$(curl -s -b "$jar" "$base/pedidos" | grep -oE 'href="/pedidos/[0-9]+"' | head -n 1 | grep -oE '[0-9]+')
test "$(status -d "_token=$csrf&estado=completed" "$base/pedidos/$order/estado")" = 303
curl -s -b "$jar" -c "$jar" "$base/pedidos/$order" | grep -q 'badge--lg badge--success'

step 'an internal note is added'
test "$(status -d "_token=$csrf" --data-urlencode 'nota=Revisado en la prueba e2e' "$base/pedidos/$order/notas")" = 303
curl -s -b "$jar" -c "$jar" "$base/pedidos/$order" | grep -q 'Revisado en la prueba e2e'

step 'a product is created and sent to the trash'
location=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$jar" -c "$jar" -F "_token=$csrf" -F 'name=Producto e2e' -F 'regular_price=10000' -F 'status=draft' "$base/productos")
[[ "$location" =~ /productos/[0-9]+$ ]]
test "$(status "$location")" = 200
test "$(status -d "_token=$csrf" "$location/eliminar")" = 303
test "$(status "$location")" = 404

step 'forms without a valid token are refused'
test "$(status -d '_token=falso&estado=cancelled' "$base/pedidos/$order/estado")" = 403

step 'signing out ends the session'
test "$(status -d "_token=$csrf" "$base/salir")" = 303
test "$(status "$base/")" = 303

echo 'e2e ok'
