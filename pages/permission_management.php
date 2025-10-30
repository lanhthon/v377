<div class="p-4 sm:p-6">
    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button class="tab-button active px-1 py-4 text-sm font-medium border-b-2 border-blue-500 text-blue-600" data-tab="users">
                <i class="fas fa-users mr-2"></i>Người dùng
            </button>
            <button class="tab-button px-1 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="roles">
                <i class="fas fa-user-tag mr-2"></i>Vai trò
            </button>
            <button class="tab-button px-1 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="permissions">
                <i class="fas fa-user-shield mr-2"></i>Phân quyền
            </button>
        </nav>
    </div>

    <!-- Tab Content: Người dùng -->
    <div id="users-tab-content" class="tab-content">
        <div class="p-6 bg-white rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-users mr-2"></i>Quản lý Người Dùng
                </h1>
                <button id="add-user-btn" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md shadow-sm hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Thêm Người Dùng
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Họ Tên</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Chức Vụ</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Số Điện Thoại</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Tên Đăng Nhập</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Vai Trò</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Trạng Thái</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab Content: Vai trò -->
    <div id="roles-tab-content" class="tab-content hidden">
        <div class="p-6 bg-white rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-user-tag mr-2"></i>Quản lý Vai Trò
                </h1>
                <button id="add-role-btn" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md shadow-sm hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Thêm Vai Trò
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="roles-container"></div>
        </div>
    </div>

    <!-- Tab Content: Phân quyền -->
    <div id="permissions-tab-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-user-shield mr-2"></i>Phân Quyền Chức Năng
                </h1>
                <button id="save-permissions-btn" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-sm hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu Phân Quyền
                </button>
            </div>
            
            <div class="p-6">
                <div class="permission-table-wrapper">
                    <table class="permission-table min-w-full text-sm">
                        <colgroup id="permission-table-colgroup"></colgroup>
                        <thead>
                            <tr id="permission-table-head" class="bg-gray-50"></tr>
                        </thead>
                        <tbody id="permission-table-body" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Người Dùng -->
<div id="add-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Thêm Người Dùng Mới</h2>
        <form id="add-user-form">
            <div class="space-y-4">
                <div>
                    <label for="new-user-hoten" class="block text-sm font-medium mb-1">Họ Tên <span class="text-red-500">*</span></label>
                    <input type="text" id="new-user-hoten" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-chucvu" class="block text-sm font-medium mb-1">Chức Vụ</label>
                    <input type="text" id="new-user-chucvu" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-sodienthoai" class="block text-sm font-medium mb-1">Số Điện Thoại</label>
                    <input type="text" id="new-user-sodienthoai" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-tendangnhap" class="block text-sm font-medium mb-1">Tên Đăng Nhập <span class="text-red-500">*</span></label>
                    <input type="text" id="new-user-tendangnhap" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-email" class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" id="new-user-email" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-password" class="block text-sm font-medium mb-1">Mật Khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="new-user-password" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-confirm-password" class="block text-sm font-medium mb-1">Xác Nhận Mật Khẩu <span class="text-red-500">*</span></label>
                    <input type="password" id="new-user-confirm-password" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new-user-role" class="block text-sm font-medium mb-1">Vai Trò <span class="text-red-500">*</span></label>
                    <select id="new-user-role" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"></select>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-add-user-btn" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Đổi Mật Khẩu -->
<div id="change-password-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Đổi mật khẩu cho: <span id="change-password-username" class="text-blue-600"></span></h2>
        <form id="change-password-form">
            <input type="hidden" id="change-password-userid">
            <div class="space-y-4">
                <div>
                    <label for="change-password-new" class="block text-sm font-medium mb-1">Mật Khẩu Mới <span class="text-red-500">*</span></label>
                    <input type="password" id="change-password-new" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="change-password-confirm" class="block text-sm font-medium mb-1">Xác Nhận Mật Khẩu Mới <span class="text-red-500">*</span></label>
                    <input type="password" id="change-password-confirm" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-change-password-btn" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Lưu Mật Khẩu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thêm/Sửa Vai Trò -->
<div id="role-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <h2 id="role-modal-title" class="text-xl font-bold mb-4">Thêm Vai Trò Mới</h2>
        <form id="role-form">
            <input type="hidden" id="role-action" value="add">
            <div class="space-y-4">
                <div>
                    <label for="role-ma" class="block text-sm font-medium mb-1">Mã Vai Trò <span class="text-red-500">*</span></label>
                    <input type="text" id="role-ma" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="vd: ketoan, truongphong">
                    <p class="text-xs text-gray-500 mt-1">Chỉ dùng chữ thường, không dấu, không khoảng trắng</p>
                </div>
                <div>
                    <label for="role-ten" class="block text-sm font-medium mb-1">Tên Vai Trò <span class="text-red-500">*</span></label>
                    <input type="text" id="role-ten" required class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="vd: Kế toán, Trưởng phòng">
                </div>
                <div>
                    <label for="role-mota" class="block text-sm font-medium mb-1">Mô Tả</label>
                    <textarea id="role-mota" rows="3" class="mt-1 w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Mô tả về vai trò này..."></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-role-btn" class="px-4 py-2 bg-gray-300 rounded-md hover:bg-gray-400">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Lưu</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Tab Styles */
.tab-button {
    transition: all 0.3s ease;
}
.tab-button.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

/* Tab Visibility Control - KHÔNG FORCE */
.tab-content {
    display: none;
}

/* Switch Toggle */
.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .3s;
    border-radius: 24px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: #3b82f6;
}
input:checked + .slider:before {
    transform: translateX(20px);
}

/* Permission Table Styles */
.permission-table-wrapper {
    position: relative;
    overflow: auto;
    max-height: calc(100vh - 280px);
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.permission-table {
    border-collapse: separate;
    border-spacing: 0;
}

.permission-table thead tr {
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.permission-table thead th {
    background-color: #f9fafb;
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}

.permission-table thead th:first-child {
    position: sticky;
    left: 0;
    z-index: 11;
    background-color: #f9fafb;
    box-shadow: 2px 0 4px rgba(0,0,0,0.05);
}

.permission-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
}

.permission-table tbody td:first-child {
    position: sticky;
    left: 0;
    background-color: white;
    font-weight: 500;
    z-index: 5;
    box-shadow: 2px 0 4px rgba(0,0,0,0.05);
}

.permission-table tbody tr:hover td {
    background-color: #f9fafb;
}

.permission-table tbody tr:hover td:first-child {
    background-color: #f0f9ff;
}

/* Role Card Styles */
.role-card {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.role-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.role-card.admin-card {
    border-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

/* Checkbox Styles */
.permission-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.permission-checkbox:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Indented Child Functions */
.function-child {
    padding-left: 2rem;
    font-size: 0.9em;
}

.function-child::before {
    content: "└─";
    margin-right: 0.5rem;
    color: #9ca3af;
}
</style>