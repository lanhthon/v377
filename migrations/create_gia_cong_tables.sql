-- ============================================================================
-- Migration: Tạo bảng cho chức năng Gia Công Mạ Nhúng Nóng
-- Version: 1.0
-- Created: 2025-10-30
-- Description: Tạo các bảng cần thiết cho quy trình gia công mạ nhúng nóng
-- ============================================================================

-- Bảng lưu phiếu xuất kho gia công
CREATE TABLE IF NOT EXISTS `phieu_xuat_gia_cong` (
    `PhieuXuatGC_ID` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'ID tự động tăng',
    `MaPhieu` VARCHAR(100) NOT NULL COMMENT 'Mã phiếu xuất gia công (GC-MNN-{CBH_ID}-{timestamp})',
    `CBH_ID` INT(11) NOT NULL COMMENT 'Liên kết với phiếu chuẩn bị hàng',
    `ChiTietCBH_ID` INT(11) NOT NULL COMMENT 'Liên kết với chi tiết CBH',

    -- Thông tin sản phẩm xuất (Mạ điện phân)
    `SanPhamXuatID` INT(11) NOT NULL COMMENT 'ID sản phẩm xuất đi gia công (variant_id)',
    `MaSanPhamXuat` VARCHAR(150) NOT NULL COMMENT 'Mã sản phẩm xuất (ULA mạ điện phân)',
    `TenSanPhamXuat` VARCHAR(255) DEFAULT NULL COMMENT 'Tên sản phẩm xuất',

    -- Thông tin sản phẩm nhận (Mạ nhúng nóng)
    `SanPhamNhanID` INT(11) NOT NULL COMMENT 'ID sản phẩm nhận sau gia công (variant_id)',
    `MaSanPhamNhan` VARCHAR(150) NOT NULL COMMENT 'Mã sản phẩm nhận (ULA mạ nhúng nóng)',
    `TenSanPhamNhan` VARCHAR(255) DEFAULT NULL COMMENT 'Tên sản phẩm nhận',

    -- Thông tin số lượng và gia công
    `SoLuongXuat` INT(11) NOT NULL COMMENT 'Số lượng xuất đi gia công',
    `SoLuongNhapVe` INT(11) DEFAULT 0 COMMENT 'Số lượng đã nhập về sau gia công',
    `LoaiGiaCong` VARCHAR(100) NOT NULL DEFAULT 'Mạ nhúng nóng' COMMENT 'Loại gia công',
    `TrangThai` VARCHAR(50) NOT NULL DEFAULT 'Đã xuất' COMMENT 'Trạng thái: Đã xuất, Đang gia công, Đã nhập kho',

    -- Thông tin người thực hiện và thời gian
    `NguoiXuat` VARCHAR(100) DEFAULT NULL COMMENT 'Người thực hiện xuất kho',
    `NgayXuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Ngày giờ xuất kho',
    `NgayDuKienHoanThanh` DATE DEFAULT NULL COMMENT 'Ngày dự kiến hoàn thành gia công',
    `NgayNhapKho` DATETIME DEFAULT NULL COMMENT 'Ngày giờ nhập kho thực tế',
    `NguoiNhapKho` VARCHAR(100) DEFAULT NULL COMMENT 'Người thực hiện nhập kho',

    -- Thông tin bổ sung
    `NhaCungCapGiaCong` VARCHAR(255) DEFAULT NULL COMMENT 'Tên nhà cung cấp gia công',
    `DonGiaGiaCong` DECIMAL(18,2) DEFAULT 0.00 COMMENT 'Đơn giá gia công/bộ',
    `TongTienGiaCong` DECIMAL(18,2) DEFAULT 0.00 COMMENT 'Tổng tiền gia công',
    `GhiChu` TEXT DEFAULT NULL COMMENT 'Ghi chú',

    -- Audit fields
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`PhieuXuatGC_ID`),
    UNIQUE KEY `idx_ma_phieu` (`MaPhieu`),
    KEY `idx_cbh_id` (`CBH_ID`),
    KEY `idx_chitiet_cbh_id` (`ChiTietCBH_ID`),
    KEY `idx_san_pham_xuat` (`SanPhamXuatID`),
    KEY `idx_san_pham_nhan` (`SanPhamNhanID`),
    KEY `idx_trang_thai` (`TrangThai`),
    KEY `idx_ngay_xuat` (`NgayXuat`),

    CONSTRAINT `fk_pxgc_cbh` FOREIGN KEY (`CBH_ID`) REFERENCES `chuanbihang` (`CBH_ID`) ON DELETE CASCADE,
    CONSTRAINT `fk_pxgc_chitiet_cbh` FOREIGN KEY (`ChiTietCBH_ID`) REFERENCES `chitietchuanbihang` (`ChiTietCBH_ID`) ON DELETE CASCADE,
    CONSTRAINT `fk_pxgc_san_pham_xuat` FOREIGN KEY (`SanPhamXuatID`) REFERENCES `variants` (`variant_id`),
    CONSTRAINT `fk_pxgc_san_pham_nhan` FOREIGN KEY (`SanPhamNhanID`) REFERENCES `variants` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Bảng lưu phiếu xuất kho gia công mạ nhúng nóng';

-- Bảng lưu lịch sử gia công (tùy chọn, để theo dõi chi tiết)
CREATE TABLE IF NOT EXISTS `lich_su_gia_cong` (
    `LichSuGC_ID` INT(11) NOT NULL AUTO_INCREMENT,
    `PhieuXuatGC_ID` INT(11) NOT NULL COMMENT 'Liên kết với phiếu xuất gia công',
    `TrangThai` VARCHAR(50) NOT NULL COMMENT 'Trạng thái tại thời điểm',
    `MoTa` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết',
    `NguoiCapNhat` VARCHAR(100) DEFAULT NULL COMMENT 'Người cập nhật',
    `NgayCapNhat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`LichSuGC_ID`),
    KEY `idx_phieu_xuat_gc` (`PhieuXuatGC_ID`),

    CONSTRAINT `fk_lsgc_phieu` FOREIGN KEY (`PhieuXuatGC_ID`) REFERENCES `phieu_xuat_gia_cong` (`PhieuXuatGC_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Bảng lưu lịch sử theo dõi gia công';

-- Thêm index cho bảng inventory_logs nếu chưa có (để track xuất/nhập gia công)
ALTER TABLE `inventory_logs`
ADD INDEX IF NOT EXISTS `idx_reference_type` (`reference_type`),
ADD INDEX IF NOT EXISTS `idx_reference_id` (`reference_id`);

-- Thêm cột mới vào bảng chitietchuanbihang nếu cần
ALTER TABLE `chitietchuanbihang`
ADD COLUMN IF NOT EXISTS `TrangThaiGiaCong` VARCHAR(50) DEFAULT NULL COMMENT 'Trạng thái gia công: Chưa xuất, Đã xuất, Đang gia công, Đã nhập kho',
ADD COLUMN IF NOT EXISTS `SoLuongDaXuatGC` INT(11) DEFAULT 0 COMMENT 'Số lượng đã xuất đi gia công',
ADD COLUMN IF NOT EXISTS `SoLuongDaNhapGC` INT(11) DEFAULT 0 COMMENT 'Số lượng đã nhập về sau gia công';

-- Thêm index
ALTER TABLE `chitietchuanbihang`
ADD INDEX IF NOT EXISTS `idx_trang_thai_gia_cong` (`TrangThaiGiaCong`);
