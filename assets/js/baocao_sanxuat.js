// js/baocao_sanxuat.js

function initializeProductionReportPage(mainContentContainer) {

    // --- DOM ELEMENTS ---
    const startDateInput = $('#report-start-date');
    const endDateInput = $('#report-end-date');
    const filterBtn = $('#filter-report-btn');
    const exportBtn = $('#export-report-excel-btn');
    const reportContainer = $('#report-container');

    // --- HELPER FUNCTIONS ---
    function formatNumber(num) {
        if (num === null || num === undefined) return 0;
        return String(num).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        return new Date(dateStr).toLocaleDateString('vi-VN');
    }

    // --- CORE FUNCTIONS ---
    function renderReport(data) {
        reportContainer.empty();
        if (!data || data.length === 0) {
            reportContainer.html('<div class="bg-white p-6 rounded-lg shadow-sm text-center text-gray-500">Không tìm thấy dữ liệu báo cáo cho khoảng thời gian đã chọn.</div>');
            return;
        }

        let tableHtml = `
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase">Ngày Báo Cáo</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase">Số LSX</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase">Mã / Tên Sản Phẩm</th>
                            <th class="px-4 py-3 text-right font-bold text-gray-600 uppercase">Sản Lượng</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase">Người Thực Hiện</th>
                            <th class="px-4 py-3 text-left font-bold text-gray-600 uppercase">Ghi Chú</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">`;
        
        data.forEach(item => {
            tableHtml += `
                <tr>
                    <td class="px-4 py-3 font-semibold">${formatDate(item.NgayBaoCao)}</td>
                    <td class="px-4 py-3">${item.SoLenhSX}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">${item.MaBTP}</div>
                        <div class="text-gray-500">${item.TenBTP}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-blue-600">${formatNumber(item.SoLuongHoanThanh)}</td>
                    <td class="px-4 py-3">${item.NguoiThucHien || 'N/A'}</td>
                    <td class="px-4 py-3 text-gray-600">${item.GhiChuNhatKy || ''}</td>
                </tr>
            `;
        });

        tableHtml += `</tbody></table></div>`;
        reportContainer.html(tableHtml);
    }

    function loadReportData() {
        const startDate = startDateInput.val();
        const endDate = endDateInput.val();

        if (!startDate || !endDate) {
            alert('Vui lòng chọn cả ngày bắt đầu và kết thúc.');
            return;
        }

        reportContainer.html('<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="mt-2 text-gray-500">Đang tải báo cáo...</p></div>');
        
        $.ajax({
            url: `api/get_daily_production_report.php?start_date=${startDate}&end_date=${endDate}`,
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    renderReport(res.data);
                } else {
                    reportContainer.html(`<div class="bg-white p-6 rounded-lg shadow-sm text-center text-red-500">Lỗi: ${res.message}</div>`);
                }
            },
            error: () => {
                reportContainer.html(`<div class="bg-white p-6 rounded-lg shadow-sm text-center text-red-500">Lỗi kết nối hoặc xử lý dữ liệu.</div>`);
            }
        });
    }
    
    function setupEventListeners() {
        filterBtn.on('click', loadReportData);
        
        exportBtn.on('click', function() {
            const startDate = startDateInput.val();
            const endDate = endDateInput.val();
            if (startDate && endDate) {
                // Bạn cần tạo file export này, nó sẽ tương tự file get_report nhưng output ra file excel
                window.location.href = `api/export_daily_production_excel.php?start_date=${startDate}&end_date=${endDate}`;
            } else {
                alert('Vui lòng chọn khoảng thời gian để xuất file.');
            }
        });
    }

    // --- INITIALIZATION ---
    function init() {
        const today = new Date().toISOString().split('T')[0];
        startDateInput.val(today);
        endDateInput.val(today);
        setupEventListeners();
        loadReportData(); // Tải dữ liệu cho ngày hôm nay khi vào trang
    }

    init();
}