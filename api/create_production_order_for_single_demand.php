<?php
/**
 * File: api/create_production_order.php
 * Version: 1.0 - Đã thêm lọc sản phẩm PUR
 * Description: API tạo lệnh sản xuất từ một đơn hàng.
 * - Chỉ thêm các sản phẩm có base_sku bắt đầu bằng 'PUR' vào chi tiết lệnh sản xuất.
 * - Cập nhật trạng thái đơn hàng thành 'Đang sản xuất' sau khi tạo lệnh.
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/database.php';

// Lấy YCSX_ID từ request (có thể là POST hoặc GET, POST an toàn hơn cho việc thay đổi dữ liệu)
$ycsxId = isset($_POST['ycsx_id']) ? intval($_POST['ycsx_id']) : 0;

if ($ycsxId === 0) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit();
}

// Biến cờ để theo dõi trạng thái transaction
$transactionStarted = false;

try {
    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $conn->begin_transaction(); // Bắt đầu transaction
    $transactionStarted = true; // Đặt cờ là true sau khi transaction bắt đầu

    // 1. Kiểm tra đơn hàng: tồn tại, trạng thái phù hợp, và chưa có lệnh sản xuất
    $sql_check_demand = "
        SELECT
            dh.YCSX_ID,
            dh.SoYCSX,
            dh.NgayTao,
            dh.NgayGiaoDuKien,
            dh.NgayHoanThanhDuKien,
            dh.TrangThai,
            dh.NeedsProduction
        FROM donhang dh
        LEFT JOIN lenh_san_xuat lsx ON dh.YCSX_ID = lsx.YCSX_ID
        WHERE dh.YCSX_ID = ? AND lsx.LenhSX_ID IS NULL
    ";
    $stmt_check_demand = $conn->prepare($sql_check_demand);
    $stmt_check_demand->bind_param("i", $ycsxId);
    $stmt_check_demand->execute();
    $result_check_demand = $stmt_check_demand->get_result();

    if ($result_check_demand->num_rows === 0) {
        // Không tìm thấy đơn hàng, hoặc đã có lệnh sản xuất, hoặc không cần sản xuất
        throw new Exception('Đơn hàng không tồn tại, đã có lệnh sản xuất, hoặc không cần sản xuất.');
    }

    $demand = $result_check_demand->fetch_assoc();
    $stmt_check_demand->close();

    // Kiểm tra trạng thái đơn hàng
    if ($demand['TrangThai'] !== 'Chờ sản xuất') {
        throw new Exception('Đơn hàng không ở trạng thái "Chờ Sản Xuất".');
    }

    $soYCSX = $demand['SoYCSX'];

    // 2. Tạo mã lệnh sản xuất mới (ví dụ: LSX-YYYY-NNNN)
    $year = date('Y');
    $prefix = "LSX-{$year}-";
    $sql_get_last_lsx_num = "SELECT MAX(CAST(SUBSTRING(SoLenhSX, LENGTH(?) + 1) AS UNSIGNED)) AS last_num FROM lenh_san_xuat WHERE SoLenhSX LIKE ?";
    $stmt_last_num = $conn->prepare($sql_get_last_lsx_num);
    $like_prefix = $prefix . '%';
    $stmt_last_num->bind_param("ss", $prefix, $like_prefix);
    $stmt_last_num->execute();
    $res_last_num = $stmt_last_num->get_result();
    $row_last_num = $res_last_num->fetch_assoc();
    $last_num = $row_last_num['last_num'] ?? 0;
    $new_num = $last_num + 1;
    $soLenhSX = $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    $stmt_last_num->close();

    // 3. Chèn vào bảng lenh_san_xuat
    $sql_insert_lsx = "
        INSERT INTO lenh_san_xuat (
            YCSX_ID, SoLenhSX, NgayTao, NgayHoanThanhUocTinh, TrangThai, NgayYCSX
        ) VALUES (?, ?, NOW(), ?, 'Chờ sản xuất', ?)
    ";
    $stmt_insert_lsx = $conn->prepare($sql_insert_lsx);
    $stmt_insert_lsx->bind_param("isss",
        $ycsxId,
        $soLenhSX,
        $demand['NgayHoanThanhDuKien'], // Dùng NgayHoanThanhDuKien từ đơn hàng
        $demand['NgayTao'] // Dùng NgayTao của đơn hàng làm NgayYCSX cho lệnh sản xuất
    );
    if (!$stmt_insert_lsx->execute()) {
        throw new Exception("Lỗi khi tạo lệnh sản xuất chính: " . $stmt_insert_lsx->error);
    }
    $lenhSxId = $stmt_insert_lsx->insert_id;
    $stmt_insert_lsx->close();

    // 4. Lấy chi tiết các mặt hàng cần sản xuất từ chitiet_donhang
    $sql_get_donhang_items = "
        SELECT
            ctdh.SanPhamID,
            ctdh.SoLuong AS SoLuongYeuCau,
            ctdh.MaHang,
            ctdh.TenSanPham,
            MAX(CASE WHEN a_dd.name = 'Độ dày' THEN ao_dd.value END) AS DoDayItem,
            MAX(CASE WHEN a_br.name = 'Bản rộng' THEN ao_br.value END) AS BanRongItem,
            MAX(CASE WHEN a_dk.name = 'Đường kính trong' THEN ao_dk.value END) AS DuongKinhTrongItem,
            p.HinhDang,
            v.sku_suffix AS VariantSkuSuffix
        FROM chitiet_donhang ctdh
        LEFT JOIN variants v ON ctdh.SanPhamID = v.variant_id
        LEFT JOIN products p ON v.product_id = p.product_id
        LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
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
        GROUP BY
            ctdh.ChiTiet_YCSX_ID, ctdh.SanPhamID, ctdh.SoLuong, ctdh.MaHang, ctdh.TenSanPham, p.HinhDang, v.sku_suffix
    ";
    $stmt_donhang_items = $conn->prepare($sql_get_donhang_items);
    $stmt_donhang_items->bind_param("i", $ycsxId);
    $stmt_donhang_items->execute();
    $result_donhang_items = $stmt_donhang_items->get_result();

    if ($result_donhang_items->num_rows === 0) {
        throw new Exception("Đơn hàng không có chi tiết sản phẩm để tạo lệnh sản xuất.");
    }

    while ($item = $result_donhang_items->fetch_assoc()) {
        // Chỉ xử lý các sản phẩm có Mã hàng bắt đầu bằng 'PUR'
        if (!str_starts_with(strtoupper($item['MaHang'] ?? ''), 'PUR')) {
            continue; // Bỏ qua sản phẩm nếu không phải là PUR
        }

        $sanPhamId = $item['SanPhamID'];
        $soLuongBoCanSX = (int)$item['SoLuongYeuCau'];

        $dinhMucCatValue = null;
        $soCayTuongDuong = 0;

        $hinhDangItem = $item['HinhDang'];
        $doDayItem = $item['DoDayItem'];
        $duongKinhTrongItem = $item['DuongKinhTrongItem'];
        $banRongItem = $item['BanRongItem'];
        $isPurcItem = str_starts_with(strtoupper($item['MaHang'] ?? ''), 'PUR-C');

        if ($hinhDangItem && is_numeric($duongKinhTrongItem) && is_numeric($banRongItem)) {
            $duongKinhTrongNum = (int)$duongKinhTrongItem;
            $banRongNum = (int)$banRongItem;

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

        if ($dinhMucCatValue > 0) {
            $soCayTuongDuong = $soLuongBoCanSX / $dinhMucCatValue;
            if ($isPurcItem) {
                $soCayTuongDuong /= 2;
            }
            $soCayTuongDuong = ceil($soCayTuongDuong);
        } else {
            $soCayTuongDuong = 0;
        }

        // Chèn vào bảng chitiet_lenh_san_xuat
        $sql_insert_ctlsx = "
            INSERT INTO chitiet_lenh_san_xuat (
                LenhSX_ID, SanPhamID, SoLuongBoCanSX, SoLuongCayCanSX, SoLuongCayTuongDuong, DinhMucCat
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt_insert_ctlsx = $conn->prepare($sql_insert_ctlsx);
        $stmt_insert_ctlsx->bind_param("iiiids",
            $lenhSxId,
            $sanPhamId,
            $soLuongBoCanSX,
            $soCayTuongDuong,
            $soCayTuongDuong,
            $dinhMucCatValue
        );
        if (!$stmt_insert_ctlsx->execute()) {
            throw new Exception("Lỗi khi tạo chi tiết lệnh SX cho sản phẩm {$item['TenSanPham']}: " . $stmt_insert_ctlsx->error);
        }
        $stmt_insert_ctlsx->close();
    }
    $stmt_donhang_items->close();

    // 5. Cập nhật trạng thái của đơn hàng trong bảng donhang
    $sql_update_donhang_status = "
        UPDATE donhang
        SET TrangThai = 'Đang sản xuất' -- Thay đổi trạng thái thành 'Đang sản xuất' sau khi tạo lệnh
        WHERE YCSX_ID = ?
    ";
    $stmt_update_donhang = $conn->prepare($sql_update_donhang_status);
    $stmt_update_donhang->bind_param("i", $ycsxId);
    if (!$stmt_update_donhang->execute()) {
        throw new Exception("Lỗi khi cập nhật trạng thái đơn hàng {$soYCSX}: " . $stmt_update_donhang->error);
    }
    $stmt_update_donhang->close();

    $conn->commit(); // Commit transaction nếu mọi thứ thành công
    $transactionStarted = false; // Đặt lại cờ
    echo json_encode(['success' => true, 'message' => "Đã tạo lệnh sản xuất {$soLenhSX} cho đơn hàng {$soYCSX} thành công."]);

} catch (Exception $e) {
    // Chỉ rollback nếu transaction đã được bắt đầu và chưa được commit
    if ($transactionStarted) {
        $conn->rollback();
    }
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>