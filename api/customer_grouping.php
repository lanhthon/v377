<?php
// api/customer_grouping.php

// Bật báo cáo lỗi để dễ dàng gỡ lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Thiết lập header để trả về JSON
header('Content-Type: application/json; charset=utf-8');

// Bao gồm tệp cấu hình cơ sở dữ liệu của bạn
// Hãy đảm bảo đường dẫn này chính xác với cấu trúc thư mục của bạn
require_once '../config/db_config.php';

try {
    // Tạo kết nối PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Câu lệnh SQL để lấy tổng giá trị và số lượng đơn hàng đã "Chốt" cho mỗi công ty
    $sql = "
        SELECT
            CongTyID,
            TenCongTy,
            COUNT(BaoGiaID) AS SoLuongDonHang,
            SUM(TongTienSauThue) AS TongGiaTri
        FROM
            baogia
        WHERE
            TrangThai = 'Chốt'
        GROUP BY
            CongTyID, TenCongTy
        HAVING
            CongTyID IS NOT NULL AND TenCongTy IS NOT NULL
        ORDER BY
            TongGiaTri DESC;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $companiesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Khởi tạo các nhóm
    $groups = [
        'Chiến lược' => [],
        'Đại Lý' => [],
        'Thân Thiết' => [],
        'Tiềm năng' => []
    ];

    // Định nghĩa các ngưỡng phân loại (bạn có thể điều chỉnh các giá trị này)
    define('STRATEGIC_VALUE_THRESHOLD', 2000000000000); // Ngưỡng giá trị cho nhóm Chiến lược (2,000 tỷ)
    define('STRATEGIC_ORDERS_THRESHOLD', 2);           // Ngưỡng số đơn hàng cho nhóm Chiến lược

    define('AGENT_ORDERS_THRESHOLD', 5);               // Ngưỡng số đơn hàng cho nhóm Đại lý

    define('LOYAL_VALUE_THRESHOLD', 100000000);        // Ngưỡng giá trị cho nhóm Thân thiết (100 triệu)
    define('LOYAL_ORDERS_THRESHOLD', 2);               // Ngưỡng số đơn hàng cho nhóm Thân thiết

    // Bắt đầu phân loại từng công ty
    foreach ($companiesData as $company) {
        $totalValue = (float)$company['TongGiaTri'];
        $orderCount = (int)$company['SoLuongDonHang'];
        $company['FormattedTongGiaTri'] = number_format($totalValue, 0, ',', '.');
        
        // Quy tắc phân nhóm (ưu tiên từ cao xuống thấp)
        // Một công ty sẽ được xếp vào nhóm cao nhất mà nó thỏa mãn điều kiện
        if ($totalValue >= STRATEGIC_VALUE_THRESHOLD && $orderCount >= STRATEGIC_ORDERS_THRESHOLD) {
            $groups['Chiến lược'][] = $company;
        } elseif ($orderCount >= AGENT_ORDERS_THRESHOLD) {
            $groups['Đại Lý'][] = $company;
        } elseif ($totalValue >= LOYAL_VALUE_THRESHOLD && $orderCount >= LOYAL_ORDERS_THRESHOLD) {
            $groups['Thân Thiết'][] = $company;
        } else {
            // Bất kỳ công ty nào đã có đơn hàng "Chốt" nhưng không thuộc các nhóm trên
            // đều được coi là "Tiềm năng"
            $groups['Tiềm năng'][] = $company;
        }
    }

    // Trả về kết quả thành công dưới dạng JSON
    echo json_encode(['success' => true, 'data' => $groups]);

} catch (PDOException $e) {
    // Xử lý lỗi kết nối hoặc truy vấn CSDL
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
?>
