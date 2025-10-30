<?php
// api/app-version.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db_config.php';

// --- Bắt đầu phần xử lý chính ---
try {
    $pdo = get_db_connection();
    $versionChecker = new VersionChecker($pdo);
    
    // Xử lý các method khác nhau
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Kiểm tra phiên bản mới
            $currentVersion = isset($_GET['current']) ? $_GET['current'] : '';
            $result = $versionChecker->checkLatestVersion($currentVersion);
            break;
            
        case 'POST':
            // Cập nhật phiên bản mới (dành cho admin)
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $versionChecker->updateVersion($input);
            break;
            
        default:
            throw new Exception('Method không được hỗ trợ');
    }
    
    http_response_code(200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Lỗi API kiểm tra phiên bản: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Đã xảy ra lỗi hệ thống.', 
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// --- Kết thúc phần xử lý chính ---

class VersionChecker {
    private PDO $pdo;
    private string $baseUrl;
    private string $downloadPath;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->baseUrl = 'https://qlnoibo.3igreen.com.vn';
        $this->downloadPath = 'downloads';
    }
    
    /**
     * Kiểm tra phiên bản mới nhất
     */
    public function checkLatestVersion(string $currentVersion = ''): array {
        try {
            // Lấy phiên bản mới nhất từ database
            $stmt = $this->pdo->prepare("
                SELECT * FROM app_versions 
                WHERE is_active = 1 
                ORDER BY version_number DESC, created_at DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $latestVersion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$latestVersion) {
                // Nếu không có trong DB, lấy thông tin từ file system
                return $this->getVersionFromFileSystem($currentVersion);
            }
            
            // Ghi log request
            $this->logVersionCheck($currentVersion);
            
            // Format response
            $response = [
                'version' => $latestVersion['version'],
                'description' => $latestVersion['description'],
                'downloadUrl' => $latestVersion['download_url'],
                'releaseNotesUrl' => $latestVersion['release_notes_url'] ?: ($this->baseUrl . '/caidatphanmen.php'),
                'releaseDate' => $latestVersion['release_date'],
                'isRequired' => (bool)$latestVersion['is_required'],
                'minVersion' => $latestVersion['min_version'],
                'downloadSize' => $latestVersion['download_size'],
                'changelog' => json_decode($latestVersion['changelog'], true) ?: []
            ];
            
            // Thêm thông tin so sánh nếu có current version
            if (!empty($currentVersion)) {
                $response['hasUpdate'] = version_compare($latestVersion['version'], $currentVersion, '>');
                $response['currentVersion'] = $currentVersion;
            }
            
            return $response;
            
        } catch (Exception $e) {
            // Fallback: lấy từ file system nếu DB lỗi
            return $this->getVersionFromFileSystem($currentVersion);
        }
    }
    
    /**
     * Lấy thông tin version từ file system (fallback)
     */
    private function getVersionFromFileSystem(string $currentVersion = ''): array {
        try {
            // Quét thư mục downloads để tìm file mới nhất
            $downloadDir = dirname(__DIR__) . '/' . $this->downloadPath;
            $latestFile = $this->findLatestVersionFile($downloadDir);
            
            if (!$latestFile) {
                return $this->getDefaultVersionInfo($currentVersion);
            }
            
            // Extract version từ tên file
            preg_match('/(\d+\.\d+\.\d+)/', $latestFile['name'], $matches);
            $version = $matches[1] ?? '3.7.9';
            
            $response = [
                'version' => $version,
                'description' => "Phiên bản cập nhật mới nhất của 3iGreen\n\nCải thiện hiệu suất và sửa các lỗi trong phiên bản trước.",
                'downloadUrl' => $this->baseUrl . '/' . $this->downloadPath . '/' . $latestFile['name'],
                'releaseNotesUrl' => $this->baseUrl . '/caidatphanmen.php',
                'releaseDate' => date('Y-m-d', $latestFile['mtime']),
                'isRequired' => false,
                'minVersion' => '3.7.0',
                'downloadSize' => $this->formatFileSize($latestFile['size']),
                'changelog' => [
                    'Cải thiện giao diện người dùng',
                    'Sửa các lỗi đã phát hiện',
                    'Tối ưu hiệu suất ứng dụng',
                    'Cập nhật bảo mật'
                ]
            ];
            
            // Thêm thông tin so sánh
            if (!empty($currentVersion)) {
                $response['hasUpdate'] = version_compare($version, $currentVersion, '>');
                $response['currentVersion'] = $currentVersion;
            }
            
            // Ghi log
            $this->logVersionCheck($currentVersion);
            
            return $response;
            
        } catch (Exception $e) {
            return $this->getDefaultVersionInfo($currentVersion);
        }
    }
    
    /**
     * Tìm file version mới nhất trong thư mục
     */
    private function findLatestVersionFile(string $dir): ?array {
        if (!is_dir($dir)) {
            return null;
        }
        
        $files = scandir($dir);
        $versionFiles = [];
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . '/' . $file;
            if (!is_file($filePath)) continue;
            
            // Chỉ lấy file có chứa "3igreen" và số version
            if (preg_match('/3igreen.*(\d+\.\d+\.\d+)/i', $file, $matches)) {
                $version = $matches[1];
                $versionFiles[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'version' => $version,
                    'size' => filesize($filePath),
                    'mtime' => filemtime($filePath)
                ];
            }
        }
        
        if (empty($versionFiles)) {
            return null;
        }
        
        // Sort theo version number
        usort($versionFiles, function($a, $b) {
            return version_compare($b['version'], $a['version']);
        });
        
        return $versionFiles[0];
    }
    
    /**
     * Format file size
     */
    private function formatFileSize(int $size): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 1) . ' ' . $units[$unit];
    }
    
    /**
     * Cập nhật thông tin phiên bản mới (dành cho admin)
     */
    public function updateVersion(array $data): array {
        try {
            // Validate dữ liệu đầu vào
            $requiredFields = ['version', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Thiếu trường bắt buộc: $field");
                }
            }
            
            // Kiểm tra version format
            if (!preg_match('/^\d+\.\d+\.\d+$/', $data['version'])) {
                throw new Exception("Format version không hợp lệ. Sử dụng format: x.y.z");
            }
            
            // Tự động tạo download URL nếu không có
            if (empty($data['download_url'])) {
                $data['download_url'] = $this->baseUrl . '/' . $this->downloadPath . '/3igreen Setup ' . $data['version'] . '.rar';
            }
            
            // Tạo version_number để sort
            $versionParts = explode('.', $data['version']);
            $versionNumber = ($versionParts[0] * 10000) + ($versionParts[1] * 100) + $versionParts[2];
            
            // Tạo bảng nếu chưa có
            $this->createTablesIfNotExists();
            
            // Chuẩn bị dữ liệu
            $insertData = [
                'version' => $data['version'],
                'version_number' => $versionNumber,
                'description' => $data['description'],
                'download_url' => $data['download_url'],
                'release_notes_url' => $data['release_notes_url'] ?? ($this->baseUrl . '/caidatphanmen.php'),
                'release_date' => $data['release_date'] ?? date('Y-m-d'),
                'is_required' => isset($data['is_required']) ? (int)$data['is_required'] : 0,
                'min_version' => $data['min_version'] ?? '3.7.0',
                'download_size' => $data['download_size'] ?? '',
                'changelog' => json_encode($data['changelog'] ?? [], JSON_UNESCAPED_UNICODE),
                'is_active' => 1
            ];
            
            // Tắt active cho các version cũ
            $this->pdo->prepare("UPDATE app_versions SET is_active = 0")->execute();
            
            // Insert version mới
            $stmt = $this->pdo->prepare("
                INSERT INTO app_versions 
                (version, version_number, description, download_url, release_notes_url, 
                 release_date, is_required, min_version, download_size, changelog, is_active, created_at)
                VALUES 
                (:version, :version_number, :description, :download_url, :release_notes_url,
                 :release_date, :is_required, :min_version, :download_size, :changelog, :is_active, NOW())
            ");
            
            $stmt->execute($insertData);
            
            return [
                'success' => true,
                'message' => 'Đã cập nhật phiên bản mới thành công',
                'data' => $insertData
            ];
            
        } catch (Exception $e) {
            throw new Exception("Lỗi khi cập nhật phiên bản: " . $e->getMessage());
        }
    }
    
    /**
     * Tạo bảng nếu chưa tồn tại
     */
    private function createTablesIfNotExists(): void {
        $sql = "
        CREATE TABLE IF NOT EXISTS `app_versions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `version` varchar(20) NOT NULL,
            `version_number` int(11) NOT NULL,
            `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `download_url` varchar(500) NOT NULL,
            `release_notes_url` varchar(500) DEFAULT '',
            `release_date` date NOT NULL,
            `is_required` tinyint(1) DEFAULT 0,
            `min_version` varchar(20) DEFAULT '3.0.0',
            `download_size` varchar(20) DEFAULT '',
            `changelog` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `version` (`version`),
            KEY `idx_version_number` (`version_number`),
            KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS `version_check_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `current_version` varchar(20) DEFAULT '',
            `check_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_check_time` (`check_time`),
            KEY `idx_current_version` (`current_version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Ghi log request kiểm tra phiên bản
     */
    private function logVersionCheck(string $currentVersion): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO version_check_logs 
                (ip_address, user_agent, current_version, check_time)
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $currentVersion
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
            error_log("Lỗi ghi log version check: " . $e->getMessage());
        }
    }
    
    /**
     * Trả về thông tin version mặc định
     */
    private function getDefaultVersionInfo(string $currentVersion = ''): array {
        $response = [
            'version' => '3.7.9',
            'description' => 'Phiên bản ổn định hiện tại của 3iGreen\n\nNếu bạn gặp vấn đề, hãy truy cập trang cài đặt để tải phiên bản mới nhất.',
            'downloadUrl' => $this->baseUrl . '/caidatphanmen.php',
            'releaseNotesUrl' => $this->baseUrl . '/caidatphanmen.php',
            'releaseDate' => date('Y-m-d'),
            'isRequired' => false,
            'minVersion' => '3.7.0',
            'downloadSize' => '',
            'changelog' => []
        ];
        
        if (!empty($currentVersion)) {
            $response['hasUpdate'] = version_compare('3.7.9', $currentVersion, '>');
            $response['currentVersion'] = $currentVersion;
        }
        
        return $response;
    }
}
?>