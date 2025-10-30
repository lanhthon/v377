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

// Kiểm tra quyền admin
if (!isAdmin($conn, $userID)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_all';

try {
    switch ($action) {
        case 'get_all':
            getAllBugs($conn);
            break;
        case 'update_status':
            updateBugStatus($conn, $userID);
            break;
        case 'add_comment':
            addComment($conn, $userID);
            break;
        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();

/**
 * Lấy tất cả báo lỗi (Admin)
 */
function getAllBugs($conn) {
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
                u.HoTen as UserName,
                u.Email as UserEmail,
                u.SoDienThoai as UserPhone
            FROM bug_reports br
            JOIN nguoidung u ON br.UserID = u.UserID
            ORDER BY 
                FIELD(br.Status, 'Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã đóng'),
                FIELD(br.Priority, 'Khẩn cấp', 'Cao', 'Trung bình', 'Thấp'),
                br.CreatedAt DESC";
    
    $result = $conn->query($sql);
    
    $reports = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Lấy comments cho mỗi báo lỗi
            $row['Comments'] = getComments($conn, $row['BugReportID']);
            $reports[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reports
    ]);
}

/**
 * Lấy comments
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
 * Cập nhật trạng thái và thông tin báo lỗi
 */
function updateBugStatus($conn, $userID) {
    $reportID = intval($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $priority = $_POST['priority'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');
    
    $validStatuses = ['Mới', 'Đã tiếp nhận', 'Đang xử lý', 'Đã giải quyết', 'Đã đóng'];
    $validPriorities = ['Thấp', 'Trung bình', 'Cao', 'Khẩn cấp'];
    
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Trạng thái không hợp lệ');
    }
    
    if (!in_array($priority, $validPriorities)) {
        throw new Exception('Mức ưu tiên không hợp lệ');
    }
    
    $sql = "UPDATE bug_reports SET 
                Status = ?, 
                Priority = ?,
                AdminNote = ?";
    $params = [$status, $priority, $adminNote];
    $types = "sss";
    
    // Nếu chuyển sang "Đã giải quyết", lưu thông tin
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
        
        // Tạo thông báo tự động cho user
        if ($status === 'Đã giải quyết' || $status === 'Đã đóng') {
            notifyUser($conn, $reportID, $status);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật thành công'
        ]);
    } else {
        throw new Exception('Không thể cập nhật: ' . $stmt->error);
    }
}

/**
 * Thêm comment (từ admin)
 */
function addComment($conn, $userID) {
    $reportID = intval($_POST['report_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($comment) || $reportID <= 0) {
        throw new Exception('Thông tin không hợp lệ');
    }
    
    // Xử lý upload ảnh
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imagePath = uploadImage($_FILES['image']['tmp_name'], $_FILES['image']['name']);
    }
    
    // Thêm comment
    $sql = "INSERT INTO bug_report_comments (BugReportID, UserID, Comment, ImagePath) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $reportID, $userID, $comment, $imagePath);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Cập nhật thời gian UpdatedAt
        $updateSql = "UPDATE bug_reports SET UpdatedAt = CURRENT_TIMESTAMP WHERE BugReportID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $reportID);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Tự động chuyển trạng thái nếu đang là "Mới"
        $statusSql = "UPDATE bug_reports SET Status = 'Đã tiếp nhận' 
                     WHERE BugReportID = ? AND Status = 'Mới'";
        $statusStmt = $conn->prepare($statusSql);
        $statusStmt->bind_param("i", $reportID);
        $statusStmt->execute();
        $statusStmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm bình luận'
        ]);
    } else {
        throw new Exception('Không thể thêm bình luận: ' . $stmt->error);
    }
}

/**
 * Upload ảnh
 */
function uploadImage($tmpName, $originalName) {
    $uploadDir = '../uploads/bug_reports/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($tmpName, $targetPath)) {
        return 'uploads/bug_reports/' . $fileName;
    }
    
    return null;
}

/**
 * Kiểm tra quyền admin
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
 * Thông báo cho user khi admin xử lý xong
 */
function notifyUser($conn, $reportID, $status) {
    // Lấy thông tin báo lỗi và user
    $sql = "SELECT br.Title, br.UserID, u.HoTen, u.Email 
            FROM bug_reports br 
            JOIN nguoidung u ON br.UserID = u.UserID 
            WHERE br.BugReportID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reportID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $message = $status === 'Đã giải quyết' 
            ? "Báo lỗi '{$row['Title']}' của bạn đã được giải quyết!" 
            : "Báo lỗi '{$row['Title']}' của bạn đã được đóng.";
        
        // Thêm comment tự động từ hệ thống
        $commentSql = "INSERT INTO bug_report_comments (BugReportID, UserID, Comment) 
                      VALUES (?, 1, ?)"; // UserID = 1 là system/admin
        $commentStmt = $conn->prepare($commentSql);
        $commentStmt->bind_param("is", $reportID, $message);
        $commentStmt->execute();
        $commentStmt->close();
        
        // TODO: Gửi email thông báo (nếu cần)
        // sendEmailNotification($row['Email'], $row['HoTen'], $message);
    }
    
    $stmt->close();
}
?>