<?php
/**
 * File: api/update_production_status.php
 * Version: 9.2 - Sync 'Cho duyet' status for BTP
 * Description: LSX Hoàn thành → "Đã SX xong" → Sau khi yêu cầu → "Chờ nhập". Cập nhật TrangThaiPUR khi LSX BTP được duyệt.
 * - [CẬP NHẬT V9.2] Thêm cập nhật cột TrangThaiPUR thành 'Chờ duyệt' khi LSX BTP chuyển sang 'Chờ duyệt'.
 * - [CẬP NHẬT V9.1] Thêm cập nhật cột TrangThaiPUR trong chuanbihang khi LSX BTP chuyển sang 'Đã duyệt (đang sx)'.
 * - [CRITICAL FIX V9.0] Thêm bước "Đã SX xong" trước khi nhập kho
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

/**
 * Lấy thông tin cơ bản của Lệnh Sản Xuất.
 *
 * @param PDO $pdo Đối tượng kết nối PDO.
 * @param integer $lenhSX_ID ID của Lệnh Sản Xuất.
 * @return array|null Mảng thông tin LSX hoặc null nếu không tìm thấy.
 */
function getLenhSXInfo(PDO $pdo, int $lenhSX_ID): ?array {
    $stmt = $pdo->prepare("
        SELECT lsx.LoaiLSX, lsx.CBH_ID, lsx.TrangThai as TrangThaiHienTai
        FROM lenh_san_xuat lsx
        WHERE lsx.LenhSX_ID = ?
    ");
    $stmt->execute([$lenhSX_ID]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Đồng bộ trạng thái của Phiếu Chuẩn Bị Hàng dựa trên thay đổi trạng thái LSX.
 *
 * @param PDO $pdo Đối tượng kết nối PDO.
 * @param integer $cbh_id ID của Phiếu Chuẩn Bị Hàng.
 * @param string $loaiLSX Loại Lệnh Sản Xuất ('BTP' hoặc 'ULA').
 * @param string $newStatus Trạng thái mới của LSX.
 * @return void
 */
function syncCBHStatus(PDO $pdo, int $cbh_id, string $loaiLSX, string $newStatus): void {
    if (!$cbh_id) return;

    $updateData = [];
    
    if ($loaiLSX === 'BTP') {
        switch ($newStatus) {
            // THÊM CASE MỚI NÀY
            case 'Chờ duyệt':
                $updateData['TrangThaiPUR'] = 'Chờ duyệt'; // Cập nhật trạng thái cụ thể của sản phẩm PUR
                break;
            // KẾT THÚC CASE MỚI

            case 'Đã duyệt (đang sx)':
                $updateData['TrangThai'] = 'Đang SX BTP'; // Cập nhật trạng thái chung của phiếu CBH
                // Thêm dòng này để cập nhật TrangThaiPUR
                $updateData['TrangThaiPUR'] = 'Đang SX'; // Cập nhật trạng thái cụ thể của sản phẩm PUR
                break;
            case 'Hoàn thành':
                // ⭐ THAY ĐỔI V9.0: Chỉ đánh dấu là "Đã SX xong", chưa cho nhập
                $updateData['TrangThai'] = 'Đã SX xong'; // Cập nhật trạng thái chung của phiếu CBH
                // Cập nhật TrangThaiPUR khi hoàn thành (nếu cần)
                // $updateData['TrangThaiPUR'] = 'Đã SX xong'; // Tùy chọn, có thể bỏ nếu không muốn cập nhật ở bước này
                break;
            // Có thể thêm case 'Hủy' nếu cần cập nhật trạng thái CBH khi LSX bị hủy
            // case 'Hủy':
            //     $updateData['TrangThaiPUR'] = 'Chờ sản xuất'; // Hoặc trạng thái phù hợp khác
            //     break;
        }
    } elseif ($loaiLSX === 'ULA') {
        switch ($newStatus) {
            case 'Chờ duyệt':
                $updateData['TrangThaiULA'] = 'Chờ duyệt'; // Cập nhật trạng thái cụ thể của sản phẩm ULA
                break;
            case 'Đã duyệt (đang sx)':
                $updateData['TrangThaiULA'] = 'Đang SX ULA'; // Cập nhật trạng thái cụ thể của sản phẩm ULA
                break;
            case 'Hoàn thành':
                // ⭐ THAY ĐỔI V9.0: Chỉ đánh dấu là "Đã SX xong ULA"
                $updateData['TrangThaiULA'] = 'Đã SX xong ULA'; // Cập nhật trạng thái cụ thể của sản phẩm ULA
                break;
            // Có thể thêm case 'Hủy'
            // case 'Hủy':
            //     $updateData['TrangThaiULA'] = 'Cần nhập'; // Hoặc trạng thái phù hợp khác
            //     break;
        }
    }
    
    // Nếu không có gì để cập nhật, thoát hàm
    if (empty($updateData)) return;
    
    // Xây dựng câu lệnh UPDATE động
    $setClauses = [];
    $params = [];
    foreach ($updateData as $col => $val) {
        $setClauses[] = "$col = ?";
        $params[] = $val;
    }
    $params[] = $cbh_id; // Thêm CBH_ID vào cuối mảng tham số cho WHERE
    
    $sql = "UPDATE chuanbihang SET " . implode(', ', $setClauses) . " WHERE CBH_ID = ?";
    $stmt = $pdo->prepare($sql);
    
    // Thực thi câu lệnh UPDATE
    try {
        $stmt->execute($params);
        // Log hoặc kiểm tra số dòng bị ảnh hưởng nếu cần
        // $affectedRows = $stmt->rowCount();
        // error_log("Synced CBH #{$cbh_id} for LSX type {$loaiLSX}. Affected rows: {$affectedRows}");
    } catch (PDOException $e) {
        // Ghi log lỗi nếu không cập nhật được CBH, nhưng không dừng script chính
        error_log("Error syncing CBH status for CBH_ID {$cbh_id}: " . $e->getMessage());
        // Có thể throw lại lỗi nếu muốn dừng hẳn quá trình: throw $e;
    }
}

try {
    // Lấy kết nối CSDL
    $pdo = get_db_connection();
    
    // Đọc dữ liệu JSON gửi đến
    $input = json_decode(file_get_contents('php://input'), true);

    // Lấy và kiểm tra ID Lệnh Sản Xuất
    $lenhSX_ID = isset($input['lenhSX_ID']) ? intval($input['lenhSX_ID']) : 0;
    // Lấy và kiểm tra trạng thái mới
    $newStatus = isset($input['status']) ? trim($input['status']) : '';

    // Kiểm tra dữ liệu đầu vào cơ bản
    if ($lenhSX_ID === 0 || empty($newStatus)) {
        throw new InvalidArgumentException('Dữ liệu không hợp lệ. Vui lòng cung cấp lenhSX_ID và status.');
    }

    // Danh sách các trạng thái hợp lệ có thể cập nhật qua API này
    $validStatuses = ['Chờ duyệt', 'Đã duyệt (đang sx)', 'Hoàn thành', 'Hủy'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new InvalidArgumentException("Trạng thái '$newStatus' không hợp lệ. Các trạng thái được chấp nhận: " . implode(', ', $validStatuses));
    }

    // Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
    $pdo->beginTransaction();

    // Lấy thông tin LSX hiện tại từ CSDL
    $lsxInfo = getLenhSXInfo($pdo, $lenhSX_ID);
    if (!$lsxInfo) {
        throw new Exception("Không tìm thấy Lệnh Sản Xuất với ID #{$lenhSX_ID}.");
    }
    
    // Lấy thông tin cần thiết từ LSX info
    $loaiLSX = $lsxInfo['LoaiLSX'];
    $cbh_id = $lsxInfo['CBH_ID'];
    $oldStatus = $lsxInfo['TrangThaiHienTai'];

    // Nếu trạng thái mới giống trạng thái cũ, không cần cập nhật
    if ($newStatus === $oldStatus) {
        $pdo->commit(); // Vẫn commit để kết thúc transaction
        echo json_encode([
            'success' => true, 
            'message' => "LSX #{$lenhSX_ID} đã ở trạng thái '$newStatus'. Không có gì thay đổi.",
            'cbh_id' => $cbh_id
        ]);
        exit; // Kết thúc script
    }

    // Chuẩn bị câu lệnh SQL để cập nhật bảng lenh_san_xuat
    $sql_update_lsx = "UPDATE lenh_san_xuat SET TrangThai = ?, NgayCapNhat = NOW()";
    // Nếu trạng thái mới là 'Hoàn thành', cập nhật thêm ngày hoàn thành thực tế
    if ($newStatus === 'Hoàn thành') {
        // Chỉ cập nhật NgayHoanThanhThucTe nếu nó đang là NULL
        $sql_update_lsx .= ", NgayHoanThanhThucTe = COALESCE(NgayHoanThanhThucTe, CURDATE())"; 
    }
    $sql_update_lsx .= " WHERE LenhSX_ID = ?";
    
    // Thực thi cập nhật bảng lenh_san_xuat
    $stmt = $pdo->prepare($sql_update_lsx);
    $stmt->execute([$newStatus, $lenhSX_ID]);

    // Gọi hàm để đồng bộ trạng thái sang bảng chuanbihang
    syncCBHStatus($pdo, $cbh_id, $loaiLSX, $newStatus);
    
    // Commit Transaction nếu mọi thứ thành công
    $pdo->commit();
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true, 
        'message' => "✅ Cập nhật thành công LSX #{$lenhSX_ID} ({$loaiLSX}): '$oldStatus' → '$newStatus'",
        'cbh_id' => $cbh_id // Trả về CBH_ID để client có thể cập nhật nếu cần
    ]);

} catch (InvalidArgumentException $e) {
    // Lỗi dữ liệu đầu vào không hợp lệ
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Lỗi dữ liệu đầu vào: ' . $e->getMessage()]);
} catch (PDOException $e) {
    // Lỗi CSDL
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500); // Internal Server Error
    error_log("Database error in update_production_status.php: " . $e->getMessage()); // Ghi log lỗi
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.']);
} catch (Exception $e) {
    // Các lỗi khác
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500); // Internal Server Error
    error_log("General error in update_production_status.php: " . $e->getMessage()); // Ghi log lỗi
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>

