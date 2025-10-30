<?php
// File: api/nhapkho.php
// Đây là API trung tâm, xử lý việc LƯU và LẤY CHI TIẾT một phiếu nhập kho.
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';
session_start();

$response = ['success' => false, 'message' => 'Invalid action'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_nhapkho') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->begin_transaction();
    try {
        // Logic để TẠO MỚI hoặc CẬP NHẬT phiếu nhập kho
        if (!empty($data['PhieuNhapKhoID'])) {
            // Cập nhật phiếu đã có (nếu cần)
            $stmt = $conn->prepare("UPDATE phieunhapkho SET NgayNhap=?, NhaCungCapID=?, NguoiGiaoHang=?, SoHoaDon=?, LyDoNhap=?, GhiChu=? WHERE PhieuNhapKhoID=?");
            $stmt->bind_param("sissssi", $data['NgayNhap'], $data['NhaCungCapID'], $data['NguoiGiaoHang'], $data['SoHoaDon'], $data['LyDoNhap'], $data['GhiChuPNK'], $data['PhieuNhapKhoID']);
            $stmt->execute();
            $pnk_id = $data['PhieuNhapKhoID'];
            // Xóa chi tiết cũ để thêm lại
            $conn->query("DELETE FROM chitietphieunhapkho WHERE PhieuNhapKhoID = $pnk_id");
        } else {
            // Tạo phiếu mới
            $stmt = $conn->prepare("INSERT INTO phieunhapkho (SoPhieuNhapKho, NgayNhap, NhaCungCapID, NguoiGiaoHang, SoHoaDon, LyDoNhap, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissss", $data['SoPhieuNhapKho'], $data['NgayNhap'], $data['NhaCungCapID'], $data['NguoiGiaoHang'], $data['SoHoaDon'], $data['LyDoNhap'], $data['GhiChuPNK']);
            $stmt->execute();
            $pnk_id = $conn->insert_id;
        }
        $stmt->close();

        // Thêm chi tiết, cập nhật tồn kho và ghi lịch sử
        $stmt_chitiet = $conn->prepare("INSERT INTO chitietphieunhapkho (PhieuNhapKhoID, SanPhamID, SoLuong, DonGiaNhap, ThanhTien, GhiChu) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_inventory = $conn->prepare("UPDATE variant_inventory SET quantity = quantity + ? WHERE variant_id = ?");
        $stmt_history = $conn->prepare("INSERT INTO lichsunhapxuat (SanPhamID, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu) VALUES (?, 'NHAP_KHO', ?, ?, ?, ?)");

        foreach ($data['items'] as $item) {
            $thanhTien = $item['SoLuong'] * $item['DonGiaNhap'];
            $stmt_chitiet->bind_param("iiidds", $pnk_id, $item['SanPhamID'], $item['SoLuong'], $item['DonGiaNhap'], $thanhTien, $item['GhiChu']);
            $stmt_chitiet->execute();

            // Cập nhật tồn kho
            $stmt_inventory->bind_param("ii", $item['SoLuong'], $item['SanPhamID']);
            $stmt_inventory->execute();

            // Ghi lịch sử
            $stmt_get_stock = $conn->prepare("SELECT quantity FROM variant_inventory WHERE variant_id = ?");
            $stmt_get_stock->bind_param("i", $item['SanPhamID']);
            $stmt_get_stock->execute();
            $ton_kho_sau = $stmt_get_stock->get_result()->fetch_assoc()['quantity'];
            $stmt_get_stock->close();
            
            $stmt_history->bind_param("iiiss", $item['SanPhamID'], $item['SoLuong'], $ton_kho_sau, $data['SoPhieuNhapKho'], $data['LyDoNhap']);
            $stmt_history->execute();
        }
        
        $conn->commit();
        $response = ['success' => true, 'message' => 'Lưu phiếu nhập kho thành công!', 'pnk_id' => $pnk_id];
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()];
        http_response_code(500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_nhapkho_details') {
    $pnk_id = $_GET['pnk_id'] ?? 0;
    if ($pnk_id) {
        try {
            $pnk_data = [];
            // Lấy thông tin phiếu nhập
            $stmt_pnk = $conn->prepare("SELECT p.*, n.TenNhaCungCap, u.HoTen as NguoiLapPhieu FROM phieunhapkho p LEFT JOIN nhacungcap n ON p.NhaCungCapID = n.NhaCungCapID LEFT JOIN nguoidung u ON p.NguoiTaoID = u.UserID WHERE p.PhieuNhapKhoID = ?");
            $stmt_pnk->bind_param("i", $pnk_id);
            $stmt_pnk->execute();
            $pnk_data['phieu_nhap_kho'] = $stmt_pnk->get_result()->fetch_assoc();
            $stmt_pnk->close();

            // Lấy chi tiết sản phẩm
            $stmt_items = $conn->prepare("
                SELECT c.*, v.variant_sku as maHang, v.variant_name as tenSanPham,
                       v_attr_id.value AS ID_ThongSo, v_attr_day.value AS DoDay, v_attr_rong.value AS BanRong
                FROM chitietphieunhapkho c
                JOIN variants v ON c.SanPhamID = v.variant_id
                LEFT JOIN variant_attributes va_id ON v.variant_id = va_id.variant_id AND va_id.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 1)
                LEFT JOIN attribute_options v_attr_id ON va_id.option_id = v_attr_id.option_id
                LEFT JOIN variant_attributes va_day ON v.variant_id = va_day.variant_id AND va_day.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 2)
                LEFT JOIN attribute_options v_attr_day ON va_day.option_id = v_attr_day.option_id
                LEFT JOIN variant_attributes va_rong ON v.variant_id = va_rong.variant_id AND va_rong.option_id IN (SELECT option_id FROM attribute_options WHERE attribute_id = 3)
                LEFT JOIN attribute_options v_attr_rong ON va_rong.option_id = v_attr_rong.option_id
                WHERE c.PhieuNhapKhoID = ?
            ");
            $stmt_items->bind_param("i", $pnk_id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            $items = [];
            while($row = $result_items->fetch_assoc()) {
                $items[] = $row;
            }
            $pnk_data['items'] = $items;
            $stmt_items->close();

            $response = ['success' => true, 'data' => $pnk_data];
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()];
        }
    } else {
        $response = ['success' => false, 'message' => 'Thiếu ID Phiếu nhập kho.'];
    }
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>