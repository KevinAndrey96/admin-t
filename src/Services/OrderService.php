<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use PDO;

class OrderService
{
    private PDO $db;
    private string $prefix;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getPdo();
        $this->prefix = Database::getInstance()->getTablePrefix();
    }

    /**
     * Fetch orders using a high-performance single SQL query
     */
    public function getAll(int $limit = 200, string $statusFilter = ''): array
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        $where = "p.post_type = 'shop_order' AND p.post_status != 'trash'";
        $params = [];

        if ($statusFilter !== '') {
            $where .= " AND p.post_status = :status";
            $params['status'] = $statusFilter;
        }

        $sql = "
            SELECT 
                p.ID,
                p.post_date,
                p.post_status,
                COALESCE(pm_fn.meta_value, '') AS first_name,
                COALESCE(pm_ln.meta_value, '') AS last_name,
                COALESCE(pm_email.meta_value, '') AS email,
                COALESCE(pm_phone.meta_value, '') AS phone,
                COALESCE(pm_city.meta_value, '') AS city,
                COALESCE(pm_total.meta_value, '0') AS total,
                COALESCE(pm_curr.meta_value, 'COP') AS currency
            FROM `{$postsTable}` p
            LEFT JOIN `{$metaTable}` pm_fn 
                ON pm_fn.post_id = p.ID AND pm_fn.meta_key = '_billing_first_name'
            LEFT JOIN `{$metaTable}` pm_ln 
                ON pm_ln.post_id = p.ID AND pm_ln.meta_key = '_billing_last_name'
            LEFT JOIN `{$metaTable}` pm_email 
                ON pm_email.post_id = p.ID AND pm_email.meta_key = '_billing_email'
            LEFT JOIN `{$metaTable}` pm_phone 
                ON pm_phone.post_id = p.ID AND pm_phone.meta_key = '_billing_phone'
            LEFT JOIN `{$metaTable}` pm_city 
                ON pm_city.post_id = p.ID AND pm_city.meta_key = '_billing_city'
            LEFT JOIN `{$metaTable}` pm_total 
                ON pm_total.post_id = p.ID AND pm_total.meta_key = '_order_total'
            LEFT JOIN `{$metaTable}` pm_curr 
                ON pm_curr.post_id = p.ID AND pm_curr.meta_key = '_order_currency'
            WHERE {$where}
            ORDER BY p.ID DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
            $row['customer_name'] = $fullName !== '' ? $fullName : 'Cliente Desconocido';
            $row['total_amount'] = (float)$row['total'];
        }

        return $rows;
    }

    /**
     * Get complete order details including billing, shipping, and IP address
     */
    public function getById(int $id): ?array
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        $stmt = $this->db->prepare("SELECT * FROM `{$postsTable}` WHERE ID = :id AND post_type = 'shop_order' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        // Fetch all postmeta key-value pairs in one clean query
        $stmtMeta = $this->db->prepare("SELECT meta_key, meta_value FROM `{$metaTable}` WHERE post_id = :id");
        $stmtMeta->execute(['id' => $id]);
        $metaRows = $stmtMeta->fetchAll();

        $meta = [];
        foreach ($metaRows as $row) {
            $meta[$row['meta_key']] = $row['meta_value'];
        }

        $firstName = $meta['_billing_first_name'] ?? '';
        $lastName = $meta['_billing_last_name'] ?? '';
        $fullName = trim("{$firstName} {$lastName}");

        $items = $this->getItems($id);

        return [
            'id'             => $id,
            'status'         => $order['post_status'],
            'date'           => $order['post_date'],
            'customer_name'  => $fullName !== '' ? $fullName : 'Cliente Desconocido',
            'email'          => $meta['_billing_email'] ?? '',
            'phone'          => $meta['_billing_phone'] ?? '',
            'address'        => trim(($meta['_billing_address_1'] ?? '') . ' ' . ($meta['_billing_address_2'] ?? '')),
            'city'           => $meta['_billing_city'] ?? '',
            'state'          => $meta['_billing_state'] ?? '',
            'postcode'       => $meta['_billing_postcode'] ?? '',
            'country'        => $meta['_billing_country'] ?? '',
            'ip'             => $meta['_customer_ip_address'] ?? 'N/A',
            'payment_method' => $meta['_payment_method_title'] ?? ($meta['_payment_method'] ?? 'N/A'),
            'total'          => (float)($meta['_order_total'] ?? 0),
            'currency'       => $meta['_order_currency'] ?? 'COP',
            'items'          => $items,
        ];
    }

    /**
     * Get line items for an order
     */
    public function getItems(int $orderId): array
    {
        $lookupTable = $this->prefix . 'wc_order_product_lookup';
        $postsTable = $this->prefix . 'posts';
        $items = [];

        // Try WooCommerce Lookup Table first
        try {
            $sqlLookup = "
                SELECT 
                    l.product_id,
                    p.post_title AS product_name,
                    l.product_qty AS quantity,
                    l.product_net_revenue AS net_revenue
                FROM `{$lookupTable}` l
                LEFT JOIN `{$postsTable}` p ON p.ID = l.product_id
                WHERE l.order_id = :order_id
            ";
            $stmt = $this->db->prepare($sqlLookup);
            $stmt->execute(['order_id' => $orderId]);
            $items = $stmt->fetchAll();
        } catch (\Throwable) {
            $items = [];
        }

        // Fallback to woocommerce_order_items table if lookup is empty
        if (empty($items)) {
            $orderItemsTable = $this->prefix . 'woocommerce_order_items';
            $orderItemMetaTable = $this->prefix . 'woocommerce_order_itemmeta';

            try {
                $sqlItems = "
                    SELECT 
                        oi.order_item_id,
                        oi.order_item_name AS product_name,
                        MAX(CASE WHEN oim.meta_key = '_product_id' THEN oim.meta_value END) AS product_id,
                        MAX(CASE WHEN oim.meta_key = '_qty' THEN oim.meta_value END) AS quantity,
                        MAX(CASE WHEN oim.meta_key = '_line_total' THEN oim.meta_value END) AS net_revenue
                    FROM `{$orderItemsTable}` oi
                    LEFT JOIN `{$orderItemMetaTable}` oim ON oim.order_item_id = oi.order_item_id
                    WHERE oi.order_id = :order_id AND oi.order_item_type = 'line_item'
                    GROUP BY oi.order_item_id
                ";
                $stmt = $this->db->prepare($sqlItems);
                $stmt->execute(['order_id' => $orderId]);
                $items = $stmt->fetchAll();
            } catch (\Throwable) {
                // Table might not exist in minimalist setups
            }
        }

        $formatted = [];
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 1);
            $revenue = (float)($item['net_revenue'] ?? 0);
            $formatted[] = [
                'id'       => (int)($item['product_id'] ?? 0),
                'name'     => (string)($item['product_name'] ?? 'Producto'),
                'quantity' => $qty,
                'total'    => $revenue,
                'unit'     => $qty > 0 ? ($revenue / $qty) : $revenue,
            ];
        }

        return $formatted;
    }

    /**
     * Update order status with security validation
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $allowedStatuses = [
            'wc-completed',
            'wc-processing',
            'wc-on-hold',
            'wc-pending',
            'wc-cancelled',
            'wc-refunded',
            'wc-failed',
            'trash',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException("Estado no valido: {$status}");
        }

        $postsTable = $this->prefix . 'posts';
        $stmt = $this->db->prepare("
            UPDATE `{$postsTable}` 
            SET post_status = :status, 
                post_modified = NOW(), 
                post_modified_gmt = NOW() 
            WHERE ID = :id AND post_type = 'shop_order'
        ");

        return $stmt->execute([
            'status' => $status,
            'id'     => $orderId,
        ]);
    }

    /**
     * Compute store metrics for dashboard widgets
     */
    public function getStats(): array
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        // Total orders count
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE post_type = 'shop_order' AND post_status != 'trash'");
        $totalOrders = (int)$stmt->fetchColumn();

        // Completed orders count
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE post_type = 'shop_order' AND post_status = 'wc-completed'");
        $completedOrders = (int)$stmt->fetchColumn();

        // Pending or processing orders count
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE post_type = 'shop_order' AND post_status IN ('wc-pending', 'wc-processing', 'wc-on-hold')");
        $pendingOrders = (int)$stmt->fetchColumn();

        // Total sales revenue from completed/processing orders
        $stmt = $this->db->query("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
            FROM `{$postsTable}` p
            JOIN `{$metaTable}` pm ON pm.post_id = p.ID AND pm.meta_key = '_order_total'
            WHERE p.post_type = 'shop_order' AND p.post_status IN ('wc-completed', 'wc-processing')
        ");
        $totalSales = (float)$stmt->fetchColumn();

        return [
            'total_orders'     => $totalOrders,
            'completed_orders' => $completedOrders,
            'pending_orders'   => $pendingOrders,
            'total_sales'      => $totalSales,
        ];
    }
}
