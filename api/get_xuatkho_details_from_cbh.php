<?php
/**
 * File: api/get_xuatkho_details_from_cbh.php
 * Version: 3.0 - Revised by Đối tác lập trình
 * Description: API để lấy chi tiết phiếu xuất kho từ phiếu chuẩn bị hàng.
 * - [FIX] Sửa đổi cấu trúc dữ liệu trả về để tương thích với frontend,
 * đảm bảo các trường thông tin hiển thị chính xác khi tạo phiếu mới.
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php'; 

$response = ['success' => false, 'header' => null, 'items' => [], 'message' => ''];
$cbh_id = $_GET['cbh_id'] ?? 0;

if (!$cbh_id) {
    http_response_code(400);
    $response['message'] = 'Không có ID Phiếu Chuẩn Bị Hàng.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin header gốc từ các bảng liên quan
    $sql_header = "
        SELECT 
            cbh.TenCongTy, 
            bg.DiaChiKhach AS DiaChiCongTy,
            cbh.DiaDiemGiaoHang, 
            cbh.NguoiNhanHang AS NguoiNhan, 
            dh.SoYCSX
        FROM chuanbihang cbh
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        WHERE cbh.CBH_ID = :cbh_id
    ";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':cbh_id' => $cbh_id]);
    $header_data = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header_data) {
        throw new Exception("Không tìm thấy thông tin cho CBH ID: " . $cbh_id);
    }

    // 2. Chuyển đổi dữ liệu sang định dạng 'HienThi' mà frontend mong đợi
    // Điều này làm cho API này đồng bộ với API get_issued_slip_details.php
    $response['header'] = [
        'TenCongTyHienThi'      => $header_data['TenCongTy'],
        'DiaChiCongTyHienThi'   => $header_data['DiaChiCongTy'],
        'NguoiNhanHienThi'      => $header_data['NguoiNhan'],
        'DiaChiGiaoHangHienThi' => $header_data['DiaDiemGiaoHang'],
        'LyDoXuatKhoHienThi'    => 'Xuất kho theo YCSX số: ' . ($header_data['SoYCSX'] ?? '...'),
        'SoPhieuXuat'           => null, // Sẽ được tạo khi lưu
        'NgayXuat'              => date('Y-m-d'), // Mặc định là ngày hiện tại
        'NguoiLapPhieuHienThi'  => null, // Sẽ được điền bởi JS từ thông tin người dùng đăng nhập
        'ThuKho'                => null,
        'NguoiGiaoHang'         => null,
        'NguoiNhanHang'         => null
    ];


    // 3. Lấy danh sách sản phẩm và nhóm lại
    $item_groups = [];
    $sql_items = "
        SELECT 
            ct.TenNhom,
            ct.SanPhamID as variant_id, 
            ct.SoLuong AS SoLuongThucXuat,
            ct.DongGoi AS TaiSo,
            ct.GhiChu,
            v.variant_sku AS MaHang,
            v.variant_name AS TenSanPham
        FROM chitietchuanbihang ct
        JOIN variants v ON ct.SanPhamID = v.variant_id
        WHERE ct.CBH_ID = :cbh_id
        ORDER BY ct.ThuTuHienThi
    ";
    
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':cbh_id' => $cbh_id]);
    $items_result = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items_result as $item) {
        $group_name = $item['TenNhom'] ?: 'Sản phẩm khác';
        if (!isset($item_groups[$group_name])) {
            $item_groups[$group_name] = ['items' => [], 'ghiChu' => '(+/-)5%'];
        }
        $item_groups[$group_name]['items'][] = $item;
    }

    // 4. Lấy danh sách vật tư kèm theo (ECU)
    $sql_ecu = "
        SELECT TenSanPhamEcu, SoLuongEcu, DongGoiEcu, GhiChuEcu 
        FROM chitiet_ecu_cbh 
        WHERE CBH_ID = :cbh_id AND SoLuongEcu > 0
    ";
    $stmt_ecu = $pdo->prepare($sql_ecu);
    $stmt_ecu->execute([':cbh_id' => $cbh_id]);
    $ecu_items = $stmt_ecu->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($ecu_items)) {
        $ecu_group_name = 'Ecu cho Cùm Ula';
        if (!isset($item_groups[$ecu_group_name])) {
            $item_groups[$ecu_group_name] = ['items' => [], 'ghiChu' => ''];
        }

        foreach ($ecu_items as $ecu) {
            $item_groups[$ecu_group_name]['items'][] = [
                'variant_id' => null,
                'MaHang' => 'VT-ECU',
                'TenSanPham' => $ecu['TenSanPhamEcu'],
                'SoLuongThucXuat' => $ecu['SoLuongEcu'],
                'TaiSo' => $ecu['DongGoiEcu'],
                'GhiChu' => $ecu['GhiChuEcu']
            ];
        }
    }

    $response['success'] = true;
    $response['items'] = $item_groups;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi kết nối CSDL: ' . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi Server: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
