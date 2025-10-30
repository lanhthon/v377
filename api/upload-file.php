<?php
// api/upload-file.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// --- Cấu hình upload ---
$uploadDir = dirname(__DIR__) . '/downloads/';
$maxFileSize = 100 * 1024 * 1024; // 100MB
$allowedExtensions = ['exe', 'msi', 'rar', 'zip'];

// --- Bắt đầu xử lý upload ---
try {
    // Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Chỉ chấp nhận POST request');
    }
    
    // Kiểm tra có file upload không
    if (!isset($_FILES['setup_file']) || $_FILES['setup_file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File quá lớn (vượt quá giới hạn PHP)',
            UPLOAD_ERR_FORM_SIZE => 'File quá lớn (vượt quá giới hạn form)',
            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Không tìm thấy thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
            UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
        ];
        
        $error_code = $_FILES['setup_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = $error_messages[$error_code] ?? 'Lỗi upload không xác định';
        throw new Exception($error_message);
    }
    
    $uploadedFile = $_FILES['setup_file'];
    $version = $_POST['version'] ?? '';
    $expectedName = $_POST['expected_name'] ?? '';
    
    // Validate dữ liệu
    if (empty($version)) {
        throw new Exception('Thiếu thông tin phiên bản');
    }
    
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        throw new Exception('Format phiên bản không hợp lệ');
    }
    
    // Kiểm tra kích thước file
    if ($uploadedFile['size'] > $maxFileSize) {
        throw new Exception('File quá lớn. Tối đa ' . formatFileSize($maxFileSize));
    }
    
    // Kiểm tra extension
    $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Chỉ chấp nhận file: ' . implode(', ', $allowedExtensions));
    }
    
    // Tạo tên file đích
    $targetFileName = "3igreen Setup {$version}.exe";
    $targetPath = $uploadDir . $targetFileName;
    
    // Tạo thư mục downloads nếu chưa có
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Không thể tạo thư mục downloads');
        }
    }
    
    // Kiểm tra quyền ghi
    if (!is_writable($uploadDir)) {
        throw new Exception('Không có quyền ghi vào thư mục downloads');
    }
    
    // Sao lưu file cũ nếu tồn tại
    if (file_exists($targetPath)) {
        $backupPath = $uploadDir . "backup_" . date('Y-m-d_H-i-s') . "_" . $targetFileName;
        if (!rename($targetPath, $backupPath)) {
            error_log("Không thể sao lưu file cũ: $targetPath");
        }
    }
    
    // Di chuyển file upload
    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        throw new Exception('Không thể lưu file');
    }
    
    // Thiết lập quyền file
    chmod($targetPath, 0644);
    
    // Tạo URL download
    $baseUrl = getBaseUrl();
    $downloadUrl = $baseUrl . '/downloads/' . $targetFileName;
    
    // Lấy thông tin file
    $fileSize = formatFileSize(filesize($targetPath));
    
    // Ghi log
    logUpload($version, $targetFileName, $uploadedFile['size'], $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    // Trả về kết quả thành công
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Upload file thành công',
        'filename' => $targetFileName,
        'download_url' => $downloadUrl,
        'file_size' => $fileSize,
        'version' => $version,
        'upload_time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Lỗi upload file: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// --- Các hàm hỗ trợ ---

/**
 * Format file size
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    
    return round($size, 1) . ' ' . $units[$unit];
}

/**
 * Lấy base URL
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Loại bỏ /api từ path
    $basePath = str_replace('/api', '', $scriptPath);
    
    return $protocol . $host . $basePath;
}

/**
 * Ghi log upload
 */
function logUpload($version, $filename, $fileSize, $ip) {
    try {
        $logFile = dirname(__DIR__) . '/logs/upload.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $version,
            'filename' => $filename,
            'file_size' => $fileSize,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        
    } catch (Exception $e) {
        error_log("Lỗi ghi log upload: " . $e->getMessage());
    }
}

/**
 * Validate và làm sạch tên file
 */
function sanitizeFilename($filename) {
    // Loại bỏ các ký tự nguy hiểm
    $filename = preg_replace('/[^a-zA-Z0-9\.\-_\s]/', '', $filename);
    
    // Loại bỏ khoảng trắng thừa
    $filename = preg_replace('/\s+/', ' ', trim($filename));
    
    return $filename;
}

/**
 * Kiểm tra file có phải malware không (cơ bản)
 */
function basicSecurityCheck($filePath) {
    // Kiểm tra kích thước file (file quá nhỏ có thể đáng ngờ)
    $fileSize = filesize($filePath);
    if ($fileSize < 1024) { // Nhỏ hơn 1KB
        return false;
    }
    
    // Kiểm tra magic bytes cho file EXE
    $handle = fopen($filePath, 'rb');
    if ($handle) {
        $header = fread($handle, 2);
        fclose($handle);
        
        // File EXE phải bắt đầu bằng "MZ"
        if ($header !== 'MZ') {
            return false;
        }
    }
    
    return true;
}
?>