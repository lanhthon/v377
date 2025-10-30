<?php
// File: api/get_items_for_tp_receipt.php
// Version: 1.0
// Chức năng: Lấy danh sách sản phẩm và số lượng đề xuất để tạo phiếu nhập kho thành phẩm.
// - Đối với PUR: Số lượng = Số Cây Đã Cắt * Định Mức Cắt (Số bộ trên cây).
// - Đối với ULA: Số lượng = Số Lượng Cần Sản Xuất.

require_once '../config/db_config.php';
header('Content-Type: application/json; charset=utf-8');

$cbh_id = $_GET['cbh_id'] ?? 0;
$type = $_GET['type'] ?? ''; // 'pur' hoặc 'ula'

if (empty($cbh_id) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cbh_id hoặc loại phiếu.']);
    exit;
}

try {
    $pdo = get_db_connection();

    // Lấy thông tin chung
    $stmt_info = $pdo->prepare("SELECT dh.SoYCSX, cbh.YCSX_ID FROM chuanbihang cbh JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID WHERE cbh.CBH_ID = ?");
    $stmt_info->execute([$cbh_id]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    if (!$info) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng.");
    }

    $items = [];
    $like_pattern = ($type === 'pur') ? 'PUR%' : (($type === 'ula') ? 'ULA%' : 'NON_EXISTING%');

    // Lấy danh sách sản phẩm cần nhập từ chi tiết phiếu CBH
    $sql_items = "
        SELECT 
            ct.ChiTietCBH_ID,
            ct.SanPhamID,
            ct.MaHang,
            v.variant_name AS TenSanPham,
            ct.SoLuongCanSX,
            ct.SoCayPhaiCat,
            ct.BanRong,
            ct.ID_ThongSo
        FROM chitietchuanbihang ct
        JOIN variants v ON ct.SanPhamID = v.variant_id
        WHERE ct.CBH_ID = ? AND ct.MaHang LIKE ? AND ct.SoLuongCanSX > 0
    ";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$cbh_id, $like_pattern]);
    $result_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Chuẩn bị câu lệnh lấy định mức cắt
    $stmt_dinh_muc = $pdo->prepare("
        SELECT SoBoTrenCay FROM dinh_muc_cat 
        WHERE ? >= MinDN AND ? <= MaxDN AND BanRong = ? AND HinhDang = ?
        LIMIT 1
    ");

    foreach ($result_items as $item) {
        $soLuongNhap = 0;
        if ($type === 'pur' && !empty($item['SoCayPhaiCat']) && (int)$item['SoCayPhaiCat'] > 0) {
            // Tính số lượng nhập cho PUR
            $dn_value = (int)filter_var($item['ID_ThongSo'], FILTER_SANITIZE_NUMBER_INT);
            $ban_rong = (int)$item['BanRong'];
            $hinh_dang = (strpos($item['MaHang'], 'PUR-S') === 0) ? 'Vuông' : 'Tròn';
            
            $stmt_dinh_muc->execute([$dn_value, $dn_value, $ban_rong, $hinh_dang]);
            $soBoTrenCay = (int)$stmt_dinh_muc->fetchColumn();
            
            if ($soBoTrenCay > 0) {
                $soLuongNhap = (int)$item['SoCayPhaiCat'] * $soBoTrenCay;
            } else {
                // Nếu không tìm thấy định mức, tạm thời lấy SoLuongCanSX
                $soLuongNhap = (int)$item['SoLuongCanSX'];
            }
        } else {
            // Đối với ULA hoặc PUR không qua cắt, lấy SoLuongCanSX
            $soLuongNhap = (int)$item['SoLuongCanSX'];
        }

        $items[] = [
            'ChiTietCBH_ID' => $item['ChiTietCBH_ID'],
            'SanPhamID' => $item['SanPhamID'],
            'MaHang' => $item['MaHang'],
            'TenSanPham' => $item['TenSanPham'],
            'SoLuongTheoDonHang' => (int)$item['SoLuongCanSX'], // Số lượng gốc cần SX
            'SoLuongNhap' => $soLuongNhap, // Số lượng đề xuất nhập
            'GhiChu' => ''
        ];
    }
    
    $data = [
        'info' => $info,
        'items' => $items
    ];

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
