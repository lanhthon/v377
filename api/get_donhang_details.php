<?php
header('Content-Type: application/json; charset=utf-8');

// Lấy ID đơn hàng từ request
$donhangId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($donhangId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit();
}

// Sử dụng kết nối cơ sở dữ liệu từ config/database.php
require_once '../config/database.php';

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $donhang = [];
    $items = [];
    $purItems = [];
    $ulaItems = [];
    $ecuItems = [];

    // Các cờ trạng thái tổng thể
    $hasPurToProduce = false;
    $hasUlaToProduce = false;
    $hasEcuToBuy = false;
    $allInventorySufficient = true;

    // 1. Lấy thông tin chính của đơn hàng
    // Chỉ truy vấn cột TrangThai từ bảng chuanbihang
    $stmt = $conn->prepare("
        SELECT
            dh.YCSX_ID AS DonHangID,
            dh.SoYCSX,
            dh.NgayTao,
            dh.NgayHoanThanhDuKien,
            dh.NgayGiaoDuKien,
            dh.TongTien AS TongTienSauThue,
            dh.TrangThai,
            dh.TenDuAn,
            dh.TenCongTy,
            dh.NguoiBaoGia,
            cbh.CBH_ID,
            cbh.TrangThai AS TrangThaiCBH,
            (SELECT PhieuXuatKhoID FROM phieuxuatkho WHERE YCSX_ID = dh.YCSX_ID ORDER BY NgayXuat DESC LIMIT 1) AS PXK_ID
        FROM donhang dh
        LEFT JOIN chuanbihang cbh ON dh.YCSX_ID = cbh.YCSX_ID
        WHERE dh.YCSX_ID = ?
        ORDER BY cbh.CBH_ID DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $donhangId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $donhang['info'] = $result->fetch_assoc();
        $donhang['info']['CBH_ID'] = $donhang['info']['CBH_ID'] ?? null;
        $donhang['info']['TrangThaiCBH'] = $donhang['info']['TrangThaiCBH'] ?? 'Chưa chuẩn bị';
        $donhang['info']['PXK_ID'] = $donhang['info']['PXK_ID'] ?? null;
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng với ID đã cho.']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // 2. Lấy thông tin chi tiết các mặt hàng trong đơn hàng
    $stmt = $conn->prepare("
        SELECT
            ctdh.ChiTiet_YCSX_ID,
            ctdh.SanPhamID,
            ctdh.MaHang,
            ctdh.TenSanPham,
            ctdh.SoLuong,
            p.HinhDang,
            p.base_sku AS ProductBaseSKU,
            v.sku_suffix AS VariantSkuSuffix,
            v.variant_name AS TenVariant,
            v.LoaiID AS category_id,
            vi.quantity AS TonKhoVatLy,
            u_prod.name AS TonKhoVatLyUnitName,
            MAX(CASE WHEN a_dd.name = 'Độ dày' THEN ao_dd.value END) AS DoDayItem,
            MAX(CASE WHEN a_br.name = 'Bản rộng' THEN ao_br.value END) AS BanRongItem,
            MAX(CASE WHEN a_dk.name = 'Đường kính trong' THEN ao_dk.value END) AS DuongKinhTrongItem
        FROM chitiet_donhang ctdh
        LEFT JOIN variants v ON ctdh.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN units u_prod ON p.base_unit_id = u_prod.unit_id
        LEFT JOIN variant_inventory vi ON ctdh.SanPhamID = vi.variant_id
        LEFT JOIN variant_attributes va_dd ON v.variant_id = va_dd.variant_id
        LEFT JOIN attribute_options ao_dd ON va_dd.option_id = ao_dd.option_id
        LEFT JOIN attributes a_dd ON ao_dd.attribute_id = a_dd.attribute_id AND a_dd.name = 'Độ dày'
        LEFT JOIN variant_attributes va_br ON v.variant_id = va_br.variant_id
        LEFT JOIN attribute_options ao_br ON va_br.option_id = ao_br.option_id
        LEFT JOIN attributes a_br ON ao_br.attribute_id = a_br.attribute_id AND a_br.name = 'Bản rộng'
        LEFT JOIN variant_attributes va_dk ON v.variant_id = va_dk.variant_id
        LEFT JOIN attribute_options ao_dk ON va_dk.option_id = ao_dk.option_id
        LEFT JOIN attributes a_dk ON ao_dk.attribute_id = a_dk.attribute_id AND a_dk.name = 'Đường kính trong'
        WHERE ctdh.DonHangID = ?
        GROUP BY ctdh.ChiTiet_YCSX_ID
        ORDER BY ctdh.ThuTuHienThi ASC
    ");
    $stmt->bind_param("i", $donhangId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $requiredQuantity = (int)$row['SoLuong'];
        $tonKhoVatLy = (int)($row['TonKhoVatLy'] ?? 0);

        $stmt_allocated = $conn->prepare("
            SELECT COALESCE(SUM(dptk_sub.SoLuongPhanBo), 0) AS TotalAllocatedOtherOrders
            FROM donhang_phanbo_tonkho dptk_sub
            WHERE dptk_sub.SanPhamID = ? AND dptk_sub.DonHangID != ?
        ");
        $stmt_allocated->bind_param("ii", $row['SanPhamID'], $donhangId);
        $stmt_allocated->execute();
        $res_allocated = $stmt_allocated->get_result();
        $totalAllocatedOtherOrders = $res_allocated->fetch_assoc()['TotalAllocatedOtherOrders'];
        $stmt_allocated->close();

        $tonKhoKhaDung = max(0, $tonKhoVatLy - $totalAllocatedOtherOrders);
        $soLuongLayTuKho = min($requiredQuantity, $tonKhoKhaDung);
        $soLuongCanThem = $requiredQuantity - $soLuongLayTuKho;

        $row['TonKhoKhaDung'] = $tonKhoKhaDung;
        $row['SoLuongLayTuKho'] = $soLuongLayTuKho;
        $row['SoLuongCanSX'] = $soLuongCanThem;
        $row['DaGanChoDonKhac'] = $totalAllocatedOtherOrders;
        $row['DinhMucCat'] = null;
        $row['SoCayPhaiCat'] = 0;

        $isPursItem = str_starts_with(strtoupper($row['ProductBaseSKU'] ?? ''), 'PUR-S');
        $isPurcItem = str_starts_with(strtoupper($row['ProductBaseSKU'] ?? ''), 'PUR-C');
        $isUlaItem = str_starts_with(strtoupper($row['ProductBaseSKU'] ?? ''), 'ULA-');
        $isEcuItem = str_starts_with(strtoupper($row['ProductBaseSKU'] ?? ''), 'ECU-');

        if ($isPursItem || $isPurcItem) {
            if ($soLuongCanThem > 0) {
                $hasPurToProduce = true;
                $allInventorySufficient = false;
                
                $dinhMucCatValue = null;
                if (isset($row['HinhDang']) && is_numeric($row['DuongKinhTrongItem']) && is_numeric($row['BanRongItem'])) {
                    $duongKinhTrongNum = (int)$row['DuongKinhTrongItem'];
                    $banRongNum = (int)$row['BanRongItem'];
                    $stmt_dinhmuc = $conn->prepare("
                        SELECT SoBoTrenCay FROM dinh_muc_cat
                        WHERE HinhDang = ? AND ? BETWEEN MinDN AND MaxDN AND BanRong = ?
                        LIMIT 1
                    ");
                    $stmt_dinhmuc->bind_param("sii", $row['HinhDang'], $duongKinhTrongNum, $banRongNum);
                    $stmt_dinhmuc->execute();
                    $res_dinhmuc = $stmt_dinhmuc->get_result();
                    if ($dinhMuc = $res_dinhmuc->fetch_assoc()) {
                        $dinhMucCatValue = $dinhMuc['SoBoTrenCay'];
                    }
                    $stmt_dinhmuc->close();
                }

                $row['DinhMucCat'] = $dinhMucCatValue;
                if ($dinhMucCatValue > 0) {
                    $soCayPhaiCat = $soLuongCanThem / $dinhMucCatValue;
                    if ($isPurcItem) {
                        $soCayPhaiCat /= 2;
                    }
                    $row['SoCayPhaiCat'] = ceil($soCayPhaiCat);
                } else {
                    $row['SoCayPhaiCat'] = 0;
                }
            }
            $purItems[] = $row;
        } elseif ($isUlaItem) {
            if ($soLuongCanThem > 0) {
                $hasUlaToProduce = true;
                $allInventorySufficient = false;
            }
            $ulaItems[] = $row;
        } elseif ($isEcuItem) {
            if ($soLuongCanThem > 0) {
                $hasEcuToBuy = true;
                $allInventorySufficient = false;
            }
            $ecuItems[] = $row;
        }

        $items[] = $row;
    }
    $result->close();

    $donhang['items'] = $items;
    $donhang['pur_items'] = $purItems;
    $donhang['ula_items'] = $ulaItems;
    $donhang['ecu_items'] = $ecuItems;

    $donhang['status_summary'] = [
        'hasPurToProduce' => $hasPurToProduce,
        'hasUlaToProduce' => $hasUlaToProduce,
        'hasEcuToBuy' => $hasEcuToBuy,
        'allInventorySufficient' => $allInventorySufficient
    ];

    echo json_encode(['success' => true, 'donhang' => $donhang], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>