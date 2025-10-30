<?php
/**
 * File: api/export_pxk_pdf.php
 * Chức năng: Xuất Phiếu Xuất Kho ra file PDF, sử dụng logic fallback để hiển thị dữ liệu đã chỉnh sửa.
 * Version: 3.0
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

try {
    $pxk_id = isset($_GET['pxk_id']) ? intval($_GET['pxk_id']) : 0;
    if ($pxk_id === 0) {
        throw new Exception('ID Phiếu Xuất Kho không hợp lệ.');
    }

    $pdo = get_db_connection();

    // 1. Lấy dữ liệu header, bao gồm cả dữ liệu gốc và dữ liệu đã lưu
    $sql_header = "
        SELECT 
            pxk.SoPhieuXuat,
            pxk.NgayXuat,
            pxk.TenCongTy AS TenCongTyDaLuu,
            pxk.DiaChiCongTy AS DiaChiCongTyDaLuu,
            pxk.NguoiNhan AS NguoiNhanDaLuu,
            pxk.DiaChiGiaoHang AS DiaChiGiaoHangDaLuu,
            pxk.LyDoXuatKho AS LyDoXuatKhoDaLuu,
            pxk.NguoiLapPhieu,
            pxk.ThuKho,
            pxk.NguoiGiaoHang,
            pxk.NguoiNhanHang,
            bg.TenCongTy AS TenCongTyGoc,
            bg.DiaChiKhach AS DiaChiCongTyGoc,
            dh.NguoiNhan AS NguoiNhanGoc,
            bg.DiaChiGiaoHang AS DiaChiGiaoHangGoc,
            CONCAT('Theo đơn hàng số: ', IFNULL(dh.SoYCSX, '...')) AS LyDoXuatKhoGoc,
            nguoi_tao.HoTen AS TenNguoiLapGoc,
            dh.YCSX_ID
        FROM phieuxuatkho pxk
        LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID
        LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        LEFT JOIN nguoidung nguoi_tao ON pxk.NguoiTaoID = nguoi_tao.UserID
        WHERE pxk.PhieuXuatKhoID = :pxk_id";
    $stmt_header = $pdo->prepare($sql_header);
    $stmt_header->execute([':pxk_id' => $pxk_id]);
    $header = $stmt_header->fetch(PDO::FETCH_ASSOC);

    if (!$header) {
        throw new Exception("Không tìm thấy phiếu xuất kho với ID: " . $pxk_id);
    }

    // 2. Áp dụng logic fallback để ưu tiên dữ liệu đã chỉnh sửa
    $header['TenCongTyHienThi'] = !empty($header['TenCongTyDaLuu']) ? $header['TenCongTyDaLuu'] : $header['TenCongTyGoc'];
    $header['DiaChiCongTyHienThi'] = !empty($header['DiaChiCongTyDaLuu']) ? $header['DiaChiCongTyDaLuu'] : $header['DiaChiCongTyGoc'];
    $header['NguoiNhanHienThi'] = !empty($header['NguoiNhanDaLuu']) ? $header['NguoiNhanDaLuu'] : $header['NguoiNhanGoc'];
    $header['DiaChiGiaoHangHienThi'] = !empty($header['DiaChiGiaoHangDaLuu']) ? $header['DiaChiGiaoHangDaLuu'] : $header['DiaChiGiaoHangGoc'];
    $header['LyDoXuatKhoHienThi'] = !empty($header['LyDoXuatKhoDaLuu']) ? $header['LyDoXuatKhoDaLuu'] : $header['LyDoXuatKhoGoc'];
    $header['NguoiLapPhieuHienThi'] = !empty($header['NguoiLapPhieu']) ? $header['NguoiLapPhieu'] : $header['TenNguoiLapGoc'];

    // 3. Lấy chi tiết sản phẩm và tên nhóm
    $sql_items = "
        SELECT 
            ct.MaHang, ct.TenSanPham, ct.SoLuongThucXuat, ct.TaiSo, ct.GhiChu, ct.SanPhamID as variant_id
        FROM chitiet_phieuxuatkho ct
        WHERE ct.PhieuXuatKhoID = :pxk_id 
        ORDER BY ct.ChiTietPXK_ID";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':pxk_id' => $pxk_id]);
    $items_result = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $ycsx_id = $header['YCSX_ID'];
    if ($ycsx_id && count($items_result) > 0) {
        $product_ids = array_map(fn($item) => $item['variant_id'], $items_result);
        if(!empty($product_ids)){
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql_groups = "SELECT SanPhamID, TenNhom FROM chitiet_donhang WHERE DonHangID = ? AND SanPhamID IN ($placeholders)";
            $stmt_groups = $pdo->prepare($sql_groups);
            $params = array_merge([$ycsx_id], $product_ids);
            $stmt_groups->execute($params);
            $groups_map = $stmt_groups->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($items_result as &$item) {
                $item['TenNhom'] = $groups_map[$item['variant_id']] ?? null;
            }
            unset($item);
        }
    }

    // Tái cấu trúc dữ liệu theo nhóm
    $item_groups = [];
    foreach ($items_result as $item) {
        $group_name = ($item['MaHang'] === 'VT-ECU') ? 'Ecu cho Cùm Ula' : ($item['TenNhom'] ?? 'Sản phẩm khác');
        if (!isset($item_groups[$group_name])) {
            $item_groups[$group_name] = ['items' => []];
        }
        $item_groups[$group_name]['items'][] = $item;
    }
    
    // 4. Chuẩn bị dữ liệu và render HTML từ template
    ob_start();
    $data = ['header' => $header, 'items' => $item_groups];
    include __DIR__ . '/../templates/pxk_pdf_template.php';
    $html = ob_get_clean();

    // 5. Tạo file PDF
    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans']);
    $mpdf->WriteHTML($html);
    
    $fileName = "PXK_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $header['SoPhieuXuat']) . ".pdf";
    $mpdf->Output($fileName, 'I');

} catch (Throwable $e) {
    header("Content-Type: text/plain; charset=utf-8");
    error_log("Lỗi tạo PDF: " . $e->getMessage());
    die("Đã có lỗi xảy ra trong quá trình tạo file PDF. Vui lòng kiểm tra log của server. Lỗi: " . $e->getMessage());
}
?>
