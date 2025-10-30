/**
 * File: js/dinh_muc_cat.js
 * Version: 1.1 - Quản lý Định mức cắt (có thêm chức năng xuất Excel)
 * Description: Khởi tạo và quản lý bảng định mức cắt.
 * - Sử dụng Tabulator để hiển thị và chỉnh sửa dữ liệu.
 * - Kết nối các sự kiện người dùng (tìm kiếm, thêm, sửa, xóa, xuất Excel).
 */

function initializeDinhMucCatPage(mainContentContainer) {
    let table;

    // --- CÁC HÀM TIỆN ÍCH ---
    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
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

    // --- QUẢN LÝ ĐỊNH MỨC CẮT ---
    async function loadAllDinhMuc() {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const result = await fetchAPI("api/get_dinh_muc.php");
            showToast(`Tải thành công ${result.data.length} định mức!`, "success");
            return result.data;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu định mức.", "error");
            return [];
        }
    }

    function createColumnDefinitions() {
        return [
            { title: "ID", field: "DinhMucID", width: 60, headerSort: false },
            { title: "Tên Nhóm DN", field: "TenNhomDN", minWidth: 150, editor: "input" },
            {
                title: "Hình Dạng", field: "HinhDang", width: 120, editor: "list",
                editorParams: { values: { "Vuông": "Vuông", "Tròn": "Tròn" } }
            },
            { title: "Min DN", field: "MinDN", width: 100, editor: "number", hozAlign: "center" },
            { title: "Max DN", field: "MaxDN", width: 100, editor: "number", hozAlign: "center" },
            { title: "Bản Rộng", field: "BanRong", width: 120, editor: "number", hozAlign: "center" },
            { title: "Số Bộ/Cây", field: "SoBoTrenCay", width: 120, editor: "number", hozAlign: "center" },
            {
                title: "Thao tác", hozAlign: "center", width: 100, headerSort: false,
                formatter: (c) => `<i class="fa-solid fa-save action-icon icon-save" title="Lưu"></i> <i class="fa-solid fa-trash-can action-icon icon-delete" title="Xóa"></i>`,
                cellClick: (e, cell) => {
                    const row = cell.getRow();
                    if (e.target.classList.contains('icon-delete')) {
                        if (confirm(`Bạn có chắc muốn xóa định mức "${row.getData().TenNhomDN}"?`)) {
                            deleteDinhMuc(row.getData().DinhMucID, row);
                        }
                    } else if (e.target.classList.contains('icon-save')) {
                        saveDinhMucUpdate(row);
                    }
                }
            },
        ];
    }

    async function saveDinhMucUpdate(row) {
        row.getElement().style.opacity = '0.5';
        try {
            await fetchAPI('api/update_dinh_muc.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(row.getData())
            });
            showToast('Cập nhật định mức thành công!', 'success');
        } finally {
            row.getElement().style.opacity = '1';
        }
    }

    async function deleteDinhMuc(id, row) {
        try {
            await fetchAPI('api/delete_dinh_muc.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast('Xóa định mức thành công!', 'success');
            row.delete();
        } catch (error) { /* Đã được xử lý trong fetchAPI */ }
    }

    function applyTableFilters() {
        const filterValue = document.getElementById("filter-field").value;
        table.setFilter("TenNhomDN", "like", filterValue);
    }

    function setupEventListeners() {
        const addBtn = document.getElementById('add-btn');
        const modal = document.getElementById('dinh-muc-modal');
        let filterTimeout;

        // Thêm sự kiện cho nút Xuất Excel
        const exportBtn = document.getElementById('export-excel-btn');
        exportBtn.onclick = () => {
            showToast("Đang xuất file Excel...", "info");
            // Gọi hàm download của Tabulator
            // tham số 1: định dạng file ('xlsx')
            // tham số 2: tên file
            // tham số 3: tùy chọn, ở đây là tên của sheet
            table.download("xlsx", "DinhMucCat.xlsx", { sheetName: "Dữ Liệu Định Mức" });
        };

        document.getElementById("filter-field").addEventListener("keyup", () => {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(applyTableFilters, 300);
        });

        addBtn.onclick = () => {
            document.getElementById('dinh-muc-form').reset();
            modal.style.display = "block";
        };

        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'dinh-muc-modal') {
                modal.style.display = 'none';
            }
        });

        document.getElementById('dinh-muc-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            try {
                const result = await fetchAPI('api/add_dinh_muc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                showToast('Thêm định mức thành công!', 'success');
                modal.style.display = "none";
                table.addData([result.data], true);
            } catch (error) { /* Đã được xử lý */ }
        });
    }

    async function initialize() {
        document.getElementById('dinh-muc-modal').innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Thêm Định Mức Mới</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form id="dinh-muc-form">
                    <div class="form-grid">
                        <div class="form-group"> <label for="TenNhomDN">Tên Nhóm DN (*)</label> <input type="text" name="TenNhomDN" required> </div>
                        <div class="form-group"> <label for="HinhDang">Hình Dạng (*)</label> 
                            <select name="HinhDang" required>
                                <option value="Vuông">Vuông</option>
                                <option value="Tròn">Tròn</option>
                            </select>
                        </div>
                        <div class="form-group"> <label for="MinDN">Min DN (*)</label> <input type="number" name="MinDN" required> </div>
                        <div class="form-group"> <label for="MaxDN">Max DN (*)</label> <input type="number" name="MaxDN" required> </div>
                        <div class="form-group"> <label for="BanRong">Bản Rộng (*)</label> <input type="number" name="BanRong" required> </div>
                        <div class="form-group"> <label for="SoBoTrenCay">Số Bộ/Cây (*)</label> <input type="number" name="SoBoTrenCay" required> </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="action-button" style="background-color: var(--primary-color);">Lưu Định Mức</button>
                    </div>
                </form>
            </div>`;

        table = new Tabulator("#dinh-muc-table", {
            height: "75vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100, true],
            placeholder: "Đang tải...",
        });

        const tableData = await loadAllDinhMuc();
        table.setData(tableData);
        if (tableData.length === 0) {
            table.setPlaceholder("Không có dữ liệu định mức.");
        }
        setupEventListeners();
    }

    initialize();
}