<?php
/**
 * File: api/create_stock_production_order.php
 * Version: 4.0 (Nâng cấp) - Hỗ trợ sản xuất song song 2 loại BTP và ULA trong cùng một lệnh lưu kho.
 * Description: API tạo lệnh sản xuất "Lưu Kho" (LK) với logic tính ngày hoàn thành chi tiết, linh hoạt theo mã sản phẩm.
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

global $conn;

// Hàm trợ giúp để tìm năng suất cho BTP (CV/CT) dựa trên đường kính
function getNangSuatTheoDuongKinh($duongKinh, $nangSuatConfig) {
    $duongKinhSo = intval(preg_replace('/[^0-9]/', '', $duongKinh));
    if ($duongKinhSo <= 0) return 0;

    foreach ($nangSuatConfig as $range => $nangSuat) {
        list($min, $max) = explode('-', $range);
        if ($duongKinhSo >= (int)$min && $duongKinhSo <= (int)$max) {
            return (float)$nangSuat;
        }
    }
    return 0; // Trả về 0 nếu không tìm thấy khoảng phù hợp
}

$transactionStarted = false;

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        throw new Exception('Bạn chưa đăng nhập. Vui lòng đăng nhập để thực hiện chức năng này.');
    }
    $nguoiYeuCauID = $_SESSION['user_id'];

    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        http_response_code(400);
        throw new Exception('Dữ liệu không hợp lệ. Cần có danh sách sản phẩm.');
    }
    
    $items_to_produce = $data['items'];
    $loaiLSX = 'LK';

    if ($conn->connect_error) {
        throw new Exception('Lỗi kết nối CSDL: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $conn->begin_transaction();
    $transactionStarted = true;

    // --- [LOGIC MỚI] Tính ngày hoàn thành dựa trên 2 nhóm sản phẩm chạy song song ---
    
    // [THAY ĐỔI] Lấy thêm cấu hình số chuyền song song
    $stmt_config = $conn->prepare("SELECT TenThietLap, GiaTriThietLap FROM cauhinh_sanxuat WHERE TenThietLap IN ('NangSuatMaGoiPU', 'NangSuatUla', 'GioLamViecMoiNgay', 'NgayNghiLe', 'SoChuyenSongSong_BTP', 'SoChuyenSongSong_ULA')");
    $stmt_config->execute();
    $result_config = $stmt_config->get_result();
    $configs = [];
    while ($row = $result_config->fetch_assoc()) {
        $configs[$row['TenThietLap']] = $row['GiaTriThietLap'];
    }
    $stmt_config->close();

    $nangSuatPuConfig = json_decode($configs['NangSuatMaGoiPU'] ?? '{}', true);
    $nangSuatUlaConfig = json_decode($configs['NangSuatUla'] ?? '{}', true);
    $gioLamViecMoiNgay = (float)($configs['GioLamViecMoiNgay'] ?? 8);
    $ngayNghiLe = json_decode($configs['NgayNghiLe'] ?? '[]', true);

    // [MỚI] Lấy số chuyền cho từng loại
    $soChuyenSongSong_BTP = (int)($configs['SoChuyenSongSong_BTP'] ?? 1);
    $soChuyenSongSong_ULA = (int)($configs['SoChuyenSongSong_ULA'] ?? 1);
    if ($soChuyenSongSong_BTP <= 0) $soChuyenSongSong_BTP = 1;
    if ($soChuyenSongSong_ULA <= 0) $soChuyenSongSong_ULA = 1;


    if ((empty($nangSuatPuConfig) && empty($nangSuatUlaConfig)) || $gioLamViecMoiNgay <= 0) {
        throw new Exception("Chưa cấu hình năng suất chi tiết (NangSuatMaGoiPU, NangSuatUla) hoặc giờ làm việc.");
    }

    // [MỚI] Tạo 2 danh sách giờ riêng biệt cho từng loại sản phẩm
    $btp_hours_list = [];
    $ula_hours_list = [];
    
    $sql_attr = "
        SELECT ao.value 
        FROM variant_attributes va
        JOIN attribute_options ao ON va.option_id = ao.option_id
        JOIN attributes a ON ao.attribute_id = a.attribute_id
        WHERE va.variant_id = ? AND a.name = ?
        LIMIT 1
    ";
    $stmt_attr = $conn->prepare($sql_attr);
    $stmt_get_sku = $conn->prepare("SELECT variant_sku FROM variants WHERE variant_id = ?");

    foreach ($items_to_produce as $item) {
        $sanPhamId = (int)$item['productId'];
        $soLuongCan = (float)$item['quantity'];
        if ($soLuongCan <= 0) continue;
        
        $stmt_get_sku->bind_param("i", $sanPhamId);
        $stmt_get_sku->execute();
        $sku_result = $stmt_get_sku->get_result();
        $sku_row = $sku_result->fetch_assoc();
        if (!$sku_row) throw new Exception("Không tìm thấy sản phẩm có ID: {$sanPhamId}.");
        
        $variant_sku = $sku_row['variant_sku'];
        $nangSuatTheoNgay = 0;
        $attributeName = '';
        $attr_value = '';

        $isBTP = strpos($variant_sku, 'CV') === 0 || strpos($variant_sku, 'CT') === 0;
        $isULA = strpos($variant_sku, 'ULA') === 0;

        if ($isBTP) {
            $attributeName = 'Đường kính trong';
            $stmt_attr->bind_param("is", $sanPhamId, $attributeName);
            $stmt_attr->execute();
            $attr_result = $stmt_attr->get_result();
            $attr_row = $attr_result->fetch_assoc();
            if (!$attr_row || empty($attr_row['value'])) throw new Exception("Không tìm thấy thuộc tính '{$attributeName}' cho sản phẩm {$variant_sku}.");
            
            $attr_value = $attr_row['value'];
            $nangSuatTheoNgay = getNangSuatTheoDuongKinh($attr_value, $nangSuatPuConfig);

        } elseif ($isULA) {
            $attributeName = 'Kích thước ren';
            $stmt_attr->bind_param("is", $sanPhamId, $attributeName);
            $stmt_attr->execute();
            $attr_result = $stmt_attr->get_result();
            $attr_row = $attr_result->fetch_assoc();
            if (!$attr_row || empty($attr_row['value'])) throw new Exception("Không tìm thấy thuộc tính '{$attributeName}' cho sản phẩm {$variant_sku}.");

            $attr_value = $attr_row['value'];
            $nangSuatTheoNgay = (float)($nangSuatUlaConfig[$attr_value] ?? 0);
        } else {
            continue; // Bỏ qua nếu không phải loại sản phẩm cần tính toán
        }

        if ($nangSuatTheoNgay <= 0) {
            throw new Exception("Năng suất không hợp lệ (<=0) cho sản phẩm {$variant_sku} với thuộc tính '{$attributeName}' giá trị '{$attr_value}'. Vui lòng kiểm tra cấu hình sản xuất");
        }

        $nangSuatTheoGio = $nangSuatTheoNgay / $gioLamViecMoiNgay;
        $hours_for_item = $soLuongCan / $nangSuatTheoGio;
        
        // [THAY ĐỔI] Thêm giờ vào danh sách tương ứng
        if ($isBTP) {
            $btp_hours_list[] = $hours_for_item;
        } elseif ($isULA) {
            $ula_hours_list[] = $hours_for_item;
        }
    }
    $stmt_attr->close();

    // [MỚI] Phân phối công việc cho từng nhóm và tìm ra thời gian dài nhất
    $total_hours_btp = 0;
    if (!empty($btp_hours_list)) {
        $chuyenSanXuatBTP = array_fill(0, $soChuyenSongSong_BTP, 0);
        rsort($btp_hours_list);
        foreach ($btp_hours_list as $hours) {
            $chuyenItViecNhat = array_keys($chuyenSanXuatBTP, min($chuyenSanXuatBTP))[0];
            $chuyenSanXuatBTP[$chuyenItViecNhat] += $hours;
        }
        $total_hours_btp = max($chuyenSanXuatBTP);
    }

    $total_hours_ula = 0;
    if (!empty($ula_hours_list)) {
        $chuyenSanXuatULA = array_fill(0, $soChuyenSongSong_ULA, 0);
        rsort($ula_hours_list);
        foreach ($ula_hours_list as $hours) {
            $chuyenItViecNhat = array_keys($chuyenSanXuatULA, min($chuyenSanXuatULA))[0];
            $chuyenSanXuatULA[$chuyenItViecNhat] += $hours;
        }
        $total_hours_ula = max($chuyenSanXuatULA);
    }

    // Thời gian tổng cộng là thời gian của nhóm nào lâu hơn
    $total_hours_needed = ceil(max($total_hours_btp, $total_hours_ula));


    if ($total_hours_needed <= 0) {
        throw new Exception("Không có sản phẩm hợp lệ nào để sản xuất hoặc năng suất bằng 0.");
    }
    
    $current_date = new DateTime("now", new DateTimeZone('Asia/Ho_Chi_Minh'));
    $hours_remaining = $total_hours_needed;

    while ($hours_remaining > 0) {
        $dayOfWeek = (int)$current_date->format('N');
        $dateString = $current_date->format('Y-m-d');
        
        // Giữ nguyên logic làm việc từ T2-T6 của file gốc
        if ($dayOfWeek <= 5 && !in_array($dateString, $ngayNghiLe)) {
            $hours_remaining -= $gioLamViecMoiNgay;
        }
        
        if ($hours_remaining > 0) {
            $current_date->modify('+1 day');
        }
    }
    $ngayHoanThanhUocTinh = $current_date->format('Y-m-d');

    // --- Tạo mã Lệnh sản xuất mới ---
    $year = date('Y');
    $prefix = "LSX-{$year}-{$loaiLSX}-";
    $stmt_last_num = $conn->prepare("SELECT MAX(CAST(SUBSTRING(SoLenhSX, LENGTH(?) + 1) AS UNSIGNED)) AS last_num FROM lenh_san_xuat WHERE SoLenhSX LIKE ?");
    $like_prefix = $prefix . '%';
    $stmt_last_num->bind_param("ss", $prefix, $like_prefix);
    $stmt_last_num->execute();
    $last_num = $stmt_last_num->get_result()->fetch_assoc()['last_num'] ?? 0;
    $soLenhSX = $prefix . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    $stmt_last_num->close();

    // --- Chèn bản ghi chính vào `lenh_san_xuat` ---
    $stmt_insert_lsx = $conn->prepare("
        INSERT INTO lenh_san_xuat (YCSX_ID, SoLenhSX, NgayTao, NgayHoanThanhUocTinh, TrangThai, LoaiLSX, NguoiYeuCau_ID) 
        VALUES (NULL, ?, NOW(), ?, 'Chờ duyệt', ?, ?)
    ");
    $stmt_insert_lsx->bind_param("sssi", $soLenhSX, $ngayHoanThanhUocTinh, $loaiLSX, $nguoiYeuCauID);
    if (!$stmt_insert_lsx->execute()) {
        throw new Exception("Lỗi khi tạo lệnh sản xuất chính: " . $stmt_insert_lsx->error);
    }
    $lenhSxId = $stmt_insert_lsx->insert_id;
    $stmt_insert_lsx->close();

    // --- Chèn chi tiết sản phẩm ---
    $stmt_insert_ctlsx = $conn->prepare("
        INSERT INTO chitiet_lenh_san_xuat (LenhSX_ID, SanPhamID, SoLuongBoCanSX, SoLuongCayCanSX, TrangThai)
        VALUES (?, ?, ?, ?, 'Mới')
    ");
    foreach ($items_to_produce as $item) {
        $sanPhamId = (int)$item['productId'];
        $soLuongCan = (float)$item['quantity'];
        
        $stmt_get_sku->bind_param("i", $sanPhamId);
        $stmt_get_sku->execute();
        $sku_result = $stmt_get_sku->get_result();
        $sku_row = $sku_result->fetch_assoc();
        $variant_sku = $sku_row['variant_sku'];
        
        $soLuongBo = 0;
        $soLuongCay = 0;

        if (strpos($variant_sku, 'ULA') === 0) {
            $soLuongBo = $soLuongCan;
        } else {
            // Mặc định còn lại là cây (BTP)
            $soLuongCay = $soLuongCan;
        }
        
        // Chỉ chèn những sản phẩm có số lượng > 0
        if ($soLuongCan > 0) {
            $stmt_insert_ctlsx->bind_param("iidd", $lenhSxId, $sanPhamId, $soLuongBo, $soLuongCay);
            if (!$stmt_insert_ctlsx->execute()) {
                throw new Exception("Lỗi khi tạo chi tiết LSX cho sản phẩm ID {$sanPhamId}: " . $stmt_insert_ctlsx->error);
            }
        }
    }
    $stmt_insert_ctlsx->close();
    $stmt_get_sku->close();
    
    // --- Hoàn tất ---
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Đã tạo Lệnh sản xuất Lưu kho '{$soLenhSX}' thành công."]);

} catch (Exception $e) {
    if ($transactionStarted && isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    // Đảm bảo mã lỗi HTTP được đặt trước khi trả về JSON
    if (http_response_code() === 200) {
        http_response_code(500);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>