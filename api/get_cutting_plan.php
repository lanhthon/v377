<?php
header('Content-Type: application/json; charset=utf-8');

// Lấy LenhSX_ID từ request
$lenhsxId = isset($_GET['lenhsxId']) ? intval($_GET['lenhsxId']) : 0;

if ($lenhsxId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID Lệnh sản xuất không hợp lệ.']);
    exit();
}

require_once '../config/database.php';

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $productionOrderInfo = [];
    $items = [];

    // 1. Lấy thông tin chính của lệnh sản xuất và đơn hàng gốc
    $stmt_po_info = $conn->prepare("
        SELECT
            lsx.LenhSX_ID,
            lsx.SoLenhSX,
            dh.YCSX_ID AS DonHangID,
            dh.SoYCSX,
            dh.TenCongTy
        FROM lenh_san_xuat lsx
        JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        WHERE lsx.LenhSX_ID = ?
        LIMIT 1
    ");
    $stmt_po_info->bind_param("i", $lenhsxId);
    $stmt_po_info->execute();
    $result_po_info = $stmt_po_info->get_result();
    if ($result_po_info->num_rows > 0) {
        $productionOrderInfo = $result_po_info->fetch_assoc();
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy lệnh sản xuất với ID đã cho.']);
        $stmt_po_info->close();
        $conn->close();
        exit();
    }
    $stmt_po_info->close();

    // 2. Lấy chi tiết các mặt hàng từ lệnh sản xuất
    $stmt_items = $conn->prepare("
        SELECT
            ctlsx.ChiTiet_LSX_ID,
            ctlsx.SanPhamID,
            ctlsx.SoLuongBoCanSX,
            v.variant_name AS TenSanPham,
            p.base_sku AS MaHang,
            MAX(CASE WHEN a_dd.name = 'Độ dày' THEN ao_dd.value END) AS DoDayItem,
            MAX(CASE WHEN a_br.name = 'Bản rộng' THEN ao_br.value END) AS BanRongItem,
            MAX(CASE WHEN a_dk.name = 'Đường kính trong' THEN ao_dk.value END) AS DuongKinhTrongItem,
            p.HinhDang,
            v.sku_suffix AS VariantSkuSuffix
        FROM chitiet_lenh_san_xuat ctlsx
        JOIN variants v ON ctlsx.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN variant_attributes va_dd ON v.variant_id = va_dd.variant_id
        LEFT JOIN attribute_options ao_dd ON va_dd.option_id = ao_dd.option_id
        LEFT JOIN attributes a_dd ON ao_dd.attribute_id = a_dd.attribute_id AND a_dd.name = 'Độ dày'
        LEFT JOIN variant_attributes va_br ON v.variant_id = va_br.variant_id
        LEFT JOIN attribute_options ao_br ON va_br.option_id = ao_br.option_id
        LEFT JOIN attributes a_br ON ao_br.attribute_id = a_br.attribute_id AND a_br.name = 'Bản rộng'
        LEFT JOIN variant_attributes va_dk ON v.variant_id = va_dk.variant_id
        LEFT JOIN attribute_options ao_dk ON va_dk.option_id = ao_dk.option_id
        LEFT JOIN attributes a_dk ON ao_dk.attribute_id = a_dk.attribute_id AND a_dk.name = 'Đường kính trong'
        WHERE ctlsx.LenhSX_ID = ?
        GROUP BY ctlsx.ChiTiet_LSX_ID, ctlsx.SanPhamID, ctlsx.SoLuongBoCanSX, v.variant_name, p.base_sku, p.HinhDang, v.sku_suffix
        ORDER BY ctlsx.ChiTiet_LSX_ID ASC
    ");
    $stmt_items->bind_param("i", $lenhsxId);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while ($row = $result_items->fetch_assoc()) {
        $soLuongCanSX = (int)$row['SoLuongBoCanSX'];

        $dinhMucCatValue = null;
        $hinhDangItem = $row['HinhDang'];
        $isPursItem = str_starts_with(strtoupper($row['MaHang'] ?? ''), 'PUR-S');
        $isPurcItem = str_starts_with(strtoupper($row['MaHang'] ?? ''), 'PUR-C');

        // Lấy định mức cắt
        if ($hinhDangItem && is_numeric($row['DuongKinhTrongItem']) && is_numeric($row['BanRongItem'])) {
            $duongKinhTrongNum = (int)$row['DuongKinhTrongItem'];
            $banRongNum = (int)$row['BanRongItem'];

            $stmt_dinhmuc = $conn->prepare("
                SELECT SoBoTrenCay FROM dinh_muc_cat
                WHERE HinhDang = ? AND ? BETWEEN MinDN AND MaxDN AND BanRong = ?
                LIMIT 1
            ");
            $stmt_dinhmuc->bind_param("sii", $hinhDangItem, $duongKinhTrongNum, $banRongNum);
            $stmt_dinhmuc->execute();
            $res_dinhmuc = $stmt_dinhmuc->get_result();
            if ($dinhMuc = $res_dinhmuc->fetch_assoc()) {
                $dinhMucCatValue = (float)$dinhMuc['SoBoTrenCay'];
            }
            $stmt_dinhmuc->close();
        }
        $row['DinhMucCat'] = $dinhMucCatValue;

        $row['MaBTPPhuHop'] = null;
        $row['TonKhoBTPPhuHop'] = null;
        $row['DonViTinhBTPPhuHop'] = null;
        $row['SoCayPhaiCat'] = null;

        // Tìm BTP phù hợp nếu là sản phẩm cần cắt
        if ($soLuongCanSX > 0 && ($isPursItem || $isPurcItem) && !empty($row['HinhDang']) && !empty($row['DoDayItem']) && !empty($row['DuongKinhTrongItem'])) {

            $hinhDangItem = $row['HinhDang'];
            $doDayItem = $row['DoDayItem'];
            $duongKinhTrongItem = $row['DuongKinhTrongItem'];
            $skuSuffixItem = $row['VariantSkuSuffix'];

            $sql_btp = "
                SELECT
                    v_btp.variant_sku AS MaBTP,
                    COALESCE(vi_btp.quantity, 0) AS TonKhoBTP,
                    u_btp.name AS DonViTinhBTP
                FROM variants v_btp
                JOIN products p_btp ON v_btp.product_id = p_btp.product_id
                LEFT JOIN variant_inventory vi_btp ON v_btp.variant_id = vi_btp.variant_id
                LEFT JOIN units u_btp ON p_btp.base_unit_id = u_btp.unit_id
                JOIN variant_attributes va_dd ON v_btp.variant_id = va_dd.variant_id
                JOIN attribute_options ao_dd ON va_dd.option_id = ao_dd.option_id
                JOIN attributes a_dd ON ao_dd.attribute_id = a_dd.attribute_id AND a_dd.name = 'Độ dày'
                JOIN variant_attributes va_dkt ON v_btp.variant_id = va_dkt.variant_id
                JOIN attribute_options ao_dkt ON va_dkt.option_id = ao_dkt.option_id
                JOIN attributes a_dkt ON ao_dkt.attribute_id = a_dkt.attribute_id AND a_dkt.name = 'Đường kính trong'
                WHERE
                    (p_btp.base_sku LIKE 'CV%' OR p_btp.base_sku LIKE 'CT%')
                    AND p_btp.HinhDang = ?
                    AND ao_dd.value = ?
                    AND ao_dkt.value = ?
            ";

            $params_btp = [$hinhDangItem, $doDayItem, $duongKinhTrongItem];
            $types_btp = "sss";

            if (!empty($skuSuffixItem)) {
                $sql_btp .= " AND v_btp.sku_suffix = ?";
                $params_btp[] = $skuSuffixItem;
                $types_btp .= "s";
            } else {
                $sql_btp .= " AND (v_btp.sku_suffix IS NULL OR v_btp.sku_suffix = '')";
            }
            $sql_btp .= " LIMIT 1";

            $stmt_btp = $conn->prepare($sql_btp);
            $stmt_btp->bind_param($types_btp, ...$params_btp);
            $stmt_btp->execute();
            $res_btp = $stmt_btp->get_result();

            if ($btpItem = $res_btp->fetch_assoc()) {
                $row['MaBTPPhuHop'] = $btpItem['MaBTP'];
                $row['TonKhoBTPPhuHop'] = (float)$btpItem['TonKhoBTP'];
                $row['DonViTinhBTPPhuHop'] = $btpItem['DonViTinhBTP'];

                if ($dinhMucCatValue > 0) {
                    $soCayPhaiCat = $soLuongCanSX / $dinhMucCatValue;
                    if ($isPurcItem) {
                        $soCayPhaiCat /= 2;
                    }
                    $row['SoCayPhaiCat'] = ceil($soCayPhaiCat);
                } else {
                    $row['SoCayPhaiCat'] = "Không có định mức";
                }
            } else {
                $row['SoCayPhaiCat'] = "Không tìm thấy BTP phù hợp";
            }
            $stmt_btp->close();
        } elseif ($soLuongCanSX > 0) {
            $row['SoCayPhaiCat'] = "Không áp dụng cắt";
        }

        $items[] = $row;
    }
    $stmt_items->close();

    echo json_encode(['success' => true, 'productionOrderInfo' => $productionOrderInfo, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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