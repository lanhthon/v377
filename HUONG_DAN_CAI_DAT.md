# 🚀 HƯỚNG DẪN CÀI ĐẶT & SỬ DỤNG HỆ THỐNG GIA CÔNG MẠ NHÚNG NÓNG

## 📋 Mục Lục
1. [Giới thiệu](#giới-thiệu)
2. [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
3. [Cài đặt](#cài-đặt)
4. [Hướng dẫn sử dụng](#hướng-dẫn-sử-dụng)
5. [Phân quyền](#phân-quyền)
6. [Troubleshooting](#troubleshooting)

---

## 🎯 Giới Thiệu

Hệ thống gia công mạ nhúng nóng tự động hóa toàn bộ quy trình:
- Phát hiện sản phẩm ULA mạ nhúng nóng trong đơn hàng
- Tự động tìm sản phẩm mạ điện phân tương ứng
- Xuất kho gia công
- Theo dõi tiến độ
- Nhập kho sau gia công
- Cập nhật tồn kho tự động

---

## 💻 Yêu Cầu Hệ Thống

### Phần mềm
- **PHP**: >= 7.4
- **MySQL/MariaDB**: >= 5.7
- **Web Server**: Apache/Nginx
- **Browser**: Chrome, Firefox, Safari (latest)

### Thư viện Frontend (đã có sẵn)
- jQuery
- Tailwind CSS
- Font Awesome

---

## 🔧 Cài Đặt

### Bước 1: Tạo Database Tables

```bash
cd /home/user/v377/migrations
php run_migration.php
```

**Kết quả mong đợi:**
```
=================================================
MIGRATION: Tạo bảng cho chức năng gia công mạ nhúng nóng
=================================================

Đang thực thi migration...

✓ Tạo bảng: phieu_xuat_gia_cong
✓ Tạo bảng: lich_su_gia_cong
✓ Cập nhật bảng: chitietchuanbihang
✓ Cập nhật bảng: inventory_logs

=================================================
KẾT QUẢ MIGRATION
=================================================
✓ Thành công: 15 câu lệnh
✗ Lỗi: 0 câu lệnh

✓ Bảng 'phieu_xuat_gia_cong' đã tồn tại
✓ Bảng 'lich_su_gia_cong' đã tồn tại

=================================================
HOÀN TẤT MIGRATION!
=================================================
```

### Bước 2: Thêm Menu và Phân Quyền

```bash
cd /home/user/v377/migrations
php run_menu_migration.php
```

**Kết quả mong đợi:**
```
=================================================
MIGRATION: Thêm menu và phân quyền gia công mạ nhúng nóng
=================================================

Đang thực thi migration...

✓ Insert vào bảng: chucnang
✓ Thiết lập biến: @gia_cong_list_id
✓ Insert vào bảng: vaitro_chucnang
...

Kiểm tra các chức năng:
✓ gia_cong_list: Xem danh sách phiếu xuất gia công mạ nhúng nóng
✓ gia_cong_view: Xem chi tiết và nhập kho phiếu gia công
✓ xuat_gia_cong: Xuất kho sản phẩm đi gia công mạ nhúng nóng
✓ nhap_gia_cong: Nhập kho sản phẩm sau khi gia công mạ nhúng nóng

=================================================
HOÀN TẤT MIGRATION!
=================================================
```

### Bước 3: Kiểm Tra Cài Đặt

1. **Kiểm tra bảng database:**
```sql
SHOW TABLES LIKE 'phieu_xuat_gia_cong';
SHOW TABLES LIKE 'lich_su_gia_cong';
```

2. **Kiểm tra phân quyền:**
```sql
SELECT c.TenChucNang, v.TenVaiTro
FROM chucnang c
JOIN vaitro_chucnang vc ON c.ChucNangID = vc.ChucNangID
JOIN vaitro v ON vc.VaiTroID = v.VaiTroID
WHERE c.TenChucNang LIKE '%gia_cong%';
```

3. **Kiểm tra files:**
```bash
ls -la pages/gia_cong_*.php
ls -la assets/js/gia_cong_*.js
ls -la api/*gia_cong*.php
```

---

## 📖 Hướng Dẫn Sử Dụng

### A. QUY TRÌNH HOÀN CHỈNH

```
┌─────────────────────────────────────────┐
│ 1. TẠO ĐƠN HÀNG VỚI ULA MẠ NHÚNG NÓNG  │
│    (Ví dụ: ULA 125x40-M10-HDG)         │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 2. TẠO PHIẾU CHUẨN BỊ HÀNG (CBH)       │
│    - Hệ thống tính toán tồn kho         │
│    - Phát hiện thiếu ULA mạ nhúng nóng │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 3. HỆ THỐNG HIỆN SECTION GIA CÔNG      │
│    - Tự động tìm SP mạ điện phân       │
│    - Hiển thị số lượng có thể xuất     │
│    - Nút "Xuất gia công"               │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 4. XUẤT KHO GIA CÔNG                    │
│    - Click "Xuất gia công"             │
│    - Nhập số lượng (hoặc xuất hết)     │
│    - Xác nhận → Tạo phiếu              │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 5. THEO DÕI TIẾN ĐỘ                    │
│    - Vào menu "Gia công mạ nhúng nóng" │
│    - Xem danh sách phiếu               │
│    - Click "Chi tiết"                  │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 6. NHẬP KHO SAU GIA CÔNG                │
│    - Nhập số lượng nhận được           │
│    - Thêm ghi chú (hao hụt nếu có)     │
│    - Xác nhận → Cập nhật tồn kho       │
└─────────────────────────────────────────┘
```

### B. CHI TIẾT TỪNG BƯỚC

#### Bước 1: Tạo đơn hàng

**Điều kiện:**
- Sản phẩm ULA
- Cột "Xử lý bề mặt" = "Mạ nhúng nóng"
- Ví dụ: `ULA 125x40-M10-HDG`

#### Bước 2: Xem phiếu CBH

1. Vào **Sản xuất** → **Chuẩn bị hàng**
2. Chọn phiếu CBH cần xem
3. Click **"Chuẩn bị"** (nếu trạng thái "Mới tạo")

#### Bước 3: Xuất kho gia công

**Nếu có section "Xuất Kho Gia Công Mạ Nhúng Nóng":**

```
┌─────────────────────────────────────────────────────────────────┐
│ 🏭 XUẤT KHO GIA CÔNG MẠ NHÚNG NÓNG                   3 sản phẩm │
├─────────────────────────────────────────────────────────────────┤
│ ℹ️ Lưu ý: Hệ thống tự động tìm sản phẩm ULA mạ điện phân...     │
├─────┬──────────────────┬─────────────┬──────┬─────────┬────────┤
│ STT │ SP Nhúng Nóng    │ SP Mạ ĐP    │ Cần  │ Xuất GC │ Thao   │
├─────┼──────────────────┼─────────────┼──────┼─────────┼────────┤
│ 1   │ ULA 125x40-M10.. │ ULA 125x40..│ 100  │   80    │ ⚡ Xuất│
│     │ Tồn: 20          │ Tồn: 150    │      │         │        │
├─────┴──────────────────┴─────────────┴──────┴─────────┴────────┤
│                          [Xuất tất cả gia công]                 │
└─────────────────────────────────────────────────────────────────┘
```

**Thao tác:**
1. Click **"Xuất gia công"** trên dòng sản phẩm
2. Modal hiện ra:
   - Sản phẩm xuất: ULA 125x40-M10 (Mạ điện phân)
   - Sản phẩm nhận: ULA 125x40-M10-HDG (Mạ nhúng nóng)
   - Số lượng: 80 (có thể chỉnh)
   - Ghi chú: (tùy chọn)
3. Click **"Xác nhận xuất"**
4. Hệ thống:
   - Tạo phiếu xuất gia công
   - Trừ tồn kho mạ điện phân
   - Ghi log vào inventory_logs
   - Cập nhật trạng thái CBH

**Hoặc xuất tất cả:**
- Click **"Xuất tất cả gia công"**
- Xác nhận → Xuất tất cả sản phẩm cùng lúc

#### Bước 4: Xem danh sách phiếu gia công

1. Vào menu **Sản xuất** → **Gia công mạ nhúng nóng**

```
┌───────────────────────────────────────────────────────────────┐
│ QUẢN LÝ GIA CÔNG MẠ NHÚNG NÓNG                                │
├───────────────────────────────────────────────────────────────┤
│ Trạng thái: [Tất cả ▼]  Từ: [____]  Đến: [____]  [Lọc]      │
├─────┬─────────────┬──────────┬──────────┬─────────┬──────────┤
│ STT │ Mã Phiếu    │ SP Xuất  │ SP Nhận  │ Tiến độ │ Trạng thái│
├─────┼─────────────┼──────────┼──────────┼─────────┼──────────┤
│ 1   │ GC-MNN-12.. │ ULA 125..│ ULA 125..│ 0/80    │ Đã xuất  │
│ 2   │ GC-MNN-13.. │ ULA 100..│ ULA 100..│ 50/50   │ Đã nhập  │
└─────┴─────────────┴──────────┴──────────┴─────────┴──────────┘
```

2. **Thống kê:**
   - Đã xuất: 5 phiếu
   - Đang gia công: 3 phiếu
   - Đã nhập kho: 12 phiếu

3. **Bộ lọc:**
   - Lọc theo trạng thái
   - Lọc theo ngày tháng
   - Phân trang

#### Bước 5: Xem chi tiết và nhập kho

1. Click **"Chi tiết"** trên phiếu cần nhập

```
┌───────────────────────────────────────────────────────────────┐
│ CHI TIẾT PHIẾU GIA CÔNG                                       │
├───────────────────────────────────────────────────────────────┤
│ THÔNG TIN PHIẾU                                               │
│ - Mã phiếu: GC-MNN-123-1730000000                            │
│ - Phiếu CBH: CBH-2025-001                                     │
│ - Trạng thái: Đã xuất                                         │
│ - Người xuất: Admin                                           │
│ - Ngày xuất: 30/10/2025 10:30                                │
├───────────────────────────────────────────────────────────────┤
│ CHI TIẾT SẢN PHẨM                                             │
│ ┌─ SP Xuất (MĐP) ─┐  ┌─ SP Nhận (MNN) ─┐                    │
│ │ ULA 125x40-M10  │  │ ULA 125x40-M10-HDG│                   │
│ │ SL: 80          │  │ SL: 0/80         │                    │
│ └─────────────────┘  └─────────────────┘                     │
├───────────────────────────────────────────────────────────────┤
│ TIẾN ĐỘ GIA CÔNG                                             │
│ Số lượng xuất: 80                                             │
│ Đã nhập về: 0                                                 │
│ Còn lại: 80                                                   │
│ [░░░░░░░░░░░░░░░░░░░░] 0%                                    │
├───────────────────────────────────────────────────────────────┤
│ 🏭 NHẬP KHO SAU GIA CÔNG                                      │
│                                                                │
│ Số lượng nhập: [___80___]  Tối đa: 80                        │
│ Ngày nhập: [2025-10-30]                                      │
│ Ghi chú: [________________________________]                   │
│          [Hao hụt 5 bộ trong quá trình gc]                   │
│                                                                │
│                       [✓ Xác Nhận Nhập Kho]                   │
└───────────────────────────────────────────────────────────────┘
```

2. **Nhập thông tin:**
   - Số lượng nhập: Nhập số lượng thực tế nhận được
   - Ngày nhập: Ngày nhập kho (mặc định hôm nay)
   - Ghi chú: Thông tin về hao hụt, chất lượng,...

3. **Click "Xác Nhận Nhập Kho"**

4. **Hệ thống xử lý:**
   - Tăng tồn kho sản phẩm mạ nhúng nóng
   - Cập nhật trạng thái phiếu
   - Ghi log inventory_logs
   - Lưu lịch sử gia công
   - Reload trang hiển thị kết quả

---

## 🔐 Phân Quyền

### Bảng Phân Quyền Chi Tiết

| Vai Trò | Xem DS | Xem CT | Xuất GC | Nhập Kho |
|---------|:------:|:------:|:-------:|:--------:|
| **Admin** | ✅ | ✅ | ✅ | ✅ |
| **Thủ Kho** | ✅ | ✅ | ✅ | ✅ |
| **TP Sản Xuất** | ✅ | ✅ | ✅ | ❌ |
| **NV Sản Xuất** | ✅ | ✅ | ❌ | ❌ |
| **Khác** | ❌ | ❌ | ❌ | ❌ |

### Giải Thích

- **Xem DS**: Xem danh sách phiếu gia công
- **Xem CT**: Xem chi tiết phiếu
- **Xuất GC**: Xuất kho đi gia công
- **Nhập Kho**: Nhập kho sau gia công

### Thay Đổi Phân Quyền

**Cách 1: Qua SQL**
```sql
-- Thêm quyền cho vai trò X (VaiTroID)
INSERT INTO vaitro_chucnang (VaiTroID, ChucNangID)
SELECT X, ChucNangID FROM chucnang WHERE TenChucNang = 'gia_cong_list';

-- Xóa quyền
DELETE FROM vaitro_chucnang
WHERE VaiTroID = X AND ChucNangID = (
    SELECT ChucNangID FROM chucnang WHERE TenChucNang = 'gia_cong_list'
);
```

**Cách 2: Qua giao diện Admin** (nếu có module quản lý phân quyền)

---

## 🐛 Troubleshooting

### Lỗi 1: `xuatTatCaGiaCong is not defined`

**Nguyên nhân:** Functions chưa được export ra global scope

**Giải pháp:**
1. Kiểm tra file `assets/js/chuanbi_hang_edit.js` có các dòng sau (khoảng dòng 698-705):
```javascript
window.xuatKhoGiaCong = xuatKhoGiaCong;
window.closeXuatGiaCongModal = closeXuatGiaCongModal;
window.confirmXuatGiaCong = confirmXuatGiaCong;
window.xuatTatCaGiaCong = xuatTatCaGiaCong;
window.showNotification = showNotification;
```

2. Clear cache trình duyệt: `Ctrl + F5`

### Lỗi 2: Không tìm thấy sản phẩm mạ điện phân

**Nguyên nhân:**
- Sản phẩm MĐP chưa được tạo
- Thuộc tính không khớp
- SKU có hậu tố sai

**Giải pháp:**

1. **Kiểm tra sản phẩm MĐP tồn tại:**
```sql
SELECT variant_id, variant_sku
FROM variants
WHERE variant_sku LIKE 'ULA 125x40-M10%'
  AND variant_sku NOT LIKE '%-HDG'
  AND variant_sku NOT LIKE '%-MNN';
```

2. **Kiểm tra thuộc tính:**
```sql
-- Sản phẩm mạ nhúng nóng
SELECT v.variant_id, v.variant_sku, a.name, ao.value
FROM variants v
JOIN variant_attributes va ON v.variant_id = va.variant_id
JOIN attribute_options ao ON va.option_id = ao.option_id
JOIN attributes a ON ao.attribute_id = a.attribute_id
WHERE v.variant_sku = 'ULA 125x40-M10-HDG'
  AND a.name IN ('ID Thông Số', 'Kích thước ren', 'Xử lý bề mặt');
```

3. **Đảm bảo:**
   - "Xử lý bề mặt" của MNN = "Mạ nhúng nóng"
   - "Xử lý bề mặt" của MĐP = "Mạ điện phân"
   - "ID Thông Số" giống nhau (ví dụ: "125x40")
   - "Kích thước ren" giống nhau (ví dụ: "M10")
   - SKU MĐP không có hậu tố (-HDG, -MNN, -PVC, -CP)

### Lỗi 3: Không đủ tồn kho mạ điện phân

**Giải pháp:**
1. Tạo yêu cầu sản xuất ULA mạ điện phân
2. Sau khi sản xuất xong → Nhập kho
3. Quay lại xuất gia công

### Lỗi 4: Lỗi khi nhập kho

**Nguyên nhân:**
- Số lượng vượt quá
- Phiếu đã nhập đủ
- Lỗi kết nối database

**Giải pháp:**
1. Kiểm tra trạng thái phiếu:
```sql
SELECT MaPhieu, SoLuongXuat, SoLuongNhapVe, TrangThai
FROM phieu_xuat_gia_cong
WHERE PhieuXuatGC_ID = [ID];
```

2. Xem log lỗi:
```bash
tail -f /var/log/apache2/error.log
# Hoặc
tail -f /var/log/nginx/error.log
```

### Lỗi 5: Không hiển thị section gia công trong CBH

**Nguyên nhân:**
- Không có sản phẩm ULA mạ nhúng nóng thiếu
- JavaScript chưa load

**Kiểm tra:**
1. Xem console browser (`F12`)
2. Tìm log: `[GIA_CONG] Có X sản phẩm cần gia công`
3. Nếu không có log → Kiểm tra API response

**Debug:**
```javascript
// Mở console browser, chạy:
console.log(danhSachGiaCongData);
```

### Lỗi 6: Permission Denied

**Giải pháp:**
1. Kiểm tra vai trò user:
```sql
SELECT u.Username, v.TenVaiTro
FROM nguoidung u
JOIN vaitro v ON u.VaiTroID = v.VaiTroID
WHERE u.UserID = [USER_ID];
```

2. Kiểm tra phân quyền:
```sql
SELECT c.TenChucNang, v.TenVaiTro
FROM chucnang c
JOIN vaitro_chucnang vc ON c.ChucNangID = vc.ChucNangID
JOIN vaitro v ON vc.VaiTroID = v.VaiTroID
WHERE c.TenChucNang LIKE '%gia_cong%';
```

3. Chạy lại migration phân quyền:
```bash
cd migrations
php run_menu_migration.php
```

---

## 📞 Liên Hệ & Hỗ Trợ

### Báo Lỗi
- **Qua hệ thống:** Vào menu **Báo lỗi** → Tạo báo cáo mới
- **Qua email:** support@company.com
- **Qua GitHub:** Tạo Issue tại repository

### Yêu Cầu Tính Năng Mới
- Liên hệ bộ phận IT
- Mô tả chi tiết yêu cầu
- Đính kèm ảnh minh họa nếu có

---

## 📚 Tài Liệu Bổ Sung

- **README_GIA_CONG.md**: Tài liệu kỹ thuật đầy đủ
- **API Documentation**: Chi tiết các API endpoints
- **Database Schema**: Cấu trúc bảng và quan hệ

---

**Phiên bản:** 1.0
**Ngày cập nhật:** 30/10/2025
**Tác giả:** Claude AI + Development Team
