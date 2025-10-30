function initializeProductionManagementPage(mainContentContainer) {
    // --- BIẾN TRẠNG THÁI ---
    let currentPage = 1;
    let currentStatusType = 'inprogress'; // 'inprogress', 'completed', or 'overdue'
    let currentStartDate = '';
    let currentEndDate = '';

    // --- CÁC HÀM TIỆN ÍCH ---
    function formatNumber(num) {
        if (num === null || num === undefined) return 0;
        let numStr = String(num);
        let parts = numStr.split('.');
        parts[0] = parts[0].replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        return parts.join('.');
    }
    
    function updateRemainingQtyDisplay() {
        const required = parseFloat($('#required-qty').text().replace(/,/g, '')) || 0;
        const produced = parseFloat($('#produced-qty').text().replace(/,/g, '')) || 0;
        const newQuantity = parseFloat($('#daily-prod-quantity').val()) || 0;
        const difference = (produced + newQuantity) - required;
        const remainingEl = $('#remaining-qty-container');

        if (difference < 0) {
            remainingEl.html(`<span class="font-bold text-lg text-red-600">Còn thiếu: ${formatNumber(Math.abs(difference))} (chưa đủ)</span>`);
        } else if (difference === 0) {
            remainingEl.html(`<span class="font-bold text-lg text-green-600">Đã đủ</span>`);
        } else {
            remainingEl.html(`<span class="font-bold text-lg text-blue-600">Thừa: +${formatNumber(difference)}</span>`);
        }
    }
    
    // --- HÀM CẬP NHẬT GIAO DIỆN ---
    function updateTabCounts(counts) {
        if (!counts) return;
        
        for (const key in counts) {
            const count = counts[key];
            const bubble = $(`.tab-btn[data-tab="${key}"] .tab-count-bubble`);
            if (count > 0) {
                bubble.text(count).show();
            } else {
                bubble.hide();
            }
        }
    }
    
    /**
     * TẠO GIAO DIỆN CHI TIẾT CỦA 1 LSX
     */
    function createDetailedViewHtml(p_order) {
        const { info, items } = p_order;
        const ngayYeuCau = info.NgayYCSX ? new Date(info.NgayYCSX).toLocaleDateString('vi-VN') : 'N/A';
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
        
        let itemsTableHtml = `
        <table class="min-w-full divide-y divide-gray-200 mt-4 text-sm">
            <thead style="background-color: #92D050;">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Stt.</th>
                    <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Mã hàng</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Khối lượng SX</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Đã SX</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Còn lại</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Đơn vị</th>
                    <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Mục đích</th>
                    <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase w-40">Trạng thái</th>
                    <th class="px-4 py-2 text-left text-xs font-bold text-black uppercase">Ghi chú</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-black uppercase">Hành động</th>
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

            const soLuongHienThi = item.MaBTP.startsWith('ULA') ? item.SoLuongBoCanSX : item.SoLuongCayCanSX;
            
            const soLuongDaSanXuat = item.SoLuongDaSanXuat || 0;
            const soLuongConLai = soLuongHienThi - soLuongDaSanXuat;
            
            const mucDich = info.SoYCSX ? 'Đơn hàng' : 'Lưu kho';

            itemsTableHtml += `
            <tr class="production-item-row" data-id="${item.ChiTiet_LSX_ID}">
                <td class="px-4 py-2">${index + 1}</td>
                <td class="px-4 py-2 font-medium text-gray-900">${item.MaBTP}</td>
                <td class="px-4 py-2 text-center font-semibold">${formatNumber(soLuongHienThi)}</td>
                <td class="px-4 py-2 text-center font-semibold text-green-600">${formatNumber(soLuongDaSanXuat)}</td>
                <td class="px-4 py-2 text-center font-bold text-red-600">${formatNumber(soLuongConLai)}</td>
                <td class="px-4 py-2 text-center">${item.DonViTinh || 'N/A'}</td>
                <td class="px-4 py-2">${mucDich}</td>
                <td class="px-4 py-2">${statusDropdown}</td>
                <td class="px-4 py-2">
                    <input type="text" data-field="GhiChu" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm" value="${item.GhiChu || ''}">
                </td>
                <td class="px-4 py-2 text-center">
                    <button class="report-daily-production-btn bg-teal-500 hover:bg-teal-600 text-white font-bold py-1 px-2 rounded-md text-xs" 
                            data-detail-id="${item.ChiTiet_LSX_ID}" 
                            data-mabtp="${item.MaBTP}"
                            data-required-quantity="${soLuongHienThi}">
                        <i class="fas fa-plus-circle mr-1"></i>Báo cáo
                    </button>
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
        </div>`;
        
        const nguoiNhanSX = info.NguoiNhanSX || 'Mr. Thiết';
        const boPhanSX = info.BoPhanSX || 'Đội trưởng SX';

        const donHangGocHtml = info.SoYCSX
            ? `<p><span class="text-gray-500 w-24 inline-block">Đơn hàng gốc:</span><span class="font-semibold">${info.SoYCSX}</span></p>`
            : `<p><span class="text-gray-500 w-24 inline-block">Loại yêu cầu:</span><span class="font-semibold text-blue-600">Sản xuất lưu kho</span></p>`;

        return `
        <div class="bg-white p-4" id="form-lsx-${info.LenhSX_ID}">
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
                        ${donHangGocHtml}
                        <p><span class="text-gray-500 w-24 inline-block">Người yêu cầu:</span><span class="font-semibold">${info.NguoiYeuCauDisplay || 'N/A'}</span></p>
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
     * HÀM RENDER CHÍNH
     */
    function renderGroupedProductionOrders(orders, container) {
        container.empty();
        if (!orders || orders.length === 0) {
            let message = 'Không có lệnh sản xuất nào phù hợp với bộ lọc.';
             if (!currentStartDate && !currentEndDate) {
                switch(currentStatusType) {
                    case 'inprogress': message = 'Không có Lệnh sản xuất nào đang xử lý.'; break;
                    case 'completed': message = 'Không có Lệnh sản xuất nào đã hoàn thành hoặc hủy.'; break;
                    case 'overdue': message = 'Không có Lệnh sản xuất nào bị quá hạn.'; break;
                }
            }
            container.html(`<div class="bg-white p-6 rounded-lg shadow-sm text-center text-gray-500">${message}</div>`);
            return;
        }

        const groupedByDonHangGoc = orders.reduce((acc, order) => {
            const key = order.info.GroupingKey;
            if (!acc[key]) {
                acc[key] = {
                    donHangInfo: { 
                        GroupingKey: key, 
                        TenCongTyDisplay: order.info.TenCongTyDisplay 
                    },
                    productionOrders: []
                };
            }
            acc[key].productionOrders.push(order);
            return acc;
        }, {});

        for (const key in groupedByDonHangGoc) {
            const group = groupedByDonHangGoc[key];
            const groupWrapper = $('<div class="bg-white rounded-lg shadow-sm mb-4"></div>');
            let groupTitleHtml = group.donHangInfo.GroupingKey === 'Sản xuất lưu kho'
                ? `<span class="font-semibold text-blue-700">Loại: ${group.donHangInfo.GroupingKey}</span>`
                : `<span class="font-semibold text-gray-800">Đơn hàng gốc: ${group.donHangInfo.GroupingKey}</span><span class="font-normal text-gray-600 ml-2">- ${group.donHangInfo.TenCongTyDisplay}</span>`;
            
            const groupHeaderHtml = `<div class="group-header font-bold p-3 flex justify-between items-center text-base rounded-t-lg" style="background-color: #eef2f9;"><div>${groupTitleHtml}</div></div>`;
            groupWrapper.append(groupHeaderHtml);

            group.productionOrders.forEach(p_order => {
                const { info } = p_order;
                let statusText, statusColor, actionButtonsHtml = '';
                
                // [LOGIC SỬA ĐỔI] Tách biệt các trạng thái để hiển thị nút chính xác
                if (info.TrangThai === 'Hoàn thành') {
                    statusText = 'LSX đã hoàn thành'; 
                    statusColor = 'text-green-600';
                    
                    const isBtpReadyForRequest = (info.LoaiLSX === 'BTP' && info.TrangThaiChuanBiHang === 'Đã SX xong');
                    const isUlaReadyForRequest = (info.LoaiLSX === 'ULA' && info.TrangThaiChuanBiHangULA === 'Đã SX xong ULA');

                    if (isBtpReadyForRequest || isUlaReadyForRequest) {
                        actionButtonsHtml = `
                        <button class="initiate-nhapkho-btn text-xs font-bold py-1 px-3 rounded-md bg-purple-600 hover:bg-purple-700 text-white" 
                                data-cbh-id="${info.CBH_ID}" 
                                data-loai-lsx="${info.LoaiLSX}">
                            <i class="fas fa-paper-plane mr-1"></i> Yêu cầu nhập kho
                        </button>`;
                    } else {
                       const trangThaiCBH = info.LoaiLSX === 'BTP' ? info.TrangThaiChuanBiHang : info.TrangThaiChuanBiHangULA;
                       actionButtonsHtml = `<span class="text-xs text-gray-500" title="Trạng thái phiếu CBH">${trangThaiCBH || 'Đang xử lý...'}</span>`;
                    }

                } else if (info.TrangThai === 'Hủy') {
                    statusText = 'Đã hủy';
                    statusColor = 'text-gray-500';
                } else { // Các trạng thái đang xử lý
                    switch (info.TrangThai) {
                        case 'Chờ duyệt':
                            statusText = 'Chờ duyệt'; statusColor = 'text-orange-600';
                            actionButtonsHtml = `
                            <button class="update-status-btn text-xs font-bold py-1 px-3 rounded-md bg-green-500 hover:bg-green-600 text-white" data-id="${info.LenhSX_ID}" data-status="Đã duyệt (đang sx)">Duyệt</button>
                            <button class="update-status-btn text-xs font-bold py-1 px-3 rounded-md bg-red-500 hover:bg-red-600 text-white ml-1" data-id="${info.LenhSX_ID}" data-status="Hủy">Hủy</button>`;
                            break;
                        case 'Đã duyệt (đang sx)':
                            statusText = 'Đã duyệt (đang sx)'; statusColor = 'text-blue-600';
                            actionButtonsHtml = `
                            <button class="update-status-btn text-xs font-bold py-1 px-3 rounded-md bg-blue-600 hover:bg-blue-700 text-white" data-id="${info.LenhSX_ID}" data-status="Hoàn thành">Hoàn thành</button>
                            <button class="update-status-btn text-xs font-bold py-1 px-3 rounded-md bg-red-500 hover:bg-red-600 text-white ml-1" data-id="${info.LenhSX_ID}" data-status="Hủy">Hủy</button>`;
                            break;
                        default:
                            statusText = info.TrangThai;
                            statusColor = 'text-gray-700';
                            break;
                    }
                }

                let dateHeader2 = 'Ngày HT Ước Tính';
                let dateValue2 = info.NgayHoanThanhUocTinh ? new Date(info.NgayHoanThanhUocTinh).toLocaleDateString('vi-VN') : 'N/A';
                if (info.TrangThai === 'Hoàn thành' && info.NgayHoanThanhThucTe) {
                    dateHeader2 = 'Ngày HT Thực Tế';
                    dateValue2 = new Date(info.NgayHoanThanhThucTe).toLocaleDateString('vi-VN');
                }

                let overdueHtml = '';
                if (info.TrangThai !== 'Hoàn thành' && info.TrangThai !== 'Hủy') {
                    let overdueText = info.TinhTrangQuaHan;
                    let overdueTextColor = 'text-gray-800', overdueBgColor = 'bg-gray-100';
                    switch (info.TinhTrangQuaHan) {
                        case 'Quá hạn':
                            overdueTextColor = 'text-red-800'; overdueBgColor = 'bg-red-100';
                            overdueText = `Quá hạn ${Math.abs(info.SoNgayConLai)} ngày`;
                            break;
                        case 'Sắp tới hạn':
                            overdueTextColor = 'text-yellow-800'; overdueBgColor = 'bg-yellow-100';
                            overdueText = `Còn ${info.SoNgayConLai} ngày`;
                            break;
                        case 'Trong hạn':
                            overdueTextColor = 'text-green-800'; overdueBgColor = 'bg-green-100';
                            overdueText = `Còn ${info.SoNgayConLai} ngày`;
                            break;
                        case 'Chưa có kế hoạch':
                             overdueText = 'Chưa có kế hoạch';
                             break;
                    }
                    overdueHtml = `<div class="col-span-2"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${overdueBgColor} ${overdueTextColor}">${overdueText}</span></div>`;
                } else {
                    overdueHtml = `<div class="col-span-2"></div>`;
                }

                const accordionHeaderHtml = `
                <div class="accordion-header grid grid-cols-12 gap-x-4 items-center p-3 text-sm cursor-pointer hover:bg-gray-50 border-t">
                    <div class="col-span-2 font-semibold text-gray-800 flex items-center"><i class="fas fa-chevron-right accordion-icon text-gray-400 text-xs mr-3 transition-transform"></i>${info.SoLenhSX}</div>
                    <div class="col-span-2 font-medium ${statusColor}">${statusText}</div>
                    ${overdueHtml}
                    <div class="col-span-2">
                        <span class="text-xs text-gray-500">Ngày tạo</span>
                        <div class="font-semibold text-gray-800">${new Date(info.NgayTao).toLocaleDateString('vi-VN')}</div>
                    </div>
                    <div class="col-span-2">
                        <span class="text-xs text-gray-500">${dateHeader2}</span>
                        <div class="font-semibold text-gray-800">${dateValue2}</div>
                    </div>
                    <div class="col-span-2 text-right">${actionButtonsHtml}</div>
                </div>`;
                
                const accordionContentHtml = `<div class="accordion-content border-t border-gray-200" style="display: none;">${createDetailedViewHtml(p_order)}</div>`;
                groupWrapper.append(`<div class="order-accordion">${accordionHeaderHtml}${accordionContentHtml}</div>`);
            });
            container.append(groupWrapper);
        }
    }
    
    /**
     * HÀM RENDER PHÂN TRANG
     */
    function renderPagination(pagination, container) {
        container.empty();
        if (!pagination || pagination.totalPages <= 1) return;

        let paginationHtml = '<nav><ul class="inline-flex items-center -space-x-px shadow-sm">';
        
        const prevDisabled = pagination.page <= 1 ? 'pointer-events-none opacity-50' : '';
        paginationHtml += `<li><a href="#" class="page-link py-2 px-3 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 ${prevDisabled}" data-page="${pagination.page - 1}">&laquo; Trước</a></li>`;

        for (let i = 1; i <= pagination.totalPages; i++) {
            const activeClass = i === pagination.page 
                ? 'z-10 py-2 px-3 leading-tight text-blue-600 bg-blue-50 border border-blue-300 hover:bg-blue-100 hover:text-blue-700' 
                : 'py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700';
            paginationHtml += `<li><a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a></li>`;
        }

        const nextDisabled = pagination.page >= pagination.totalPages ? 'pointer-events-none opacity-50' : '';
        paginationHtml += `<li><a href="#" class="page-link py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 ${nextDisabled}" data-page="${pagination.page + 1}">Tiếp &raquo;</a></li>`;

        paginationHtml += '</ul></nav>';
        container.html(paginationHtml);
    }


    /**
     * HÀM TẢI DỮ LIỆU
     */
    function loadData() {
        let container, paginationContainer;

        if (currentStatusType === 'inprogress') {
            container = $('#orders-container-inprogress');
            paginationContainer = $('#pagination-container-inprogress');
        } else if (currentStatusType === 'completed') {
            container = $('#orders-container-completed');
            paginationContainer = $('#pagination-container-completed');
        } else if (currentStatusType === 'overdue') {
            container = $('#orders-container-overdue');
            paginationContainer = $('#pagination-container-overdue');
        }
        
        container.html('<div class="text-center p-8"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i><p class="mt-2 text-gray-500">Đang tải dữ liệu...</p></div>');
        paginationContainer.empty();
        
        let url = `api/get_production_data_btp.php?status_type=${currentStatusType}&page=${currentPage}`;
        if (currentStartDate) url += `&start_date=${currentStartDate}`;
        if (currentEndDate) url += `&end_date=${currentEndDate}`;
        
        $.ajax({
            url: url,
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    updateTabCounts(res.tab_counts);
                    renderGroupedProductionOrders(res.orders, container);
                    renderPagination(res.pagination, paginationContainer);
                } else {
                    container.html(`<div class="bg-white p-6 rounded-lg shadow-sm text-center text-red-500">Lỗi: ${res.message}</div>`);
                }
            },
            error: (xhr) => {
                console.error("Lỗi AJAX:", xhr.responseText);
                container.html(`<div class="bg-white p-6 rounded-lg shadow-sm text-center text-red-500">Lỗi kết nối hoặc xử lý dữ liệu.</div>`);
            }
        });
    }

    /**
     * HÀM GÁN SỰ KIỆN
     */
    function setupEventListeners() {
        mainContentContainer.off('.production');

        // Chuyển tab
        mainContentContainer.on('click.production', '.tab-btn', function () {
            const tab = $(this).data('tab');
            if (tab !== currentStatusType) {
                currentStatusType = tab;
                currentPage = 1; 
                loadData();
            }
            
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-pane').addClass('hidden');
            $(`#tab-content-${tab}`).removeClass('hidden');
        });

        // Lọc dữ liệu
        mainContentContainer.on('click.production', '#apply-filter-btn', function() {
            currentStartDate = $('#start-date-filter').val();
            currentEndDate = $('#end-date-filter').val();
            currentPage = 1;
            loadData();
        });

        // Xóa bộ lọc
        mainContentContainer.on('click.production', '#clear-filter-btn', function() {
            $('#start-date-filter').val('');
            $('#end-date-filter').val('');

            currentStartDate = '';
            currentEndDate = '';
            currentPage = 1;
            loadData();
        });
        
        // Phân trang
        mainContentContainer.on('click.production', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page !== currentPage) {
                currentPage = parseInt(page);
                loadData();
            }
        });

        // Mở/đóng accordion
        mainContentContainer.on('click.production', '.accordion-header', function(e) {
            if ($(e.target).is('button, a, input, span') || $(e.target).parent().is('button, a, input, span')) return;
            $(this).closest('.order-accordion').toggleClass('open').find('.accordion-content').slideToggle(250);
        });
        
         mainContentContainer.on('click.production', '.update-status-btn', function() {
            const btn = $(this);
            const lenhSX_ID = btn.data('id');
            const newStatus = btn.data('status');
            const originalText = btn.html();
            
            if (confirm(`Bạn có chắc muốn chuyển trạng thái thành "${newStatus}"?`)) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                $.ajax({
                    url: 'api/update_production_status.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ lenhSX_ID, status: newStatus }),
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            alert(res.message);
                            loadData(); 
                        } else {
                            alert('Lỗi: ' + res.message);
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: () => {
                        alert('Lỗi kết nối server.');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            }
        });
        
        mainContentContainer.on('click.production', '.initiate-nhapkho-btn', function() {
            const btn = $(this);
            const cbh_id = btn.data('cbh-id');
            const loai_lsx = btn.data('loai-lsx'); 
            const originalText = btn.html();

            if (confirm('Bạn có chắc muốn gửi yêu cầu nhập kho cho bộ phận Chuẩn bị hàng?')) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                $.ajax({
                    url: 'api/update_chuanbihang_to_nhapkho.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ cbh_id: cbh_id, loai_lsx: loai_lsx }),
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            alert(res.message);
                            loadData();
                        } else {
                            alert('Lỗi: ' + res.message);
                            btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: () => {
                        alert('Lỗi kết nối server.');
                        btn.prop('disabled', false).html(originalText);
                    }
                });
            }
        });


        mainContentContainer.on('click.production', '.update-details-btn', function() {
            const btn = $(this);
            const lenhSX_ID = btn.data('id');
            const formContainer = $(`#form-lsx-${lenhSX_ID}`);
            const originalText = btn.html();
            
            const nguoiNhanSX = formContainer.find('[data-field="NguoiNhanSX"]').val();
            const boPhanSX = formContainer.find('[data-field="BoPhanSX"]').val();
            const ngayHoanThanh = formContainer.find('[data-field="NgayHoanThanhUocTinh"]').val() || null;

            const itemsToUpdate = [];
            formContainer.find('.production-item-row').each(function() {
                const row = $(this);
                itemsToUpdate.push({
                    ChiTiet_LSX_ID: row.data('id'),
                    TrangThai: row.find('[data-field="TrangThai"]').val(),
                    GhiChu: row.find('[data-field="GhiChu"]').val()
                });
            });

            if (confirm('Bạn có chắc muốn lưu các thay đổi này?')) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
                $.ajax({
                    url: 'api/update_production_details.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        LenhSX_ID: lenhSX_ID,
                        NguoiNhanSX: nguoiNhanSX,
                        BoPhanSX: boPhanSX,
                        NgayHoanThanhUocTinh: ngayHoanThanh,
                        items: itemsToUpdate
                    }),
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            alert('Cập nhật thành công!');
                            loadData();
                        } else {
                            alert('Lỗi: ' + res.message);
                        }
                    },
                    error: () => alert('Lỗi kết nối server.'),
                    complete: () => btn.prop('disabled', false).html(originalText)
                });
            }
        });
        
         mainContentContainer.on('change.production', 'input[data-field="NgayHoanThanhUocTinh"]', function() {
            const inputNgayHT = $(this);
            const formContainer = inputNgayHT.closest('[id^="form-lsx-"]');
            const ngayYeuCauStr = formContainer.find('[data-ngay-yeu-cau]').data('ngay-yeu-cau');
            const ngayHoanThanhStr = inputNgayHT.val();
            
            if (ngayYeuCauStr && ngayHoanThanhStr) {
                const ngayYC = new Date(ngayYeuCauStr);
                const ngayHT = new Date(ngayHoanThanhStr);
                if (!isNaN(ngayYC.getTime()) && !isNaN(ngayHT.getTime())) {
                    const diffTime = Math.abs(ngayHT - ngayYC);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    formContainer.find('[id^="tong-ngay-text-"]').text(`(Tổng ngày: ${diffDays} ngày)`);
                } else {
                    formContainer.find('[id^="tong-ngay-text-"]').text('');
                }
            } else {
                 formContainer.find('[id^="tong-ngay-text-"]').text('');
            }
        });

        mainContentContainer.on('click.production', '.export-btn-excel', function() {
            const lenhSX_ID = $(this).data('id');
            if (lenhSX_ID) {
                window.location.href = `api/export_production_order_excel.php?id=${lenhSX_ID}`;
            }
        });

        mainContentContainer.on('click.production', '.export-btn-pdf', function() {
            const lenhSX_ID = $(this).data('id');
            if (lenhSX_ID) {
                window.open(`api/export_production_order_pdf.php?id=${lenhSX_ID}`, '_blank');
            }
        });


mainContentContainer.on('click.production', '.initiate-nhapkho-btn', function() {
        const btn = $(this);
        const cbh_id = btn.data('cbh-id');
        const loai_lsx = btn.data('loai-lsx'); 
        const originalText = btn.html();

        if (confirm('Bạn có chắc muốn gửi yêu cầu nhập kho cho bộ phận Chuẩn bị hàng?')) {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            $.ajax({
                url: 'api/update_chuanbihang_to_nhapkho.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ cbh_id: cbh_id, loai_lsx: loai_lsx }),
                dataType: 'json',
                success: (res) => {
                    if (res.success) {
                        alert(res.message);
                        loadData();
                    } else {
                        alert('Lỗi: ' + res.message);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: () => {
                    alert('Lỗi kết nối server.');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        }
    });
        mainContentContainer.on('click.production', '.report-daily-production-btn', function() {
            const btn = $(this);
            const detailId = btn.data('detail-id');
            const maBTP = btn.data('mabtp');
            const requiredQuantity = parseFloat(btn.data('required-quantity')) || 0;
            const today = new Date().toISOString().split('T')[0];

            $('body').append('<div id="modal-loader" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center"><i class="fas fa-spinner fa-spin text-white text-4xl"></i></div>');

            $.ajax({
                url: `api/add_daily_production_log.php?chiTiet_LSX_ID=${detailId}`,
                method: 'GET',
                dataType: 'json',
                success: (res) => {
                    $('#modal-loader').remove();
                    if (res.success) {
                        const producedQuantity = res.produced || 0;
                        
                        const modalHtml = `
                            <div id="daily-production-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                                <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Báo cáo sản lượng cho: <span class="font-bold text-blue-600">${maBTP}</span></h3>
                                    <div class="space-y-3 text-sm">
                                        <div class="grid grid-cols-3 items-center">
                                            <label class="font-medium text-gray-700">Ngày sản xuất</label>
                                            <div class="col-span-2">
                                                <input type="date" id="daily-prod-date" class="block w-full border-gray-300 rounded-md shadow-sm" value="${today}">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 items-center p-2 bg-gray-50 rounded-md">
                                            <label class="font-medium text-gray-500">Số lượng YC:</label>
                                            <span id="required-qty" class="col-span-2 font-bold text-lg text-blue-600">${formatNumber(requiredQuantity)}</span>
                                        </div>
                                        <div class="grid grid-cols-3 items-center p-2 bg-gray-50 rounded-md">
                                            <label class="font-medium text-gray-500">Số lượng đã SX:</label>
                                            <span id="produced-qty" class="col-span-2 font-bold text-lg text-green-600">${formatNumber(producedQuantity)}</span>
                                        </div>
                                        <div class="grid grid-cols-3 items-center">
                                            <label for="daily-prod-quantity" class="font-medium text-gray-700">Nhập số lượng</label>
                                            <div class="col-span-2">
                                                <input type="number" id="daily-prod-quantity" class="block w-full border-gray-300 rounded-md shadow-sm text-lg font-semibold" placeholder="Nhập số lượng..." autofocus>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 items-center p-2 bg-yellow-50 rounded-md">
                                            <label class="font-medium text-gray-500">Tình trạng:</label>
                                            <div id="remaining-qty-container" class="col-span-2"></div>
                                        </div>
                                        <div>
                                            <label for="daily-prod-notes" class="block font-medium text-gray-700">Ghi chú</label>
                                            <textarea id="daily-prod-notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                                        </div>
                                    </div>
                                    <div class="items-center px-4 py-3 mt-4 text-right space-x-2 border-t">
                                        <button id="cancel-daily-prod" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 font-semibold">Hủy</button>
                                        <button id="save-daily-prod" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 font-semibold">Lưu</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('body').append(modalHtml);

                        updateRemainingQtyDisplay();

                        $('#daily-prod-quantity').on('input', updateRemainingQtyDisplay);

                        $('#cancel-daily-prod').on('click', () => $('#daily-production-modal').remove());

                        $('#save-daily-prod').on('click', function() {
                            const saveBtn = $(this);
                            const originalText = saveBtn.html();
                            const data = {
                                chiTiet_LSX_ID: detailId,
                                ngayBaoCao: $('#daily-prod-date').val(),
                                soLuong: parseFloat($('#daily-prod-quantity').val()),
                                ghiChu: $('#daily-prod-notes').val()
                            };

                            if (!data.ngayBaoCao || !data.soLuong || data.soLuong <= 0) {
                                alert('Vui lòng nhập ngày và số lượng hoàn thành hợp lệ!');
                                return;
                            }

                            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

                            $.ajax({
                                url: 'api/add_daily_production_log.php',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify(data),
                                dataType: 'json',
                                success: (res) => {
                                    alert(res.message);
                                    if(res.success) {
                                        $('#daily-production-modal').remove();
                                        loadData();
                                    }
                                },
                                error: () => alert('Lỗi kết nối server.'),
                                complete: () => saveBtn.prop('disabled', false).html(originalText)
                            });
                        });

                    } else {
                        alert('Lỗi: ' + (res.message || 'Không thể tải dữ liệu sản lượng.'));
                    }
                },
                error: () => {
                    $('#modal-loader').remove();
                    alert('Lỗi kết nối server khi tải dữ liệu sản lượng.');
                }
            });
        });

    }

    // --- KHỞI CHẠY ---
    loadData();
    setupEventListeners();
}
