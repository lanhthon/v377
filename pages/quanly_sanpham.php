<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm Chi Tiết</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-gray: #f8f9fa;
            --medium-gray: #dee2e6;
            --dark-gray: #343a40;
            --text-color: #495057;
            --border-radius: 0.375rem;
        }

        .container {
            max-width: 98%;
            margin: auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-primary { background-color: var(--primary-color); }
        .btn-success { background-color: var(--success-color); }
        .btn-danger { background-color: var(--danger-color); }

        .controls-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding: 1rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075);
        }

        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 500;
            margin-bottom: 5px;
            font-size: 12px;
            color: #6c757d;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
        }

        #filter-field {
            min-width: 250px;
        }

        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.6);
        }
        .modal-content {
            background-color: #fff; margin: 3% auto; padding: 25px; border: none; width: 90%; max-width: 1000px; border-radius: var(--border-radius); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15); animation: fadeIn 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--medium-gray); padding-bottom: 15px; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 1.5rem; }
        .close-btn { color: #6c757d; font-size: 2rem; font-weight: bold; cursor: pointer; line-height: 1; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid var(--medium-gray); border-radius: var(--border-radius); box-sizing: border-box; }
        .form-group input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        .form-group.hidden { display: none; }
        .input-with-button { display: flex; gap: 5px; }
        .input-with-button select { flex-grow: 1; }
        .add-option-btn { flex-shrink: 0; width: 38px; height: 38px; background-color: var(--success-color); color: white; border: none; border-radius: var(--border-radius); font-size: 20px; cursor: pointer; }
        .modal-footer { text-align: right; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--medium-gray); }
        #toast { visibility: hidden; min-width: 280px; margin-left: -140px; background-color: var(--dark-gray); color: #fff; text-align: center; border-radius: 4px; padding: 16px; position: fixed; z-index: 2000; left: 50%; bottom: 30px; transition: all 0.5s ease; opacity: 0; }
        #toast.show { visibility: visible; opacity: 1; }
        #toast.success { background-color: var(--success-color); }
        #toast.error { background-color: var(--danger-color); }
        .tabulator-cell .action-icon { cursor: pointer; margin: 0 8px; font-size: 16px; transition: transform 0.2s; }
        .icon-edit { color: var(--warning-color); }
        .icon-save { color: var(--primary-color); }
        .icon-delete { color: var(--danger-color); }
        .icon-inventory { color: var(--info-color); }
        .product-name-cell { display: flex; align-items: center; justify-content: space-between; }
        .product-name-cell .product-name-text { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .product-name-cell .product-action-icon { margin-left: 10px; cursor: pointer; color: var(--primary-color); font-size: 1.1em; }
        #data-manager-body .tabs { display: flex; border-bottom: 1px solid var(--medium-gray); margin-bottom: 15px; }
        #data-manager-body .tab-link { padding: 10px 15px; cursor: pointer; border: 1px solid transparent; border-bottom: none; }
        #data-manager-body .tab-link.active { border-color: var(--medium-gray); border-bottom-color: white; font-weight: 500; background-color: white; border-radius: 4px 4px 0 0; margin-bottom: -1px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .attribute-manager-grid { display: grid; grid-template-columns: 250px 1fr; gap: 20px; min-height: 50vh; }
        .attributes-list, .data-item-list { border-right: 1px solid var(--medium-gray); padding-right: 20px; overflow-y: auto; }
        .attributes-list ul, .options-list ul, .data-item-list ul { list-style: none; padding: 0; margin: 0; }
        .attributes-list-item, .options-list-item, .data-item { padding: 10px 15px; border-radius: var(--border-radius); cursor: pointer; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
        .attributes-list-item:hover, .data-item:hover { background-color: var(--light-gray); }
        .attributes-list-item.active { background-color: var(--primary-color); color: white; font-weight: 500; }
        .options-list-item input, .data-item input { flex-grow: 1; border: 1px solid var(--medium-gray); padding: 6px 10px; border-radius: 4px; }
        .options-list-item .option-actions, .data-item .item-actions { display: flex; gap: 5px; margin-left: 10px; }
        .option-actions button, .item-actions button { background: none; border: none; font-size: 16px; cursor: pointer; padding: 5px; }
        .add-item-form { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--medium-gray); }
        .add-item-form input { flex-grow: 1; }
        .tabulator { border: 1px solid var(--medium-gray); }
        .tabulator .tabulator-header .tabulator-col { border-right: 1px solid var(--medium-gray); border-bottom: 1px solid var(--medium-gray); }
        .tabulator .tabulator-row .tabulator-cell { border-right: 1px solid var(--medium-gray); }
        .tabulator .tabulator-row { border-bottom: 1px solid var(--medium-gray); }
        .pagination-info { display: flex; align-items: center; gap: 15px; margin-top: 10px; padding: 10px; background-color: white; border-radius: var(--border-radius); box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075); font-size: 14px; color: #6c757d; }
        .pagination-info select { padding: 6px 10px; border: 1px solid var(--medium-gray); border-radius: var(--border-radius); }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fa-solid fa-boxes-stacked"></i> Quản lý Sản phẩm Chi tiết</h1>
            <div class="header-buttons">
                <button id="add-product-btn" class="btn btn-primary"><i class="fa fa-sitemap"></i> SP Gốc</button>
                <button id="add-variant-btn" class="btn btn-primary"><i class="fa fa-plus"></i> Thêm mới</button>
                <button id="import-excel-btn" class="btn btn-success"><i class="fa fa-file-excel"></i> Nhập Excel</button>
                <button id="export-template-btn" class="btn btn-primary" style="background-color: #00774c;"><i class="fa fa-file-download"></i> Tải file mẫu</button>
                <button id="data-manager-btn" class="btn btn-primary"><i class="fa fa-database"></i> Dữ liệu chung</button>
                <!-- Input file ẩn, chấp nhận cả .csv và .xlsx -->
                <input type="file" id="import-file-input" accept=".csv, .xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" style="display: none;" />
            </div>
        </div>
        
        <!-- [UPDATED] Stats Section -->
        <div id="stats-container" class="mb-5 grid grid-cols-2 md:grid-cols-6 gap-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
              <div id="stats-total-products" class="text-3xl font-bold text-blue-600">0</div>
              <div class="text-sm text-blue-800 mt-1">Tổng SP Chi Tiết</div>
            </div>
             <div class="bg-cyan-50 border border-cyan-200 rounded-lg p-4 text-center">
                <div id="stats-total-base-products" class="text-3xl font-bold text-cyan-600">0</div>
                <div class="text-sm text-cyan-800 mt-1">Tổng SP Gốc</div>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
              <div id="stats-total-columns" class="text-3xl font-bold text-purple-600">0</div>
              <div class="text-sm text-purple-800 mt-1">Tổng Thuộc Tính</div>
            </div>
             <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <div id="stats-total-pur" class="text-3xl font-bold text-red-600">0</div>
                <div class="text-sm text-red-800 mt-1">Tổng mã PUR</div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
              <div id="stats-ula-dmdt" class="text-3xl font-bold text-green-600">0</div>
              <div class="text-sm text-green-800 mt-1">ULA có Định Mức Đóng Thùng/Tải</div>
            </div>
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
              <div id="stats-ula-dmkg" class="text-3xl font-bold text-emerald-600">0</div>
              <div class="text-sm text-emerald-800 mt-1">ULA có Định Mưc Kg/Bộ</div>
            </div>
        </div>

        <div class="controls-container">
            <div class="filter-section">
                <div class="filter-group"><label>Nhóm SP</label><select id="filter-group-id"></select></div>
                <div class="filter-group"><label>Loại Phân Loại</label><select id="filter-loai-id"></select></div>
                <div class="filter-group"><label>Tìm kiếm</label><input id="filter-field" type="text" placeholder="Mã, tên sản phẩm..."></div>
                <div class="filter-group"><label>&nbsp;</label><button id="clear-filters-btn" class="btn" style="background-color: var(--dark-gray);"><i class="fa fa-times"></i> Xóa Lọc</button></div>
            </div>
        </div>

        <div id="variants-table"></div>

        <div class="pagination-info">
            <span id="total-variants"></span>
            <div class="filter-group">
                <label for="page-size-select">Hiển thị:</label>
                <select id="page-size-select">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">Tất cả</option>
                </select>
                <span>sản phẩm mỗi trang</span>
            </div>
             <button id="delete-selected-btn" class="btn btn-danger" style="display: none; margin-left: auto;"><i class="fa fa-trash-can"></i> Xóa (0) mục</button>
        </div>
    </div>

    <div id="variant-form-modal"></div>
    <div id="product-form-modal"></div>
    <div id="inventory-modal"></div>
    <div id="data-manager-modal"></div>
    <div id="toast"></div>

    <script src="https://unpkg.com/tabulator-tables@5.6.1/dist/js/tabulator.min.js"></script>
    <!-- Thêm Tailwind CSS để căn chỉnh layout cho phần checkbox được đẹp hơn -->
    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>

