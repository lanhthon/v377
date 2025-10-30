function initializeInventorySalesReportPage() {
    // =================================================================
    // KHAI BÁO BIẾN VÀ DOM ELEMENTS
    // =================================================================
    const startDateInput = $('#report-start-date');
    const endDateInput = $('#report-end-date');
    const customerSelect = $('#report-customer-select');
    const productCodeInput = $('#report-product-code');
    const projectNameInput = $('#report-project-name');
    const viewReportBtn = $('#view-report-btn');
    const exportExcelBtn = $('#export-excel-btn');
    const resetFiltersBtn = $('#reset-filters-btn');
    const resultsContainer = $('#report-results-container');
    const summaryContainer = $('#report-summary-container');
    
    let reportData = [];

    // =================================================================
    // HÀM TIỆN ÍCH
    // =================================================================
    function fetchData(url, params = {}) {
        return $.ajax({
            url: url,
            type: 'GET',
            data: params,
            dataType: 'json',
            cache: false
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(`Lỗi AJAX khi gọi ${url}:`, textStatus, errorThrown);
            resultsContainer.html(`
                <div class="text-center p-8 text-red-600">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <p class="font-semibold">Lỗi kết nối đến server. Vui lòng thử lại.</p>
                </div>
            `);
        });
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function getStatusBadgeClass(percent) {
        if (percent >= 100) return 'bg-green-100 text-green-800';
        if (percent > 0) return 'bg-yellow-100 text-yellow-800';
        return 'bg-gray-100 text-gray-800';
    }

    // =================================================================
    // HÀM RENDER
    // =================================================================
    function renderSummary(summaryData) {
        if (!summaryData) {
            summaryContainer.html('');
            return;
        }

        const completionRate = summaryData.totalSoLuongDaChot > 0 
            ? Math.round((summaryData.totalSoLuongDaGiao / summaryData.totalSoLuongDaChot) * 100)
            : 0;

        const html = `
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-poll text-green-600"></i>
                    Tổng Hợp Kết Quả
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Báo giá chưa chốt -->
                    <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-gray-400">
                        <p class="text-sm text-gray-600 font-semibold flex items-center"><i class="fas fa-file-alt mr-2"></i>Báo Giá (Chưa chốt)</p>
                        <p class="text-2xl font-bold text-gray-800 mt-2">${formatNumber(summaryData.totalBaoGiaChuaChot)} <span class="text-base font-normal">báo giá</span></p>
                        <p class="text-lg font-semibold text-gray-700">${formatNumber(summaryData.totalSoLuongChuaChot)} <span class="text-sm font-normal">sản phẩm</span></p>
                    </div>
                     <!-- Đơn hàng đã chốt -->
                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                        <p class="text-sm text-blue-600 font-semibold flex items-center"><i class="fas fa-check-circle mr-2"></i>Đơn Hàng (Đã chốt)</p>
                        <p class="text-2xl font-bold text-blue-800 mt-2">${formatNumber(summaryData.totalDonHangDaChot)} <span class="text-base font-normal">đơn hàng</span></p>
                        <p class="text-lg font-semibold text-blue-700">${formatNumber(summaryData.totalSoLuongDaChot)} <span class="text-sm font-normal">sản phẩm</span></p>
                    </div>
                     <!-- Tình hình giao hàng -->
                    <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                        <p class="text-sm text-green-600 font-semibold flex items-center"><i class="fas fa-truck mr-2"></i>Giao Hàng (trên đơn đã chốt)</p>
                        <p class="text-2xl font-bold text-green-800 mt-2">${formatNumber(summaryData.totalSoLuongDaGiao)} <span class="text-base font-normal">đã giao</span></p>
                        <p class="text-lg font-semibold text-green-700">${completionRate}% <span class="text-sm font-normal">hoàn thành</span></p>
                    </div>
                </div>
            </div>
        `;
        summaryContainer.html(html);
    }

    function renderReportTable() {
        if (!reportData || reportData.length === 0) {
            resultsContainer.html(`
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">Không có báo giá hoặc đơn hàng nào phù hợp với bộ lọc.</p>
                    </div>
                </div>
            `);
            return;
        }

        let html = `
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list-alt text-green-600"></i>
                        Chi Tiết Báo Giá & Đơn Hàng
                        <span class="text-sm font-normal text-gray-500">(${reportData.length} mục)</span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="report-table">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center border-b-2 border-gray-300">
                                    <input type="checkbox" id="select-all-checkbox" title="Chọn tất cả" class="form-checkbox h-4 w-4 text-green-600 rounded">
                                </th>
                                <th class="py-3 px-3 w-10 border-b-2 border-gray-300"></th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">Số Báo Giá & Trạng Thái</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">Ngày</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">Khách Hàng</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">Dự Án</th>
                                <th class="text-right py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">Tổng SL</th>
                                <th class="text-right py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">SL Đã Giao</th>
                                <th class="text-center py-3 px-3 font-semibold text-gray-700 border-b-2 border-gray-300">% Giao Hàng</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        reportData.forEach((item, idx) => {
            const itemId = `item-${idx}`;
            let rowHtml = `
                <tr class="border-b hover:bg-gray-50 transition-colors item-row" data-item-id="${itemId}">
                    <td class="py-3 px-3 text-center">
                        <input type="checkbox" class="row-checkbox form-checkbox h-4 w-4 text-green-600 rounded" data-index="${idx}">
                    </td>
                    <td class="py-3 px-3 text-center"><i class="fas fa-chevron-down text-gray-400 toggle-icon"></i></td>
            `;

            if (item.status === 'Đã chốt') {
                const badgeClass = getStatusBadgeClass(item.OverallCompletion);
                rowHtml += `
                        <td class="py-3 px-3">
                            <span class="font-semibold text-blue-600">${item.SoBaoGia}</span>
                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">Đã chốt</span>
                        </td>
                        <td class="py-3 px-3">${formatDate(item.NgayBaoGia)}</td>
                        <td class="py-3 px-3">${item.TenCongTy}</td>
                        <td class="py-3 px-3">${item.TenDuAn || '-'}</td>
                        <td class="py-3 px-3 text-right font-medium">${formatNumber(item.TotalSoLuongBaoGia)}</td>
                        <td class="py-3 px-3 text-right font-bold text-green-700">${formatNumber(item.TotalSLDaGiao)}</td>
                        <td class="py-3 px-3 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold ${badgeClass}">${item.OverallCompletion}%</span>
                        </td>
                `;
            } else { // 'Chưa chốt'
                 rowHtml += `
                        <td class="py-3 px-3">
                            <span class="font-semibold text-gray-600">${item.SoBaoGia}</span>
                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-gray-200 text-gray-700 rounded-full">Chưa chốt</span>
                        </td>
                        <td class="py-3 px-3">${formatDate(item.NgayBaoGia)}</td>
                        <td class="py-3 px-3">${item.TenCongTy}</td>
                        <td class="py-3 px-3">${item.TenDuAn || '-'}</td>
                        <td class="py-3 px-3 text-right font-medium">${formatNumber(item.TotalSoLuongBaoGia)}</td>
                        <td class="py-3 px-3 text-right text-gray-400">-</td>
                        <td class="py-3 px-3 text-center text-gray-400">-</td>
                `;
            }
            rowHtml += `</tr>`;
            html += rowHtml;
            html += `
                <tr class="product-details-row hidden bg-gray-50" data-parent-item-id="${itemId}">
                     <td colspan="9" class="p-4">${renderProductTable(item.products, itemId)}</td>
                </tr>
            `;
        });

        html += `</tbody></table></div></div>`;
        resultsContainer.html(html);
        attachTableEvents();
    }
    
    function renderProductTable(products, itemId) {
        if (!products || products.length === 0) return '<p class="text-center text-gray-500 p-4">Không có sản phẩm trong mục này.</p>';
        
        let tableHtml = `
            <div class="bg-white p-4 rounded-lg border">
            <h4 class="font-semibold mb-2 text-gray-700">Chi tiết sản phẩm (${products.length} loại):</h4>
            <table class="min-w-full text-xs">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="w-8"></th>
                        <th class="text-left p-2 font-semibold">Mã Hàng</th>
                        <th class="text-left p-2 font-semibold">Tên Sản Phẩm</th>
                        <th class="text-right p-2 font-semibold">Số Lượng</th>
                        <th class="text-right p-2 font-semibold">SL Đã Giao</th>
                        <th class="text-right p-2 font-semibold">SL Còn Lại</th>
                        <th class="text-center p-2 font-semibold">% Giao Hàng</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        products.forEach((product, pIdx) => {
            const productId = `${itemId}-product-${pIdx}`;
            const hasDeliveries = product.deliveries && product.deliveries.length > 0;
            const productBadgeClass = getStatusBadgeClass(product.PhanTramHoanThanh);

            tableHtml += `
                <tr class="border-b hover:bg-blue-50 ${hasDeliveries ? 'cursor-pointer' : ''} product-row" data-product-id="${productId}">
                    <td class="p-2 text-center">
                        ${hasDeliveries ? '<i class="fas fa-chevron-down text-gray-400 product-toggle-icon"></i>' : ''}
                    </td>
                    <td class="p-2 font-mono">${product.MaHang}</td>
                    <td class="p-2">${product.TenSanPham}</td>
                    <td class="p-2 text-right font-medium">${formatNumber(product.SoLuongBaoGia)}</td>
                    <td class="p-2 text-right font-bold text-green-600">${product.TongSLDaGiao > 0 ? formatNumber(product.TongSLDaGiao) : '-'}</td>
                    <td class="p-2 text-right font-medium text-orange-600">${product.SoLuongConLai > 0 ? formatNumber(product.SoLuongConLai) : '-'}</td>
                    <td class="p-2 text-center">
                         ${product.TongSLDaGiao > 0 ? `<span class="px-2 py-0.5 rounded-full text-xs font-semibold ${productBadgeClass}">${product.PhanTramHoanThanh}%</span>` : '-'}
                    </td>
                </tr>
            `;

            if (hasDeliveries) {
                tableHtml += `
                    <tr class="delivery-details-row hidden bg-blue-50" data-parent-product-id="${productId}">
                        <td colspan="7" class="py-2 px-4">
                            ${renderDeliveryDetails(product.deliveries)}
                        </td>
                    </tr>
                `;
            }
        });
        
        tableHtml += `</tbody></table></div>`;
        return tableHtml;
    }

    function renderDeliveryDetails(deliveries) {
         let deliveryHtml = `
            <div class="bg-white rounded-lg p-3 border-l-4 border-green-500 my-2">
                <h5 class="font-semibold text-gray-700 text-xs mb-2">Chi tiết ${deliveries.length} đợt giao hàng:</h5>
                <div class="space-y-1">
        `;

        deliveries.forEach((delivery, dIdx) => {
            deliveryHtml += `
                <div class="flex items-center justify-between p-2 bg-green-50 rounded">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-semibold text-green-700 bg-green-200 px-2 py-0.5 rounded-full text-xs">Đợt ${dIdx + 1}</span>
                        <span class="text-gray-600 text-xs"><i class="fas fa-calendar-alt fa-fw mr-1"></i>${formatDate(delivery.NgayGiao)}</span>
                        ${delivery.SoBBGH ? `<span class="text-gray-600 text-xs"><i class="fas fa-file-invoice fa-fw mr-1"></i>${delivery.SoBBGH}</span>` : ''}
                        ${delivery.SoPhieuXuat ? `<span class="text-gray-600 text-xs"><i class="fas fa-box-open fa-fw mr-1"></i>${delivery.SoPhieuXuat}</span>` : ''}
                    </div>
                    <span class="font-bold text-green-700 text-xs">SL: ${formatNumber(delivery.SoLuongGiao)}</span>
                </div>
            `;
        });

        deliveryHtml += `</div></div>`;
        return deliveryHtml;
    }

    function attachTableEvents() {
        // Hủy các event handler cũ để tránh bị gọi nhiều lần
        resultsContainer.off('click');

        // Mở rộng/thu gọn chi tiết sản phẩm
        resultsContainer.on('click', '.item-row', function(e) {
            // Không thực hiện nếu click vào checkbox hoặc ô chứa checkbox
            if ($(e.target).is('input:checkbox') || $(e.target).closest('td').is(':first-child')) {
                return;
            }
            if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;
            
            const itemId = $(this).data('item-id');
            const detailRow = $(`.product-details-row[data-parent-item-id="${itemId}"]`);
            const icon = $(this).find('.toggle-icon');
            
            detailRow.toggleClass('hidden');
            icon.toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Mở rộng/thu gọn chi tiết giao hàng
        resultsContainer.on('click', '.product-row', function(e) {
            if (!$(this).hasClass('cursor-pointer')) return;
            if ($(e.target).is('a, button') || $(e.target).closest('a, button').length) return;

            const productId = $(this).data('product-id');
            const deliveryRow = $(`.delivery-details-row[data-parent-product-id="${productId}"]`);
            const icon = $(this).find('.product-toggle-icon');

            deliveryRow.toggleClass('hidden');
            icon.toggleClass('fa-chevron-down fa-chevron-up');
        });

        // Logic cho checkbox "Chọn tất cả"
        resultsContainer.on('click', '#select-all-checkbox', function() {
            const isChecked = $(this).prop('checked');
            resultsContainer.find('.row-checkbox').prop('checked', isChecked);
        });

        // Logic cho các checkbox của từng dòng
        resultsContainer.on('click', '.row-checkbox', function() {
            const totalCheckboxes = resultsContainer.find('.row-checkbox').length;
            const checkedCheckboxes = resultsContainer.find('.row-checkbox:checked').length;
            resultsContainer.find('#select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    }

    // =================================================================
    // HÀM XỬ LÝ SỰ KIỆN
    // =================================================================
    function handleViewReport() {
        const button = viewReportBtn;
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');

        const params = {
            start_date: startDateInput.val(),
            end_date: endDateInput.val(),
            customer_id: customerSelect.val(),
            delivery_status: $('input[name="delivery_status"]:checked').val(),
            quote_status: $('input[name="quote_status"]:checked').val(),
            product_code: productCodeInput.val(),
            project_name: projectNameInput.val()
        };

        fetchData('api/get_inventory_sales_report_enhanced.php', params)
            .done(function(response) {
                if (response.success) {
                    reportData = response.data;
                    renderSummary(response.summary);
                    renderReportTable();
                    
                    const totalRecords = response.data.length;
                     if(totalRecords > 0) {
                        App.showMessageModal(
                            `Đã tải thành công dữ liệu cho ${totalRecords} mục!`,
                            'success'
                        );
                    }
                } else {
                    resultsContainer.html(`
                        <div class="text-center p-8 text-red-500">
                            <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                            <p class="font-semibold">${response.message || 'Có lỗi xảy ra.'}</p>
                        </div>
                    `);
                    summaryContainer.html('');
                }
            })
            .always(function() {
                button.prop('disabled', false).html(originalHtml);
            });
    }

    function handleExportExcel() {
        const checkedRows = $('#report-table tbody .row-checkbox:checked');
        
        if (checkedRows.length === 0) {
            App.showMessageModal('Vui lòng chọn ít nhất một mục để xuất file Excel.', 'warning');
            return;
        }

        const button = exportExcelBtn;
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');

        try {
            const excelData = [];
            
            excelData.push([
                'Trạng Thái', 'Số Báo Giá', 'Ngày Báo Giá', 'Khách Hàng', 'Dự Án', 
                'Mã Hàng', 'Tên Sản Phẩm', 'SL Báo Giá', 'SL Đã Giao', 'SL Còn Lại', '% Hoàn Thành',
                'Đợt Giao', 'Ngày Giao', 'Số Lượng Giao', 'Số BBGH', 'Số Phiếu Xuất'
            ]);

            checkedRows.each(function() {
                const index = $(this).data('index');
                const item = reportData[index];

                if (item.products && item.products.length > 0) {
                    item.products.forEach(product => {
                        const baseRow = [
                            item.status, item.SoBaoGia, formatDate(item.NgayBaoGia), item.TenCongTy, item.TenDuAn || '',
                            product.MaHang, product.TenSanPham, product.SoLuongBaoGia, product.TongSLDaGiao, product.SoLuongConLai, 
                            item.status === 'Đã chốt' ? `${product.PhanTramHoanThanh}%` : 'N/A'
                        ];

                        if (product.deliveries && product.deliveries.length > 0) {
                             product.deliveries.forEach((delivery, dIdx) => {
                                excelData.push([
                                    ...baseRow,
                                    `Đợt ${dIdx + 1}`, formatDate(delivery.NgayGiao), delivery.SoLuongGiao, delivery.SoBBGH || '', delivery.SoPhieuXuat || ''
                                ]);
                            });
                        } else {
                             excelData.push([
                                ...baseRow,
                                item.status === 'Đã chốt' ? 'Chưa giao' : 'N/A', '', '', '', ''
                            ]);
                        }
                    });
                }
                // Thêm một dòng trống để phân cách các báo giá
                excelData.push([]);
            });


            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(excelData);

            ws['!cols'] = [
                { wch: 12 }, { wch: 15 }, { wch: 12 }, { wch: 30 }, { wch: 30 },
                { wch: 18 }, { wch: 35 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 },
                { wch: 10 }, { wch: 12 }, { wch: 10 }, { wch: 15 }, { wch: 15 }
            ];
            
            XLSX.utils.book_append_sheet(wb, ws, 'BaoCaoBanHang');
            const fileName = `BaoCao_BanHang_${new Date().toISOString().slice(0,10)}.xlsx`;
            XLSX.writeFile(wb, fileName);
            
            App.showMessageModal(`Xuất Excel thành công ${checkedRows.length} mục đã chọn!`, 'success');
        } catch (error) {
            console.error('Lỗi xuất Excel:', error);
            App.showMessageModal('Có lỗi khi xuất Excel. Vui lòng thử lại.', 'error');
        } finally {
            button.prop('disabled', false).html(originalHtml);
        }
    }

    function handleResetFilters() {
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        startDateInput.val(startOfMonth.toISOString().split('T')[0]);
        endDateInput.val(today.toISOString().split('T')[0]);
        customerSelect.val('');
        productCodeInput.val('');
        projectNameInput.val('');
        $('input[name="delivery_status"][value="all"]').prop('checked', true);
        $('input[name="quote_status"][value="all"]').prop('checked', true);
        
        reportData = [];
        
        summaryContainer.html('');
        resultsContainer.html(`
            <div class="bg-white rounded-xl shadow-lg p-6">
                <p class="text-center text-gray-500 py-8">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Vui lòng chọn bộ lọc và nhấn "Xem Báo Cáo" để bắt đầu.
                </p>
            </div>
        `);
    }

    // =================================================================
    // KHỞI CHẠY
    // =================================================================
    function initializePage() {
        const today = new Date();
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        startDateInput.val(startOfMonth.toISOString().split('T')[0]);
        endDateInput.val(today.toISOString().split('T')[0]);

        if (App.customerList && App.customerList.length > 0) {
            const uniqueCustomers = [...new Map(App.customerList.map(item => [item['CongTyID'], item])).values()];
            uniqueCustomers.sort((a, b) => a.TenCongTy.localeCompare(b.TenCongTy, 'vi'));
            
            uniqueCustomers.forEach(c => {
                customerSelect.append(`<option value="${c.CongTyID}">${c.TenCongTy}</option>`);
            });
        }
        
        viewReportBtn.on('click', handleViewReport);
        exportExcelBtn.on('click', handleExportExcel);
        resetFiltersBtn.on('click', handleResetFilters);
    }

    initializePage();
}

$(document).ready(function() {
    if (typeof initializeInventorySalesReportPage === 'function') {
        initializeInventorySalesReportPage();
    }
});
