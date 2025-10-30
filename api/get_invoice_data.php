<?php
// File: api/get_invoice_data.php
// Version: 1.5 - Corrected column name in ORDER BY clause to match schema

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Báo cáo lỗi để gỡ lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

function get_labels($conn) {
    $labels = [];
    $sql = "SELECT label_key, label_vi FROM quotation_labels";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $labels[$row['label_key']] = $row['label_vi'];
        }
    }
    return $labels;
}

$ycsxId = filter_input(INPUT_GET, 'ycsx_id', FILTER_VALIDATE_INT);

if (!$ycsxId) {
    echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
    exit;
}

global $conn;

try {
    // Bắt đầu bằng cách kiểm tra xem hóa đơn đã tồn tại chưa
    $stmt_check = $conn->prepare("SELECT * FROM hoadon WHERE YCSX_ID = ?");
    $stmt_check->bind_param("i", $ycsxId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $invoice_header = $result_check->fetch_assoc();
    $stmt_check->close();
    
    $hoaDonID = null;

    // Nếu hóa đơn CHƯA tồn tại, tiến hành tạo mới
    if (!$invoice_header) {
        // Bắt đầu một transaction để đảm bảo toàn vẹn dữ liệu
        $conn->begin_transaction();
        
        try {
            // Khóa các bảng cần thiết để ngăn chặn race condition, bao gồm cả các alias sẽ được sử dụng
            $conn->query("LOCK TABLES hoadon WRITE, chitiet_hoadon WRITE, donhang AS dh READ, chitiet_donhang READ, baogia READ, congty AS ct READ");

            // Tạo số hóa đơn mới một cách an toàn
            $result_max = $conn->query("SELECT MAX(CAST(SoHoaDon AS UNSIGNED)) as max_so FROM hoadon");
            $row_max = $result_max->fetch_assoc();
            $next_num = ($row_max['max_so'] ?? 0) + 1;
            $soHoaDon = str_pad($next_num, 7, '0', STR_PAD_LEFT);

            // Lấy thông tin cần thiết từ đơn hàng và báo giá
            $stmt_order_info = $conn->prepare("
                SELECT 
                    dh.NgayGiaoDuKien, 
                    dh.TenCongTy, 
                    dh.BaoGiaID,
                    ct.MaSoThue,
                    ct.DiaChi
                FROM donhang AS dh
                LEFT JOIN congty AS ct ON dh.CongTyID = ct.CongTyID
                WHERE dh.YCSX_ID = ?
            ");
            $stmt_order_info->bind_param("i", $ycsxId);
            $stmt_order_info->execute();
            $order_info = $stmt_order_info->get_result()->fetch_assoc();
            $stmt_order_info->close();

            $ngayXuat = $order_info['NgayGiaoDuKien'] ?? date('Y-m-d');
            $thueVAT_PhanTram = 8.00; // Mặc định hoặc lấy từ báo giá

            // Lấy % thuế từ báo giá gốc
            if($order_info['BaoGiaID']) {
                $stmt_bg = $conn->prepare("SELECT ThuePhanTram FROM baogia WHERE BaoGiaID = ?");
                $stmt_bg->bind_param("i", $order_info['BaoGiaID']);
                $stmt_bg->execute();
                $bg_result = $stmt_bg->get_result()->fetch_assoc();
                if($bg_result && isset($bg_result['ThuePhanTram'])) {
                    $thueVAT_PhanTram = $bg_result['ThuePhanTram'];
                }
                $stmt_bg->close();
            }

            // Insert vào bảng hoadon
            $stmt_insert_header = $conn->prepare(
                "INSERT INTO hoadon (YCSX_ID, SoHoaDon, NgayXuat, TenCongTy, MaSoThue, DiaChi, ThueVAT_PhanTram) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_insert_header->bind_param(
                "isssssd",
                $ycsxId,
                $soHoaDon,
                $ngayXuat,
                $order_info['TenCongTy'],
                $order_info['MaSoThue'],
                $order_info['DiaChi'],
                $thueVAT_PhanTram
            );
            $stmt_insert_header->execute();
            $hoaDonID = $conn->insert_id;
            $stmt_insert_header->close();

            // Lấy chi tiết đơn hàng để insert vào chi tiết hóa đơn
            $stmt_order_details = $conn->prepare("SELECT TenSanPham, SoLuong, DonGia, ThanhTien FROM chitiet_donhang WHERE DonHangID = ?");
            $stmt_order_details->bind_param("i", $ycsxId);
            $stmt_order_details->execute();
            $order_details_result = $stmt_order_details->get_result();

            $stmt_insert_item = $conn->prepare(
                "INSERT INTO chitiet_hoadon (HoaDonID, TenSanPham, DonViTinh, SoLuong, DonGia, ThanhTien) VALUES (?, ?, 'Bộ', ?, ?, ?)"
            );
            while ($item = $order_details_result->fetch_assoc()) {
                $stmt_insert_item->bind_param(
                    "isidd",
                    $hoaDonID,
                    $item['TenSanPham'],
                    $item['SoLuong'],
                    $item['DonGia'],
                    $item['ThanhTien']
                );
                $stmt_insert_item->execute();
            }
            $stmt_insert_item->close();
            $stmt_order_details->close();
            
            // Commit transaction và mở khóa bảng
            $conn->commit();
            $conn->query("UNLOCK TABLES");

        } catch (Exception $e) {
            // Nếu có lỗi, rollback và mở khóa
            $conn->rollback();
            $conn->query("UNLOCK TABLES");
            throw $e; // Ném lỗi ra ngoài để khối catch bên ngoài xử lý
        }

        // Sau khi tạo thành công, tải lại dữ liệu vừa tạo
        $stmt_refetch = $conn->prepare("SELECT * FROM hoadon WHERE HoaDonID = ?");
        $stmt_refetch->bind_param("i", $hoaDonID);
        $stmt_refetch->execute();
        $invoice_header = $stmt_refetch->get_result()->fetch_assoc();
        $stmt_refetch->close();
    }
    
    if (!$hoaDonID) {
        $hoaDonID = $invoice_header['HoaDonID'];
    }

    // Lấy chi tiết hóa đơn
    $stmt_items = $conn->prepare("SELECT * FROM chitiet_hoadon WHERE HoaDonID = ? ORDER BY ChiTietHD_ID ASC");
    $stmt_items->bind_param("i", $hoaDonID);
    $stmt_items->execute();
    $items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    // Tính toán lại tổng tiền
    $tongTienTruocThue = array_sum(array_column($items, 'ThanhTien'));
    $tienThueVAT = $tongTienTruocThue * ($invoice_header['ThueVAT_PhanTram'] / 100);
    $tongTienSauThue = $tongTienTruocThue + $tienThueVAT;

    $invoice_header['TongTienTruocThue'] = $tongTienTruocThue;
    $invoice_header['TienThueVAT'] = $tienThueVAT;
    $invoice_header['TongTienSauThue'] = $tongTienSauThue;

    // Lấy thông tin công ty từ DB
    $labels = get_labels($conn);
    $company_info = [
        'ten_cong_ty' => $labels['company_name'] ?? '',
        'ma_so_thue' => $labels['tax_code_value'] ?? '',
        'dia_chi' => $labels['company_full_address_value'] ?? '',
        'so_dien_thoai' => $labels['supplier_phone'] ?? '',
        'email' => '', // Bạn có thể thêm nhãn này nếu cần
        'so_tai_khoan' => $labels['bank_account_number'] ?? '',
        'ngan_hang' => $labels['bank_account_details_value'] ?? '',
        'mau_so' => $labels['invoice_template_num'] ?? '01GTKT0/001',
        'ky_hieu' => $labels['invoice_symbol'] ?? '3iG/25E',
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'header' => $invoice_header,
            'items' => $items,
            'company_info' => $company_info
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}

$conn->close();
?>

