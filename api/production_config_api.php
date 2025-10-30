<?php
header('Content-Type: application/json');

// Thay thế bằng đường dẫn thực tế đến file cấu hình của bạn
require_once '../config/db_config.php'; 

$response = ['success' => false, 'message' => 'Hành động không hợp lệ.'];
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    $pdo = get_db_connection();

    switch ($action) {
        // Lấy tất cả các cấu hình
        case 'get_all_configs':
            $stmt = $pdo->query("SELECT ID, TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat ORDER BY ID");
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $configs];
            break;

        // Cập nhật nhiều cấu hình cùng lúc
        case 'update_configs':
            if (empty($input['configs']) || !is_array($input['configs'])) {
                $response['message'] = 'Dữ liệu cấu hình không hợp lệ.';
                break;
            }

            $pdo->beginTransaction();

            $sql = "UPDATE cauhinh_sanxuat SET GiaTriThietLap = :GiaTriThietLap WHERE TenThietLap = :TenThietLap";
            $stmt = $pdo->prepare($sql);

            foreach ($input['configs'] as $config) {
                if (isset($config['TenThietLap']) && isset($config['GiaTriThietLap'])) {
                    $stmt->execute([
                        ':GiaTriThietLap' => $config['GiaTriThietLap'],
                        ':TenThietLap' => $config['TenThietLap']
                    ]);
                }
            }

            $pdo->commit();
            $response = ['success' => true, 'message' => 'Cập nhật cấu hình thành công.'];
            break;
        
        default:
             $response['message'] = "Hành động '$action' không được hỗ trợ.";
             break;
    }
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Ghi log lỗi thay vì hiển thị chi tiết cho người dùng
    error_log('Lỗi CSDL: ' . $e->getMessage());
    $response['message'] = 'Đã có lỗi xảy ra với cơ sở dữ liệu. Vui lòng thử lại sau.';
}

echo json_encode($response);
?>
