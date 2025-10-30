<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php'; // Adjust path as needed

$response = ['success' => false, 'categories' => []];

try {
    $stmt = $conn->prepare("SELECT LoaiID, TenLoai FROM loaisanpham"); // Assuming ThuTuHienThi for order
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['categories'][] = [
            'categoryId' => $row['LoaiID'],
            'categoryName' => $row['TenLoai']
        ];
    }
    $stmt->close();
    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi khi tải danh mục sản phẩm: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>