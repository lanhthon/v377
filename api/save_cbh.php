<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cbhID'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID Phiếu Chuẩn Bị Hàng.']);
    exit;
}

$cbhId = $data['cbhID'];
$donHangID = $data['thongTinChung']['maDon']; 

$conn->begin_transaction();

try {
    // 1. Cập nhật thông tin chung của phiếu và trạng thái
    $info = $data['thongTinChung'];
    $stmt_info = $conn->prepare("
        UPDATE chuanbihang SET
            BoPhan = ?, NgayGuiYCSX = ?, PhuTrach = ?, NgayGiao = ?, NguoiNhanHang = ?,
            DangKiCongTruong = ?, SoDon = ?, DiaDiemGiaoHang = ?, QuyCachThung = ?,
            XeGrap = ?, XeTai = ?, SoLaiXe = ?, TrangThai = 'Đã cập nhật'
        WHERE CBH_ID = ?
    ");
    $ngayGui = !empty($info['ngayGui']) ? $info['ngayGui'] : null;
    $ngayGiao = !empty($info['ngayGiao']) ? $info['ngayGiao'] : null;
    $stmt_info->bind_param("ssssssssssssi",
        $info['boPhan'], $ngayGui, $info['phuTrach'], $ngayGiao, $info['nguoiNhan'],
        $info['congTrinh'], $info['soDon'], $info['diaDiem'], $info['quyCachThung'],
        $info['xeGrap'], $info['xeTai'], $info['soLaiXe'], $cbhId
    );
    $stmt_info->execute();
    $stmt_info->close();

    // 2. Chuẩn bị các câu lệnh
    $stmt_update_item = $conn->prepare("UPDATE chitietchuanbihang SET TonKho = ?, DaGan = ?, SoThung = ?, DongGoi = ?, DatThem = ?, SoKg = ?, GhiChu = ?, SoLuongLayTuKho = ?, SoLuongCanSX = ?, SoCayPhaiCat = ? WHERE ChiTietCBH_ID = ?");
    $stmt_phanbo = $conn->prepare("INSERT INTO donhang_phanbo_tonkho (DonHangID, SanPhamID, SoLuongPhanBo) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE SoLuongPhanBo = VALUES(SoLuongPhanBo)");
    
    $banThanhPhamList = []; // Mảng để tổng hợp lại BTP

    // 3. Lấy dữ liệu gốc từ DB để tính toán lại
    $stmt_get_items = $conn->prepare("SELECT * FROM chitietchuanbihang WHERE CBH_ID = ?");
    $stmt_get_items->bind_param("i", $cbhId);
    $stmt_get_items->execute();
    $items_in_db = $stmt_get_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_get_items->close();
    
    $userItems = $data['items'] ?? [];

    // 4. Lặp qua từng sản phẩm chính (PUR, ULA) để cập nhật và tính toán
    foreach ($items_in_db as $item) {
        $sanPhamID = $item['SanPhamID'];

        $stmt_inv = $conn->prepare("SELECT vi.quantity, p.HinhDang, p.base_sku, ao_br.value as BanRong, ao_dk.value as DuongKinhTrong FROM variants v LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id LEFT JOIN products p ON v.product_id = p.product_id LEFT JOIN variant_attributes va_br ON v.variant_id = va_br.variant_id JOIN attribute_options ao_br ON va_br.option_id = ao_br.option_id AND ao_br.attribute_id = 2 LEFT JOIN variant_attributes va_dk ON v.variant_id = va_dk.variant_id JOIN attribute_options ao_dk ON va_dk.option_id = ao_dk.option_id AND ao_dk.attribute_id = 7 WHERE v.variant_id = ? GROUP BY v.variant_id");
        $stmt_inv->bind_param("i", $sanPhamID);
        $stmt_inv->execute();
        $prodInfo = $stmt_inv->get_result()->fetch_assoc();
        $tonKhoVatLy = (int)($prodInfo['quantity'] ?? 0);
        $stmt_inv->close();

        $stmt_allocated = $conn->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) AS DaGan FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND DonHangID != ?");
        $stmt_allocated->bind_param("ii", $sanPhamID, $donHangID);
        $stmt_allocated->execute();
        $daGan = (int)$stmt_allocated->get_result()->fetch_assoc()['DaGan'];
        $stmt_allocated->close();

        $tonKhoKhaDung = max(0, $tonKhoVatLy - $daGan);
        $soLuongYeuCau = (int)$item['SoLuong'];
        $soLuongLayTuKho = min($soLuongYeuCau, $tonKhoKhaDung);
        $soLuongCanThem = max(0, $soLuongYeuCau - $soLuongLayTuKho);
        $soCayPhaiCat = null;
        
        if (str_starts_with($prodInfo['base_sku'] ?? '', 'PUR-') && $soLuongCanThem > 0) {
            if (!empty($prodInfo['HinhDang']) && is_numeric($prodInfo['DuongKinhTrong']) && is_numeric($prodInfo['BanRong'])) {
                $stmt_dinhmuc = $conn->prepare("SELECT SoBoTrenCay FROM dinh_muc_cat WHERE HinhDang = ? AND ? BETWEEN MinDN AND MaxDN AND BanRong = ? LIMIT 1");
                $hinhDang = $prodInfo['HinhDang'];
                $duongKinhTrong = $prodInfo['DuongKinhTrong'];
                $banRong = $prodInfo['BanRong'];
                $stmt_dinhmuc->bind_param("sii", $hinhDang, $duongKinhTrong, $banRong);
                $stmt_dinhmuc->execute();
                if ($dinhMuc = $stmt_dinhmuc->get_result()->fetch_assoc()) {
                    $soCayPhaiCat = ceil($soLuongCanThem / (int)$dinhMuc['SoBoTrenCay']);

                    $stmt_btp_find = $conn->prepare("SELECT v_btp.variant_sku AS MaBTP, v_btp.variant_name AS TenBTP, vi_btp.quantity AS TonKhoBTP, u_btp.name AS DonViTinhBTP FROM variants v_btp JOIN products p_btp ON v_btp.product_id = p_btp.product_id LEFT JOIN variant_inventory vi_btp ON v_btp.variant_id = vi_btp.variant_id LEFT JOIN units u_btp ON p_btp.base_unit_id = u_btp.unit_id JOIN variant_attributes va_dd ON v_btp.variant_id = va_dd.variant_id JOIN attribute_options ao_dd ON va_dd.option_id = ao_dd.option_id AND ao_dd.attribute_id = 1 JOIN variant_attributes va_dk ON v_btp.variant_id = va_dk.variant_id JOIN attribute_options ao_dk ON va_dk.option_id = ao_dk.option_id AND ao_dk.attribute_id = 7 WHERE (p_btp.base_sku = 'CV' OR p_btp.base_sku = 'CT') AND p_btp.HinhDang = ? AND ao_dd.value = ? AND ao_dk.value = ? LIMIT 1");
                    $stmt_btp_find->bind_param("sss", $hinhDang, $item['DoDay'], $duongKinhTrong);
                    $stmt_btp_find->execute();
                     if ($btpItem = $stmt_btp_find->get_result()->fetch_assoc()) {
                        $maBTP = $btpItem['MaBTP'];
                        if (!isset($banThanhPhamList[$maBTP])) {
                            $banThanhPhamList[$maBTP] = ['MaBTP' => $maBTP, 'TenBTP' => $btpItem['TenBTP'], 'SoLuongCan' => 0, 'TonKhoSnapshot' => (int)$btpItem['TonKhoBTP'], 'DonViTinh' => $btpItem['DonViTinhBTP']];
                        }
                        $banThanhPhamList[$maBTP]['SoLuongCan'] += $soCayPhaiCat;
                    }
                    $stmt_btp_find->close();
                }
                $stmt_dinhmuc->close();
            }
        }

        $filteredItems = array_filter($userItems, fn($i) => ($i['chiTietCBH_ID'] ?? null) == $item['ChiTietCBH_ID']);
        $itemDataFromUser = array_values($filteredItems)[0] ?? [];
        
        $soThung = $itemDataFromUser['soThung'] ?? $item['SoThung'];
        $dongGoi = $itemDataFromUser['dongGoi'] ?? $item['DongGoi'];
        $datThem = $itemDataFromUser['datThem'] ?? $item['DatThem'];
        $soKg = $itemDataFromUser['soKg'] ?? $item['SoKg'];
        $ghiChu = $itemDataFromUser['ghiChu'] ?? $item['GhiChu'];
        $chiTietCBH_ID = $item['ChiTietCBH_ID'];

        $stmt_update_item->bind_param("iisssdsiisi", $tonKhoVatLy, $daGan, $soThung, $dongGoi, $datThem, $soKg, $ghiChu, $soLuongLayTuKho, $soLuongCanThem, $soCayPhaiCat, $chiTietCBH_ID);
        $stmt_update_item->execute();

        $stmt_phanbo->bind_param("iii", $donHangID, $sanPhamID, $soLuongLayTuKho);
        $stmt_phanbo->execute();
    }
    $stmt_update_item->close();
    $stmt_phanbo->close();

    // 5. Lưu snapshot Bán Thành Phẩm
    $stmt_delete_btp = $conn->prepare("DELETE FROM chitiet_btp_cbh WHERE CBH_ID = ?");
    $stmt_delete_btp->bind_param("i", $cbhId);
    $stmt_delete_btp->execute();
    $stmt_delete_btp->close();

    if (!empty($banThanhPhamList)) {
        $stmt_insert_btp = $conn->prepare("INSERT INTO chitiet_btp_cbh (CBH_ID, MaBTP, TenBTP, SoLuongCan, TonKhoSnapshot, DaGanSnapshot, DonViTinh) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($banThanhPhamList as $btp) {
            // Lấy lại DaGan cho BTP
            $stmt_btp_allocated = $conn->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) AS DaGan FROM donhang_phanbo_tonkho dptk JOIN variants v ON dptk.SanPhamID = v.variant_id WHERE v.variant_sku = ? AND dptk.DonHangID != ?");
            $stmt_btp_allocated->bind_param("si", $btp['MaBTP'], $donHangID);
            $stmt_btp_allocated->execute();
            $daGanBTP = (int)$stmt_btp_allocated->get_result()->fetch_assoc()['DaGan'];
            $stmt_btp_allocated->close();

            $stmt_insert_btp->bind_param("issdiis", $cbhId, $btp['MaBTP'], $btp['TenBTP'], $btp['SoLuongCan'], $btp['TonKhoSnapshot'], $daGanBTP, $btp['DonViTinh']);
            $stmt_insert_btp->execute();
        }
        $stmt_insert_btp->close();
    }
    
    // 6. Lưu Vật Tư Đi Kèm (ECU)
    $stmt_delete_ecu = $conn->prepare("DELETE FROM chitiet_ecu_cbh WHERE CBH_ID = ?");
    $stmt_delete_ecu->bind_param("i", $cbhId);
    $stmt_delete_ecu->execute();
    $stmt_delete_ecu->close();

    if (isset($data['itemsEcuKemTheo']) && is_array($data['itemsEcuKemTheo'])) {
        $stmt_insert_ecu = $conn->prepare("INSERT INTO chitiet_ecu_cbh (CBH_ID, TenSanPhamEcu, SoLuongEcu, TonKhoSnapshot, DaGanSnapshot, GhiChuEcu) VALUES (?, ?, ?, ?, ?, ?)");
        foreach($data['itemsEcuKemTheo'] as $ecu_item) {
            $ecu_sku = str_replace('Đai ốc ', '', $ecu_item['tenSanPhamEcu']);
            $stmt_ecu_info = $conn->prepare("SELECT v.variant_id, vi.quantity, COALESCE(dptk.DaGan, 0) as DaGan FROM variants v LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id LEFT JOIN (SELECT SanPhamID, SUM(SoLuongPhanBo) as DaGan FROM donhang_phanbo_tonkho WHERE DonHangID != ? GROUP BY SanPhamID) dptk ON v.variant_id = dptk.SanPhamID WHERE v.variant_sku = ?");
            $stmt_ecu_info->bind_param("is", $donHangID, $ecu_sku);
            $stmt_ecu_info->execute();
            $ecuInfoDb = $stmt_ecu_info->get_result()->fetch_assoc();
            $stmt_ecu_info->close();

            $tonKhoEcu = (int)($ecuInfoDb['quantity'] ?? 0);
            $daGanEcu = (int)($ecuInfoDb['DaGan'] ?? 0);

            $stmt_insert_ecu->bind_param("isiiss", $cbhId, $ecu_item['tenSanPhamEcu'], $ecu_item['soLuongCan'], $tonKhoEcu, $daGanEcu, $ecu_item['ghiChuEcu']);
            $stmt_insert_ecu->execute();
        }
        $stmt_insert_ecu->close();
    }

    // 7. Hoàn tất giao dịch
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Lưu toàn bộ dữ liệu phiếu thành công!']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi khi lưu phiếu CBH: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>