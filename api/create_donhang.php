<?php
// Báo cho client biết rằng nội dung trả về là JSON
header('Content-Type: application/json');

// Nạp tệp cấu hình và kết nối cơ sở dữ liệu
require_once '../config/database.php'; 

global $conn;

// Kiểm tra kết nối cơ sở dữ liệu
if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

// Lấy dữ liệu đầu vào từ yêu cầu POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Kiểm tra xem BaoGiaID có được cung cấp không
if (!isset($data['baoGiaID'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu BaoGiaID.']);
    exit;
}

$baoGiaID = (int)$data['baoGiaID'];

try {
    // Bắt đầu một giao dịch để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();

    // 1. Kiểm tra xem báo giá có tồn tại và đã được xử lý để tạo đơn hàng chưa
    $stmt = $conn->prepare("SELECT TrangThai FROM baogia WHERE BaoGiaID = ?");
    $stmt->bind_param("i", $baoGiaID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Báo giá không tồn tại.']);
        exit;
    }
    $quoteRow = $result->fetch_assoc();
    $baoGiaStatus = $quoteRow['TrangThai'];
    $stmt->close();

    // Ngăn chặn việc tạo đơn hàng trùng lặp từ cùng một báo giá
    if ($baoGiaStatus === 'Đã tạo đơn hàng') {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Báo giá này đã được dùng để tạo đơn hàng trước đó.']);
        exit;
    }
    
    // 2. Lấy thông tin đầy đủ của báo giá
    $stmt = $conn->prepare("SELECT * FROM baogia WHERE BaoGiaID = ?");
    $stmt->bind_param("i", $baoGiaID);
    $stmt->execute();
    $quoteInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Lấy tất cả các mục chi tiết của báo giá
    $quoteItems = [];
    $stmt = $conn->prepare("SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY ThuTuHienThi ASC");
    $stmt->bind_param("i", $baoGiaID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $quoteItems[] = $row;
    }
    $stmt->close();

    if (empty($quoteItems)) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Báo giá không có sản phẩm nào để tạo đơn hàng.']);
        exit;
    }

    // 3. Tạo số yêu cầu sản xuất (Mã đơn hàng)
    $currentYear = date('y');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM donhang WHERE YEAR(NgayTao) = ?");
    $year = date('Y');
    $stmt->bind_param("s", $year);
    
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $newOrderNumber = $count + 1;
    $soYCSX = "DH" . $currentYear . str_pad($newOrderNumber, 5, '0', STR_PAD_LEFT);

    // 4. Tính toán các ngày quan trọng
    $totalProductionUnits = 0;
    foreach ($quoteItems as $item) {
        $totalProductionUnits += ($item['SoLuong'] ?? 0); 
    }
    
    // Lấy năng suất và ngày nghỉ lễ từ bảng `cauhinh_sanxuat`
    $nangSuatLK = 500; // Giá trị mặc định
    $ngayNghiLe = []; // Mảng ngày nghỉ lễ
    
    // Lấy cấu hình năng suất LK
    $stmt_nangSuat = $conn->prepare("SELECT GiaTriThietLap FROM cauhinh_sanxuat WHERE TenThietLap = 'NangSuatLK'");
    $stmt_nangSuat->execute();
    $result_nangSuat = $stmt_nangSuat->get_result();
    if ($result_nangSuat->num_rows > 0) {
        $row_nangSuat = $result_nangSuat->fetch_assoc();
        $nangSuatLK = (int)$row_nangSuat['GiaTriThietLap'];
    }
    $stmt_nangSuat->close();
    
    // Lấy danh sách ngày nghỉ lễ
    $stmt_nghiLe = $conn->prepare("SELECT GiaTriThietLap FROM cauhinh_sanxuat WHERE TenThietLap = 'NgayNghiLe'");
    $stmt_nghiLe->execute();
    $result_nghiLe = $stmt_nghiLe->get_result();
    if ($result_nghiLe->num_rows > 0) {
        $row_nghiLe = $result_nghiLe->fetch_assoc();
        $ngayNghiLe = json_decode($row_nghiLe['GiaTriThietLap'], true) ?? [];
    }
    $stmt_nghiLe->close();

    // Hàm kiểm tra ngày có phải là ngày nghỉ không (cuối tuần hoặc ngày lễ)
    function isNonWorkingDay($date, $holidays) {
        // Kiểm tra cuối tuần (6 = Thứ 7, 7 = Chủ Nhật)
        if (in_array($date->format('N'), [6, 7])) {
            return true;
        }
        
        // Kiểm tra ngày nghỉ lễ
        $dateString = $date->format('Y-m-d');
        if (in_array($dateString, $holidays)) {
            return true;
        }
        
        return false;
    }

    $productionDays = $nangSuatLK > 0 ? ceil($totalProductionUnits / $nangSuatLK) : 0;
    $ngayTao = new DateTime();
    $ngayHoanThanhDuKien = clone $ngayTao;
    
    // Cộng ngày sản xuất, bỏ qua cuối tuần và ngày nghỉ lễ
    $daysAdded = 0;
    while ($daysAdded < $productionDays) {
        $ngayHoanThanhDuKien->modify('+1 day');
        
        // Nếu không phải ngày nghỉ, tăng counter
        if (!isNonWorkingDay($ngayHoanThanhDuKien, $ngayNghiLe)) {
            $daysAdded++;
        }
    }
    $ngayHoanThanhDuKienFormatted = $ngayHoanThanhDuKien->format('Y-m-d');

    // --- PHẦN BỊ THAY ĐỔI --- //
    // Tính ngày giao dự kiến từ chuỗi thoigiangiao
    $ngayGiaoDuKien = clone $ngayTao;
    $deliveryDays = 0;

    if (isset($quoteInfo['ThoiGianGiaoHang']) && !empty($quoteInfo['ThoiGianGiaoHang'])) {
        // Sử dụng biểu thức chính quy để tìm tất cả các số trong chuỗi
        preg_match_all('/\d+/', $quoteInfo['ThoiGianGiaoHang'], $matches);
        
        if (!empty($matches[0])) {
            // Lấy số lớn nhất trong các số tìm được để đảm bảo thời gian giao hàng đủ dài
            $deliveryDays = max($matches[0]);
        }
    }

    // Cộng số ngày giao hàng, bỏ qua cuối tuần và ngày nghỉ lễ
    $deliveryDaysAdded = 0;
    while ($deliveryDaysAdded < $deliveryDays) {
        $ngayGiaoDuKien->modify('+1 day');
        
        // Nếu không phải ngày nghỉ, tăng counter
        if (!isNonWorkingDay($ngayGiaoDuKien, $ngayNghiLe)) {
            $deliveryDaysAdded++;
        }
    }
    $ngayGiaoDuKienFormatted = $ngayGiaoDuKien->format('Y-m-d');
    // --- KẾT THÚC PHẦN THAY ĐỔI --- //

    // 5. Chèn dữ liệu vào bảng `donhang`
    $sql_donhang = "INSERT INTO donhang (
                            BaoGiaID, CongTyID, NguoiLienHeID, DuAnID, TenCongTy, NguoiNhan, TenDuAn, SoYCSX,
                            NgayTao, NgayGiaoDuKien, NgayHoanThanhDuKien, TrangThai, NeedsProduction, NguoiBaoGia,
                            DiaChiGiaoHang, DieuKienThanhToan, TongTien
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_donhang = $conn->prepare($sql_donhang);

    $needsProduction = 0; // Sẽ được cập nhật sau
    $initialOrderStatus = 'Chờ xử lý';

    $stmt_donhang->bind_param("iiiisssssssisssd",
        $baoGiaID,
        $quoteInfo['CongTyID'],
        $quoteInfo['NguoiLienHeID'],
        $quoteInfo['DuAnID'],
        $quoteInfo['TenCongTy'],
        $quoteInfo['NguoiNhan'],
        $quoteInfo['TenDuAn'],
        $soYCSX,
        $ngayGiaoDuKienFormatted,
        $ngayHoanThanhDuKienFormatted,
        $initialOrderStatus,
        $needsProduction,
        $quoteInfo['NguoiBaoGia'],
        $quoteInfo['DiaChiGiaoHang'],
        $quoteInfo['DieuKienThanhToan'],
        $quoteInfo['TongTienSauThue']
    );
    $stmt_donhang->execute();
    $donHangID = $conn->insert_id;
    $stmt_donhang->close();

    // 6. Chèn các mục chi tiết vào bảng `chitiet_donhang`
    $stmt_chitiet = $conn->prepare("INSERT INTO chitiet_donhang (
                                            DonHangID, SanPhamID, MaHang, TenSanPham, SoLuong, DonGia, ThanhTien,
                                            SoLuongLayTuKho, SoLuongCanSX, GhiChu, ThuTuHienThi, TenNhom,
                                            ID_ThongSo, DoDay, BanRong
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($quoteItems as $item) {
        $soLuongLayTuKho = 0; // Logic kiểm tra tồn kho có thể được thêm ở đây
        $soLuongCanSX = $item['SoLuong'] - $soLuongLayTuKho;

        if ($soLuongCanSX > 0) {
            $needsProduction = 1; // Đánh dấu đơn hàng này cần sản xuất
        }

        $stmt_chitiet->bind_param("iisssddiissssss",
            $donHangID,
            $item['variant_id'],
            $item['MaHang'],
            $item['TenSanPham'],
            $item['SoLuong'],
            $item['DonGia'],
            $item['ThanhTien'],
            $soLuongLayTuKho,
            $soLuongCanSX,
            $item['GhiChu'],
            $item['ThuTuHienThi'],
            $item['TenNhom'],
            $item['ID_ThongSo'],
            $item['DoDay'],
            $item['ChieuRong'] // Ánh xạ từ cột ChieuRong trong chitietbaogia
        );
        $stmt_chitiet->execute();
    }
    $stmt_chitiet->close();

    // Cập nhật lại đơn hàng nếu nó thực sự cần sản xuất
    if ($needsProduction === 1) {
        $stmt_update_order = $conn->prepare("UPDATE donhang SET NeedsProduction = 1, TrangThai = 'Đang chờ xử lý' WHERE YCSX_ID = ?");
        $stmt_update_order->bind_param("i", $donHangID);
        $stmt_update_order->execute();
        $stmt_update_order->close();
    }

    // 7. Cập nhật trạng thái của báo giá thành 'Chốt' để không tạo lại đơn hàng
    $stmt_update_quote_status = $conn->prepare("UPDATE baogia SET TrangThai = 'Chốt' WHERE BaoGiaID = ?");
    $stmt_update_quote_status->bind_param("i", $baoGiaID);
    $stmt_update_quote_status->execute();
    $stmt_update_quote_status->close();

    // Nếu mọi thứ thành công, xác nhận giao dịch
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng mới đã được tạo thành công!',
        'donHangID' => $donHangID,
        'soYCSX' => $soYCSX
    ]);

} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, hủy bỏ tất cả các thay đổi
    if ($conn) {
        $conn->rollback();
    }
    // Ghi lại lỗi và thông báo cho người dùng
    error_log("Lỗi trong create_donhang.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage()]);
}
?>