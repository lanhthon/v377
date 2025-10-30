<?php
// api/export_invoice_pdf.php
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // Đường dẫn đến file autoload của mPDF

// --- Lấy dữ liệu hóa đơn (logic tương tự get_invoice_data.php) ---
$ycsx_id = isset($_GET['ycsx_id']) ? (int)$_GET['ycsx_id'] : 0;
if ($ycsx_id === 0) {
    die("YCSX ID không hợp lệ.");
}

global $conn;
$invoice_data = null;
try {
    // Luôn bắt đầu transaction để đảm bảo tính toàn vẹn khi tạo hóa đơn mới
    $conn->begin_transaction();

    // 1. Kiểm tra hóa đơn đã tồn tại chưa
    $stmt = $conn->prepare("SELECT * FROM hoadon WHERE YCSX_ID = ?");
    $stmt->bind_param("i", $ycsx_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $hoadon = $result->fetch_assoc();
        $hoadon_id = $hoadon['HoaDonID'];
        $stmt_ct = $conn->prepare("SELECT * FROM chitiet_hoadon WHERE HoaDonID = ?");
        $stmt_ct->bind_param("i", $hoadon_id);
        $stmt_ct->execute();
        $chitiet = $stmt_ct->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_ct->close();
        $invoice_data = ['header' => $hoadon, 'items' => $chitiet];
    } else {
        // 2. Nếu chưa, tạo hóa đơn mới
        $query_dh = "SELECT dh.TongTien, ct.TenCongTy, ct.MaSoThue, ct.DiaChi, bg.ThuePhanTram FROM donhang dh LEFT JOIN congty ct ON dh.CongTyID = ct.CongTyID LEFT JOIN baogia bg ON dh.BaoGiaID = bg.BaoGiaID WHERE dh.YCSX_ID = ?";
        $stmt_dh = $conn->prepare($query_dh);
        $stmt_dh->bind_param("i", $ycsx_id);
        $stmt_dh->execute();
        $donhang = $stmt_dh->get_result()->fetch_assoc();
        if(!$donhang) throw new Exception("Không tìm thấy đơn hàng.");

        // Tạo số hóa đơn
        $year = date('Y');
        $stmt_max = $conn->prepare("SELECT MAX(SoHoaDon) FROM hoadon WHERE SoHoaDon LIKE ?");
        $prefix = "HD-{$year}-%";
        $stmt_max->bind_param("s", $prefix);
        $stmt_max->execute();
        $max_so = null;
        $stmt_max->bind_result($max_so);
        $stmt_max->fetch();
        $stmt_max->close();
        $next_id = $max_so ? (int)substr($max_so, -4) + 1 : 1;
        $so_hoa_don = sprintf("HD-%d-%04d", $year, $next_id);
        $ngay_xuat = date('Y-m-d');

        // Tính toán thuế
        $thue_phan_tram = $donhang['ThuePhanTram'] ?? 8.00;
        $tong_tien_sau_thue = (float)$donhang['TongTien'];
        $tong_tien_truoc_thue = round($tong_tien_sau_thue / (1 + ($thue_phan_tram / 100)));
        $tien_thue = $tong_tien_sau_thue - $tong_tien_truoc_thue;

        // Insert vào hoadon
        $sql_insert_hd = "INSERT INTO hoadon (YCSX_ID, SoHoaDon, NgayXuat, TenCongTy, MaSoThue, DiaChi, TongTienTruocThue, ThueVAT_PhanTram, TienThueVAT, TongTienSauThue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_hd);
        $stmt_insert->bind_param("isssssdddd", $ycsx_id, $so_hoa_don, $ngay_xuat, $donhang['TenCongTy'], $donhang['MaSoThue'], $donhang['DiaChi'], $tong_tien_truoc_thue, $thue_phan_tram, $tien_thue, $tong_tien_sau_thue);
        $stmt_insert->execute();
        $hoadon_id = $conn->insert_id;

        // Insert vào chitiet_hoadon
        $stmt_ctdh = $conn->prepare("SELECT TenSanPham, SoLuong, DonGia, ThanhTien, DonViTinh FROM chitietbaogia WHERE BaoGiaID = (SELECT BaoGiaID FROM donhang WHERE YCSX_ID = ?)");
        $stmt_ctdh->bind_param("i", $ycsx_id);
        $stmt_ctdh->execute();
        $chitiet_donhang = $stmt_ctdh->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $sql_insert_cthd = "INSERT INTO chitiet_hoadon (HoaDonID, TenSanPham, DonViTinh, SoLuong, DonGia, ThanhTien) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_cthd = $conn->prepare($sql_insert_cthd);
        foreach ($chitiet_donhang as $item) {
            $stmt_cthd->bind_param("issidd", $hoadon_id, $item['TenSanPham'], $item['DonViTinh'], $item['SoLuong'], $item['DonGia'], $item['ThanhTien']);
            $stmt_cthd->execute();
        }

        // Cập nhật ngày xuất HĐ vào bảng công nợ
        $sql_update_cn = "INSERT INTO quanly_congno (YCSX_ID, NgayXuatHoaDon) VALUES (?, ?) ON DUPLICATE KEY UPDATE NgayXuatHoaDon = VALUES(NgayXuatHoaDon)";
        $stmt_cn = $conn->prepare($sql_update_cn);
        $stmt_cn->bind_param("is", $ycsx_id, $ngay_xuat);
        $stmt_cn->execute();
        
        $conn->commit();

        $hoadon_moi = $conn->query("SELECT * FROM hoadon WHERE HoaDonID = $hoadon_id")->fetch_assoc();
        $invoice_data = ['header' => $hoadon_moi, 'items' => $chitiet_donhang];
    }

    if (!$invoice_data) {
        throw new Exception("Không thể lấy hoặc tạo dữ liệu hóa đơn.");
    }

    // --- Tạo file PDF ---
    $data = $invoice_data; // Biến $data được sử dụng trong template
    ob_start();
    include __DIR__ . '/../templates/invoice_pdf_template.php';
    $html = ob_get_clean();

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp'
    ]);
    
    $mpdf->WriteHTML($html);
    
    // Header để trình duyệt hiển thị file PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="HoaDon_'.$data['header']['SoHoaDon'].'.pdf"');
    $mpdf->Output();

} catch (Exception $e) {
    if ($conn->in_transaction) {
        $conn->rollback();
    }
    // Trả về lỗi thay vì file PDF
    header('Content-Type: text/plain');
    die('Lỗi khi tạo PDF: ' . $e->getMessage());
}
?>
