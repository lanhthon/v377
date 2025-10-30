<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Quản lý Người dùng</h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">Thêm tài khoản mới</h2>
        <form id="create-user-form" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="ho_ten" class="block text-sm font-medium text-gray-700">Họ và Tên</label>
                <input type="text" id="ho_ten" name="ho_ten" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="ten_dang_nhap" class="block text-sm font-medium text-gray-700">Tên đăng nhập</label>
                <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="mat_khau" class="block text-sm font-medium text-gray-700">Mật khẩu</label>
                <input type="password" id="mat_khau" name="mat_khau" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="vai_tro" class="block text-sm font-medium text-gray-700">Vai trò</label>
                <select id="vai_tro" name="vai_tro" required
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="kinhdoanh">Kinh doanh</option>
                    <option value="kho">Kho</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-plus mr-2"></i> Tạo tài khoản
                </button>
            </div>
        </form>
        <div id="form-message" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Danh sách tài khoản</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Họ và
                            Tên</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên
                            đăng nhập</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai
                            trò</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày
                            tạo</th>
                    </tr>
                </thead>
                <tbody id="user-list-body" class="bg-white divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const userListBody = $('#user-list-body');
    const createUserForm = $('#create-user-form');
    const formMessage = $('#form-message');

    // Hàm để tải và hiển thị danh sách người dùng
    function loadUsers() {
        userListBody.html('<tr><td colspan="5" class="text-center p-4">Đang tải...</td></tr>');
        $.ajax({
            url: 'api/get_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                userListBody.empty();
                if (response.success && response.users.length > 0) {
                    response.users.forEach(user => {
                        const userRow = `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">${user.HoTen}</td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium">${user.TenDangNhap}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${user.Email || ''}</td>
                                <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${user.Role}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(user.CreatedAt).toLocaleDateString('vi-VN')}</td>
                            </tr>
                        `;
                        userListBody.append(userRow);
                    });
                } else {
                    userListBody.html(
                        '<tr><td colspan="5" class="text-center p-4">Không có người dùng nào.</td></tr>'
                    );
                }
            },
            error: function() {
                userListBody.html(
                    '<tr><td colspan="5" class="text-center p-4 text-red-500">Lỗi khi tải danh sách người dùng.</td></tr>'
                );
            }
        });
    }

    // Xử lý sự kiện submit form
    createUserForm.on('submit', function(e) {
        e.preventDefault();
        formMessage.text('').removeClass('text-green-600 text-red-600');

        const formData = {
            ho_ten: $('#ho_ten').val(),
            ten_dang_nhap: $('#ten_dang_nhap').val(),
            mat_khau: $('#mat_khau').val(),
            email: $('#email').val(),
            vai_tro: $('#vai_tro').val()
        };

        $.ajax({
            url: 'api/create_user.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    formMessage.text(response.message).addClass('text-green-600');
                    createUserForm[0].reset(); // Xóa form
                    loadUsers(); // Tải lại danh sách
                } else {
                    formMessage.text(response.message).addClass('text-red-600');
                }
            },
            error: function() {
                formMessage.text('Đã xảy ra lỗi. Vui lòng thử lại.').addClass(
                    'text-red-600');
            }
        });
    });

    // Tải danh sách người dùng khi trang được mở
    loadUsers();
});
</script>