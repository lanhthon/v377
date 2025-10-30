<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

// Kiểm tra xem ID đơn hàng có được cung cấp không
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng.']);
    exit;
}

$donHangID = (int)$_GET['id'];
$results = [];

try {
    // 1. Lấy thông tin cơ bản của đơn hàng
    $stmt_donhang = $conn->prepare(
        "SELECT d.YCSX_ID, d.SoYCSX, d.NgayTao, d.NgayGiaoDuKien, d.TrangThai, b.TenCongTy 
         FROM donhang d 
         JOIN baogia b ON d.BaoGiaID = b.BaoGiaID 
         WHERE d.YCSX_ID = ?"
    );
    $stmt_donhang->bind_param("i", $donHangID);
    $stmt_donhang->execute();
    $donhang_info = $stmt_donhang->get_result()->fetch_assoc();
    $stmt_donhang->close();

    if (!$donhang_info) {
        throw new Exception("Không tìm thấy đơn hàng.");
    }

    // 2. Lấy chi tiết các sản phẩm trong đơn hàng cùng với thông tin tồn kho và định mức
    $sql_items = "
        SELECT
            cd.SanPhamID,
            cd.MaHang,
            cd.TenSanPham,
            cd.SoLuong AS SoLuongYeuCau,
            sp.LoaiID,
            sp.ID_ThongSo,
            sp.BanRong,
            COALESCE(spt.TonKhoBo, 0) AS TonKhoBo,
            COALESCE(spt.TonKhoCay, 0) AS TonKhoCay,
            dmc.SoBoTrenCay
        FROM
            chitiet_donhang AS cd
        JOIN
            sanpham AS sp ON cd.SanPhamID = sp.SanPhamID
        LEFT JOIN
            sanpham_tonkho AS spt ON cd.SanPhamID = spt.SanPhamID
        LEFT JOIN
            dinh_muc_cat AS dmc ON
                sp.BanRong = dmc.BanRong
                AND CAST(sp.ID_ThongSo AS UNSIGNED) >= dmc.MinDN
                AND CAST(sp.ID_ThongSo AS UNSIGNED) <= dmc.MaxDN
        WHERE
            cd.DonHangID = ?
        ORDER BY cd.ThuTuHienThi
    ";

    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $donHangID);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    $calculated_items = [];
    while ($item = $items_result->fetch_assoc()) {
        $soLuongYeuCau = (int)$item['SoLuongYeuCau'];
        $tonKhoBo = (int)$item['TonKhoBo'];
        $tonKhoCay = (int)$item['TonKhoCay'];
        $soBoTrenCay = (int)$item['SoBoTrenCay'];
        
        $boConThieu = $soLuongYeuCau - $tonKhoBo;
        $cayTuongDuong = 0;
        $cayCanSanXuat = 0;
        $hangCanDatThem = 0;
        $hanhDong = '';

        if ($boConThieu <= 0) {
            // Trường hợp 1: Tồn kho thành phẩm đủ
            $hanhDong = 'Chuẩn bị hàng';
            $boConThieu = 0;
        } else {
            // Trường hợp 2: Thiếu thành phẩm
            if ($item['LoaiID'] == 2) { // LoaiID = 2 là Cùm ULA
                // Đây là sản phẩm mua ngoài, không sản xuất
                $hanhDong = 'Đặt thêm hàng';
                $hangCanDatThem = $boConThieu;
            } else {
                // Đây là sản phẩm tự sản xuất
                if ($soBoTrenCay > 0) {
                    $cayTuongDuong = ceil($boConThieu / $soBoTrenCay);
                    $cayThieuThucTe = $cayTuongDuong - $tonKhoCay;

                    if ($cayThieuThucTe <= 0) {
                        // Tồn kho bán thành phẩm (cây) đủ
                        $hanhDong = 'Sản xuất (từ cây có sẵn)';
                        $cayCanSanXuat = 0; // Không cần sản xuất cây mới
                    } else {
                        // Thiếu cả bán thành phẩm -> Cần sản xuất cây mới
                        $hanhDong = 'Lệnh sản xuất';
                        $cayCanSanXuat = $cayThieuThucTe;
                    }
                } else {
                    // Không tìm thấy định mức cắt cho sản phẩm này
                    $hanhDong = 'Lỗi - Không có định mức';
                    $cayCanSanXuat = 0;
                }
            }
        }
        
        $calculated_items[] = [
            'MaHang' => $item['MaHang'],
            'TenSanPham' => $item['TenSanPham'],
            'SoLuongYeuCau' => $soLuongYeuCau,
            'TonKhoBo' => $tonKhoBo,
            'BoCanSanXuat' => $boConThieu,
            'CayTuongDuong' => $cayTuongDuong,
            'TonKhoCay' => $tonKhoCay,
            'CayCanSanXuat' => $cayCanSanXuat,
            'HangCanDatThem' => $hangCanDatThem,
            'HanhDong' => $hanhDong
        ];
    }
    $stmt_items->close();

    // Chuẩn bị kết quả cuối cùng
    $results = [
        'success' => true,
        'donhang_info' => $donhang_info,
        'items' => $calculated_items
    ];

} catch (Exception $e) {
    http_response_code(500);
    $results = ['success' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($results);
?>