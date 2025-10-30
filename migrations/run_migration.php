<?php
/**
 * Script chạy migration để tạo bảng gia công mạ nhúng nóng
 */

require_once '../config/db_config.php';

echo "=================================================\n";
echo "MIGRATION: Tạo bảng cho chức năng gia công mạ nhúng nóng\n";
echo "=================================================\n\n";

try {
    $pdo = get_db_connection();

    // Đọc file SQL
    $sqlFile = __DIR__ . '/create_gia_cong_tables.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Không tìm thấy file migration: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    if (empty($sql)) {
        throw new Exception("File migration rỗng");
    }

    echo "Đang thực thi migration...\n\n";

    // Tách các câu lệnh SQL
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Loại bỏ comments và empty statements
            $stmt = preg_replace('/^\s*--.*$/m', '', $stmt);
            return !empty(trim($stmt));
        }
    );

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $index => $statement) {
        // Bỏ qua các dòng comment
        if (preg_match('/^\s*--/', $statement)) {
            continue;
        }

        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $successCount++;

            // Lấy tên bảng hoặc thao tác
            if (preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Tạo bảng: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Cập nhật bảng: {$matches[1]}\n";
            } else {
                echo "✓ Thực thi câu lệnh #" . ($index + 1) . "\n";
            }

        } catch (PDOException $e) {
            $errorCount++;
            // Bỏ qua lỗi nếu bảng/cột đã tồn tại
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Đã tồn tại, bỏ qua: " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "✗ LỖI: " . $e->getMessage() . "\n";
                echo "  SQL: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }

    echo "\n=================================================\n";
    echo "KẾT QUẢ MIGRATION\n";
    echo "=================================================\n";
    echo "✓ Thành công: $successCount câu lệnh\n";
    echo "✗ Lỗi: $errorCount câu lệnh\n";
    echo "\n";

    // Kiểm tra các bảng đã được tạo
    echo "Kiểm tra các bảng:\n";
    $tables = ['phieu_xuat_gia_cong', 'lich_su_gia_cong'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Bảng '$table' đã tồn tại\n";
        } else {
            echo "✗ Bảng '$table' KHÔNG tồn tại\n";
        }
    }

    echo "\n=================================================\n";
    echo "HOÀN TẤT MIGRATION!\n";
    echo "=================================================\n";

} catch (Exception $e) {
    echo "\n✗ LỖI NGHIÊM TRỌNG: " . $e->getMessage() . "\n";
    exit(1);
}
?>
