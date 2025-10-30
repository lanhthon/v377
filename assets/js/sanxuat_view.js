function initializeSanXuatViewPage() {
    const params = new URLSearchParams(window.location.search);
    const donhangId = params.get('id');
    const itemsBody = $('#sanxuat-items-body');

    if (!donhangId) {
        showMessageModal('Không tìm thấy ID đơn hàng.', 'error');
        return;
    }

    $('#page-title').text(`Kế Hoạch Sản Xuất cho Đơn Hàng #${donhangId}`);
    itemsBody.html('<tr><td colspan="6" class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Đang tính toán...</td></tr>');

    $.ajax({
        url: `api/tinh_toan_san_xuat.php?id=${donhangId}`,
        dataType: 'json',
        success: function (res) {
            itemsBody.empty();
            if (res.success && res.items.length > 0) {
                res.items.forEach(item => {
                    const row = `
                    <tr>
                        <td class="py-2 px-3 border-b">${item.MaHang}</td>
                        <td class="py-2 px-3 border-b">${item.TenSanPham}</td>
                        <td class="py-2 px-3 border-b text-right">${formatNumber(item.SoLuongYeuCau)}</td>
                        <td class="py-2 px-3 border-b text-right">${formatNumber(item.TonKhoBo)}</td>
                        <td class="py-2 px-3 border-b text-right">${formatNumber(item.SoLuongCanSanXuat)}</td>
                        <td class="py-2 px-3 border-b text-right font-bold text-red-600 bg-red-50">${formatNumber(item.CayCanCat)}</td>
                    </tr>
                `;
                    itemsBody.append(row);
                });
            } else {
                itemsBody.html('<tr><td colspan="6" class="text-center p-4">Không có dữ liệu hoặc có lỗi xảy ra.</td></tr>');
            }
        },
        error: function () {
            itemsBody.html('<tr><td colspan="6" class="text-center p-4 text-red-500">Lỗi khi gọi API tính toán.</td></tr>');
        }
    });
}