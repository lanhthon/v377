-- ============================================================================
-- Migration: Thêm menu và phân quyền cho chức năng Gia Công Mạ Nhúng Nóng
-- Version: 1.0
-- Created: 2025-10-30
-- ============================================================================

-- ================================================
-- 1. THÊM CHỨC NĂNG MỚI VÀO BẢNG chucnang
-- ================================================

-- Chức năng: Xem danh sách phiếu gia công
INSERT INTO `chucnang` (`ChucNangID`, `TenChucNang`, `MoTa`, `ThuocMenu`)
VALUES (NULL, 'gia_cong_list', 'Xem danh sách phiếu xuất gia công mạ nhúng nóng', 'Sản xuất')
ON DUPLICATE KEY UPDATE
    `TenChucNang` = VALUES(`TenChucNang`),
    `MoTa` = VALUES(`MoTa`),
    `ThuocMenu` = VALUES(`ThuocMenu`);

-- Chức năng: Xem chi tiết phiếu gia công
INSERT INTO `chucnang` (`ChucNangID`, `TenChucNang`, `MoTa`, `ThuocMenu`)
VALUES (NULL, 'gia_cong_view', 'Xem chi tiết và nhập kho phiếu gia công', 'Sản xuất')
ON DUPLICATE KEY UPDATE
    `TenChucNang` = VALUES(`TenChucNang`),
    `MoTa` = VALUES(`MoTa`),
    `ThuocMenu` = VALUES(`ThuocMenu`);

-- Chức năng: Xuất kho gia công (trong trang chuẩn bị hàng)
INSERT INTO `chucnang` (`ChucNangID`, `TenChucNang`, `MoTa`, `ThuocMenu`)
VALUES (NULL, 'xuat_gia_cong', 'Xuất kho sản phẩm đi gia công mạ nhúng nóng', 'Sản xuất')
ON DUPLICATE KEY UPDATE
    `TenChucNang` = VALUES(`TenChucNang`),
    `MoTa` = VALUES(`MoTa`),
    `ThuocMenu` = VALUES(`ThuocMenu`);

-- Chức năng: Nhập kho sau gia công
INSERT INTO `chucnang` (`ChucNangID`, `TenChucNang`, `MoTa`, `ThuocMenu`)
VALUES (NULL, 'nhap_gia_cong', 'Nhập kho sản phẩm sau khi gia công mạ nhúng nóng', 'Sản xuất')
ON DUPLICATE KEY UPDATE
    `TenChucNang` = VALUES(`TenChucNang`),
    `MoTa` = VALUES(`MoTa`),
    `ThuocMenu` = VALUES(`ThuocMenu`);

-- ================================================
-- 2. PHÂN QUYỀN CHO CÁC VAI TRÒ
-- ================================================

-- Lấy ID của các chức năng vừa tạo
SET @gia_cong_list_id = (SELECT ChucNangID FROM chucnang WHERE TenChucNang = 'gia_cong_list' LIMIT 1);
SET @gia_cong_view_id = (SELECT ChucNangID FROM chucnang WHERE TenChucNang = 'gia_cong_view' LIMIT 1);
SET @xuat_gia_cong_id = (SELECT ChucNangID FROM chucnang WHERE TenChucNang = 'xuat_gia_cong' LIMIT 1);
SET @nhap_gia_cong_id = (SELECT ChucNangID FROM chucnang WHERE TenChucNang = 'nhap_gia_cong' LIMIT 1);

-- --- PHÂN QUYỀN CHO VAI TRÒ ADMIN (VaiTroID = 1) ---
-- Admin có toàn quyền

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (1, @gia_cong_list_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (1, @gia_cong_view_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (1, @xuat_gia_cong_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (1, @nhap_gia_cong_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

-- --- PHÂN QUYỀN CHO VAI TRÒ THỦ KHO (VaiTroID = 3) ---
-- Thủ kho có quyền xem, xuất và nhập gia công

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (3, @gia_cong_list_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (3, @gia_cong_view_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (3, @xuat_gia_cong_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (3, @nhap_gia_cong_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

-- --- PHÂN QUYỀN CHO VAI TRÒ TRƯỞNG PHÒNG SẢN XUẤT (VaiTroID = 4) ---
-- TP Sản xuất có quyền xem và xuất gia công

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (4, @gia_cong_list_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (4, @gia_cong_view_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (4, @xuat_gia_cong_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

-- --- PHÂN QUYỀN CHO VAI TRÒ NHÂN VIÊN SẢN XUẤT (VaiTroID = 5) ---
-- Nhân viên chỉ được xem

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (5, @gia_cong_list_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

INSERT INTO `vaitro_chucnang` (`VaiTroID`, `ChucNangID`)
VALUES (5, @gia_cong_view_id)
ON DUPLICATE KEY UPDATE `VaiTroID` = VALUES(`VaiTroID`);

-- ================================================
-- 3. HOÀN TẤT
-- ================================================

SELECT 'Migration completed successfully!' AS Message;

-- Hiển thị các chức năng đã thêm
SELECT
    c.ChucNangID,
    c.TenChucNang,
    c.MoTa,
    c.ThuocMenu,
    GROUP_CONCAT(v.TenVaiTro SEPARATOR ', ') AS VaiTroCoQuyen
FROM chucnang c
LEFT JOIN vaitro_chucnang vc ON c.ChucNangID = vc.ChucNangID
LEFT JOIN vaitro v ON vc.VaiTroID = v.VaiTroID
WHERE c.TenChucNang IN ('gia_cong_list', 'gia_cong_view', 'xuat_gia_cong', 'nhap_gia_cong')
GROUP BY c.ChucNangID, c.TenChucNang, c.MoTa, c.ThuocMenu;
