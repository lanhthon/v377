function initializeSupplierManagementPage(mainContentContainer) {
    const API_URL = 'api/suppliers_api.php';
    let table; // Bảng chính (danh sách nhà cung cấp)
    let productsTable; // Bảng phụ (danh sách sản phẩm trong modal)

    // Hàm gọi API chung, không thay đổi
    async function apiRequest(action, data = {}, method = 'POST', queryParams = '') {
        const url = `${API_URL}?action=${action}${queryParams}`;
        const options = {
            method: method,
            headers: { 'Content-Type': 'application/json' },
        };
        if (method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        const result = await response.json();
        if (!result.success) {
            App.showMessageModal('Lỗi', result.message || 'Có lỗi xảy ra', 'error');
            throw new Error(result.message);
        }
        return result;
    }

    async function loadSuppliers() {
        try {
            const result = await apiRequest('get_all_suppliers', {}, 'GET'); // GET request ko cần body
            table.setData(result.data);
        } catch (error) {
            console.error("Không thể tải danh sách nhà cung cấp:", error);
            table.setData([]);
        }
    }
    
    // Hàm mở form nhà cung cấp, không thay đổi
    function openSupplierForm(data = null) {
        // ... (giữ nguyên toàn bộ code của hàm này)
        const isEditing = data !== null;
        const modalTitle = isEditing ? 'Chỉnh Sửa Nhà Cung Cấp' : 'Thêm Nhà Cung Cấp Mới';
        const modalHTML = `
            <div id="supplier-modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center animate-fade-in">
                <div class="relative p-8 border w-full max-w-2xl shadow-2xl rounded-lg bg-white transform animate-scale-in">
                    <div class="flex justify-between items-center pb-4 mb-4 border-b border-gray-200">
                        <h3 class="text-2xl font-semibold text-gray-800">${modalTitle}</h3>
                        <button id="close-supplier-modal-btn" class="text-gray-400 hover:text-gray-600 text-3xl font-light">&times;</button>
                    </div>
                    <form id="supplier-form" class="space-y-6">
                        <input type="hidden" name="NhaCungCapID" value="${isEditing ? data.NhaCungCapID : ''}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="TenNhaCungCap" class="block text-sm font-medium text-gray-700 mb-1">Tên Nhà Cung Cấp (*)</label>
                                <input type="text" id="TenNhaCungCap" name="TenNhaCungCap" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="${isEditing ? (data.TenNhaCungCap || '') : ''}">
                            </div>
                            <div>
                                <label for="MaSoThue" class="block text-sm font-medium text-gray-700 mb-1">Mã Số Thuế</label>
                                <input type="text" id="MaSoThue" name="MaSoThue" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="${isEditing ? (data.MaSoThue || '') : ''}">
                            </div>
                        </div>
                        <div>
                            <label for="DiaChi" class="block text-sm font-medium text-gray-700 mb-1">Địa Chỉ</label>
                            <input type="text" id="DiaChi" name="DiaChi" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="${isEditing ? (data.DiaChi || '') : ''}">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="SoDienThoai" class="block text-sm font-medium text-gray-700 mb-1">Số Điện Thoại</label>
                                <input type="tel" id="SoDienThoai" name="SoDienThoai" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="${isEditing ? (data.SoDienThoai || '') : ''}">
                            </div>
                            <div>
                                <label for="Email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="Email" name="Email" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="${isEditing ? (data.Email || '') : ''}">
                            </div>
                        </div>
                        <div class="pt-6 border-t border-gray-200 flex justify-end space-x-4">
                            <button type="button" id="cancel-supplier-form-btn" class="px-6 py-2 bg-gray-100 text-gray-700 font-semibold rounded-md border border-gray-300 hover:bg-gray-200">Hủy</button>
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700">Lưu Lại</button>
                        </div>
                    </form>
                </div>
            </div>
            <style>
                @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
                .animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
                @keyframes scale-in { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                .animate-scale-in { animation: scale-in 0.3s ease-out forwards; }
            </style>
        `;
        mainContentContainer.find('#supplier-form-modal-placeholder').html(modalHTML);
        const closeModal = () => mainContentContainer.find('#supplier-modal').remove();
        mainContentContainer.find('#close-supplier-modal-btn, #cancel-supplier-form-btn').on('click', closeModal);
        mainContentContainer.find('#supplier-form').on('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const supplierData = Object.fromEntries(formData.entries());
            try {
                const action = isEditing ? 'update_supplier' : 'add_supplier';
                const result = await apiRequest(action, supplierData);
                App.showMessageModal('Thành công', result.message, 'success');
                closeModal();
                loadSuppliers();
            } catch (error) {}
        });
    }

    // --- MỚI: CÁC HÀM QUẢN LÝ SẢN PHẨM ---

    /**
     * Mở modal form để thêm/sửa sản phẩm của một nhà cung cấp
     * @param {object} supplier - Dữ liệu nhà cung cấp (cần ID và Tên)
     * @param {object|null} productData - Dữ liệu sản phẩm để sửa. Null nếu thêm mới.
     */
    function openProductForm(supplier, productData = null) {
        const isEditing = productData !== null;
        const modalTitle = isEditing ? 'Chỉnh Sửa Sản Phẩm' : 'Thêm Sản Phẩm Mới';

        const modalHTML = `
            <div id="product-form-modal" class="fixed inset-0 bg-gray-900 bg-opacity-70 z-[60] flex items-center justify-center animate-fade-in">
                <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-xl animate-scale-in">
                    <h3 class="text-xl font-semibold mb-4">${modalTitle}</h3>
                    <form id="product-form">
                        <input type="hidden" name="NhaCungCapID" value="${supplier.NhaCungCapID}">
                        <input type="hidden" name="SanPhamNCCID" value="${isEditing ? productData.SanPhamNCCID : ''}">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                             <div>
                                <label class="block text-sm font-medium text-gray-700">Mã Sản Phẩm</label>
                                <input name="MaSanPham" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" value="${isEditing ? (productData.MaSanPham || '') : ''}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tên Sản Phẩm (*)</label>
                                <input name="TenSanPham" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm" value="${isEditing ? (productData.TenSanPham || '') : ''}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Đơn Vị Tính</label>
                                <input name="DonViTinh" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" value="${isEditing ? (productData.DonViTinh || '') : ''}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Đơn Giá</label>
                                <input type="number" name="DonGia" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" value="${isEditing ? (productData.DonGia || 0) : 0}">
                            </div>
                        </div>
                        <div class="mt-4">
                           <label class="block text-sm font-medium text-gray-700">Ghi Chú</label>
                           <textarea name="GhiChu" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">${isEditing ? (productData.GhiChu || '') : ''}</textarea>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" id="cancel-product-btn" class="px-4 py-2 bg-gray-200 rounded-md">Hủy</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        mainContentContainer.find('#supplier-form-modal-placeholder').append(modalHTML);
        
        const closeForm = () => mainContentContainer.find('#product-form-modal').remove();
        mainContentContainer.find('#cancel-product-btn').on('click', closeForm);
        
        mainContentContainer.find('#product-form').on('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const newProductData = Object.fromEntries(formData.entries());
            try {
                const action = isEditing ? 'update_supplier_product' : 'add_supplier_product';
                const result = await apiRequest(action, newProductData);
                App.showMessageModal('Thành công', result.message, 'success');
                closeForm();
                // Tải lại danh sách sản phẩm trong bảng
                const productResult = await apiRequest('get_products_by_supplier', {}, 'GET', `&supplier_id=${supplier.NhaCungCapID}`);
                productsTable.setData(productResult.data);
            } catch(error) {}
        });
    }

    /**
     * Mở modal chính để hiển thị danh sách sản phẩm của nhà cung cấp
     * @param {object} supplierData - Dữ liệu của nhà cung cấp được chọn
     */
    function openProductsModal(supplierData) {
        const modalHTML = `
            <div id="products-modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 z-50 flex items-center justify-center animate-fade-in">
                <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col animate-scale-in">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <div>
                            <h3 class="text-2xl font-semibold">Quản lý sản phẩm</h3>
                            <p class="text-gray-600">${supplierData.TenNhaCungCap}</p>
                        </div>
                        <button id="close-products-modal-btn" class="text-gray-500 text-3xl">&times;</button>
                    </div>
                    <div class="mt-4">
                        <button id="add-product-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                           <i class="fas fa-plus mr-2"></i>Thêm Sản Phẩm
                        </button>
                    </div>
                    <div id="products-table-container" class="mt-4 flex-grow"></div>
                </div>
            </div>`;
        
        mainContentContainer.find('#supplier-form-modal-placeholder').html(modalHTML);
        
        const closeModal = () => mainContentContainer.find('#products-modal').remove();
        mainContentContainer.find('#close-products-modal-btn').on('click', closeModal);
        mainContentContainer.find('#add-product-btn').on('click', () => openProductForm(supplierData));

        // Khởi tạo bảng Tabulator cho sản phẩm
        productsTable = new Tabulator("#products-table-container", {
            height: "100%",
            layout: "fitColumns",
            placeholder: "Nhà cung cấp này chưa có sản phẩm nào.",
            columns: [
                { title: "Mã SP", field: "MaSanPham", width: 120 },
                { title: "Tên Sản Phẩm", field: "TenSanPham", minWidth: 200 },
                { title: "ĐVT", field: "DonViTinh", width: 100 },
                { title: "Đơn Giá", field: "DonGia", hozAlign: "right", formatter: "money", formatterParams: { symbol: " VNĐ", precision: 0 } },
                { title: "Ghi Chú", field: "GhiChu", minWidth: 150 },
                {
                    title: "Thao Tác", width: 100, hozAlign: "center",
                    formatter: (cell) => `<i class="fas fa-edit text-yellow-500 cursor-pointer p-1" title="Sửa"></i>
                                         <i class="fas fa-trash text-red-500 cursor-pointer p-1" title="Xóa"></i>`,
                    cellClick: (e, cell) => {
                        const productRowData = cell.getRow().getData();
                        if (e.target.classList.contains('fa-edit')) {
                            openProductForm(supplierData, productRowData);
                        } else if (e.target.classList.contains('fa-trash')) {
                            App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa sản phẩm "${productRowData.TenSanPham}"?`, async () => {
                                try {
                                    const result = await apiRequest('delete_supplier_product', { SanPhamNCCID: productRowData.SanPhamNCCID });
                                    App.showMessageModal('Thành công', result.message, 'success');
                                    cell.getRow().delete(); // Xóa dòng khỏi bảng
                                } catch (error) {}
                            });
                        }
                    }
                }
            ]
        });

        // Tải dữ liệu sản phẩm cho nhà cung cấp này
        apiRequest('get_products_by_supplier', {}, 'GET', `&supplier_id=${supplierData.NhaCungCapID}`)
            .then(result => productsTable.setData(result.data))
            .catch(error => console.error("Lỗi tải sản phẩm:", error));
    }


    function initializeTable() {
        table = new Tabulator("#supplier-table", {
            height: "65vh",
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 15,
            paginationSizeSelector: [10, 15, 25, 50],
            placeholder: "Không có dữ liệu nhà cung cấp",
            columns: [
                // Các cột cũ giữ nguyên
                { title: "ID", field: "NhaCungCapID", width: 70, hozAlign: "center" },
                { title: "Tên Nhà Cung Cấp", field: "TenNhaCungCap", minWidth: 200, headerFilter: "input" },
                { title: "Địa Chỉ", field: "DiaChi", minWidth: 250 },
                { title: "Điện Thoại", field: "SoDienThoai", width: 130 },
                { title: "Mã Số Thuế", field: "MaSoThue", width: 130 },
                {
                    title: "Thao Tác", width: 150, hozAlign: "center", headerSort: false, // Tăng độ rộng cột
                    formatter: (cell) => `
                        <i class="fas fa-box-open text-blue-500 hover:text-blue-700 cursor-pointer p-2 product-btn" title="Sản phẩm"></i>
                        <i class="fas fa-edit text-yellow-500 hover:text-yellow-700 cursor-pointer p-2 edit-btn" title="Sửa NCC"></i>
                        <i class="fas fa-trash text-red-500 hover:text-red-700 cursor-pointer p-2 delete-btn" title="Xóa NCC"></i>`,
                    cellClick: (e, cell) => {
                        const rowData = cell.getRow().getData();
                        if (e.target.classList.contains('product-btn')) { // Nút sản phẩm MỚI
                            openProductsModal(rowData);
                        } else if (e.target.classList.contains('edit-btn')) {
                            openSupplierForm(rowData);
                        } else if (e.target.classList.contains('delete-btn')) {
                            App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa nhà cung cấp "${rowData.TenNhaCungCap}"? Việc này sẽ xóa TẤT CẢ sản phẩm liên quan.`, async () => {
                                try {
                                    const result = await apiRequest('delete_supplier', { NhaCungCapID: rowData.NhaCungCapID });
                                    App.showMessageModal('Thành công', result.message, 'success');
                                    loadSuppliers();
                                } catch (error) {}
                            });
                        }
                    }
                }
            ],
        });
        loadSuppliers();
    }

    function setupEventListeners() {
        mainContentContainer.on('click', '#add-supplier-btn', () => openSupplierForm());
    }

    // --- KHỞI CHẠY ---
    initializeTable();
    setupEventListeners();
}