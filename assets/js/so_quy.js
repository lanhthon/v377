/**
 * File: js/so_quy.js
 * Version: 2.0 - Fixed setPlaceholder issue
 */

function initializeSoQuyPage(mainContentContainer) {
    console.log("✅ initializeSoQuyPage được gọi");
    
    let table;
    let summaryData = { tongThu: 0, tongChi: 0, tonQuy: 0 };

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

    async function loadSoQuy(filters = {}) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const result = await fetchAPI(`api/get_so_quy.php?${queryParams}`);
            
            summaryData = result.summary || summaryData;
            updateSummaryCards();
            
            showToast(`Tải thành công ${result.data.length} giao dịch!`, "success");
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu sổ quỹ.", "error");
            return [];
        }
    }

    function updateSummaryCards() {
        const elTongThu = document.getElementById('total-thu');
        const elTongChi = document.getElementById('total-chi');
        const elTonQuy = document.getElementById('ton-quy');
        const elLoiNhuan = document.getElementById('loi-nhuan');
        
        if (elTongThu) elTongThu.textContent = formatCurrency(summaryData.tongThu);
        if (elTongChi) elTongChi.textContent = formatCurrency(summaryData.tongChi);
        if (elTonQuy) elTonQuy.textContent = formatCurrency(summaryData.tonQuy);
        if (elLoiNhuan) elLoiNhuan.textContent = formatCurrency(summaryData.tongThu - summaryData.tongChi);
    }

    function createColumnDefinitions() {
        return [
            { title: "Ngày", field: "NgayGhiSo", width: 120, sorter: "date" },
            { 
                title: "Loại", 
                field: "LoaiGiaoDich", 
                width: 100,
                formatter: (cell) => {
                    const val = cell.getValue();
                    const badge = val === 'thu' 
                        ? '<span style="padding:4px 8px; background:#28a745; color:#fff; border-radius:4px; font-size:12px;">Thu</span>' 
                        : '<span style="padding:4px 8px; background:#dc3545; color:#fff; border-radius:4px; font-size:12px;">Chi</span>';
                    return badge;
                }
            },
            { title: "Số CT", field: "SoChungTu", width: 130 },
            { title: "Nội dung", field: "NoiDung", minWidth: 250 },
            { title: "Đối tượng", field: "DoiTuong", width: 180 },
            { 
                title: "Thu", 
                field: "SoTienThu", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => {
                    const val = cell.getValue();
                    return val > 0 ? formatCurrency(val) : '-';
                }
            },
            { 
                title: "Chi", 
                field: "SoTienChi", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => {
                    const val = cell.getValue();
                    return val > 0 ? formatCurrency(val) : '-';
                }
            },
            { 
                title: "Số dư", 
                field: "SoDu", 
                width: 150, 
                hozAlign: "right",
                formatter: (cell) => {
                    const val = cell.getValue();
                    const color = val >= 0 ? '#28a745' : '#dc3545';
                    cell.getElement().style.color = color;
                    cell.getElement().style.fontWeight = 'bold';
                    return formatCurrency(val);
                }
            },
            {
                title: "Thao tác", 
                hozAlign: "center", 
                width: 120, 
                headerSort: false,
                formatter: () => `
                    <i class="fa fa-eye" style="cursor:pointer; color:#007bff; margin:0 5px;" title="Xem"></i>
                    <i class="fa fa-trash-can" style="cursor:pointer; color:#dc3545; margin:0 5px;" title="Xóa"></i>
                `,
                cellClick: (e, cell) => {
                    const row = cell.getRow();
                    if (e.target.classList.contains('fa-trash-can')) {
                        if (confirm('Bạn có chắc muốn xóa giao dịch này?')) {
                            deleteSoQuy(row.getData().SoQuyID, row);
                        }
                    } else if (e.target.classList.contains('fa-eye')) {
                        viewDetail(row.getData());
                    }
                }
            },
        ];
    }

    async function deleteSoQuy(id, row) {
        try {
            await fetchAPI('api/delete_so_quy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Xóa giao dịch thành công!', 'success');
            row.delete();
            refreshTable();
        } catch (error) { 
            console.error("Lỗi xóa:", error);
        }
    }

    function viewDetail(data) {
        const modalContent = `
            <div class="modal-content" style="background:#fff; margin:5% auto; padding:25px; width:90%; max-width:700px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #ddd; padding-bottom:15px; margin-bottom:20px;">
                    <h2>Chi Tiết Giao Dịch</h2>
                    <span class="close-btn" style="cursor:pointer; font-size:28px;">&times;</span>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div><strong>Ngày:</strong> ${data.NgayGhiSo}</div>
                        <div><strong>Loại:</strong> ${data.LoaiGiaoDich === 'thu' ? 'Thu' : 'Chi'}</div>
                        <div><strong>Số chứng từ:</strong> ${data.SoChungTu || '-'}</div>
                        <div><strong>Đối tượng:</strong> ${data.DoiTuong || '-'}</div>
                        <div style="grid-column: 1/-1;"><strong>Nội dung:</strong> ${data.NoiDung}</div>
                        <div><strong>Thu:</strong> ${formatCurrency(data.SoTienThu)}</div>
                        <div><strong>Chi:</strong> ${formatCurrency(data.SoTienChi)}</div>
                        <div style="grid-column: 1/-1;"><strong>Số dư:</strong> ${formatCurrency(data.SoDu)}</div>
                        ${data.GhiChu ? `<div style="grid-column: 1/-1;"><strong>Ghi chú:</strong> ${data.GhiChu}</div>` : ''}
                    </div>
                </div>
            </div>
        `;
        const modal = document.getElementById('so-quy-modal');
        if (modal) {
            modal.innerHTML = modalContent;
            modal.style.display = 'block';
        }
    }

    function openAddModal(loaiGiaoDich) {
        const isPhieuThu = loaiGiaoDich === 'thu';
        const modalTitle = isPhieuThu ? 'Thêm Phiếu Thu' : 'Thêm Phiếu Chi';
        
        const modalHTML = `
            <div class="modal-content" style="background:#fff; margin:3% auto; padding:25px; width:90%; max-width:700px; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #ddd; padding-bottom:15px; margin-bottom:20px;">
                    <h2>${modalTitle}</h2>
                    <span class="close-btn" style="cursor:pointer; font-size:28px;">&times;</span>
                </div>
                <form id="so-quy-form">
                    <input type="hidden" name="LoaiGiaoDich" value="${loaiGiaoDich}">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Ngày ghi sổ (*)</label>
                            <input type="date" name="NgayGhiSo" required value="${new Date().toISOString().split('T')[0]}" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Số tiền (*)</label>
                            <input type="number" name="SoTien" required step="0.01" min="0" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Loại đối tượng</label>
                            <select name="LoaiDoiTuong" style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                                <option value="khac">Khác</option>
                                <option value="khachhang">Khách hàng</option>
                                <option value="nhacungcap">Nhà cung cấp</option>
                            </select>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Tên đối tượng</label>
                            <input type="text" name="DoiTuong" placeholder="Tên khách hàng/NCC..." style="padding:8px; border:1px solid #ddd; border-radius:4px;">
                        </div>
                        <div style="grid-column:1/-1; display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Nội dung giao dịch (*)</label>
                            <textarea name="NoiDung" required rows="3" placeholder="Mô tả chi tiết giao dịch..." style="padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                        </div>
                        <div style="grid-column:1/-1; display:flex; flex-direction:column;">
                            <label style="margin-bottom:8px; font-weight:500;">Ghi chú</label>
                            <textarea name="GhiChu" rows="2" style="padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                        </div>
                    </div>
                    <div style="border-top:1px solid #ddd; margin-top:25px; padding-top:20px; text-align:right;">
                        <button type="submit" style="padding:8px 15px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                            <i class="fa fa-save"></i> Lưu
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        const modal = document.getElementById('so-quy-modal');
        if (modal) {
            modal.innerHTML = modalHTML;
            modal.style.display = 'block';
            
            const form = document.getElementById('so-quy-form');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const data = Object.fromEntries(formData.entries());
                    
                    try {
                        await fetchAPI('api/add_so_quy.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        });
                        showToast('Thêm giao dịch thành công!', 'success');
                        modal.style.display = 'none';
                        refreshTable();
                    } catch (error) {
                        console.error("Lỗi thêm:", error);
                    }
                });
            }
        }
    }

    async function refreshTable() {
        const filters = getFilters();
        const data = await loadSoQuy(filters);
        if (table) {
            table.setData(data);
        }
    }

    function getFilters() {
        return {
            dateFrom: document.getElementById('filter-date-from')?.value || '',
            dateTo: document.getElementById('filter-date-to')?.value || '',
            loai: document.getElementById('filter-loai')?.value || '',
            search: document.getElementById('filter-search')?.value || ''
        };
    }

    function applyFilters() {
        refreshTable();
    }

    function exportToExcel() {
        if (table) {
            table.download("xlsx", "so_quy.xlsx", {sheetName: "Sổ Quỹ"});
            showToast("Đã xuất file Excel!", "success");
        }
    }

    function setupEventListeners() {
        const modal = document.getElementById('so-quy-modal');
        
        const addThuBtn = document.getElementById('add-thu-btn');
        const addChiBtn = document.getElementById('add-chi-btn');
        const exportBtn = document.getElementById('export-btn');
        
        if (addThuBtn) addThuBtn.onclick = () => openAddModal('thu');
        if (addChiBtn) addChiBtn.onclick = () => openAddModal('chi');
        if (exportBtn) exportBtn.onclick = exportToExcel;
        
        const filterDateFrom = document.getElementById('filter-date-from');
        const filterDateTo = document.getElementById('filter-date-to');
        const filterLoai = document.getElementById('filter-loai');
        const filterSearch = document.getElementById('filter-search');
        
        if (filterDateFrom) filterDateFrom.addEventListener('change', applyFilters);
        if (filterDateTo) filterDateTo.addEventListener('change', applyFilters);
        if (filterLoai) filterLoai.addEventListener('change', applyFilters);
        
        if (filterSearch) {
            let searchTimeout;
            filterSearch.addEventListener('keyup', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 500);
            });
        }
        
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'so-quy-modal') {
                if (modal) modal.style.display = 'none';
            }
        });
    }

    async function initialize() {
        console.log("🚀 Khởi tạo Sổ Quỹ...");
        
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

        const tableEl = document.getElementById("so-quy-table");
        if (!tableEl) {
            console.error("❌ Không tìm thấy element #so-quy-table");
            return;
        }

        table = new Tabulator("#so-quy-table", {
            height: "60vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100],
            placeholder: "Đang tải dữ liệu...",
        });

        const tableData = await loadSoQuy(getFilters());
        table.setData(tableData);
        
        // BỎ setPlaceholder - không cần thiết
        // if (tableData.length === 0) {
        //     table.setPlaceholder("Không có dữ liệu.");
        // }
        
        setupEventListeners();
        
        console.log("✅ Sổ Quỹ đã sẵn sàng!");
    }

    initialize();
}

if (typeof window !== 'undefined') {
    window.initializeSoQuyPage = initializeSoQuyPage;
    console.log("✅ initializeSoQuyPage đã được export");
}