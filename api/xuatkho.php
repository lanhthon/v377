<?php
// api/xuatkho.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Lấy dữ liệu từ body của request (dùng cho việc lưu)
$input_data = file_get_contents('php://input');
$decoded_data = json_decode($input_data, true);

// Khởi tạo $decoded_data là mảng rỗng nếu không có dữ liệu để tránh lỗi
if (!is_array($decoded_data)) {
    $decoded_data = [];
}

// Xác định hành động: ưu tiên từ POST body (cho việc lưu), sau đó mới đến GET (cho việc tải)
$action = isset($decoded_data['action']) ? $decoded_data['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'get_xuatkho_details':
        // Hàm này sẽ chỉ dùng $_GET
        handleGetXuatKhoDetails($conn);
        break;
    case 'save_xuatkho':
        // Hàm này sẽ dùng dữ liệu từ POST body
        handleSaveXuatKho($conn, $decoded_data);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        break;
}

/**
 * Lấy chi tiết phiếu để hiển thị lên trang tạo/xem.
 * Hàm này CHỈ sử dụng phương thức GET.
 */
function handleGetXuatKhoDetails($conn) {
    // Luôn đọc ID từ tham số trên URL ($_GET)
    $cbh_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $pxk_id = isset($_GET['pxk_id']) ? (int)$_GET['pxk_id'] : 0;
    
    $response = ['success' => false, 'data' => null, 'message' => ''];

    try {
        if ($pxk_id > 0) {
            // Trường hợp 1: Xem lại phiếu xuất kho đã được tạo
            $stmt_pxk = $conn->prepare("SELECT pxk.*, dh.SoYCSX, cbh.SoCBH FROM phieuxuatkho pxk LEFT JOIN donhang dh ON pxk.YCSX_ID = dh.YCSX_ID LEFT JOIN chuanbihang cbh ON pxk.CBH_ID = cbh.CBH_ID WHERE pxk.PhieuXuatKhoID = ?");
            $stmt_pxk->bind_param("i", $pxk_id);
            $stmt_pxk->execute();
            $phieu_xuat_kho = $stmt_pxk->get_result()->fetch_assoc();
            $stmt_pxk->close();

            if ($phieu_xuat_kho) {
                $response['data']['phieu_xuat_kho'] = $phieu_xuat_kho;
                
                $stmt_items = $conn->prepare("SELECT * FROM chitiet_phieuxuatkho WHERE PhieuXuatKhoID = ? ORDER BY ChiTietPXK_ID");
                $stmt_items->bind_param("i", $pxk_id);
                $stmt_items->execute();
                $response['data']['main_items'] = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_items->close();

                $response['data']['extra_items'] = [];
                $response['success'] = true;
            } else {
                $response['message'] = "Không tìm thấy Phiếu Xuất Kho với ID: $pxk_id.";
            }

        } elseif ($cbh_id > 0) {
            // Trường hợp 2: Tạo phiếu xuất kho mới từ phiếu chuẩn bị hàng
            $stmt_cbh = $conn->prepare("SELECT cbh.*, dh.SoYCSX FROM chuanbihang cbh JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID WHERE cbh.CBH_ID = ?");
            $stmt_cbh->bind_param("i", $cbh_id);
            $stmt_cbh->execute();
            $chuan_bi_hang = $stmt_cbh->get_result()->fetch_assoc();
            $stmt_cbh->close();

            if ($chuan_bi_hang) {
                $response['data']['chuan_bi_hang'] = $chuan_bi_hang;

                $stmt_main = $conn->prepare("SELECT * FROM chitietchuanbihang WHERE CBH_ID = ? ORDER BY ThuTuHienThi");
                $stmt_main->bind_param("i", $cbh_id);
                $stmt_main->execute();
                $response['data']['main_items'] = $stmt_main->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_main->close();

                $stmt_extra = $conn->prepare("SELECT * FROM chitiet_ecu_cbh WHERE CBH_ID = ?");
                $stmt_extra->bind_param("i", $cbh_id);
                $stmt_extra->execute();
                $response['data']['extra_items'] = $stmt_extra->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt_extra->close();
                
                $response['success'] = true;
            } else {
                $response['message'] = "Không tìm thấy Phiếu Chuẩn Bị Hàng với ID: $cbh_id.";
            }
        } else {
            // Lỗi này xảy ra khi URL không có tham số id hoặc pxk_id
            $response['message'] = 'Yêu cầu không chứa ID hợp lệ (thiếu tham số "id" hoặc "pxk_id").';
        }
    } catch (Exception $e) {
        http_response_code(500);
        $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * Lưu phiếu xuất kho vào CSDL.
 * Hàm này CHỈ sử dụng dữ liệu từ POST body.
 */
function handleSaveXuatKho($conn, $data) {
    $response = ['success' => false, 'message' => ''];
    session_start();
    $nguoi_tao_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if (!$nguoi_tao_id) {
        $response['message'] = 'Lỗi phiên đăng nhập.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    $cbh_id = isset($data['cbh_id']) ? $data['cbh_id'] : null;
    $so_phieu_xuat = isset($data['soPhieuXuat']) ? $data['soPhieuXuat'] : '';
    $items = isset($data['items']) ? $data['items'] : [];

    if (empty($cbh_id) || empty($so_phieu_xuat) || empty($items)) {
        $response['message'] = 'Dữ liệu không hợp lệ (thiếu ID, số phiếu hoặc sản phẩm).';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt_get_ids = $conn->prepare("SELECT YCSX_ID FROM chuanbihang WHERE CBH_ID = ?");
        $stmt_get_ids->bind_param("i", $cbh_id);
        $stmt_get_ids->execute();
        $ids = $stmt_get_ids->get_result()->fetch_assoc();
        $ycsx_id = isset($ids['YCSX_ID']) ? $ids['YCSX_ID'] : null;
        $stmt_get_ids->close();
        if (!$ycsx_id) throw new Exception("Không tìm thấy đơn hàng tương ứng với CBH ID: $cbh_id.");

        $stmt_pxk = $conn->prepare("INSERT INTO phieuxuatkho (YCSX_ID, CBH_ID, SoPhieuXuat, NgayXuat, NguoiNhan, GhiChu, NguoiTaoID, DiaChiGiaoHang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_pxk->bind_param("iissssis", $ycsx_id, $cbh_id, $so_phieu_xuat, $data['ngayXuat'], $data['nguoiNhan'], $data['ghiChu'], $nguoi_tao_id, $data['diaChiGiaoHang']);
        $stmt_pxk->execute();
        $pxk_id = $conn->insert_id;
        $stmt_pxk->close();
        if ($pxk_id == 0) throw new Exception("Tạo phiếu xuất kho thất bại.");

        $stmt_item = $conn->prepare("INSERT INTO chitiet_phieuxuatkho (PhieuXuatKhoID, SanPhamID, MaHang, TenSanPham, SoLuongYeuCau, SoLuongThucXuat, TaiSo, GhiChu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_update_inv = $conn->prepare("UPDATE variant_inventory SET quantity = quantity - ? WHERE variant_id = ?");

        foreach ($items as $item) {
            $sl_thuc_xuat = isset($item['soLuongThucXuat']) ? (int)$item['soLuongThucXuat'] : 0;
            if ($sl_thuc_xuat > 0 && !empty($item['sanPhamID'])) {
                $stmt_item->bind_param("iississs", $pxk_id, $item['sanPhamID'], $item['maHang'], $item['tenSanPham'], $item['soLuongYeuCau'], $sl_thuc_xuat, $item['taiSo'], $item['ghiChu']);
                $stmt_item->execute();
                
                $stmt_update_inv->bind_param("ii", $sl_thuc_xuat, $item['sanPhamID']);
                $stmt_update_inv->execute();
            }
        }
        $stmt_item->close();
        $stmt_update_inv->close();

        $stmt_update_cbh = $conn->prepare("UPDATE chuanbihang SET TrangThai = 'Đã xuất kho' WHERE CBH_ID = ?");
        $stmt_update_cbh->bind_param("i", $cbh_id);
        $stmt_update_cbh->execute();
        $stmt_update_cbh->close();

        $stmt_update_dh = $conn->prepare("UPDATE donhang SET TrangThai = 'Đã xuất kho', PXK_ID = ? WHERE YCSX_ID = ?");
        $stmt_update_dh->bind_param("ii", $pxk_id, $ycsx_id);
        $stmt_update_dh->execute();
        $stmt_update_dh->close();

        $conn->commit();
        $response['success'] = true;
        $response['message'] = 'Phiếu xuất kho đã được lưu thành công!';
        $response['pxk_id'] = $pxk_id;
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        $response['message'] = 'Lỗi khi lưu phiếu xuất kho: ' . $e->getMessage();
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>