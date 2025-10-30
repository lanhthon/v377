/**
 * File: assets/js/gia_cong_view.js
 * Description: JavaScript cho trang chi tiết phiếu gia công
 */

function initGiaCongViewPage(container) {
    console.log('[GIA_CONG_VIEW] Khởi tạo trang chi tiết');

    // Get phieu ID from URL
    const params = new URLSearchParams(window.location.search);
    const phieuId = params.get('id');

    if (!phieuId) {
        showError('Không tìm thấy ID phiếu gia công');
        return;
    }

    // Elements
    const loadingSection = container.find('#loading-section');
    const mainContent = container.find('#main-content');
    const phieuInfo = container.find('#phieu-info');
    const sanPhamXuat = container.find('#san-pham-xuat');
    const sanPhamNhan = container.find('#san-pham-nhan');
    const slXuat = container.find('#sl-xuat');
    const slNhap = container.find('#sl-nhap');
    const slConLai = container.find('#sl-con-lai');
    const progressBar = container.find('#progress-bar');
    const progressText = container.find('#progress-text');
    const formNhapKho = container.find('#form-nhap-kho-section');
    const inputSoLuongNhap = container.find('#input-so-luong-nhap');
    const inputNgayNhap = container.find('#input-ngay-nhap');
    const inputGhiChu = container.find('#input-ghi-chu');
    const btnNhapKho = container.find('#btn-nhap-kho');
    const maxNhap = container.find('#max-nhap');
    const lichSuContainer = container.find('#lich-su-container');

    // State
    let currentPhieu = null;

    /**
     * Load chi tiết phiếu
     */
    function loadPhieuDetails() {
        $.ajax({
            url: 'api/get_gia_cong_detail.php',
            method: 'GET',
            data: { id: phieuId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currentPhieu = response.data;
                    renderPhieuInfo(currentPhieu);
                    renderSanPham(currentPhieu);
                    renderProgress(currentPhieu);
                    renderLichSu(response.lich_su || []);

                    loadingSection.hide();
                    mainContent.removeClass('hidden');

                    // Show/hide form nhập kho
                    if (currentPhieu.TrangThai === 'Đã nhập kho') {
                        formNhapKho.hide();
                    } else {
                        setupFormNhapKho(currentPhieu);
                    }
                } else {
                    showError(response.message || 'Không thể tải thông tin phiếu');
                }
            },
            error: function(xhr) {
                console.error('Error loading phieu:', xhr);
                showError('Lỗi kết nối đến máy chủ');
            }
        });
    }

    /**
     * Render thông tin phiếu
     */
    function renderPhieuInfo(phieu) {
        const statusClass = getStatusClass(phieu.TrangThai);

        phieuInfo.html(`
            <div>
                <label class="text-sm text-gray-600">Mã phiếu:</label>
                <p class="font-semibold text-gray-900">${phieu.MaPhieu}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Phiếu CBH:</label>
                <p class="font-semibold">
                    <a href="?page=chuanbi_hang_edit&id=${phieu.CBH_ID}" class="text-indigo-600 hover:text-indigo-900">
                        ${phieu.SoCBH || 'N/A'}
                    </a>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Trạng thái:</label>
                <p><span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${phieu.TrangThai}</span></p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Loại gia công:</label>
                <p class="font-semibold text-gray-900">${phieu.LoaiGiaCong}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Người xuất:</label>
                <p class="font-semibold text-gray-900">${phieu.NguoiXuat || 'N/A'}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Ngày xuất:</label>
                <p class="font-semibold text-gray-900">${formatDateTime(phieu.NgayXuat)}</p>
            </div>
            ${phieu.NgayNhapKho ? `
            <div>
                <label class="text-sm text-gray-600">Người nhập kho:</label>
                <p class="font-semibold text-gray-900">${phieu.NguoiNhapKho || 'N/A'}</p>
            </div>
            <div>
                <label class="text-sm text-gray-600">Ngày nhập kho:</label>
                <p class="font-semibold text-gray-900">${formatDateTime(phieu.NgayNhapKho)}</p>
            </div>
            ` : ''}
            ${phieu.GhiChu ? `
            <div class="col-span-2">
                <label class="text-sm text-gray-600">Ghi chú:</label>
                <p class="text-gray-900 whitespace-pre-wrap">${phieu.GhiChu}</p>
            </div>
            ` : ''}
        `);
    }

    /**
     * Render sản phẩm
     */
    function renderSanPham(phieu) {
        sanPhamXuat.html(`
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Mã sản phẩm:</label>
                    <p class="font-medium text-gray-900">${phieu.MaSanPhamXuat}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Tên sản phẩm:</label>
                    <p class="text-sm text-gray-700">${phieu.TenSanPhamXuat || 'N/A'}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Số lượng xuất:</label>
                    <p class="text-lg font-bold text-blue-600">${phieu.SoLuongXuat}</p>
                </div>
            </div>
        `);

        sanPhamNhan.html(`
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Mã sản phẩm:</label>
                    <p class="font-medium text-gray-900">${phieu.MaSanPhamNhan}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Tên sản phẩm:</label>
                    <p class="text-sm text-gray-700">${phieu.TenSanPhamNhan || 'N/A'}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Số lượng nhận:</label>
                    <p class="text-lg font-bold text-green-600">${phieu.SoLuongNhapVe} / ${phieu.SoLuongXuat}</p>
                </div>
            </div>
        `);
    }

    /**
     * Render tiến độ
     */
    function renderProgress(phieu) {
        const soLuongXuat = parseInt(phieu.SoLuongXuat) || 0;
        const soLuongNhap = parseInt(phieu.SoLuongNhapVe) || 0;
        const conLai = soLuongXuat - soLuongNhap;
        const percent = soLuongXuat > 0 ? Math.round((soLuongNhap / soLuongXuat) * 100) : 0;

        slXuat.text(soLuongXuat);
        slNhap.text(soLuongNhap);
        slConLai.text(conLai);
        progressBar.css('width', percent + '%');
        progressText.text(percent + '%');

        // Change color based on progress
        if (percent === 100) {
            progressBar.removeClass('bg-yellow-500 bg-blue-500').addClass('bg-green-500');
        } else if (percent > 0) {
            progressBar.removeClass('bg-green-500 bg-blue-500').addClass('bg-yellow-500');
        } else {
            progressBar.removeClass('bg-green-500 bg-yellow-500').addClass('bg-blue-500');
        }
    }

    /**
     * Setup form nhập kho
     */
    function setupFormNhapKho(phieu) {
        const conLai = parseInt(phieu.SoLuongXuat) - parseInt(phieu.SoLuongNhapVe);
        maxNhap.text(conLai);
        inputSoLuongNhap.attr('max', conLai).val(conLai);
        inputNgayNhap.val(new Date().toISOString().split('T')[0]);
    }

    /**
     * Render lịch sử
     */
    function renderLichSu(lichSu) {
        if (!lichSu || lichSu.length === 0) {
            lichSuContainer.html('<p class="text-gray-500 text-sm">Chưa có lịch sử gia công</p>');
            return;
        }

        let html = '<div class="space-y-3">';
        lichSu.forEach((item, index) => {
            const statusClass = getStatusClass(item.TrangThai);
            html += `
                <div class="flex items-start border-l-4 border-orange-500 pl-4 py-2 ${index > 0 ? 'border-t border-gray-200 pt-4' : ''}">
                    <div class="flex-shrink-0">
                        <i class="fas fa-circle text-orange-500 text-xs"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">${item.TrangThai}</span>
                            <span class="text-xs text-gray-500">${formatDateTime(item.NgayCapNhat)}</span>
                        </div>
                        <p class="text-sm text-gray-700">${item.MoTa}</p>
                        ${item.NguoiCapNhat ? `<p class="text-xs text-gray-500 mt-1">Bởi: ${item.NguoiCapNhat}</p>` : ''}
                    </div>
                </div>
            `;
        });
        html += '</div>';

        lichSuContainer.html(html);
    }

    /**
     * Xử lý nhập kho
     */
    btnNhapKho.on('click', function() {
        const soLuongNhap = parseInt(inputSoLuongNhap.val());
        const ngayNhap = inputNgayNhap.val();
        const ghiChu = inputGhiChu.val().trim();

        // Validate
        if (!soLuongNhap || soLuongNhap <= 0) {
            alert('Vui lòng nhập số lượng hợp lệ');
            return;
        }

        const conLai = parseInt(currentPhieu.SoLuongXuat) - parseInt(currentPhieu.SoLuongNhapVe);
        if (soLuongNhap > conLai) {
            alert(`Số lượng nhập vượt quá số lượng còn lại (${conLai})`);
            return;
        }

        if (!confirm(`Xác nhận nhập ${soLuongNhap} sản phẩm ${currentPhieu.MaSanPhamNhan} vào kho?`)) {
            return;
        }

        // Disable button
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...');

        // Send request
        $.ajax({
            url: 'api/import_gia_cong_ma_nhung_nong.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                phieu_xuat_gc_id: phieuId,
                so_luong_nhap: soLuongNhap,
                nguoi_nhap: window.currentUser || 'Hệ thống',
                ghi_chu: ghiChu
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Nhập kho thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể nhập kho'));
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                const errorMsg = xhr.responseJSON?.message || 'Lỗi kết nối đến máy chủ';
                alert('Lỗi: ' + errorMsg);
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    /**
     * Helper functions
     */
    function getStatusClass(status) {
        switch (status) {
            case 'Đã xuất':
                return 'bg-blue-100 text-blue-800';
            case 'Đang gia công':
                return 'bg-yellow-100 text-yellow-800';
            case 'Đã nhập kho':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showError(message) {
        loadingSection.html(`
            <div class="text-center text-red-500">
                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                <p class="text-lg font-semibold">${message}</p>
                <a href="?page=gia_cong_list" class="mt-4 inline-block px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Quay lại danh sách
                </a>
            </div>
        `);
    }

    // Initial load
    loadPhieuDetails();
}

// Export to global
window.initGiaCongViewPage = initGiaCongViewPage;
