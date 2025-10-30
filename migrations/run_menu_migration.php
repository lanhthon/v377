<?php
/**
 * Script chạy migration thêm menu và phân quyền
 */

require_once '../config/db_config.php';

echo "=================================================\n";
echo "MIGRATION: Thêm menu và phân quyền gia công mạ nhúng nóng\n";
echo "=================================================\n\n";

try {
    $pdo = get_db_connection();

    // Đọc file SQL
    $sqlFile = __DIR__ . '/add_gia_cong_menu_permissions.sql';

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
            $stmt = preg_replace('/^\s*--.*$/m', '', $stmt);
            return !empty(trim($stmt));
        }
    );

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $index => $statement) {
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

            if (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Insert vào bảng: {$matches[1]}\n";
            } elseif (preg_match('/SET @(\w+)/i', $statement, $matches)) {
                echo "✓ Thiết lập biến: @{$matches[1]}\n";
            } elseif (preg_match('/SELECT.*AS Message/i', $statement)) {
                echo "✓ Migration hoàn tất\n";
            } else {
                echo "✓ Thực thi câu lệnh #" . ($index + 1) . "\n";
            }

        } catch (PDOException $e) {
            $errorCount++;
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Đã tồn tại, bỏ qua\n";
            } else {
                echo "✗ LỖI: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n=================================================\n";
    echo "KẾT QUẢ MIGRATION\n";
    echo "=================================================\n";
    echo "✓ Thành công: $successCount câu lệnh\n";
    echo "✗ Lỗi: $errorCount câu lệnh\n";
    echo "\n";

    // Kiểm tra các chức năng đã được thêm
    echo "Kiểm tra các chức năng:\n";
    $stmt = $pdo->query("
        SELECT TenChucNang, MoTa
        FROM chucnang
        WHERE TenChucNang IN ('gia_cong_list', 'gia_cong_view', 'xuat_gia_cong', 'nhap_gia_cong')
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "✓ {$row['TenChucNang']}: {$row['MoTa']}\n";
    }

    echo "\n=================================================\n";
    echo "HOÀN TẤT MIGRATION!\n";
    echo "=================================================\n";

} catch (Exception $e) {
    echo "\n✗ LỖI NGHIÊM TRỌNG: " . $e->getMessage() . "\n";
    exit(1);
}
?>
