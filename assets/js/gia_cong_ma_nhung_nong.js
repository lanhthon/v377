/**
 * =================================================================================
 * MODULE XỬ LÝ GIA CÔNG MẠ NHÚNG NÓNG
 * =================================================================================
 * Version: 1.0
 * Description: Xử lý xuất kho ULA mạ điện phân để gia công mạ nhúng nóng
 * 
 * Chức năng:
 * - Hiển thị danh sách sản phẩm ULA cần gia công mạ nhúng nóng
 * - Xử lý xuất kho mạ điện phân để gia công
 * - Theo dõi trạng thái gia công
 * - Nhập kho sản phẩm sau gia công
 */

// Global variables
let danhSachGiaCongData = [];
let currentCbhId = null;

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
                            <strong>Lưu ý:</strong> Hệ thống tự động tìm sản phẩm ULA mạ điện phân tương ứng để xuất kho gia công. 
                            Sau khi gia công xong, sản phẩm sẽ được nhập vào kho với tình trạng mạ nhúng nóng.
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
        
        const hasEnoughDienPhan = soLuongXuat > 0;
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
                    ` : `
                        <span class="text-xs text-gray-400">Không đủ hàng</span>
                    `}
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

// Export functions to global scope
window.initGiaCongMaNhungNong = initGiaCongMaNhungNong;
window.renderDanhSachGiaCong = renderDanhSachGiaCong;
window.xuatKhoGiaCong = xuatKhoGiaCong;
window.closeXuatGiaCongModal = closeXuatGiaCongModal;
window.confirmXuatGiaCong = confirmXuatGiaCong;
window.xuatTatCaGiaCong = xuatTatCaGiaCong;
window.showNotification = showNotification;