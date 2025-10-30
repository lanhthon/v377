/**
 * =================================================================================
 * SCRIPT QUẢN LÝ TRANG CHỈNH SỬA PHIẾU CHUẨN BỊ HÀNG (VERSION 23.3 - FIX CUTTING BUTTON VISIBILITY)
 * =================================================================================
 * - [CẬP NHẬT V23.3] Sửa lỗi logic hiển thị nút "Tạo PXK BTP (Cây Cắt)". Cho phép hiển thị khi TrangThaiXuatBTP là 'Chờ xuất' (trường hợp đủ BTP).
 * - [CẬP NHẬT V23.2] Cải thiện logic hiển thị nút "Yêu Cầu SX BTP". Nút sẽ bị ẩn nếu yêu cầu đã được gửi và đang ở trạng thái chờ xử lý ('Chờ duyệt', 'Đang SX', v.v.).
 * - [CẬP NHẬT V23.1] Sửa lỗi logic trong hàm renderActionButtons khiến nút "Yêu Cầu SX BTP" không hiển thị khi cần.
 * - [CẬP NHẬT V23.0] Bổ sung hàm showBtpCuttingConfirmationModal để sửa lỗi 'is not defined'.
 * - Cửa sổ xác nhận xuất kho BTP giờ đây cho phép xem chi tiết và chỉnh sửa số lượng.
 * - [CẬP NHẬT V22.0] Cập nhật logic tạo Phiếu Xuất Kho (PXK) Tổng.
 * =================================================================================
 */

// --- CÁC HÀM TIỆN ÍCH ---
const formatDateOnly = (dateString) => dateString ? new Date(dateString).toISOString().split('T')[0] : '';
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'Chưa có';
    const date = new Date(dateTimeString);
    return date.toLocaleString('vi-VN', {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};

function handleAjaxError(xhr, defaultMessage = 'Lỗi kết nối đến máy chủ.') {
    console.error("Lỗi AJAX:", xhr.status, xhr.statusText, xhr.responseText);
    let errorMessage = defaultMessage;
    try {
        const response = JSON.parse(xhr.responseText);
        if (response && response.message) {
            errorMessage = response.message;
        }
    } catch (e) {
        if (xhr.statusText && xhr.statusText !== 'error') {
            errorMessage = `${defaultMessage} (${xhr.statusText})`;
        }
    }
    return errorMessage;
}

function getStatusClass(status) {
    switch (status) {
        // Gray/Neutral
        case 'Hoàn thành': case 'Đã xuất kho': case 'Đã giao hàng': case 'Không cần':
            return 'bg-gray-200 text-gray-800';
        
        // Green/Success
        case 'Đã chuẩn bị': case 'Cần nhập': case 'Đã nhập kho VT': case 'Đủ hàng': case 'Đã nhập': case 'Đã xuất':
            return 'bg-green-100 text-green-800';
            
        // Teal/In Progress
        case 'Chờ xuất kho': case 'Đã nhập kho TP':
            return 'bg-teal-100 text-teal-800';
            
        // Yellow/Pending/Warning
        case 'Đang SX':
        case 'Chờ duyệt': case 'Đã duyệt (đang sx)': case 'Chờ xử lý':
        case 'Chưa nhập': case 'Chưa xuất': case 'Mới tạo': case 'Chờ cắt':
            return 'bg-yellow-100 text-yellow-800';

        // Indigo/Special Process
        case 'Chờ nhập': case 'Đang nhập kho VT': case 'Chờ BTP':
            return 'bg-indigo-100 text-indigo-800';

        // Red/Urgent
        case 'Chờ xuất':
            return 'bg-orange-100 text-orange-800';

        // Default/Info
        default:
            return 'bg-blue-100 text-blue-800';
    }
}

function createModal(id, title, content, type = 'info', showCancel = false) {
    $(`#${id}`).remove();
    const typeConfig = {
        success: { icon: 'fa-check', color: 'green' },
        error: { icon: 'fa-exclamation-triangle', color: 'red' },
        info: { icon: 'fa-info-circle', color: 'blue' }
    };
    const config = typeConfig[type] || typeConfig['info'];
    const modalHtml = `
        <div id="${id}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center no-print">
            <div class="relative p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-${config.color}-100">
                        <i class="fas ${config.icon} text-${config.color}-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">${title}</h3>
                    <div class="mt-2 px-7 py-3"><div class="text-sm text-gray-500">${content}</div></div>
                    <div class="items-center px-4 py-3 space-y-2 md:space-y-0 md:space-x-4 md:flex md:justify-center">
                        <button id="${id}-ok-btn" class="w-full md:w-auto px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">OK</button>
                        ${showCancel ? `<button id="${id}-cancel-btn" class="w-full md:w-auto px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">Hủy</button>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHtml);
}

// --- HÀM KHỞI TẠO CHÍNH ---

function initializeChuanBiHangEditPage(mainContentContainer) {
    const params = new URLSearchParams(window.location.search);
    const cbhId = params.get('id');
    const mode = params.get('mode');
    let dinhMucThungData = []; 
    let currentUser = 'Admin'; // Khai báo biến currentUser
    // Thêm vào phần khai báo biến global


let danhSachGiaCongData = [];
let currentCbhId = null;


    if (!cbhId) {
        mainContentContainer.html('<div class="text-center p-8">ID phiếu không được tìm thấy.</div>');
        return;
    }

    // --- CÁC HÀM UTILITY CHO FORM ---
    
    function markFormAsChanged() {
        if (!window.formChanged) {
            window.formChanged = true;
            
            const saveBtn = $('#save-chuanbi-btn');
            if (!saveBtn.find('.change-indicator').length) {
                saveBtn.append('<span class="change-indicator ml-1 text-red-300">●</span>');
            }
            
            $(window).on('beforeunload.formChanged', function(e) {
                if (window.formChanged) {
                    e.returnValue = 'Bạn có thay đổi chưa được lưu. Bạn có chắc muốn rời khỏi trang?';
                    return e.returnValue;
                }
            });
        }
    }

    function resetFormChangedState() {
        window.formChanged = false;
        $('#save-chuanbi-btn .change-indicator').remove();
        $(window).off('beforeunload.formChanged');
    }

    function updateCvCtDisplay(row, canSanXuatCay) {
        const maHang = row.find('td:eq(1)').text().trim();
        
        const cvText = row.find('td:eq(10)').html();
        const ctText = row.find('td:eq(11)').html();
        
        const cvMatch = cvText.match(/class="font-bold text-blue-600">([^<]+)</);
        const ctMatch = ctText.match(/class="font-bold text-blue-600">([^<]+)</);
        
        const khaDungCV = cvMatch ? parseInt(cvMatch[1].replace(/[,\s]/g, '')) || 0 : 0;
        const khaDungCT = ctMatch ? parseInt(ctMatch[1].replace(/[,\s]/g, '')) || 0 : 0;
        
        let cvCanSX = 0;
        let ctCanSX = 0;
        
        if (maHang.includes('PUR-S')) {
            cvCanSX = Math.max(0, canSanXuatCay - khaDungCV);
            ctCanSX = Math.max(0, canSanXuatCay - khaDungCT);
        } else if (maHang.includes('PUR-C')) {
            const ctCanSX_total = Math.max(0, (canSanXuatCay * 2) - khaDungCT);
            cvCanSX = 0;
            ctCanSX = ctCanSX_total;
        } else {
            cvCanSX = Math.max(0, canSanXuatCay - khaDungCV);
            ctCanSX = Math.max(0, canSanXuatCay - khaDungCT);
        }
        
        const cvSpan = row.find('td:eq(12) .font-semibold span:first');
        const ctSpan = row.find('td:eq(12) .font-semibold span:last');
        
        cvSpan.text(window.App.formatNumber(cvCanSX))
              .removeClass('text-red-500 text-green-500')
              .addClass(cvCanSX > 0 ? 'text-red-500' : 'text-green-500');
              
        ctSpan.text(window.App.formatNumber(ctCanSX))
              .removeClass('text-red-500 text-green-500')
              .addClass(ctCanSX > 0 ? 'text-red-500' : 'text-green-500');
    }

    // --- HÀM MODAL XÁC NHẬN XUẤT BTP ---
    function showBtpCuttingConfirmationModal(items, cbh_id) {
        const title = "Xác nhận Xuất kho BTP để Cắt";
        let tableRowsHtml = '';

        if (!items || items.length === 0) {
            showMessageModal('Không có BTP nào cần xuất để cắt cho phiếu này.', 'info');
            return;
        }

        items.forEach((item, index) => {
            const tonKhoVatLy = parseInt(item.tonKhoVatLy) || 0;
            const ganChoPhieuNay = parseInt(item.ganChoPhieuNay) || 0;
            const soCayCat = parseInt(item.SoCayCat) || 0;

            tableRowsHtml += `
                <tr data-variant-id="${item.variant_id}" data-so-luong-yeu-cau="${soCayCat}">
                    <td class="px-3 py-2 text-center">${index + 1}</td>
                    <td class="px-3 py-2 font-semibold text-blue-700">${item.MaBTP}</td>
                    <td class="px-3 py-2 text-sm text-gray-700">${item.TenBTP}</td>
                    <td class="px-3 py-2 text-center">${tonKhoVatLy}</td>
                    <td class="px-3 py-2 text-center text-orange-600">${ganChoPhieuNay}</td>
                    <td class="px-3 py-2">
                        <input type="number" class="btp-cutting-qty-input w-full border-gray-300 rounded-md shadow-sm text-center font-bold" value="${soCayCat}" min="0">
                    </td>
                </tr>
            `;
        });

        const modalContent = `
            <p class="text-left text-sm text-gray-600 mb-3">Vui lòng kiểm tra và xác nhận số lượng Bán thành phẩm (cây) cần xuất kho để đem đi cắt. Bạn có thể điều chỉnh số lượng thực xuất nếu cần.</p>
            <div class="max-h-80 overflow-y-auto border rounded-md">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase">STT</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Mã BTP</th>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Tên BTP</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase">Tồn Kho VL</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase">Đã Gán</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase">SL Thực Xuất</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${tableRowsHtml}
                    </tbody>
                </table>
            </div>
            <p class="text-left text-xs text-gray-500 mt-2 italic">* Tồn Kho VL: Tồn kho vật lý. Đã Gán: Số lượng đã được hệ thống giữ cho phiếu này.</p>
        `;
        
        createModal('btp-cutting-confirm-modal', title, modalContent, 'info', true);
        
        $('#btp-cutting-confirm-modal-ok-btn').off('click').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');

            const itemsToSubmit = [];
            $('#btp-cutting-confirm-modal table tbody tr').each(function() {
                const row = $(this);
                itemsToSubmit.push({
                    variant_id: row.data('variant-id'),
                    so_luong_yeu_cau: row.data('so-luong-yeu-cau'),
                    so_luong_thuc_xuat: row.find('.btp-cutting-qty-input').val()
                });
            });
            
            const postData = {
                cbh_id: cbh_id,
                items: itemsToSubmit
            };

            $.ajax({
                url: 'api/create_pxk_btp_for_cutting.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(postData),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#btp-cutting-confirm-modal').remove();
                        showMessageModal(res.message || 'Tạo phiếu thành công! Đang chuyển hướng...', 'success');
                        
                        if (res.pxk_id) {
                             setTimeout(() => {
                                history.pushState(null, '', `?page=xuatkho_btp_view&pxk_id=${res.pxk_id}`);
                                window.App.handleRouting();
                            }, 1500);
                        } else {
                            loadData();
                        }
                    } else {
                        showMessageModal(res.message || 'Thao tác thất bại.', 'error');
                         btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    const errorMessage = handleAjaxError(xhr, 'Lỗi nghiêm trọng khi tạo phiếu xuất kho.');
                    showMessageModal(errorMessage, 'error');
                     btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#btp-cutting-confirm-modal-cancel-btn').off('click').on('click', function() {
            $('#btp-cutting-confirm-modal').remove();
        });
    }

//gia công nhúng nóng


/**
 * Khởi tạo module gia công mạ nhúng nóng
 */
function initGiaCongMaNhungNong(cbhId) {
    currentCbhId = cbhId;
    console.log('[GIA_CONG] Khởi tạo module cho CBH:', cbhId);
}

/**
 * Hiển thị danh sách sản phẩm cần gia công
 */
function renderDanhSachGiaCong(danhSachGiaCong) {
    danhSachGiaCongData = danhSachGiaCong;

    if (!danhSachGiaCong || danhSachGiaCong.length === 0) {
        $('#gia-cong-section').hide();
        return;
    }

    console.log('[GIA_CONG] Có', danhSachGiaCong.length, 'sản phẩm cần gia công');

    let html = `
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-industry text-orange-500 mr-2"></i>
                    Xuất Kho Gia Công Mạ Nhúng Nóng
                </h2>
                <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
                    ${danhSachGiaCong.length} sản phẩm
                </span>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Quy trình:</strong> Hệ thống tự động tìm sản phẩm ULA mạ điện phân tương ứng.
                            <br/>
                            • Nếu <strong>ĐỦ</strong> mạ điện phân → Xuất kho gia công ngay
                            <br/>
                            • Nếu <strong>THIẾU</strong> mạ điện phân → Tạo yêu cầu sản xuất ULA mạ điện phân trước
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm mạ nhúng nóng</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sản phẩm mạ điện phân</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SL cần</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SL xuất GC</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">SL còn thiếu</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ghi chú</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    danhSachGiaCong.forEach((item, index) => {
        const spNhungNong = item.san_pham_nhung_nong;
        const spDienPhan = item.san_pham_dien_phan;
        const soLuongXuat = item.so_luong_xuat_gia_cong;
        const soLuongThieu = item.so_luong_con_thieu;

        // Chỉ đủ khi số lượng có thể xuất >= số lượng cần thiết
        const hasEnoughDienPhan = soLuongXuat >= soLuongThieu;
        const statusClass = hasEnoughDienPhan ? 'text-green-600' : 'text-red-600';
        const statusIcon = hasEnoughDienPhan ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>
                
                <!-- Sản phẩm mạ nhúng nóng -->
                <td class="px-3 py-4">
                    <div class="text-sm font-medium text-gray-900">${spNhungNong.MaHang}</div>
                    <div class="text-xs text-gray-500">Tồn kho: ${spNhungNong.TonKho - spNhungNong.DaGan}</div>
                </td>
                
                <!-- Sản phẩm mạ điện phân -->
                <td class="px-3 py-4">
                    ${spDienPhan ? `
                        <div class="text-sm text-gray-900">${spDienPhan.sku}</div>
                        <div class="text-xs text-gray-500">Tồn: ${spDienPhan.TonKhoVatLy || 0}</div>
                    ` : '<span class="text-red-500 text-xs">Không tìm thấy</span>'}
                </td>
                
                <!-- SL cần -->
                <td class="px-3 py-4 text-center">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        ${spNhungNong.SoLuongCanSX}
                    </span>
                </td>
                
                <!-- SL xuất GC -->
                <td class="px-3 py-4 text-center">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${hasEnoughDienPhan ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${soLuongXuat}
                    </span>
                </td>
                
                <!-- SL còn thiếu -->
                <td class="px-3 py-4 text-center">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${soLuongThieu > soLuongXuat ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">
                        ${soLuongThieu - soLuongXuat}
                    </span>
                </td>
                
                <!-- Ghi chú -->
                <td class="px-3 py-4">
                    <div class="text-xs ${statusClass}">
                        <i class="fas ${statusIcon} mr-1"></i>
                        ${item.ghi_chu}
                    </div>
                </td>
                
                <!-- Thao tác -->
                <td class="px-3 py-4 text-center">
                    ${hasEnoughDienPhan ? `
                        <button
                            onclick="xuatKhoGiaCong(${index})"
                            class="px-3 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            Xuất gia công
                        </button>
                    ` : (spDienPhan ? `
                        <button
                            onclick="yeuCauSanXuatUlaMdp(${index})"
                            class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-industry mr-1"></i>
                            SX ULA MĐP
                        </button>
                    ` : `
                        <span class="text-xs text-red-500">Không tìm thấy MĐP</span>
                    `)}
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            
            <!-- Action buttons -->
            <div class="mt-4 flex justify-end space-x-3">
                <button 
                    onclick="xuatTatCaGiaCong()"
                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    Xuất tất cả gia công
                </button>
            </div>
        </div>
    `;
    
    $('#gia-cong-section').html(html).show();
}

/**
 * Xuất kho gia công cho 1 sản phẩm
 */
function xuatKhoGiaCong(index) {
    const item = danhSachGiaCongData[index];

    if (!item || item.so_luong_xuat_gia_cong <= 0) {
        showNotification('error', 'Không thể xuất gia công', 'Không đủ hàng mạ điện phân');
        return;
    }

    if (!item.san_pham_dien_phan || !item.san_pham_dien_phan.variant_id) {
        showNotification('error', 'Lỗi', 'Không tìm thấy thông tin sản phẩm mạ điện phân');
        return;
    }
    
    const modalHtml = `
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center" id="xuat-gia-cong-modal">
            <div class="relative p-6 border w-full max-w-lg shadow-lg rounded-lg bg-white">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="fas fa-external-link-alt text-orange-500 mr-2"></i>
                    Xác nhận xuất kho gia công
                </h3>
                
                <div class="space-y-3 mb-6">
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="text-sm text-gray-600">Sản phẩm xuất:</p>
                        <p class="text-base font-semibold text-gray-900">${item.san_pham_dien_phan.sku}</p>
                        <p class="text-xs text-gray-500">Tồn kho: ${item.san_pham_dien_phan.TonKhoVatLy || 0}</p>
                    </div>
                    
                    <div class="bg-orange-50 p-3 rounded">
                        <p class="text-sm text-gray-600">Sản phẩm nhận (sau gia công):</p>
                        <p class="text-base font-semibold text-gray-900">${item.san_pham_nhung_nong.MaHang}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng xuất:</label>
                        <input 
                            type="number" 
                            id="so-luong-xuat-gc" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                            value="${item.so_luong_xuat_gia_cong}"
                            max="${item.so_luong_xuat_gia_cong}"
                            min="1"
                        />
                        <p class="text-xs text-gray-500 mt-1">Tối đa: ${item.so_luong_xuat_gia_cong}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú:</label>
                        <textarea 
                            id="ghi-chu-xuat-gc" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                            rows="2"
                            placeholder="Nhập ghi chú (tùy chọn)..."
                        ></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        onclick="closeXuatGiaCongModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Hủy
                    </button>
                    <button 
                        onclick="confirmXuatGiaCong(${index})"
                        class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <i class="fas fa-check mr-2"></i>
                        Xác nhận xuất
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
}

/**
 * Đóng modal xuất gia công
 */
function closeXuatGiaCongModal() {
    $('#xuat-gia-cong-modal').remove();
}

/**
 * Xác nhận xuất kho gia công
 */
function confirmXuatGiaCong(index) {
    const item = danhSachGiaCongData[index];
    const soLuongXuat = parseInt($('#so-luong-xuat-gc').val());
    const ghiChu = $('#ghi-chu-xuat-gc').val().trim();
    
    if (!soLuongXuat || soLuongXuat <= 0) {
        showNotification('error', 'Lỗi', 'Số lượng xuất không hợp lệ');
        return;
    }
    
    if (soLuongXuat > item.so_luong_xuat_gia_cong) {
        showNotification('error', 'Lỗi', 'Số lượng xuất vượt quá số lượng có thể xuất');
        return;
    }
    
    // Hiển thị loading
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
    btn.disabled = true;
    
    const requestData = {
        cbh_id: currentCbhId,
        chi_tiet_cbh_id: item.san_pham_nhung_nong.ChiTietCBH_ID,
        variant_id_dien_phan: item.san_pham_dien_phan.variant_id,
        so_luong_xuat: soLuongXuat,
        nguoi_xuat: currentUser || 'Hệ thống',
        ghi_chu: ghiChu || `Xuất gia công mạ nhúng nóng từ CBH-${currentCbhId}`
    };
    
    $.ajax({
        url: 'api/process_gia_cong_ma_nhung_nong.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Thành công', response.message || 'Đã xuất kho gia công');
                closeXuatGiaCongModal();
                
                // Reload trang để cập nhật dữ liệu
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('error', 'Lỗi', response.message || 'Không thể xuất kho gia công');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function(xhr) {
            const errorMsg = handleAjaxError(xhr, 'Lỗi khi xuất kho gia công');
            showNotification('error', 'Lỗi', errorMsg);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

/**
 * Xuất tất cả sản phẩm cần gia công
 */
function xuatTatCaGiaCong() {
    const itemsToProcess = danhSachGiaCongData.filter(item => item.so_luong_xuat_gia_cong > 0);
    
    if (itemsToProcess.length === 0) {
        showNotification('warning', 'Thông báo', 'Không có sản phẩm nào có thể xuất gia công');
        return;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn xuất kho gia công cho ${itemsToProcess.length} sản phẩm?`)) {
        return;
    }
    
    let successCount = 0;
    let errorCount = 0;
    let processedCount = 0;
    
    showNotification('info', 'Đang xử lý', `Đang xuất kho gia công cho ${itemsToProcess.length} sản phẩm...`);
    
    itemsToProcess.forEach((item, index) => {
        const requestData = {
            cbh_id: currentCbhId,
            chi_tiet_cbh_id: item.san_pham_nhung_nong.ChiTietCBH_ID,
            variant_id_dien_phan: item.san_pham_dien_phan.variant_id,
            so_luong_xuat: item.so_luong_xuat_gia_cong,
            nguoi_xuat: currentUser || 'Hệ thống',
            ghi_chu: `Xuất gia công hàng loạt từ CBH-${currentCbhId}`
        };
        
        $.ajax({
            url: 'api/process_gia_cong_ma_nhung_nong.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(requestData),
            success: function(response) {
                processedCount++;
                if (response.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
                
                // Kiểm tra xem đã xử lý hết chưa
                if (processedCount === itemsToProcess.length) {
                    showNotification(
                        errorCount > 0 ? 'warning' : 'success',
                        'Hoàn thành',
                        `Đã xuất kho: ${successCount}. Lỗi: ${errorCount}`
                    );
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            },
            error: function(xhr) {
                processedCount++;
                errorCount++;
                
                if (processedCount === itemsToProcess.length) {
                    showNotification('error', 'Lỗi', `Đã xuất kho: ${successCount}. Lỗi: ${errorCount}`);
                }
            }
        });
    });
}

/**
 * Hiển thị thông báo
 */
function showNotification(type, title, message) {
    const iconMap = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };

    const colorMap = {
        success: 'green',
        error: 'red',
        warning: 'yellow',
        info: 'blue'
    };

    const icon = iconMap[type] || iconMap.info;
    const color = colorMap[type] || colorMap.info;

    createModal('notification-modal', title, message, type, false);

    $('#notification-modal-ok-btn').on('click', function() {
        $('#notification-modal').remove();
    });
}

/**
 * Yêu cầu sản xuất ULA mạ điện phân (khi tồn kho không đủ)
 */
function yeuCauSanXuatUlaMdp(index) {
    const item = danhSachGiaCongData[index];

    if (!item || !item.san_pham_dien_phan) {
        showNotification('error', 'Lỗi', 'Không tìm thấy thông tin sản phẩm mạ điện phân');
        return;
    }

    const spDienPhan = item.san_pham_dien_phan;
    const spNhungNong = item.san_pham_nhung_nong;

    // Tạo modal xác nhận
    const modalHtml = `
        <div id="yeu-cau-sx-modal" class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-industry text-orange-500 mr-2"></i>
                        Yêu Cầu Sản Xuất ULA Mạ Điện Phân
                    </h3>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Lưu ý:</strong> Tồn kho mạ điện phân không đủ để gia công.
                                    <br/>Cần tạo yêu cầu sản xuất ULA mạ điện phân trước khi gia công.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="border-b pb-3">
                            <h4 class="font-semibold text-gray-700 mb-2">Sản phẩm cần gia công (MNN):</h4>
                            <div class="pl-4 text-sm">
                                <p><strong>Mã:</strong> ${spNhungNong.VariantSKU}</p>
                                <p><strong>Tên:</strong> ${spNhungNong.VariantName}</p>
                                <p class="text-red-600"><strong>Số lượng cần:</strong> ${item.so_luong_xuat_gia_cong}</p>
                            </div>
                        </div>

                        <div class="border-b pb-3">
                            <h4 class="font-semibold text-gray-700 mb-2">Sản phẩm cần sản xuất (MĐP):</h4>
                            <div class="pl-4 text-sm">
                                <p><strong>Mã:</strong> ${spDienPhan.VariantSKU}</p>
                                <p><strong>Tên:</strong> ${spDienPhan.VariantName}</p>
                                <p><strong>Tồn kho hiện tại:</strong> <span class="text-red-600">${spDienPhan.SoLuongTon} (thiếu ${item.so_luong_xuat_gia_cong - spDienPhan.SoLuongTon})</span></p>
                                <p class="text-blue-600"><strong>Số lượng yêu cầu SX:</strong> ${item.so_luong_xuat_gia_cong}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú yêu cầu:</label>
                            <textarea
                                id="ghi-chu-yeu-cau-sx"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                rows="3"
                                placeholder="Ghi chú về yêu cầu sản xuất..."
                            >Yêu cầu SX ULA MĐP phục vụ gia công mạ nhúng nóng cho CBH-${currentCbhId}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button
                            onclick="closeYeuCauSxModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                            Hủy
                        </button>
                        <button
                            onclick="confirmYeuCauSxUlaMdp(${index})"
                            class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <i class="fas fa-check mr-2"></i>
                            Tạo yêu cầu SX
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
}

/**
 * Đóng modal yêu cầu sản xuất
 */
function closeYeuCauSxModal() {
    $('#yeu-cau-sx-modal').remove();
}

/**
 * Xác nhận yêu cầu sản xuất ULA mạ điện phân
 */
function confirmYeuCauSxUlaMdp(index) {
    const item = danhSachGiaCongData[index];
    const ghiChu = $('#ghi-chu-yeu-cau-sx').val().trim();

    // Hiển thị loading
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
    btn.disabled = true;

    const requestData = {
        cbh_id: currentCbhId,
        chi_tiet_cbh_id: item.san_pham_nhung_nong.ChiTietCBH_ID,
        variant_id_mdp: item.san_pham_dien_phan.VariantID,
        so_luong: item.so_luong_xuat_gia_cong,
        nguoi_tao: currentUser || 'Hệ thống',
        ghi_chu: ghiChu || `Yêu cầu SX ULA MĐP phục vụ gia công MNN cho CBH-${currentCbhId}`
    };

    $.ajax({
        url: 'api/create_production_request_ula_mdp.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        success: function(response) {
            if (response.success) {
                showNotification(
                    'success',
                    'Thành công',
                    `Đã tạo yêu cầu sản xuất: ${response.so_lenh_sx || 'N/A'}`
                );
                closeYeuCauSxModal();

                // Reload trang để cập nhật dữ liệu
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('error', 'Lỗi', response.message || 'Không thể tạo yêu cầu sản xuất');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function(xhr) {
            console.error('Error creating production request:', xhr);
            let errorMsg = 'Lỗi kết nối đến máy chủ';

            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMsg = response.message;
                }
            } catch (e) {
                // Keep default error message
            }

            showNotification('error', 'Lỗi', errorMsg);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

// Export functions gia công ra global scope để có thể gọi từ HTML onclick
window.xuatKhoGiaCong = xuatKhoGiaCong;
window.closeXuatGiaCongModal = closeXuatGiaCongModal;
window.confirmXuatGiaCong = confirmXuatGiaCong;
window.xuatTatCaGiaCong = xuatTatCaGiaCong;
window.showNotification = showNotification;
window.initGiaCongMaNhungNong = initGiaCongMaNhungNong;
window.renderDanhSachGiaCong = renderDanhSachGiaCong;
window.yeuCauSanXuatUlaMdp = yeuCauSanXuatUlaMdp;
window.closeYeuCauSxModal = closeYeuCauSxModal;
window.confirmYeuCauSxUlaMdp = confirmYeuCauSxUlaMdp;

/// hết gia công nhúng nóng











    // --- HÀM TÍNH TỔNG ---
    function calculateAndUpdateTotals() {
        const purTableBody = $('#pur-table-body');
        purTableBody.find('.pur-total-row').remove();
        if (purTableBody.find('tr[data-id]').length > 0) {
            let totalThung = 0;
            let chiTietThung = { thungTo: 0, thungNho: 0, boLe: 0 };

            purTableBody.find('tr[data-id]').each(function() {
                const value = $(this).find('input[name="soThung"]').val();
                const ghiChu = $(this).find('input[name="ghiChu"]').val() || '';

                totalThung += parseFloat(value) || 0;

                const matchThungTo = ghiChu.match(/(\d+)\s*thùng to/);
                const matchThungNho = ghiChu.match(/(\d+)\s*thùng nhỏ/);
                const matchBoLe = ghiChu.match(/(\d+)\s*bộ lẻ/);

                if (matchThungTo) chiTietThung.thungTo += parseInt(matchThungTo[1], 10);
                if (matchThungNho) chiTietThung.thungNho += parseInt(matchThungNho[1], 10);
                if (matchBoLe) chiTietThung.boLe += parseInt(matchBoLe[1], 10);
            });
            
            if (chiTietThung.boLe > 0 && typeof dinhMucThungData !== 'undefined' && dinhMucThungData.length > 0) {
                let minSlThungNho = Infinity;
                dinhMucThungData.forEach(dm => {
                    if (dm.so_luong_thung_nho > 0 && dm.so_luong_thung_nho < minSlThungNho) {
                        minSlThungNho = dm.so_luong_thung_nho;
                    }
                });
    
                if (minSlThungNho !== Infinity && chiTietThung.boLe >= minSlThungNho) {
                    const themThungNho = Math.floor(chiTietThung.boLe / minSlThungNho);
                    chiTietThung.thungNho += themThungNho;
                    chiTietThung.boLe %= minSlThungNho;
                }
            }

            let tongChiTiet = [];
            if (chiTietThung.thungTo > 0) tongChiTiet.push(`${chiTietThung.thungTo} thùng to`);
            if (chiTietThung.thungNho > 0) tongChiTiet.push(`${chiTietThung.thungNho} thùng nhỏ`);
            if (chiTietThung.boLe > 0) tongChiTiet.push(`${chiTietThung.boLe} bộ lẻ`);

            const ghiChuTong = tongChiTiet.length > 0 ? `<div class="text-xs text-gray-600 mt-1">${tongChiTiet.join(' + ')}</div>` : '';

            purTableBody.append(`
                <tr class="bg-yellow-100 font-bold text-sm pur-total-row">
                    <td colspan="13" class="p-2 border text-right">Tổng số thùng:</td>
                    <td class="p-2 border text-center">
                        <div class="text-red-600 text-lg">${totalThung.toFixed(2)}</div>
                        ${ghiChuTong}
                    </td>
                    <td class="p-2 border"></td>
                </tr>
            `);
        }

        const processUlaLikeTotal = (tbodySelector) => {
            const tableBody = $(tbodySelector);
            tableBody.find('.ula-total-row').remove();
            if (tableBody.find('tr[data-id]').length > 0) {
                let totalTai = 0;
                let totalKg = 0;

                tableBody.find('tr[data-id]').each(function() {
                    const taiValue = $(this).find('input[name="dongGoi"]').val();
                    totalTai += parseFloat(taiValue) || 0;

                    const kgValue = $(this).find('input[name="ghiChu"]').val();
                    const kgMatch = kgValue.match(/(\d+\.?\d*)/);
                    if (kgMatch && kgMatch[1]) {
                        totalKg += parseFloat(kgMatch[1]) || 0;
                    }
                });

                const colSpan = 9;
                tableBody.append(`
                    <tr class="bg-yellow-100 font-bold text-sm ula-total-row">
                        <td colspan="${colSpan}" class="p-2 border text-right"><strong>Tổng cộng:</strong></td>
                        <td class="p-2 border text-center text-red-600"><strong>${totalTai.toFixed(1)} tải</strong></td>
                        <td class="p-2 border text-center text-red-600"><strong>~${totalKg.toFixed(1)} Kg</strong></td>
                    </tr>
                `);
            }
        };

        processUlaLikeTotal('#ula-table-body');
        processUlaLikeTotal('#deo-treo-table-body');
    }

    // --- HÀM TÍNH GHI CHÚ TỰ ĐỘNG ---
    async function calculateAndFillNotes() {
        let missingDinhMucMessages = [];

        try {
            showMessageModal('<i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu định mức...', 'info');
            let countPUR = 0, countULA = 0, countDeoTreo = 0;

            const dinhMucThungRes = await $.getJSON('api/get_dinh_muc_dong_thung.php');

            if (!dinhMucThungRes.success) {
                throw new Error('Không thể tải dữ liệu định mức từ API.');
            }
            const dinhMucThung = dinhMucThungRes.data;
            dinhMucThungData = dinhMucThung;

            $('#pur-table-body tr[data-id]').each(function(index) {
                const row = $(this);
                const soLuong = parseInt(row.find('td:eq(5)').text().replace(/,/g, '')) || 0;
                const idText = row.find('td:eq(2)').text().trim();
                const doDay = parseInt(row.find('td:eq(3)').text()) || 0;
                const banRong = parseInt(row.find('td:eq(4)').text()) || 0;
                const duongKinhTrong = parseInt(idText.replace(/\D/g, '')) || 0;

                if (soLuong <= 0 || !duongKinhTrong || !doDay || !banRong) return;

                let dinhMuc = dinhMucThung.find(dm => 
                    dm.duong_kinh_trong === duongKinhTrong && dm.ban_rong === banRong && dm.do_day === doDay
                );

                if (!dinhMuc && banRong !== 40) {
                    dinhMuc = dinhMucThung.find(dm => 
                        dm.duong_kinh_trong === duongKinhTrong && dm.ban_rong === 40 && dm.do_day === doDay
                    );
                }

                if (!dinhMuc) {
                    const availableIDs = [...new Set(dinhMucThung.map(dm => dm.duong_kinh_trong))].sort((a, b) => a - b);
                    let closestID = null;
                    let minDiff = Infinity;
                    for (const id of availableIDs) {
                        const diff = Math.abs(id - duongKinhTrong);
                        if (diff < minDiff && diff <= 5) {
                            minDiff = diff;
                            closestID = id;
                        }
                    }
                    if (closestID) {
                        dinhMuc = dinhMucThung.find(dm => 
                            dm.duong_kinh_trong === closestID && dm.ban_rong === 40 && dm.do_day === doDay
                        );
                    }
                }

                if (!dinhMuc) {
                    const missingInfo = `<b>PUR: ID${duongKinhTrong}mm - ${banRong}x${doDay}</b>`;
                    if (!missingDinhMucMessages.includes(missingInfo)) {
                        missingDinhMucMessages.push(missingInfo);
                    }
                    row.find('input[name="ghiChu"]').val(`⚠️ Cần bổ sung định mức`);
                    return;
                }
                
                const slThungTo = dinhMuc.so_luong_thung_to || 0;
                const slThungNho = dinhMuc.so_luong_thung_nho || 0;

                if (slThungTo === 0 && slThungNho === 0) {
                    row.find('input[name="ghiChu"]').val(`⚠️ Định mức bằng 0`);
                    return;
                }

                let soLuongConLai = soLuong;
                let resultText = '';
                let tongSoThung = 0;

                if (slThungTo > 0) {
                    const soThungTo = Math.floor(soLuongConLai / slThungTo);
                    if (soThungTo > 0) {
                        resultText += `${soThungTo} thùng to`;
                        tongSoThung += soThungTo;
                        soLuongConLai -= (soThungTo * slThungTo);
                    }
                }

                if (slThungNho > 0 && soLuongConLai > 0) {
                    const soThungNho = Math.floor(soLuongConLai / slThungNho);
                    if (soThungNho > 0) {
                        if (resultText) resultText += ' + ';
                        resultText += `${soThungNho} thùng nhỏ`;
                        tongSoThung += soThungNho;
                        soLuongConLai -= (soThungNho * slThungNho);
                    }
                }

                let soThungHienThi = tongSoThung;
                if (soLuongConLai > 0) {
                    const dungLuongThamChieu = slThungNho > 0 ? slThungNho : (slThungTo > 0 ? slThungTo : 1);
                    const phanThapPhan = soLuongConLai / dungLuongThamChieu;
                    soThungHienThi = tongSoThung + phanThapPhan;
                }

                row.find('input[name="soThung"]').val(soThungHienThi.toFixed(2));

                let ghiChu = resultText || '';
                if (soLuongConLai > 0) {
                    if (ghiChu) ghiChu += ' + ';
                    ghiChu += `${soLuongConLai} bộ lẻ`;
                }
                if (!ghiChu) ghiChu = `${tongSoThung} thùng`;
                
                row.find('input[name="ghiChu"]').val(ghiChu);
                countPUR++;
            });

            const processUlaLikeTable = (selector) => {
                $(selector).each(function() {
                    const row = $(this);
                    const soLuong = parseInt(row.find('td:eq(5)').text().replace(/,/g, '')) || 0;
                    const maHang = row.find('td:eq(1)').text().trim();
                    
                    if (soLuong <= 0) return;

                    const dinhMucTaiText = row.data('dinh-muc-tai') || '';
                    const dinhMucKgText = row.data('dinh-muc-kg') || '';
                    
                    const soBoTrenTai = parseFloat(dinhMucTaiText);
                    const kgPerSet = parseFloat(dinhMucKgText) || 0;

                    if (!soBoTrenTai || isNaN(soBoTrenTai)) {
                        const missingInfo = `<b>ULA/Đai: ${maHang}</b>`;
                        if (!missingDinhMucMessages.includes(missingInfo)) {
                            missingDinhMucMessages.push(missingInfo);
                        }
                        row.find('input[name="dongGoi"]').val('');
                        row.find('input[name="ghiChu"]').val(`⚠️ Thiếu định mức tải`);
                        return;
                    }
                    
                    if (!kgPerSet || kgPerSet <= 0) {
                        const missingInfo = `<b>ULA/Đai: ${maHang}</b>`;
                        if (!missingDinhMucMessages.includes(missingInfo)) {
                            missingDinhMucMessages.push(missingInfo);
                        }
                        row.find('input[name="ghiChu"]').val(`⚠️ Thiếu định mức kg`);
                        return;
                    }

                    const soTai = soLuong / soBoTrenTai;
                    const tongKg = soLuong * kgPerSet;

                    row.find('input[name="dongGoi"]').val(soTai.toFixed(1));
                    row.find('input[name="ghiChu"]').val(`~${tongKg.toFixed(1)}kg`);
                    
                    if (selector.includes('#ula-table-body')) countULA++;
                    else if (selector.includes('#deo-treo-table-body')) countDeoTreo++;
                });
            };

            processUlaLikeTable('#ula-table-body tr[data-id]');
            processUlaLikeTable('#deo-treo-table-body tr[data-id]');

            calculateAndUpdateTotals();
            $('#message-modal').remove();

            if (missingDinhMucMessages.length > 0) {
                const uniqueMissing = [...new Set(missingDinhMucMessages)];
                let modalContent = `
                    <p class="text-left font-semibold text-gray-800 mb-2">Hệ thống không tìm thấy các định mức sau. Vui lòng bổ sung để tính toán chính xác:</p>
                    <ul class="list-disc list-inside bg-red-50 p-3 rounded-md text-left text-sm text-red-700 space-y-1">
                `;
                uniqueMissing.forEach(msg => {
                    modalContent += `<li>${msg}</li>`;
                });
                modalContent += '</ul><p class="text-xs text-gray-500 mt-3 text-left"><i>Mẹo: Bạn có thể thêm các định mức này trong mục "Quản lý sản phẩm".</i></p>';
                
                createModal('missing-dinh-muc-modal', 'Thiếu Định Mức Quan Trọng', modalContent, 'error');
                $('#missing-dinh-muc-modal-ok-btn').on('click', () => $('#missing-dinh-muc-modal').remove());

            } else {
                const message = `Đã tính toán thành công!<br>- PUR: ${countPUR} dòng<br>- ULA: ${countULA} dòng<br>- Đai Treo: ${countDeoTreo} dòng`;
                showMessageModal(message, 'success');
            }
            
            markFormAsChanged();

        } catch (error) {
            console.error('⚠️ Lỗi nghiêm trọng khi tính toán ghi chú:', error);
            $('#message-modal').remove();
            showMessageModal('Lỗi: ' + error.message, 'error');
        }
    }

    // --- CÁC HÀM TẠO GIAO DIỆN ---
    
    function createDetailedViewHtml(p_order) {
        const { info, items } = p_order;
        const ngayYeuCau = info.NgayYCSX ? new Date(info.NgayYCSX).toLocaleDateString('vi-VN') : 'N/A';
        const ngayHoanThanh = info.NgayHoanThanhUocTinh ? new Date(info.NgayHoanThanhUocTinh).toLocaleDateString('vi-VN') : 'N/A';
        let tongNgayText = '';
        if (info.NgayHoanThanhUocTinh && info.NgayYCSX) {
            const diffTime = Math.abs(new Date(info.NgayHoanThanhUocTinh) - new Date(info.NgayYCSX));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            tongNgayText = `(Tổng ngày: ${diffDays} ngày)`;
        }
        function formatDateForInput(dateStr) {
            if (!dateStr) return '';
            try { return new Date(dateStr).toISOString().split('T')[0]; } catch (e) { return ''; }
        }
        const ngayHoanThanhValue = formatDateForInput(info.NgayHoanThanhUocTinh);
        const isUlaType = (info.LoaiLSX === 'ULA');
        let itemsTableHtml = `
            <table class="min-w-full divide-y divide-gray-200 mt-4 text-sm">
                <thead style="background-color: #92D050;">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Stt.</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Mã hàng</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Khối lượng SX</th>
                        <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Đơn vị</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Mục đích</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase w-40">Trạng thái</th>
                        <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">`;
        items.forEach((item, index) => {
            const statusOptions = ['Mới', 'Đang SX', 'Hoàn thành'];
            let statusDropdown = `<select data-field="TrangThai" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">`;
            statusOptions.forEach(opt => {
                const selected = (item.TrangThaiChiTiet === opt) ? 'selected' : '';
                statusDropdown += `<option value="${opt}" ${selected}>${opt}</option>`;
            });
            statusDropdown += `</select>`;
            const soLuongHienThi = isUlaType ? item.SoLuongBoCanSX : item.SoLuongCayCanSX;
            const soLuongInput = `<input type="number" data-field="SoLuongCanSX" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm text-center font-semibold" value="${soLuongHienThi}">`;
            itemsTableHtml += `
                <tr class="production-item-row" data-id="${item.ChiTiet_LSX_ID}" data-loai-lsx="${info.LoaiLSX}">
                    <td class="px-4 py-2">${index + 1}</td>
                    <td class="px-4 py-2 font-medium text-gray-900">${item.MaBTP}</td>
                    <td class="px-4 py-2 text-center">${soLuongInput}</td>
                    <td class="px-4 py-2 text-center">${item.DonViTinh || 'N/A'}</td>
                    <td class="px-4 py-2">Đơn hàng</td>
                    <td class="px-4 py-2">${statusDropdown}</td>
                    <td class="px-4 py-2">
                        <input type="text" data-field="GhiChu" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm" value="${item.GhiChu || ''}">
                    </td>
                </tr>`;
        });
        itemsTableHtml += '</tbody></table>';
        const actionButtonsHtml = `
            <div class="mt-6 flex justify-end space-x-3">
                <button class="export-btn-excel bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md text-sm" data-id="${info.LenhSX_ID}">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </button>
                <button class="export-btn-pdf bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md text-sm" data-id="${info.LenhSX_ID}">
                    <i class="fas fa-file-pdf mr-2"></i>Xuất PDF
                </button>
                <button class="update-details-btn bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md text-sm" data-id="${info.LenhSX_ID}">
                    <i class="fas fa-save mr-2"></i>Cập nhật
                </button>
            </div>
        `;
        const nguoiNhanSX = info.NguoiNhanSX || 'Mr. Thiết';
        const boPhanSX = info.BoPhanSX || 'Đội trưởng SX';
        return `
            <div class="bg-white p-4 my-4 rounded shadow-sm" id="form-lsx-${info.LenhSX_ID}">
                <div class="flex justify-between items-start mb-4">
                    <div><img src="logo.png" alt="Logo" class="h-10"></div>
                    <div class="text-right">
                        <h2 class="text-2xl font-bold text-gray-800">LỆNH SẢN XUẤT - LSX</h2>
                        <p class="font-semibold text-lg text-red-600">Số: ${info.SoLenhSX}</p>
                    </div>
                </div>
                <div class="border-t border-b py-3 mb-4 text-sm">
                    <div class="flex justify-between items-end">
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <label class="text-gray-500 w-24">Người nhận:</label>
                                <input type="text" data-field="NguoiNhanSX" class="font-semibold p-1 border rounded-md" value="${nguoiNhanSX}">
                            </div>
                            <div class="flex items-center">
                                <label class="text-gray-500 w-24">Đơn vị:</label>
                                <input type="text" data-field="BoPhanSX" class="font-semibold p-1 border rounded-md" value="${boPhanSX}">
                            </div>
                            <p><span class="text-gray-500 w-24 inline-block">Đơn hàng gốc:</span><span class="font-semibold">${info.SoYCSX}</span></p>
                            <p><span class="text-gray-500 w-24 inline-block">Người yêu cầu:</span><span class="font-semibold">${info.NguoiBaoGia || 'N/A'}</span></p>
                        </div>
                        <div class="space-y-2 text-right">
                            <div class="p-2 rounded inline-block" style="background-color: #FFFF00;">
                                <span class="text-gray-600">Ngày yêu cầu:</span>
                                <span class="font-bold text-black" data-ngay-yeu-cau="${formatDateForInput(info.NgayYCSX)}"> ${ngayYeuCau}</span>
                            </div>
                            <br>
                            <div class="p-2 rounded inline-block" style="background-color: #FFFF00;">
                                <label for="ngay-hoan-thanh-${info.LenhSX_ID}" class="text-gray-600">Ngày hoàn thành:</label>
                                <input type="date" id="ngay-hoan-thanh-${info.LenhSX_ID}" data-field="NgayHoanThanhUocTinh" class="font-bold text-black bg-transparent border-none focus:ring-0 p-0" value="${ngayHoanThanhValue}">
                                <span class="text-sm text-gray-700 ml-2" id="tong-ngay-text-${info.LenhSX_ID}">${tongNgayText}</span>
                            </div>
                        </div>
                    </div>
                </div>
                ${itemsTableHtml}
                ${actionButtonsHtml}
            </div>
        `;
    }

    /**
     * Nâng cấp hàm tạo modal để tìm kiếm, thêm, chỉnh sửa và xóa sản phẩm.
     */
    function showConfirmProductionModal(items, orderType, onConfirm) {
        const title = `Tạo Lệnh Sản Xuất ${orderType}`;
        const donViTinh = orderType === 'BTP' ? 'Cây' : 'Bộ';

        let modalContent = `
            <p class="mb-2 text-sm text-left text-gray-600">Kiểm tra, chỉnh sửa số lượng hoặc thêm/xóa sản phẩm trước khi xác nhận.</p>
            
            <!-- DANH SÁCH SẢN PHẨM -->
            <div class="max-h-60 overflow-y-auto border rounded-md mb-4">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase">Mã hàng</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase w-32">Số lượng (${donViTinh})</th>
                            <th class="px-3 py-2 text-center text-xs font-bold text-gray-600 uppercase w-16">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="production-items-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Dữ liệu sẽ được render ở đây -->
                    </tbody>
                </table>
            </div>

            <!-- CHỨC NĂNG TÌM KIẾM & THÊM SẢN PHẨM -->
            <div class="border-t pt-3">
                <label for="item-search-input" class="block text-sm font-medium text-gray-700 mb-1">Thêm sản phẩm khác:</label>
                <div class="relative">
                    <input type="text" id="item-search-input" placeholder="Gõ mã hoặc tên sản phẩm để tìm..." class="w-full border-gray-300 rounded-md shadow-sm">
                    <div id="search-results-container" class="absolute z-20 w-full bg-white border border-gray-300 rounded-md mt-1 shadow-lg max-h-48 overflow-y-auto hidden">
                        <!-- Kết quả tìm kiếm sẽ hiện ở đây -->
                    </div>
                </div>
            </div>

            <p class="mt-4 text-red-600 text-sm font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i>Lệnh sản xuất sẽ được tạo dựa trên danh sách trên và không thể hoàn tác.</p>`;
        
        createModal('confirmation-modal-lsx', title, modalContent, 'info', true);

        const tableBody = $('#production-items-table-body');

        const renderRow = (maHang, soLuong) => {
            if (tableBody.find(`tr[data-ma-hang="${maHang}"]`).length > 0) {
                if(typeof showMessageModal === 'function'){
                    showMessageModal(`Sản phẩm '${maHang}' đã có trong danh sách.`, 'info');
                } else {
                    alert(`Sản phẩm '${maHang}' đã có trong danh sách.`);
                }
                return;
            }
            const newRow = `
                <tr data-ma-hang="${maHang}">
                    <td class="px-3 py-2 font-medium">${maHang}</td>
                    <td class="px-3 py-2">
                        <input type="number" class="production-quantity-input w-full border-gray-300 rounded-md shadow-sm text-center font-semibold" value="${soLuong}" min="1" step="1">
                    </td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" class="delete-item-btn text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>`;
            tableBody.append(newRow);
            updateTableEmptyState();
        };

        const updateTableEmptyState = () => {
             if (tableBody.find('tr').length === 0) {
                tableBody.html(`<tr><td colspan="3" class="p-4 text-center text-gray-500 italic">Chưa có sản phẩm nào.</td></tr>`);
            } else {
                 tableBody.find('td[colspan="3"]').parent().remove();
            }
        };

        if (items && items.length > 0) {
            items.forEach(item => {
                const maHienThi = (orderType === 'BTP') ? (item.MaBTP || 'N/A') : (item.MaHang || 'N/A');
                const soLuongHienThi = (orderType === 'BTP') ? (item.SoLuongCan || 0) : (item.SoLuongCanSX || 0);
                if (parseFloat(soLuongHienThi) > 0) {
                    renderRow(maHienThi, soLuongHienThi);
                }
            });
        }
        updateTableEmptyState();

        let searchTimeout;
        $('#item-search-input').on('keyup', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val();
            const resultsContainer = $('#search-results-container');

            if (query.length < 2) {
                resultsContainer.hide().empty();
                return;
            }

            searchTimeout = setTimeout(() => {
                resultsContainer.show().html('<div class="p-2 text-center text-gray-500"><i class="fas fa-spinner fa-spin"></i> Đang tìm...</div>');
                $.ajax({
                    url: 'api/search_production_items.php',
                    type: 'GET',
                    data: { query: query, type: orderType },
                    dataType: 'json',
                    success: res => {
                        resultsContainer.empty();
                        if (res.success && res.data.length > 0) {
                            res.data.forEach(item => {
                                const resultItem = `
                                    <div class="p-2 border-b hover:bg-gray-100 cursor-pointer flex justify-between items-center search-result-item" data-ma-hang="${item.variant_sku}">
                                        <span class="text-left">${item.variant_name} <br> <b class="text-blue-600">(${item.variant_sku})</b></span>
                                        <button class="add-item-btn bg-green-500 text-white px-2 py-1 text-xs rounded hover:bg-green-600">Thêm</button>
                                    </div>`;
                                resultsContainer.append(resultItem);
                            });
                        } else {
                            resultsContainer.html('<div class="p-2 text-center text-gray-500">Không tìm thấy sản phẩm.</div>');
                        }
                    },
                    error: xhr => {
                        resultsContainer.html('<div class="p-2 text-center text-red-500">Lỗi khi tìm kiếm.</div>');
                    }
                });
            }, 300);
        });

        $('#search-results-container').on('click', '.add-item-btn', function(e) {
             e.stopPropagation();
            const maHang = $(this).closest('.search-result-item').data('ma-hang');
            renderRow(maHang, 1);
            $('#item-search-input').val('').focus();
            $('#search-results-container').hide().empty();
        });
        
        $(document).on('click.searchModal', function(event) {
            if (!$(event.target).closest('#item-search-input, #search-results-container').length) {
                $('#search-results-container').hide();
            }
        });

        tableBody.on('click', '.delete-item-btn', function() {
            $(this).closest('tr').remove();
            updateTableEmptyState();
        });

        $('#confirmation-modal-lsx-ok-btn').off('click').on('click', function() {
            $(document).off('click.searchModal');
            const itemsToProduce = [];
            tableBody.find('tr').each(function() {
                const row = $(this);
                const maHang = row.data('ma-hang');
                const soLuong = parseFloat(row.find('.production-quantity-input').val());
                if (maHang && !isNaN(soLuong) && soLuong > 0) {
                    itemsToProduce.push({ maHang: maHang, soLuong: soLuong });
                }
            });
            
            if (itemsToProduce.length === 0) {
                 if(typeof showMessageModal === 'function'){
                    showMessageModal('Danh sách sản xuất rỗng. Vui lòng thêm sản phẩm hoặc hủy bỏ.', 'info');
                 } else {
                    alert('Danh sách sản xuất rỗng. Vui lòng thêm sản phẩm hoặc hủy bỏ.');
                 }
                 return;
            }

            $('#confirmation-modal-lsx').remove();
            if (onConfirm) onConfirm(itemsToProduce);
        });

        $('#confirmation-modal-lsx-cancel-btn').off('click').on('click', () => {
             $(document).off('click.searchModal');
            $('#confirmation-modal-lsx').remove();
        });
    }

    function generateRowHtml(item, index, type) {
        switch (type) {
            case 'sanxuat':
                const daGanCV = item.DaGanCV || 0;
                const khaDungCV = Math.max(0, (item.TonKhoCV || 0) - daGanCV);
                const tonKhoCV_display = item.TonKhoCV !== null 
                    ? `${window.App.formatNumber(item.TonKhoCV)} / <span class="text-yellow-600">${window.App.formatNumber(daGanCV)}</span> / <span class="font-bold text-blue-600">${window.App.formatNumber(khaDungCV)}</span>` 
                    : 'N/A';
    
                const daGanCT = item.DaGanCT || 0;
                const khaDungCT = Math.max(0, (item.TonKhoCT || 0) - daGanCT);
                const tonKhoCT_display = item.TonKhoCT !== null 
                    ? `${window.App.formatNumber(item.TonKhoCT)} / <span class="text-yellow-600">${window.App.formatNumber(daGanCT)}</span> / <span class="font-bold text-blue-600">${window.App.formatNumber(khaDungCT)}</span>` 
                    : 'N/A';
                
                const canSanXuatCay = item.CanSanXuatCay || 0;
                const maHang = item.MaHang || '';
                
                let cvCanSX = 0;
                let ctCanSX = 0;
                let displayLabel = '';
                
                if (maHang.includes('PUR-S')) {
                    cvCanSX = Math.max(0, canSanXuatCay - khaDungCV);
                    ctCanSX = Math.max(0, canSanXuatCay - khaDungCT);
                    displayLabel = 'CV/CT';
                } else if (maHang.includes('PUR-C')) {
                    const ctCanSX_total = Math.max(0, (canSanXuatCay * 2) - khaDungCT);
                    cvCanSX = 0;
                    ctCanSX = ctCanSX_total;
                    displayLabel = 'CT/CT';
                } else {
                    cvCanSX = Math.max(0, canSanXuatCay - khaDungCV);
                    ctCanSX = Math.max(0, canSanXuatCay - khaDungCT);
                    displayLabel = 'CV/CT';
                }
                
                const canSXCayDisplay = `
                    <div class="text-center">
                        <div class="mb-1">
                            <input type="number" 
                                   class="table-input font-bold text-red-600 w-20 text-center border-red-300 focus:border-red-500" 
                                   name="canSanXuatCay" 
                                   value="${canSanXuatCay}"
                                   min="0"
                                   step="1"
                                   title="Tổng số cây cần sản xuất">
                        </div>
                        <div class="text-xs text-gray-500 mb-1">${displayLabel}</div>
                        <div class="text-sm font-semibold">
                            <span class="${cvCanSX > 0 ? 'text-red-500' : 'text-green-500'}" title="CV cần sản xuất">${window.App.formatNumber(cvCanSX)}</span>
                            <span class="text-gray-400 mx-1">/</span>
                            <span class="${ctCanSX > 0 ? 'text-red-500' : 'text-green-500'}" title="CT cần sản xuất">${window.App.formatNumber(ctCanSX)}</span>
                        </div>
                    </div>
                `;
                
                return `<tr data-id="${item.ChiTietCBH_ID}">
                            <td class="p-2 border text-center">${index}</td>
                            <td class="p-2 border">${item.MaHang || ''}</td>
                            <td class="p-2 border text-center">${item.ID_ThongSo || ''}</td>
                            <td class="p-2 border text-center">${item.DoDayItem || ''}</td>
                            <td class="p-2 border text-center">${item.BanRongItem || ''}</td>
                            <td class="p-2 border font-semibold text-center">${window.App.formatNumber(item.SoLuong)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.TonKho)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.DaGan)}</td>
                            <td class="p-2 border text-red-600 font-bold text-center">${window.App.formatNumber(item.SoLuongCanSX)}</td>
                            <td class="p-2 border text-blue-600 font-bold text-center">${item.SoCayPhaiCat || '-'}</td>
                            <td class="p-2 border text-center" title="Tồn kho / Đã Gán / Khả dụng Cây Vuông">${tonKhoCV_display}</td>
                            <td class="p-2 border text-center" title="Tồn kho / Đã Gán / Khả dụng Cây Tròn">${tonKhoCT_display}</td>
                            <td class="p-2 border">${canSXCayDisplay}</td>
                            <td class="p-2 border"><input type="text" class="table-input" name="soThung" value="${item.SoThung || ''}"></td>
                            <td class="p-2 border"><input type="text" class="table-input" name="ghiChu" value="${item.GhiChu || ''}"></td>
                        </tr>`;
    
            case 'ula': 
                return `<tr data-id="${item.ChiTietCBH_ID}" 
                           data-dinh-muc-tai="${item.dinh_muc_tai || ''}" 
                           data-dinh-muc-kg="${item.dinh_muc_kg || ''}">
                            <td class="p-2 border text-center">${index}</td>
                            <td class="p-2 border">${item.MaHang || ''}</td>
                            <td class="p-2 border text-center">${item.ID_ThongSo || ''}</td>
                            <td class="p-2 border text-center">${item.DoDayItem || ''}</td>
                            <td class="p-2 border text-center">${item.BanRongItem || ''}</td>
                            <td class="p-2 border font-semibold text-center">${window.App.formatNumber(item.SoLuong)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.TonKho)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.DaGan)}</td>
                            <td class="p-2 border text-red-600 font-bold text-center">${window.App.formatNumber(item.SoLuongCanSX)}</td>
                            <td class="p-2 border"><input type="text" class="table-input" name="dongGoi" value="${item.DongGoi || ''}"></td>
                            <td class="p-2 border"><input type="text" class="table-input" name="ghiChu" value="${item.GhiChu || ''}"></td>
                        </tr>`;
    
            case 'ecu': 
                const canMuaThemEcu = Math.max(0, (item.SoLuongEcu || 0) - item.SoLuongPhanBo); 
                return `<tr data-id="${item.ChiTietEcuCBH_ID}">
                            <td class="p-2 border text-center">${index}</td>
                            <td class="p-2 border">${item.TenSanPhamEcu || ''}</td>
                            <td class="p-2 border text-center font-semibold">${window.App.formatNumber(item.SoLuongEcu)}</td>
                            <td class="p-2 border text-center text-blue-600 font-bold">${window.App.formatNumber(item.SoLuongPhanBo)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.TonKho)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.DaGan)}</td>
                            <td class="p-2 border text-center text-red-600 font-bold">${window.App.formatNumber(canMuaThemEcu)}</td>
                            <td class="p-2 border text-center">${item.SoKgEcu ? parseFloat(item.SoKgEcu).toFixed(2) : '0.00'}</td>
                            <td class="p-2 border"><input type="text" class="table-input" name="dongGoiEcu" value="${item.DongGoiEcu || ''}"></td>
                            <td class="p-2 border"><input type="text" class="table-input" name="ghiChuEcu" value="${item.GhiChuEcu || ''}"></td>
                        </tr>`;
    
            case 'btp': 
                const tonKhoKhaDungBTP = (item.TonKhoSnapshot || 0) - (item.DaGanSnapshot || 0); 
                const soLuongCanSanXuat = parseFloat(item.SoLuongCan) || 0;
                const thieu = Math.max(0, soLuongCanSanXuat - tonKhoKhaDungBTP);
                const ghiChuBTP = thieu > 0 ? `Thiếu ${window.App.formatNumber(thieu)} (TKKD: ${window.App.formatNumber(tonKhoKhaDungBTP)})` : `Đủ TKKD (${window.App.formatNumber(tonKhoKhaDungBTP)})`;
                const chenhLechClass = thieu > 0 ? 'text-red-600' : 'text-green-600'; 
                return `<tr data-id="${item.ChiTietBTP_ID}" data-ton-kho-kha-dung="${tonKhoKhaDungBTP}">
                            <td class="p-2 border text-center">${index}</td>
                            <td class="p-2 border">${item.MaBTP || ''}</td>
                            <td class="p-2 border font-bold text-center">${window.App.formatNumber(item.SoCayCat)}</td>
                            <td class="p-2 border font-bold text-center text-red-600">${window.App.formatNumber(soLuongCanSanXuat)}</td>
                            <td class="p-2 border text-center">${window.App.formatNumber(item.TonKhoSnapshot)}</td>
                            <td class="p-2 border font-semibold ${chenhLechClass} text-center">${ghiChuBTP}</td>
                        </tr>`;
            default: 
                return '';
        }
    }
    
    function renderTable(items, tbodySelector, type, sectionSelector) {
        const tableBody = $(tbodySelector);
        const section = $(sectionSelector);
        tableBody.empty();

        if (!items || !Array.isArray(items) || items.length === 0) {
            section.hide();
            return;
        }

        items.forEach((item, index) => tableBody.append(generateRowHtml(item, index + 1, type)));
        section.show();
    }

   /**
 * Hàm này tính toán thâm hụt BTP từ 'data.banThanhPham' 
 * và cập nhật lại 'data.hangSanXuat'.
 * * @param {object} data Dữ liệu JSON đầy đủ từ AJAX
 */
function tinhToanVaCapNhatThieuHutBTP(data) {
    if (!data || !data.banThanhPham || !data.hangSanXuat) {
        console.error("Dữ liệu không đủ để tính toán BTP");
        return;
    }

    // 1. Tạo một Map để tra cứu thâm hụt BTP
    // Key sẽ là MaBTP (ví dụ "CV 020x25"), Value là số thâm hụt
    const thieuHutMap = new Map();
    data.banThanhPham.forEach(btp => {
        const soLuongCan = parseFloat(btp.SoLuongCan) || 0;
        const tonKho = parseFloat(btp.TonKhoSnapshot) || 0;
        const thieuHut = Math.max(0, soLuongCan - tonKho); // Chỉ lấy số dương

        if (thieuHut > 0) {
            thieuHutMap.set(btp.MaBTP.trim(), thieuHut);
        }
    });

    if (thieuHutMap.size === 0) {
        console.log("Không có BTP nào thâm hụt.");
        // Vẫn chạy hàm kiểm tra nút, vì có thể người dùng nhập tay
        kiemTraHienThiNutSanXuatBTP(data.hangSanXuat);
        return;
    }

    // 2. Cập nhật mảng hangSanXuat
    //    Chúng ta cần liên kết 'hangSanXuat' với 'banThanhPham'.
    //    Giả sử 'MaHang' (PUR-S 020x25x50) liên kết với 'MaBTP' (CV 020x25 và CT 020x25)
    //    bằng mã "020x25".
    data.hangSanXuat.forEach(item => {
        // Trích xuất mã BTP từ MaHang, ví dụ "020x25"
        const maHangParts = item.MaHang.split(' ');
        if (maHangParts.length < 2) return; // Bỏ qua nếu định dạng MaHang không đúng
        
        // Giả sử mã có dạng "PUR-S 020x25x50". Ta lấy "020x25"
        const maBTPKey = maHangParts[1].substring(0, 6); // "020x25"
        
        const maCV = `CV ${maBTPKey}`; // "CV 020x25"
        const maCT = `CT ${maBTPKey}`; // "CT 020x25"

        // Kiểm tra xem có thâm hụt cho mã này không
        if (thieuHutMap.has(maCV)) {
            // Cập nhật giá trị CanSanXuatCV
            item.CanSanXuatCV = thieuHutMap.get(maCV).toString();
            // Cập nhật cả giao diện (DOM) nếu bảng đã được render
            // Ví dụ: $('tr[data-id="' + item.ChiTietCBH_ID + '"] .o-cansx-cv').val(item.CanSanXuatCV);
        }
        
        if (thieuHutMap.has(maCT)) {
            // Cập nhật giá trị CanSanXuatCT
            item.CanSanXuatCT = thieuHutMap.get(maCT).toString();
            // Cập nhật cả giao diện (DOM)
            // Ví dụ: $('tr[data-id="' + item.ChiTietCBH_ID + '"] .o-cansx-ct').val(item.CanSanXuatCT);
        }
    });

    // 3. Gọi hàm kiểm tra hiển thị nút
    //    Đây là hàm mà bạn dùng để ẩn/hiện nút
    kiemTraHienThiNutSanXuatBTP(data.hangSanXuat);
}

/**
 * Kiểm tra và ẩn/hiện nút tạo phiếu SX BTP
 * @param {Array} hangSanXuatData Mảng hangSanXuat đã được cập nhật
 */
function kiemTraHienThiNutSanXuatBTP(hangSanXuatData) {
    let tongCanSanXuatBTP = 0;
    hangSanXuatData.forEach(item => {
        tongCanSanXuatBTP += (parseFloat(item.CanSanXuatCV) || 0);
        tongCanSanXuatBTP += (parseFloat(item.CanSanXuatCT) || 0);
    });

    console.log("Tổng BTP cần sản xuất:", tongCanSanXuatBTP);

    // Thay '#id-nut-tao-phieu-btp' bằng ID thật của nút
    const $nutBTP = $('#id-nut-tao-phieu-btp'); 

    if (tongCanSanXuatBTP > 0) {
        $nutBTP.show();
        console.log("-> Hiển thị nút SX BTP");
    } else {
        $nutBTP.hide();
        console.log("-> Ẩn nút SX BTP");
    }
} 
function renderActionButtons(info, statusSummary, ids, data) {
    const actionButtonsContainer = $('#additional-action-buttons');
    actionButtonsContainer.empty();
    let buttonsHtml = '';
    const cbhStatus = info.TrangThai;
    const ulaStatus = info.TrangThaiULA;
    const purStatus = info.TrangThaiPUR || 'Chờ xử lý';
    const trangThaiECU = info.TrangThaiECU || 'Chờ xử lý';
    const finalStatuses = ['Hoàn thành', 'Chờ xuất kho', 'Đã xuất kho'];

    if (cbhStatus === 'Mới tạo') {
        buttonsHtml += `<p class="text-sm text-blue-700 ml-4 font-semibold"><i class="fas fa-info-circle mr-2"></i>Vui lòng phân tích phiếu để tính toán tồn kho và vật tư.</p>`;
        buttonsHtml += `<button id="process-cbh-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-blue-600 hover:bg-blue-700 text-white"><i class="fas fa-cogs"></i> Phân Tích & Chuẩn Bị Hàng</button>`;
        actionButtonsContainer.html(buttonsHtml);
        return;
    }

    if (!finalStatuses.includes(cbhStatus)) {
        // === XỬ LÝ BTP/PUR ===
        const hasBtpProduction = data.lenhSanXuatPUR && data.lenhSanXuatPUR.length > 0 && data.lenhSanXuatPUR[0].info;
        const btpProductionComplete = hasBtpProduction && data.lenhSanXuatPUR[0]?.info?.TrangThai === 'Hoàn thành';
        const needsBtpProduction = data.banThanhPham && data.banThanhPham.some(item => parseFloat(item.SoLuongCan) > 0);
        const hasItemsNeedCutting = data.hangSanXuat && data.hangSanXuat.some(item => parseInt(item.SoCayPhaiCat) > 0);
        const trangThaiNhapBTP = info.TrangThaiNhapBTP || 'Chưa nhập';
        const trangThaiXuatBTP = info.TrangThaiXuatBTP || 'Chưa xuất';
        const purInProgressStatuses = ['Chờ sản xuất', 'Đang SX', 'Chờ duyệt'];

        // Logic hiển thị nút và trạng thái cho BTP/PUR
        if (needsBtpProduction && !hasBtpProduction && !purInProgressStatuses.includes(purStatus)) {
            // Case 1: THIẾU BTP và CHƯA GỬI YÊU CẦU SX -> Hiện nút yêu cầu
            const btpItemsToProduce = data.banThanhPham.filter(item => parseFloat(item.SoLuongCan) > 0);
            const btpItemsJson = JSON.stringify(btpItemsToProduce);
            buttonsHtml += `<button id="create-btp-pur-lsx-btn" data-ycsx-id="${ids.donHangID}" data-items='${btpItemsJson}' class="action-btn bg-red-600 hover:bg-red-700 text-white"><i class="fas fa-tools"></i> Yêu Cầu SX BTP</button>`;
        } else if (purInProgressStatuses.includes(purStatus) || (hasBtpProduction && !btpProductionComplete)) {
            // Case 2: YÊU CẦU ĐÃ ĐƯỢC GỬI (dựa vào purStatus) hoặc LSX đang chạy
            const lsxInfo = hasBtpProduction ? data.lenhSanXuatPUR[0]?.info : null;
            const statusText = lsxInfo ? `LSX: ${lsxInfo.SoLenhSX} - ${lsxInfo.TrangThai}` : `Trạng thái hiện tại: ${purStatus}`;
            buttonsHtml += `
                <div class="flex items-center ml-4 px-4 py-2 bg-blue-50 border border-blue-300 rounded-md">
                    <i class="fas fa-clock text-blue-600 mr-2"></i>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-blue-700">Đang chờ sản xuất BTP hoàn thành...</span>
                        <span class="text-xs text-blue-600 mt-1">${statusText}</span>
                    </div>
                </div>
                <button id="refresh-status-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-gray-500 hover:bg-gray-600 text-white ml-2">
                    <i class="fas fa-sync-alt"></i> Kiểm Tra Trạng Thái
                </button>`;
        }
        
        // Nút "Tạo PXK BTP (Cây Cắt)" khi đủ BTP
        // SỬA ĐỔI V23.3: Cho phép hiển thị khi trạng thái là 'Chờ xuất'
        if (!needsBtpProduction && hasItemsNeedCutting && (trangThaiXuatBTP === 'Chưa xuất' || trangThaiXuatBTP === 'Chờ xuất') && cbhStatus === 'Đã chuẩn bị') {
             buttonsHtml += `<button id="create-pxk-caycat-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-yellow-500 hover:bg-yellow-600 text-white"><i class="fas fa-cut"></i> Tạo PXK BTP (Cây Cắt)</button>`;
        }

        // Nút nhập kho BTP sau khi sản xuất xong
        if (hasBtpProduction && btpProductionComplete) {
            const lsxInfo = data.lenhSanXuatPUR[0]?.info;
            const trangThaiCBH = lsxInfo.TrangThaiChuanBiHang || '';
            if (trangThaiCBH === 'Đã SX xong') {
                buttonsHtml += `<button id="request-nhapkho-btp-btn" data-cbh-id="${ids.cbhId}" data-loai-lsx="BTP" class="action-btn bg-purple-600 hover:bg-purple-700 text-white"><i class="fas fa-bell"></i> Yêu Cầu Nhập Kho BTP</button>`;
            } else if (trangThaiCBH === 'Chờ Nhập Kho BTP' || trangThaiNhapBTP === 'Chờ nhập') {
                buttonsHtml += `<button id="create-receive-btp-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-indigo-600 hover:bg-indigo-700 text-white"><i class="fas fa-dolly-flatbed"></i> Nhập Kho BTP</button>`;
            }
        }
        
        // Nút xuất BTP đi cắt sau khi đã nhập kho BTP từ SX
        if (hasItemsNeedCutting && trangThaiNhapBTP === 'Đã nhập' && trangThaiXuatBTP === 'Chưa xuất') {
            buttonsHtml += `<button id="create-pxk-caycat-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-yellow-500 hover:bg-yellow-600 text-white"><i class="fas fa-cut"></i> Tạo PXK BTP (Cây Cắt)</button>`; 
        }
        
        // Nút nhập thành phẩm PUR sau khi đã xuất BTP đi cắt
        if (trangThaiXuatBTP === 'Đã xuất' && info.TrangThaiNhapTP_PUR === 'Chưa nhập') { 
            buttonsHtml += `<button id="create-receive-pur-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-purple-600 hover:bg-purple-700 text-white"><i class="fas fa-box-open"></i> Nhập Thành Phẩm PUR</button>`; 
        }
        
        // === XỬ LÝ ULA ===
        const hasUlaProduction = data.lenhSanXuatULA && data.lenhSanXuatULA.length > 0;
        
        if (ulaStatus === 'Cần nhập' && hasUlaProduction) {
            const ulaItemsJson = JSON.stringify(data.lenhSanXuatULA);
            buttonsHtml += `<button id="create-ula-lsx-btn" data-cbh-id="${ids.cbhId}" data-ycsx-id="${ids.donHangID}" data-items='${ulaItemsJson}' class="action-btn bg-blue-600 hover:bg-blue-700 text-white"><i class="fas fa-industry"></i> Yêu Cầu SX ULA</button>`;
        }
        
        if (ulaStatus === 'Chờ nhập ULA' || ulaStatus === 'Chờ nhập') {
            buttonsHtml += `<button id="create-receive-ula-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-green-600 hover:bg-green-700 text-white"><i class="fas fa-box-open"></i> Nhập Thành Phẩm ULA</button>`;
        } else if (ulaStatus === 'Đã SX xong ULA') {
            buttonsHtml += `<button id="request-nhapkho-ula-btn" data-cbh-id="${ids.cbhId}" data-loai-lsx="ULA" class="action-btn bg-purple-600 hover:bg-purple-700 text-white"><i class="fas fa-bell"></i> Yêu Cầu Nhập Kho ULA</button>`;
        } else if (ulaStatus === 'Đang SX' || ulaStatus === 'Chờ duyệt') {
            buttonsHtml += `
                <div class="flex items-center ml-4 px-4 py-2 bg-blue-50 border border-blue-300 rounded-md">
                    <i class="fas fa-clock text-blue-600 mr-2"></i>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-blue-700">Đang chờ sản xuất ULA hoàn thành...</span>
                        <span class="text-xs text-blue-600 mt-1">Trạng thái: ${ulaStatus}</span>
                    </div>
                </div>`;
        }
        
        // === ECU ===
        if (statusSummary.ecuStatus === 'INSUFFICIENT' && trangThaiECU !== 'Đã nhập kho VT') {
            buttonsHtml += `<button id="import-missing-ecu-btn" data-cbh-id="${ids.cbhId}" class="action-btn bg-purple-500 hover:bg-purple-600 text-white"><i class="fas fa-truck-loading"></i> Nhập Kho Vật Tư (ECU)</button>`;
        }
    }
    
    actionButtonsContainer.html(buttonsHtml);
}
    
    function populateForm(data) {
        if (!data.info || !data.statusSummary) {
            mainContentContainer.html(`<div class="text-center p-8 text-red-500"><strong>Lỗi Dữ Liệu!</strong><br>Phản hồi từ API không chứa thông tin hoặc tóm tắt trạng thái cần thiết.</div>`);
            return;
        }
        const { info, hangSanXuat, hangChuanBi_ULA, hangDeoTreo, vatTuKem_ECU, banThanhPham, lenhSanXuatULA, lenhSanXuatPUR, statusSummary } = data;
        document.title = `CBH - ${info.SoYCSX || info.SoCBH || cbhId}`;
        
        $('#info-bophan').val(info.BoPhan || 'Kho - Logistic');
        $('#info-ngaygui').val(formatDateOnly(info.NgayGuiYCSX || info.NgayTao));
        $('#info-phutrach').val(info.PhuTrach || info.NguoiBaoGia);
        $('#info-ngaygiao').val(formatDateOnly(info.NgayGiao || info.NgayGiaoDuKien));
        $('#info-nguoinhan').val(info.NguoiNhanHang || info.NguoiNhanBaoGia || info.TenCongTyBaoGia);
        $('#info-sdtnguoinhan').val(info.SdtNguoiNhan || '');
        $('#info-congtrinh').val(info.DangKiCongTruong || info.TenDuAn);
        $('#info-sodon').val(info.SoDon || info.SoYCSX);
        $('#info-diadiem').val(info.DiaDiemGiaoHang || info.DiaChiGiaoHangBaoGia);
        $('#info-madon').val(info.DonHangID);
        $('#info-quycachthung').val(info.QuyCachThung || '');
        $('#info-xegrap').val(info.XeGrap || '');
        $('#info-xetai').val(info.XeTai || '');
        $('#info-solaixe').val(info.SoLaiXe || '');
        
        // --- HIỂN THỊ CÁC TRẠNG THÁI CHI TIẾT ---
        const createStatusHtml = (label, status) => {
            if (!status) return '';
            return `<div class="flex justify-between items-center py-1">
                        <span class="text-sm font-medium text-gray-600">${label}:</span>
                        <span class="font-semibold px-2 py-1 text-xs rounded-full ${getStatusClass(status)}">${status}</span>
                    </div>`;
        };
        
        $('#slip-status-container').html(createStatusHtml('Trạng thái Phiếu', info.TrangThai || 'Mới tạo'));
        $('#pur-status-container').html(createStatusHtml('Sản phẩm PUR', info.TrangThaiPUR || 'Chờ xử lý'));
        $('#ula-status-container').html(createStatusHtml('Sản phẩm ULA', info.TrangThaiULA || 'Chờ xử lý'));
        $('#dai-treo-status-container').html(createStatusHtml('Sản phẩm Đai Treo', info.TrangThaiDaiTreo || 'Chờ xử lý'));
        $('#ecu-status-container').html(createStatusHtml('Vật tư ECU', info.TrangThaiECU || 'Chờ xử lý'));

        $('#nhap-btp-status-container').html(createStatusHtml('Nhập BTP từ SX', info.TrangThaiNhapBTP || 'Chưa nhập'));
        $('#xuat-btp-status-container').html(createStatusHtml('Xuất BTP đi cắt', info.TrangThaiXuatBTP || 'Chưa xuất'));
        $('#nhap-tp-pur-status-container').html(createStatusHtml('Nhập TP PUR', info.TrangThaiNhapTP_PUR || 'Chưa nhập'));
        $('#nhap-tp-ula-status-container').html(createStatusHtml('Nhập TP ULA', info.TrangThaiNhapTP_ULA || 'Chưa nhập'));
        // --- KẾT THÚC HIỂN THỊ TRẠNG THÁI ---
        
        $('#slip-created-at').text(formatDateOnly(info.NgayTao));
        $('#slip-updated-at').text(formatDateTime(info.updated_at));
        
        renderTable(hangSanXuat, '#pur-table-body', 'sanxuat', '#pur-section');
        renderTable(hangChuanBi_ULA, '#ula-table-body', 'ula', '#ula-section');
        renderTable(hangDeoTreo, '#deo-treo-table-body', 'ula', '#deo-treo-section');
        renderTable(vatTuKem_ECU, '#ecu-table-body', 'ecu', '#ecu-section');
        renderTable(banThanhPham, '#btp-table-body', 'btp', '#btp-section');

        calculateAndUpdateTotals();

        const ulaLsxContainer = $('#ula-lsx-container');
        if (lenhSanXuatULA && lenhSanXuatULA.length > 0 && lenhSanXuatULA[0].info) {
            ulaLsxContainer.html(createDetailedViewHtml({info: lenhSanXuatULA[0].info, items: lenhSanXuatULA[0].items})).show();
        } else { ulaLsxContainer.hide(); }

        const btpLsxContainer = $('#btp-lsx-container');
        if (lenhSanXuatPUR && lenhSanXuatPUR.length > 0 && lenhSanXuatPUR[0].info) {
            btpLsxContainer.html(createDetailedViewHtml({info: lenhSanXuatPUR[0].info, items: lenhSanXuatPUR[0].items})).show();
        } else { btpLsxContainer.hide(); }
        
        const buttonContainer = $('#save-chuanbi-btn').parent();
        if (buttonContainer.length) {
            const currentCbhStatus = info.TrangThai || 'Mới tạo';
            buttonContainer.find('.export-btn, .final-export-group, .save-note, .completed-note, .auto-note-btn').remove(); 
            
            if (currentCbhStatus === 'Mới tạo' || currentCbhStatus === 'Đã chuẩn bị') {
                $('#save-chuanbi-btn').after(`<span class="save-note text-sm text-gray-500 italic ml-2 self-center">Lưu lại thay đổi trước khi thực hiện hành động khác.</span>`);
            }

            if (currentCbhStatus !== 'Hoàn thành' && currentCbhStatus !== 'Chờ xuất kho' && currentCbhStatus !== 'Đã xuất kho') {
                $('#save-chuanbi-btn').before(`
                    <button id="auto-calculate-notes-btn" type="button" class="auto-note-btn px-4 py-2 bg-purple-600 text-white rounded-md shadow-md hover:bg-purple-700 transition-colors flex items-center">
                        <i class="fas fa-calculator mr-2"></i>Tính Ghi Chú
                    </button>
                `);
            }

            const pdfUrl = `api/export_chuanbihang_pdf.php?id=${cbhId}`;
            const excelUrl = `api/export_chuanbihang_excel.php?id=${cbhId}`;
            buttonContainer.append(`<a href="${pdfUrl}" target="_blank" class="export-btn action-btn bg-red-500 hover:bg-red-600 text-white ml-2"><i class="fas fa-file-pdf"></i> PDF</a><a href="${excelUrl}" target="_blank" class="export-btn action-btn bg-green-500 hover:bg-green-600 text-white ml-2"><i class="fas fa-file-excel"></i> Excel</a>`);
            
            const exportFinalStatuses = ['Hoàn thành', 'Chờ xuất kho', 'Đã xuất kho', 'Đã giao hàng', 'Hủy'];
            if (exportFinalStatuses.includes(currentCbhStatus)) {
                buttonContainer.append(`<div class="completed-note flex items-center ml-4 pl-4 border-l border-gray-300"><div class="flex items-center p-2 rounded-md bg-green-50 border border-green-200"><i class="fas fa-check-circle text-green-600 mr-2"></i><span class="text-sm font-semibold text-green-700">Quy trình đã hoàn tất.</span></div></div>`);
            } else {

                const areAllProcessesComplete = (info, data) => {
                    const completedStatuses = ['Hoàn thành', 'Đã nhập', 'Đã xuất', 'Không cần', 'Đã nhập kho VT', 'Đủ hàng', 'Đã giao hàng'];
                    const isCompleted = (status) => completedStatuses.includes(status);

                    const purNeedsProduction = data.hangSanXuat && data.hangSanXuat.some(item => item.SoLuongCanSX > 0);
                    if (purNeedsProduction) {
                        if (!isCompleted(info.TrangThaiNhapBTP) || !isCompleted(info.TrangThaiXuatBTP) || !isCompleted(info.TrangThaiNhapTP_PUR)) {
                            return false;
                        }
                    }
                    
                    const ulaNeedsProduction = data.hangChuanBi_ULA && data.hangChuanBi_ULA.some(item => item.SoLuongCanSX > 0);
                    if (ulaNeedsProduction) {
                        if (!isCompleted(info.TrangThaiNhapTP_ULA)) {
                            return false;
                        }
                    }
                    
                    const daiTreoNeedsProduction = data.hangDeoTreo && data.hangDeoTreo.some(item => item.SoLuongCanSX > 0);
                    if(daiTreoNeedsProduction){
                         if (!['Đủ hàng', 'Hoàn thành', 'Không cần'].includes(info.TrangThaiDaiTreo)) {
                            return false;
                        }
                    }
                    
                    const ecuNeedsPurchase = data.vatTuKem_ECU && data.vatTuKem_ECU.some(item => (item.SoLuongEcu - (item.SoLuongPhanBo || 0)) > 0);
                    if (ecuNeedsPurchase) {
                        if (!isCompleted(info.TrangThaiECU)) {
                            return false;
                        }
                    }
                    return true;
                };

                const allStepsCompleted = areAllProcessesComplete(info, data);
                const disabledAttribute = allStepsCompleted ? '' : 'disabled title="Chưa hoàn thành tất cả các bước trong quy trình. Không thể xuất kho."';
                const disabledClasses = allStepsCompleted ? '' : 'opacity-50 cursor-not-allowed';

                let exportButtonsHtml = `
                    <div class="final-export-group flex items-center ml-4 pl-4 border-l border-gray-300">
                        <div class="flex flex-col items-start gap-y-2">
                            <button id="create-pxk-final-btn" data-cbh-id="${cbhId}" class="action-btn bg-teal-600 hover:bg-teal-700 text-white ${disabledClasses}" ${disabledAttribute}>
                                <i class="fas fa-shipping-fast"></i> Tạo Phiếu Xuất Kho Tổng
                            </button>
                            <button id="create-pxk-emergency-btn" data-cbh-id="${cbhId}" class="action-btn bg-orange-500 hover:bg-orange-600 text-white">
                                <i class="fas fa-exclamation-triangle"></i> Xuất Khẩn Cấp
                            </button>
                        </div>
                    </div>`;
                buttonContainer.append(exportButtonsHtml);
            }
        }
        
        const currentCbhStatus = info.TrangThai || 'Mới tạo';
        if (currentCbhStatus !== 'Mới tạo' && currentCbhStatus !== 'Đã chuẩn bị') {
            $('#pur-table-body input, #ula-table-body input, #ecu-table-body input, .info-table input').prop('disabled', true).addClass('bg-gray-100 cursor-not-allowed');
            $('#save-chuanbi-btn').prop('disabled', true).removeClass('bg-blue-600 hover:bg-blue-700').addClass('bg-gray-400 hover:bg-gray-400 cursor-not-allowed').html('<i class="fas fa-lock mr-2"></i> Đã khóa');
        }
        if (mode === 'view') {
            $('#main-form-title').after(`<div class="mb-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded-md no-print"><i class="fas fa-eye mr-2"></i><b>Chế độ chỉ xem:</b> Mọi chức năng chỉnh sửa và hành động đã được vô hiệu hóa.</div>`);
            $('.info-table input, table input, table select').prop('disabled', true).addClass('bg-gray-100 cursor-not-allowed');
            $('#save-chuanbi-btn, .update-details-btn, #additional-action-buttons, .final-export-group, .save-note').hide();
        }
        
        renderActionButtons(info, statusSummary, { donHangID: info.DonHangID, cbhId: cbhId }, data);
    }
    
  function loadData() {
    mainContentContainer.html('<div class="flex justify-center items-center h-64"><i class="fas fa-spinner fa-spin fa-3x text-blue-500"></i></div>');
    $.getJSON(`api/get_chuanbihang_details.php?id=${cbhId}`).done(response => {
       
        if (response.success && response.data) {
             tinhToanVaCapNhatThieuHutBTP(response.data);
             
             
              if (typeof initGiaCongMaNhungNong === 'function') {
                initGiaCongMaNhungNong(cbhId);
                
                // Render danh sách gia công
                // === SỬA LỖI: Thay 'data' bằng 'response.data' ===
                if (response.data.danhSachGiaCongMaNhungNong && 
                    response.data.danhSachGiaCongMaNhungNong.length > 0) {
                    renderDanhSachGiaCong(response.data.danhSachGiaCongMaNhungNong);
                }
            }
            if ($('#original-main-content').length === 0) {
                $('body').append('<div id="original-main-content" style="display: none;"></div>');
                $('#original-main-content').html(mainContentContainer.html());
            }
            mainContentContainer.html($('#original-main-content').html()); 
            populateForm(response.data); 
        } else {
            mainContentContainer.html(`<div class="text-center p-8 text-red-500">${response.message || 'Không thể tải dữ liệu.'}</div>`);
        }
    }).fail(xhr => {
        const errorMessage = handleAjaxError(xhr, 'Không thể tải dữ liệu chi tiết của phiếu.');
        mainContentContainer.html(`<div class="text-center p-8 text-red-500">${errorMessage}</div>`);
    });
}

    function setupEventListeners() {
        mainContentContainer.off('.production');
        
        const protectedActionIds = [
            'process-cbh-btn', 'create-btp-pur-lsx-btn', 'create-ula-lsx-btn',
            'create-receive-btp-btn', 'create-receive-pur-btn',
            'create-receive-ula-btn', 'import-missing-ecu-btn'
        ];

        mainContentContainer.on('click.production', '.action-btn', function(e) {
            const btn = $(this);
            if (btn.is('a') || btn.attr('id') === 'create-pxk-final-btn' || btn.attr('id') === 'create-pxk-emergency-btn' || btn.attr('id') === 'create-pxk-caycat-btn') { return; }
            e.preventDefault();

            if (window.formChanged && protectedActionIds.includes(btn.attr('id'))) {
                showMessageModal('Vui lòng lưu thay đổi trước khi thực hiện hành động này.', 'info');
                return;
            }

            const originalHtml = btn.html();
            const id = btn.data('cbh-id') || cbhId;
            const ycsxId = btn.data('ycsx-id');
            
            if (btn.attr('id') === 'create-btp-pur-lsx-btn' || btn.attr('id') === 'create-ula-lsx-btn') {
                const orderType = (btn.attr('id') === 'create-btp-pur-lsx-btn') ? 'BTP' : 'ULA';
                let itemsToSuggest = [];
                const itemsJsonString = btn.attr('data-items');

                if (itemsJsonString) {
                    try {
                        itemsToSuggest = JSON.parse(itemsJsonString);
                    } catch (err) {
                        console.error("Lỗi phân tích JSON từ data-items:", err);
                        showMessageModal('Lỗi dữ liệu sản phẩm trên nút. Không thể hiển thị.', 'error');
                        return;
                    }
                }
                
                showConfirmProductionModal(itemsToSuggest, orderType, (finalItemsToProduce) => {
                    if (finalItemsToProduce.length === 0) {
                        showMessageModal('Không có sản phẩm nào để tạo lệnh sản xuất.', 'info');
                        return;
                    }

                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
                    
                    const postData = { 
                        cbh_id: id, 
                        loai_lsx: orderType,
                        items: finalItemsToProduce
                    };

                    $.ajax({
                        url: 'api/create_production_order.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(postData),
                        dataType: 'json',
                        success: (res) => {
                            if (res.success) {
                                showMessageModal(res.message, 'success');
                                loadData();
                            } else {
                                showMessageModal(res.message || 'Thất bại.', 'error');
                                btn.prop('disabled', false).html(originalHtml);
                            }
                        },
                        error: (xhr) => { 
                            const errorMessage = handleAjaxError(xhr);
                            showMessageModal(errorMessage, 'error'); 
                            btn.prop('disabled', false).html(originalHtml); 
                        }
                    });
                });
                return;
            }

            let apiUrl = '';
            let postData = {};
            let confirmMessage = '';
            switch (btn.attr('id')) {
                case 'process-cbh-btn':
                    apiUrl = 'api/process_cbh_details_CBH.php';
                    postData = { cbh_id: id };
                    confirmMessage = 'Xác nhận Phân Tích & Chuẩn Bị Hàng? Hành động này sẽ tính toán lại tồn kho và cập nhật thông tin.';
                    break;
                case 'create-receive-ula-btn': history.pushState(null, '', `?page=nhapkho_tp_create&cbh_id=${id}&type=ula`); window.App.handleRouting(); return;
                case 'create-receive-btp-btn': history.pushState(null, '', `?page=nhapkho_btp_create&cbh_id=${id}`); window.App.handleRouting(); return;
                case 'create-receive-pur-btn': history.pushState(null, '', `?page=nhapkho_tp_create&cbh_id=${id}&type=pur`); window.App.handleRouting(); return;
                case 'import-missing-ecu-btn':
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lấy dữ liệu...');
                    $.getJSON(`api/get_ecu_to_purchase.php?cbh_id=${id}`)
                        .done(function(response) {
                            if (response.success && response.data && response.data.length > 0) {
                                sessionStorage.setItem('pendingEcuImportData', JSON.stringify(response.data));
                                history.pushState(null, '', `?page=nhapkho_vattu_create&cbh_id=${id}&source=cbh`);
                                window.App.handleRouting();
                            } else { showMessageModal(response.message || 'Không có vật tư nào cần nhập thêm.', 'info'); }
                        })
                        .fail((xhr) => {
                            const errorMessage = handleAjaxError(xhr, 'Lỗi khi lấy dữ liệu vật tư cần mua.');
                            showMessageModal(errorMessage, 'error');
                        })
                        .always(() => btn.prop('disabled', false).html(originalHtml));
                    return;
                default: return;
            }

            if(typeof showConfirmationModal !== 'function') {
                 if(confirm(confirmMessage)){
                 } else {
                     return;
                 }
            }

            showConfirmationModal(confirmMessage, () => {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
                $.ajax({
                    url: apiUrl, type: 'POST', data: postData, dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            showMessageModal(res.message, 'success');
                            loadData();
                        } else {
                            showMessageModal(res.message || 'Thao tác thất bại.', 'error');
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) { 
                        const errorMessage = handleAjaxError(xhr);
                        showMessageModal(errorMessage, 'error'); 
                        btn.prop('disabled', false).html(originalHtml); 
                    }
                });
            });
        });

        mainContentContainer.on('click.production', '#create-pxk-caycat-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang tải...');
            const id = btn.data('cbh-id') || cbhId;

            $.getJSON(`api/get_btp_cutting_preview.php?cbh_id=${id}`)
                .done(function(response) {
                    if (response.success) {
                        showBtpCuttingConfirmationModal(response.data, id);
                    } else {
                        showMessageModal(response.message || 'Không thể lấy dữ liệu xem trước.', 'error');
                    }
                })
                .fail(function(xhr) {
                    const errorMessage = handleAjaxError(xhr, 'Lỗi khi tải dữ liệu BTP để xuất.');
                    showMessageModal(errorMessage, 'error');
                })
                .always(function() {
                    btn.prop('disabled', false).html(originalHtml);
                });
        });

// Thêm vào setupEventListeners()
mainContentContainer.on('click', '#refresh-status-btn', function(e) {
    e.preventDefault();
    const btn = $(this);
    const originalHtml = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...');
    
    loadData(); // Tải lại dữ liệu
    
    setTimeout(() => {
        btn.prop('disabled', false).html(originalHtml);
        showMessageModal('Đã cập nhật trạng thái mới nhất!', 'success');
    }, 2000);
});
        mainContentContainer.on('click.production', '#create-pxk-final-btn, #create-pxk-emergency-btn', function(e) {
            e.preventDefault();
            const btn = $(this);

            if (btn.is(':disabled')) {
                const reason = btn.attr('title') || 'Quy trình chuẩn bị hàng chưa hoàn tất.';
                showMessageModal(reason, 'info');
                return;
            }

            const isEmergency = btn.attr('id') === 'create-pxk-emergency-btn';
            
            const confirmMessage = isEmergency 
                ? 'Bạn có chắc muốn XUẤT KHO KHẨN CẤP? Hành động này sẽ bỏ qua các bước kiểm tra và tạo phiếu xuất kho tổng ngay lập tức.'
                : 'Hành động này sẽ tạo phiếu xuất kho tổng. Bạn chắc chắn muốn tiếp tục?';

            const originalHtml = btn.html();
            const cbhId = btn.data('cbh-id');

            showConfirmationModal(confirmMessage, () => {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
                $.ajax({
                    url: 'api/create_pxk_final.php',
                    type: 'POST',
                    data: { cbh_id: cbhId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            showMessageModal(res.message, 'success');
                            loadData();
                        } else {
                            showMessageModal(res.message || 'Tạo phiếu xuất kho thất bại.', 'error');
                            btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) { 
                        const errorMessage = handleAjaxError(xhr, 'Lỗi khi tạo phiếu xuất kho.');
                        showMessageModal(errorMessage, 'error'); 
                        btn.prop('disabled', false).html(originalHtml); 
                    }
                });
            });
        });
        // === [MỚI] XỬ LÝ NÚT KIỂM TRA TRẠNG THÁI LSX ===
mainContentContainer.on('click', '.refresh-status-btn', function(e) {
    e.preventDefault();
    const btn = $(this);
    const cbhId = btn.data('cbh-id');
    const originalHtml = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.getJSON(`api/check_lsx_completion_status.php?cbh_id=${cbhId}`)
        .done(function(response) {
            if (response.success) {
                const btpCompleted = response.btpCompleted;
                const ulaCompleted = response.ulaCompleted;
                
                let message = '<div class="text-left"><strong>Trạng thái Lệnh Sản Xuất:</strong><ul class="mt-2 space-y-1">';
                
                if (response.details.btp && response.details.btp.exists !== false) {
                    const btpStatus = response.details.btp.TrangThai;
                    const btpIcon = btpCompleted ? '✅' : '⏳';
                    message += `<li>${btpIcon} <b>BTP:</b> ${response.details.btp.SoLenhSX} - ${btpStatus}</li>`;
                }
                
                if (response.details.ula && response.details.ula.exists !== false) {
                    const ulaStatus = response.details.ula.TrangThai;
                    const ulaIcon = ulaCompleted ? '✅' : '⏳';
                    message += `<li>${ulaIcon} <b>ULA:</b> ${response.details.ula.SoLenhSX} - ${ulaStatus}</li>`;
                }
                
                message += '</ul></div>';
                
                if (btpCompleted || ulaCompleted) {
                    message += '<p class="mt-3 text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Có lệnh đã hoàn thành! Đang tải lại trang...</p>';
                    showMessageModal(message, 'success');
                    setTimeout(() => loadData(), 1500);
                } else {
                    message += '<p class="mt-3 text-yellow-600 italic text-sm">Chưa có lệnh nào hoàn thành. Vui lòng kiểm tra lại sau.</p>';
                    showMessageModal(message, 'info');
                }
            } else {
                showMessageModal('Không thể kiểm tra trạng thái: ' + (response.message || 'Lỗi không xác định'), 'error');
            }
        })
        .fail(function(xhr) {
            const errorMessage = handleAjaxError(xhr, 'Lỗi khi kiểm tra trạng thái LSX.');
            showMessageModal(errorMessage, 'error');
        })
        .always(function() {
            btn.prop('disabled', false).html(originalHtml);
        });
});
        mainContentContainer.on('click', '#auto-calculate-notes-btn', function(e) {
             e.preventDefault();
            calculateAndFillNotes();
        });

        mainContentContainer.on('click', '#save-chuanbi-btn', function(e) {
            e.preventDefault();
            
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            
            const thongTinChung = {
                boPhan: $('#info-bophan').val(),
                ngayGui: $('#info-ngaygui').val(),
                phuTrach: $('#info-phutrach').val(),
                ngayGiao: $('#info-ngaygiao').val(),
                nguoiNhan: $('#info-nguoinhan').val(),
                sdtNguoiNhan: $('#info-sdtnguoinhan').val() || null,
                congTrinh: $('#info-congtrinh').val(),
                soDon: $('#info-sodon').val(),
                diaDiem: $('#info-diadiem').val(),
                maDon: $('#info-madon').val(),
                quyCachThung: $('#info-quycachthung').val() || null,
                xeGrap: $('#info-xegrap').val() || null,
                xeTai: $('#info-xetai').val() || null,
                soLaiXe: $('#info-solaixe').val() || null
            };
            
            const items = [];
            $('#pur-table-body tr[data-id]').each(function(index) {
                const row = $(this);
                const canSanXuatCayInput = row.find('input[name="canSanXuatCay"]');
                
                const newValue = parseInt(canSanXuatCayInput.val());
                const finalValue = isNaN(newValue) ? 0 : Math.max(0, newValue);

                const cvCanSXText = row.find('td:eq(12) .font-semibold span:first').text();
                const ctCanSXText = row.find('td:eq(12) .font-semibold span:last').text();

                const cvCanSX = parseInt(cvCanSXText.replace(/,/g, ''), 10) || 0;
                const ctCanSX = parseInt(ctCanSXText.replace(/,/g, ''), 10) || 0;

                items.push({
                    chiTietCBH_ID: row.data('id'),
                    soThung: row.find('input[name="soThung"]').val() || null,
                    ghiChu: row.find('input[name="ghiChu"]').val() || null,
                    canSanXuatCay: finalValue,
                    canSanXuatCV: cvCanSX,
                    canSanXuatCT: ctCanSX
                });
            });

            $('#ula-table-body tr[data-id], #deo-treo-table-body tr[data-id]').each(function() {
                const row = $(this);
                items.push({
                    chiTietCBH_ID: row.data('id'),
                    dongGoi: row.find('input[name="dongGoi"]').val() || null,
                    ghiChu: row.find('input[name="ghiChu"]').val() || null
                });
            });
            
            const itemsEcuKemTheo = [];
            $('#ecu-table-body tr[data-id]').each(function() {
                const row = $(this);
                itemsEcuKemTheo.push({
                    chiTietEcuCBH_ID: row.data('id'),
                    dongGoiEcu: row.find('input[name="dongGoiEcu"]').val() || null,
                    ghiChuEcu: row.find('input[name="ghiChuEcu"]').val() || null
                });
            });
            
            const dataToSend = {
                cbhID: cbhId,
                thongTinChung: thongTinChung,
                items: items,
                itemsEcuKemTheo: itemsEcuKemTheo
            };
            
            $.ajax({
                url: 'api/save_phieu_chuan_bi.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(dataToSend),
                dataType: 'json',
                success: function(response) {
                    if (response.success) { 
                        showMessageModal('Lưu phiếu thành công!', 'success');
                        resetFormChangedState();
                        loadData(); 
                    } else { 
                        let errorMessage = response.message || 'Lỗi không xác định khi lưu phiếu.';
                        if (response.warnings && response.warnings.length > 0) {
                            errorMessage += '<br><br><strong>Cảnh báo từ hệ thống:</strong><ul class="text-left list-disc pl-5 mt-2">';
                            response.warnings.forEach(warn => {
                                errorMessage += `<li>${warn}</li>`;
                            });
                            errorMessage += '</ul>';
                        }
                        showMessageModal(errorMessage, 'error'); 
                    }
                },
                error: function(xhr) { 
                    const errorMessage = handleAjaxError(xhr, 'Lỗi nghiêm trọng khi lưu phiếu.');
                    showMessageModal(errorMessage, 'error'); 
                },
                complete: function() { 
                    btn.prop('disabled', false).html(originalHtml); 
                }
            });
        });

        mainContentContainer.on('input change', 'input[name="canSanXuatCay"]', function() {
            const input = $(this);
            let value = parseInt(input.val());

            if (isNaN(value) || value < 0) {
                input.val(0);
                value = 0;
            }
            
            const row = input.closest('tr');
            
            input.addClass('changed');
            setTimeout(() => input.removeClass('changed'), 500);
            
            updateCvCtDisplay(row, value);
            
            markFormAsChanged();
        });

        mainContentContainer.on('input.production change.production', '.info-table input, input[name="soThung"], input[name="ghiChu"], input[name="dongGoi"], input[name="dongGoiEcu"], input[name="ghiChuEcu"]', function() {
            markFormAsChanged();
        });
        
        mainContentContainer.on('input.totals', '#pur-table-body input, #ula-table-body input, #deo-treo-table-body input', function() {
             calculateAndUpdateTotals();
        });
    }

    // Khởi chạy
    if ($('#original-main-content').length === 0) {
        $('body').append('<div id="original-main-content" style="display: none;"></div>');
        $('#original-main-content').html(mainContentContainer.html());
    }
    loadData();
    setupEventListeners();
}

/**
 * File: ma_nhung_nong_handler.js
 * Version: 2.0
 * Description: Xử lý UI cho quy trình gia công mạ nhúng nóng
 * 
 * QUY TRÌNH:
 * 1. Kiểm tra trạng thái (check_status)
 * 2a. Nếu đủ hàng → Xuất đi gia công (export_for_processing)
 * 2b. Nếu không đủ → Tạo yêu cầu SX (create_production_request)
 * 3. Sau khi gia công xong → Nhập kho (import_after_processing)
 */

class MaNhungNongHandler {
    constructor() {
        this.currentCbhId = null;
        this.currentChiTietId = null;
        this.statusData = null;
    }

    /**
     * Khởi tạo handler
     */
    init(cbhId) {
        this.currentCbhId = cbhId;
        this.attachEventListeners();
    }

    /**
     * Gắn các event listener
     */
    attachEventListeners() {
        // Event cho nút kiểm tra trạng thái trong bảng ULA
        $(document).on('click', '.btn-check-mnn-status', (e) => {
            e.preventDefault();
            const btn = $(e.currentTarget);
            const chiTietId = btn.data('chitiet-id');
            this.checkStatus(chiTietId);
        });

        // Event cho các nút action trong modal
        $(document).on('click', '#btn-create-production', (e) => {
            e.preventDefault();
            this.createProductionRequest();
        });

        $(document).on('click', '#btn-export-processing', (e) => {
            e.preventDefault();
            this.exportForProcessing();
        });

        $(document).on('click', '#btn-import-after-processing', (e) => {
            e.preventDefault();
            this.importAfterProcessing();
        });

        // Event đóng modal
        $(document).on('click', '#mnn-modal-close, #mnn-modal-cancel', (e) => {
            e.preventDefault();
            this.closeModal();
        });
    }

    /**
     * BƯỚC 1: Kiểm tra trạng thái
     */
    checkStatus(chiTietId) {
        this.currentChiTietId = chiTietId;

        $.ajax({
            url: 'api/process_gia_cong_ma_nhung_nong_v2.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'check_status',
                cbh_id: this.currentCbhId,
                chi_tiet_cbh_id: chiTietId
            }),
            beforeSend: () => {
                this.showLoadingModal();
            },
            success: (response) => {
                if (response.success) {
                    this.statusData = response.data;
                    this.showStatusModal(response.data);
                } else {
                    this.showErrorModal(response.message);
                }
            },
            error: (xhr) => {
                const errorMsg = this.handleAjaxError(xhr);
                this.showErrorModal(errorMsg);
            }
        });
    }

    /**
     * BƯỚC 2A: Tạo yêu cầu sản xuất
     */
    createProductionRequest() {
        if (!this.statusData) return;

        const soLuongCanSX = this.statusData.san_pham_nhung_nong.so_luong_can;

        $.ajax({
            url: 'api/process_gia_cong_ma_nhung_nong_v2.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'create_production_request',
                cbh_id: this.currentCbhId,
                chi_tiet_cbh_id: this.currentChiTietId,
                so_luong_can_sx: soLuongCanSX,
                nguoi_tao: currentUser || 'Admin'
            }),
            beforeSend: () => {
                this.showLoadingModal('Đang tạo yêu cầu sản xuất...');
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccessModal(
                        'Tạo yêu cầu sản xuất thành công!',
                        `Đã tạo lệnh sản xuất: <strong>${response.data.so_lenh_sx}</strong><br>
                         Sản phẩm: ${response.data.san_pham.ma}<br>
                         Số lượng: ${response.data.so_luong}<br>
                         Trạng thái: ${response.data.trang_thai}<br><br>
                         <em>Sau khi sản xuất xong và nhập kho, vui lòng kiểm tra lại để xuất đi gia công.</em>`
                    );
                    
                    // Reload lại trang sau 3 giây
                    setTimeout(() => {
                        if (typeof loadData === 'function') {
                            loadData();
                        }
                        this.closeModal();
                    }, 3000);
                } else {
                    this.showErrorModal(response.message);
                }
            },
            error: (xhr) => {
                const errorMsg = this.handleAjaxError(xhr);
                this.showErrorModal(errorMsg);
            }
        });
    }

    /**
     * BƯỚC 2B hoặc 3: Xuất kho đi gia công
     */
    exportForProcessing() {
        if (!this.statusData) return;

        const soLuongXuat = this.statusData.san_pham_nhung_nong.so_luong_can;

        $.ajax({
            url: 'api/process_gia_cong_ma_nhung_nong_v2.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'export_for_processing',
                cbh_id: this.currentCbhId,
                chi_tiet_cbh_id: this.currentChiTietId,
                so_luong_xuat: soLuongXuat,
                nguoi_xuat: currentUser || 'Admin',
                ghi_chu: `Xuất ${soLuongXuat} ${this.statusData.san_pham_dien_phan.ma} đi gia công mạ nhúng nóng`
            }),
            beforeSend: () => {
                this.showLoadingModal('Đang xuất kho...');
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccessModal(
                        'Xuất kho thành công!',
                        `Mã phiếu xuất: <strong>${response.data.ma_phieu_xuat}</strong><br>
                         Số lượng xuất: ${response.data.so_luong_xuat}<br>
                         Tồn kho còn lại: ${response.data.ton_kho_con_lai}<br><br>
                         <em>Sau khi gia công xong, vui lòng nhập kho sản phẩm mạ nhúng nóng.</em>`
                    );
                    
                    // Reload lại trang sau 3 giây
                    setTimeout(() => {
                        if (typeof loadData === 'function') {
                            loadData();
                        }
                        this.closeModal();
                    }, 3000);
                } else {
                    this.showErrorModal(response.message);
                }
            },
            error: (xhr) => {
                const errorMsg = this.handleAjaxError(xhr);
                this.showErrorModal(errorMsg);
            }
        });
    }

    /**
     * BƯỚC 4: Nhập kho sau gia công
     */
    importAfterProcessing() {
        if (!this.statusData || !this.statusData.phieu_xuat) return;

        const phieuXuatId = this.statusData.phieu_xuat.PhieuXuatID;
        const soLuongNhap = this.statusData.phieu_xuat.SoLuongXuat;

        $.ajax({
            url: 'api/process_gia_cong_ma_nhung_nong_v2.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'import_after_processing',
                phieu_xuat_id: phieuXuatId,
                so_luong_nhap: soLuongNhap,
                nguoi_nhap: currentUser || 'Admin',
                ghi_chu: 'Nhập kho sau gia công mạ nhúng nóng'
            }),
            beforeSend: () => {
                this.showLoadingModal('Đang nhập kho...');
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccessModal(
                        'Nhập kho thành công!',
                        `Mã phiếu nhập: <strong>${response.data.ma_phieu_nhap}</strong><br>
                         Số lượng nhập: ${response.data.so_luong_nhap}<br>
                         Tồn kho mới: ${response.data.ton_kho_moi}<br><br>
                         <em>Đã hoàn thành quy trình gia công mạ nhúng nóng.</em>`
                    );
                    
                    // Reload lại trang sau 3 giây
                    setTimeout(() => {
                        if (typeof loadData === 'function') {
                            loadData();
                        }
                        this.closeModal();
                    }, 3000);
                } else {
                    this.showErrorModal(response.message);
                }
            },
            error: (xhr) => {
                const errorMsg = this.handleAjaxError(xhr);
                this.showErrorModal(errorMsg);
            }
        });
    }

    /**
     * Hiển thị modal trạng thái
     */
    showStatusModal(data) {
        const spMNN = data.san_pham_nhung_nong;
        const spMDP = data.san_pham_dien_phan;
        
        let actionButtons = '';
        let statusIcon = '';
        let statusColor = '';
        let statusMessage = data.status_message;

        // Xác định màu và icon dựa trên next_action
        switch (data.next_action) {
            case 'create_production_request':
                statusIcon = '<i class="fas fa-exclamation-triangle"></i>';
                statusColor = 'text-yellow-600';
                actionButtons = `
                    <button id="btn-create-production" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <i class="fas fa-industry mr-2"></i>Tạo Yêu Cầu SX
                    </button>
                `;
                break;
                
            case 'export_for_processing':
                statusIcon = '<i class="fas fa-check-circle"></i>';
                statusColor = 'text-green-600';
                actionButtons = `
                    <button id="btn-export-processing" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        <i class="fas fa-truck-loading mr-2"></i>Xuất Đi Gia Công
                    </button>
                `;
                break;
                
            case 'import_after_processing':
                statusIcon = '<i class="fas fa-clock"></i>';
                statusColor = 'text-blue-600';
                actionButtons = `
                    <button id="btn-import-after-processing" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        <i class="fas fa-warehouse mr-2"></i>Nhập Kho Sau GC
                    </button>
                `;
                break;
                
            case 'wait_for_production':
                statusIcon = '<i class="fas fa-hourglass-half"></i>';
                statusColor = 'text-orange-600';
                statusMessage += `<br><br><strong>Lệnh SX:</strong> ${data.lenh_san_xuat.SoLenhSX}<br>
                    <strong>Trạng thái:</strong> ${data.lenh_san_xuat.TrangThai}<br>
                    <strong>Số lượng:</strong> ${data.lenh_san_xuat.SoLuongYeuCau}`;
                break;
        }

        const modalHtml = `
            <div id="mnn-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                <div class="relative p-6 border w-full max-w-3xl shadow-lg rounded-lg bg-white">
                    <div class="absolute top-4 right-4">
                        <button id="mnn-modal-close" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            <i class="fas fa-fire-alt text-orange-500 mr-2"></i>
                            Trạng Thái Gia Công Mạ Nhúng Nóng
                        </h3>
                        <div class="h-1 w-20 bg-orange-500 rounded"></div>
                    </div>

                    <!-- Trạng thái -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border-l-4 ${statusColor.replace('text-', 'border-')}">
                        <div class="flex items-start">
                            <div class="${statusColor} text-2xl mr-3 mt-1">${statusIcon}</div>
                            <div class="flex-1">
                                <h4 class="font-semibold ${statusColor} mb-1">Trạng Thái</h4>
                                <p class="text-gray-700">${statusMessage}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin sản phẩm -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Sản phẩm Mạ Nhúng Nóng -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-bullseye text-orange-500 mr-2"></i>
                                Sản Phẩm Đích (MNN)
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Mã hàng:</span>
                                    <span class="font-semibold ml-2">${spMNN.ma}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Tên:</span>
                                    <span class="ml-2">${spMNN.ten}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Số lượng cần:</span>
                                    <span class="font-bold text-orange-600 ml-2">${spMNN.so_luong_can}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sản phẩm Mạ Điện Phân -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-box text-blue-500 mr-2"></i>
                                Sản Phẩm Nguồn (MĐP)
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Mã hàng:</span>
                                    <span class="font-semibold ml-2">${spMDP.ma}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Tồn kho:</span>
                                    <span class="ml-2">${spMDP.ton_kho_vat_ly}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Đã gán:</span>
                                    <span class="ml-2">${spMDP.da_gan}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Khả dụng:</span>
                                    <span class="font-bold ${data.can_xuat_gia_cong ? 'text-green-600' : 'text-red-600'} ml-2">
                                        ${spMDP.ton_kho_kha_dung}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nút hành động -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button id="mnn-modal-cancel" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                            Đóng
                        </button>
                        ${actionButtons}
                    </div>
                </div>
            </div>
        `;

        $('#mnn-modal').remove();
        $('body').append(modalHtml);
    }

    /**
     * Hiển thị modal loading
     */
    showLoadingModal(message = 'Đang xử lý...') {
        const modalHtml = `
            <div id="mnn-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                <div class="relative p-8 border w-full max-w-md shadow-lg rounded-lg bg-white text-center">
                    <div class="mb-4">
                        <i class="fas fa-spinner fa-spin text-blue-600 text-4xl"></i>
                    </div>
                    <p class="text-gray-700 font-medium">${message}</p>
                </div>
            </div>
        `;

        $('#mnn-modal').remove();
        $('body').append(modalHtml);
    }

    /**
     * Hiển thị modal thành công
     */
    showSuccessModal(title, message) {
        const modalHtml = `
            <div id="mnn-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                <div class="relative p-6 border w-full max-w-md shadow-lg rounded-lg bg-white">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <i class="fas fa-check text-green-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">${title}</h3>
                        <div class="text-gray-600 text-sm mb-6">${message}</div>
                        <button id="mnn-modal-close" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#mnn-modal').remove();
        $('body').append(modalHtml);
    }

    /**
     * Hiển thị modal lỗi
     */
    showErrorModal(message) {
        const modalHtml = `
            <div id="mnn-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                <div class="relative p-6 border w-full max-w-md shadow-lg rounded-lg bg-white">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Lỗi</h3>
                        <div class="text-gray-600 text-sm mb-6">${message}</div>
                        <button id="mnn-modal-close" class="px-6 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#mnn-modal').remove();
        $('body').append(modalHtml);
    }

    /**
     * Đóng modal
     */
    closeModal() {
        $('#mnn-modal').remove();
    }

    /**
     * Xử lý lỗi AJAX
     */
    handleAjaxError(xhr) {
        let errorMessage = 'Lỗi kết nối đến máy chủ.';
        try {
            const response = JSON.parse(xhr.responseText);
            if (response && response.message) {
                errorMessage = response.message;
            }
        } catch (e) {
            if (xhr.statusText && xhr.statusText !== 'error') {
                errorMessage = `Lỗi: ${xhr.statusText}`;
            }
        }
        return errorMessage;
    }
}

// Khởi tạo handler
const mnnHandler = new MaNhungNongHandler();