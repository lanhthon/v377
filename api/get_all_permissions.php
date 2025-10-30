<?php
// File: api/get_all_permissions.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [
    'success' => false,
    'roles' => [],
    'functions' => [],
    'permissions' => []
];

try {
    // 1. Lấy tất cả vai trò
    $roles_result = $conn->query("SELECT MaVaiTro, TenVaiTro FROM vaitro ORDER BY MaVaiTro");
    while($row = $roles_result->fetch_assoc()) {
        $response['roles'][] = $row;
    }

    // 2. Lấy tất cả chức năng
    $functions_result = $conn->query("SELECT MaChucNang, TenChucNang FROM chucnang ORDER BY TenChucNang");
    while($row = $functions_result->fetch_assoc()) {
        $response['functions'][] = $row;
    }

    // 3. Lấy các quyền hiện tại
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