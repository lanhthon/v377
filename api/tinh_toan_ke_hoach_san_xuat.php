<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng.']);
    exit;
}

$donHangID = (int)$_GET['id'];
$results = [];

try {
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

    // BỔ SUNG LẤY THÔNG TIN "SoLuongPhanBo" (Số lượng đã gán)
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
            dmc.SoBoTrenCay,
            COALESCE(dpt.SoLuongPhanBo, 0) AS SoLuongDaGan -- DÒNG MỚI: Lấy số lượng đã gán cho đơn hàng này
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
        LEFT JOIN
            donhang_phanbo_tonkho AS dpt ON cd.DonHangID = dpt.DonHangID AND cd.SanPhamID = dpt.SanPhamID -- JOIN MỚI: Nối bảng phân bổ tồn kho
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
        $soLuongDaGan = (int)$item['SoLuongDaGan'];
        $tonKhoBo = (int)$item['TonKhoBo'];
        $tonKhoCay = (int)$item['TonKhoCay'];
        $soBoTrenCay = (int)$item['SoBoTrenCay'];
        
        // Logic tính toán không đổi, chỉ bổ sung trường dữ liệu trả về
        $boConThieu = $soLuongYeuCau - $soLuongDaGan;
        $cayTuongDuong = 0;
        $cayCanSanXuat = 0;
        $hangCanDatThem = 0;
        $hanhDong = '';
        $dinhMucCatDisplay = ''; // BỔ SUNG: Biến hiển thị định mức

        if ($boConThieu <= 0) {
            $hanhDong = 'Chuẩn bị hàng';
            $boConThieu = 0;
        } else {
            if ($item['LoaiID'] == 2) {
                $hanhDong = 'Đặt thêm hàng';
                $hangCanDatThem = $boConThieu;
            } else {
                if ($soBoTrenCay > 0) {
                    $dinhMucCatDisplay = $soBoTrenCay . ' bộ/cây'; // BỔ SUNG: Định dạng chuỗi định mức
                    $cayTuongDuong = ceil($boConThieu / $soBoTrenCay);
                    $cayThieuThucTe = $cayTuongDuong - $tonKhoCay;

                    if ($cayThieuThucTe <= 0) {
                        $hanhDong = 'Sản xuất (từ cây có sẵn)';
                        $cayCanSanXuat = 0;
                    } else {
                        $hanhDong = 'Lệnh sản xuất';
                        $cayCanSanXuat = $cayThieuThucTe;
                    }
                } else {
                    $hanhDong = 'Lỗi - Không có định mức';
                    $dinhMucCatDisplay = 'N/A';
                    $cayCanSanXuat = 0;
                }
            }
        }
        
        $calculated_items[] = [
            'MaHang' => $item['MaHang'],
            'TenSanPham' => $item['TenSanPham'],
            'SoLuongYeuCau' => $soLuongYeuCau,
            'SoLuongDaGan' => $soLuongDaGan, // BỔ SUNG: Trả về số lượng đã gán
            'TonKhoBo' => $tonKhoBo,
            'BoCanSanXuat' => $boConThieu,
            'CayTuongDuong' => $cayTuongDuong,
            'TonKhoCay' => $tonKhoCay,
            'DinhMucCat' => $dinhMucCatDisplay, // BỔ SUNG: Trả về định mức cắt
            'CayCanSanXuat' => $cayCanSanXuat,
            'HangCanDatThem' => $hangCanDatThem,
            'HanhDong' => $hanhDong
        ];
    }
    $stmt_items->close();

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