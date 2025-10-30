/**
 * File: js/phieu_chi.js
 * Version: 1.0 - Quản lý Phiếu Chi
 * Description: Module quản lý phiếu chi tiền mặt
 */

function initializePhieuChiPage(mainContentContainer) {
    console.log("✅ initializePhieuChiPage được gọi");
    
    let table;

    // --- HÀM TIỆN ÍCH ---
    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        if (!toast) {
            console.warn("Không tìm thấy element #toast");
            return;
        }
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { 
            toast.className = toast.className.replace("show", ""); 
        }, 3000);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND' 
        }).format(amount || 0);
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
            console.error('Lỗi Fetch:', error);
            throw error;
        }
    }

    // --- QUẢN LÝ PHIẾU CHI ---
    async function loadPhieuChi(filters = {}) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const result = await fetchAPI(`api/get_phieu_chi.php?${queryParams}`);
            
            showToast(`Tải thành công ${result.data.length} phiếu chi!`, "success");
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu phiếu chi.", "error");
            return [];
        }
    }

    function createColumnDefinitions() {
        return [
            { title: "Số PC", field: "SoPhieuChi", width: 150 },
            { title: "Ngày chi", field: "NgayChi", width: 120, sorter: "date" },
            { title: "Đối tượng", field: "TenDoiTuong", minWidth: 200 },
            { title: "Lý do chi", field: "LyDoChi", minWidth: 200 },
            {
                title: "Loại CP",
                field: "LoaiChiPhi",
                width: 140,
                formatter: (cell) => {
                    const labels = {
                        'MH': 'Mua hàng',
                        'LUONG': 'Lương',
                        'VPPM': 'VP phẩm',
                        'DIEN_NUOC': 'Điện nước',
                        'VAN_CHUYEN': 'Vận chuyển',
                        'MARKETING': 'Marketing',
                        'BAO_TRI': 'Bảo trì',
                        'KHAC': 'Khác'
                    };
                    return labels[cell.getValue()] || cell.getValue();
                }
            },
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
                        'chuyen_khoan': 'CK',
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
                    const styles = {
                        'cho_duyet': 'background:#ffc107; color:#000;',
                        'da_duyet': 'background:#28a745; color:#fff;',
                        'da_huy': 'background:#dc3545; color:#fff;'
                    };
                    const labels = {
                        'cho_duyet': 'Chờ duyệt',
                        'da_duyet': 'Đã duyệt',
                        'da_huy': 'Đã hủy'
                    };
                    return `<span style="padding:4px 8px; border-radius:4px; font-size:12px; ${styles[val]}">${labels[val] || val}</span>`;
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
                        approvePhieuChi(data.PhieuChiID, row);
                    } else if (e.target.classList.contains('fa-times-circle')) {
                        cancelPhieuChi(data.PhieuChiID, row);
                    } else if (e.target.classList.contains('fa-print')) {
                        printPhieuChi(data.PhieuChiID);
                    }
                }
            }
        ];
    }

    async function approvePhieuChi(id, row) {
        if (!confirm('Bạn có chắc muốn duyệt phiếu chi này?')) return;
        try {
            await fetchAPI('api/approve_phieu_chi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Duyệt phiếu chi thành công!', 'success');
            refreshTable();
        } catch (error) {
            console.error("Lỗi duyệt:", error);
        }
    }

    async function cancelPhieuChi(id, row) {
        if (!confirm('Bạn có chắc muốn hủy phiếu chi này?')) return;
        try {
            await fetchAPI('api/cancel_phieu_chi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Hủy phiếu chi thành công!', 'success');
            refreshTable();
        } catch (error) {
            console.error("Lỗi hủy:", error);
        }
    }

    function printPhieuChi(id) {
        window.open(`print_phieu_chi.php?id=${id}`, '_blank');
    }

    function viewDetail(data) {
        const modalContent = `
            <div class="modal-content" style="background:#fff; margin:5% auto; padding:25px; width:90%; max-width:700px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #ddd; padding-bottom:15px; margin-bottom:20px;">
                    <h2>Chi Tiết Phiếu Chi: ${data.SoPhieuChi}</h2>
                    <span class="close-btn" style="cursor:pointer; font-size:28px;">&times;</span>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div><strong>Ngày chi:</strong> ${data.NgayChi}</div>
                        <div><strong>Số tiền:</strong> ${formatCurrency(data.SoTien)}</div>
                        <div><strong>Đối tượng:</strong> ${data.TenDoiTuong}</div>
                        <div><strong>Loại chi phí:</strong> ${data.LoaiChiPhi}</div>
                        <div style="grid-column: 1/-1;"><strong>Lý do chi:</strong> ${data.LyDoChi}</div>
                        <div><strong>Hình thức:</strong> ${data.HinhThucThanhToan}</div>
                        <div><strong>Người nhận:</strong> ${data.NguoiNhan || '-'}</div>
                        ${data.SoTaiKhoan ? `<div><strong>Số TK:</strong> ${data.SoTaiKhoan}</div>` : ''}
                        ${data.NganHang ? `<div><strong>Ngân hàng:</strong> ${data.NganHang}</div>` : ''}
                        ${data.GhiChu ? `<div style="grid-column: 1/-1;"><strong>Ghi chú:</strong> ${data.GhiChu}</div>` : ''}
                        <div><strong>Trạng thái:</strong> ${data.TrangThai}</div>
                    </div>
                </div>
            </div>
        `;
        const modal = document.getElementById('phieu-chi-modal');
        if (modal) {
            modal.innerHTML = modalContent;
            modal.style.display = 'block';
        }
    }

    function openAddModal() {
        const modalHTML = `
            <div class="modal-content" style="background:#fff; margin:3% auto; padding:25px; width:90%; max-width:800px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #ddd; padding-bottom:15px; margin-bottom:20px;">
                    <h2>Thêm Phiếu Chi Mới</h2>
                    <span class="close-btn" style="cursor:pointer; font-size:28px;">&times;</span>
                </div>
                <form id="phieu-chi-form">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Ngày chi (*)</label>
                            <input type="date" name="NgayChi" required value="${new Date().toISOString().split('T')[0]}" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Số tiền (*)</label>
                            <input type="number" name="SoTien" required step="0.01" min="0" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Loại đối tượng (*)</label>
                            <select name="LoaiDoiTuong" required style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="nhacungcap">Nhà cung cấp</option>
                                <option value="nhanvien">Nhân viên</option>
                                <option value="khachhang">Khách hàng</option>
                                <option value="khac">Khác</option>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Tên đối tượng (*)</label>
                            <input type="text" name="TenDoiTuong" required placeholder="Tên NCC/Nhân viên..." style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Loại chi phí (*)</label>
                            <select name="LoaiChiPhi" required style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="MH">Mua hàng</option>
                                <option value="LUONG">Lương nhân viên</option>
                                <option value="VPPM">Văn phòng phẩm</option>
                                <option value="DIEN_NUOC">Điện nước</option>
                                <option value="VAN_CHUYEN">Vận chuyển</option>
                                <option value="MARKETING">Marketing</option>
                                <option value="BAO_TRI">Bảo trì sửa chữa</option>
                                <option value="KHAC">Chi phí khác</option>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Hình thức thanh toán (*)</label>
                            <select name="HinhThucThanhToan" required style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="tien_mat">Tiền mặt</option>
                                <option value="chuyen_khoan">Chuyển khoản</option>
                                <option value="séc">Séc</option>
                            </select>
                        </div>
                        <div style="grid-column:1/-1; display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Địa chỉ</label>
                            <input type="text" name="DiaChiDoiTuong" placeholder="Địa chỉ đối tượng..." style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="grid-column:1/-1; display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Lý do chi (*)</label>
                            <textarea name="LyDoChi" required rows="2" placeholder="VD: Thanh toán mua nguyên liệu..." style="padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Người nhận</label>
                            <input type="text" name="NguoiNhan" placeholder="Tên người nhận tiền" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">ĐT người nhận</label>
                            <input type="text" name="DienThoaiNguoiNhan" placeholder="Số điện thoại" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Số tài khoản</label>
                            <input type="text" name="SoTaiKhoan" placeholder="Số TK (nếu CK)" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Ngân hàng</label>
                            <input type="text" name="NganHang" placeholder="Tên ngân hàng" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="grid-column:1/-1; display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Ghi chú</label>
                            <textarea name="GhiChu" rows="2" style="padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                        </div>
                    </div>
                    <div style="border-top:1px solid #ddd; margin-top:25px; padding-top:20px; text-align:right;">
                        <button type="button" onclick="document.getElementById('phieu-chi-modal').style.display='none'" style="padding:8px 15px; background:#6c757d; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-right:10px;">Hủy</button>
                        <button type="submit" style="padding:8px 15px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                            <i class="fa fa-save"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        const modal = document.getElementById('phieu-chi-modal');
        if (modal) {
            modal.innerHTML = modalHTML;
            modal.style.display = 'block';
            
            const form = document.getElementById('phieu-chi-form');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const data = Object.fromEntries(formData.entries());
                    
                    try {
                        await fetchAPI('api/add_phieu_chi.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });
                        showToast('Thêm phiếu chi thành công!', 'success');
                        modal.style.display = 'none';
                        refreshTable();
                    } catch (error) {
                        console.error("Lỗi thêm:", error);
                    }
                });
            }
        }
    }

    function getFilters() {
        return {
            dateFrom: document.getElementById('filter-date-from')?.value || '',
            dateTo: document.getElementById('filter-date-to')?.value || '',
            loaiCP: document.getElementById('filter-loai-cp')?.value || '',
            status: document.getElementById('filter-status')?.value || '',
            search: document.getElementById('filter-search')?.value || ''
        };
    }

    async function refreshTable() {
        const data = await loadPhieuChi(getFilters());
        if (table) {
            table.setData(data);
        }
    }

    function applyFilters() {
        refreshTable();
    }

    function exportToExcel() {
        if (table) {
            table.download("xlsx", "phieu_chi.xlsx", {sheetName: "Phiếu Chi"});
            showToast("Đã xuất file Excel!", "success");
        }
    }

    function setupEventListeners() {
        const modal = document.getElementById('phieu-chi-modal');
        
        const addBtn = document.getElementById('add-btn');
        const exportBtn = document.getElementById('export-btn');
        
        if (addBtn) addBtn.onclick = openAddModal;
        if (exportBtn) exportBtn.onclick = exportToExcel;
        
        const filterDateFrom = document.getElementById('filter-date-from');
        const filterDateTo = document.getElementById('filter-date-to');
        const filterLoaiCP = document.getElementById('filter-loai-cp');
        const filterStatus = document.getElementById('filter-status');
        const filterSearch = document.getElementById('filter-search');
        
        if (filterDateFrom) filterDateFrom.addEventListener('change', applyFilters);
        if (filterDateTo) filterDateTo.addEventListener('change', applyFilters);
        if (filterLoaiCP) filterLoaiCP.addEventListener('change', applyFilters);
        if (filterStatus) filterStatus.addEventListener('change', applyFilters);
        
        if (filterSearch) {
            let searchTimeout;
            filterSearch.addEventListener('keyup', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 500);
            });
        }
        
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'phieu-chi-modal') {
                if (modal) modal.style.display = 'none';
            }
        });
    }

    async function initialize() {
        console.log("🚀 Khởi tạo Phiếu Chi...");
        
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        const filterFrom = document.getElementById('filter-date-from');
        const filterTo = document.getElementById('filter-date-to');
        
        if (filterFrom) filterFrom.value = firstDay.toISOString().split('T')[0];
        if (filterTo) filterTo.value = today.toISOString().split('T')[0];

        if (typeof Tabulator === 'undefined') {
            console.error("❌ Tabulator chưa được load!");
            showToast("Lỗi: Tabulator library chưa được load", "error");
            return;
        }

        const tableEl = document.getElementById("phieu-chi-table");
        if (!tableEl) {
            console.error("❌ Không tìm thấy element #phieu-chi-table");
            return;
        }

        table = new Tabulator("#phieu-chi-table", {
            height: "65vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100],
            placeholder: "Đang tải dữ liệu...",
        });

        const tableData = await loadPhieuChi(getFilters());
        table.setData(tableData);
        
        setupEventListeners();
        
        console.log("✅ Phiếu Chi đã sẵn sàng!");
    }

    initialize();
}

if (typeof window !== 'undefined') {
    window.initializePhieuChiPage = initializePhieuChiPage;
    console.log("✅ initializePhieuChiPage đã được export");
}