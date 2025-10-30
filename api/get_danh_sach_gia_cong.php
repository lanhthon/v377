<?php
/**
 * File: api/get_danh_sach_gia_cong.php
 * Version: 1.0
 * Description: API lấy danh sách phiếu xuất gia công mạ nhúng nóng
 *
 * Chức năng:
 * - Lấy danh sách tất cả phiếu xuất gia công
 * - Lọc theo trạng thái, CBH_ID, ngày tháng
 * - Hỗ trợ phân trang
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once '../config/db_config.php';

try {
    $pdo = get_db_connection();

    // Lấy parameters
    $cbhId = isset($_GET['cbh_id']) ? intval($_GET['cbh_id']) : null;
    $trangThai = isset($_GET['trang_thai']) ? $_GET['trang_thai'] : null;
    $tuNgay = isset($_GET['tu_ngay']) ? $_GET['tu_ngay'] : null;
    $denNgay = isset($_GET['den_ngay']) ? $_GET['den_ngay'] : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(1000, intval($_GET['limit']))) : 50;
    $offset = ($page - 1) * $limit;

    // Build WHERE clause
    $where = [];
    $params = [];

    if ($cbhId) {
        $where[] = "pxgc.CBH_ID = :cbhId";
        $params[':cbhId'] = $cbhId;
    }

    if ($trangThai) {
        $where[] = "pxgc.TrangThai = :trangThai";
        $params[':trangThai'] = $trangThai;
    }

    if ($tuNgay) {
        $where[] = "DATE(pxgc.NgayXuat) >= :tuNgay";
        $params[':tuNgay'] = $tuNgay;
    }

    if ($denNgay) {
        $where[] = "DATE(pxgc.NgayXuat) <= :denNgay";
        $params[':denNgay'] = $denNgay;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Đếm tổng số
    $sqlCount = "
        SELECT COUNT(*) as total
        FROM phieu_xuat_gia_cong pxgc
        $whereClause
    ";

    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();

    // Lấy dữ liệu
    $sql = "
        SELECT
            pxgc.*,
            cbh.SoCBH,
            cbh.TrangThai as TrangThaiCBH,
            v_xuat.variant_name as TenSPXuat,
            v_nhan.variant_name as TenSPNhan
        FROM phieu_xuat_gia_cong pxgc
        LEFT JOIN chuanbihang cbh ON pxgc.CBH_ID = cbh.CBH_ID
        LEFT JOIN variants v_xuat ON pxgc.SanPhamXuatID = v_xuat.variant_id
        LEFT JOIN variants v_nhan ON pxgc.SanPhamNhanID = v_nhan.variant_id
        $whereClause
        ORDER BY pxgc.NgayXuat DESC
        LIMIT :limit OFFSET :offset
    ";

    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    $stmt = $pdo->prepare($sql);

    // Bind parameters với đúng kiểu
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if ($key === ':limit' || $key === ':offset' || $key === ':cbhId') {
            $type = PDO::PARAM_INT;
        }
        $stmt->bindValue($key, $value, $type);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dữ liệu
    foreach ($data as &$item) {
        $item['TienDoNhap'] = sprintf(
            "%d/%d (%s%%)",
            $item['SoLuongNhapVe'],
            $item['SoLuongXuat'],
            $item['SoLuongXuat'] > 0 ? round(($item['SoLuongNhapVe'] / $item['SoLuongXuat']) * 100) : 0
        );

        // Format ngày tháng
        $item['NgayXuatFormatted'] = date('d/m/Y H:i', strtotime($item['NgayXuat']));
        $item['NgayNhapKhoFormatted'] = $item['NgayNhapKho'] ? date('d/m/Y H:i', strtotime($item['NgayNhapKho'])) : null;
    }
    unset($item);

    // Thống kê theo trạng thái
    $sqlStats = "
        SELECT TrangThai, COUNT(*) as SoLuong
        FROM phieu_xuat_gia_cong
        GROUP BY TrangThai
    ";

    $stmtStats = $pdo->query($sqlStats);
    $stats = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => ceil($total / $limit)
        ],
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("ERROR in get_danh_sach_gia_cong.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
