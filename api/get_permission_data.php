<?php
// File: api/get_permission_data.php
// Lấy tất cả dữ liệu cần thiết cho trang quản lý người dùng và phân quyền

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [
    'success' => false,
    'users' => [],
    'roles' => [],
    'functions' => [],
    'permissions' => []
];

try {
    // 1. Lấy tất cả người dùng và vai trò của họ
    $users_result = $conn->query("SELECT UserID, HoTen, ChucVu, SoDienThoai, TenDangNhap, Email, MaVaiTro, TrangThai FROM nguoidung ORDER BY HoTen");
    while($row = $users_result->fetch_assoc()) {
        $response['users'][] = $row;
    }
    
    // 2. Lấy tất cả vai trò với thông tin thêm về số người dùng
    $roles_result = $conn->query("
        SELECT v.MaVaiTro, v.TenVaiTro, v.MoTa, 
               COUNT(n.UserID) as SoNguoiDung
        FROM vaitro v
        LEFT JOIN nguoidung n ON v.MaVaiTro = n.MaVaiTro
        GROUP BY v.MaVaiTro, v.TenVaiTro, v.MoTa
        ORDER BY v.MaVaiTro
    ");
    while($row = $roles_result->fetch_assoc()) {
        $response['roles'][] = $row;
    }

    // 3. Lấy tất cả chức năng (bao gồm ParentMaChucNang để hiển thị thụt lề)
    $functions_result = $conn->query("
        SELECT MaChucNang, TenChucNang, ParentMaChucNang, ThuTuHienThi 
        FROM chucnang 
        ORDER BY 
            CASE WHEN ParentMaChucNang IS NULL THEN MaChucNang ELSE ParentMaChucNang END,
            ParentMaChucNang IS NOT NULL,
            ThuTuHienThi,
            TenChucNang
    ");
    while($row = $functions_result->fetch_assoc()) {
        $response['functions'][] = $row;
    }

    // 4. Lấy các quyền hiện tại
    $permissions_result = $conn->query("SELECT MaVaiTro, MaChucNang FROM vaitro_chucnang");
    $permissions_map = [];
    while($row = $permissions_result->fetch_assoc()) {
        if (!isset($permissions_map[$row['MaVaiTro']])) {
            $permissions_map[$row['MaVaiTro']] = [];
        }
        $permissions_map[$row['MaVaiTro']][] = $row['MaChucNang'];
    }
    $response['permissions'] = $permissions_map;

    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>