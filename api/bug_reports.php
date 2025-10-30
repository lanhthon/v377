<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$userID = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            getBugReports($conn, $userID);
            break;
        case 'create':
            createBugReport($conn, $userID);
            break;
        case 'add_comment':
            addComment($conn, $userID);
            break;
        case 'update_status':
            updateStatus($conn, $userID);
            break;
        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

/**
 * Cấu hình file được phép upload
 */
function getAllowedFileTypes() {
    return [
        // Images
        'image/jpeg' => ['ext' => ['jpg', 'jpeg'], 'maxSize' => 5 * 1024 * 1024], // 5MB
        'image/png' => ['ext' => ['png'], 'maxSize' => 5 * 1024 * 1024],
        'image/gif' => ['ext' => ['gif'], 'maxSize' => 5 * 1024 * 1024],
        'image/webp' => ['ext' => ['webp'], 'maxSize' => 5 * 1024 * 1024],
        
        // Documents
        'application/pdf' => ['ext' => ['pdf'], 'maxSize' => 10 * 1024 * 1024], // 10MB
        'application/msword' => ['ext' => ['doc'], 'maxSize' => 10 * 1024 * 1024],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['ext' => ['docx'], 'maxSize' => 10 * 1024 * 1024],
        
        // Spreadsheets
        'application/vnd.ms-excel' => ['ext' => ['xls'], 'maxSize' => 10 * 1024 * 1024],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['ext' => ['xlsx'], 'maxSize' => 10 * 1024 * 1024],
        'text/csv' => ['ext' => ['csv'], 'maxSize' => 5 * 1024 * 1024],
        
        // Text
        'text/plain' => ['ext' => ['txt'], 'maxSize' => 2 * 1024 * 1024], // 2MB
        
        // Archives
        'application/zip' => ['ext' => ['zip'], 'maxSize' => 20 * 1024 * 1024], // 20MB
        'application/x-rar-compressed' => ['ext' => ['rar'], 'maxSize' => 20 * 1024 * 1024],
        'application/x-rar' => ['ext' => ['rar'], 'maxSize' => 20 * 1024 * 1024],
        'application/x-7z-compressed' => ['ext' => ['7z'], 'maxSize' => 20 * 1024 * 1024],
        
        // Video
        'video/mp4' => ['ext' => ['mp4'], 'maxSize' => 50 * 1024 * 1024], // 50MB
        'video/quicktime' => ['ext' => ['mov'], 'maxSize' => 50 * 1024 * 1024],
    ];
}

/**
 * Lấy danh sách báo lỗi của user
 */
function getBugReports($conn, $userID) {
    $isAdmin = isAdmin($conn, $userID);
    
    if ($isAdmin) {
        $sql = "SELECT 
                    br.BugReportID,
                    br.UserID,
                    br.Title,
                    br.Description,
                    br.ImagePath,
                    br.Status,
                    br.Priority,
                    br.AdminNote,
                    br.ResolvedAt,
                    br.ResolvedBy,
                    br.CreatedAt,
                    br.UpdatedAt,
                    u.HoTen as UserName
                FROM bug_reports br
                JOIN nguoidung u ON br.UserID = u.UserID
                ORDER BY 
                    FIELD(br.Status, 'Khẩn cấp', 'Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã đóng'),
                    FIELD(br.Priority, 'Khẩn cấp', 'Cao', 'Trung bình', 'Thấp'),
                    br.CreatedAt DESC";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT 
                    br.BugReportID,
                    br.UserID,
                    br.Title,
                    br.Description,
                    br.ImagePath,
                    br.Status,
                    br.Priority,
                    br.AdminNote,
                    br.ResolvedAt,
                    br.ResolvedBy,
                    br.CreatedAt,
                    br.UpdatedAt
                FROM bug_reports br
                WHERE br.UserID = ?
                ORDER BY br.CreatedAt DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userID);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $row['Comments'] = getComments($conn, $row['BugReportID']);
        $reports[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $reports,
        'isAdmin' => $isAdmin
    ]);
}

/**
 * Lấy comments cho một báo lỗi
 */
function getComments($conn, $bugReportID) {
    $sql = "SELECT 
                c.CommentID,
                c.Comment,
                c.ImagePath,
                c.CreatedAt,
                c.UserID,
                u.HoTen,
                u.MaVaiTro,
                CASE WHEN u.MaVaiTro IN ('admin', 'manager') THEN 1 ELSE 0 END as IsAdmin
            FROM bug_report_comments c
            JOIN nguoidung u ON c.UserID = u.UserID
            WHERE c.BugReportID = ?
            ORDER BY c.CreatedAt ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bugReportID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    $stmt->close();
    return $comments;
}

/**
 * Tạo báo lỗi mới với multi-file upload
 */
function createBugReport($conn, $userID) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'Trung bình';
    
    if (empty($title) || empty($description)) {
        throw new Exception('Vui lòng điền đầy đủ thông tin');
    }
    
    // Validate độ dài
    if (strlen($title) > 255) {
        throw new Exception('Tiêu đề quá dài (tối đa 255 ký tự)');
    }
    
    // Xử lý upload nhiều file
    $filePaths = [];
    $uploadErrors = [];
    
    if (isset($_FILES['files']) && is_array($_FILES['files']['error'])) {
        $fileCount = count($_FILES['files']['error']);
        
        // Giới hạn số lượng file
        if ($fileCount > 10) {
            throw new Exception('Chỉ được upload tối đa 10 file');
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] == 0) {
                $uploadResult = uploadFile(
                    $_FILES['files']['tmp_name'][$i],
                    $_FILES['files']['name'][$i],
                    $_FILES['files']['size'][$i]
                );
                
                if ($uploadResult['success']) {
                    $filePaths[] = $uploadResult['path'];
                } else {
                    $uploadErrors[] = $uploadResult['error'];
                }
            }
        }
    }
    
    // Nếu có lỗi upload, thông báo
    if (!empty($uploadErrors)) {
        throw new Exception('Lỗi upload file: ' . implode(', ', $uploadErrors));
    }
    
    $filePathString = !empty($filePaths) ? implode(',', $filePaths) : null;
    
    // Chèn vào database
    $sql = "INSERT INTO bug_reports (UserID, Title, Description, ImagePath, Priority, Status) 
            VALUES (?, ?, ?, ?, ?, 'Mới')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $userID, $title, $description, $filePathString, $priority);
    
    if ($stmt->execute()) {
        $reportID = $conn->insert_id;
        $stmt->close();
        
        // Gửi thông báo cho admin
        notifyAdmin($conn, $reportID, $title, $userID);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã gửi báo lỗi thành công',
            'report_id' => $reportID,
            'files_uploaded' => count($filePaths)
        ]);
    } else {
        // Xóa các file đã upload nếu insert database thất bại
        foreach ($filePaths as $path) {
            if (file_exists('../' . $path)) {
                unlink('../' . $path);
            }
        }
        throw new Exception('Không thể tạo báo lỗi: ' . $stmt->error);
    }
}

/**
 * Thêm comment vào báo lỗi (hỗ trợ file đính kèm)
 */
function addComment($conn, $userID) {
    $reportID = intval($_POST['report_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($comment) || $reportID <= 0) {
        throw new Exception('Thông tin không hợp lệ');
    }
    
    // Kiểm tra quyền truy cập
    $isAdmin = isAdmin($conn, $userID);
    
    if (!$isAdmin) {
        $checkSql = "SELECT BugReportID FROM bug_reports WHERE BugReportID = ? AND UserID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $reportID, $userID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows == 0) {
            throw new Exception('Không có quyền truy cập báo lỗi này');
        }
        $checkStmt->close();
    }
    
    // Xử lý upload file (1 file cho comment)
    $filePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadResult = uploadFile(
            $_FILES['image']['tmp_name'],
            $_FILES['image']['name'],
            $_FILES['image']['size']
        );
        
        if ($uploadResult['success']) {
            $filePath = $uploadResult['path'];
        }
    }
    
    // Thêm comment
    $sql = "INSERT INTO bug_report_comments (BugReportID, UserID, Comment, ImagePath) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $reportID, $userID, $comment, $filePath);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Cập nhật thời gian UpdatedAt
        $updateSql = "UPDATE bug_reports SET UpdatedAt = CURRENT_TIMESTAMP WHERE BugReportID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $reportID);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Nếu là admin comment, tự động chuyển trạng thái
        if ($isAdmin) {
            $statusSql = "UPDATE bug_reports SET Status = 'Đã tiếp nhận' WHERE BugReportID = ? AND Status = 'Mới'";
            $statusStmt = $conn->prepare($statusSql);
            $statusStmt->bind_param("i", $reportID);
            $statusStmt->execute();
            $statusStmt->close();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm bình luận'
        ]);
    } else {
        throw new Exception('Không thể thêm bình luận: ' . $stmt->error);
    }
}

/**
 * Cập nhật trạng thái (chỉ admin)
 */
function updateStatus($conn, $userID) {
    if (!isAdmin($conn, $userID)) {
        throw new Exception('Không có quyền thực hiện');
    }
    
    $reportID = intval($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');
    
    $validStatuses = ['Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã đóng'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Trạng thái không hợp lệ');
    }
    
    $sql = "UPDATE bug_reports SET Status = ?, AdminNote = ?";
    $params = [$status, $adminNote];
    $types = "ss";
    
    if ($status === 'Đã giải quyết') {
        $sql .= ", ResolvedAt = CURRENT_TIMESTAMP, ResolvedBy = ?";
        $params[] = $userID;
        $types .= "i";
    }
    
    $sql .= " WHERE BugReportID = ?";
    $params[] = $reportID;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái'
        ]);
    } else {
        throw new Exception('Không thể cập nhật trạng thái');
    }
}

/**
 * Upload file với validation đầy đủ
 */
function uploadFile($tmpName, $originalName, $fileSize) {
    $uploadDir = '../uploads/bug_reports/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Lấy MIME type thực tế của file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    // Kiểm tra loại file có được phép không
    $allowedTypes = getAllowedFileTypes();
    
    if (!isset($allowedTypes[$mimeType])) {
        return [
            'success' => false,
            'error' => "File '$originalName' không được hỗ trợ (MIME: $mimeType)"
        ];
    }
    
    $fileConfig = $allowedTypes[$mimeType];
    
    // Kiểm tra kích thước file
    if ($fileSize > $fileConfig['maxSize']) {
        $maxSizeMB = round($fileConfig['maxSize'] / 1024 / 1024, 1);
        return [
            'success' => false,
            'error' => "File '$originalName' vượt quá kích thước cho phép ({$maxSizeMB}MB)"
        ];
    }
    
    // Kiểm tra extension
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $fileConfig['ext'])) {
        return [
            'success' => false,
            'error' => "Extension '$extension' không hợp lệ cho file '$originalName'"
        ];
    }
    
    // Sanitize filename - loại bỏ ký tự đặc biệt
    $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    
    // Generate unique filename
    $fileName = uniqid() . '_' . time() . '_' . $safeOriginalName . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    // Di chuyển file
    if (move_uploaded_file($tmpName, $targetPath)) {
        // Set quyền file an toàn
        chmod($targetPath, 0644);
        
        return [
            'success' => true,
            'path' => 'uploads/bug_reports/' . $fileName
        ];
    }
    
    return [
        'success' => false,
        'error' => "Không thể upload file '$originalName'"
    ];
}

/**
 * Kiểm tra user có phải admin không
 */
function isAdmin($conn, $userID) {
    $sql = "SELECT MaVaiTro FROM nguoidung WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return in_array($row['MaVaiTro'], ['admin', 'manager']);
    }
    
    $stmt->close();
    return false;
}

/**
 * Gửi thông báo cho admin
 */
function notifyAdmin($conn, $reportID, $title, $fromUserID) {
    // Lấy thông tin user gửi báo cáo
    $userSql = "SELECT HoTen FROM nguoidung WHERE UserID = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $fromUserID);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userName = 'Người dùng';
    if ($userRow = $userResult->fetch_assoc()) {
        $userName = $userRow['HoTen'];
    }
    $userStmt->close();
    
    // Tạo notification
    // TODO: Implement email notification hoặc in-app notification
    
    // Log activity
    error_log("New bug report #$reportID from $userName: $title");
}

/**
 * Xóa file cũ khi cập nhật
 */
function deleteOldFiles($filePaths) {
    if (empty($filePaths)) return;
    
    $files = explode(',', $filePaths);
    foreach ($files as $path) {
        $fullPath = '../' . trim($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
?>