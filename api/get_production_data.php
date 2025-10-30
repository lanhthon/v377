<?php
header('Content-Type: application/json; charset=utf-8');

// Sử dụng kết nối cơ sở dữ liệu từ config/database.php
require_once '../config/database.php';

$statusType = isset($_GET['status_type']) ? $_GET['status_type'] : 'inprogress'; // 'inprogress' hoặc 'completed'

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $productionOrders = [];
    $allowedStatuses = [];

    if ($statusType === 'inprogress') {
        $allowedStatuses = ["Chờ sản xuất", "Đang sản xuất"];
    } elseif ($statusType === 'completed') {
        $allowedStatuses = ["Đã hoàn thành"];
    } else {
        echo json_encode(['success' => false, 'message' => 'Loại trạng thái không hợp lệ.']);
        exit();
    }

    $inClause = implode(',', array_fill(0, count($allowedStatuses), '?'));

    // 1. Lấy thông tin chính của lệnh sản xuất
    // THAY ĐỔI: Lấy thêm NgayHoanThanhThucTe
    $sql_production_orders = "
        SELECT
            lsx.LenhSX_ID,
            lsx.SoLenhSX,
            lsx.YCSX_ID,
            dh.SoYCSX,
            dh.TenCongTy,
            lsx.NgayYCSX,
            dh.NgayGiaoDuKien,
            lsx.TrangThai,
            lsx.NgayHoanThanhThucTe,
            dh.NgayHoanThanhDuKien
        FROM lenh_san_xuat lsx
        JOIN donhang dh ON lsx.YCSX_ID = dh.YCSX_ID
        WHERE lsx.TrangThai IN ($inClause)
        ORDER BY lsx.NgayYCSX DESC, lsx.LenhSX_ID DESC
    ";
    $stmt_production_orders = $conn->prepare($sql_production_orders);

    $types = str_repeat('s', count($allowedStatuses));
    $stmt_production_orders->bind_param($types, ...$allowedStatuses);
    $stmt_production_orders->execute();
    $result_production_orders = $stmt_production_orders->get_result();

    $orderIds = [];
    $orderData = [];
    while ($row = $result_production_orders->fetch_assoc()) {
        $orderIds[] = $row['LenhSX_ID'];
        $orderData[$row['LenhSX_ID']] = [
            'info' => $row,
            'items' => [],
            'summary' => [
                'tongSoBo' => 0,
                'tongSoCay' => 0,
                // THAY ĐỔI: Ưu tiên ngày thực tế nếu có, nếu không thì dùng ngày dự kiến
                'ngayHoanThanh' => $row['NgayHoanThanhThucTe'] ?? $row['NgayHoanThanhDuKien']
            ]
        ];
    }
    $stmt_production_orders->close();

    if (empty($orderIds)) {
        echo json_encode(['success' => true, 'orders' => []], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $conn->close();
        exit();
    }

    $idPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $types = str_repeat('i', count($orderIds));

    // 2. Lấy thông tin chi tiết các mặt hàng trong từng lệnh sản xuất
    $sql_items = "
        SELECT
            ctlsx.ChiTiet_LSX_ID, ctlsx.LenhSX_ID, ctlsx.SanPhamID, ctlsx.SoLuongBoCanSX,
            v.variant_name AS TenSanPham, v.variant_sku AS MaHang, p.base_sku,
            MAX(CASE WHEN a_dd.name = 'Độ dày' THEN ao_dd.value END) AS DoDayItem,
            MAX(CASE WHEN a_br.name = 'Bản rộng' THEN ao_br.value END) AS BanRongItem,
            MAX(CASE WHEN a_dk.name = 'Đường kính trong' THEN ao_dk.value END) AS DuongKinhTrongItem,
            p.HinhDang, v.sku_suffix AS VariantSkuSuffix
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
        WHERE ctlsx.LenhSX_ID IN ($idPlaceholders)
        GROUP BY
            ctlsx.ChiTiet_LSX_ID, ctlsx.SanPhamID, ctlsx.SoLuongBoCanSX,
            v.variant_name, v.variant_sku, p.base_sku, p.HinhDang, v.sku_suffix
        ORDER BY ctlsx.ChiTiet_LSX_ID ASC
    ";

    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param($types, ...$orderIds);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while ($itemRow = $result_items->fetch_assoc()) {
        $lenhSX_ID = $itemRow['LenhSX_ID'];
        if (!isset($orderData[$lenhSX_ID])) continue;

        $soLuongBoCanSX = (float)$itemRow['SoLuongBoCanSX'];
        $dinhMucCatValue = null;
        $soCayTuongDuong = 0;
        $itemRow['MaBTPPhuHop'] = null;
        $hinhDangItem = $itemRow['HinhDang'];
        $duongKinhTrongItem = $itemRow['DuongKinhTrongItem'];
        $banRongItem = $itemRow['BanRongItem'];
        $isPurcItem = str_starts_with(strtoupper($itemRow['base_sku'] ?? ''), 'PUR-C');
        $isPursItem = str_starts_with(strtoupper($itemRow['base_sku'] ?? ''), 'PUR-S');

        if ($hinhDangItem && is_numeric($duongKinhTrongItem) && is_numeric($banRongItem)) {
            $duongKinhTrongNum = (int)$duongKinhTrongItem;
            $banRongNum = (int)$banRongItem;
            $stmt_dinhmuc = $conn->prepare("SELECT SoBoTrenCay FROM dinh_muc_cat WHERE HinhDang = ? AND ? BETWEEN MinDN AND MaxDN AND BanRong = ? LIMIT 1");
            $stmt_dinhmuc->bind_param("sii", $hinhDangItem, $duongKinhTrongNum, $banRongNum);
            $stmt_dinhmuc->execute();
            $res_dinhmuc = $stmt_dinhmuc->get_result();
            if ($dinhMuc = $res_dinhmuc->fetch_assoc()) {
                $dinhMucCatValue = (float)$dinhMuc['SoBoTrenCay'];
            }
            $stmt_dinhmuc->close();
        }
        $itemRow['DinhMucCat'] = $dinhMucCatValue;

        if ($dinhMucCatValue > 0) {
            $soCayTuongDuong = $soLuongBoCanSX / $dinhMucCatValue;
            if ($isPurcItem) {
                $soCayTuongDuong /= 2;
            }
            $soCayTuongDuong = ceil($soCayTuongDuong);
        } else {
            $soCayTuongDuong = "Không có định mức";
        }
        $itemRow['SoLuongCayTuongDuong'] = $soCayTuongDuong;

        if (($isPursItem || $isPurcItem) && !empty($itemRow['HinhDang']) && !empty($itemRow['DoDayItem']) && !empty($itemRow['DuongKinhTrongItem'])) {
            $doDayItem = $itemRow['DoDayItem'];
            $skuSuffixItem = $itemRow['VariantSkuSuffix'];
            $sql_btp = "
                SELECT v_btp.variant_sku AS MaBTP FROM variants v_btp
                JOIN products p_btp ON v_btp.product_id = p_btp.product_id
                JOIN variant_attributes va_dd ON v_btp.variant_id = va_dd.variant_id
                JOIN attribute_options ao_dd ON va_dd.option_id = ao_dd.option_id
                JOIN attributes a_dd ON ao_dd.attribute_id = a_dd.attribute_id AND a_dd.name = 'Độ dày'
                JOIN variant_attributes va_dkt ON v_btp.variant_id = va_dkt.variant_id
                JOIN attribute_options ao_dkt ON va_dkt.option_id = ao_dkt.option_id
                JOIN attributes a_dkt ON ao_dkt.attribute_id = a_dkt.attribute_id AND a_dkt.name = 'Đường kính trong'
                WHERE (p_btp.base_sku LIKE 'CV%' OR p_btp.base_sku LIKE 'CT%')
                AND p_btp.HinhDang = ? AND ao_dd.value = ? AND ao_dkt.value = ?
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
                $itemRow['MaBTPPhuHop'] = $btpItem['MaBTP'];
            }
            $stmt_btp->close();
        }

        $orderData[$lenhSX_ID]['items'][] = $itemRow;
        $orderData[$lenhSX_ID]['summary']['tongSoBo'] += $soLuongBoCanSX;
        if (is_numeric($soCayTuongDuong)) {
            $orderData[$lenhSX_ID]['summary']['tongSoCay'] += $soCayTuongDuong;
        }
    }
    $stmt_items->close();

    foreach ($orderData as $lenhSX_ID => $data) {
        if (empty($data['items'])) {
            unset($orderData[$lenhSX_ID]);
        }
    }

    $productionOrders = array_values($orderData);

    echo json_encode(['success' => true, 'orders' => $productionOrders], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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