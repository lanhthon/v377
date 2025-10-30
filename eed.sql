-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th10 31, 2025 lúc 02:35 AM
-- Phiên bản máy phục vụ: 10.11.14-MariaDB-cll-lve-log
-- Phiên bản PHP: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `eedsyydkhosting_qlnoibo3igreen`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `app_versions`
--

CREATE TABLE `app_versions` (
  `id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `version_number` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `download_url` varchar(500) NOT NULL,
  `release_notes_url` varchar(500) DEFAULT '',
  `release_date` date NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `min_version` varchar(20) DEFAULT '3.0.0',
  `download_size` varchar(20) DEFAULT '',
  `changelog` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `area_templates`
--

CREATE TABLE `area_templates` (
  `TemplateID` int(11) NOT NULL,
  `TenTemplate` varchar(255) NOT NULL COMMENT 'Tên template khu vực',
  `DanhSachKhuVuc` text NOT NULL COMMENT 'JSON danh sách khu vực và màu sắc',
  `NguoiTao` int(11) DEFAULT NULL COMMENT 'ID người tạo template',
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
  `TrangThai` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attributes`
--

CREATE TABLE `attributes` (
  `attribute_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT 99 COMMENT 'Dùng để sắp xếp thứ tự hiển thị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attribute_options`
--

CREATE TABLE `attribute_options` (
  `option_id` int(11) NOT NULL,
  `attribute_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bang_dinh_muc_dong_thung`
--

CREATE TABLE `bang_dinh_muc_dong_thung` (
  `id` int(11) NOT NULL,
  `duong_kinh_trong` int(11) NOT NULL COMMENT 'Đường kính trong của gối đỡ (mm)',
  `ban_rong` int(11) NOT NULL COMMENT 'Bản rộng của gối đỡ (mm)',
  `do_day` int(11) NOT NULL COMMENT 'Độ dày của gối đỡ (mm)',
  `loai_thung` varchar(100) NOT NULL COMMENT 'Tên loại thùng (ví dụ: Thùng nhỏ, Thùng to)',
  `so_luong` int(11) NOT NULL COMMENT 'Số lượng bộ sản phẩm trong thùng'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng master để tra cứu và quản lý định mức đóng thùng';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `baogia`
--

CREATE TABLE `baogia` (
  `BaoGiaID` int(11) NOT NULL,
  `SoBaoGia` varchar(50) DEFAULT NULL,
  `NgayBaoGia` date NOT NULL,
  `NgayGiaoDuKien` date DEFAULT NULL COMMENT 'Ngày giao hàng dự kiến',
  `KhachHangID` int(11) DEFAULT NULL,
  `CongTyID` int(11) DEFAULT NULL,
  `NguoiLienHeID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL,
  `DuAnID` int(11) DEFAULT NULL,
  `TenCongTy` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `DiaChiKhach` varchar(500) DEFAULT NULL,
  `NguoiNhan` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `SoDienThoaiKhach` varchar(30) DEFAULT NULL,
  `SoFaxKhach` varchar(30) DEFAULT NULL,
  `SoDiDongKhach` varchar(30) DEFAULT NULL,
  `TenDuAn` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CoCheGiaApDung` varchar(10) DEFAULT NULL,
  `TongTienTruocThue` decimal(18,2) DEFAULT NULL,
  `ThueVAT` decimal(18,2) DEFAULT NULL,
  `TongTienSauThue` decimal(18,2) DEFAULT NULL,
  `SoLuongVanChuyen` int(11) DEFAULT 1,
  `DonGiaVanChuyen` decimal(18,2) DEFAULT 0.00,
  `TongTienVanChuyen` decimal(18,2) DEFAULT 0.00,
  `TrangThai` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `NguoiTao` int(11) DEFAULT NULL,
  `DiaChiGiaoHang` varchar(255) DEFAULT NULL,
  `ThoiGianGiaoHang` varchar(255) DEFAULT NULL,
  `DieuKienThanhToan` varchar(255) DEFAULT NULL,
  `HieuLucBaoGia` varchar(255) DEFAULT NULL,
  `NguoiBaoGia` varchar(100) DEFAULT NULL,
  `ChucVuNguoiBaoGia` varchar(100) DEFAULT NULL,
  `DiDongNguoiBaoGia` varchar(30) DEFAULT NULL,
  `HangMuc` varchar(100) DEFAULT NULL,
  `HinhAnh1` varchar(255) DEFAULT 'uploads/default_image_1.png' COMMENT 'Đường dẫn hình ảnh 1',
  `HinhAnh2` varchar(255) DEFAULT 'uploads/default_image_2.png' COMMENT 'Đường dẫn hình ảnh 2',
  `XuatXu` varchar(255) DEFAULT NULL COMMENT 'Xuất xứ của sản phẩm/báo giá',
  `GhiChuVanChuyen` varchar(500) DEFAULT NULL COMMENT 'Ghi chú vận chuyển',
  `PhanTramDieuChinh` decimal(5,2) DEFAULT 0.00,
  `ThuePhanTram` decimal(5,2) DEFAULT 8.00 COMMENT 'Phần trăm thuế VAT được áp dụng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bienbangiaohang`
--

CREATE TABLE `bienbangiaohang` (
  `BBGH_ID` int(11) NOT NULL,
  `YCSX_ID` int(11) NOT NULL COMMENT 'ID của YCSX gốc',
  `PhieuXuatKhoID` int(11) DEFAULT NULL COMMENT 'Liên kết đến Phiếu Xuất Kho',
  `BaoGiaID` int(11) NOT NULL COMMENT 'ID của Báo giá gốc',
  `SoBBGH` varchar(100) NOT NULL COMMENT 'Số biên bản, được tạo tự động',
  `NgayTao` date NOT NULL COMMENT 'Ngày tạo biên bản',
  `TenCongTy` varchar(255) DEFAULT NULL,
  `DiaChiKhachHang` varchar(500) DEFAULT NULL COMMENT 'Địa chỉ công ty khách hàng',
  `DiaChiGiaoHang` varchar(255) DEFAULT NULL,
  `NguoiNhanHang` varchar(100) DEFAULT NULL,
  `ChucVuNhanHang` varchar(100) DEFAULT 'QL. Kho' COMMENT 'Chức vụ người nhận hàng',
  `SoDienThoaiNhanHang` varchar(50) DEFAULT NULL,
  `DuAn` varchar(255) DEFAULT NULL COMMENT 'Tên dự án từ báo giá',
  `SanPham` varchar(255) DEFAULT NULL COMMENT 'Thông tin sản phẩm tổng quát',
  `NguoiGiaoHang` varchar(100) DEFAULT NULL,
  `SdtNguoiGiaoHang` varchar(50) DEFAULT NULL,
  `TrangThai` varchar(50) NOT NULL DEFAULT 'Mới tạo',
  `NgayGiao` date DEFAULT NULL COMMENT 'Ngày giao hàng thực tế',
  `GhiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bug_reports`
--

CREATE TABLE `bug_reports` (
  `BugReportID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `ImagePath` varchar(500) DEFAULT NULL,
  `Status` enum('Mới','Đã tiếp nhận','Đang xử lý','Đã giải quyết','Đã đóng') DEFAULT 'Mới',
  `Priority` enum('Thấp','Trung bình','Cao','Khẩn cấp') DEFAULT 'Trung bình',
  `AdminNote` text DEFAULT NULL COMMENT 'Ghi chú từ admin',
  `ResolvedAt` datetime DEFAULT NULL COMMENT 'Thời gian giải quyết',
  `ResolvedBy` int(11) DEFAULT NULL COMMENT 'Người giải quyết',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bug_report_comments`
--

CREATE TABLE `bug_report_comments` (
  `CommentID` int(11) NOT NULL,
  `BugReportID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Comment` text NOT NULL,
  `ImagePath` varchar(500) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cauhinh_sanxuat`
--

CREATE TABLE `cauhinh_sanxuat` (
  `ID` int(11) NOT NULL,
  `TenThietLap` varchar(100) NOT NULL COMMENT 'Tên của thiết lập, ví dụ: NangSuatChung',
  `GiaTriThietLap` varchar(255) NOT NULL COMMENT 'Giá trị của thiết lập'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietbaogia`
--

CREATE TABLE `chitietbaogia` (
  `ChiTietID` int(11) NOT NULL,
  `BaoGiaID` int(11) DEFAULT NULL,
  `TenNhom` varchar(255) DEFAULT NULL,
  `ID_ThongSo` varchar(255) DEFAULT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `MaHang` varchar(50) DEFAULT NULL,
  `TenSanPham` varchar(255) DEFAULT NULL,
  `DoDay` varchar(255) DEFAULT NULL,
  `ChieuRong` varchar(255) DEFAULT NULL,
  `DonViTinh` varchar(50) DEFAULT 'Bộ',
  `SoLuong` int(11) NOT NULL,
  `DonGia` decimal(18,2) NOT NULL,
  `ThanhTien` decimal(18,2) NOT NULL,
  `GhiChu` varchar(500) DEFAULT NULL,
  `ThuTuHienThi` int(11) DEFAULT NULL,
  `KhuVuc` varchar(100) DEFAULT NULL COMMENT 'Khu vực phân chia khối lượng',
  `KhuVucMauSac` varchar(7) DEFAULT NULL COMMENT 'Màu sắc hiển thị khu vực (hex color)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietbienbangiaohang`
--

CREATE TABLE `chitietbienbangiaohang` (
  `ChiTietBBGH_ID` int(11) NOT NULL,
  `BBGH_ID` int(11) NOT NULL,
  `TenNhom` varchar(255) DEFAULT NULL COMMENT 'Tên nhóm sản phẩm',
  `SanPhamID` int(11) DEFAULT NULL,
  `MaHang` varchar(100) DEFAULT NULL,
  `TenSanPham` varchar(255) DEFAULT NULL,
  `ID_ThongSo` varchar(50) DEFAULT NULL,
  `DoDay` varchar(50) DEFAULT NULL,
  `BanRong` varchar(50) DEFAULT NULL,
  `DonViTinh` varchar(50) DEFAULT 'Bộ',
  `SoLuong` int(11) NOT NULL,
  `SoThung` varchar(50) DEFAULT NULL COMMENT 'Số thùng/kiện hàng',
  `GhiChu` text DEFAULT NULL,
  `ThuTuHienThi` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietchuanbihang`
--

CREATE TABLE `chitietchuanbihang` (
  `ChiTietCBH_ID` int(11) NOT NULL,
  `CBH_ID` int(11) NOT NULL,
  `TenNhom` varchar(255) DEFAULT NULL,
  `SanPhamID` int(11) DEFAULT NULL,
  `MaHang` varchar(100) DEFAULT NULL,
  `TenSanPham` varchar(255) DEFAULT NULL,
  `SoLuong` int(11) NOT NULL,
  `ID_ThongSo` varchar(50) DEFAULT NULL,
  `DoDay` varchar(50) DEFAULT NULL,
  `BanRong` varchar(50) DEFAULT NULL,
  `SoThung` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `TonKho` int(11) DEFAULT 0 COMMENT 'Cập nhật bởi thủ kho',
  `DaGan` int(11) NOT NULL DEFAULT 0 COMMENT 'Lượng đã gán cho đơn hàng khác tại thời điểm lưu',
  `SoLuongLayTuKho` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng quyết định lấy từ kho',
  `CayCat` varchar(100) DEFAULT NULL COMMENT 'Cập nhật bởi thủ kho',
  `DongGoi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `DatThem` varchar(100) DEFAULT NULL COMMENT 'Cập nhật bởi thủ kho',
  `SoKg` decimal(10,2) DEFAULT NULL COMMENT 'Cập nhật bởi thủ kho',
  `GhiChu` text DEFAULT NULL,
  `ThuTuHienThi` int(11) DEFAULT 0,
  `TrangThaiXuatKho` varchar(50) DEFAULT 'chưa xử lý',
  `DuongKinhTrong` varchar(50) DEFAULT NULL COMMENT 'Đường kính trong của sản phẩm',
  `SoLuongCanSX` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng cần sản xuất thêm',
  `SoCayPhaiCat` varchar(50) DEFAULT NULL COMMENT 'Số cây phải cắt',
  `TonKhoCay` int(11) DEFAULT 0 COMMENT 'DEPRECATED: Sử dụng TonKhoCV và TonKhoCT thay thế',
  `DaGanCay` int(11) DEFAULT 0 COMMENT 'DEPRECATED: Sử dụng DaGanCV và DaGanCT thay thế',
  `CanSanXuatCay` int(11) DEFAULT 0 COMMENT 'Số lượng BTP cây cần cho đơn hàng này (bằng số cây phải cắt)',
  `CanSanXuatCV` int(11) DEFAULT 0 COMMENT 'Số lượng Cây Vuông cần sản xuất',
  `CanSanXuatCT` int(11) DEFAULT 0 COMMENT 'Số lượng Cây Tròn cần sản xuất',
  `TonKhoCV` int(11) DEFAULT 0 COMMENT 'Tồn kho Cây Vuông tại thời điểm xử lý',
  `DaGanCV` int(11) DEFAULT 0 COMMENT 'Số lượng Cây Vuông đã gán cho các đơn khác',
  `TonKhoCT` int(11) DEFAULT 0 COMMENT 'Tồn kho Cây Tròn tại thời điểm xử lý',
  `DaGanCT` int(11) DEFAULT 0 COMMENT 'Số lượng Cây Tròn đã gán cho các đơn khác',
  `SoLuongDaNhapTP` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng thành phẩm (PUR) đã nhập kho từ CBH'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietphieunhapkho`
--

CREATE TABLE `chitietphieunhapkho` (
  `ChiTietPNK_ID` int(11) NOT NULL COMMENT 'ID duy nhất của dòng chi tiết',
  `PhieuNhapKhoID` int(11) NOT NULL COMMENT 'Liên kết đến phiếu nhập kho tương ứng',
  `SanPhamID` int(11) NOT NULL COMMENT 'Liên kết đến sản phẩm được nhập',
  `SoLuongTheoDonHang` int(11) DEFAULT NULL COMMENT 'Số lượng gốc theo YCSX để tham chiếu',
  `SoLuong` int(11) NOT NULL COMMENT 'Số lượng sản phẩm nhập',
  `DonGiaNhap` decimal(18,2) NOT NULL COMMENT 'Đơn giá tại thời điểm nhập',
  `ThanhTien` decimal(18,2) NOT NULL COMMENT 'Thành tiền = Số lượng * Đơn giá',
  `GhiChu` varchar(500) DEFAULT NULL COMMENT 'Ghi chú cho từng dòng sản phẩm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_btp_cbh`
--

CREATE TABLE `chitiet_btp_cbh` (
  `ChiTietBTP_ID` int(11) NOT NULL,
  `CBH_ID` int(11) NOT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng',
  `MaBTP` varchar(100) NOT NULL COMMENT 'Mã bán thành phẩm (variant_sku)',
  `TenBTP` varchar(255) DEFAULT NULL,
  `SoCayCat` int(11) DEFAULT NULL COMMENT 'Tổng số cây BTP cần cắt cho thành phẩm',
  `SoLuongCan` decimal(10,2) NOT NULL COMMENT 'Số lượng cần (ví dụ: số cây)',
  `TonKhoSnapshot` int(11) DEFAULT 0 COMMENT 'Tồn kho tại thời điểm lưu',
  `DaGanSnapshot` int(11) NOT NULL DEFAULT 0 COMMENT 'Lượng đã gán cho đơn hàng khác tại thời điểm lưu',
  `DonViTinh` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu snapshot BTP cần cho phiếu CBH';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_chungchi_chatluong`
--

CREATE TABLE `chitiet_chungchi_chatluong` (
  `ChiTietCCCL_ID` int(11) NOT NULL,
  `CCCL_ID` int(11) NOT NULL,
  `SanPhamID` int(11) NOT NULL,
  `MaHang` varchar(100) DEFAULT NULL,
  `TenSanPham` varchar(255) DEFAULT NULL,
  `SoLuong` int(11) NOT NULL COMMENT 'Số lượng sản phẩm',
  `DonViTinh` varchar(50) DEFAULT 'Bộ',
  `TaiSo` varchar(100) DEFAULT NULL COMMENT 'Tải số từ phiếu xuất kho',
  `TieuChuanDatDuoc` varchar(255) DEFAULT NULL COMMENT 'Các tiêu chuẩn đạt được',
  `KetQuaKiemTra` text DEFAULT NULL COMMENT 'Kết quả kiểm tra thực tế',
  `GhiChuChiTiet` text DEFAULT NULL COMMENT 'Ghi chú cho từng dòng sản phẩm trong chứng chỉ',
  `ThuTuHienThi` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_donhang`
--

CREATE TABLE `chitiet_donhang` (
  `ChiTiet_YCSX_ID` int(11) NOT NULL,
  `DonHangID` int(11) NOT NULL,
  `SanPhamID` int(11) DEFAULT NULL COMMENT 'Liên kết đến variant_id trong bảng variants',
  `TenNhom` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `MaHang` varchar(100) DEFAULT NULL,
  `TenSanPham` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ID_ThongSo` varchar(50) DEFAULT NULL COMMENT 'ID Thông Số của sản phẩm (ví dụ: DN25)',
  `DoDay` varchar(50) DEFAULT NULL COMMENT 'Độ dày của sản phẩm',
  `BanRong` varchar(50) DEFAULT NULL COMMENT 'Bản rộng của sản phẩm',
  `SoLuong` int(11) NOT NULL,
  `DonGia` decimal(18,2) NOT NULL DEFAULT 0.00,
  `ThanhTien` decimal(18,2) NOT NULL DEFAULT 0.00,
  `SoLuongLayTuKho` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng được gán từ kho',
  `SoLuongCanSX` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng cần sản xuất thêm',
  `GhiChu` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ThuTuHienThi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_ecu_cbh`
--

CREATE TABLE `chitiet_ecu_cbh` (
  `ChiTietEcuCBH_ID` int(11) NOT NULL,
  `CBH_ID` int(11) NOT NULL,
  `TenSanPhamEcu` varchar(255) DEFAULT NULL,
  `SoLuongEcu` int(11) DEFAULT NULL,
  `SoLuongPhanBo` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng được phân bổ từ tồn kho cho phiếu CBH này',
  `DongGoiEcu` varchar(255) DEFAULT NULL,
  `SoKgEcu` decimal(10,2) DEFAULT NULL,
  `GhiChuEcu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `TonKhoSnapshot` int(11) NOT NULL DEFAULT 0 COMMENT 'Tồn kho tại thời điểm lưu',
  `DaGanSnapshot` int(11) NOT NULL DEFAULT 0 COMMENT 'Lượng đã gán cho đơn hàng khác tại thời điểm lưu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_hoadon`
--

CREATE TABLE `chitiet_hoadon` (
  `ChiTietHD_ID` int(11) NOT NULL,
  `HoaDonID` int(11) NOT NULL,
  `TenSanPham` varchar(255) NOT NULL,
  `DonViTinh` varchar(50) DEFAULT 'Bộ',
  `SoLuong` int(11) NOT NULL,
  `DonGia` decimal(18,2) NOT NULL,
  `ThanhTien` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu các dòng chi tiết của hóa đơn';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_kehoach_giaohang`
--

CREATE TABLE `chitiet_kehoach_giaohang` (
  `ChiTiet_KHGH_ID` int(11) NOT NULL,
  `KHGH_ID` int(11) NOT NULL,
  `ChiTiet_DonHang_ID` int(11) NOT NULL COMMENT 'Liên kết đến dòng sản phẩm trong đơn hàng gốc',
  `SoLuongGiao` int(11) NOT NULL COMMENT 'Số lượng sản phẩm cho đợt giao này'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_lenh_san_xuat`
--

CREATE TABLE `chitiet_lenh_san_xuat` (
  `ChiTiet_LSX_ID` int(11) NOT NULL,
  `LenhSX_ID` int(11) NOT NULL COMMENT 'Liên kết với lệnh sản xuất cha',
  `SanPhamID` int(11) NOT NULL,
  `SoLuongBoCanSX` int(11) NOT NULL,
  `SoLuongCayCanSX` int(11) NOT NULL,
  `SoLuongDaNhap` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng cây đã nhập kho từ sản xuất',
  `SoLuongCayTuongDuong` int(11) NOT NULL,
  `DinhMucCat` varchar(50) DEFAULT NULL,
  `GhiChu` text DEFAULT NULL,
  `TrangThai` varchar(50) DEFAULT 'Mới'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_phieuxuatkho`
--

CREATE TABLE `chitiet_phieuxuatkho` (
  `ChiTietPXK_ID` int(11) NOT NULL,
  `PhieuXuatKhoID` int(11) NOT NULL,
  `SanPhamID` int(11) DEFAULT NULL,
  `MaHang` varchar(100) DEFAULT NULL,
  `TenSanPham` varchar(255) DEFAULT NULL,
  `SoLuongYeuCau` int(11) NOT NULL COMMENT 'Số lượng theo đơn hàng',
  `SoLuongThucXuat` int(11) NOT NULL COMMENT 'Số lượng thực tế xuất từ kho',
  `TaiSo` varchar(100) DEFAULT NULL COMMENT 'Số tải hoặc thông tin vận chuyển',
  `DonViTinh` varchar(50) DEFAULT NULL,
  `GhiChu` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitiet_pnk_btp`
--

CREATE TABLE `chitiet_pnk_btp` (
  `ChiTiet_PNKBTP_ID` int(11) NOT NULL,
  `PNK_BTP_ID` int(11) NOT NULL COMMENT 'Liên kết đến phiếu nhập kho BTP cha',
  `BTP_ID` int(11) NOT NULL COMMENT 'ID của BTP (chính là variant_id)',
  `SoLuong` int(11) NOT NULL COMMENT 'Số lượng thực nhập',
  `so_luong_theo_lenh_sx` int(11) DEFAULT NULL COMMENT 'Số lượng gốc theo Lệnh Sản Xuất',
  `GhiChu` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chuanbihang`
--

CREATE TABLE `chuanbihang` (
  `CBH_ID` int(11) NOT NULL,
  `YCSX_ID` int(11) NOT NULL,
  `KHGH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến kế hoạch giao hàng',
  `BaoGiaID` int(11) NOT NULL,
  `SoCBH` varchar(100) DEFAULT NULL,
  `NgayTao` date DEFAULT NULL,
  `TenCongTy` varchar(255) DEFAULT NULL,
  `BoPhan` varchar(100) DEFAULT 'Kho - Logistic',
  `NgayGuiYCSX` date DEFAULT NULL,
  `NgayGiao` date DEFAULT NULL,
  `DangKiCongTruong` varchar(255) DEFAULT NULL,
  `DiaDiemGiaoHang` varchar(255) DEFAULT NULL,
  `NguoiNhanHang` varchar(100) DEFAULT NULL,
  `SdtNguoiNhan` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại người nhận hàng',
  `SoDon` varchar(100) DEFAULT NULL,
  `MaDon` varchar(100) DEFAULT NULL,
  `QuyCachThung` varchar(255) DEFAULT NULL,
  `LoaiXe` varchar(100) DEFAULT NULL,
  `XeGrap` varchar(100) DEFAULT NULL,
  `XeTai` varchar(100) DEFAULT NULL,
  `SoLaiXe` varchar(100) DEFAULT NULL,
  `PhuTrach` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật cuối cùng',
  `TrangThai` varchar(50) NOT NULL DEFAULT 'Mới tạo' COMMENT 'Trạng thái: Mới tạo, Đã chuẩn bị, Đã giao',
  `TrangThaiULA` varchar(50) NOT NULL DEFAULT 'Chờ xử lý' COMMENT 'Trạng thái sản xuất/chuẩn bị của các mặt hàng ULA',
  `TrangThaiPUR` varchar(50) DEFAULT 'Chờ sản xuất' COMMENT 'Trạng thái sản phẩm PUR',
  `TrangThaiECU` varchar(50) NOT NULL DEFAULT 'Chờ xử lý' COMMENT 'Trạng thái vật tư ECU: Chờ xử lý, Đã nhập kho VT',
  `TrangThaiNhapBTP` varchar(50) DEFAULT 'Chưa nhập' COMMENT 'Trạng thái nhập kho BTP: Chưa nhập, Đã nhập, Không cần',
  `TrangThaiXuatBTP` varchar(50) DEFAULT 'Chưa xuất' COMMENT 'Trạng thái xuất BTP để cắt: Chưa xuất, Đã xuất, Không cần',
  `TrangThaiNhapTP_PUR` varchar(50) DEFAULT 'Chưa nhập' COMMENT 'Trạng thái nhập TP PUR: Chưa nhập, Đã nhập, Không cần',
  `TrangThaiNhapTP_ULA` varchar(50) DEFAULT 'Chưa nhập' COMMENT 'Trạng thái nhập TP ULA: Chưa nhập, Đã nhập, Không cần',
  `TrangThaiDaiTreo` varchar(50) DEFAULT 'Chờ xử lý' COMMENT 'Trạng thái Đai treo: Chờ xử lý, Đã nhập, Đủ hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chucnang`
--

CREATE TABLE `chucnang` (
  `MaChucNang` varchar(50) NOT NULL COMMENT 'Mã định danh duy nhất cho chức năng',
  `TenChucNang` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Tên hiển thị trên menu',
  `Url` varchar(255) NOT NULL COMMENT 'Đường dẫn đến trang',
  `Icon` varchar(50) DEFAULT 'fas fa-circle-notch' COMMENT 'Lớp icon FontAwesome',
  `ParentMaChucNang` varchar(50) DEFAULT NULL,
  `ThuTuHienThi` int(11) DEFAULT 0,
  `IsMenuItem` tinyint(1) NOT NULL DEFAULT 1,
  `HienThiTrenMenu` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Hiển thị trên menu, 0 = Ẩn khỏi menu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chungchi_chatluong`
--

CREATE TABLE `chungchi_chatluong` (
  `CCCL_ID` int(11) NOT NULL,
  `PhieuXuatKhoID` int(11) NOT NULL COMMENT 'Liên kết với Phiếu Xuất Kho',
  `BBGH_ID` int(11) DEFAULT NULL,
  `SoCCCL` varchar(100) NOT NULL COMMENT 'Số chứng chỉ chất lượng, tự động tạo',
  `NgayCap` date NOT NULL COMMENT 'Ngày cấp chứng chỉ',
  `TenCongTyKhach` varchar(255) DEFAULT NULL COMMENT 'Tên công ty khách hàng',
  `DiaChiKhach` varchar(500) DEFAULT NULL COMMENT 'Địa chỉ khách hàng',
  `TenDuAn` varchar(255) DEFAULT NULL COMMENT 'Tên dự án',
  `DiaChiDuAn` varchar(500) DEFAULT NULL COMMENT 'Địa chỉ dự án có thể chỉnh sửa',
  `SanPham` varchar(500) DEFAULT NULL COMMENT 'Tên sản phẩm tổng quát có thể chỉnh sửa',
  `TieuChuanApDung` varchar(255) DEFAULT 'TCVN XXXX' COMMENT 'Tiêu chuẩn áp dụng',
  `NguoiKiemTra` varchar(100) DEFAULT NULL COMMENT 'Người kiểm tra chất lượng',
  `NguoiLap` int(11) DEFAULT NULL COMMENT 'ID người dùng lập chứng chỉ',
  `GhiChuChung` text DEFAULT NULL COMMENT 'Ghi chú chung cho chứng chỉ',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cochegia`
--

CREATE TABLE `cochegia` (
  `CoCheGiaID` int(11) NOT NULL,
  `MaCoChe` varchar(10) NOT NULL,
  `TenCoChe` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `PhanTramDieuChinh` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `congty`
--

CREATE TABLE `congty` (
  `CongTyID` int(11) NOT NULL,
  `MaCongTy` varchar(50) DEFAULT NULL COMMENT 'Mã công ty tùy chỉnh, không trùng lặp',
  `TenCongTy` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `DiaChi` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `Website` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ website của công ty',
  `MaSoThue` varchar(20) DEFAULT NULL,
  `SoDienThoaiChinh` varchar(20) DEFAULT NULL,
  `SoFax` varchar(30) DEFAULT NULL,
  `CoCheGiaID` int(11) DEFAULT NULL,
  `NhomKhachHang` varchar(50) DEFAULT 'Tiềm năng' COMMENT 'Phân nhóm khách hàng: Đại Lý, Chiến lược, Thân Thiết, Tiềm năng',
  `SoNgayThanhToan` int(11) DEFAULT 30 COMMENT 'Số ngày thanh toán theo hợp đồng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `CongTy_Comment`
--

CREATE TABLE `CongTy_Comment` (
  `CommentID` int(11) NOT NULL,
  `CongTyID` int(11) NOT NULL,
  `NguoiBinhLuan` varchar(255) DEFAULT 'System',
  `NoiDung` text NOT NULL,
  `NgayBinhLuan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dinh_muc_cat`
--

CREATE TABLE `dinh_muc_cat` (
  `DinhMucID` int(11) NOT NULL,
  `TenNhomDN` varchar(100) NOT NULL COMMENT 'Ví dụ: DN15-80',
  `HinhDang` varchar(50) NOT NULL DEFAULT 'Vuông' COMMENT 'Áp dụng cho gối Vuông hay Tròn',
  `MinDN` int(11) NOT NULL COMMENT 'Kích thước DN tối thiểu của nhóm',
  `MaxDN` int(11) NOT NULL COMMENT 'Kích thước DN tối đa của nhóm',
  `BanRong` int(11) NOT NULL COMMENT 'Bản rộng (50, 40, 30...)',
  `SoBoTrenCay` int(11) NOT NULL COMMENT 'Định mức: Số bộ cắt được trên 1 cây'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `YCSX_ID` int(11) NOT NULL,
  `BaoGiaID` int(11) NOT NULL COMMENT 'Liên kết với báo giá gốc',
  `CongTyID` int(11) DEFAULT NULL,
  `NguoiLienHeID` int(11) DEFAULT NULL,
  `DuAnID` int(11) DEFAULT NULL,
  `TenCongTy` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `NguoiNhan` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `TenDuAn` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `SoYCSX` varchar(50) NOT NULL COMMENT 'Số YCSX, ví dụ: YCSX-2024-001',
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Ngày tạo YCSX',
  `NgayGiaoDuKien` date DEFAULT NULL COMMENT 'Ngày giao hàng dự kiến',
  `NgayHoanThanhDuKien` date DEFAULT NULL COMMENT 'Ngày dự kiến sản xuất hoàn thành',
  `TrangThai` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'Mới tạo' COMMENT 'Trạng thái: Mới tạo, Đang sản xuất, Hoàn thành',
  `NeedsProduction` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Cờ xác định có cần sản xuất/đặt thêm hàng không (1=Có, 0=Không)',
  `BBGH_ID` int(11) DEFAULT NULL,
  `CBH_ID` int(11) DEFAULT NULL,
  `GhiChu` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `NguoiBaoGia` varchar(100) DEFAULT NULL COMMENT 'Tên người tạo báo giá gốc',
  `DiaChiGiaoHang` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ giao hàng từ báo giá',
  `DieuKienThanhToan` varchar(255) DEFAULT NULL COMMENT 'Điều kiện thanh toán từ báo giá',
  `TongTien` decimal(18,2) DEFAULT 0.00 COMMENT 'Tổng tiền sau thuế từ báo giá',
  `TrangThaiCBH` varchar(50) DEFAULT 'Chưa chuẩn bị' COMMENT 'Trạng thái chuẩn bị hàng',
  `PXK_ID` int(11) DEFAULT NULL COMMENT 'ID Phiếu Xuất Kho liên quan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang_phanbo_tonkho`
--

CREATE TABLE `donhang_phanbo_tonkho` (
  `PhanBoID` int(11) NOT NULL,
  `DonHangID` int(11) NOT NULL COMMENT 'ID của đơn hàng được gán',
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng',
  `SanPhamID` int(11) NOT NULL COMMENT 'ID của sản phẩm được gán',
  `SoLuongPhanBo` int(11) NOT NULL COMMENT 'Số lượng được gán từ kho',
  `NgayPhanBo` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `DuAn`
--

CREATE TABLE `DuAn` (
  `DuAnID` int(11) NOT NULL,
  `MaDuAn` varchar(100) DEFAULT NULL,
  `TenDuAn` varchar(255) NOT NULL,
  `DiaChi` text DEFAULT NULL,
  `TinhThanh` varchar(100) DEFAULT NULL,
  `LoaiHinh` varchar(100) DEFAULT NULL,
  `GiaTriDauTu` varchar(100) DEFAULT NULL,
  `NgayKhoiCong` varchar(100) DEFAULT NULL,
  `NgayHoanCong` varchar(100) DEFAULT NULL,
  `ChuDauTu` varchar(255) DEFAULT NULL,
  `TongThau` varchar(255) DEFAULT NULL,
  `ThauMEP` varchar(255) DEFAULT NULL,
  `DauMoiLienHe` varchar(255) DEFAULT NULL,
  `HangMucBaoGia` text DEFAULT NULL,
  `GiaTriDuKien` decimal(18,2) DEFAULT NULL,
  `TienDoLamViec` text DEFAULT NULL,
  `KetQua` varchar(100) DEFAULT NULL,
  `SalePhuTrach` varchar(255) DEFAULT NULL,
  `NgayTao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `DuAn_Comment`
--

CREATE TABLE `DuAn_Comment` (
  `CommentID` int(11) NOT NULL,
  `DuAnID` int(11) NOT NULL,
  `NguoiBinhLuan` varchar(255) DEFAULT 'System',
  `NoiDung` text NOT NULL,
  `NgayBinhLuan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `DuAn_HangMuc`
--

CREATE TABLE `DuAn_HangMuc` (
  `DuAnID` int(11) NOT NULL,
  `HangMucID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `HangMuc`
--

CREATE TABLE `HangMuc` (
  `HangMucID` int(11) NOT NULL,
  `TenHangMuc` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoadon`
--

CREATE TABLE `hoadon` (
  `HoaDonID` int(11) NOT NULL,
  `YCSX_ID` int(11) NOT NULL,
  `SoHoaDon` varchar(50) NOT NULL,
  `NgayXuat` date NOT NULL,
  `TenCongTy` varchar(255) DEFAULT NULL,
  `MaSoThue` varchar(50) DEFAULT NULL,
  `DiaChi` varchar(500) DEFAULT NULL,
  `TongTienTruocThue` decimal(18,2) NOT NULL,
  `ThueVAT_PhanTram` decimal(5,2) DEFAULT 8.00,
  `TienThueVAT` decimal(18,2) NOT NULL,
  `TongTienSauThue` decimal(18,2) NOT NULL,
  `GhiChu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin hóa đơn GTGT';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `kehoach_giaohang`
--

CREATE TABLE `kehoach_giaohang` (
  `KHGH_ID` int(11) NOT NULL,
  `DonHangID` int(11) NOT NULL,
  `SoKeHoach` varchar(100) DEFAULT NULL COMMENT 'Mã đợt giao hàng, VD: DH2500004-1',
  `NgayGiaoDuKien` date DEFAULT NULL,
  `TrangThai` varchar(50) NOT NULL DEFAULT 'Bản nháp' COMMENT 'Bản nháp, Chờ chuẩn bị hàng, Đã tạo CBH, Đã xuất kho, Hoàn thành',
  `NguoiNhanHang` varchar(100) DEFAULT NULL,
  `DiaDiemGiaoHang` varchar(255) DEFAULT NULL,
  `DangKiCongTruong` varchar(255) DEFAULT NULL,
  `XeGrap` varchar(100) DEFAULT NULL,
  `XeTai` varchar(100) DEFAULT NULL,
  `SoLaiXe` varchar(100) DEFAULT NULL,
  `QuyCachThung` varchar(255) DEFAULT NULL,
  `GhiChu` text DEFAULT NULL,
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'ID của Phiếu Chuẩn Bị Hàng sau khi tạo',
  `PXK_ID` int(11) DEFAULT NULL COMMENT 'ID của Phiếu Xuất Kho sau khi tạo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ke_hoach_doanh_thu`
--

CREATE TABLE `ke_hoach_doanh_thu` (
  `KeHoachID` int(11) NOT NULL,
  `Nam` int(4) NOT NULL COMMENT 'Năm kế hoạch',
  `MucTieuDoanhthu` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT 'Mục tiêu doanh thu năm',
  `MucTieuThang1` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 1',
  `MucTieuThang2` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 2',
  `MucTieuThang3` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 3',
  `MucTieuThang4` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 4',
  `MucTieuThang5` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 5',
  `MucTieuThang6` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 6',
  `MucTieuThang7` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 7',
  `MucTieuThang8` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 8',
  `MucTieuThang9` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 9',
  `MucTieuThang10` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 10',
  `MucTieuThang11` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 11',
  `MucTieuThang12` decimal(18,2) DEFAULT 0.00 COMMENT 'Mục tiêu tháng 12',
  `GhiChu` text DEFAULT NULL COMMENT 'Ghi chú về kế hoạch',
  `NguoiTao` int(11) DEFAULT NULL COMMENT 'ID người tạo kế hoạch',
  `NgayTao` datetime DEFAULT current_timestamp() COMMENT 'Ngày tạo kế hoạch',
  `NgayCapNhat` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu kế hoạch doanh thu theo năm và tháng';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhang`
--

CREATE TABLE `khachhang` (
  `KhachHangID` int(11) NOT NULL,
  `TenCongTy` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `NguoiLienHe` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `SoFax` varchar(30) DEFAULT NULL COMMENT 'Số Fax của khách hàng',
  `SoDiDong` varchar(30) DEFAULT NULL COMMENT 'Số di động của người liên hệ (HP)',
  `Email` varchar(100) DEFAULT NULL,
  `DiaChi` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `MaSoThue` varchar(20) DEFAULT NULL,
  `CoCheGiaID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `lang_code` varchar(5) NOT NULL,
  `lang_name` varchar(50) NOT NULL,
  `currency_name_native` varchar(50) NOT NULL,
  `currency_suffix` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lenh_san_xuat`
--

CREATE TABLE `lenh_san_xuat` (
  `LenhSX_ID` int(11) NOT NULL,
  `YCSX_ID` int(11) DEFAULT NULL COMMENT 'Liên kết với đơn hàng gốc của khách',
  `SoLenhSX` varchar(50) NOT NULL COMMENT 'Mã lệnh sản xuất, ví dụ LSX-2025-0001',
  `NgayTao` datetime NOT NULL DEFAULT current_timestamp(),
  `NgayHoanThanhUocTinh` date DEFAULT NULL,
  `NgayHoanThanhThucTe` date DEFAULT NULL,
  `TrangThai` varchar(50) NOT NULL DEFAULT 'Chờ sản xuất' COMMENT 'Trạng thái chung của cả lệnh',
  `TrangThaiNhapKho` varchar(50) NOT NULL DEFAULT 'Chưa nhập' COMMENT 'Trạng thái nhập kho: Chưa nhập, Đang nhập, Đã nhập đủ',
  `GhiChu` text DEFAULT NULL,
  `NguoiNhanSX` varchar(100) DEFAULT NULL COMMENT 'Người nhận của lệnh sản xuất',
  `BoPhanSX` varchar(100) DEFAULT NULL COMMENT 'Bộ phận/đơn vị sản xuất',
  `NgayYCSX` date DEFAULT curdate(),
  `LoaiLSX` varchar(50) DEFAULT NULL COMMENT 'Phân loại lệnh sản xuất, ví dụ: ULA, BTP',
  `NguoiYeuCau_ID` int(11) DEFAULT NULL COMMENT 'ID của người dùng tạo yêu cầu',
  `NgayCapNhat` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Thời gian cập nhật bản ghi gần nhất',
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng gốc'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsunhapxuat`
--

CREATE TABLE `lichsunhapxuat` (
  `LichSuID` int(11) NOT NULL,
  `SanPhamID` int(11) NOT NULL COMMENT 'Sản phẩm nào được giao dịch',
  `NgayGiaoDich` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Thời điểm diễn ra giao dịch',
  `LoaiGiaoDich` varchar(50) NOT NULL COMMENT 'Loại giao dịch: NHAP_KHO, XUAT_KHO, KIEM_KE',
  `SoLuongThayDoi` int(11) NOT NULL COMMENT 'Số lượng thay đổi. DƯƠNG (+) cho nhập, ÂM (-) cho xuất',
  `SoLuongSauGiaoDich` int(11) NOT NULL COMMENT 'Số lượng tồn kho của sản phẩm ngay sau giao dịch',
  `MaThamChieu` varchar(100) DEFAULT NULL COMMENT 'Mã tham chiếu tới chứng từ gốc (VD: SoPhieuNhapKho)',
  `DonGia` decimal(18,2) DEFAULT NULL COMMENT 'Đơn giá tại thời điểm giao dịch',
  `GhiChu` text DEFAULT NULL,
  `MaSanPham_Temp` varchar(150) DEFAULT NULL COMMENT 'Mã sản phẩm mới (tạm)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsunhapxuat_backup_before_update`
--

CREATE TABLE `lichsunhapxuat_backup_before_update` (
  `LichSuID` int(11) NOT NULL DEFAULT 0,
  `SanPhamID` int(11) NOT NULL COMMENT 'Sản phẩm nào được giao dịch',
  `NgayGiaoDich` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Thời điểm diễn ra giao dịch',
  `LoaiGiaoDich` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Loại giao dịch: NHAP_KHO, XUAT_KHO, KIEM_KE',
  `SoLuongThayDoi` int(11) NOT NULL COMMENT 'Số lượng thay đổi. DƯƠNG (+) cho nhập, ÂM (-) cho xuất',
  `SoLuongSauGiaoDich` int(11) NOT NULL COMMENT 'Số lượng tồn kho của sản phẩm ngay sau giao dịch',
  `MaThamChieu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã tham chiếu tới chứng từ gốc (VD: SoPhieuNhapKho)',
  `DonGia` decimal(18,2) DEFAULT NULL COMMENT 'Đơn giá tại thời điểm giao dịch',
  `GhiChu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `MaSanPham_Temp` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mã sản phẩm mới (tạm)'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaisanpham`
--

CREATE TABLE `loaisanpham` (
  `LoaiID` int(11) NOT NULL,
  `TenLoai` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loai_chi_phi`
--

CREATE TABLE `loai_chi_phi` (
  `LoaiChiPhiID` int(11) NOT NULL,
  `MaLoaiCP` varchar(50) NOT NULL,
  `TenLoaiCP` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `TrangThai` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `material_tree`
--

CREATE TABLE `material_tree` (
  `material_tree_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL COMMENT 'Liên kết với variant_id trong bảng variants',
  `MaCayVatTu` varchar(100) NOT NULL COMMENT 'Mã số cây vật tư',
  `TonKho` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tồn kho của cây vật tư này',
  `SoCayCanCat` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số cây cần cắt cho sản phẩm này (có thể là số thập phân nếu cắt lẻ)',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Cờ xác định đây có phải là cây vật tư chính cho sản phẩm này không (1=Có, 0=Không)',
  `GhiChu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trữ thông tin cây vật tư và số lượng cần cắt cho từng sản phẩm.';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nang_suat`
--

CREATE TABLE `nang_suat` (
  `id` int(11) NOT NULL,
  `bo_tren_ngay` int(11) NOT NULL DEFAULT 500 COMMENT 'Số lượng bộ sản phẩm sản xuất được mỗi ngày'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoidung`
--

CREATE TABLE `nguoidung` (
  `UserID` int(11) NOT NULL,
  `HoTen` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `ChucVu` varchar(100) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `TenDangNhap` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `MaVaiTro` varchar(50) DEFAULT NULL,
  `TrangThai` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Hoạt động, 0 = Bị khóa',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoilienhe`
--

CREATE TABLE `nguoilienhe` (
  `NguoiLienHeID` int(11) NOT NULL,
  `HoTen` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `SoDiDong` varchar(30) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `ChucVu` varchar(100) DEFAULT NULL,
  `CongTyID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhacungcap`
--

CREATE TABLE `nhacungcap` (
  `NhaCungCapID` int(11) NOT NULL COMMENT 'ID duy nhất của nhà cung cấp',
  `TenNhaCungCap` varchar(255) NOT NULL COMMENT 'Tên đầy đủ của nhà cung cấp',
  `DiaChi` varchar(500) DEFAULT NULL COMMENT 'Địa chỉ của nhà cung cấp',
  `SoDienThoai` varchar(30) DEFAULT NULL COMMENT 'Số điện thoại liên hệ',
  `Email` varchar(100) DEFAULT NULL COMMENT 'Email liên hệ',
  `MaSoThue` varchar(50) DEFAULT NULL COMMENT 'Mã số thuế của nhà cung cấp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhat_ky_san_xuat`
--

CREATE TABLE `nhat_ky_san_xuat` (
  `NhatKyID` int(11) NOT NULL,
  `ChiTiet_LSX_ID` int(11) NOT NULL,
  `NgayBaoCao` date NOT NULL,
  `SoLuongHoanThanh` decimal(15,2) NOT NULL DEFAULT 0.00,
  `NguoiThucHien_ID` int(11) DEFAULT NULL,
  `GhiChu` text DEFAULT NULL,
  `ThoiGianTao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhomsanpham`
--

CREATE TABLE `nhomsanpham` (
  `NhomID` int(11) NOT NULL,
  `TenNhomSanPham` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieunhapkho`
--

CREATE TABLE `phieunhapkho` (
  `PhieuNhapKhoID` int(11) NOT NULL COMMENT 'ID duy nhất của phiếu nhập kho',
  `SoPhieuNhapKho` varchar(50) NOT NULL COMMENT 'Số phiếu nhập kho, ví dụ: PNK-2025-0001',
  `LoaiPhieu` varchar(50) NOT NULL DEFAULT 'nhap_mua_hang' COMMENT 'Loại phiếu: nhap_mua_hang, nhap_btp_tu_sx, nhap_tp_tu_sx',
  `NgayNhap` date NOT NULL COMMENT 'Ngày thực hiện nhập kho',
  `NhaCungCapID` int(11) DEFAULT NULL COMMENT 'Liên kết với bảng nhà cung cấp',
  `LenhSX_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến Lệnh Sản Xuất nếu là nhập kho BTP',
  `YCSX_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến đơn hàng (donhang) khi nhập thành phẩm',
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng gốc',
  `NguoiGiaoHang` varchar(255) DEFAULT NULL COMMENT 'Tên người hoặc đơn vị giao hàng thực tế',
  `SoHoaDon` varchar(100) DEFAULT NULL COMMENT 'Số hóa đơn đi kèm (nếu có)',
  `LyDoNhap` text DEFAULT NULL COMMENT 'Lý do nhập kho',
  `NguoiTaoID` int(11) DEFAULT NULL,
  `TongTien` decimal(18,2) DEFAULT 0.00 COMMENT 'Tổng giá trị của phiếu nhập',
  `GhiChu` text DEFAULT NULL COMMENT 'Ghi chú chung cho cả phiếu nhập'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieunhapkho_btp`
--

CREATE TABLE `phieunhapkho_btp` (
  `PNK_BTP_ID` int(11) NOT NULL,
  `SoPhieuNhapKhoBTP` varchar(50) NOT NULL COMMENT 'Số phiếu, ví dụ: PNKBTP-20250808-0001',
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng (nếu có)',
  `LenhSX_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến lệnh sản xuất',
  `NgayNhap` date NOT NULL COMMENT 'Ngày thực hiện nhập kho',
  `NguoiTaoID` int(11) DEFAULT NULL COMMENT 'ID người dùng tạo phiếu',
  `LyDoNhap` text DEFAULT NULL COMMENT 'Lý do nhập kho',
  `GhiChu` text DEFAULT NULL,
  `TongTien` decimal(18,2) DEFAULT 0.00 COMMENT 'Tổng giá trị phiếu nhập (đặc biệt cho hàng mua ngoài)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieuxuatkho`
--

CREATE TABLE `phieuxuatkho` (
  `PhieuXuatKhoID` int(11) NOT NULL,
  `YCSX_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến đơn hàng (donhang)',
  `CBH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến phiếu chuẩn bị hàng',
  `BBGH_ID` int(11) DEFAULT NULL COMMENT 'Liên kết đến Biên Bản Giao Hàng',
  `SoPhieuXuat` varchar(50) NOT NULL COMMENT 'Số phiếu xuất kho, tự động tạo',
  `TenCongTy` varchar(255) DEFAULT NULL COMMENT 'Tên công ty đã được chỉnh sửa',
  `DiaChiCongTy` varchar(500) DEFAULT NULL COMMENT 'Địa chỉ công ty đã được chỉnh sửa',
  `LoaiPhieu` varchar(50) DEFAULT 'xuat_thanh_pham' COMMENT 'Loại phiếu: xuat_thanh_pham, xuat_btp_cat',
  `NgayXuat` date NOT NULL COMMENT 'Ngày thực hiện xuất kho',
  `NguoiNhan` varchar(255) DEFAULT NULL COMMENT 'Người nhận hàng hoặc bộ phận vận chuyển',
  `GhiChu` text DEFAULT NULL COMMENT 'Ghi chú chung cho phiếu xuất',
  `NguoiTaoID` int(11) DEFAULT NULL COMMENT 'ID người dùng tạo phiếu',
  `NguoiLapPhieu` varchar(100) DEFAULT NULL COMMENT 'Tên người lập phiếu',
  `ThuKho` varchar(100) DEFAULT NULL COMMENT 'Tên thủ kho',
  `NguoiGiaoHang` varchar(100) DEFAULT NULL COMMENT 'Tên người giao hàng',
  `NguoiNhanHang` varchar(100) DEFAULT NULL COMMENT 'Tên người nhận hàng',
  `CCCL_ID` int(11) DEFAULT NULL,
  `DiaChiGiaoHang` varchar(255) DEFAULT NULL COMMENT 'Địa chỉ giao hàng cho phiếu xuất',
  `LyDoXuatKho` text DEFAULT NULL COMMENT 'Lý do xuất kho đã được chỉnh sửa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_chi`
--

CREATE TABLE `phieu_chi` (
  `PhieuChiID` int(11) NOT NULL,
  `SoPhieuChi` varchar(50) NOT NULL COMMENT 'Mã phiếu chi tự động: PC-YYYYMMDD-XXXX',
  `NgayChi` date NOT NULL,
  `DoiTuongID` int(11) DEFAULT NULL,
  `LoaiDoiTuong` enum('khachhang','nhacungcap','nhanvien','khac') DEFAULT 'nhacungcap',
  `TenDoiTuong` varchar(255) NOT NULL,
  `DiaChiDoiTuong` varchar(500) DEFAULT NULL,
  `LyDoChi` varchar(255) NOT NULL COMMENT 'Lý do chi tiền',
  `LoaiChiPhi` varchar(100) DEFAULT NULL COMMENT 'Loại chi phí: Mua hàng, Lương, Văn phòng,...',
  `SoTien` decimal(18,2) NOT NULL,
  `HinhThucThanhToan` enum('tien_mat','chuyen_khoan','séc') DEFAULT 'tien_mat',
  `SoTaiKhoan` varchar(50) DEFAULT NULL,
  `NganHang` varchar(100) DEFAULT NULL,
  `NguoiNhan` varchar(100) DEFAULT NULL COMMENT 'Người nhận tiền',
  `DienThoaiNguoiNhan` varchar(20) DEFAULT NULL,
  `NguoiLap` int(11) DEFAULT NULL,
  `NguoiDuyet` int(11) DEFAULT NULL,
  `TrangThai` enum('cho_duyet','da_duyet','da_huy') DEFAULT 'cho_duyet',
  `NgayDuyet` datetime DEFAULT NULL,
  `PhieuNhapKhoID` int(11) DEFAULT NULL COMMENT 'Liên kết phiếu nhập kho nếu có',
  `GhiChu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_thu`
--

CREATE TABLE `phieu_thu` (
  `PhieuThuID` int(11) NOT NULL,
  `SoPhieuThu` varchar(50) NOT NULL COMMENT 'Mã phiếu thu tự động: PT-YYYYMMDD-XXXX',
  `NgayThu` date NOT NULL,
  `DoiTuongID` int(11) DEFAULT NULL,
  `LoaiDoiTuong` enum('khachhang','nhacungcap','khac') DEFAULT 'khachhang',
  `TenDoiTuong` varchar(255) NOT NULL,
  `DiaChiDoiTuong` varchar(500) DEFAULT NULL,
  `LyDoThu` varchar(255) NOT NULL COMMENT 'Lý do thu tiền',
  `SoTien` decimal(18,2) NOT NULL,
  `HinhThucThanhToan` enum('tien_mat','chuyen_khoan','séc') DEFAULT 'tien_mat',
  `SoTaiKhoan` varchar(50) DEFAULT NULL COMMENT 'Số TK nếu chuyển khoản',
  `NganHang` varchar(100) DEFAULT NULL,
  `NguoiNop` varchar(100) DEFAULT NULL COMMENT 'Người nộp tiền',
  `DienThoaiNguoiNop` varchar(20) DEFAULT NULL,
  `NguoiLap` int(11) DEFAULT NULL,
  `NguoiDuyet` int(11) DEFAULT NULL,
  `TrangThai` enum('cho_duyet','da_duyet','da_huy') DEFAULT 'cho_duyet',
  `NgayDuyet` datetime DEFAULT NULL,
  `DonHangID` int(11) DEFAULT NULL COMMENT 'Liên kết đơn hàng nếu có',
  `GhiChu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `base_sku` varchar(100) NOT NULL COMMENT 'Mã đại diện cho dòng sản phẩm, ví dụ: PUR-S',
  `sku_prefix` varchar(50) DEFAULT NULL COMMENT 'Tiền tố để tạo SKU, ví dụ: PUR-S',
  `name` varchar(255) NOT NULL COMMENT 'Tên chung của sản phẩm, ví dụ: Gối đỡ PU đế vuông',
  `HinhDang` varchar(50) DEFAULT NULL COMMENT 'Hình dạng của sản phẩm, ví dụ: Vuông, Tròn',
  `name_prefix` varchar(100) DEFAULT NULL COMMENT 'Tiền tố để tạo Tên, ví dụ: Gối đỡ đế vuông',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `base_unit_id` int(11) DEFAULT NULL COMMENT 'Đơn vị cơ sở của sản phẩm',
  `attribute_config` text DEFAULT NULL COMMENT 'Lưu JSON danh sách ID thuộc tính được hiển thị',
  `sku_name_formula` text DEFAULT NULL COMMENT 'Lưu JSON công thức tạo SKU và Tên'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_groups`
--

CREATE TABLE `product_groups` (
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_types`
--

CREATE TABLE `product_types` (
  `type_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quanly_congno`
--

CREATE TABLE `quanly_congno` (
  `CongNoID` int(11) NOT NULL COMMENT 'ID duy nhất',
  `YCSX_ID` int(11) NOT NULL COMMENT 'Khóa ngoại liên kết đến bảng donhang',
  `SoTienTamUng` decimal(18,2) DEFAULT 0.00 COMMENT 'Số tiền khách đã tạm ứng',
  `GiaTriConLai` decimal(18,2) DEFAULT 0.00 COMMENT 'Giá trị còn lại cần thanh toán',
  `NgayXuatHoaDon` date DEFAULT NULL COMMENT 'Ngày kế toán xuất hóa đơn',
  `ThoiHanThanhToan` date DEFAULT NULL COMMENT 'Hạn cuối cùng khách hàng phải thanh toán',
  `NgayThanhToan` date DEFAULT NULL COMMENT 'Ngày khách hàng thực tế thanh toán',
  `TrangThaiThanhToan` varchar(50) DEFAULT 'Chưa thanh toán',
  `DonViTra` varchar(255) DEFAULT NULL COMMENT 'Tên đơn vị thực tế trả tiền',
  `GhiChu` text DEFAULT NULL COMMENT 'Ghi chú của kế toán',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng theo dõi công nợ cho bộ phận kế toán';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quotation_labels`
--

CREATE TABLE `quotation_labels` (
  `id` int(11) NOT NULL,
  `label_key` varchar(100) NOT NULL,
  `label_vi` varchar(255) NOT NULL,
  `label_zh` varchar(255) NOT NULL,
  `label_en` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham_nhacungcap`
--

CREATE TABLE `sanpham_nhacungcap` (
  `SanPhamNCCID` int(11) NOT NULL COMMENT 'ID duy nhất của sản phẩm',
  `NhaCungCapID` int(11) NOT NULL COMMENT 'Khóa ngoại, liên kết với bảng nhacungcap',
  `MaSanPham` varchar(100) DEFAULT NULL COMMENT 'Mã sản phẩm/dịch vụ của nhà cung cấp',
  `TenSanPham` varchar(255) NOT NULL COMMENT 'Tên sản phẩm/dịch vụ',
  `DonViTinh` varchar(50) DEFAULT NULL COMMENT 'Đơn vị tính (VD: cái, kg, m2, dịch vụ)',
  `DonGia` decimal(18,2) DEFAULT 0.00 COMMENT 'Đơn giá gần nhất',
  `GhiChu` text DEFAULT NULL COMMENT 'Ghi chú thêm về sản phẩm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `so_quy`
--

CREATE TABLE `so_quy` (
  `SoQuyID` int(11) NOT NULL,
  `NgayGhiSo` date NOT NULL,
  `LoaiGiaoDich` enum('thu','chi') NOT NULL COMMENT 'Loại giao dịch',
  `SoChungTu` varchar(50) DEFAULT NULL COMMENT 'Số chứng từ (Phiếu thu/chi)',
  `NoiDung` text NOT NULL COMMENT 'Diễn giải nội dung',
  `DoiTuong` varchar(255) DEFAULT NULL COMMENT 'Khách hàng/NCC',
  `DoiTuongID` int(11) DEFAULT NULL COMMENT 'ID của đối tượng',
  `LoaiDoiTuong` enum('khachhang','nhacungcap','khac') DEFAULT 'khac',
  `SoTienThu` decimal(18,2) DEFAULT 0.00,
  `SoTienChi` decimal(18,2) DEFAULT 0.00,
  `SoDu` decimal(18,2) DEFAULT 0.00 COMMENT 'Số dư sau giao dịch',
  `NguoiLap` int(11) DEFAULT NULL,
  `GhiChu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `units`
--

CREATE TABLE `units` (
  `unit_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro`
--

CREATE TABLE `vaitro` (
  `MaVaiTro` varchar(50) NOT NULL,
  `TenVaiTro` varchar(100) NOT NULL,
  `MoTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vaitro_chucnang`
--

CREATE TABLE `vaitro_chucnang` (
  `ID` int(11) NOT NULL,
  `MaVaiTro` varchar(50) NOT NULL,
  `MaChucNang` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `variants`
--

CREATE TABLE `variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `LoaiID` int(11) DEFAULT NULL,
  `variant_sku` varchar(150) NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL COMMENT 'Tên chi tiết của biến thể',
  `price` decimal(18,2) NOT NULL DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `sku_suffix` varchar(50) DEFAULT NULL COMMENT 'Hậu tố của SKU biến thể (ví dụ: TQ, HT)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `variant_attributes`
--

CREATE TABLE `variant_attributes` (
  `variant_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `variant_inventory`
--

CREATE TABLE `variant_inventory` (
  `inventory_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `minimum_stock_level` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `version_check_logs`
--

CREATE TABLE `version_check_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `current_version` varchar(20) DEFAULT '',
  `check_time` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `workflow_statuses`
--

CREATE TABLE `workflow_statuses` (
  `status_id` int(11) NOT NULL,
  `status_key` varchar(50) NOT NULL COMMENT 'Khóa định danh duy nhất, dùng trong code',
  `status_name` varchar(100) NOT NULL COMMENT 'Tên hiển thị cho người dùng (tiếng Việt)',
  `description` varchar(255) DEFAULT NULL COMMENT 'Mô tả chi tiết về trạng thái',
  `flow_type` enum('PUR','ULA','GENERAL') NOT NULL COMMENT 'Loại quy trình mà trạng thái này thuộc về',
  `step_order` int(11) NOT NULL COMMENT 'Thứ tự của bước trong quy trình'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `app_versions`
--
ALTER TABLE `app_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `version` (`version`),
  ADD KEY `idx_version_number` (`version_number`),
  ADD KEY `idx_active` (`is_active`);

--
-- Chỉ mục cho bảng `area_templates`
--
ALTER TABLE `area_templates`
  ADD PRIMARY KEY (`TemplateID`),
  ADD KEY `fk_template_nguoitao` (`NguoiTao`);

--
-- Chỉ mục cho bảng `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`attribute_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `attribute_options`
--
ALTER TABLE `attribute_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `attribute_id` (`attribute_id`);

--
-- Chỉ mục cho bảng `bang_dinh_muc_dong_thung`
--
ALTER TABLE `bang_dinh_muc_dong_thung`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tra_cuu_dinh_muc` (`duong_kinh_trong`,`ban_rong`,`do_day`);

--
-- Chỉ mục cho bảng `baogia`
--
ALTER TABLE `baogia`
  ADD PRIMARY KEY (`BaoGiaID`),
  ADD UNIQUE KEY `SoBaoGia` (`SoBaoGia`),
  ADD KEY `fk_baogia_congty` (`CongTyID`),
  ADD KEY `fk_baogia_nguoilienhe` (`NguoiLienHeID`),
  ADD KEY `fk_baogia_khachhang` (`KhachHangID`),
  ADD KEY `fk_baogia_nguoitao` (`NguoiTao`);

--
-- Chỉ mục cho bảng `bienbangiaohang`
--
ALTER TABLE `bienbangiaohang`
  ADD PRIMARY KEY (`BBGH_ID`),
  ADD UNIQUE KEY `SoBBGH` (`SoBBGH`),
  ADD KEY `YCSX_ID` (`YCSX_ID`),
  ADD KEY `BaoGiaID` (`BaoGiaID`),
  ADD KEY `fk_bbgh_pxk` (`PhieuXuatKhoID`);

--
-- Chỉ mục cho bảng `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD PRIMARY KEY (`BugReportID`),
  ADD KEY `fk_bugreport_user` (`UserID`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_priority` (`Priority`);

--
-- Chỉ mục cho bảng `bug_report_comments`
--
ALTER TABLE `bug_report_comments`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `fk_comment_bugreport` (`BugReportID`),
  ADD KEY `fk_comment_user` (`UserID`);

--
-- Chỉ mục cho bảng `cauhinh_sanxuat`
--
ALTER TABLE `cauhinh_sanxuat`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `TenThietLap` (`TenThietLap`);

--
-- Chỉ mục cho bảng `chitietbaogia`
--
ALTER TABLE `chitietbaogia`
  ADD PRIMARY KEY (`ChiTietID`),
  ADD KEY `BaoGiaID` (`BaoGiaID`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `chitietbienbangiaohang`
--
ALTER TABLE `chitietbienbangiaohang`
  ADD PRIMARY KEY (`ChiTietBBGH_ID`),
  ADD KEY `BBGH_ID` (`BBGH_ID`);

--
-- Chỉ mục cho bảng `chitietchuanbihang`
--
ALTER TABLE `chitietchuanbihang`
  ADD PRIMARY KEY (`ChiTietCBH_ID`),
  ADD KEY `CBH_ID` (`CBH_ID`);

--
-- Chỉ mục cho bảng `chitietphieunhapkho`
--
ALTER TABLE `chitietphieunhapkho`
  ADD PRIMARY KEY (`ChiTietPNK_ID`),
  ADD KEY `fk_ctpnk_pnk` (`PhieuNhapKhoID`),
  ADD KEY `fk_ctpnk_sp` (`SanPhamID`);

--
-- Chỉ mục cho bảng `chitiet_btp_cbh`
--
ALTER TABLE `chitiet_btp_cbh`
  ADD PRIMARY KEY (`ChiTietBTP_ID`),
  ADD KEY `CBH_ID` (`CBH_ID`);

--
-- Chỉ mục cho bảng `chitiet_chungchi_chatluong`
--
ALTER TABLE `chitiet_chungchi_chatluong`
  ADD PRIMARY KEY (`ChiTietCCCL_ID`),
  ADD KEY `CCCL_ID` (`CCCL_ID`),
  ADD KEY `SanPhamID` (`SanPhamID`);

--
-- Chỉ mục cho bảng `chitiet_donhang`
--
ALTER TABLE `chitiet_donhang`
  ADD PRIMARY KEY (`ChiTiet_YCSX_ID`),
  ADD KEY `DonHangID` (`DonHangID`),
  ADD KEY `fk_ctdh_sanpham_idx` (`SanPhamID`);

--
-- Chỉ mục cho bảng `chitiet_ecu_cbh`
--
ALTER TABLE `chitiet_ecu_cbh`
  ADD PRIMARY KEY (`ChiTietEcuCBH_ID`),
  ADD KEY `FK_CBH_ID_ECU` (`CBH_ID`);

--
-- Chỉ mục cho bảng `chitiet_hoadon`
--
ALTER TABLE `chitiet_hoadon`
  ADD PRIMARY KEY (`ChiTietHD_ID`),
  ADD KEY `HoaDonID` (`HoaDonID`);

--
-- Chỉ mục cho bảng `chitiet_kehoach_giaohang`
--
ALTER TABLE `chitiet_kehoach_giaohang`
  ADD PRIMARY KEY (`ChiTiet_KHGH_ID`),
  ADD KEY `KHGH_ID` (`KHGH_ID`),
  ADD KEY `ChiTiet_DonHang_ID` (`ChiTiet_DonHang_ID`);

--
-- Chỉ mục cho bảng `chitiet_lenh_san_xuat`
--
ALTER TABLE `chitiet_lenh_san_xuat`
  ADD PRIMARY KEY (`ChiTiet_LSX_ID`),
  ADD KEY `LenhSX_ID` (`LenhSX_ID`),
  ADD KEY `SanPhamID` (`SanPhamID`);

--
-- Chỉ mục cho bảng `chitiet_phieuxuatkho`
--
ALTER TABLE `chitiet_phieuxuatkho`
  ADD PRIMARY KEY (`ChiTietPXK_ID`);

--
-- Chỉ mục cho bảng `chitiet_pnk_btp`
--
ALTER TABLE `chitiet_pnk_btp`
  ADD PRIMARY KEY (`ChiTiet_PNKBTP_ID`),
  ADD KEY `PNK_BTP_ID` (`PNK_BTP_ID`),
  ADD KEY `BTP_ID` (`BTP_ID`);

--
-- Chỉ mục cho bảng `chuanbihang`
--
ALTER TABLE `chuanbihang`
  ADD PRIMARY KEY (`CBH_ID`),
  ADD KEY `fk_cbh_ycsx` (`YCSX_ID`);

--
-- Chỉ mục cho bảng `chucnang`
--
ALTER TABLE `chucnang`
  ADD PRIMARY KEY (`MaChucNang`),
  ADD KEY `fk_chucnang_parent` (`ParentMaChucNang`);

--
-- Chỉ mục cho bảng `chungchi_chatluong`
--
ALTER TABLE `chungchi_chatluong`
  ADD PRIMARY KEY (`CCCL_ID`),
  ADD UNIQUE KEY `SoCCCL` (`SoCCCL`),
  ADD KEY `PhieuXuatKhoID` (`PhieuXuatKhoID`),
  ADD KEY `NguoiLap` (`NguoiLap`),
  ADD KEY `fk_cccl_bbgh` (`BBGH_ID`);

--
-- Chỉ mục cho bảng `cochegia`
--
ALTER TABLE `cochegia`
  ADD PRIMARY KEY (`CoCheGiaID`),
  ADD UNIQUE KEY `MaCoChe` (`MaCoChe`);

--
-- Chỉ mục cho bảng `congty`
--
ALTER TABLE `congty`
  ADD PRIMARY KEY (`CongTyID`),
  ADD UNIQUE KEY `TenCongTy` (`TenCongTy`);

--
-- Chỉ mục cho bảng `CongTy_Comment`
--
ALTER TABLE `CongTy_Comment`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `FK_Comment_CongTy` (`CongTyID`);

--
-- Chỉ mục cho bảng `dinh_muc_cat`
--
ALTER TABLE `dinh_muc_cat`
  ADD PRIMARY KEY (`DinhMucID`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`YCSX_ID`),
  ADD UNIQUE KEY `SoYCSX` (`SoYCSX`),
  ADD KEY `BaoGiaID` (`BaoGiaID`),
  ADD KEY `fk_ycsx_cbh` (`CBH_ID`),
  ADD KEY `fk_donhang_bbgh` (`BBGH_ID`),
  ADD KEY `fk_donhang_congty` (`CongTyID`),
  ADD KEY `fk_donhang_nguoilienhe` (`NguoiLienHeID`),
  ADD KEY `fk_donhang_duan` (`DuAnID`);

--
-- Chỉ mục cho bảng `donhang_phanbo_tonkho`
--
ALTER TABLE `donhang_phanbo_tonkho`
  ADD PRIMARY KEY (`PhanBoID`),
  ADD UNIQUE KEY `uq_cbh_sanpham` (`CBH_ID`,`SanPhamID`),
  ADD KEY `DonHangID` (`DonHangID`),
  ADD KEY `SanPhamID` (`SanPhamID`),
  ADD KEY `idx_cbh_id` (`CBH_ID`);

--
-- Chỉ mục cho bảng `DuAn`
--
ALTER TABLE `DuAn`
  ADD PRIMARY KEY (`DuAnID`),
  ADD UNIQUE KEY `MaDuAn` (`MaDuAn`);

--
-- Chỉ mục cho bảng `DuAn_Comment`
--
ALTER TABLE `DuAn_Comment`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `FK_Comment_DuAn` (`DuAnID`);

--
-- Chỉ mục cho bảng `DuAn_HangMuc`
--
ALTER TABLE `DuAn_HangMuc`
  ADD PRIMARY KEY (`DuAnID`,`HangMucID`),
  ADD KEY `FK_DuAn` (`DuAnID`),
  ADD KEY `FK_HangMuc` (`HangMucID`);

--
-- Chỉ mục cho bảng `HangMuc`
--
ALTER TABLE `HangMuc`
  ADD PRIMARY KEY (`HangMucID`),
  ADD UNIQUE KEY `TenHangMuc` (`TenHangMuc`);

--
-- Chỉ mục cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  ADD PRIMARY KEY (`HoaDonID`),
  ADD UNIQUE KEY `SoHoaDon` (`SoHoaDon`),
  ADD KEY `YCSX_ID` (`YCSX_ID`);

--
-- Chỉ mục cho bảng `kehoach_giaohang`
--
ALTER TABLE `kehoach_giaohang`
  ADD PRIMARY KEY (`KHGH_ID`),
  ADD KEY `DonHangID` (`DonHangID`);

--
-- Chỉ mục cho bảng `ke_hoach_doanh_thu`
--
ALTER TABLE `ke_hoach_doanh_thu`
  ADD PRIMARY KEY (`KeHoachID`),
  ADD UNIQUE KEY `unique_year` (`Nam`),
  ADD KEY `fk_kehoach_nguoitao` (`NguoiTao`);

--
-- Chỉ mục cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`KhachHangID`),
  ADD KEY `CoCheGiaID` (`CoCheGiaID`);

--
-- Chỉ mục cho bảng `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lang_code` (`lang_code`);

--
-- Chỉ mục cho bảng `lenh_san_xuat`
--
ALTER TABLE `lenh_san_xuat`
  ADD PRIMARY KEY (`LenhSX_ID`),
  ADD UNIQUE KEY `SoLenhSX` (`SoLenhSX`),
  ADD KEY `YCSX_ID` (`YCSX_ID`),
  ADD KEY `fk_lsx_cbh` (`CBH_ID`);

--
-- Chỉ mục cho bảng `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  ADD PRIMARY KEY (`LichSuID`),
  ADD KEY `idx_sanpham` (`SanPhamID`);

--
-- Chỉ mục cho bảng `loaisanpham`
--
ALTER TABLE `loaisanpham`
  ADD PRIMARY KEY (`LoaiID`),
  ADD UNIQUE KEY `TenLoai` (`TenLoai`);

--
-- Chỉ mục cho bảng `loai_chi_phi`
--
ALTER TABLE `loai_chi_phi`
  ADD PRIMARY KEY (`LoaiChiPhiID`),
  ADD UNIQUE KEY `MaLoaiCP` (`MaLoaiCP`);

--
-- Chỉ mục cho bảng `material_tree`
--
ALTER TABLE `material_tree`
  ADD PRIMARY KEY (`material_tree_id`),
  ADD UNIQUE KEY `uq_variant_matcode` (`variant_id`,`MaCayVatTu`),
  ADD KEY `idx_variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `nang_suat`
--
ALTER TABLE `nang_suat`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `TenDangNhap` (`TenDangNhap`),
  ADD KEY `fk_nguoidung_vaitro_idx` (`MaVaiTro`);

--
-- Chỉ mục cho bảng `nguoilienhe`
--
ALTER TABLE `nguoilienhe`
  ADD PRIMARY KEY (`NguoiLienHeID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `CongTyID` (`CongTyID`);

--
-- Chỉ mục cho bảng `nhacungcap`
--
ALTER TABLE `nhacungcap`
  ADD PRIMARY KEY (`NhaCungCapID`);

--
-- Chỉ mục cho bảng `nhat_ky_san_xuat`
--
ALTER TABLE `nhat_ky_san_xuat`
  ADD PRIMARY KEY (`NhatKyID`),
  ADD KEY `ChiTiet_LSX_ID` (`ChiTiet_LSX_ID`);

--
-- Chỉ mục cho bảng `nhomsanpham`
--
ALTER TABLE `nhomsanpham`
  ADD PRIMARY KEY (`NhomID`),
  ADD UNIQUE KEY `TenNhomSanPham` (`TenNhomSanPham`);

--
-- Chỉ mục cho bảng `phieunhapkho`
--
ALTER TABLE `phieunhapkho`
  ADD PRIMARY KEY (`PhieuNhapKhoID`),
  ADD UNIQUE KEY `SoPhieuNhapKho` (`SoPhieuNhapKho`),
  ADD KEY `fk_pnk_ncc` (`NhaCungCapID`),
  ADD KEY `idx_lenhsx_id` (`LenhSX_ID`),
  ADD KEY `idx_nguoitao` (`NguoiTaoID`),
  ADD KEY `fk_pnk_cbh` (`CBH_ID`);

--
-- Chỉ mục cho bảng `phieunhapkho_btp`
--
ALTER TABLE `phieunhapkho_btp`
  ADD PRIMARY KEY (`PNK_BTP_ID`),
  ADD UNIQUE KEY `SoPhieuNhapKhoBTP` (`SoPhieuNhapKhoBTP`),
  ADD KEY `CBH_ID` (`CBH_ID`),
  ADD KEY `LenhSX_ID` (`LenhSX_ID`),
  ADD KEY `NguoiTaoID` (`NguoiTaoID`);

--
-- Chỉ mục cho bảng `phieuxuatkho`
--
ALTER TABLE `phieuxuatkho`
  ADD PRIMARY KEY (`PhieuXuatKhoID`),
  ADD UNIQUE KEY `SoPhieuXuat` (`SoPhieuXuat`),
  ADD KEY `fk_pxk_ycsx` (`YCSX_ID`),
  ADD KEY `fk_pxk_nguoitao` (`NguoiTaoID`),
  ADD KEY `fk_pxk_cccl` (`CCCL_ID`),
  ADD KEY `fk_pxk_cbh` (`CBH_ID`);

--
-- Chỉ mục cho bảng `phieu_chi`
--
ALTER TABLE `phieu_chi`
  ADD PRIMARY KEY (`PhieuChiID`),
  ADD UNIQUE KEY `SoPhieuChi` (`SoPhieuChi`),
  ADD KEY `idx_ngaychi` (`NgayChi`),
  ADD KEY `idx_trangthai` (`TrangThai`),
  ADD KEY `idx_loaichiphi` (`LoaiChiPhi`);

--
-- Chỉ mục cho bảng `phieu_thu`
--
ALTER TABLE `phieu_thu`
  ADD PRIMARY KEY (`PhieuThuID`),
  ADD UNIQUE KEY `SoPhieuThu` (`SoPhieuThu`),
  ADD KEY `idx_ngaythu` (`NgayThu`),
  ADD KEY `idx_trangthai` (`TrangThai`),
  ADD KEY `idx_doituong` (`DoiTuongID`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `base_sku` (`base_sku`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `fk_product_base_unit` (`base_unit_id`);

--
-- Chỉ mục cho bảng `product_groups`
--
ALTER TABLE `product_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `product_types`
--
ALTER TABLE `product_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `quanly_congno`
--
ALTER TABLE `quanly_congno`
  ADD PRIMARY KEY (`CongNoID`),
  ADD UNIQUE KEY `YCSX_ID` (`YCSX_ID`);

--
-- Chỉ mục cho bảng `quotation_labels`
--
ALTER TABLE `quotation_labels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `label_key` (`label_key`);

--
-- Chỉ mục cho bảng `sanpham_nhacungcap`
--
ALTER TABLE `sanpham_nhacungcap`
  ADD PRIMARY KEY (`SanPhamNCCID`),
  ADD KEY `NhaCungCapID` (`NhaCungCapID`);

--
-- Chỉ mục cho bảng `so_quy`
--
ALTER TABLE `so_quy`
  ADD PRIMARY KEY (`SoQuyID`),
  ADD KEY `idx_ngay` (`NgayGhiSo`),
  ADD KEY `idx_loai` (`LoaiGiaoDich`),
  ADD KEY `idx_nguoilap` (`NguoiLap`);

--
-- Chỉ mục cho bảng `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Chỉ mục cho bảng `vaitro`
--
ALTER TABLE `vaitro`
  ADD PRIMARY KEY (`MaVaiTro`);

--
-- Chỉ mục cho bảng `vaitro_chucnang`
--
ALTER TABLE `vaitro_chucnang`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_vaitro_chucnang` (`MaVaiTro`,`MaChucNang`),
  ADD KEY `fk_vaitro_chucnang_chucnang_idx` (`MaChucNang`);

--
-- Chỉ mục cho bảng `variants`
--
ALTER TABLE `variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `variant_sku` (`variant_sku`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_variant_loai` (`LoaiID`);

--
-- Chỉ mục cho bảng `variant_attributes`
--
ALTER TABLE `variant_attributes`
  ADD PRIMARY KEY (`variant_id`,`option_id`),
  ADD KEY `option_id` (`option_id`);

--
-- Chỉ mục cho bảng `variant_inventory`
--
ALTER TABLE `variant_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `uk_variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `version_check_logs`
--
ALTER TABLE `version_check_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_check_time` (`check_time`),
  ADD KEY `idx_current_version` (`current_version`);

--
-- Chỉ mục cho bảng `workflow_statuses`
--
ALTER TABLE `workflow_statuses`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_key` (`status_key`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `app_versions`
--
ALTER TABLE `app_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `area_templates`
--
ALTER TABLE `area_templates`
  MODIFY `TemplateID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `attributes`
--
ALTER TABLE `attributes`
  MODIFY `attribute_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `attribute_options`
--
ALTER TABLE `attribute_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bang_dinh_muc_dong_thung`
--
ALTER TABLE `bang_dinh_muc_dong_thung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `baogia`
--
ALTER TABLE `baogia`
  MODIFY `BaoGiaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bienbangiaohang`
--
ALTER TABLE `bienbangiaohang`
  MODIFY `BBGH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bug_reports`
--
ALTER TABLE `bug_reports`
  MODIFY `BugReportID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `bug_report_comments`
--
ALTER TABLE `bug_report_comments`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cauhinh_sanxuat`
--
ALTER TABLE `cauhinh_sanxuat`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietbaogia`
--
ALTER TABLE `chitietbaogia`
  MODIFY `ChiTietID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietbienbangiaohang`
--
ALTER TABLE `chitietbienbangiaohang`
  MODIFY `ChiTietBBGH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietchuanbihang`
--
ALTER TABLE `chitietchuanbihang`
  MODIFY `ChiTietCBH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitietphieunhapkho`
--
ALTER TABLE `chitietphieunhapkho`
  MODIFY `ChiTietPNK_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất của dòng chi tiết';

--
-- AUTO_INCREMENT cho bảng `chitiet_btp_cbh`
--
ALTER TABLE `chitiet_btp_cbh`
  MODIFY `ChiTietBTP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_chungchi_chatluong`
--
ALTER TABLE `chitiet_chungchi_chatluong`
  MODIFY `ChiTietCCCL_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_donhang`
--
ALTER TABLE `chitiet_donhang`
  MODIFY `ChiTiet_YCSX_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_ecu_cbh`
--
ALTER TABLE `chitiet_ecu_cbh`
  MODIFY `ChiTietEcuCBH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_hoadon`
--
ALTER TABLE `chitiet_hoadon`
  MODIFY `ChiTietHD_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_kehoach_giaohang`
--
ALTER TABLE `chitiet_kehoach_giaohang`
  MODIFY `ChiTiet_KHGH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_lenh_san_xuat`
--
ALTER TABLE `chitiet_lenh_san_xuat`
  MODIFY `ChiTiet_LSX_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_phieuxuatkho`
--
ALTER TABLE `chitiet_phieuxuatkho`
  MODIFY `ChiTietPXK_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chitiet_pnk_btp`
--
ALTER TABLE `chitiet_pnk_btp`
  MODIFY `ChiTiet_PNKBTP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chuanbihang`
--
ALTER TABLE `chuanbihang`
  MODIFY `CBH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chungchi_chatluong`
--
ALTER TABLE `chungchi_chatluong`
  MODIFY `CCCL_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cochegia`
--
ALTER TABLE `cochegia`
  MODIFY `CoCheGiaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `congty`
--
ALTER TABLE `congty`
  MODIFY `CongTyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `CongTy_Comment`
--
ALTER TABLE `CongTy_Comment`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `dinh_muc_cat`
--
ALTER TABLE `dinh_muc_cat`
  MODIFY `DinhMucID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `donhang`
--
ALTER TABLE `donhang`
  MODIFY `YCSX_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `donhang_phanbo_tonkho`
--
ALTER TABLE `donhang_phanbo_tonkho`
  MODIFY `PhanBoID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `DuAn`
--
ALTER TABLE `DuAn`
  MODIFY `DuAnID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `DuAn_Comment`
--
ALTER TABLE `DuAn_Comment`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `HangMuc`
--
ALTER TABLE `HangMuc`
  MODIFY `HangMucID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  MODIFY `HoaDonID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `kehoach_giaohang`
--
ALTER TABLE `kehoach_giaohang`
  MODIFY `KHGH_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `ke_hoach_doanh_thu`
--
ALTER TABLE `ke_hoach_doanh_thu`
  MODIFY `KeHoachID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `KhachHangID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lenh_san_xuat`
--
ALTER TABLE `lenh_san_xuat`
  MODIFY `LenhSX_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lichsunhapxuat`
--
ALTER TABLE `lichsunhapxuat`
  MODIFY `LichSuID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `loaisanpham`
--
ALTER TABLE `loaisanpham`
  MODIFY `LoaiID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `loai_chi_phi`
--
ALTER TABLE `loai_chi_phi`
  MODIFY `LoaiChiPhiID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `material_tree`
--
ALTER TABLE `material_tree`
  MODIFY `material_tree_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nang_suat`
--
ALTER TABLE `nang_suat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoilienhe`
--
ALTER TABLE `nguoilienhe`
  MODIFY `NguoiLienHeID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nhacungcap`
--
ALTER TABLE `nhacungcap`
  MODIFY `NhaCungCapID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất của nhà cung cấp';

--
-- AUTO_INCREMENT cho bảng `nhat_ky_san_xuat`
--
ALTER TABLE `nhat_ky_san_xuat`
  MODIFY `NhatKyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nhomsanpham`
--
ALTER TABLE `nhomsanpham`
  MODIFY `NhomID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phieunhapkho`
--
ALTER TABLE `phieunhapkho`
  MODIFY `PhieuNhapKhoID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất của phiếu nhập kho';

--
-- AUTO_INCREMENT cho bảng `phieunhapkho_btp`
--
ALTER TABLE `phieunhapkho_btp`
  MODIFY `PNK_BTP_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phieuxuatkho`
--
ALTER TABLE `phieuxuatkho`
  MODIFY `PhieuXuatKhoID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phieu_chi`
--
ALTER TABLE `phieu_chi`
  MODIFY `PhieuChiID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `phieu_thu`
--
ALTER TABLE `phieu_thu`
  MODIFY `PhieuThuID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_groups`
--
ALTER TABLE `product_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_types`
--
ALTER TABLE `product_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `quanly_congno`
--
ALTER TABLE `quanly_congno`
  MODIFY `CongNoID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất';

--
-- AUTO_INCREMENT cho bảng `quotation_labels`
--
ALTER TABLE `quotation_labels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `sanpham_nhacungcap`
--
ALTER TABLE `sanpham_nhacungcap`
  MODIFY `SanPhamNCCID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID duy nhất của sản phẩm';

--
-- AUTO_INCREMENT cho bảng `so_quy`
--
ALTER TABLE `so_quy`
  MODIFY `SoQuyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `units`
--
ALTER TABLE `units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `vaitro_chucnang`
--
ALTER TABLE `vaitro_chucnang`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `variants`
--
ALTER TABLE `variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `variant_inventory`
--
ALTER TABLE `variant_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `version_check_logs`
--
ALTER TABLE `version_check_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `workflow_statuses`
--
ALTER TABLE `workflow_statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ràng buộc đối với các bảng kết xuất
--

--
-- Ràng buộc cho bảng `attribute_options`
--
ALTER TABLE `attribute_options`
  ADD CONSTRAINT `attribute_options_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`attribute_id`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `baogia`
--
ALTER TABLE `baogia`
  ADD CONSTRAINT `baogia_ibfk_1` FOREIGN KEY (`KhachHangID`) REFERENCES `khachhang` (`KhachHangID`),
  ADD CONSTRAINT `fk_baogia_congty` FOREIGN KEY (`CongTyID`) REFERENCES `congty` (`CongTyID`),
  ADD CONSTRAINT `fk_baogia_khachhang` FOREIGN KEY (`KhachHangID`) REFERENCES `khachhang` (`KhachHangID`),
  ADD CONSTRAINT `fk_baogia_nguoilienhe` FOREIGN KEY (`NguoiLienHeID`) REFERENCES `nguoilienhe` (`NguoiLienHeID`),
  ADD CONSTRAINT `fk_baogia_nguoitao` FOREIGN KEY (`NguoiTao`) REFERENCES `nguoidung` (`UserID`);

--
-- Ràng buộc cho bảng `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD CONSTRAINT `fk_bugreport_user` FOREIGN KEY (`UserID`) REFERENCES `nguoidung` (`UserID`);

--
-- Ràng buộc cho bảng `bug_report_comments`
--
ALTER TABLE `bug_report_comments`
  ADD CONSTRAINT `fk_comment_bugreport` FOREIGN KEY (`BugReportID`) REFERENCES `bug_reports` (`BugReportID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`UserID`) REFERENCES `nguoidung` (`UserID`);

--
-- Ràng buộc cho bảng `chitiet_kehoach_giaohang`
--
ALTER TABLE `chitiet_kehoach_giaohang`
  ADD CONSTRAINT `fk_ctkhgh_ctdh` FOREIGN KEY (`ChiTiet_DonHang_ID`) REFERENCES `chitiet_donhang` (`ChiTiet_YCSX_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ctkhgh_khgh` FOREIGN KEY (`KHGH_ID`) REFERENCES `kehoach_giaohang` (`KHGH_ID`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `CongTy_Comment`
--
ALTER TABLE `CongTy_Comment`
  ADD CONSTRAINT `FK_Comment_CongTy` FOREIGN KEY (`CongTyID`) REFERENCES `congty` (`CongTyID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `DuAn_Comment`
--
ALTER TABLE `DuAn_Comment`
  ADD CONSTRAINT `FK_Comment_DuAn` FOREIGN KEY (`DuAnID`) REFERENCES `DuAn` (`DuAnID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `DuAn_HangMuc`
--
ALTER TABLE `DuAn_HangMuc`
  ADD CONSTRAINT `FK_DuAn` FOREIGN KEY (`DuAnID`) REFERENCES `DuAn` (`DuAnID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_HangMuc` FOREIGN KEY (`HangMucID`) REFERENCES `HangMuc` (`HangMucID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ràng buộc cho bảng `kehoach_giaohang`
--
ALTER TABLE `kehoach_giaohang`
  ADD CONSTRAINT `fk_khgh_donhang` FOREIGN KEY (`DonHangID`) REFERENCES `donhang` (`YCSX_ID`) ON DELETE CASCADE;

--
-- Ràng buộc cho bảng `ke_hoach_doanh_thu`
--
ALTER TABLE `ke_hoach_doanh_thu`
  ADD CONSTRAINT `fk_kehoach_nguoitao` FOREIGN KEY (`NguoiTao`) REFERENCES `nguoidung` (`UserID`);

--
-- Ràng buộc cho bảng `sanpham_nhacungcap`
--
ALTER TABLE `sanpham_nhacungcap`
  ADD CONSTRAINT `fk_sanpham_nhacungcap` FOREIGN KEY (`NhaCungCapID`) REFERENCES `nhacungcap` (`NhaCungCapID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
