<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Helpers\Security;
use PDO;
use RuntimeException;

class UploadService
{
    private PDO $db;
    private string $prefix;
    private string $uploadDir;
    private string $uploadUrlPath;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Database::getPdo();
        $this->prefix = Database::getInstance()->getTablePrefix();
        $this->resolveUploadPaths();
    }

    private function resolveUploadPaths(): void
    {
        // Detect if within WordPress installation
        $wpUploads = __DIR__ . '/../../../../wp-content/uploads/products';
        if (is_dir(dirname($wpUploads))) {
            $this->uploadDir = $wpUploads;
            $this->uploadUrlPath = '/wp-content/uploads/products/';
        } else {
            $this->uploadDir = __DIR__ . '/../../uploads/products';
            $this->uploadUrlPath = '/uploads/products/';
        }

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload an image and register it in WordPress media library
     */
    public function handleUpload(array $file): array
    {
        if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al subir el archivo (codigo: ' . ($file['error'] ?? 'desconocido') . ').');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!isset($allowedMimes[$mime])) {
            throw new RuntimeException('Tipo de archivo no permitido. Solo se aceptan imagenes JPG, PNG, WEBP o GIF.');
        }

        // Limit size to 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('El archivo excede el tamano maximo permitido (10MB).');
        }

        $ext = $allowedMimes[$mime];
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeName = Security::sanitizeSlug($originalName);
        $uniqueName = sprintf('%s-%s.%s', $safeName !== '' ? $safeName : 'producto', uniqid(), $ext);

        $targetPath = rtrim($this->uploadDir, '/') . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo mover el archivo subido al directorio de destino.');
        }

        // Determine site URL
        $optionsTable = $this->prefix . 'options';
        $siteUrl = '';
        try {
            $stmt = $this->db->prepare("SELECT option_value FROM `{$optionsTable}` WHERE option_name = 'siteurl' LIMIT 1");
            $stmt->execute();
            $siteUrl = (string)$stmt->fetchColumn();
        } catch (\Throwable) {
            $siteUrl = '';
        }

        if ($siteUrl === '') {
            $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        $guid = rtrim($siteUrl, '/') . $this->uploadUrlPath . $uniqueName;

        // Register in WordPress wp_posts
        $postsTable = $this->prefix . 'posts';
        $metaTable = $this->prefix . 'postmeta';

        $this->db->beginTransaction();
        try {
            $sqlPost = "
                INSERT INTO `{$postsTable}` (
                    post_author, post_date, post_date_gmt, post_content, post_title,
                    post_excerpt, post_status, comment_status, ping_status, post_name,
                    post_modified, post_modified_gmt, guid, post_type, post_mime_type, comment_count
                ) VALUES (
                    1, NOW(), NOW(), '', :title,
                    '', 'inherit', 'open', 'closed', :name,
                    NOW(), NOW(), :guid, 'attachment', :mime, 0
                )
            ";

            $stmt = $this->db->prepare($sqlPost);
            $stmt->execute([
                'title' => $originalName,
                'name'  => $safeName,
                'guid'  => $guid,
                'mime'  => $mime,
            ]);

            $attachmentId = (int)$this->db->lastInsertId();

            // Register _wp_attached_file
            $sqlMeta = "INSERT INTO `{$metaTable}` (post_id, meta_key, meta_value) VALUES (:post_id, :key, :val)";
            $stmtMeta = $this->db->prepare($sqlMeta);
            $stmtMeta->execute([
                'post_id' => $attachmentId,
                'key'     => '_wp_attached_file',
                'val'     => 'products/' . $uniqueName,
            ]);

            $this->db->commit();

            return [
                'id'       => $attachmentId,
                'name'     => $uniqueName,
                'title'    => $originalName,
                'url'      => $guid,
                'mime'     => $mime,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            @unlink($targetPath);
            throw $e;
        }
    }
}
