<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Người dùng chưa đăng nhập.']);
    exit;
}

$userID = $_SESSION['user_id'];
$response = [
    'success' => false,
    'user' => null,
    'permissions' => [],
    'counts' => [] // Thêm mục counts để chứa số lượng cho các chức năng
];

try {
    // Lấy thông tin chi tiết của người dùng
    $stmt_user = $conn->prepare(
        "SELECT u.UserID, u.HoTen, u.ChucVu, u.SoDienThoai, v.TenVaiTro, u.MaVaiTro 
         FROM nguoidung u 
         JOIN vaitro v ON u.MaVaiTro = v.MaVaiTro 
         WHERE u.UserID = ?"
    );
    $stmt_user->bind_param("i", $userID);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $userRole = '';

    if ($user_data = $result_user->fetch_assoc()) {
    $response['user'] = [
        'userID'   => $user_data['UserID'],
        'fullName' => $user_data['HoTen'],
        'position' => $user_data['ChucVu'],
        'phone'    => $user_data['SoDienThoai'],
        'role'     => $user_data['TenVaiTro'],
        'roleCode' => $user_data['MaVaiTro']  // THÊM DÒNG NÀY
    ];
    $userRole = $user_data['MaVaiTro'];
}
    $stmt_user->close();

    if (empty($userRole)) {
         throw new Exception("Không tìm thấy vai trò cho người dùng.");
    }

    // Lấy danh sách quyền (permissions) và sắp xếp theo cấu trúc cha-con
    $stmt_perms = $conn->prepare(
       "SELECT 
            c.MaChucNang, 
            c.TenChucNang, 
            c.Url, 
            c.Icon,
            c.ParentMaChucNang
        FROM vaitro_chucnang vc
        JOIN chucnang c ON vc.MaChucNang = c.MaChucNang
        LEFT JOIN chucnang p ON c.ParentMaChucNang = p.MaChucNang
        WHERE vc.MaVaiTro = ?
        ORDER BY 
            COALESCE(p.ThuTuHienThi, c.ThuTuHienThi),
            c.ParentMaChucNang IS NOT NULL,
            c.ThuTuHienThi"
    );

    $stmt_perms->bind_param("s", $userRole);
    $stmt_perms->execute();
    $result_perms = $stmt_perms->get_result();
    while ($row = $result_perms->fetch_assoc()) {
        $response['permissions'][] = $row;
    }
    $stmt_perms->close();

    // --- PHẦN MỚI: ĐẾM SỐ LƯỢNG CHỜ XUẤT KHO ---
    try {
        $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM chuanbihang WHERE TrangThai = 'Chờ xuất kho'");
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        if ($row_count = $result_count->fetch_assoc()) {
            // Gán số lượng vào mã chức năng tương ứng
            if ($row_count['total'] > 0) {
                $response['counts']['chuanbi_xuatkho_tp'] = $row_count['total'];
            }
        }
        $stmt_count->close();
    } catch (Exception $e) {
        // Nếu có lỗi ở đây, không làm gián đoạn việc lấy menu chính
        error_log("Lỗi đếm số lượng chờ xuất kho: " . $e->getMessage());
    }
    // --- KẾT THÚC PHẦN MỚI ---


    $response['success'] = true;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
