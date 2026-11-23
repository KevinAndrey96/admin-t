<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Helpers\Security;
use PDO;

class ProductService
{
    private PDO $db;
    private string $prefix;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getPdo();
        $this->prefix = Database::getInstance()->getTablePrefix();
    }

    /**
     * Fetch products using an optimized single-query JOIN
     */
    public function getAll(int $limit = 200, int $offset = 0, string $search = ''): array
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';
        $relTable = $this->prefix . 'term_relationships';
        $taxTable = $this->prefix . 'term_taxonomy';
        $termsTable = $this->prefix . 'terms';

        $where = "p.post_type = 'product' AND p.post_status != 'trash'";
        $params = [];

        if ($search !== '') {
            $where .= " AND (p.post_title LIKE :search OR p.post_content LIKE :search OR p.ID = :searchId)";
            $params['search'] = '%' . $search . '%';
            $params['searchId'] = is_numeric($search) ? (int)$search : 0;
        }

        $sql = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_name,
                p.post_excerpt,
                p.post_content,
                p.post_status,
                p.post_date,
                p.guid,
                COALESCE(pm_price.meta_value, '0') AS price,
                COALESCE(pm_reg.meta_value, '0') AS regular_price,
                pm_thumb.meta_value AS thumbnail_id,
                att.guid AS thumbnail_url,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS categories
            FROM `{$postsTable}` p
            LEFT JOIN `{$metaTable}` pm_price 
                ON pm_price.post_id = p.ID AND pm_price.meta_key = '_price'
            LEFT JOIN `{$metaTable}` pm_reg 
                ON pm_reg.post_id = p.ID AND pm_reg.meta_key = '_regular_price'
            LEFT JOIN `{$metaTable}` pm_thumb 
                ON pm_thumb.post_id = p.ID AND pm_thumb.meta_key = '_thumbnail_id'
            LEFT JOIN `{$postsTable}` att 
                ON att.ID = CAST(pm_thumb.meta_value AS UNSIGNED) AND att.post_type = 'attachment'
            LEFT JOIN `{$relTable}` tr 
                ON tr.object_id = p.ID
            LEFT JOIN `{$taxTable}` tt 
                ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy IN ('product_cat', 'category')
            LEFT JOIN `{$termsTable}` t 
                ON t.term_id = tt.term_id
            WHERE {$where}
            GROUP BY p.ID
            ORDER BY p.ID DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get single product by ID
     */
    public function getById(int $id): ?array
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        $sql = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_name,
                p.post_excerpt,
                p.post_content,
                p.post_status,
                p.guid,
                COALESCE(pm_price.meta_value, '0') AS price,
                COALESCE(pm_reg.meta_value, '0') AS regular_price,
                pm_thumb.meta_value AS thumbnail_id,
                att.guid AS thumbnail_url
            FROM `{$postsTable}` p
            LEFT JOIN `{$metaTable}` pm_price 
                ON pm_price.post_id = p.ID AND pm_price.meta_key = '_price'
            LEFT JOIN `{$metaTable}` pm_reg 
                ON pm_reg.post_id = p.ID AND pm_reg.meta_key = '_regular_price'
            LEFT JOIN `{$metaTable}` pm_thumb 
                ON pm_thumb.post_id = p.ID AND pm_thumb.meta_key = '_thumbnail_id'
            LEFT JOIN `{$postsTable}` att 
                ON att.ID = CAST(pm_thumb.meta_value AS UNSIGNED) AND att.post_type = 'attachment'
            WHERE p.ID = :id AND p.post_type = 'product'
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();

        return $product ?: null;
    }

    /**
     * Create product with prepared statements and transaction
     */
    public function create(array $data): int
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';
        $optionsTable = $this->prefix . 'options';

        $title = Security::sanitizeString($data['name'] ?? ($data['NAME'] ?? ''));
        $price = Security::sanitizeFloat($data['price'] ?? ($data['PRICE'] ?? 0));
        $short = Security::sanitizeString($data['short'] ?? ($data['SHORT'] ?? ''));
        $long = Security::sanitizeString($data['long'] ?? ($data['LONG'] ?? ''));
        $thumbId = !empty($data['thumbnail_id']) ? (int)$data['thumbnail_id'] : (!empty($data['FOTO']) ? (int)$data['FOTO'] : 0);

        if ($title === '') {
            throw new \InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        // Get site URL for GUID
        $siteUrl = '';
        try {
            $stmt = $this->db->prepare("SELECT option_value FROM `{$optionsTable}` WHERE option_name = 'siteurl' LIMIT 1");
            $stmt->execute();
            $siteUrl = (string)$stmt->fetchColumn();
        } catch (\Throwable) {
            $siteUrl = 'http://localhost';
        }

        $slugBase = Security::sanitizeSlug($title);
        $slug = $slugBase . '-' . rand(100, 999);
        $guid = rtrim($siteUrl, '/') . '/producto/' . $slug;

        $this->db->beginTransaction();
        try {
            $insertPost = "
                INSERT INTO `{$postsTable}` (
                    post_author, post_date, post_date_gmt, post_content, post_title,
                    post_excerpt, post_status, comment_status, ping_status, post_name,
                    post_modified, post_modified_gmt, guid, post_type, comment_count
                ) VALUES (
                    1, NOW(), NOW(), :long, :title,
                    :short, 'publish', 'closed', 'closed', :slug,
                    NOW(), NOW(), :guid, 'product', 0
                )
            ";
            $stmt = $this->db->prepare($insertPost);
            $stmt->execute([
                'long'  => $long,
                'title' => $title,
                'short' => $short,
                'slug'  => $slug,
                'guid'  => $guid,
            ]);

            $productId = (int)$this->db->lastInsertId();

            $metaEntries = [
                '_price'                 => (string)$price,
                '_regular_price'         => (string)$price,
                '_thumbnail_id'          => (string)$thumbId,
                '_product_image_gallery' => '',
                '_visibility'            => 'visible',
                '_stock_status'          => 'instock',
            ];

            $insertMeta = $this->db->prepare("
                INSERT INTO `{$metaTable}` (post_id, meta_key, meta_value) 
                VALUES (:post_id, :meta_key, :meta_value)
            ");

            foreach ($metaEntries as $k => $v) {
                $insertMeta->execute([
                    'post_id'    => $productId,
                    'meta_key'   => $k,
                    'meta_value' => $v,
                ]);
            }

            $this->db->commit();
            return $productId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update product details and postmeta
     */
    public function update(int $id, array $data): bool
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        $title = Security::sanitizeString($data['name'] ?? ($data['NAME'] ?? ''));
        $price = Security::sanitizeFloat($data['price'] ?? ($data['PRICE'] ?? 0));
        $short = Security::sanitizeString($data['short'] ?? ($data['SHORT'] ?? ''));
        $long = Security::sanitizeString($data['long'] ?? ($data['LONG'] ?? ''));
        $thumbId = isset($data['thumbnail_id']) ? (int)$data['thumbnail_id'] : (isset($data['FOTO']) ? (int)$data['FOTO'] : null);

        $this->db->beginTransaction();
        try {
            $updatePost = "
                UPDATE `{$postsTable}` 
                SET post_title = :title, 
                    post_content = :long, 
                    post_excerpt = :short, 
                    post_modified = NOW(), 
                    post_modified_gmt = NOW() 
                WHERE ID = :id AND post_type = 'product'
            ";
            $stmt = $this->db->prepare($updatePost);
            $stmt->execute([
                'title' => $title,
                'long'  => $long,
                'short' => $short,
                'id'    => $id,
            ]);

            $this->upsertMeta($id, '_price', (string)$price);
            $this->upsertMeta($id, '_regular_price', (string)$price);

            if ($thumbId !== null) {
                $this->upsertMeta($id, '_thumbnail_id', (string)$thumbId);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Upsert helper for postmeta
     */
    private function upsertMeta(int $postId, string $key, string $value): void
    {
        $metaTable = $this->prefix . 'postmeta';
        $check = $this->db->prepare("SELECT meta_id FROM `{$metaTable}` WHERE post_id = :id AND meta_key = :k LIMIT 1");
        $check->execute(['id' => $postId, 'k' => $key]);
        $existing = $check->fetchColumn();

        if ($existing) {
            $up = $this->db->prepare("UPDATE `{$metaTable}` SET meta_value = :val WHERE meta_id = :mid");
            $up->execute(['val' => $value, 'mid' => $existing]);
        } else {
            $ins = $this->db->prepare("INSERT INTO `{$metaTable}` (post_id, meta_key, meta_value) VALUES (:id, :k, :val)");
            $ins->execute(['id' => $postId, 'k' => $key, 'val' => $value]);
        }
    }

    /**
     * Delete product and associated meta
     */
    public function delete(int $id, bool $permanent = true): bool
    {
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';
        $relTable = $this->prefix . 'term_relationships';

        if (!$permanent) {
            $stmt = $this->db->prepare("UPDATE `{$postsTable}` SET post_status = 'trash' WHERE ID = :id");
            return $stmt->execute(['id' => $id]);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM `{$postsTable}` WHERE ID = :id");
            $stmt->execute(['id' => $id]);

            $stmt = $this->db->prepare("DELETE FROM `{$metaTable}` WHERE post_id = :id");
            $stmt->execute(['id' => $id]);

            $stmt = $this->db->prepare("DELETE FROM `{$relTable}` WHERE object_id = :id");
            $stmt->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get image attachments for media selector
     */
    public function getMediaList(int $limit = 60): array
    {
        $postsTable = $this->prefix . 'posts';
        $sql = "
            SELECT ID, post_title, guid 
            FROM `{$postsTable}` 
            WHERE post_type = 'attachment' 
              AND (post_mime_type LIKE 'image/%')
            ORDER BY ID DESC 
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total product count
     */
    public function countProducts(): int
    {
        $postsTable = $this->prefix . 'posts';
        $stmt = $this->db->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE post_type = 'product' AND post_status != 'trash'");
        return (int)$stmt->fetchColumn();
    }
}
