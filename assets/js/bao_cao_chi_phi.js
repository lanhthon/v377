function initializeBaoCaoChiPhiPage(mainContentContainer) {
    let table;
    let summaryData = [];
    let chartInstance = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND' 
        }).format(amount);
    }

    async function fetchAPI(url, options) {
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok || (result && result.success === false)) {
                throw new Error(result.message || 'Lỗi từ máy chủ');
            }
            return result;
        } catch (error) {
            showToast(error.message, 'error');
            throw error;
        }
    }

    async function loadBaoCaoChiPhi(filters = {}) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const result = await fetchAPI(`api/get_bao_cao_chi_phi.php?${queryParams}`);
            showToast(`Tải thành công ${result.data.length} bản ghi!`, "success");
            
            // Cập nhật thống kê tổng
            updateSummaryStats(result.summary || [], result.totalAmount || 0);
            
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu: " + error.message, "error");
            return [];
        }
    }

    function updateSummaryStats(summary, totalAmount) {
        summaryData = summary;
        
        // Cập nhật các thẻ thống kê
        document.getElementById('total-expense').textContent = formatCurrency(totalAmount);
        document.getElementById('total-records').textContent = summary.reduce((sum, item) => sum + parseInt(item.SoLuong || 0), 0);
        document.getElementById('total-categories').textContent = summary.length;
        
        // Tìm loại chi phí cao nhất
        if (summary.length > 0) {
            const highest = summary.reduce((max, item) => 
                parseFloat(item.TongTien) > parseFloat(max.TongTien) ? item : max
            );
            document.getElementById('highest-category').textContent = highest.TenLoaiCP || 'N/A';
        } else {
            document.getElementById('highest-category').textContent = 'N/A';
        }
        
        // Hiển thị danh sách tổng hợp
        renderSummaryList(summary);
        
        // Vẽ biểu đồ
        renderChart(summary);
    }

    function renderSummaryList(summary) {
        const container = document.getElementById('summary-list');
        if (summary.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Không có dữ liệu</p>';
            return;
        }

        let html = '<table style="width: 100%; font-size: 13px;">';
        html += '<thead><tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
        html += '<th style="padding: 10px; text-align: left;">Loại Chi Phí</th>';
        html += '<th style="padding: 10px; text-align: right;">Số Lượng</th>';
        html += '<th style="padding: 10px; text-align: right;">Tổng Tiền</th>';
        html += '<th style="padding: 10px; text-align: right;">Tỷ Lệ</th>';
        html += '</tr></thead><tbody>';

        summary.forEach((item, index) => {
            const bgColor = index % 2 === 0 ? '#fff' : '#f8f9fa';
            html += `<tr style="background: ${bgColor}; border-bottom: 1px solid #dee2e6;">`;
            html += `<td style="padding: 8px;">${item.TenLoaiCP}</td>`;
            html += `<td style="padding: 8px; text-align: right;">${item.SoLuong}</td>`;
            html += `<td style="padding: 8px; text-align: right; font-weight: bold;">${formatCurrency(item.TongTien)}</td>`;
            html += `<td style="padding: 8px; text-align: right; color: #007bff;">${item.TyLe}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function renderChart(summary) {
        const ctx = document.getElementById('expense-chart');
        if (!ctx) return;
        
        // Hủy biểu đồ cũ nếu có
        if (chartInstance) {
            chartInstance.destroy();
        }

        if (summary.length === 0) {
            ctx.style.display = 'none';
            return;
        }

        ctx.style.display = 'block';
        
        // Chuẩn bị dữ liệu cho biểu đồ
        const labels = summary.map(item => item.TenLoaiCP || 'Khác');
        const data = summary.map(item => parseFloat(item.TongTien || 0));
        const colors = generateColors(summary.length);
        
        chartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Chi phí',
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = formatCurrency(context.parsed);
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function generateColors(count) {
        const colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#E7E9ED', '#8E5EA2', '#3cba9f', '#e8c3b9',
            '#c45850', '#3e95cd', '#8e5ea2', '#3cba9f', '#c45850'
        ];
        return colors.slice(0, count);
    }

    function createColumnDefinitions() {
        return [
            { title: "Số PC", field: "SoPhieuChi", width: 150 },
            { title: "Ngày chi", field: "NgayChi", width: 120 },
            { title: "Loại chi phí", field: "TenLoaiCP", minWidth: 180 },
            { title: "Đối tượng", field: "TenDoiTuong", minWidth: 200 },
            { title: "Lý do chi", field: "LyDoChi", minWidth: 250 },
            { 
                title: "Số tiền", 
                field: "SoTien", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => formatCurrency(cell.getValue()),
                bottomCalc: "sum",
                bottomCalcFormatter: (cell) => `<strong>${formatCurrency(cell.getValue())}</strong>`
            },
            {
                title: "Hình thức",
                field: "HinhThucThanhToan",
                width: 120,
                formatter: (cell) => {
                    const val = cell.getValue();
                    const labels = {
                        'tien_mat': 'Tiền mặt',
                        'chuyen_khoan': 'Chuyển khoản',
                        'séc': 'Séc'
                    };
                    return labels[val] || val;
                }
            },
            {
                title: "Trạng thái",
                field: "TrangThai",
                width: 120,
                formatter: (cell) => {
                    const val = cell.getValue();
                    const labels = {
                        'cho_duyet': 'Chờ duyệt',
                        'da_duyet': 'Đã duyệt',
                        'da_huy': 'Đã hủy'
                    };
                    return `<span class="badge ${val}">${labels[val] || val}</span>`;
                }
            },
            {
                title: "Thao tác",
                hozAlign: "center",
                width: 120,
                headerSort: false,
                formatter: () => {
                    return `
                        <i class="fa fa-eye" style="cursor:pointer; color:#007bff; margin:0 5px;" title="Xem"></i>
                        <i class="fa fa-print" style="cursor:pointer; color:#6c757d; margin:0 5px;" title="In"></i>
                    `;
                },
                cellClick: (e, cell) => {
                    const data = cell.getRow().getData();
                    if (e.target.classList.contains('fa-eye')) {
                        viewDetail(data);
                    } else if (e.target.classList.contains('fa-print')) {
                        printPhieuChi(data.PhieuChiID);
                    }
                }
            }
        ];
    }

    function printPhieuChi(id) {
        window.open(`print_phieu_chi.php?id=${id}`, '_blank');
    }

    function viewDetail(data) {
        const modalHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Chi Tiết Phiếu Chi: ${data.SoPhieuChi}</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <div style="padding: 20px;">
                    <div class="form-grid">
                        <div><strong>Ngày chi:</strong> ${data.NgayChi}</div>
                        <div><strong>Số tiền:</strong> ${formatCurrency(data.SoTien)}</div>
                        <div><strong>Loại chi phí:</strong> ${data.TenLoaiCP || 'N/A'}</div>
                        <div><strong>Hình thức:</strong> ${data.HinhThucThanhToan}</div>
                        <div><strong>Đối tượng:</strong> ${data.TenDoiTuong}</div>
                        <div><strong>Loại đối tượng:</strong> ${data.LoaiDoiTuong}</div>
                        <div class="full-width"><strong>Địa chỉ:</strong> ${data.DiaChiDoiTuong || '-'}</div>
                        <div class="full-width"><strong>Lý do chi:</strong> ${data.LyDoChi}</div>
                        <div><strong>Người nhận:</strong> ${data.NguoiNhan || '-'}</div>
                        <div><strong>ĐT người nhận:</strong> ${data.DienThoaiNguoiNhan || '-'}</div>
                        ${data.SoTaiKhoan ? `<div><strong>Số TK:</strong> ${data.SoTaiKhoan}</div>` : ''}
                        ${data.NganHang ? `<div><strong>Ngân hàng:</strong> ${data.NganHang}</div>` : ''}
                        ${data.GhiChu ? `<div class="full-width"><strong>Ghi chú:</strong> ${data.GhiChu}</div>` : ''}
                        <div><strong>Trạng thái:</strong> <span class="badge ${data.TrangThai}">${data.TrangThai}</span></div>
                        ${data.NgayDuyet ? `<div><strong>Ngày duyệt:</strong> ${data.NgayDuyet}</div>` : ''}
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="document.getElementById('chi-phi-modal').style.display='none'" 
                            class="action-button" style="background-color: #6c757d;">Đóng</button>
                    <button onclick="window.open('print_phieu_chi.php?id=${data.PhieuChiID}', '_blank')" 
                            class="action-button" style="background-color: #007bff;">
                        <i class="fa fa-print"></i> In
                    </button>
                </div>
            </div>
        `;
        const modal = document.getElementById('chi-phi-modal');
        modal.innerHTML = modalHTML;
        modal.style.display = 'block';
    }

    function getFilters() {
        return {
            dateFrom: document.getElementById('filter-date-from').value,
            dateTo: document.getElementById('filter-date-to').value,
            loaiChiPhi: document.getElementById('filter-loai-chi-phi').value,
            status: document.getElementById('filter-status').value,
            search: document.getElementById('filter-search').value
        };
    }

    async function refreshTable() {
        const data = await loadBaoCaoChiPhi(getFilters());
        table.setData(data);
    }

    function applyFilters() {
        refreshTable();
    }

    function exportToExcel() {
        table.download("xlsx", `bao_cao_chi_phi_${new Date().toISOString().split('T')[0]}.xlsx`, {
            sheetName: "Báo Cáo Chi Phí"
        });
        showToast("Đã xuất file Excel!", "success");
    }

    function exportSummaryToExcel() {
        if (summaryData.length === 0) {
            showToast("Không có dữ liệu để xuất!", "warning");
            return;
        }
        
        // Tạo worksheet từ summaryData
        const ws = XLSX.utils.json_to_sheet(summaryData.map(item => ({
            'Loại Chi Phí': item.TenLoaiCP,
            'Số Lượng': item.SoLuong,
            'Tổng Tiền': parseFloat(item.TongTien),
            'Tỷ Lệ %': item.TyLe
        })));
        
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Tổng Hợp");
        XLSX.writeFile(wb, `tong_hop_chi_phi_${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast("Đã xuất báo cáo tổng hợp!", "success");
    }

    async function loadLoaiChiPhi() {
        try {
            const result = await fetchAPI('api/get_loai_chi_phi.php');
            const select = document.getElementById('filter-loai-chi-phi');
            select.innerHTML = '<option value="">Tất cả loại chi phí</option>';
            if (result.data && result.data.length > 0) {
                result.data.forEach(item => {
                    select.innerHTML += `<option value="${item.MaLoaiCP}">${item.TenLoaiCP}</option>`;
                });
            }
        } catch (error) {
            console.error('Lỗi khi tải loại chi phí:', error);
        }
    }

    function setupEventListeners() {
        const modal = document.getElementById('chi-phi-modal');
        
        document.getElementById('export-btn').onclick = exportToExcel;
        document.getElementById('export-summary-btn').onclick = exportSummaryToExcel;
        
        document.getElementById('filter-date-from').addEventListener('change', applyFilters);
        document.getElementById('filter-date-to').addEventListener('change', applyFilters);
        document.getElementById('filter-loai-chi-phi').addEventListener('change', applyFilters);
        document.getElementById('filter-status').addEventListener('change', applyFilters);
        
        let searchTimeout;
        document.getElementById('filter-search').addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });
        
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'chi-phi-modal') {
                modal.style.display = 'none';
            }
        });
    }

    async function initialize() {
        // Đặt ngày mặc định (tháng hiện tại)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('filter-date-from').value = firstDay.toISOString().split('T')[0];
        document.getElementById('filter-date-to').value = today.toISOString().split('T')[0];

        // Tải danh sách loại chi phí
        await loadLoaiChiPhi();

        // Khởi tạo bảng
        table = new Tabulator("#chi-phi-table", {
            height: "50vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100],
            placeholder: "Đang tải...",
        });

        // Tải dữ liệu
        const tableData = await loadBaoCaoChiPhi(getFilters());
        table.setData(tableData);
        
        if (tableData.length === 0) {
            table.setPlaceholder("Không có dữ liệu.");
        }
        
        setupEventListeners();
    }

    initialize();
}

window.initializeBaoCaoChiPhiPage = initializeBaoCaoChiPhiPage;