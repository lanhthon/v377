<?php
header('Content-Type: application/json');

// 1. Tải tệp cấu hình chứa hàm kết nối
require_once '../config/db_config.php';

// 2. ✅✅✅ GỌI HÀM ĐỂ LẤY KẾT NỐI VÀ GÁN VÀO BIẾN $pdo ✅✅✅
$pdo = get_db_connection();

//------------------------------------------------

$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = ['success' => false, 'message' => 'Hành động không hợp lệ.'];

// Lấy dữ liệu đầu vào dạng JSON
$input = json_decode(file_get_contents('php://input'), true);

if ($action !== 'get_all_suppliers' && $input === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $response['message'] = 'Dữ liệu gửi lên không hợp lệ (không phải JSON).';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        // --- CÁC HÀNH ĐỘNG QUẢN LÝ NHÀ CUNG CẤP (giữ nguyên) ---
        case 'get_all_suppliers':
            $stmt = $pdo->query("SELECT * FROM nhacungcap ORDER BY TenNhaCungCap ASC");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $suppliers];
            break;

        case 'add_supplier':
            if (empty($input['TenNhaCungCap'])) {
                $response['message'] = 'Tên nhà cung cấp là trường bắt buộc.';
                break;
            }
            // ... (các logic khác giữ nguyên)
            $sql = "INSERT INTO nhacungcap (TenNhaCungCap, DiaChi, SoDienThoai, Email, MaSoThue) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim($input['TenNhaCungCap']),
                trim($input['DiaChi'] ?? ''),
                trim($input['SoDienThoai'] ?? ''),
                trim($input['Email'] ?? ''),
                trim($input['MaSoThue'] ?? '')
            ]);
            $response = ['success' => true, 'message' => 'Thêm nhà cung cấp thành công.'];
            break;

        case 'update_supplier':
            if (empty($input['NhaCungCapID']) || empty($input['TenNhaCungCap'])) {
                 $response['message'] = 'ID và Tên nhà cung cấp là bắt buộc.';
                 break;
            }
             // ... (các logic khác giữ nguyên)
            $sql = "UPDATE nhacungcap SET TenNhaCungCap = ?, DiaChi = ?, SoDienThoai = ?, Email = ?, MaSoThue = ? WHERE NhaCungCapID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim($input['TenNhaCungCap']),
                trim($input['DiaChi'] ?? ''),
                trim($input['SoDienThoai'] ?? ''),
                trim($input['Email'] ?? ''),
                trim($input['MaSoThue'] ?? ''),
                $input['NhaCungCapID']
            ]);
            $response = ['success' => true, 'message' => 'Cập nhật nhà cung cấp thành công.'];
            break;

        case 'delete_supplier':
            if (empty($input['NhaCungCapID'])) {
                 $response['message'] = 'Không tìm thấy ID nhà cung cấp để xóa.';
                 break;
            }
            $sql = "DELETE FROM nhacungcap WHERE NhaCungCapID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['NhaCungCapID']]);
            $response = ['success' => true, 'message' => 'Xóa nhà cung cấp thành công.'];
            break;

        // --- MỚI: CÁC HÀNH ĐỘNG QUẢN LÝ SẢN PHẨM CỦA NHÀ CUNG CẤP ---
        case 'get_products_by_supplier':
            if (empty($_GET['supplier_id'])) {
                $response['message'] = 'Thiếu ID của nhà cung cấp.';
                break;
            }
            $stmt = $pdo->prepare("SELECT * FROM sanpham_nhacungcap WHERE NhaCungCapID = ? ORDER BY TenSanPham ASC");
            $stmt->execute([$_GET['supplier_id']]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $products];
            break;

        case 'add_supplier_product':
            if (empty($input['NhaCungCapID']) || empty($input['TenSanPham'])) {
                $response['message'] = 'ID Nhà cung cấp và Tên sản phẩm là bắt buộc.';
                break;
            }
            $sql = "INSERT INTO sanpham_nhacungcap (NhaCungCapID, MaSanPham, TenSanPham, DonViTinh, DonGia, GhiChu) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['NhaCungCapID'],
                trim($input['MaSanPham'] ?? ''),
                trim($input['TenSanPham']),
                trim($input['DonViTinh'] ?? ''),
                $input['DonGia'] ?: 0,
                trim($input['GhiChu'] ?? '')
            ]);
            $response = ['success' => true, 'message' => 'Thêm sản phẩm thành công.'];
            break;

        case 'update_supplier_product':
             if (empty($input['SanPhamNCCID']) || empty($input['TenSanPham'])) {
                $response['message'] = 'ID Sản phẩm và Tên sản phẩm là bắt buộc.';
                break;
            }
            $sql = "UPDATE sanpham_nhacungcap SET MaSanPham = ?, TenSanPham = ?, DonViTinh = ?, DonGia = ?, GhiChu = ? WHERE SanPhamNCCID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim($input['MaSanPham'] ?? ''),
                trim($input['TenSanPham']),
                trim($input['DonViTinh'] ?? ''),
                $input['DonGia'] ?: 0,
                trim($input['GhiChu'] ?? ''),
                $input['SanPhamNCCID']
            ]);
             $response = ['success' => true, 'message' => 'Cập nhật sản phẩm thành công.'];
            break;

        case 'delete_supplier_product':
             if (empty($input['SanPhamNCCID'])) {
                $response['message'] = 'Không tìm thấy ID sản phẩm để xóa.';
                break;
            }
            $sql = "DELETE FROM sanpham_nhacungcap WHERE SanPhamNCCID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['SanPhamNCCID']]);
            $response = ['success' => true, 'message' => 'Xóa sản phẩm thành công.'];
            break;
    }
} catch (PDOException $e) {
    // ... (phần xử lý lỗi giữ nguyên)
    if ($e->getCode() == '23000') {
        $response['message'] = 'Lỗi CSDL: Dữ liệu bị trùng lặp. Vui lòng kiểm tra lại Tên hoặc Mã số thuế.';
    } else {
        $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>