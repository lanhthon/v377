/* eslint-disable no-unused-vars */
/**
 * Initialize function for the Detailed Product Management page.
 */
function initializeQuanLySanPhamPage(mainContentContainer) {

    // --- VARIABLES AND CONSTANTS DECLARATION ---
    const API_URL = 'api/api.php';
    let variantsTable;
    let variantFormState = {};
    let currentVariantId = null;
    let shouldRestoreVariantForm = false;
    let allAttributes = []; // Variable to store all attributes
    let allBaseProducts = []; // Variable to store all base products

    // Custom MIME type for builder drag-and-drop operations (to avoid default browser text/html behavior)
    const DND_MIME = 'application/x-qsp-builder';

    // --- UTILITY FUNCTIONS ---
    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        if (!toast) return;
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => {
            toast.className = toast.className.replace("show", "");
        }, 4000);
    }

    async function apiRequest(action, data = {}) {
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (!result.success) {
                App.showMessageModal(result.message || 'Lỗi API không xác định', 'error');
                throw new Error(result.message || 'Lỗi API không xác định');
            }
            return result.data;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    function populateSelect(selectEl, data, valueField, textField, addDefault = true) {
        selectEl.innerHTML = addDefault ? '<option value="">-- Chọn --</option>' : '';
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            if (item.base_sku) option.dataset.baseSku = item.base_sku;
            if (item.sku_prefix) option.dataset.skuPrefix = item.sku_prefix;
            if (item.name_prefix) option.dataset.namePrefix = item.name_prefix;
            if (item.attribute_config) option.dataset.attributeConfig = JSON.stringify(item.attribute_config);
            if (item.sku_name_formula) option.dataset.skuNameFormula = JSON.stringify(item.sku_name_formula);
            selectEl.appendChild(option);
        });
    }

    function createModal(id) {
        let modal = document.getElementById(id);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = id;
            mainContentContainer[0].appendChild(modal);
        }
        modal.className = 'modal';
        return modal;
    }

    // --- [UPDATED] STATS FUNCTIONS ---
    async function loadAndDisplayStats() {
        try {
            const stats = await apiRequest('get_product_stats');
            document.getElementById('stats-total-products').textContent = stats.totalProducts || 0;
            document.getElementById('stats-total-base-products').textContent = stats.totalBaseProducts || 0;
            document.getElementById('stats-total-columns').textContent = stats.totalColumns || 0;
            document.getElementById('stats-total-pur').textContent = stats.totalPUR || 0;

            // Format ULA stats as "Có / Tổng"
            const totalUla = stats.totalULA || 0;
            const ulaDmdt = stats.ulaWithDinhMucDongThung || 0;
            const ulaDmkg = stats.ulaWithDinhMucKgBo || 0;

            document.getElementById('stats-ula-dmdt').textContent = `${ulaDmdt} / ${totalUla}`;
            document.getElementById('stats-ula-dmkg').textContent = `${ulaDmkg} / ${totalUla}`;

        } catch (error) {
            console.error('Failed to load product stats:', error);
            // Error handling
            const statsIds = ['stats-total-products', 'stats-total-base-products', 'stats-total-columns', 'stats-total-pur', 'stats-ula-dmdt', 'stats-ula-dmkg'];
            statsIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = 'Lỗi';
            });
        }
    }


    // --- PRODUCT TABLE (TABULATOR) & FILTERS ---
    async function initializeTable() {
        // Lấy tất cả các thuộc tính để tạo cột một cách linh hoạt
        let attributeColumns = [];
        try {
            // Sử dụng API get_all_attributes để lấy danh sách thuộc tính
            const allAttributesData = await apiRequest('get_all_attributes');
            
            // Sắp xếp các thuộc tính dựa trên order_index, những thuộc tính không có order_index sẽ được xếp cuối
            allAttributesData.sort((a, b) => (a.order_index || 999) - (b.order_index || 999));
            
            // Tạo mảng các cột từ dữ liệu thuộc tính
            attributeColumns = allAttributesData.map(attr => ({
                title: attr.name,
                field: attr.name, // API 'get_all_variants_flat' phải trả về các trường có tên này
                width: 150,
                hozAlign: "center",
                headerFilter: "input" // Thêm bộ lọc cho cột
            }));
        } catch (error) {
            console.error("Không thể tải thuộc tính cho các cột của bảng:", error);
        }

        const baseColumns = [
            {
                formatter: "rowSelection",
                titleFormatter: "rowSelection",
                hozAlign: "center",
                headerSort: false,
                width: 60,
                frozen: true,
                cellClick: function(e, cell) {
                    cell.getRow().toggleSelect();
                }
            },
            { title: "ID", field: "variant_id", width: 60, frozen: true },
            { title: "Mã SKU", field: "variant_sku", width: 180, frozen: true },
            { title: "Tên Biến Thể", field: "variant_name", width: 250 },
            {
                title: "Tên SP Gốc",
                field: "product_name",
                width: 250,
                formatter: function(cell, formatterParams, onRendered) {
                    const data = cell.getRow().getData();
                    return `<div class="product-name-cell"><span class="product-name-text">${data.product_name}</span><i class="fa-solid fa-edit product-action-icon" title="Sửa SP Gốc" data-product-id="${data.product_id}"></i></div>`;
                },
                cellClick: function(e, cell) {
                    if (e.target.classList.contains('product-action-icon')) {
                        const productId = e.target.dataset.productId;
                        if (productId) openProductForm(productId);
                    }
                }
            },
            { title: "Nhóm SP", field: "group_name", width: 150 },
            { title: "Loại Phân Loại", field: "loai_name", width: 250 },
            // THÊM CỘT "Đơn vị tính" VÀO BẢNG HIỂN THỊ
            { title: "Đơn vị tính", field: "base_unit_name", width: 120, hozAlign: "center" },
        ];
        
        // Các cột đặc biệt không nằm trong bảng attributes
        const specialColumns = [
             {
                title: "ID ống đồng 2",
                field: "ID ống đồng 2",
                width: 150,
                hozAlign: "center"
            },
        ];

        const finalColumns = [
            {
                title: "Giá",
                field: "price",
                width: 120,
                hozAlign: "right",
                formatter: "money",
                formatterParams: { symbol: " ₫" }
            },
            {
                title: "Tồn kho",
                field: "inventory_display",
                width: 200,
                hozAlign: "left"
            },
            {
                title: "Hậu tố SKU",
                field: "sku_suffix",
                width: 120,
                hozAlign: "center"
            },
            { field: "minimum_stock_level", visible: false },
            { field: "base_unit_name", visible: false },
            { field: "quantity_in_base_unit", visible: false },
            {
                title: "Thao Tác",
                hozAlign: "center",
                width: 160,
                headerSort: false,
                frozen: true,
                formatter: (cell) => `<i class="fa-solid fa-edit action-icon icon-edit" title="Sửa Biến thể"></i><i class="fa-solid fa-boxes-packing action-icon icon-inventory" title="Quản lý Tồn kho"></i><i class="fa-solid fa-trash-can action-icon icon-delete" title="Xóa Biến thể"></i>`,
                cellClick: (e, cell) => {
                    e.stopPropagation();
                    const data = cell.getRow().getData();
                    if (e.target.closest('.icon-edit')) {
                        openVariantForm(data.variant_id);
                    } else if (e.target.closest('.icon-inventory')) {
                        openInventoryManager(data.variant_id, data.variant_sku);
                    } else if (e.target.closest('.icon-delete')) {
                        App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa sản phẩm "${data.variant_name}"?`, () => {
                            apiRequest('delete_multiple_variants', {
                                ids: [data.variant_id]
                            }).then(() => {
                                showToast('Đã xóa sản phẩm thành công.');
                                loadAllVariants();
                            });
                        });
                    }
                }
            }
        ];

        const allTableColumns = [...baseColumns, ...attributeColumns, ...specialColumns, ...finalColumns];

        variantsTable = new Tabulator("#variants-table", {
            height: "75vh",
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [10, 25, 50, 100, true],
            placeholder: "Đang tải dữ liệu...",
            selectableRows: true,
            index: "variant_id",
            initialSort: [{
                column: "variant_id",
                dir: "desc"
            }],
            rowSelectionChanged: function(data, rows) {
                const deleteBtn = document.getElementById('delete-selected-btn');
                const count = data.length;
                if (count > 0) {
                    deleteBtn.innerHTML = `<i class="fa fa-trash-can"></i> Xóa (${count}) mục`;
                    deleteBtn.style.display = 'inline-flex';
                } else {
                    deleteBtn.style.display = 'none';
                }
            },
            dataLoaded: function(data) {
                document.getElementById('total-variants').textContent = `Tổng số: ${data.length} sản phẩm`;
            },
            columns: allTableColumns, // Sử dụng mảng cột đã được tạo tự động
        });
        loadAllVariants();
    }


    async function loadAllVariants() {
        try {
            const data = await apiRequest('get_all_variants_flat');
            variantsTable.setData(data);
            const pageSizeSelect = document.getElementById('page-size-select');
            if (pageSizeSelect.value === 'all') {
                variantsTable.setPageSize(data.length);
            } else {
                variantsTable.setPageSize(parseInt(pageSizeSelect.value));
            }
        } catch (error) {
            variantsTable.setPlaceholder("Lỗi tải dữ liệu.");
        }
    }

    async function initializeFilters() {
        const groupSelect = document.getElementById('filter-group-id');
        const loaiSelect = document.getElementById('filter-loai-id');
        const pageSizeSelect = document.getElementById('page-size-select');
        try {
            const [groups, loai] = await Promise.all([
                apiRequest('get_product_groups'),
                apiRequest('get_all_loai')
            ]);
            populateSelect(groupSelect, groups, 'group_id', 'name');
            populateSelect(loaiSelect, loai, 'LoaiID', 'TenLoai');
        } catch (error) {
            console.error("Lỗi tải bộ lọc");
        }
        document.getElementById('filter-field').addEventListener('keyup', applyTableFilters);
        groupSelect.addEventListener('change', applyTableFilters);
        loaiSelect.addEventListener('change', applyTableFilters);
        pageSizeSelect.addEventListener('change', function() {
            const selectedSize = this.value;
            if (selectedSize === 'all') {
                variantsTable.setPageSize(variantsTable.getDataCount());
            } else {
                variantsTable.setPageSize(parseInt(selectedSize));
            }
        });
    }

    function applyTableFilters() {
        const filters = [];
        const textValue = document.getElementById('filter-field').value;
        const groupId = document.getElementById('filter-group-id').value;
        const loaiId = document.getElementById('filter-loai-id').value;
        if (textValue) {
            filters.push([{
                field: 'variant_sku',
                type: 'like',
                value: textValue
            }, {
                field: 'variant_name',
                type: 'like',
                value: textValue
            }, {
                field: 'product_name',
                type: 'like',
                value: textValue
            }]);
        }
        if (groupId) {
            filters.push({
                field: 'group_id',
                type: '=',
                value: groupId
            });
        }
        if (loaiId) {
            filters.push({
                field: 'LoaiID',
                type: '=',
                value: loaiId
            });
        }
        variantsTable.setFilter(filters);
    }

    // --- PRODUCT MANAGEMENT FUNCTIONS ---
    async function openProductListManager() {
        const modal = createModal('product-list-modal');
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h2>Quản lý Sản phẩm gốc</h2>
                    <button id="add-new-product-btn" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm mới</button>
                    <span class="close-btn">&times;</span>
                </div>
                <div id="product-list-container" class="space-y-2 overflow-y-auto" style="max-height: 70vh;">
                    Đang tải...
                </div>
            </div>
        `;
        modal.style.display = 'block';
        
        document.getElementById('add-new-product-btn').addEventListener('click', () => {
            modal.style.display = 'none';
            openProductForm();
        });

        const container = document.getElementById('product-list-container');
        await loadProductList(container);
    }

    async function loadProductList(container) {
        try {
            const products = await apiRequest('get_all_base_products_list');
            container.innerHTML = '';
            if (products.length === 0) {
                container.innerHTML = '<p>Không có sản phẩm gốc nào.</p>';
            } else {
                products.forEach(product => {
                    const productEl = document.createElement('div');
                    productEl.className = 'flex items-center justify-between p-3 border rounded-md bg-gray-50';
                    productEl.innerHTML = `
                        <div>
                            <p class="font-bold">${product.name} <span class="text-gray-500 text-sm">(${product.base_sku})</span></p>
                            <p class="text-sm text-gray-600">Nhóm: ${product.group_name || 'N/A'}</p>
                        </div>
                        <div class="space-x-2">
                            <button class="btn btn-sm btn-primary edit-product-btn" data-id="${product.product_id}" title="Sửa"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger delete-product-btn" data-id="${product.product_id}" data-name="${product.name}" title="Xóa"><i class="fa fa-trash-can"></i></button>
                        </div>
                    `;
                    container.appendChild(productEl);
                });

                container.querySelectorAll('.edit-product-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.getElementById('product-list-modal').style.display = 'none';
                        openProductForm(btn.dataset.id);
                    });
                });

                container.querySelectorAll('.delete-product-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const productId = btn.dataset.id;
                        const productName = btn.dataset.name;
                        App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc chắn muốn xóa sản phẩm gốc "${productName}"?`, async () => {
                            try {
                                await apiRequest('delete_product', { product_id: productId });
                                showToast('Đã xóa sản phẩm gốc thành công.');
                                loadProductList(container);
                                loadAllVariants(); // Cập nhật lại bảng biến thể
                            } catch (error) {
                                App.showMessageModal(error.message || 'Không thể xóa sản phẩm gốc này.', 'error');
                            }
                        });
                    });
                });
            }
        } catch (error) {
            container.innerHTML = '<p class="text-red-500">Lỗi tải danh sách sản phẩm gốc.</p>';
        }
    }


    async function openProductForm(productId = null) {
        const modal = createModal('product-form-modal');
        modal.innerHTML = `<div class="modal-content" style="max-width: 700px;"><div class="modal-header"><h2 id="product-form-title"></h2><span class="close-btn">&times;</span></div><form id="product-form"></form></div>`;
        const form = modal.querySelector('#product-form');
        const title = modal.querySelector('#product-form-title');
        let details = {};
        const [groups, units, allAttributesData] = await Promise.all([
            apiRequest('get_product_groups'),
            apiRequest('get_all_units'),
            apiRequest('get_all_attributes')
        ]);
        allAttributes = allAttributesData; // Store all attributes
        if (productId) {
            title.textContent = "Chỉnh sửa Sản phẩm gốc";
            details = await apiRequest('get_product_details', {
                product_id: productId
            });
            details.attribute_config = JSON.parse(details.attribute_config || '[]');
            details.sku_name_formula = JSON.parse(details.sku_name_formula || '{}');
        } else {
            title.textContent = "Thêm Sản phẩm gốc mới";
        }

        let attributeCheckboxes = '';
        allAttributes.forEach(attr => {
            const isChecked = details.attribute_config && details.attribute_config.includes(parseInt(attr.attribute_id)) ? 'checked' : '';
            attributeCheckboxes += `
                <div class="flex items-center">
                    <input id="attr-${attr.attribute_id}" type="checkbox" name="attribute_config[]" value="${attr.attribute_id}" ${isChecked} class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="attr-${attr.attribute_id}" class="ml-2 text-sm font-medium text-gray-900">${attr.name}</label>
                </div>
            `;
        });

        const defaultFormula = {
            sku_attributes: [],
            name_attributes: [],
            sku_prefix: false,
            name_prefix: false
        };
        const currentFormula = details.sku_name_formula || defaultFormula;
        
        // Parse JSON strings to objects if they exist
        if (typeof currentFormula.sku_attributes === 'string') {
            try { currentFormula.sku_attributes = JSON.parse(currentFormula.sku_attributes); } catch (e) { currentFormula.sku_attributes = []; }
        }
        if (typeof currentFormula.name_attributes === 'string') {
            try { currentFormula.name_attributes = JSON.parse(currentFormula.name_attributes); } catch (e) { currentFormula.name_attributes = []; }
        }

        const attributesForBuilder = allAttributes.map(attr =>
            `<div class="p-2 border rounded-md bg-gray-200 text-gray-800 cursor-grab attribute-block"
                 draggable="true"
                 data-name="${attr.name}"
                 data-type="attribute">${attr.name}</div>`
        ).join('');

        const separatorBlocks = `
            <div class="p-2 border rounded-md bg-gray-200 text-gray-800 cursor-grab attribute-block"
                 draggable="true"
                 data-type="separator"
                 data-name="x">Dấu phân cách (x)</div>
            <div class="p-2 border rounded-md bg-gray-200 text-gray-800 cursor-grab attribute-block"
                 draggable="true"
                 data-type="separator"
                 data-name="-">Dấu phân cách (-)</div>
            <div class="p-2 border rounded-md bg-gray-200 text-gray-800 cursor-grab attribute-block"
                 draggable="true"
                 data-type="separator"
                 data-name="/">Dấu phân cách (/)</div>
        `;

        const buildFormulaHtml = (formulaArray) => {
            let html = '';
            formulaArray.forEach(item => {
                // Check if the item name corresponds to a known attribute
                const isAttribute = allAttributes.some(attr => attr.name === item);
                if (!isAttribute) { // It's a separator
                    html += `<div class="formula-item flex items-center mb-1" draggable="true" data-name="${item}" data-type="separator">
                                <input type="text" value="${item}" placeholder="Dấu phân cách" class="p-2 border rounded-md flex-grow" draggable="false">
                                <button type="button" class="btn bg-red-500 text-white p-2 rounded remove-formula-item ml-2">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>`;
                } else { // It's an attribute
                    html += `<div class="formula-item flex items-center mb-1" draggable="true" data-name="${item}" data-type="attribute">
                                <span class="p-2 border rounded-md bg-blue-100 text-blue-800 flex-grow">${item}</span>
                                <button type="button" class="btn bg-red-500 text-white p-2 rounded remove-formula-item ml-2">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>`;
                }
            });
            return html;
        }

        form.innerHTML = `
            <input type="hidden" name="product_id" value="${productId || ''}">
            <div class="form-grid">
                <div class="form-group"><label>Tên Sản phẩm gốc</label><input type="text" name="name" required value="${details.name || ''}"></div>
                <div class="form-group"><label>Mã Gốc (Base SKU)</label><input type="text" name="base_sku" required value="${details.base_sku || ''}"></div>
                <div class="form-group"><label>Tiền tố Mã (SKU Prefix)</label><input type="text" name="sku_prefix" value="${details.sku_prefix || ''}" placeholder="Ví dụ: PUR-S"></div>
                <div class="form-group"><label>Tiền tố Tên (Name Prefix)</label><input type="text" name="name_prefix" value="${details.name_prefix || ''}" placeholder="Ví dụ: Gối đỡ đế vuông"></div>
                <div class="form-group"><label>Nhóm Sản phẩm</label><select name="group_id" required></select></div>
                <div class="form-group"><label>Đơn vị cơ sở</label><select name="base_unit_id" required></select></div>
            </div>
            <hr style="margin: 15px 0;"><h4 style="margin-bottom: 15px;">Cấu hình công thức SKU và Tên</h4>
            
            <div class="space-y-4">
                <div class="w-full">
                    <h5 class="font-bold mb-2">Công thức SKU</h5>
                    <div id="sku-formula-builder" class="h-32 p-2 border-2 border-dashed rounded-md mb-2 bg-gray-50 overflow-y-auto">
                        ${buildFormulaHtml(currentFormula.sku_attributes || [])}
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" name="sku_prefix_toggle" ${currentFormula.sku_prefix ? 'checked' : ''}> <span class="ml-2">Sử dụng Tiền tố Mã</span>
                    </div>

                    <h5 class="font-bold mb-2">Công thức Tên</h5>
                    <div id="name-formula-builder" class="h-32 p-2 border-2 border-dashed rounded-md bg-gray-50 overflow-y-auto">
                        ${buildFormulaHtml(currentFormula.name_attributes || [])}
                    </div>
                    <div class="flex items-center mb-4">
                        <input type="checkbox" name="name_prefix_toggle" ${currentFormula.name_prefix ? 'checked' : ''}> <span class="ml-2">Sử dụng Tiền tố Tên</span>
                    </div>
                </div>
                <div class="w-full">
                    <h5 class="font-bold mb-2">Các thành phần kéo thả</h5>
                    <div id="attribute-builder-toolbox" class="flex flex-wrap gap-2 p-2 border rounded-md bg-gray-100">
                        ${attributesForBuilder}
                        ${separatorBlocks}
                    </div>
                </div>
            </div>
            
            <div class="form-preview mt-4 p-4 bg-gray-100 rounded-md">
                <h5 class="font-bold">Xem trước kết quả (với hậu tố: TQ, giá trị mẫu: 50):</h5>
                <p><strong>Mã SKU mẫu:</strong> <span id="preview-sku"></span></p>
                <p><strong>Tên Biến Thể mẫu:</strong> <span id="preview-name"></span></p>
            </div>
            

            <hr style="margin: 15px 0;"><h4 style="margin-bottom: 15px;">Cấu hình thuộc tính cho sản phẩm con</h4>
            <div id="attribute-config-grid" class="form-grid grid-cols-2">
                ${attributeCheckboxes}
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu</button></div>`;

        const groupSelect = form.querySelector('select[name="group_id"]');
        const baseUnitSelect = form.querySelector('select[name="base_unit_id"]');
        populateSelect(groupSelect, groups, 'group_id', 'name', false);
        populateSelect(baseUnitSelect, units, 'unit_id', 'name', false);
        if (productId) {
            groupSelect.value = details.group_id;
            baseUnitSelect.value = details.base_unit_id;
        }

        const skuFormulaBuilder = form.querySelector('#sku-formula-builder');
        const nameFormulaBuilder = form.querySelector('#name-formula-builder');
        const toolbox = form.querySelector('#attribute-builder-toolbox');
        const skuPrefixToggle = form.querySelector('input[name="sku_prefix_toggle"]');
        const namePrefixToggle = form.querySelector('input[name="name_prefix_toggle"]');
        const previewSkuEl = form.querySelector('#preview-sku');
        const previewNameEl = form.querySelector('#preview-name');
        
        const stopDragOnInputs = (rootEl) => {
            rootEl.querySelectorAll('.formula-item input').forEach(inp => {
                inp.setAttribute('draggable', 'false');
                inp.addEventListener('dragstart', ev => {
                    ev.preventDefault();
                    ev.stopPropagation();
                });
                inp.addEventListener('mousedown', ev => {
                    ev.stopPropagation();
                });
            });
        };

        let draggedItem = null;

        const updateFormulaPreview = () => {
            const getFormulaParts = (container) => {
                const parts = [];
                container.querySelectorAll('.formula-item').forEach(item => {
                    const type = item.dataset.type;
                    if (type === 'attribute') {
                        parts.push(item.dataset.name);
                    } else if (type === 'separator') {
                        const val = (item.querySelector('input')?.value ?? '').toString();
                        parts.push(val);
                        item.dataset.name = val;
                    }
                });
                return parts;
            };

            const skuParts = getFormulaParts(skuFormulaBuilder);
            const nameParts = getFormulaParts(nameFormulaBuilder);
            const skuPrefix = skuPrefixToggle.checked ? form.querySelector('[name="sku_prefix"]').value : '';
            const namePrefix = namePrefixToggle.checked ? form.querySelector('[name="name_prefix"]').value : '';

            let previewSku = '';
            if (skuPrefix) previewSku += skuPrefix + ' ';
            previewSku += skuParts.map(part => {
                if (allAttributes.some(attr => attr.name === part)) return '50';
                return part;
            }).join('').replace(/ +/g, ' ').trim();
            previewSku += '-TQ';

            let previewName = '';
            if (namePrefix) previewName += namePrefix + ' ';
            previewName += nameParts.map(part => {
                if (allAttributes.some(attr => attr.name === part)) return '50';
                return part;
            }).join('').replace(/ +/g, ' ').trim();
            previewName += ' TQ';

            previewSkuEl.textContent = previewSku;
            previewNameEl.textContent = previewName;
        };
        
        const onDragStart = (e) => {
            const targetItem = e.target.closest('.attribute-block, .formula-item');
            if (!targetItem) return;
            if (e.target.tagName === 'INPUT') {
                e.preventDefault();
                return;
            }
            draggedItem = targetItem;
            let type = targetItem.dataset.type;
            let name = targetItem.dataset.name;
            if (type === 'separator' && targetItem.closest('.formula-item')) {
                const inp = targetItem.querySelector('input');
                if (inp) name = inp.value || '';
            }
            const payload = {
                type,
                name: name || '',
                origin: targetItem.closest('#attribute-builder-toolbox') ? 'toolbox' : 'builder'
            };
            try {
                e.dataTransfer.setData(DND_MIME, JSON.stringify(payload));
                e.dataTransfer.setData('text/plain', 'QSP_BUILDER');
            } catch (_) { /* ignore */ }
            e.dataTransfer.effectAllowed = 'move';
            requestAnimationFrame(() => {
                if (draggedItem) {
                    draggedItem.classList.add('opacity-50', 'dragging');
                }
            });
        };

        const onDragOver = (e) => {
            e.preventDefault();
            const dropZone = e.target.closest('#sku-formula-builder, #name-formula-builder');
            if (dropZone) {
                e.dataTransfer.dropEffect = 'move';
            }
        };

        const onDragEnd = (e) => {
            if (draggedItem) {
                draggedItem.classList.remove('opacity-50', 'dragging');
                draggedItem = null;
            }
        };

        const onDrop = (e) => {
            e.preventDefault();
            const dropZone = e.target.closest('#sku-formula-builder, #name-formula-builder');
            if (!dropZone) return;

            const existingItem = e.target.closest('.formula-item');

            if (draggedItem && draggedItem.closest('#sku-formula-builder, #name-formula-builder')) {
                if (existingItem && existingItem !== draggedItem) {
                    existingItem.parentNode.insertBefore(draggedItem, existingItem);
                } else if (!existingItem) {
                    dropZone.appendChild(draggedItem);
                }
                updateFormulaPreview();
                stopDragOnInputs(form);
                return;
            }

            const types = Array.from(e.dataTransfer.types || []);
            if (!types.includes(DND_MIME)) {
                return;
            }

            let data;
            try {
                const raw = e.dataTransfer.getData(DND_MIME);
                if (!raw) return;
                data = JSON.parse(raw);
            } catch (err) {
                console.error("Invalid DnD Payload:", err);
                return;
            }

            const newItem = document.createElement('div');
            newItem.className = 'formula-item flex items-center mb-1';
            newItem.draggable = true;
            newItem.dataset.type = data.type;
            newItem.dataset.name = data.name || '';

            if (data.type === 'attribute') {
                newItem.innerHTML = `
                    <span class="p-2 border rounded-md bg-blue-100 text-blue-800 flex-grow">${data.name || ''}</span>
                    <button type="button" class="btn bg-red-500 text-white p-2 rounded remove-formula-item ml-2">
                        <i class="fa fa-minus"></i>
                    </button>`;
            } else if (data.type === 'separator') {
                newItem.innerHTML = `
                    <input type="text"
                           value="${data.name || ''}"
                           placeholder="Dấu phân cách"
                           class="p-2 border rounded-md flex-grow"
                           draggable="false">
                    <button type="button" class="btn bg-red-500 text-white p-2 rounded remove-formula-item ml-2">
                        <i class="fa fa-minus"></i>
                    </button>`;
            }

            if (existingItem) {
                existingItem.parentNode.insertBefore(newItem, existingItem);
            } else {
                dropZone.appendChild(newItem);
            }

            updateFormulaPreview();
            stopDragOnInputs(form);
        };

        // Attach DnD events
        toolbox.addEventListener('dragstart', onDragStart);
        skuFormulaBuilder.addEventListener('dragstart', onDragStart);
        nameFormulaBuilder.addEventListener('dragstart', onDragStart);

        skuFormulaBuilder.addEventListener('dragover', onDragOver);
        skuFormulaBuilder.addEventListener('drop', onDrop);
        nameFormulaBuilder.addEventListener('dragover', onDragOver);
        nameFormulaBuilder.addEventListener('drop', onDrop);

        form.addEventListener('dragend', onDragEnd);
        stopDragOnInputs(form);

        form.addEventListener('change', (e) => {
            if (e.target.closest('input[name="sku_prefix_toggle"], input[name="name_prefix_toggle"]')) {
                updateFormulaPreview();
            }
        });

        form.addEventListener('click', (e) => {
            if (e.target.closest('.remove-formula-item')) {
                e.target.closest('.formula-item').remove();
                updateFormulaPreview();
            }
        });
        form.addEventListener('input', (e) => {
            if (e.target.closest('#sku-formula-builder input, #name-formula-builder input')) {
                updateFormulaPreview();
            }
        });
        
        form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const dataToSave = Object.fromEntries(formData.entries());
            dataToSave.attribute_config = formData.getAll('attribute_config[]').map(id => parseInt(id));

            const buildFormula = (container) => {
                const parts = [];
                container.querySelectorAll('.formula-item').forEach(item => {
                    const type = item.dataset.type;
                    if (type === 'attribute') {
                        parts.push(item.dataset.name);
                    } else if (type === 'separator') {
                        parts.push(item.querySelector('input').value);
                    }
                });
                return parts;
            };

            dataToSave.sku_name_formula = JSON.stringify({
                sku_prefix: skuPrefixToggle.checked,
                sku_attributes: buildFormula(skuFormulaBuilder),
                name_prefix: namePrefixToggle.checked,
                name_attributes: buildFormula(nameFormulaBuilder),
            });
            dataToSave.attribute_config = JSON.stringify(dataToSave.attribute_config);

            try {
                await apiRequest('save_product', {
                    data: dataToSave
                });
                showToast('Lưu sản phẩm gốc thành công!');
                modal.style.display = 'none';
                loadAllVariants();
            } catch (error) {}
        };
        modal.style.display = 'block';
        updateFormulaPreview();
    }

    async function openVariantForm(variantId = null) {
        const modal = createModal('variant-form-modal');
        modal.innerHTML = `<div class="modal-content"><div class="modal-header"><h2 id="variant-form-title"></h2><span class="close-btn">&times;</span></div><form id="variant-form"></form></div>`;
        const form = modal.querySelector('#variant-form');
        const title = modal.querySelector('#variant-form-title');
        currentVariantId = variantId;
        let details = {};
        if (variantId) {
            title.textContent = "Chỉnh Sửa Sản Phẩm Chi Tiết";
            if (!shouldRestoreVariantForm) {
                details = await apiRequest('get_variant_details', {
                    variant_id: variantId
                });
                variantFormState = { ...details
                };
            } else {
                details = { ...variantFormState
                };
            }
        } else {
            title.textContent = "Thêm Sản phẩm Chi Tiết Mới";
            if (!shouldRestoreVariantForm) {
                details = {};
                variantFormState = {};
            } else {
                details = { ...variantFormState
                };
            }
        }
        shouldRestoreVariantForm = false;

        const [allAttributesData, allLoai, allBaseProductsData] = await Promise.all([
            apiRequest('get_all_attributes'),
            apiRequest('get_all_loai'),
            apiRequest('get_all_base_products')
        ]);
        allAttributes = allAttributesData;
        allBaseProducts = allBaseProductsData.map(p => {
            if (typeof p.attribute_config === 'string') {
                p.attribute_config = JSON.parse(p.attribute_config);
            }
            if (typeof p.sku_name_formula === 'string') {
                p.sku_name_formula = JSON.parse(p.sku_name_formula);
            }
            return p;
        });

        let formHTML = `
            <input type="hidden" name="variant_id" value="${currentVariantId || ''}">
            <div class="form-grid">
                <div class="form-group"><label>Sản phẩm gốc (*)</label><select name="product_id" required></select></div>
                <div class="form-group"><label>Tên Biến Thể</label><input type="text" name="variant_name" required value="${details.variant_name || ''}"></div>
                <div class="form-group"><label>Mã Biến Thể (SKU)</label><input type="text" name="variant_sku" required value="${details.variant_sku || ''}" readonly></div>
                <div class="form-group"><label>Hậu tố SKU (ví dụ: TQ, HT)</label><input type="text" name="sku_suffix" value="${details.sku_suffix || ''}"></div>
                <div class="form-group"><label>Giá Gốc</label><input type="number" name="price" value="${details.price || 0}"></div>
                <div class="form-group"><label>Loại Phân Loại</label><select name="LoaiID"></select></div>
            </div>
            <hr style="margin: 15px 0;"><h4 style="margin-bottom: 15px;">Các thuộc tính</h4>
            <div class="form-grid" id="attribute-form-grid"></div>
            <div class="form-preview mt-4 p-4 bg-gray-100 rounded-md">
                <h5 class="font-bold">Xem trước kết quả:</h5>
                <p><strong>Mã SKU:</strong> <span id="preview-sku"></span></p>
                <p><strong>Tên Biến Thể:</strong> <span id="preview-name"></span></p>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu Biến Thể</button></div>`;
        form.innerHTML = formHTML;

        const baseProductSelect = form.querySelector('select[name="product_id"]');
        const loaiSelect = form.querySelector('select[name="LoaiID"]');
        const skuSuffixInput = form.querySelector('[name="sku_suffix"]');
        const variantSkuInput = form.querySelector('[name="variant_sku"]');
        const attributeFormGrid = form.querySelector('#attribute-form-grid');
        const variantNameInput = form.querySelector('[name="variant_name"]');
        const previewSkuEl = form.querySelector('#preview-sku');
        const previewNameEl = form.querySelector('#preview-name');

        populateSelect(baseProductSelect, allBaseProducts, 'product_id', 'name', false);
        populateSelect(loaiSelect, allLoai, 'LoaiID', 'TenLoai', true);

        if (details.product_id) baseProductSelect.value = details.product_id;
        if (details.LoaiID) loaiSelect.value = details.LoaiID;

        const updateAttributeFields = () => {
            const selectedProduct = allBaseProducts.find(p => p.product_id == baseProductSelect.value);
            attributeFormGrid.innerHTML = '';
            if (selectedProduct && selectedProduct.attribute_config) {
                const config = selectedProduct.attribute_config;
                config.forEach(attrId => {
                    const attr = allAttributes.find(a => a.attribute_id == attrId);
                    if (attr) {
                        const selectedOptionId = details.selected_options ? details.selected_options.find(optId => attr.options.some(o => Number(o.option_id) === Number(optId))) : null;
                        const optionsHTML = attr.options.map(opt => `<option value="${opt.option_id}" ${selectedOptionId && Number(opt.option_id) === Number(selectedOptionId) ? 'selected' : ''}>${opt.value}</option>`).join('');

                        const fieldHTML = `
                            <div class="form-group">
                                <label>${attr.name}</label>
                                <div class="input-with-button">
                                    <select class="attribute-select" name="option_ids[]" data-attribute-id="${attr.attribute_id}" data-attribute-name="${attr.name}">
                                        <option value="">-- Không chọn --</option>
                                        ${optionsHTML}
                                    </select>
                                    <button type="button" class="add-option-btn" title="Thêm tùy chọn mới">+</button>
                                </div>
                            </div>
                        `;
                        attributeFormGrid.innerHTML += fieldHTML;
                    }
                });
            }
        };

        const generateSkuAndName = () => {
            const baseOpt = baseProductSelect.options[baseProductSelect.selectedIndex];
            if (!baseOpt) {
                variantSkuInput.value = '';
                variantNameInput.value = '';
                previewSkuEl.textContent = '';
                previewNameEl.textContent = '';
                return;
            }
            const productDetails = allBaseProducts.find(p => p.product_id == baseProductSelect.value);

            const getAttrVal = (attrName) => {
                const select = form.querySelector(`[data-attribute-name="${attrName}"]`);
                return select && select.value ? select.options[select.selectedIndex].textContent : '';
            };
            
            // Lấy các giá trị thuộc tính hiện tại từ form
            const currentAttributeValues = {};
            form.querySelectorAll('.attribute-select').forEach(select => {
                const attrName = select.dataset.attributeName;
                if (attrName) {
                    currentAttributeValues[attrName] = getAttrVal(attrName);
                }
            });

            // Lấy công thức từ sản phẩm gốc
            const formula = productDetails?.sku_name_formula;
            const skuPrefix = productDetails?.sku_prefix;
            const namePrefix = productDetails?.name_prefix;
            const skuSuffix = skuSuffixInput.value ? skuSuffixInput.value.toUpperCase() : '';

            // Generate SKU based on the formula
            let generatedSku = '';
            if (formula && formula.sku_attributes && formula.sku_attributes.length > 0) {
                const parts = [];
                formula.sku_attributes.forEach(part => {
                    const attrValue = currentAttributeValues[part];
                    if (attrValue) {
                         parts.push(attrValue);
                    } else {
                         // It's a separator
                         parts.push(part);
                    }
                });
                generatedSku = parts.join('');
            }
            if (formula?.sku_prefix && skuPrefix) {
                generatedSku = skuPrefix + (generatedSku ? ' ' + generatedSku : '');
            }
            if (skuSuffix) {
                generatedSku += `-${skuSuffix}`;
            }

            // Generate Name based on the formula
            let generatedName = '';
            if (formula && formula.name_attributes && formula.name_attributes.length > 0) {
                const parts = [];
                formula.name_attributes.forEach(part => {
                    const attrValue = currentAttributeValues[part];
                    if (attrValue) {
                        parts.push(attrValue);
                    } else {
                        // It's a separator
                        parts.push(part);
                    }
                });
                generatedName = parts.join('');
            }
            if (formula?.name_prefix && namePrefix) {
                generatedName = namePrefix + (generatedName ? ' ' + generatedName : '');
            }
            if (skuSuffix) {
                generatedName += ` ${skuSuffix}`;
            }

            // Trim and set values
            variantSkuInput.value = generatedSku.trim();
            variantNameInput.value = generatedName.trim();
            previewSkuEl.textContent = generatedSku.trim();
            previewNameEl.textContent = generatedName.trim();
        };

        baseProductSelect.addEventListener('change', () => {
            updateAttributeFields();
            skuSuffixInput.value = '';
            generateSkuAndName();
        });

        form.addEventListener('change', e => {
            if (e.target.tagName === 'SELECT' || e.target.name === 'sku_suffix') {
                generateSkuAndName();
            }
        });

        updateAttributeFields();
        generateSkuAndName();

        form.addEventListener('click', async e => {
            if (e.target.classList.contains('add-option-btn')) {
                const select = e.target.previousElementSibling;
                const attrId = select.dataset.attributeId;
                const currentFormData = new FormData(form);
                variantFormState = {
                    product_id: currentFormData.get('product_id'),
                    variant_name: currentFormData.get('variant_name'),
                    variant_sku: currentFormData.get('variant_sku'),
                    sku_suffix: currentFormData.get('sku_suffix'),
                    price: currentFormData.get('price'),
                    LoaiID: currentFormData.get('LoaiID'),
                    selected_options: Array.from(form.querySelectorAll('.attribute-select')).map(s => s.value).filter(Boolean)
                };
                shouldRestoreVariantForm = true;
                const callback = () => {
                    openVariantForm(currentVariantId);
                };
                modal.style.display = 'none';
                openDataManager('attributes', attrId, callback);
            }
        });

        form.onsubmit = async (e) => {
            e.preventDefault();
            const dataToSave = {
                ...Object.fromEntries(new FormData(form).entries()),
                option_ids: Array.from(form.querySelectorAll('.attribute-select')).map(s => s.value).filter(Boolean)
            };
            try {
                await apiRequest('save_variant', {
                    data: dataToSave
                });
                showToast('Lưu biến thể thành công!');
                modal.style.display = 'none';
                loadAllVariants();
                variantFormState = {};
            } catch (error) {
                console.error("Lỗi khi lưu biến thể:", error);
            }
        };
        modal.style.display = 'block';
    }

    async function openInventoryManager(variantId, variantSku) {
        const modal = createModal('inventory-modal');
        modal.innerHTML = `<div class="modal-content" style="max-width: 600px;"><div class="modal-header"><h2>Tồn kho: ${variantSku}</h2><span class="close-btn">&times;</span></div><form id="inventory-form">Đang tải...</form></div>`;
        const form = modal.querySelector('#inventory-form');
        modal.style.display = 'block';
        try {
            const inventoryData = await apiRequest('get_inventory_for_variant', {
                variant_id: variantId
            });
            if (typeof inventoryData.unit_id === 'undefined' || inventoryData.unit_id === null) {
                form.innerHTML = '<p style="color: orange;">Sản phẩm gốc chưa được thiết lập đơn vị cơ sở. Tồn kho sẽ hiển thị mà không có đơn vị.</p>';
            }
            const currentQuantity = parseInt(inventoryData.quantity || 0);
            const minimumStockLevel = parseInt(inventoryData.minimum_stock_level || 0);
            const unitName = inventoryData.unit_name || 'Đơn vị';
            let formHTML = `
                <p>Nhập số lượng mới cho đơn vị cơ sở.</p>
                <div class="form-grid">
                    <div class="form-group"><label>Số lượng (${unitName})</label><input type="number" name="quantity" value="${currentQuantity}"></div>
                    <div class="form-group"><label>Định mức tồn kho tối thiểu (${unitName})</label><input type="number" name="minimum_stock_level" value="${minimumStockLevel}"></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Lưu Tồn Kho</button></div>`;
            form.innerHTML = formHTML;
            form.onsubmit = async (e) => {
                e.preventDefault();
                const newQuantity = parseInt(form.querySelector('[name="quantity"]').value || 0);
                const newMinimumStockLevel = parseInt(form.querySelector('[name="minimum_stock_level"]').value || 0);
                try {
                    await apiRequest('update_inventory', {
                        variant_id: variantId,
                        quantity: newQuantity,
                        minimum_stock_level: newMinimumStockLevel
                    });
                    showToast("Cập nhật tồn kho thành công!");
                    modal.style.display = 'none';
                    loadAllVariants();
                } catch (err) {
                    console.error("Lỗi khi cập nhật tồn kho:", err);
                }
            };
        } catch (err) {
            console.error("Lỗi trong openInventoryManager:", err);
            form.innerHTML = `<p style="color: red;">Đã xảy ra lỗi khi tải dữ liệu tồn kho: ${err.message}.</p>`;
        }
    }

    async function openDataManager(defaultTab = 'attributes', defaultItemId = null, onCloseCallback = null) {
        const modal = createModal('data-manager-modal');
        modal.innerHTML = `<div class="modal-content" style="max-width: 800px;"><div class="modal-header"><h2><i class="fa fa-database"></i> Quản lý Dữ liệu Chung</h2><span class="close-btn">&times;</span></div><div id="data-manager-body"></div></div>`;
        const body = modal.querySelector('#data-manager-body');
        body.innerHTML = `<div class="tabs"><span class="tab-link" data-tab="attributes">Thuộc tính</span><span class="tab-link" data-tab="types">Loại Phân loại</span><span class="tab-link" data-tab="groups">Nhóm Sản phẩm</span><span class="tab-link" data-tab="units">Đơn vị tính</span></div><div id="attributes" class="tab-content"></div><div id="types" class="tab-content"></div><div id="groups" class="tab-content"></div><div id="units" class="tab-content"></div>`;
        modal.style.display = 'block';
        modal.onCloseCallback = onCloseCallback;
        const openTab = (tabName) => {
            body.querySelectorAll('.tab-link, .tab-content').forEach(el => el.classList.remove('active'));
            body.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            body.querySelector(`#${tabName}`).classList.add('active');
            loadDataForTab(tabName, defaultItemId);
        };
        body.querySelector('.tabs').addEventListener('click', e => {
            if (e.target.classList.contains('tab-link')) openTab(e.target.dataset.tab);
        });
        openTab(defaultTab);
    }

    async function loadDataForTab(tabName, defaultItemId = null) {
        const container = document.getElementById(tabName);
        if (tabName === 'attributes') loadAttributeManager(container, defaultItemId);
        else if (tabName === 'types') loadSimpleDataManager(container, 'Loại Phân loại', 'type', 'get_all_loai', 'LoaiID', 'TenLoai');
        else if (tabName === 'groups') loadSimpleDataManager(container, 'Nhóm SP', 'group', 'get_product_groups', 'group_id', 'name');
        else if (tabName === 'units') loadSimpleDataManager(container, 'Đơn vị tính', 'unit', 'get_all_units', 'unit_id', 'name');
    }

    async function loadAttributeManager(container, defaultAttrId = null) {
        container.innerHTML = 'Đang tải...';
        const attributes = await apiRequest('get_attributes_for_management');
        let listHTML = '<h3>Loại thuộc tính</h3><ul>';
        attributes.forEach(attr => {
            listHTML += `<li class="attributes-list-item" data-id="${attr.attribute_id}" data-name="${attr.name}"><div class="item-content">${attr.name}</div><div class="item-actions"><button class="delete-attribute-item" title="Xóa"><i class="fa fa-trash-can icon-delete"></i></button></div></li>`;
        });
        listHTML += `</ul><form class="add-item-form"><input type="text" placeholder="Nhập tên thuộc tính mới" required><button type="submit" class="btn btn-success">Thêm</button></form>`;
        container.innerHTML = `<div class="attribute-manager-grid"><div class="attributes-list">${listHTML}</div><div class="options-container"><h3 id="options-title">Chọn một loại thuộc tính</h3><div id="options-list-wrapper"></div></div></div>`;
        const attrList = container.querySelector('.attributes-list');
        attrList.addEventListener('click', e => {
            if (e.target.closest('.delete-attribute-item')) {
                const li = e.target.closest('.attributes-list-item');
                const attrId = li.dataset.id;
                const attrName = li.dataset.name;
                App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa thuộc tính "${attrName}"?`, () => {
                    apiRequest('delete_attribute', {
                        attribute_id: attrId
                    }).then(() => {
                        showToast('Đã xóa thuộc tính thành công.');
                        loadAttributeManager(container);
                    }).catch(err => {
                        App.showMessageModal(err.message || 'Không thể xóa thuộc tính này.', 'error');
                    });
                });
                return;
            }
            if (e.target.closest('.attributes-list-item')) {
                const li = e.target.closest('.attributes-list-item');
                attrList.querySelectorAll('.attributes-list-item').forEach(el => el.classList.remove('active'));
                li.classList.add('active');
                loadOptionsForAttribute(li.dataset.id, li.dataset.name);
            }
        });
        
        container.querySelector('.add-item-form').addEventListener('submit', async e => {
            e.preventDefault();
            const newValue = e.target.querySelector('input').value;
            if (newValue) {
                try {
                    await apiRequest('create_attribute', { name: newValue });
                    showToast('Đã thêm thuộc tính mới!');
                    loadAttributeManager(container);
                } catch (err) {
                    App.showMessageModal(err.message || 'Không thể thêm thuộc tính.', 'error');
                }
            }
        });

        if (defaultAttrId) {
            const defaultAttrElement = attrList.querySelector(`[data-id='${defaultAttrId}']`);
            if (defaultAttrElement) defaultAttrElement.click();
        }
    }

    async function loadOptionsForAttribute(attributeId, attributeName) {
        document.getElementById('options-title').textContent = `Các tùy chọn cho: ${attributeName}`;
        const wrapper = document.getElementById('options-list-wrapper');
        wrapper.innerHTML = 'Đang tải...';
        const options = await apiRequest('get_options_for_attribute', {
            attribute_id: attributeId
        });
        let optionsHTML = '<ul class="options-list">';
        options.forEach(opt => {
            optionsHTML += `<li class="options-list-item" data-id="${opt.option_id}"><input type="text" value="${opt.value}"><div class="option-actions"><button class="save-option" title="Lưu"><i class="fa fa-save icon-save"></i></button><button class="delete-option" title="Xóa"><i class="fa fa-trash-can icon-delete"></i></button></div></li>`;
        });
        optionsHTML += `</ul><form class="add-item-form"><input type="text" placeholder="Nhập giá trị tùy chọn mới" required><button type="submit" class="btn btn-success">Thêm</button></form>`;
        wrapper.innerHTML = optionsHTML;
        wrapper.querySelector('.add-item-form').addEventListener('submit', async e => {
            e.preventDefault();
            const newValue = e.target.querySelector('input').value;
            if (newValue) {
                await apiRequest('create_attribute_option', {
                    attribute_id: attributeId,
                    value: newValue
                });
                showToast('Đã thêm!');
                loadOptionsForAttribute(attributeId, attributeName);
            }
        });
        wrapper.addEventListener('click', async e => {
            const button = e.target.closest('button');
            if (!button) return;
            const li = button.closest('li');
            if (!li) return;
            if (button.classList.contains('save-option')) {
                await apiRequest('update_attribute_option', {
                    option_id: li.dataset.id,
                    value: li.querySelector('input').value
                });
                showToast('Đã cập nhật!');
            } else if (button.classList.contains('delete-option')) {
                App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa tùy chọn "${li.querySelector('input').value}"?`, () => {
                    apiRequest('delete_attribute_option', {
                        option_id: li.dataset.id
                    }).then(() => {
                        li.remove();
                        showToast('Đã xóa!');
                    }).catch(err => {
                        App.showMessageModal(err.message || 'Không thể xóa tùy chọn này.', 'error');
                    });
                });
            }
        });
    }

    async function loadSimpleDataManager(container, title, type, getAction, idField, nameField) {
        container.innerHTML = 'Đang tải...';
        const items = await apiRequest(getAction);
        let itemsHTML = `<h3>Danh sách ${title}</h3><ul class="data-item-list">`;
        items.forEach(item => {
            itemsHTML += `<li class="data-item" data-id="${item[idField]}"><div class="item-content"><input type="text" value="${item[nameField]}"></div><div class="item-actions"><button class="save-item" title="Lưu"><i class="fa fa-save icon-save"></i></button><button class="delete-item" title="Xóa"><i class="fa fa-trash-can icon-delete"></i></button></div></li>`;
        });
        itemsHTML += `</ul><form class="add-item-form"><input type="text" placeholder="Nhập tên mới" required><button type="submit" class="btn btn-success">Thêm</button></form>`;
        container.innerHTML = itemsHTML;
        container.querySelector('.add-item-form').addEventListener('submit', async e => {
            e.preventDefault();
            const newValue = e.target.querySelector('input').value;
            if (newValue) {
                await apiRequest('create_data_item', {
                    value: newValue,
                    type: type
                });
                showToast('Đã thêm mới!');
                loadSimpleDataManager(container, title, type, getAction, idField, nameField);
            }
        });
        container.addEventListener('click', async e => {
            const button = e.target.closest('button');
            if (!button) return;
            const li = button.closest('li');
            if (li) {
                if (button.classList.contains('save-item')) {
                    await apiRequest('save_data_item', {
                        id: li.dataset.id,
                        value: li.querySelector('input').value,
                        type: type
                    });
                    showToast('Đã cập nhật!');
                } else if (button.classList.contains('delete-item')) {
                     App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa "${li.querySelector('input').value}"?`, () => {
                        apiRequest('delete_data_item', {
                            id: li.dataset.id,
                            type: type
                        }).then(() => {
                            li.remove();
                            showToast('Đã xóa!');
                        }).catch(err => {
                            App.showMessageModal(err.message || 'Không thể xóa mục này.', 'error');
                        });
                    });
                }
            }
        });
    }

    function setupEventListeners() {
        document.getElementById("add-variant-btn").addEventListener("click", () => openVariantForm());
        document.getElementById("add-product-btn").addEventListener("click", () => openProductListManager());
        document.getElementById("data-manager-btn").addEventListener("click", () => openDataManager());
        document.getElementById("clear-filters-btn").addEventListener("click", () => {
            document.getElementById('filter-group-id').value = '';
            document.getElementById('filter-loai-id').value = '';
            document.getElementById('filter-field').value = '';
            if (variantsTable) variantsTable.clearFilter();
        });
        document.getElementById('delete-selected-btn').addEventListener('click', async () => {
            if (!variantsTable) return;
            const selectedData = variantsTable.getSelectedData();
            if (selectedData.length === 0) return;
            App.showConfirmationModal('Xác nhận xóa', `Bạn có chắc muốn xóa ${selectedData.length} sản phẩm đã chọn?`, async () => {
                const idsToDelete = selectedData.map(row => row.variant_id);
                try {
                    await apiRequest('delete_multiple_variants', {
                        ids: idsToDelete
                    });
                    showToast(`Đã xóa thành công ${idsToDelete.length} sản phẩm.`);
                    loadAllVariants();
                } catch (error) {}
            });
        });

        document.body.addEventListener('click', e => {
            if (e.target.classList.contains('close-btn')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    if (modal.id === 'data-manager-modal' && modal.onCloseCallback && shouldRestoreVariantForm) {
                        modal.onCloseCallback();
                    }
                    shouldRestoreVariantForm = false;
                    modal.onCloseCallback = null;
                    modal.style.display = 'none';
                }
            }
        });

        // === CODE FOR EXCEL IMPORT FEATURE (UPDATED) ===
        const importExcelBtn = document.getElementById('import-excel-btn');
        const importFileInput = document.getElementById('import-file-input');

        if (importExcelBtn) {
            importExcelBtn.addEventListener('click', () => {
                importFileInput.click();
            });
        }

        if (importFileInput) {
            importFileInput.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) return;

                const allowedExtensions = ['.csv', '.xlsx'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExtension)) {
                    showToast('Lỗi: Vui lòng chỉ chọn file có định dạng .csv hoặc .xlsx', 'error');
                    importFileInput.value = '';
                    return;
                }

                const formData = new FormData();
                formData.append('import_file', file);

                showToast('Đang xử lý file, vui lòng chờ...', 'info');

                try {
                    const response = await fetch(`${API_URL}?action=import_variants`, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message || 'Nhập file thành công!', 'success');
                        loadAllVariants();
                    } else {
                        App.showMessageModal(result.message || 'Có lỗi xảy ra khi nhập file.', 'error');
                    }
                } catch (error) {
                    console.error('Lỗi khi nhập file:', error);
                    App.showMessageModal('Lỗi hệ thống: Không thể kết nối tới máy chủ để nhập file.', 'error');
                } finally {
                    importFileInput.value = '';
                }
            });
        }

        // === CODE FOR EXPORTING TEMPLATE FILE ===
        const exportTemplateBtn = document.getElementById('export-template-btn');
        if (exportTemplateBtn) {
            exportTemplateBtn.addEventListener('click', () => {
                window.location.href = `${API_URL}?action=export_product_template`;
            });
        }
    }

    // --- INITIALIZE PAGE LOGIC ---
    // Sử dụng hàm async để đảm bảo bảng được khởi tạo trước các thành phần khác
    (async () => {
        console.log("Initializing Product Management Page...");
        await loadAndDisplayStats(); // Tải thống kê trước
        await initializeTable(); // Chờ cho bảng và các cột được tạo xong
        await initializeFilters(); // Sau đó mới khởi tạo bộ lọc
        setupEventListeners();
    })();
}

