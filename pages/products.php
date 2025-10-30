<?php
// File: pages/products.php
?>
<div class="p-4 sm:p-6 bg-white rounded-lg shadow-lg min-h-screen">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Quản lý Sản phẩm</h1>

    <div class="mb-6">
        <button id="btnAddProduct" class="btn btn-primary">Thêm Sản phẩm</button>
        <button id="btnImportProducts" class="btn btn-secondary">Nhập Sản phẩm</button>
        <button id="btnExportProducts" class="btn btn-secondary">Xuất Sản phẩm</button>
        <button id="btnRefreshProducts" class="btn btn-secondary">Làm mới</button>
        <button id="btnSearchProducts" class="btn btn-secondary">Tìm kiếm</button>
        <button id="btnFilterProducts" class="btn btn-secondary">Lọc</button>
        <button id="btnClearFilters" class="btn btn-secondary">Xóa bộ lọc</button>
    </div>
    <!--this is the table to display $products-->
    <table id="productsTable" class="w-full table-auto border-collapse">
        <thead>
            <tr class="bg-gray-200">
                <th class="px-4 py-2">Mã Hàng</th>
                <th class="px-4 py-2">Tên Sản Phẩm</th>
                <th class="px-4 py-2">Loại Sản Phẩm</th>
                <th class="px-4 py-2">Đơn Vị Tính</th>
                <th class="px-4 py-2">Giá Gốc</th>
                <th class="px-4 py-2">Số Lượng Tồn Kho</th>
                <th class="px-4 py-2">Hành Động</th>
            </tr>
        </thead>
        <tbody id="productsBody">
            <!-- Dữ liệu sản phẩm sẽ được chèn vào đây bằng JavaScript -->
        </tbody>
    </table>
    <div id="pagination" class="mt-4">
        <!-- Nút phân trang sẽ được chèn vào đây bằng JavaScript -->
    </div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/products.js"></script>