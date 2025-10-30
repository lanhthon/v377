function initializePhieuThuPage(mainContentContainer) {
    let table;

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

    async function loadPhieuThu(filters = {}) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const result = await fetchAPI(`api/get_phieu_thu.php?${queryParams}`);
            showToast(`Tải thành công ${result.data.length} phiếu thu!`, "success");
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu.", "error");
            return [];
        }
    }

    function createColumnDefinitions() {
        return [
            { title: "Số PT", field: "SoPhieuThu", width: 150 },
            { title: "Ngày thu", field: "NgayThu", width: 120 },
            { title: "Đối tượng", field: "TenDoiTuong", minWidth: 200 },
            { title: "Lý do thu", field: "LyDoThu", minWidth: 250 },
            { 
                title: "Số tiền", 
                field: "SoTien", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => formatCurrency(cell.getValue())
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
                width: 150,
                headerSort: false,
                formatter: (cell) => {
                    const status = cell.getRow().getData().TrangThai;
                    let actions = `<i class="fa fa-eye" style="cursor:pointer; color:#007bff; margin:0 5px;" title="Xem"></i>`;
                    if (status === 'cho_duyet') {
                        actions += `<i class="fa fa-check-circle" style="cursor:pointer; color:#28a745; margin:0 5px;" title="Duyệt"></i>`;
                        actions += `<i class="fa fa-times-circle" style="cursor:pointer; color:#dc3545; margin:0 5px;" title="Hủy"></i>`;
                    }
                    actions += `<i class="fa fa-print" style="cursor:pointer; color:#6c757d; margin:0 5px;" title="In"></i>`;
                    return actions;
                },
                cellClick: (e, cell) => {
                    const row = cell.getRow();
                    const data = row.getData();
                    if (e.target.classList.contains('fa-eye')) {
                        viewDetail(data);
                    } else if (e.target.classList.contains('fa-check-circle')) {
                        approvePhieuThu(data.PhieuThuID, row);
                    } else if (e.target.classList.contains('fa-times-circle')) {
                        cancelPhieuThu(data.PhieuThuID, row);
                    } else if (e.target.classList.contains('fa-print')) {
                        printPhieuThu(data.PhieuThuID);
                    }
                }
            }
        ];
    }

    async function approvePhieuThu(id, row) {
        if (!confirm('Bạn có chắc muốn duyệt phiếu thu này?')) return;
        try {
            await fetchAPI('api/approve_phieu_thu.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Duyệt phiếu thu thành công!', 'success');
            refreshTable();
        } catch (error) { /* Handled */ }
    }

    async function cancelPhieuThu(id, row) {
        if (!confirm('Bạn có chắc muốn hủy phiếu thu này?')) return;
        try {
            await fetchAPI('api/cancel_phieu_thu.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Hủy phiếu thu thành công!', 'success');
            refreshTable();
        } catch (error) { /* Handled */ }
    }

    function printPhieuThu(id) {
        window.open(`print_phieu_thu.php?id=${id}`, '_blank');
    }

    function viewDetail(data) {
        const modalHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Chi Tiết Phiếu Thu: ${data.SoPhieuThu}</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <div style="padding: 20px;">
                    <div class="form-grid">
                        <div><strong>Ngày thu:</strong> ${data.NgayThu}</div>
                        <div><strong>Số tiền:</strong> ${formatCurrency(data.SoTien)}</div>
                        <div><strong>Đối tượng:</strong> ${data.TenDoiTuong}</div>
                        <div><strong>Hình thức:</strong> ${data.HinhThucThanhToan}</div>
                        <div class="full-width"><strong>Lý do thu:</strong> ${data.LyDoThu}</div>
                        <div><strong>Người nộp:</strong> ${data.NguoiNop || '-'}</div>
                        <div><strong>ĐT người nộp:</strong> ${data.DienThoaiNguoiNop || '-'}</div>
                        ${data.SoTaiKhoan ? `<div><strong>Số TK:</strong> ${data.SoTaiKhoan}</div>` : ''}
                        ${data.NganHang ? `<div><strong>Ngân hàng:</strong> ${data.NganHang}</div>` : ''}
                        ${data.GhiChu ? `<div class="full-width"><strong>Ghi chú:</strong> ${data.GhiChu}</div>` : ''}
                        <div><strong>Trạng thái:</strong> <span class="badge ${data.TrangThai}">${data.TrangThai}</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="document.getElementById('phieu-thu-modal').style.display='none'" 
                            class="action-button" style="background-color: #6c757d;">Đóng</button>
                </div>
            </div>
        `;
        const modal = document.getElementById('phieu-thu-modal');
        modal.innerHTML = modalHTML;
        modal.style.display = 'block';
    }

    function openAddModal() {
        const modalHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Thêm Phiếu Thu Mới</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form id="phieu-thu-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Ngày thu (*)</label>
                            <input type="date" name="NgayThu" required value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        <div class="form-group">
                            <label>Số tiền (*)</label>
                            <input type="number" name="SoTien" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label>Loại đối tượng (*)</label>
                            <select name="LoaiDoiTuong" id="loai-doi-tuong" required>
                                <option value="khachhang">Khách hàng</option>
                                <option value="nhacungcap">Nhà cung cấp</option>
                                <option value="khac">Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tên đối tượng (*)</label>
                            <input type="text" name="TenDoiTuong" required placeholder="Tên khách hàng/công ty...">
                        </div>
                        <div class="form-group full-width">
                            <label>Địa chỉ</label>
                            <input type="text" name="DiaChiDoiTuong" placeholder="Địa chỉ đối tượng...">
                        </div>
                        <div class="form-group full-width">
                            <label>Lý do thu (*)</label>
                            <textarea name="LyDoThu" required rows="2" placeholder="VD: Thanh toán đơn hàng..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Hình thức thanh toán (*)</label>
                            <select name="HinhThucThanhToan" id="hinh-thuc-tt" required>
                                <option value="tien_mat">Tiền mặt</option>
                                <option value="chuyen_khoan">Chuyển khoản</option>
                                <option value="séc">Séc</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Người nộp</label>
                            <input type="text" name="NguoiNop" placeholder="Tên người nộp tiền">
                        </div>
                        <div class="form-group">
                            <label>Số tài khoản</label>
                            <input type="text" name="SoTaiKhoan" placeholder="Số TK (nếu CK)">
                        </div>
                        <div class="form-group">
                            <label>Ngân hàng</label>
                            <input type="text" name="NganHang" placeholder="Tên ngân hàng">
                        </div>
                        <div class="form-group full-width">
                            <label>Ghi chú</label>
                            <textarea name="GhiChu" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="action-button" style="background-color: #6c757d;" 
                                onclick="document.getElementById('phieu-thu-modal').style.display='none'">Hủy</button>
                        <button type="submit" class="action-button" style="background-color: var(--success-color);">
                            <i class="fa fa-save"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        const modal = document.getElementById('phieu-thu-modal');
        modal.innerHTML = modalHTML;
        modal.style.display = 'block';
        
        document.getElementById('phieu-thu-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            try {
                await fetchAPI('api/add_phieu_thu.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                showToast('Thêm phiếu thu thành công!', 'success');
                modal.style.display = 'none';
                refreshTable();
            } catch (error) { /* Handled */ }
        });
    }

    function getFilters() {
        return {
            dateFrom: document.getElementById('filter-date-from').value,
            dateTo: document.getElementById('filter-date-to').value,
            status: document.getElementById('filter-status').value,
            search: document.getElementById('filter-search').value
        };
    }

    async function refreshTable() {
        const data = await loadPhieuThu(getFilters());
        table.setData(data);
    }

    function applyFilters() {
        refreshTable();
    }

    function exportToExcel() {
        table.download("xlsx", "phieu_thu.xlsx", {sheetName: "Phiếu Thu"});
        showToast("Đã xuất file Excel!", "success");
    }

    function setupEventListeners() {
        const modal = document.getElementById('phieu-thu-modal');
        
        document.getElementById('add-btn').onclick = openAddModal;
        document.getElementById('export-btn').onclick = exportToExcel;
        
        document.getElementById('filter-date-from').addEventListener('change', applyFilters);
        document.getElementById('filter-date-to').addEventListener('change', applyFilters);
        document.getElementById('filter-status').addEventListener('change', applyFilters);
        
        let searchTimeout;
        document.getElementById('filter-search').addEventListener('keyup', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });
        
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'phieu-thu-modal') {
                modal.style.display = 'none';
            }
        });
    }

    // === PHẦN ĐÃ SỬA LỖI ===
    async function initialize() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('filter-date-from').value = firstDay.toISOString().split('T')[0];
        document.getElementById('filter-date-to').value = today.toISOString().split('T')[0];

        table = new Tabulator("#phieu-thu-table", {
            height: "65vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100],
            // THAY ĐỔI 1: Đặt placeholder mặc định là "Không có dữ liệu."
            placeholder: "Không có dữ liệu.",
        });

        const tableData = await loadPhieuThu(getFilters());
        table.setData(tableData);
        
        // THAY ĐỔI 2: Xóa khối lệnh if gây lỗi
        // Đoạn code cũ đã được xóa bỏ khỏi đây.
        
        setupEventListeners();
    }

    initialize();
}

window.initializePhieuThuPage = initializePhieuThuPage;