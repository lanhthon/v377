<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$conn->begin_transaction();

try {
    // Cập nhật thông tin chung của phiếu
    $info = $data['info'];
    $sql_info = "UPDATE chuanbihang SET NgayGiao=?, DangKiCongTruong=?, QuyCachThung=?, LoaiXe=?, XeGrap=?, XeTai=?, SoLaiXe=?, PhuTrach=? WHERE CBH_ID=?";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("ssssssssi", 
        $info['ngayGiao'], $info['dangKiCongTruong'], $info['quyCachThung'], $info['loaiXe'], 
        $info['xeGrap'], $info['xeTai'], $info['soLaiXe'], $info['phuTrach'], $info['cbhId']
    );
    $stmt_info->execute();
    $stmt_info->close();

    // Cập nhật chi tiết từng sản phẩm
    $details = $data['details'];
    $sql_detail = "UPDATE chitietchuanbihang SET SoThung=?, TonKho=?, CayCat=?, DongGoi=?, DatThem=?, SoKg=?, GhiChu=? WHERE ChiTietCBH_ID=?";
    $stmt_detail = $conn->prepare($sql_detail);
    
    foreach ($details as $item) {
        $stmt_detail->bind_param("sisssdsi",
            $item['soThung'], $item['tonKho'], $item['cayCat'], $item['dongGoi'], 
            $item['datThem'], $item['soKg'], $item['ghiChu'], $item['chiTietId']
        );
        $stmt_detail->execute();
    }
    $stmt_detail->close();

    // Cập nhật trạng thái đơn hàng thành "Chờ giao hàng"
    $ycsx_id = $data['ycsxId'];
    $new_status = 'Chờ giao hàng';
    $stmt_update_status = $conn->prepare("UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?");
    $stmt_update_status->bind_param("si", $new_status, $ycsx_id);
    $stmt_update_status->execute();
    $stmt_update_status->close();


    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Đã lưu thông tin chuẩn bị hàng thành công!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()]);
}

$conn->close();
?>