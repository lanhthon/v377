<?php
// Hiển thị dưới dạng văn bản thuần để dễ dàng sao chép
header('Content-Type: text/plain; charset=utf-8');

// ===================================================================
// THÔNG SỐ KẾT NỐI CƠ SỞ DỮ LIỆU
// !! Hãy thay đổi các thông số này cho phù hợp với bạn !!
// ===================================================================
require_once '../config/database.php';
// Tạo kết nối

// Set charset để đảm bảo không lỗi font tiếng Việt
$conn->set_charset("utf8mb4");

// ===================================================================
// BẮT ĐẦU TẠO SCRIPT SQL
// ===================================================================

echo "-- SCRIPT DI CHUYỂN DỮ LIỆU TỰ ĐỘNG\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
echo "-- LƯU Ý: HÃY CHẠY TRÊN CSDL THỬ NGHIỆM TRƯỚC!\n\n";

// --- BƯỚC 1: TẠO BẢNG MỚI (nếu chưa tồn tại) ---
// (Bạn đã chạy bước này ở yêu cầu trước, có thể bỏ qua nếu đã có bảng)
echo "-- ===================================================================\n";
echo "-- BƯỚC 1: CẤU TRÚC BẢNG MỚI (bỏ qua nếu đã tạo)\n";
echo "-- ===================================================================\n";
echo "
CREATE TABLE IF NOT EXISTS `sanpham_goc` (
  `GocID` INT(11) NOT NULL AUTO_INCREMENT, `TenGoc` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `NhomID` INT(11) DEFAULT NULL, `LoaiID` INT(11) DEFAULT NULL, `MoTa` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, PRIMARY KEY (`GocID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `thuoc_tinh` (
  `ThuocTinhID` INT(11) NOT NULL AUTO_INCREMENT, `TenThuocTinh` VARCHAR(100) NOT NULL, PRIMARY KEY (`ThuocTinhID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bienthe_sanpham` (
  `BienTheID` INT(11) NOT NULL, `GocID` INT(11) NOT NULL, `MaHang` VARCHAR(100) NOT NULL,
  `TenBienThe` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL, `GiaGoc` DECIMAL(18,2) NOT NULL,
  `DonViTinh` VARCHAR(50) DEFAULT 'Bộ', `SoLuongTonKho` INT(11) NOT NULL DEFAULT 0, `DinhMucToiThieu` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`BienTheID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bienthe_thuoctinh` (
  `BienTheID` INT(11) NOT NULL, `ThuocTinhID` INT(11) NOT NULL, `GiaTri` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`BienTheID`, `ThuocTinhID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
\n";

// --- BƯỚC 2: THÊM DỮ LIỆU NỀN TẢNG ---
echo "-- ===================================================================\n";
echo "-- BƯỚC 2: DỮ LIỆU NỀN TẢNG (GỐC & THUỘC TÍNH)\n";
echo "-- ===================================================================\n";
echo "
TRUNCATE TABLE `bienthe_thuoctinh`;
DELETE FROM `bienthe_sanpham`;
TRUNCATE TABLE `sanpham_goc`;
TRUNCATE TABLE `thuoc_tinh`;

ALTER TABLE `sanpham_goc` AUTO_INCREMENT = 1;
ALTER TABLE `thuoc_tinh` AUTO_INCREMENT = 1;

INSERT INTO `thuoc_tinh` (`ThuocTinhID`, `TenThuocTinh`) VALUES
(1, 'ID_ThongSo'), (2, 'DoDay'), (3, 'BanRong'), (4, 'KichThuocRen'),
(5, 'NguonGoc'), (6, 'HinhDang'), (7, 'TinhTrang'), (8, 'XuLyBeMat');

INSERT INTO `sanpham_goc` (`GocID`, `TenGoc`, `NhomID`, `LoaiID`) VALUES
(1, 'Gối đỡ đế vuông', 2, 1), (2, 'Cùm Ula', 2, 2),
(3, 'Cách nhiệt Tấm Vàng (CV)', 7, NULL), (4, 'Cách nhiệt Tấm Trắng (CT)', 7, NULL),
(5, 'Ecu', 7, NULL), (6, 'Phụ kiện khác', NULL, NULL);
\n";

// --- BƯỚC 3: DI CHUYỂN DỮ LIỆU SẢN PHẨM ---
echo "-- ===================================================================\n";
echo "-- BƯỚC 3: DI CHUYỂN TOÀN BỘ DỮ LIỆU SẢN PHẨM\n";
echo "-- ===================================================================\n";
echo "START TRANSACTION;\n\n";

$sql_select = "SELECT * FROM sanpham ORDER BY SanPhamID";
$result = $conn->query($sql_select);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bienTheID = $row['SanPhamID'];

        // --- Logic xác định sản phẩm gốc (GocID) ---
        $gocID = 6; // Mặc định là 'Phụ kiện khác'
        $nhomID = $row['NhomID'];
        $maHang = strtoupper($row['MaHang']);
        
        if ($nhomID == 2) {
            $gocID = (strpos($maHang, 'ULA') !== false) ? 2 : 1;
        } elseif ($nhomID == 7) {
            if (strpos($maHang, 'CV') === 0) $gocID = 3;
            elseif (strpos($maHang, 'CT') === 0) $gocID = 4;
            elseif (strpos($maHang, 'ECU') === 0) $gocID = 5;
        } elseif ($nhomID == 1 && strpos($maHang, 'ULA') !== false) {
             $gocID = 2; // Cùm Ula
        }
        
        // --- Tạo lệnh INSERT cho bienthe_sanpham ---
        $maHang_escaped = $conn->real_escape_string($row['MaHang']);
        $tenSP_escaped = $conn->real_escape_string($row['TenSanPham']);
        $donViTinh_escaped = $conn->real_escape_string($row['DonViTinh']);

        echo "-- Sản phẩm: {$tenSP_escaped} (ID cũ: {$bienTheID})\n";
        echo "INSERT INTO `bienthe_sanpham` (`BienTheID`, `GocID`, `MaHang`, `TenBienThe`, `GiaGoc`, `DonViTinh`, `SoLuongTonKho`, `DinhMucToiThieu`) VALUES \n";
        echo "  ({$bienTheID}, {$gocID}, '{$maHang_escaped}', '{$tenSP_escaped}', {$row['GiaGoc']}, '{$donViTinh_escaped}', {$row['SoLuongTonKho']}, {$row['DinhMucToiThieu']});\n";
        
        // --- Tạo các lệnh INSERT cho bienthe_thuoctinh ---
        $attributes = [];
        if (!empty($row['ID_ThongSo'])) $attributes[] = "({$bienTheID}, 1, '" . $conn->real_escape_string($row['ID_ThongSo']) . "')";
        if (!empty($row['DoDay'])) $attributes[] = "({$bienTheID}, 2, '" . $conn->real_escape_string($row['DoDay']) . "')";
        if (!empty($row['BanRong'])) $attributes[] = "({$bienTheID}, 3, '" . $conn->real_escape_string($row['BanRong']) . "')";
        if (!empty($row['KichThuocRen'])) $attributes[] = "({$bienTheID}, 4, '" . $conn->real_escape_string($row['KichThuocRen']) . "')";
        if (!empty($row['NguonGoc'])) $attributes[] = "({$bienTheID}, 5, '" . $conn->real_escape_string($row['NguonGoc']) . "')";
        if (!empty($row['HinhDang'])) $attributes[] = "({$bienTheID}, 6, '" . $conn->real_escape_string($row['HinhDang']) . "')";

        // Suy luận các thuộc tính từ tên
        if (strpos(strtolower($tenSP_escaped), '(hàng cũ)') !== false) {
             $attributes[] = "({$bienTheID}, 7, 'Hàng cũ')";
        }
        if (strpos(strtolower($tenSP_escaped), 'nhúng nóng') !== false) {
             $attributes[] = "({$bienTheID}, 8, 'Nhúng nóng')";
        }

        if (!empty($attributes)) {
            echo "INSERT INTO `bienthe_thuoctinh` (`BienTheID`, `ThuocTinhID`, `GiaTri`) VALUES \n  " . implode(",\n  ", $attributes) . ";\n\n";
        } else {
            echo "\n";
        }
    }
} else {
    echo "-- Không tìm thấy sản phẩm nào trong bảng 'sanpham'.\n";
}

echo "COMMIT;\n";

$conn->close();
?>