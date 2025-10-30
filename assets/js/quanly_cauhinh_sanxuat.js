// ✅ SỬA LỖI HIỂN THỊ MODAL: Định nghĩa đối tượng App một cách an toàn
// 1. Đảm bảo đối tượng App toàn cục (global) tồn tại
window.App = window.App || {};

// 2. Gán hoặc ghi đè hàm showMessageModal vào đối tượng App một cách tường minh
window.App.showMessageModal = function(title, message, type) {
    const modal = $('#app-modal');
    if (modal.length) {
        modal.find('#modal-title').text(title);
        modal.find('#modal-message').text(message);
        modal.show(); // Hiển thị modal

        // Gán sự kiện đóng modal một lần duy nhất
        if (!modal.data('handler-attached')) {
            $('#modal-close-btn').on('click', function() {
                $('#app-modal').hide();
            });
            modal.data('handler-attached', true);
        }
    } else {
        // Fallback nếu không tìm thấy modal trong HTML
        console.error("Không tìm thấy #app-modal. Hiển thị bằng alert().");
        alert(`${title}\n\n${message}`);
    }
};


$(document).ready(function() {
    // Truyền vào window.App đã được định nghĩa an toàn ở trên
    initializeProductionConfigPage($('.config-page-container'), window.App);
});

/**
 * Khởi tạo trang quản lý cấu hình sản xuất.
 * @param {jQuery} mainContentContainer - Container chính của trang.
 * @param {object} App - Đối tượng App chứa các hàm tiện ích (như showMessageModal).
 */
function initializeProductionConfigPage(mainContentContainer, App) {
    const API_URL = 'api/production_config_api.php'; // Cần đảm bảo đường dẫn này đúng
    const formGrid = mainContentContainer.find('#config-form-grid');
    const configForm = mainContentContainer.find('#config-form');

    const descriptions = {
        'NgayNghiLe': 'Quản lý danh sách các ngày nghỉ lễ trong năm. Các ngày này sẽ được loại trừ khi tính toán tiến độ.',
        'NangSuatMaGoiPU': 'Cấu hình năng suất sản xuất cho từng loại "Mã gối PU" (cây/ngày).',
        'NangSuatUla': 'Cấu hình năng suất sản xuất cho từng loại "Ula" (cái/ngày).',
        'GioLamViecMoiNgay': 'Tổng số giờ làm việc trong một ngày. Hệ thống mặc định không tính Thứ 7 và Chủ Nhật là ngày làm việc.'
    };

    async function apiRequest(action, data = {}) {
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) {
                throw new Error(`Lỗi mạng: ${response.statusText}`);
            }
            const result = await response.json();
            if (!result.success) {
                App.showMessageModal('Lỗi API', result.message || 'Có lỗi xảy ra từ phía máy chủ', 'error');
                throw new Error(result.message);
            }
            return result;
        } catch (error) {
            App.showMessageModal('Lỗi Mạng', `Không thể kết nối đến API. Vui lòng kiểm tra đường dẫn và kết nối mạng. Lỗi: ${error.message}`, 'error');
            throw error;
        }
    }

    function createKeyValueEditorHTML(config, description, keyLabel, valueLabel) {
        let values = {};
        try {
            values = JSON.parse(config.GiaTriThietLap || '{}');
        } catch (e) { console.error(`Lỗi parse JSON cho ${config.TenThietLap}:`, e); }

        const itemsHTML = Object.entries(values).map(([key, value]) => `
            <div class="kv-item">
                <input type="text" class="kv-key border" value="${key}" placeholder="${keyLabel}">
                <input type="number" class="kv-value border" value="${value}" placeholder="${valueLabel}">
                <button type="button" class="delete-kv-btn" title="Xóa dòng này">&times;</button>
            </div>`).join('');

        return `
            <div class="form-group kv-manager" data-ten-thiet-lap="${config.TenThietLap}">
                <label>${config.TenThietLap}</label>
                <p class="description">${description}</p>
                <div class="kv-list">${itemsHTML}</div>
                <button type="button" class="add-kv-btn">Thêm Dòng</button>
            </div>`;
    }
    
    async function loadAndRenderConfigs() {
        try {
            const result = await apiRequest('get_all_configs');
            formGrid.empty();
            const sortedData = result.data.sort((a, b) => {
                if (a.TenThietLap === 'GioLamViecMoiNgay') return -1;
                if (b.TenThietLap === 'GioLamViecMoiNgay') return 1;
                if (a.TenThietLap === 'NgayNghiLe') return -1;
                if (b.TenThietLap === 'NgayNghiLe') return 1;
                return 0;
            });

            sortedData.forEach(config => {
                const description = descriptions[config.TenThietLap] || 'Thiết lập cho hệ thống';
                let formGroupHTML = '';

                if (config.TenThietLap === 'NgayNghiLe') {
                    let dates = [];
                    try {
                        dates = JSON.parse(config.GiaTriThietLap || '[]');
                        if (!Array.isArray(dates)) dates = [];
                    } catch (e) { dates = []; console.error("Lỗi parse JSON cho NgayNghiLe:", e); }
                    
                    const dateItems = dates.map(date => `
                        <li class="date-item" data-date="${date}">
                            <span>${date}</span>
                            <button type="button" class="delete-date-btn" title="Xóa ngày này">&times;</button>
                        </li>`).join('');

                    formGroupHTML = `
                        <div class="form-group holiday-manager" data-ten-thiet-lap="NgayNghiLe">
                            <label>Ngày Nghỉ Lễ</label>
                            <p class="description">${description}</p>
                            <div class="add-date-form">
                                <input type="date" id="new-holiday-date" class="border">
                                <button type="button" id="add-date-btn" class="add-date-btn">Thêm</button>
                            </div>
                            <ul class="date-list">${dateItems}</ul>
                        </div>`;
                } else if (config.TenThietLap === 'NangSuatMaGoiPU') {
                    formGroupHTML = createKeyValueEditorHTML(config, description, 'Mã Gối', 'Cây/Ngày');
                } else if (config.TenThietLap === 'NangSuatUla') {
                    formGroupHTML = createKeyValueEditorHTML(config, description, 'Mã Ula', 'Cái/Ngày');
                } else {
                    formGroupHTML = `
                        <div class="form-group">
                            <label for="config-${config.TenThietLap}">${config.TenThietLap}</label>
                            <p class="description">${description}</p>
                            <input type="number" id="config-${config.TenThietLap}" name="${config.TenThietLap}" value="${config.GiaTriThietLap}" data-ten-thiet-lap="${config.TenThietLap}">
                        </div>`;
                }
                formGrid.append(formGroupHTML);
            });
        } catch (error) {
            formGrid.html('<p style="color: red;">Không thể tải dữ liệu cấu hình. Vui lòng kiểm tra lại kết nối và API.</p>');
            console.error("Lỗi khi tải cấu hình:", error);
        }
    }

    function setupEventListeners() {
        configForm.on('submit', async (e) => {
            e.preventDefault();
            const updatedConfigs = [];
            
            // ✅ SỬA LỖI LOGIC: Sử dụng selector chính xác hơn để không bị trùng lặp dữ liệu
            mainContentContainer.find('.form-group:not(.kv-manager):not(.holiday-manager) input[type="number"]').each(function() {
                const input = $(this);
                if (input.data('ten-thiet-lap')) {
                    updatedConfigs.push({
                        TenThietLap: input.data('ten-thiet-lap'),
                        GiaTriThietLap: input.val()
                    });
                }
            });

            mainContentContainer.find('.kv-manager').each(function() {
                const manager = $(this);
                const tenThietLap = manager.data('ten-thiet-lap');
                const values = {};
                manager.find('.kv-item').each(function() {
                    const key = $(this).find('.kv-key').val().trim();
                    const value = $(this).find('.kv-value').val().trim();
                    if (key && value) {
                        values[key] = parseFloat(value) || value;
                    }
                });
                updatedConfigs.push({ TenThietLap: tenThietLap, GiaTriThietLap: JSON.stringify(values) });
            });

            const holidayDates = [];
            mainContentContainer.find('.holiday-manager .date-item').each(function() {
                holidayDates.push($(this).data('date'));
            });
            updatedConfigs.push({ TenThietLap: 'NgayNghiLe', GiaTriThietLap: JSON.stringify(holidayDates) });

            try {
                const result = await apiRequest('update_configs', { configs: updatedConfigs });
                App.showMessageModal('Thành công', result.message, 'success');
            } catch (error) { 
                // Lỗi đã được xử lý và hiển thị bởi hàm apiRequest, không cần làm gì thêm ở đây.
            }
        });

        configForm.on('click', '.delete-kv-btn', function() { $(this).closest('.kv-item').remove(); });
        
        configForm.on('click', '.add-kv-btn', function() {
            const list = $(this).siblings('.kv-list');
            const newItemHTML = `
                <div class="kv-item">
                    <input type="text" class="kv-key border" placeholder="Nhập mã">
                    <input type="number" class="kv-value border" placeholder="Nhập giá trị">
                    <button type="button" class="delete-kv-btn" title="Xóa dòng này">&times;</button>
                </div>`;
            list.append(newItemHTML);
        });

        configForm.on('click', '.delete-date-btn', function() { $(this).closest('.date-item').remove(); });

        configForm.on('click', '#add-date-btn', function() {
            const dateInput = mainContentContainer.find('#new-holiday-date');
            const newDate = dateInput.val();
            if (!newDate) { App.showMessageModal('Thông báo', 'Vui lòng chọn một ngày.', 'info'); return; }
            
            let isDuplicate = false;
            const dateList = mainContentContainer.find('.holiday-manager .date-list');
            dateList.find('.date-item').each(function() { if ($(this).data('date') === newDate) isDuplicate = true; });

            if (isDuplicate) { App.showMessageModal('Thông báo', 'Ngày này đã tồn tại.', 'info'); return; }
            
            const newItemHTML = `<li class="date-item" data-date="${newDate}"><span>${newDate}</span><button type="button" class="delete-date-btn" title="Xóa">&times;</button></li>`;
            dateList.append(newItemHTML);
            dateInput.val('');
        });
    }

    loadAndRenderConfigs();
    setupEventListeners();
}