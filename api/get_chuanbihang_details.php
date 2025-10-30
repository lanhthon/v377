<?php
/**
 * File: api/get_chuanbihang_details.php
 * Version: 15.5 (FIX MNN LOGIC - EXCLUDE ALL KNOWN SUFFIXES)
 * Description: API để lấy chi tiết phiếu chuẩn bị hàng.
 * - [CẬP NHẬT V15.5] Sửa logic tìm SP MĐP: Mở rộng điều kiện loại trừ hậu tố (-HDG, -MNN, -PVC) trong variant_sku để tìm sản phẩm gốc (base product) Mạ Điện Phân.
 * - [CẬP NHẬT V15.3] Sửa logic tìm SP MĐP: Bỏ check product_id, chỉ so khớp trên thuộc tính (ID Thông Số, Kích thước ren).
 * - [CẬP NHẬT V15.2] Sửa lỗi SQLSTATE[HY093] bằng cách dùng 2 tham số riêng biệt cho subquery.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';


$cbhId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$cbhId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID phiếu không hợp lệ.']);
    exit;
}

try {
    $pdo = get_db_connection();

    // 1. Lấy thông tin chung của phiếu, JOIN thêm kehoach_giaohang
    $sql_info = "
        SELECT cbh.*, dh.SoYCSX, dh.NgayGiaoDuKien, dh.DiaChiGiaoHang AS DiaChiGiaoHangBaoGia,
               dh.NguoiNhan AS NguoiNhanBaoGia, dh.TenDuAn, bg.NguoiBaoGia, dh.YCSX_ID as DonHangID,
               khgh.GhiChu AS GhiChuDonHang
        FROM chuanbihang cbh
        JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
        JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID
        LEFT JOIN kehoach_giaohang khgh ON cbh.KHGH_ID = khgh.KHGH_ID
        WHERE cbh.CBH_ID = :cbhId";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':cbhId' => $cbhId]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception('Không tìm thấy phiếu chuẩn bị hàng.');
    }

    // 2. Lấy chi tiết sản phẩm và thuộc tính "Xử lý bề mặt"
    $sql_items = "
    SELECT
        ctcbh.*,
        ctcbh.DoDay as DoDayItem,
        ctcbh.BanRong as BanRongItem,
        v.variant_name,
        vi.quantity AS TonKhoVatLy,

        -- Lấy giá trị Xử lý bề mặt
        (SELECT ao_xlbm.value
         FROM variant_attributes va_xlbm
         JOIN attribute_options ao_xlbm ON va_xlbm.option_id = ao_xlbm.option_id
         JOIN attributes a_xlbm ON ao_xlbm.attribute_id = a_xlbm.attribute_id
         WHERE va_xlbm.variant_id = v.variant_id AND a_xlbm.name = 'Xử lý bề mặt'
         LIMIT 1) AS XuLyBeMat,

        -- Lấy giá trị Định mức kg/ bộ
        (SELECT ao_kg.value
         FROM variant_attributes va_kg
         JOIN attribute_options ao_kg ON va_kg.option_id = ao_kg.option_id
         JOIN attributes a_kg ON ao_kg.attribute_id = a_kg.attribute_id
         WHERE va_kg.variant_id = v.variant_id AND a_kg.name = 'Định mức kg/ bộ'
         LIMIT 1) AS dinh_muc_kg,

        -- Lấy giá trị Định mức đóng thùng/tải
        (SELECT ao_tai.value
         FROM variant_attributes va_tai
         JOIN attribute_options ao_tai ON va_tai.option_id = ao_tai.option_id
         JOIN attributes a_tai ON ao_tai.attribute_id = a_tai.attribute_id
         WHERE va_tai.variant_id = v.variant_id AND a_tai.name = 'Định mức đóng thùng/tải'
         LIMIT 1) AS dinh_muc_tai

    FROM chitietchuanbihang ctcbh
    LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id
    LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
    WHERE ctcbh.CBH_ID = :cbhId
    ORDER BY ctcbh.ThuTuHienThi";

    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([':cbhId' => $cbhId]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_alloc_by_id = $pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND CBH_ID != ?");

    $hangSanXuat = [];
    $hangChuanBi_ULA = [];
    $hangDeoTreo = [];

    foreach ($items as &$item) {
        $sanPhamID = $item['SanPhamID'];
        $stmt_alloc_by_id->execute([$sanPhamID, $cbhId]);
        $daGan = $stmt_alloc_by_id->fetchColumn();
        $item['DaGan'] = (int)$daGan;

        $tonKhoVatLy = (int)($item['TonKhoVatLy'] ?? 0);
        $item['TonKho'] = $tonKhoVatLy;

        $tonKhoKhaDung = max(0, $tonKhoVatLy - $item['DaGan']);
        $soLuongLayTuKho = min((int)$item['SoLuong'], $tonKhoKhaDung);

        $item['SoLuongLayTuKho'] = $soLuongLayTuKho;
        $item['SoLuongCanSX'] = (int)$item['SoLuong'] - $soLuongLayTuKho;

        $maHang = strtoupper(trim($item['MaHang'] ?? ''));

        // Phân loại dựa trên mã hàng hoặc nhóm sản phẩm nếu có
        $isPurProduct = str_starts_with($maHang, 'PUR');
        $isUlaProduct = str_starts_with($maHang, 'ULA');
        $isDtProduct = str_starts_with($maHang, 'DT');

        if ($isPurProduct) {
            $item['TonKhoCV'] = (int)($item['TonKhoCV'] ?? 0);
            $item['DaGanCV'] = (int)($item['DaGanCV'] ?? 0);
            $item['TonKhoCT'] = (int)($item['TonKhoCT'] ?? 0);
            $item['DaGanCT'] = (int)($item['DaGanCT'] ?? 0);
            if (!isset($item['CanSanXuatCay'])) $item['CanSanXuatCay'] = 0;
            if (!isset($item['SoCayPhaiCat'])) $item['SoCayPhaiCat'] = '0';
            $hangSanXuat[] = $item;
        } elseif ($isUlaProduct) {
            $hangChuanBi_ULA[] = $item; // Bao gồm cả MNN và MDP ở đây
        } elseif ($isDtProduct) {
            $hangDeoTreo[] = $item;
        } else {
             // Fallback logic nếu cần
            if (str_contains($maHang, 'PUR')) {
                if (!isset($item['TonKhoCV'])) $item['TonKhoCV'] = 0;
                if (!isset($item['DaGanCV'])) $item['DaGanCV'] = 0;
                if (!isset($item['TonKhoCT'])) $item['TonKhoCT'] = 0;
                if (!isset($item['DaGanCT'])) $item['DaGanCT'] = 0;
                if (!isset($item['CanSanXuatCay'])) $item['CanSanXuatCay'] = 0;
                if (!isset($item['SoCayPhaiCat'])) $item['SoCayPhaiCat'] = '0';
                $hangSanXuat[] = $item;
            } elseif (str_contains($maHang, 'ULA')) {
                 $hangChuanBi_ULA[] = $item;
            }
        }
    }
    unset($item);

    // Lọc ra các LSX sau khi đã phân loại
    $lenhSanXuatPUR = array_filter($hangSanXuat, fn($item) => ($item['SoLuongCanSX'] ?? 0) > 0);
     $lenhSanXuatPUR = array_map(function($item) {
        // Chỉ lấy các trường cần thiết cho LSX
        return [
            'ChiTietCBH_ID' => $item['ChiTietCBH_ID'] ?? null,
            'MaHang' => $item['MaHang'] ?? '',
            'SoLuongCanSX' => $item['SoLuongCanSX'] ?? 0,
            'CanSanXuatCay' => $item['CanSanXuatCay'] ?? 0, // Quan trọng cho LSX BTP
            // Thêm các trường khác nếu LSX BTP cần
        ];
    }, $lenhSanXuatPUR);


    $lenhSanXuatULA = array_filter($hangChuanBi_ULA, fn($item) => ($item['SoLuongCanSX'] ?? 0) > 0 && (!isset($item['XuLyBeMat']) || $item['XuLyBeMat'] !== 'Mạ nhúng nóng')); // Chỉ ULA thường cần SX
    $lenhSanXuatULA = array_map(function($item) {
        return [
            'ChiTietCBH_ID' => $item['ChiTietCBH_ID'] ?? null,
            'MaHang' => $item['MaHang'] ?? '',
            'SoLuongCanSX' => $item['SoLuongCanSX'] ?? 0
            // Thêm các trường khác nếu LSX ULA cần
        ];
    }, $lenhSanXuatULA);


    $sql_ecu = "SELECT ChiTietEcuCBH_ID, TenSanPhamEcu, SoLuongEcu, SoLuongPhanBo, DongGoiEcu, SoKgEcu, GhiChuEcu, TonKhoSnapshot as TonKho, DaGanSnapshot as DaGan FROM chitiet_ecu_cbh WHERE CBH_ID = :cbhId";
    $stmt_ecu = $pdo->prepare($sql_ecu);
    $stmt_ecu->execute([':cbhId' => $cbhId]);
    $vatTuKem_ECU = $stmt_ecu->fetchAll(PDO::FETCH_ASSOC);

    $sql_btp = "SELECT * FROM chitiet_btp_cbh WHERE CBH_ID = :cbhId";
    $stmt_btp = $pdo->prepare($sql_btp);
    $stmt_btp->execute([':cbhId' => $cbhId]);
    $banThanhPham = $stmt_btp->fetchAll(PDO::FETCH_ASSOC);


    // =================================================================
    // === BẮT ĐẦU LOGIC TRUY VẤN GIA CÔNG MẠ NHÚNG NÓNG (MNN) ===
    // =================================================================

    $danhSachGiaCongMaNhungNong = [];
    // $stmt_alloc_by_id đã được chuẩn bị

    // --- Câu lệnh mới để tìm SP MDP dựa trên thuộc tính ---
    // [ĐÃ SỬA V15.5] Mở rộng điều kiện loại trừ hậu tố (-HDG, -MNN, -PVC)
    $sql_find_mdp = "
        SELECT v_mdp.variant_id, v_mdp.variant_sku, vi_mdp.quantity AS TonKhoVatLy
        FROM variants v_mdp
        LEFT JOIN variant_inventory vi_mdp ON v_mdp.variant_id = vi_mdp.variant_id

        -- 1. JOIN để lấy Xử lý bề mặt (Mục tiêu: 'Mạ điện phân')
        JOIN variant_attributes va_xlbm_mdp ON v_mdp.variant_id = va_xlbm_mdp.variant_id
        JOIN attribute_options ao_xlbm_mdp ON va_xlbm_mdp.option_id = ao_xlbm_mdp.option_id
        JOIN attributes a_xlbm_mdp ON ao_xlbm_mdp.attribute_id = a_xlbm_mdp.attribute_id AND a_xlbm_mdp.name = 'Xử lý bề mặt'

        -- 2. JOIN để lấy 'ID Thông Số' (Để so khớp)
        JOIN variant_attributes va_idts_mdp ON v_mdp.variant_id = va_idts_mdp.variant_id
        JOIN attribute_options ao_idts_mdp ON va_idts_mdp.option_id = ao_idts_mdp.option_id
        JOIN attributes a_idts_mdp ON a_idts_mdp.attribute_id = ao_idts_mdp.attribute_id AND a_idts_mdp.name = 'ID Thông Số'

        -- 3. LEFT JOIN để lấy 'Kích thước ren' (Vì không phải SP nào cũng có)
        LEFT JOIN variant_attributes va_ren_mdp ON v_mdp.variant_id = va_ren_mdp.variant_id
        LEFT JOIN attribute_options ao_ren_mdp ON va_ren_mdp.option_id = ao_ren_mdp.option_id
        LEFT JOIN attributes a_ren_mdp ON a_ren_mdp.attribute_id = ao_ren_mdp.attribute_id AND a_ren_mdp.name = 'Kích thước ren'

        WHERE
            -- ĐK 1: Phải là 'Mạ điện phân'
            ao_xlbm_mdp.value = 'Mạ điện phân'

            -- ĐK 2: Phải là SKU gốc (không có hậu tố)
            AND v_mdp.variant_sku NOT LIKE '%-HDG'
            AND v_mdp.variant_sku NOT LIKE '%-MNN'
            AND v_mdp.variant_sku NOT LIKE '%-PVC'
            AND v_mdp.variant_sku NOT LIKE '%-CP'

            -- ĐK 3: Phải có cùng 'ID Thông Số' (option_id) với sản phẩm MNN
            AND ao_idts_mdp.option_id = (
                SELECT va_idts_mnn.option_id
                FROM variant_attributes va_idts_mnn
                JOIN attribute_options ao_idts_mnn ON va_idts_mnn.option_id = ao_idts_mnn.option_id
                JOIN attributes a_idts_mnn ON a_idts_mnn.attribute_id = ao_idts_mnn.attribute_id AND a_idts_mnn.name = 'ID Thông Số'
                WHERE va_idts_mnn.variant_id = :mnn_variant_id_1
                LIMIT 1
            )

            -- ĐK 4: Phải có cùng 'Kích thước ren' (option_id) với sản phẩm MNN
            AND COALESCE(ao_ren_mdp.option_id, 'NULL_ATTR') = COALESCE(
                (
                    SELECT va_ren_mnn.option_id
                    FROM variant_attributes va_ren_mnn
                    JOIN attribute_options ao_ren_mnn ON va_ren_mnn.option_id = ao_ren_mnn.option_id
                    JOIN attributes a_ren_mnn ON a_ren_mnn.attribute_id = ao_ren_mnn.attribute_id AND a_ren_mnn.name = 'Kích thước ren'
                    WHERE va_ren_mnn.variant_id = :mnn_variant_id_2
                    LIMIT 1
                ),
                'NULL_ATTR'
            )
        LIMIT 1"; // Chỉ tìm 1 sản phẩm MDP tương ứng
    $stmt_find_mdp = $pdo->prepare($sql_find_mdp);
    // --- Kết thúc câu lệnh tìm MDP ---


    foreach ($hangChuanBi_ULA as $itemULA) {

        // --- Kiểm tra bằng cột XuLyBeMat ---
        if (isset($itemULA['XuLyBeMat']) && $itemULA['XuLyBeMat'] === 'Mạ nhúng nóng' && $itemULA['SoLuongCanSX'] > 0) {

            $mnn_variant_id = $itemULA['SanPhamID']; // Lấy variant_id của sản phẩm MNN

            // 1. Tìm sản phẩm Mạ Điện Phân (MDP) tương ứng dựa trên thuộc tính
            // [ĐÃ SỬA V15.2] Truyền cả 2 tham số đã định nghĩa trong SQL
            $stmt_find_mdp->execute([
                ':mnn_variant_id_1' => $mnn_variant_id,
                ':mnn_variant_id_2' => $mnn_variant_id
            ]);
            $spDienPhan = $stmt_find_mdp->fetch(PDO::FETCH_ASSOC);

            $spDienPhanData = null;
            $soLuongCoTheXuat = 0;
            $ghiChu = "Không tìm thấy SP MĐP tương ứng."; // Mặc định

            // 2. Nếu tìm thấy SP Mạ Điện Phân
            if ($spDienPhan) {
                // Lấy số lượng đã gán của SP Mạ Điện Phân cho các phiếu CBH khác
                $stmt_alloc_by_id->execute([$spDienPhan['variant_id'], $cbhId]);
                $daGanDienPhan = (int)$stmt_alloc_by_id->fetchColumn();

                $tonKhoVatLyDienPhan = (int)($spDienPhan['TonKhoVatLy'] ?? 0);
                $tonKhoKhaDungDienPhan = max(0, $tonKhoVatLyDienPhan - $daGanDienPhan);

                $spDienPhanData = [
                    'variant_id' => $spDienPhan['variant_id'],
                    'sku' => $spDienPhan['variant_sku'],
                    'TonKhoVatLy' => $tonKhoVatLyDienPhan,
                    'DaGan' => $daGanDienPhan,
                    'TonKhoKhaDung' => $tonKhoKhaDungDienPhan
                ];

                // 3. Tính toán số lượng có thể xuất
                $soLuongCanThiet = (int)$itemULA['SoLuongCanSX'];
                $soLuongCoTheXuat = min($soLuongCanThiet, $tonKhoKhaDungDienPhan);

                // 4. Ghi chú trạng thái
                if ($soLuongCoTheXuat <= 0) {
                     $ghiChu = "Hết hàng MĐP ({$spDienPhan['variant_sku']} - TKKD: $tonKhoKhaDungDienPhan)";
                } elseif ($soLuongCoTheXuat < $soLuongCanThiet) {
                     $ghiChu = "Thiếu hàng MĐP ({$spDienPhan['variant_sku']} - Cần $soLuongCanThiet, TKKD: $tonKhoKhaDungDienPhan)";
                } else {
                     $ghiChu = "Đủ hàng MĐP ({$spDienPhan['variant_sku']} - TKKD: $tonKhoKhaDungDienPhan)";
                }
            } else {
                // Ghi chú nếu không tìm thấy MDP
                $ghiChu = "Không tìm thấy SP MĐP tương ứng (kiểm tra thuộc tính 'ID Thông Số', 'Kích thước ren' và quy tắc hậu tố SKU).";
            }

            // 5. Thêm vào mảng kết quả
            $danhSachGiaCongMaNhungNong[] = [
                'san_pham_nhung_nong' => $itemULA,
                'san_pham_dien_phan' => $spDienPhanData,
                'so_luong_xuat_gia_cong' => $soLuongCoTheXuat,
                'so_luong_con_thieu' => (int)$itemULA['SoLuongCanSX'],
                'ghi_chu' => $ghiChu
            ];
        }
    }
    // =================================================================
    // === KẾT THÚC LOGIC TRUY VẤN GIA CÔNG MẠ NHÚNG NÓNG (MNN) ===
    // =================================================================


    $statusSummary = [
        'inventoryStatus' => 'DU_HANG_THANH_PHAM',
        'ulaStatus' => empty($hangChuanBi_ULA) && empty($hangDeoTreo) ? 'NO_ULA_PRODUCTS' : 'OK',
        'ecuStatus' => empty($vatTuKem_ECU) ? 'NO_ECU_ITEMS' : 'OK',
        'overallReady' => false
    ];

    $needs_btp_production = false;
    foreach ($banThanhPham as $btp) { if (floatval($btp['SoLuongCan']) > 0) { $needs_btp_production = true; break; } }

    if ($needs_btp_production) {
        $statusSummary['inventoryStatus'] = 'NEEDS_BTP_PRODUCTION';
    } else {
        $needs_cutting = false;
        foreach ($hangSanXuat as $item) { if (($item['SoLuongCanSX'] ?? 0) > 0) { $needs_cutting = true; break; } } // Kiểm tra nếu PUR cần sản xuất (cắt)
        if ($needs_cutting) { $statusSummary['inventoryStatus'] = 'NEEDS_CUTTING'; }
    }

    // Kiểm tra thiếu ULA (không phải MNN) hoặc Đai treo
    foreach ($hangChuanBi_ULA as $item) {
        if (($item['SoLuongCanSX'] ?? 0) > 0 && (!isset($item['XuLyBeMat']) || $item['XuLyBeMat'] !== 'Mạ nhúng nóng')) {
            $statusSummary['ulaStatus'] = 'INSUFFICIENT'; break;
        }
    }
    if ($statusSummary['ulaStatus'] !== 'INSUFFICIENT') { // Chỉ kiểm tra Đai treo nếu ULA đã đủ
        foreach ($hangDeoTreo as $item) {
            if (($item['SoLuongCanSX'] ?? 0) > 0) { $statusSummary['ulaStatus'] = 'INSUFFICIENT'; break; }
        }
    }

    // Kiểm tra thiếu ECU
    foreach ($vatTuKem_ECU as $item) {
        $needed = $item['SoLuongEcu'] ?? 0;
        $allocated = $item['SoLuongPhanBo'] ?? 0; // Số lượng lấy từ kho cho phiếu này
        if ($needed > $allocated) {
            $statusSummary['ecuStatus'] = 'INSUFFICIENT'; break;
        }
    }

     // Kiểm tra thiếu hàng gia công MNN (sau khi đã tính toán)
    $needs_mnn_processing = false;
    foreach($danhSachGiaCongMaNhungNong as $gcItem){
        if($gcItem['so_luong_xuat_gia_cong'] < $gcItem['so_luong_con_thieu']){
            $needs_mnn_processing = true;
            break;
        }
    }
    // Nếu ULA nói chung là OK, nhưng cần xử lý MNN, thì cập nhật trạng thái ULA
    if($statusSummary['ulaStatus'] === 'OK' && $needs_mnn_processing){
         $statusSummary['ulaStatus'] = 'NEEDS_MNN_PROCESSING';
    }


    // Kiểm tra tổng thể sẵn sàng
     if (
        ($statusSummary['inventoryStatus'] === 'DU_HANG_THANH_PHAM' || $statusSummary['inventoryStatus'] === 'NEEDS_CUTTING') // PUR ok hoặc chỉ cần cắt
        && ($statusSummary['ulaStatus'] === 'OK' || $statusSummary['ulaStatus'] === 'NEEDS_MNN_PROCESSING') // ULA ok hoặc chỉ cần gia công MNN
        && $statusSummary['ecuStatus'] === 'OK' // ECU ok
    ) {
        // Có thể cần thêm kiểm tra trạng thái nhập/xuất kho BTP, TP nếu quy trình phức tạp hơn
        $statusSummary['overallReady'] = true;
    }


    echo json_encode([
        'success' => true,
        'data' => [
            'info' => $info,
            'hangSanXuat' => array_values($hangSanXuat),
            'hangChuanBi_ULA' => array_values($hangChuanBi_ULA),
            'hangDeoTreo' => array_values($hangDeoTreo),
            'vatTuKem_ECU' => $vatTuKem_ECU,
            'banThanhPham' => $banThanhPham,
            'lenhSanXuatULA' => array_values($lenhSanXuatULA), // Lọc MNN ra khỏi đây
            'lenhSanXuatPUR' => array_values($lenhSanXuatPUR), // Chỉ chứa PUR cần SX/cắt
            'statusSummary' => $statusSummary,
            'danhSachGiaCongMaNhungNong' => $danhSachGiaCongMaNhungNong // Dữ liệu gia công
        ],
        'debug' => [
            'totalItemsFromDB' => count($items),
            'hangSanXuatCount' => count($hangSanXuat),
            'hangULACount' => count($hangChuanBi_ULA),
            'hangDeoTreoCount' => count($hangDeoTreo),
            'giaCongCount' => count($danhSachGiaCongMaNhungNong),
            'sampleItem' => isset($items[0]) ? ['MaHang' => $items[0]['MaHang'] ?? 'NULL', 'XuLyBeMat' => $items[0]['XuLyBeMat'] ?? 'NULL'] : 'NO_ITEMS'
        ]
    ]);

} catch (Exception $e) {
    error_log("ERROR in get_chuanbihang_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>