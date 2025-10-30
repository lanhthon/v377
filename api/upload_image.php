<?php
header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => 'Lỗi không xác định.'];

// Kiểm tra xem có file nào được gửi lên không
if (isset($_FILES['imageFile'])) {
    $uploadDir = '../uploads/'; // Đường dẫn tới thư mục lưu file
    $file = $_FILES['imageFile'];

    // Kiểm tra lỗi upload
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Kiểm tra loại file (chỉ cho phép ảnh)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            $response['message'] = 'Lỗi: Chỉ cho phép tải lên file ảnh (JPG, PNG, GIF).';
        } 
        // Kiểm tra dung lượng file (ví dụ: tối đa 5MB)
        else if ($fileSize > 5 * 1024 * 1024) {
            $response['message'] = 'Lỗi: Kích thước file không được vượt quá 5MB.';
        } 
        else {
            // Tạo tên file mới, duy nhất để tránh bị ghi đè
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('img_', true) . '.' . strtolower($fileExtension);
            $destination = $uploadDir . $newFileName;

            // Di chuyển file từ thư mục tạm vào thư mục uploads
            if (move_uploaded_file($fileTmpName, $destination)) {
                $response['success'] = true;
                $response['message'] = 'Tải file thành công!';
                // Trả về đường dẫn tương đối để JS có thể sử dụng
                $response['filePath'] = 'uploads/' . $newFileName; 
            } else {
                $response['message'] = 'Lỗi: Không thể di chuyển file.';
            }
        }
    } else {
        $response['message'] = 'Lỗi khi tải file: ' . $file['error'];
    }
} else {
    $response['message'] = 'Không có file nào được chọn.';
}

echo json_encode($response);
?>