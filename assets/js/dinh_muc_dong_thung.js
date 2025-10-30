/**
 * File: js/dinh_muc_dong_thung.js
 * Version: 1.1 - Quản lý Định mức đóng thùng
 * Description: Khởi tạo và quản lý bảng định mức đóng thùng sản phẩm PUR, bao gồm các chức năng CRUD, lọc và xuất Excel.
 * Author: Đối tác lập trình
 */

function initializeDinhMucDongThungPage(mainContentContainer) {
    let table; // Biến để lưu trữ đối tượng bảng Tabulator

    // --- CÁC HÀM TIỆN ÍCH ---

    /**
     * Hiển thị một thông báo tạm thời (toast message).
     * @param {string} message - Nội dung thông báo.
     * @param {string} [type='success'] - Loại thông báo ('success', 'error', 'info').
     */
    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
    }

    /**
     * Gửi yêu cầu đến API và xử lý phản hồi.
     * @param {string} url - URL của API.
     * @param {object} options - Các tùy chọn cho lệnh fetch (method, headers, body).
     * @returns {Promise<object>} - Dữ liệu JSON từ phản hồi.
     */
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
            throw error; // Ném lỗi để các hàm gọi nó có thể xử lý
        }
    }

    // --- QUẢN LÝ ĐỊNH MỨC ĐÓNG THÙNG ---

    /**
     * Tải tất cả dữ liệu định mức từ máy chủ.
     * @returns {Promise<Array>} - Mảng dữ liệu định mức.
     */
    async function loadAllDinhMuc() {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const result = await fetchAPI("api/get_chi_tiet_dinh_muc.php");
            showToast(`Tải thành công ${result.data.length} định mức!`, "success");
            return result.data; // Dữ liệu trả về đã đúng định dạng
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu định mức.", "error");
            return []; // Trả về mảng rỗng nếu có lỗi
        }
    }

    /**
     * Tạo và trả về cấu hình cột cho bảng Tabulator.
     * @returns {Array<object>}
     */
    function createColumnDefinitions() {
        return [
            { title: "ID", field: "id", width: 60, headerSort: false },
            { title: "ĐK Trong (mm)", field: "duong_kinh_trong", width: 150, editor: "number", hozAlign: "center" },
            { title: "Bản Rộng (mm)", field: "ban_rong", width: 150, editor: "number", hozAlign: "center" },
            { title: "Độ Dày (mm)", field: "do_day", width: 140, editor: "number", hozAlign: "center" },
            {
                title: "Loại Thùng", field: "loai_thung", width: 130, editor: "list",
                editorParams: { values: { "Thùng nhỏ": "Thùng nhỏ", "Thùng to": "Thùng to" } }
            },
            { title: "Số Lượng (bộ/thùng)", field: "so_luong", width: 180, editor: "number", hozAlign: "center", bottomCalc:"sum" },
            {
                title: "Thao tác", hozAlign: "center", width: 100, headerSort: false,
                formatter: (cell, formatterParams, onRendered) => `<i class="fa-solid fa-save action-icon icon-save" title="Lưu"></i> <i class="fa-solid fa-trash-can action-icon icon-delete" title="Xóa"></i>`,
                cellClick: (e, cell) => {
                    const row = cell.getRow();
                    const rowData = row.getData();
                    if (e.target.classList.contains('icon-delete')) {
                        if (confirm(`Bạn có chắc muốn xóa định mức ID: ${rowData.id}?`)) {
                            deleteDinhMuc(rowData.id, row);
                        }
                    } else if (e.target.classList.contains('icon-save')) {
                        saveDinhMucUpdate(row);
                    }
                }
            },
        ];
    }

    /**
     * Lưu các thay đổi của một dòng vào cơ sở dữ liệu.
     * @param {Tabulator.RowComponent} row - Dòng đã được chỉnh sửa.
     */
    async function saveDinhMucUpdate(row) {
        row.getElement().style.opacity = '0.5'; // Làm mờ dòng để báo hiệu đang xử lý
        try {
            const result = await fetchAPI('api/update_dinh_muc_dong_thung.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(row.getData())
            });
            showToast(result.message || 'Cập nhật định mức thành công!', 'success');
        } finally {
            row.getElement().style.opacity = '1'; // Khôi phục lại độ sáng
        }
    }

    /**
     * Xóa một định mức khỏi cơ sở dữ liệu.
     * @param {number} id - ID của định mức cần xóa.
     * @param {Tabulator.RowComponent} row - Dòng tương ứng trên bảng để xóa.
     */
    async function deleteDinhMuc(id, row) {
        try {
            const result = await fetchAPI('api/delete_dinh_muc_dong_thung.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            showToast(result.message || 'Xóa định mức thành công!', 'success');
            row.delete(); // Xóa dòng khỏi bảng sau khi thành công
        } catch (error) { /* Lỗi đã được xử lý trong fetchAPI */ }
    }

    /**
     * Áp dụng bộ lọc cho bảng dựa trên giá trị nhập vào.
     */
    function applyTableFilters() {
        const filterValue = document.getElementById("filter-field").value;
        // Tạo một mảng các bộ lọc cho các trường mong muốn
        const filters = [
            {field:"duong_kinh_trong", type:"like", value:filterValue},
            {field:"ban_rong", type:"like", value:filterValue},
            {field:"do_day", type:"like", value:filterValue},
        ];
        // Tabulator sẽ áp dụng logic OR giữa các bộ lọc trong cùng một mảng
        table.setFilter([filters]);
    }

    /**
     * Thiết lập các trình lắng nghe sự kiện cho các nút và form.
     */
    function setupEventListeners() {
        const addBtn = document.getElementById('add-btn');
        const exportExcelBtn = document.getElementById('export-excel-btn');
        const modal = document.getElementById('dinh-muc-dong-thung-modal');
        let filterTimeout; // Biến để trì hoãn việc lọc

        // Lọc dữ liệu sau khi người dùng ngừng gõ
        document.getElementById("filter-field").addEventListener("keyup", () => {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(applyTableFilters, 300); // Chờ 300ms
        });

        // Mở modal thêm mới
        addBtn.onclick = () => {
            document.getElementById('dinh-muc-form').reset();
            modal.style.display = "block";
        };

        // Đóng modal
        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('close-btn') || event.target.id === 'dinh-muc-dong-thung-modal') {
                modal.style.display = 'none';
            }
        });

        // Xử lý submit form thêm mới định mức
        document.getElementById('dinh-muc-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            try {
                const result = await fetchAPI('api/add_dinh_muc_dong_thung.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                showToast(result.message || 'Thêm định mức thành công!', 'success');
                modal.style.display = "none";
                table.addData([result.data], true); // Thêm dòng mới vào đầu bảng
            } catch (error) { /* Lỗi đã được xử lý */ }
        });

        // Xử lý sự kiện nhấn nút xuất Excel
        exportExcelBtn.addEventListener('click', function() {
            if (table) {
                showToast('Đang xuất tệp Excel...', 'info');
                // Gọi hàm download của Tabulator
                table.download("xlsx", "dinh_muc_dong_thung.xlsx", { sheetName: "Định Mức Đóng Thùng" });
            }
        });
    }

    /**
     * Hàm khởi tạo chính: tạo modal, bảng và tải dữ liệu.
     */
    async function initialize() {
        // Tạo cấu trúc HTML cho modal thêm mới
        document.getElementById('dinh-muc-dong-thung-modal').innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Thêm Định Mức Đóng Thùng Mới</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form id="dinh-muc-form">
                    <div class="form-grid">
                        <div class="form-group"> <label for="duong_kinh_trong">ĐK Trong (mm) (*)</label> <input type="number" name="duong_kinh_trong" required step="any"> </div>
                        <div class="form-group"> <label for="ban_rong">Bản Rộng (mm) (*)</label> <input type="number" name="ban_rong" required step="any"> </div>
                        <div class="form-group"> <label for="do_day">Độ Dày (mm) (*)</label> <input type="number" name="do_day" required step="any"> </div>
                        <div class="form-group"> <label for="loai_thung">Loại Thùng (*)</label>
                            <select name="loai_thung" required>
                                <option value="" disabled selected>-- Chọn loại thùng --</option>
                                <option value="Thùng nhỏ">Thùng nhỏ</option>
                                <option value="Thùng to">Thùng to</option>
                            </select>
                        </div>
                        <div class="form-group"> <label for="so_luong">Số Lượng (bộ/thùng) (*)</label> <input type="number" name="so_luong" required> </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="action-button" style="background-color: var(--primary-color);">Lưu Định Mức</button>
                    </div>
                </form>
            </div>`;

        // Khởi tạo bảng Tabulator
        table = new Tabulator("#dinh-muc-dong-thung-table", {
            height: "75vh",
            layout: "fitColumns",
            columns: createColumnDefinitions(),
            pagination: true,
            paginationMode: "local",
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100, true],
            placeholder: "Đang tải dữ liệu...",
            locale: true, // Sử dụng ngôn ngữ mặc định (nếu có)
            langs:{
                "default":{
                    "pagination":{
                        "page_size":"Kích thước trang",
                        "page_title":"Hiển thị trang",
                        "first":"Đầu",
                        "first_title":"Trang đầu",
                        "last":"Cuối",
                        "last_title":"Trang cuối",
                        "prev":"Trước",
                        "prev_title":"Trang trước",
                        "next":"Sau",
                        "next_title":"Trang sau",
                        "all":"Tất cả",
                    }
                }
            }
        });

        // Tải dữ liệu và gán vào bảng
        const tableData = await loadAllDinhMuc();
        table.setData(tableData);
        if (tableData.length === 0) {
            table.setPlaceholder("Không có dữ liệu định mức.");
        }

        // Thiết lập các trình lắng nghe sự kiện
        setupEventListeners();
    }

    // Chạy hàm khởi tạo
    initialize();
}