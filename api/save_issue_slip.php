<?php
// File: api/save_issue_slip.php

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Adjust path if necessary

$response = ['success' => false, 'message' => '', 'soPhieu' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $ycsxId = isset($input['ycsx_id']) ? (int)$input['ycsx_id'] : 0;
    $nguoiNhan = $input['nguoi_nhan'] ?? '';
    $items = $input['items'] ?? [];
    $ghiChu = $input['ghi_chu'] ?? ''; // Assuming you might add a general note field later

    if ($ycsxId <= 0 || empty($items)) {
        http_response_code(400);
        $response['message'] = 'Dữ liệu không hợp lệ: ID đơn hàng hoặc danh sách sản phẩm trống.';
        echo json_encode($response);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 1. Generate SoPhieuXuat
        // Logic để tạo số phiếu mới (ví dụ: PXK-YYYYMMDD-001)
        $today = date('Y-m-d');
        $prefix = "PXK-" . date('Ymd');
        $sql_count = "SELECT COUNT(*) FROM phieuxuatkho WHERE NgayXuat = ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("s", $today);
        $stmt_count->execute();
        $count = $stmt_count->get_result()->fetch_row()[0];
        $stmt_count->close();
        $soPhieuXuat = $prefix . "-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        // 2. Insert into phieuxuatkho
        // Lấy UserID của người tạo phiếu (giả sử có session hoặc gửi từ frontend)
        // For now, let's assume a default UserID or fetch from session
        $nguoiTaoID = 1; // CHANGE THIS: Replace with actual logged-in UserID (e.g., $_SESSION['user_id'])

        $sql_insert_pxk = "INSERT INTO phieuxuatkho (YCSX_ID, SoPhieuXuat, NgayXuat, NguoiNhan, GhiChu, NguoiTaoID)
                           VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_pxk = $conn->prepare($sql_insert_pxk);
        if (!$stmt_insert_pxk) {
            throw new Exception("Lỗi chuẩn bị câu lệnh insert phieuxuatkho: " . $conn->error);
        }
        $currentDate = date('Y-m-d');
        $stmt_insert_pxk->bind_param("issssi", $ycsxId, $soPhieuXuat, $currentDate, $nguoiNhan, $ghiChu, $nguoiTaoID);
        $stmt_insert_pxk->execute();
        $phieuXuatKhoID = $conn->insert_id;
        $stmt_insert_pxk->close();

        if (!$phieuXuatKhoID) {
            throw new Exception("Không thể tạo phiếu xuất kho chính.");
        }

        // 3. Insert into chitiet_phieuxuatkho and update sanpham.SoLuongTonKho
        // Modified to handle SanPhamID being null for ECU items
        $sql_insert_ctpxk = "INSERT INTO chitiet_phieuxuatkho (PhieuXuatKhoID, SanPhamID, SoLuongYeuCau, SoLuongThucXuat, TaiSo, DonViTinh, GhiChu)
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_ctpxk = $conn->prepare($sql_insert_ctpxk);
        if (!$stmt_insert_ctpxk) {
            throw new Exception("Lỗi chuẩn bị câu lệnh insert chitiet_phieuxuatkho: " . $conn->error);
        }

        $sql_update_tonkho = "UPDATE sanpham SET SoLuongTonKho = SoLuongTonKho - ? WHERE SanPhamID = ?";
        $stmt_update_tonkho = $conn->prepare($sql_update_tonkho);
        if (!$stmt_update_tonkho) {
            throw new Exception("Lỗi chuẩn bị câu lệnh update tonkho: " . $conn->error);
        }

        $sql_insert_lichsu = "INSERT INTO lichsunhapxuat (SanPhamID, NgayGiaoDich, LoaiGiaoDich, SoLuongThayDoi, SoLuongSauGiaoDich, MaThamChieu, GhiChu)
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_lichsu = $conn->prepare($sql_insert_lichsu);
        if (!$stmt_insert_lichsu) {
            throw new Exception("Lỗi chuẩn bị câu lệnh insert lichsunhapxuat: " . $conn->error);
        }

        foreach ($items as $item) {
            $sanPhamID = isset($item['sanpham_id']) ? (int)$item['sanpham_id'] : null; // Can be null for ECU
            $isEcu = filter_var($item['is_ecu'], FILTER_VALIDATE_BOOLEAN); // Get the boolean flag
            
            $soLuongYeuCau = (int)$item['so_luong_yc'];
            $soLuongThucXuat = (int)$item['so_luong_xuat'];
            $taiSo = $item['tai_so'] ?? null;
            $donViTinh = $item['dvt'] ?? 'Bộ'; // Default 'Bộ', but will be 'Cái' for ECU from frontend
            $ghiChuItem = $item['ghi_chu'] ?? null;

            // Bind SanPhamID for ctpxk. If it's null, bind_param should handle it as NULL.
            // For integer type in bind_param, you might need to use `NULL` keyword or cast it carefully if the column is nullable.
            // With "s" type for integer, it might try to convert 'NULL' string. 'i' for null requires special handling or nullable column.
            // A simpler way with nullable column is to use `bind_param("iisss", ...)` for integers,
            // but if SanPhamID is always int, null is tricky.
            // Let's assume SanPhamID column in chitiet_phieuxuatkho allows NULL for now.
            // Or you can convert null to 0 if your DB design requires it, but 0 might map to a real product.
            // It's safer to keep it NULL if the column is nullable.
            
            if ($sanPhamID === null) {
                $stmt_insert_ctpxk->bind_param("iisisss",
                    $phieuXuatKhoID, $sanPhamID, $soLuongYeuCau, $soLuongThucXuat, $taiSo, $donViTinh, $ghiChuItem
                );
            } else {
                 $stmt_insert_ctpxk->bind_param("iiiisss",
                    $phieuXuatKhoID, $sanPhamID, $soLuongYeuCau, $soLuongThucXuat, $taiSo, $donViTinh, $ghiChuItem
                );
            }
           
            $stmt_insert_ctpxk->execute();


            // CHỈ CẬP NHẬT TỒN KHO VÀ GHI LỊCH SỬ NẾU KHÔNG PHẢI LÀ ECU (có SanPhamID hợp lệ và isEcu là false)
            if (!$isEcu && $sanPhamID !== null) {
                // Update sanpham.SoLuongTonKho
                $stmt_update_tonkho->bind_param("ii", $soLuongThucXuat, $sanPhamID);
                $stmt_update_tonkho->execute();
                if ($stmt_update_tonkho->affected_rows === 0) {
                    // This might indicate an issue if product doesn't exist or quantity is already zero/negative
                    // Consider throwing an error or logging
                }
                
                // Record in lichsunhapxuat
                // Get current SoLuongTonKho after update for lichsunhapxuat
                $sql_current_tonkho = "SELECT SoLuongTonKho FROM sanpham WHERE SanPhamID = ?";
                $stmt_current_tonkho = $conn->prepare($sql_current_tonkho);
                $stmt_current_tonkho->bind_param("i", $sanPhamID);
                $stmt_current_tonkho->execute();
                $currentTonKho = $stmt_current_tonkho->get_result()->fetch_row()[0];
                $stmt_current_tonkho->close();

                $loaiGiaoDich = 'XUAT_KHO';
                $soLuongThayDoi = -$soLuongThucXuat; // Âm vì là xuất
                $maThamChieu = $soPhieuXuat;

                $stmt_insert_lichsu->bind_param("isiiiis",
                    $sanPhamID, $currentDate, $loaiGiaoDich, $soLuongThayDoi, $currentTonKho, $maThamChieu, $ghiChuItem
                );
                $stmt_insert_lichsu->execute();
            }
        }
        $stmt_insert_ctpxk->close();
        $stmt_update_tonkho->close(); // Close only if it was prepared and used
        $stmt_insert_lichsu->close(); // Close only if it was prepared and used


        // 4. Update donhang.TrangThai to 'Đã giao hàng' (or 'Đã xuất kho' if that's the next step)
        $sql_update_donhang_status = "UPDATE donhang SET TrangThai = ? WHERE YCSX_ID = ?";
        $stmt_update_donhang_status = $conn->prepare($sql_update_donhang_status);
        if (!$stmt_update_donhang_status) {
            throw new Exception("Lỗi chuẩn bị câu lệnh cập nhật trạng thái đơn hàng: " . $conn->error);
        }
        $newDonHangStatus = 'Đã giao hàng'; // Or 'Đã xuất kho' depending on your workflow
        $stmt_update_donhang_status->bind_param("si", $newDonHangStatus, $ycsxId);
        $stmt_update_donhang_status->execute();
        $stmt_update_donhang_status->close();


        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Phiếu xuất kho đã được tạo và tồn kho đã cập nhật thành công!';
        $response['soPhieu'] = $soPhieuXuat;
        $response['phieuXuatKhoID'] = $phieuXuatKhoID; // Return new PXK ID
        

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        $response['message'] = 'Lỗi trong quá trình tạo phiếu xuất kho: ' . $e->getMessage();
    }

} else {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Yêu cầu không hợp lệ. Chỉ chấp nhận phương thức POST.';
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>