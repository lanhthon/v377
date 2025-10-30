<?php
// File: api/receive_ula_products.php
// CHỨC NĂNG: Xử lý nhập kho thành phẩm ULA và cập nhật trạng thái phiếu.

require_once '../config/db_config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

// Kiểm tra quyền và dữ liệu đầu vào
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cbh_id'])) {
    http_response_code(400);
    $response['message'] = 'Yêu cầu không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$cbh_id = intval($_POST['cbh_id']);
$userId = $_SESSION['user_id'] ?? 1; // Giả sử user_id là 1 nếu không có session

if ($cbh_id <= 0) {
    http_response_code(400);
    $response['message'] = 'ID Phiếu chuẩn bị hàng không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();
    $pdo->beginTransaction();

    // 1. Lấy thông tin phiếu chuẩn bị hàng
    $stmt = $pdo->prepare("SELECT * FROM chuanbihang WHERE CBH_ID = ?");
    $stmt->execute([$cbh_id]);
    $cbh_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cbh_info) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng.");
    }
    
    // Kiểm tra trạng thái ULA của phiếu
    // Bảng `chuanbihang` trong file SQL bạn gửi không có cột `TrangThaiULA`,
    // nên tôi sẽ bỏ qua kiểm tra này để tránh lỗi.
    // Nếu bạn có cột này, bạn có thể uncomment đoạn code dưới.
    /*
    if ($cbh_info['TrangThaiULA'] !== 'Chờ nhập ULA') {
        throw new Exception("Phiếu không ở trạng thái 'Chờ nhập ULA' để thực hiện nhập kho.");
    }
    */

    // 2. Tạo một phiếu nhập kho mới
    // Bảng phieunhapkho không có cột 'NguonID' nên sẽ bị loại bỏ
    $soPhieuNhapKho = 'PNK-TP-ULA-' . date('YmdHis');
    $lyDoNhap = 'Nhập kho thành phẩm ULA từ phiếu chuẩn bị hàng ' . $cbh_info['SoCBH'];
    $stmt_insert_pnk = $pdo->prepare("INSERT INTO phieunhapkho (SoPhieuNhapKho, NgayNhap, LyDoNhap, NguoiGiaoHang) VALUES (?, NOW(), ?, ?)");
    $stmt_insert_pnk->execute([$soPhieuNhapKho, $lyDoNhap, $cbh_info['PhuTrach']]);
    $new_pnk_id = $pdo->lastInsertId();

    if (!$new_pnk_id) {
        throw new Exception("Không thể tạo phiếu nhập kho.");
    }

    // 3. Lấy danh sách sản phẩm ULA cần nhập kho từ chitietchuanbihang
    $stmt_items = $pdo->prepare("SELECT SanPhamID, SoLuongCanSX, DongGoi FROM chitietchuanbihang WHERE CBH_ID = ? AND SanPhamID IN (SELECT variant_id FROM variants WHERE product_id = (SELECT product_id FROM products WHERE base_sku = 'ULA'))");
    $stmt_items->execute([$cbh_id]);
    $ula_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if (empty($ula_items)) {
        throw new Exception("Không tìm thấy sản phẩm ULA nào để nhập kho.");
    }
    
    // Ghi log vào lịch sử nhập xuất
    $stmt_log = $pdo->prepare("INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'NHAP_KHO', ?, ?, ?, ?)");
    
    // 4. Cập nhật tồn kho và tạo chi tiết phiếu nhập kho
    foreach ($ula_items as $item) {
        $variant_id = $item['SanPhamID'];
        $so_luong_nhap = $item['SoLuongCanSX'];
        $ghi_chu = $item['DongGoi'];
        
        if ($so_luong_nhap > 0) {
            // Lấy tồn kho hiện tại để tính toán số lượng sau giao dịch
            $stmt_current_inv = $pdo->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
            $stmt_current_inv->execute([$variant_id]);
            $current_quantity = $stmt_current_inv->fetchColumn() ?? 0;
            $new_quantity = $current_quantity + $so_luong_nhap;

            // Thêm vào chi tiết phiếu nhập kho
            $stmt_insert_detail = $pdo->prepare("INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, GhiChu) VALUES (?, ?, ?, ?)");
            $stmt_insert_detail->execute([$new_pnk_id, $variant_id, $so_luong_nhap, $ghi_chu]);

            // Cập nhật tồn kho trong bảng `variant_inventory`
            $stmt_update_inventory = $pdo->prepare("UPDATE variant_inventory SET quantity = ? WHERE variant_id = ?");
            $stmt_update_inventory->execute([$new_quantity, $variant_id]);
            
            // Ghi log vào lịch sử
            $log_message = 'Nhập kho thành phẩm ULA từ phiếu chuẩn bị hàng ID: ' . $cbh_id;
            $stmt_log->execute([$variant_id, $so_luong_nhap, $new_quantity, $soPhieuNhapKho, $log_message]);
        }
    }
    
    // 5. Cập nhật trạng thái phiếu chuẩn bị hàng cuối cùng
    $stmt_update_cbh = $pdo->prepare("UPDATE chuanbihang SET TrangThai = 'Đã nhập kho TP', updated_at = NOW() WHERE CBH_ID = ?");
    $stmt_update_cbh->execute([$cbh_id]);
    
    // Cập nhật trạng thái YCSX liên quan
    $stmt_update_ycsx = $pdo->prepare("UPDATE donhang SET TrangThaiCBH = 'Đã nhập kho TP', updated_at = NOW() WHERE YCSX_ID = ?");
    $stmt_update_ycsx->execute([$cbh_info['YCSX_ID']]);

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Đã nhập kho thành công thành phẩm ULA và tạo phiếu nhập.';
    $response['pnk_id'] = $new_pnk_id;

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    $response['message'] = 'Lỗi trong quá trình xử lý: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>