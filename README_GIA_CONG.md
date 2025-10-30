# Hệ Thống Gia Công Mạ Nhúng Nóng

## Tổng Quan

Hệ thống gia công mạ nhúng nóng tự động hóa quy trình xuất kho sản phẩm ULA mạ điện phân để gia công thành mạ nhúng nóng.

## Quy Trình

```
┌─────────────────────────────────────────────────────────────────┐
│  1. ĐƠN HÀNG CÓ SẢN PHẨM ULA MẠ NHÚNG NÓNG                      │
│     (Xử lý bề mặt = "Mạ nhúng nóng")                            │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. KIỂM TRA TỒN KHO SẢN PHẨM MẠ NHÚNG NÓNG                     │
└────────────────────┬────────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
         ▼                       ▼
   ┌─────────┐           ┌─────────────┐
   │ ĐỦ HÀNG │           │ THIẾU HÀNG  │
   └────┬────┘           └──────┬──────┘
        │                       │
        ▼                       ▼
   KHÔNG LÀM GÌ        ┌──────────────────────────┐
                       │ 3. TÌM SẢN PHẨM MẠ ĐIỆN  │
                       │    PHÂN TƯƠNG ỨNG        │
                       │    (cùng thông số,       │
                       │     không có hậu tố)     │
                       └────────┬─────────────────┘
                                │
                    ┌───────────┴───────────┐
                    │                       │
                    ▼                       ▼
              ┌─────────┐           ┌──────────────┐
              │ ĐỦ MĐP  │           │ THIẾU MĐP    │
              └────┬────┘           └──────┬───────┘
                   │                       │
                   ▼                       ▼
      ┌────────────────────────┐  ┌────────────────────┐
      │ 4. XUẤT KHO MẠ ĐIỆN    │  │ A. SẢN XUẤT ULA    │
      │    PHÂN ĐI GIA CÔNG    │  │    MẠ ĐIỆN PHÂN    │
      └────────────┬───────────┘  └─────────┬──────────┘
                   │                        │
                   │                        ▼
                   │              ┌────────────────────┐
                   │              │ B. NHẬP KHO MĐP    │
                   │              └─────────┬──────────┘
                   │                        │
                   └────────────────────────┘
                                │
                                ▼
                   ┌────────────────────────┐
                   │ 5. GIA CÔNG MẠ NHÚNG   │
                   │    NÓNG                │
                   └────────────┬───────────┘
                                │
                                ▼
                   ┌────────────────────────┐
                   │ 6. NHẬP KHO SẢN PHẨM   │
                   │    MẠ NHÚNG NÓNG       │
                   └────────────────────────┘
```

## Cài Đặt

### 1. Chạy Migration

Tạo bảng database cần thiết:

```bash
cd /home/user/v377/migrations
php run_migration.php
```

### 2. Cấu Trúc Database

**Bảng chính:**
- `phieu_xuat_gia_cong` - Lưu phiếu xuất kho gia công
- `lich_su_gia_cong` - Lưu lịch sử theo dõi
- `chitietchuanbihang` - Thêm cột theo dõi trạng thái gia công

## API Endpoints

### 1. Xuất Kho Gia Công

**Endpoint:** `POST api/process_gia_cong_ma_nhung_nong.php`

**Request:**
```json
{
  "cbh_id": 123,
  "chi_tiet_cbh_id": 456,
  "so_luong_xuat": 100,
  "nguoi_xuat": "Admin",
  "ghi_chu": "Xuất gia công lô 1"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Xuất kho gia công mạ nhúng nóng thành công",
  "data": {
    "ma_phieu_xuat": "GC-MNN-123-1730000000",
    "phieu_xuat_id": 1,
    "san_pham_xuat": {
      "id": 100,
      "ma": "ULA 125x40-M10",
      "ten": "ULA 125x40-M10 Mạ điện phân",
      "ton_kho_truoc": 500,
      "ton_kho_sau": 400
    },
    "san_pham_nhan": {
      "id": 101,
      "ma": "ULA 125x40-M10-HDG",
      "ten": "ULA 125x40-M10 Mạ nhúng nóng"
    },
    "so_luong_xuat": 100,
    "ngay_xuat": "2025-10-30 10:30:00"
  }
}
```

### 2. Nhập Kho Sau Gia Công

**Endpoint:** `POST api/import_gia_cong_ma_nhung_nong.php`

**Request:**
```json
{
  "phieu_xuat_gc_id": 1,
  "so_luong_nhap": 95,
  "nguoi_nhap": "Admin",
  "ghi_chu": "Nhập kho sau gia công, hao hụt 5 bộ"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Nhập kho sau gia công thành công",
  "data": {
    "ma_phieu": "GC-MNN-123-1730000000",
    "san_pham_nhan": {
      "id": 101,
      "ma": "ULA 125x40-M10-HDG",
      "ten": "ULA 125x40-M10 Mạ nhúng nóng",
      "ton_kho_truoc": 0,
      "ton_kho_sau": 95
    },
    "so_luong_nhap": 95,
    "so_luong_da_nhap": 95,
    "so_luong_xuat": 100,
    "trang_thai": "Đã nhập kho",
    "ngay_nhap": "2025-10-30 15:45:00"
  }
}
```

### 3. Lấy Danh Sách Phiếu Gia Công

**Endpoint:** `GET api/get_danh_sach_gia_cong.php`

**Parameters:**
- `cbh_id` (optional) - Lọc theo phiếu CBH
- `trang_thai` (optional) - Lọc theo trạng thái (Đã xuất, Đang gia công, Đã nhập kho)
- `tu_ngay` (optional) - Lọc từ ngày (Y-m-d)
- `den_ngay` (optional) - Lọc đến ngày (Y-m-d)
- `page` (optional, default: 1) - Trang hiện tại
- `limit` (optional, default: 50) - Số bản ghi mỗi trang

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "PhieuXuatGC_ID": 1,
      "MaPhieu": "GC-MNN-123-1730000000",
      "CBH_ID": 123,
      "SoCBH": "CBH-2025-001",
      "MaSanPhamXuat": "ULA 125x40-M10",
      "MaSanPhamNhan": "ULA 125x40-M10-HDG",
      "SoLuongXuat": 100,
      "SoLuongNhapVe": 95,
      "TrangThai": "Đã nhập kho",
      "TienDoNhap": "95/100 (95%)",
      "NgayXuat": "2025-10-30 10:30:00",
      "NgayNhapKho": "2025-10-30 15:45:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 50,
    "total": 1,
    "totalPages": 1
  },
  "stats": {
    "Đã xuất": 5,
    "Đang gia công": 3,
    "Đã nhập kho": 12
  }
}
```

## Logic Tìm Sản Phẩm

### Quy Tắc Mapping Sản Phẩm

Khi có sản phẩm mạ nhúng nóng (ví dụ: `ULA 125x40-M10-HDG`), hệ thống tìm sản phẩm mạ điện phân tương ứng dựa trên:

1. **Thuộc tính "Xử lý bề mặt":**
   - Sản phẩm xuất: `"Mạ điện phân"`
   - Sản phẩm nhận: `"Mạ nhúng nóng"`

2. **Cùng thuộc tính kỹ thuật:**
   - `ID Thông Số` (ví dụ: 125x40)
   - `Kích thước ren` (ví dụ: M10)

3. **Không có hậu tố SKU:**
   - Loại trừ: `-HDG`, `-MNN`, `-PVC`, `-CP`
   - Ví dụ mapping:
     - `ULA 125x40-M10-HDG` ➜ `ULA 125x40-M10`
     - `ULA 100x50-M8-HDG` ➜ `ULA 100x50-M8`

### Ví Dụ Cụ Thể

```
Đơn hàng yêu cầu: 100 bộ "ULA 125x40-M10-HDG" (Mạ nhúng nóng)
                           ↓
Kiểm tra tồn kho: ULA 125x40-M10-HDG = 20 bộ (Thiếu 80 bộ)
                           ↓
Tìm sản phẩm MĐP: ULA 125x40-M10 (Mạ điện phân)
                           ↓
Kiểm tra tồn kho MĐP: 150 bộ (Đủ)
                           ↓
Xuất 80 bộ ULA 125x40-M10 đi gia công
                           ↓
Sau gia công: Nhập 80 bộ ULA 125x40-M10-HDG vào kho
                           ↓
Tổng tồn kho MNN: 20 + 80 = 100 bộ (Đủ cho đơn hàng)
```

## Giao Diện Người Dùng

### Trang Chuẩn Bị Hàng

Khi xem chi tiết phiếu chuẩn bị hàng, nếu có sản phẩm ULA mạ nhúng nóng cần gia công, hệ thống hiển thị:

```
┌───────────────────────────────────────────────────────────┐
│ 🏭 XUẤT KHO GIA CÔNG MẠ NHÚNG NÓNG                         │
│ 3 sản phẩm                                                │
├───────────────────────────────────────────────────────────┤
│ ℹ️ Lưu ý: Hệ thống tự động tìm sản phẩm ULA mạ điện phân  │
│ tương ứng để xuất kho gia công...                        │
├───────────────────────────────────────────────────────────┤
│ STT │ SP Nhúng Nóng      │ SP Mạ ĐP  │ Cần │ Xuất GC │... │
├─────┼────────────────────┼───────────┼─────┼─────────┼────┤
│ 1   │ ULA 125x40-M10-HDG │ ULA 125.. │ 100 │   80    │ ⚡  │
│     │ Tồn: 20            │ Tồn: 150  │     │         │    │
├─────┼────────────────────┼───────────┼─────┼─────────┼────┤
│ [Xuất tất cả gia công]                                    │
└───────────────────────────────────────────────────────────┘
```

### Danh Sách Phiếu Gia Công

Trang quản lý theo dõi các phiếu đã xuất:

```
┌───────────────────────────────────────────────────────────┐
│ DANH SÁCH PHIẾU XUẤT GIA CÔNG                             │
├───────────────────────────────────────────────────────────┤
│ Trạng thái: [Tất cả ▼] Từ ngày: [____] Đến: [____] [Lọc] │
├─────┬─────────────┬──────────┬──────────┬────────┬────────┤
│ STT │ Mã Phiếu    │ SP Xuất  │ SP Nhận  │ Tiến độ│ TT     │
├─────┼─────────────┼──────────┼──────────┼────────┼────────┤
│ 1   │ GC-MNN-123..│ ULA 125..│ ULA 125..│ 95/100 │ ✅ Xong│
│ 2   │ GC-MNN-124..│ ULA 100..│ ULA 100..│ 0/50   │ 📤 Xuất│
└─────┴─────────────┴──────────┴──────────┴────────┴────────┘
```

## Trạng Thái

### Trạng Thái Phiếu Xuất Gia Công

- **Đã xuất**: Đã xuất kho mạ điện phân, chưa nhập về
- **Đang gia công**: Đã nhập về một phần
- **Đã nhập kho**: Đã nhập về đủ số lượng

### Trạng Thái Chi Tiết CBH

- `TrangThaiGiaCong`:
  - `NULL`: Không cần gia công
  - `Chưa xuất`: Cần gia công nhưng chưa xuất
  - `Đã xuất`: Đã xuất đi gia công
  - `Đang gia công`: Đang trong quá trình
  - `Đã nhập kho`: Đã nhập về đủ

## Báo Cáo & Thống Kê

### Inventory Logs

Mọi thay đổi tồn kho đều được ghi log:

```sql
-- Xuất gia công
change_type: 'XUAT_GIA_CONG'
reference_type: 'PHIEU_XUAT_GIA_CONG'

-- Nhập sau gia công
change_type: 'NHAP_GIA_CONG'
reference_type: 'PHIEU_XUAT_GIA_CONG'
```

### Lịch Sử Gia Công

Bảng `lich_su_gia_cong` theo dõi từng bước:

```
2025-10-30 10:30 | Đã xuất     | Xuất 100 bộ ULA 125x40-M10
2025-10-30 15:45 | Đã nhập kho | Nhập 95 bộ ULA 125x40-M10-HDG
```

## Lưu Ý Quan Trọng

1. **Kiểm tra tồn kho:** Luôn kiểm tra tồn kho khả dụng trước khi xuất
2. **Hao hụt:** Cho phép nhập về ít hơn xuất đi (hao hụt trong gia công)
3. **Transaction:** Tất cả thao tác đều dùng transaction để đảm bảo tính nhất quán
4. **Audit trail:** Mọi thay đổi đều được ghi log
5. **Rollback:** Nếu có lỗi, toàn bộ thao tác sẽ bị rollback

## Troubleshooting

### Không tìm thấy sản phẩm mạ điện phân

**Nguyên nhân:**
- Sản phẩm MĐP chưa được tạo trong hệ thống
- Thuộc tính "ID Thông Số" hoặc "Kích thước ren" không khớp
- SKU có hậu tố không đúng quy chuẩn

**Giải pháp:**
1. Kiểm tra sản phẩm MĐP đã tồn tại chưa
2. Đảm bảo các thuộc tính khớp nhau
3. Kiểm tra SKU không có hậu tố (-HDG, -MNN, -PVC)

### Lỗi không đủ tồn kho

**Nguyên nhân:**
- Tồn kho mạ điện phân không đủ
- Đã có phiếu CBH khác gán tồn kho này

**Giải pháp:**
1. Tạo yêu cầu sản xuất ULA mạ điện phân
2. Chờ sản xuất hoàn tất
3. Thực hiện lại xuất gia công

## Phát Triển Tương Lai

- [ ] Tích hợp với hệ thống ERP nhà cung cấp
- [ ] Tự động tạo yêu cầu sản xuất khi thiếu MĐP
- [ ] Báo cáo chi phí gia công
- [ ] Dự báo nhu cầu gia công
- [ ] Mobile app cho thủ kho

## Liên Hệ & Hỗ Trợ

Nếu có vấn đề, vui lòng liên hệ bộ phận IT hoặc tạo bug report.
