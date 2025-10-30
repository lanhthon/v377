<?php
// File: api/get_chuanbi_hang_details.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$cbh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cbh_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Phiếu chuẩn bị hàng không hợp lệ.']);
    exit;
}

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    // 1. Lấy thông tin chung của phiếu và YCSX_ID
    $sql_info = "SELECT
                     cbh.*,
                     dh.YCSX_ID,
                     dh.SoYCSX,
                     dh.NgayGiaoDuKien,
                     bg.DiaChiGiaoHang,
                     bg.TenCongTy,
                     bg.NguoiNhan,
                     bg.SoDienThoaiKhach
                   FROM chuanbihang cbh
                   JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID
                   JOIN baogia bg ON cbh.BaoGiaID = bg.BaoGiaID
                   WHERE cbh.CBH_ID = ?";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("i", $cbh_id);
    $stmt_info->execute();
    $info_result = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    if (!$info_result) {
        throw new Exception("Không tìm thấy phiếu chuẩn bị hàng.");
    }

    $donhangId = $info_result['YCSX_ID'];

    // 2. Lấy chi tiết sản phẩm từ phiếu chuẩn bị hàng (chung cho sản xuất và nhập), bao gồm thông số kích thước từ bảng sanpham
    $sql_items_cbh = "SELECT
                        ctcbh.*,
                        sp.ID_ThongSo,
                        sp.DoDay,
                        sp.BanRong,
                        sp.TenSanPham,
                        lsp.TenLoai,
                        sp.NguonGoc
                      FROM chitietchuanbihang ctcbh
                      JOIN sanpham sp ON ctcbh.SanPhamID = sp.SanPhamID
                      LEFT JOIN loaisanpham lsp ON sp.LoaiID = lsp.LoaiID
                      WHERE ctcbh.CBH_ID = ?
                      ORDER BY ctcbh.ThuTuHienThi ASC";
    $stmt_items_cbh = $conn->prepare($sql_items_cbh);
    $stmt_items_cbh->bind_param("i", $cbh_id);
    $stmt_items_cbh->execute();
    $cbh_items_raw = $stmt_items_cbh->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items_cbh->close();

    // 3. Lấy chi tiết ECU cho Cùm Ula từ bảng mới (chitiet_ecu_chuanbihang)
    $ecu_for_clamp_details = [];
    $sql_ecu_clamp_details = "SELECT
                                ChiTietEcuCBH_ID,
                                TenSanPhamEcu as TenSanPham, -- Đổi tên cột để khớp với cấu trúc frontend nếu cần
                                SoLuongEcu as SoLuong,
                                DongGoiEcu as DongGoi,
                                SoKgEcu as SoKg,
                                GhiChuEcu as GhiChu
                              FROM chitiet_ecu_chuanbihang
                              WHERE CBH_ID = ?
                              ORDER BY ChiTietEcuCBH_ID ASC"; // Hoặc một cột thứ tự hiển thị khác nếu có
    $stmt_ecu_clamp_details = $conn->prepare($sql_ecu_clamp_details);
    $stmt_ecu_clamp_details->bind_param("i", $cbh_id);
    $stmt_ecu_clamp_details->execute();
    $ecu_for_clamp_details = $stmt_ecu_clamp_details->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_ecu_clamp_details->close();


    // 4. Chuẩn bị các câu lệnh để làm giàu dữ liệu (các phần này không đổi)
    $final_details = [];
    $stmt_donhang_details = $conn->prepare("SELECT SoLuong, SoLuongLayTuKho, SoLuongCanSX FROM chitiet_donhang WHERE DonHangID = ? AND SanPhamID = ?");
    $stmt_sanpham_info = $conn->prepare("SELECT SoLuongTonKho FROM sanpham WHERE SanPhamID = ?"); // NguonGoc đã lấy ở trên
    $stmt_lsx_details = $conn->prepare("SELECT ct.DinhMucCat, ct.SoLuongCayCanSX FROM chitiet_lenh_san_xuat ct JOIN lenh_san_xuat lsx ON ct.LenhSX_ID = lsx.LenhSX_ID WHERE lsx.YCSX_ID = ? AND ct.SanPhamID = ?");
    $stmt_tong_gan = $conn->prepare("SELECT SUM(cbh_detail.SoLuong) as TongDaGan FROM chitietchuanbihang cbh_detail JOIN chuanbihang cbh ON cbh_detail.CBH_ID = cbh.CBH_ID WHERE cbh_detail.SanPhamID = ? AND cbh.TrangThai NOT IN ('Đã giao hàng', 'Đã hủy', 'Hoàn thành')");

    // 5. Lặp qua từng sản phẩm chính để tính toán và làm giàu dữ liệu
    foreach ($cbh_items_raw as $item) {
        $sanPhamID = $item['SanPhamID'];

        // Lấy thông tin từ chi tiết đơn hàng gốc
        $stmt_donhang_details->bind_param("ii", $donhangId, $sanPhamID);
        $stmt_donhang_details->execute();
        $dh_details = $stmt_donhang_details->get_result()->fetch_assoc();

        // Lấy thông tin tồn kho vật lý
        $stmt_sanpham_info->bind_param("i", $sanPhamID);
        $stmt_sanpham_info->execute();
        $sp_info = $stmt_sanpham_info->get_result()->fetch_assoc();

        // Lấy thông tin từ lệnh sản xuất (nếu có)
        $stmt_lsx_details->bind_param("ii", $donhangId, $sanPhamID);
        $stmt_lsx_details->execute();
        $lsx_info = $stmt_lsx_details->get_result()->fetch_assoc();

        // Tính toán tồn kho đã gán cho các đơn khác
        // Cần điều chỉnh logic này nếu 'DaGanChoDonKhac' được tính bằng cách trừ đi số lượng của đơn hiện tại
        // Đã điều chỉnh câu truy vấn để tính tổng số lượng đã chuẩn bị cho các đơn khác (không phải đơn hiện tại và không phải trạng thái hoàn thành/hủy)
        $stmt_tong_gan->bind_param("i", $sanPhamID);
        $stmt_tong_gan->execute();
        $tongDaGan = (int)($stmt_tong_gan->get_result()->fetch_assoc()['TongDaGan'] ?? 0);
        
        // Điều chỉnh lại logic DaGanChoDonKhac: nếu số lượng hiện tại đã được "gán" thì không tính vào đây.
        // Đây là một cách đơn giản để tránh tình trạng "gán cho chính nó"
        $current_order_assigned_amount = (int)($dh_details['SoLuongLayTuKho'] ?? 0);
        $daGanChoDonKhac = $tongDaGan - $current_order_assigned_amount;
        $daGanChoDonKhac = $daGanChoDonKhac < 0 ? 0 : $daGanChoDonKhac;


        // Gộp tất cả thông tin vào một mảng
        $final_details[] = array_merge($item, [
            'SoLuongYeuCau' => $dh_details['SoLuong'] ?? 0,
            'SoLuongLayTuKho' => $dh_details['SoLuongLayTuKho'] ?? 0,
            'SoLuongCanSX' => $dh_details['SoLuongCanSX'] ?? 0,
            'TonKhoVatLy' => $sp_info['SoLuongTonKho'] ?? 0,
            'NguonGoc' => $item['NguonGoc'] ?? 'sản xuất', // Sử dụng NguonGoc đã lấy từ JOIN
            'DinhMucCat' => $lsx_info['DinhMucCat'] ?? 'N/A',
            'SoLuongCayCanSX' => $lsx_info['SoLuongCayCanSX'] ?? 0,
            'DaGanChoDonKhac' => $daGanChoDonKhac, // Giá trị đã tính toán
        ]);
    }

    // Đóng các prepared statements
    $stmt_donhang_details->close();
    $stmt_sanpham_info->close();
    $stmt_lsx_details->close();
    $stmt_tong_gan->close();

    $response['success'] = true;
    $response['data'] = [
        'info' => $info_result,
        'details' => $final_details,
        'ecu_for_clamp_details' => $ecu_for_clamp_details // Thêm dữ liệu ECU cho cùm vào phản hồi
    ];

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>