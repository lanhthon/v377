<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'message' => ''];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    $response['message'] = 'ID công ty là bắt buộc.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$companyId = $data['id'];

// Start transaction
$conn->begin_transaction();

try {
    // 1. Delete associated contacts
    $sql_delete_contacts = "DELETE FROM nguoilienhe WHERE CongTyID = ?";
    $stmt_contacts = $conn->prepare($sql_delete_contacts);
    if ($stmt_contacts === false) {
        throw new Exception("Lỗi prepare (xóa liên hệ): " . $conn->error);
    }
    $stmt_contacts->bind_param("i", $companyId);
    $stmt_contacts->execute();
    $stmt_contacts->close();

    // 2. Delete the company
    $sql_delete_company = "DELETE FROM congty WHERE CongTyID = ?";
    $stmt_company = $conn->prepare($sql_delete_company);
    if ($stmt_company === false) {
        throw new Exception("Lỗi prepare (xóa công ty): " . $conn->error);
    }
    $stmt_company->bind_param("i", $companyId);
    $stmt_company->execute();

    if ($stmt_company->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Xóa công ty và các liên hệ thành công!';
    } else {
        throw new Exception("Không tìm thấy công ty để xóa.");
    }
    
    $stmt_company->close();

    // Commit transaction
    $conn->commit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>