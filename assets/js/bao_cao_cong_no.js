
function initializeBaoCaoCongNoPage(mainContentContainer) {
    let table;
    let summaryData = { tongCongNo: 0, tongQuaHan: 0, tongDaThu: 0 };

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

    async function loadCongNo(filters = {}) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const result = await fetchAPI(`api/get_bao_cao_cong_no.php?${queryParams}`);
            
            summaryData = result.summary || summaryData;
            updateSummaryCards();
            
            showToast(`Tải thành công ${result.data.length} công nợ!`, "success");
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu.", "error");
            return [];
        }
    }

    function updateSummaryCards() {
        document.getElementById('tong-cong-no').textContent = formatCurrency(summaryData.tongCongNo);
        document.getElementById('tong-qua-han').textContent = formatCurrency(summaryData.tongQuaHan);
        document.getElementById('tong-da-thu').textContent = formatCurrency(summaryData.tongDaThu);
    }

    function createColumnDefinitions() {
        return [
            { title: "Mã ĐH", field: "SoYCSX", width: 130 },
            { title: "Công ty", field: "TenCongTy", minWidth: 250 },
            { 
                title: "Tổng giá trị", 
                field: "TongTien", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => formatCurrency(cell.getValue())
            },
            { 
                title: "Đã thu", 
                field: "SoTienTamUng", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => formatCurrency(cell.getValue())
            },
            { 
                title: "Còn lại", 
                field: "GiaTriConLai", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => {
                    const val = cell.getValue();
                    const elem = cell.getElement();
                    elem.style.color = val > 0 ? '#dc3545' : '#28a745';
                    elem.style.fontWeight = 'bold';
                    return formatCurrency(val);
                }
            },
            { title: "Ngày XHĐ", field: "NgayXuatHoaDon", width: 120 },
            { title: "Hạn TT", field: "ThoiHanThanhToan", width: 120 },
            {
                title: "Trạng thái",
                field: "TrangThaiThanhToan",
                width: 150,
                formatter: (cell) => {
                    const val = cell.getValue();
                    const labels = {
                        'Chưa thanh toán': 'Con hạn',
                        'Quá hạn': 'Quá hạn',
                        'Đã thanh toán': 'Đã thanh toán',
                        'Sắp hết hạn': 'Sắp hết hạn'
                    };
                    const cssClass = {
                        'Chưa thanh toán': 'con-han',
                        'Quá hạn': 'qua-han',
                        'Đã thanh toán': 'da-thanh-toan',
                        'Sắp hết hạn': 'sap-het-han'
                    };
                    return `<span class="badge ${cssClass[val] || ''}">${labels[val] || val}</span>`;
                }
            },
            {
                title: "Thao tác",
                hozAlign: "center",
                width: 100,
                headerSort: false,
                formatter: () => `<i class="fa fa-eye" style="cursor:pointer; color:#007bff;" title="Xem chi tiết"></i>`,
                cellClick: (e, cell) => {
                    if (e.target.classList.contains('fa-eye')) {
                        const data = cell.getRow().getData();
                        window.open(`?page=donhang_view&id=${data.YCSX_ID}`, '_blank');
                    }
                }
            }
        ];
    }

    function getFilters() {
        return {
            status: document.getElementById('filter-status').value,
            search: document.getElementById('filter-search').value
        };
    }

    async function refreshTable() {
        const data = await loadCongNo(getFilters());
        table.setData(data);
    }

    function applyFilters() {
        refreshTable();
    }

    function exportToExcel() {
        table.download("xlsx", "bao_cao_cong_no.xlsx", {sheetName: "Công Nợ"});
        showToast("Đã xuất file Excel!", "success");
    }

    function setupEventListeners() {
        document.getElementById('refresh-btn').onclick = refreshTable;
        document.getElementById('export-btn').onclick = exportToExcel;
        document.getElementById('filter-status').addEventListener('change', applyFilters);
        
        let searchTimeout;
        document.getElementById('filter-search').addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });
    }

    async function initialize() {
        table = new Tabulator("#congno-table", {
            height: "55vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100],
            placeholder: "Đang tải...",
        });

        const tableData = await loadCongNo(getFilters());
        table.setData(tableData);
        
        if (tableData.length === 0) {
            table.setPlaceholder("Không có dữ liệu.");
        }
        
        setupEventListeners();
    }

    initialize();
}

window.initializeBaoCaoCongNoPage = initializeBaoCaoCongNoPage;
