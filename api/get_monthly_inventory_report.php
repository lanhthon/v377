<?php
// api/get_inventory_report.php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    $start_date_str = $_GET['start_date'] ?? null;
    $end_date_str = $_GET['end_date'] ?? null;

    if (!$start_date_str || !$end_date_str) {
        throw new Exception("Vui lòng cung cấp ngày bắt đầu và ngày kết thúc.");
    }

    // Validate format YYYY-MM-DD
    $date_regex = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_regex, $start_date_str) || !preg_match($date_regex, $end_date_str)) {
        throw new Exception("Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng YYYY-MM-DD.");
    }
    
    $start_date = $start_date_str . ' 00:00:00';
    $end_date = $end_date_str . ' 23:59:59';
    
    if (new DateTime($start_date) > new DateTime($end_date)) {
        throw new Exception("Ngày bắt đầu không được lớn hơn ngày kết thúc.");
    }

    // This complex query calculates opening stock, total imports, total exports, and closing stock for the selected date range.
    $sql = "
        WITH DateRangeMovements AS (
            SELECT
                SanPhamID,
                SUM(IF(SoLuongThayDoi > 0, SoLuongThayDoi, 0)) AS TongNhap,
                SUM(IF(SoLuongThayDoi < 0, -SoLuongThayDoi, 0)) AS TongXuat
            FROM lichsunhapxuat
            WHERE NgayGiaoDich >= ? AND NgayGiaoDich <= ?
            GROUP BY SanPhamID
        ),
        OpeningBalances AS (
            SELECT
                ls.SanPhamID,
                ls.SoLuongSauGiaoDich AS TonDauKy
            FROM lichsunhapxuat ls
            INNER JOIN (
                SELECT SanPhamID, MAX(LichSuID) as MaxID
                FROM lichsunhapxuat
                WHERE NgayGiaoDich < ?
                GROUP BY SanPhamID
            ) max_ids ON ls.LichSuID = max_ids.MaxID
        ),
        AllProducts AS (
            SELECT SanPhamID FROM DateRangeMovements
            UNION
            SELECT SanPhamID FROM OpeningBalances
        )
        SELECT
            p.SanPhamID,
            v.variant_sku,
            v.variant_name,
            COALESCE(ob.TonDauKy, 0) AS TonDauKy,
            COALESCE(mm.TongNhap, 0) AS TongNhap,
            COALESCE(mm.TongXuat, 0) AS TongXuat,
            (COALESCE(ob.TonDauKy, 0) + COALESCE(mm.TongNhap, 0) - COALESCE(mm.TongXuat, 0)) AS TonCuoiKy
        FROM AllProducts p
        LEFT JOIN OpeningBalances ob ON p.SanPhamID = ob.SanPhamID
        LEFT JOIN DateRangeMovements mm ON p.SanPhamID = mm.SanPhamID
        JOIN variants v ON p.SanPhamID = v.variant_id
        ORDER BY v.variant_sku;
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị câu lệnh SQL: " . $conn->error);
    }

    $stmt->bind_param("sss", $start_date, $end_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    $response['success'] = true;
    $response['data'] = $data;

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
