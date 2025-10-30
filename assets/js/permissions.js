function initializePermissionPage(mainContentContainer) {
    let currentData = null;

    // ========== TAB SWITCHING ==========
    function switchTab(tabName) {
        mainContentContainer.find('.tab-button')
            .removeClass('active border-blue-500 text-blue-600')
            .addClass('border-transparent text-gray-500');
        
        mainContentContainer.find(`.tab-button[data-tab="${tabName}"]`)
            .addClass('active border-blue-500 text-blue-600')
            .removeClass('border-transparent text-gray-500');

        // Hide all tabs
        mainContentContainer.find('.tab-content').hide();
        
        // Show selected tab
        mainContentContainer.find(`#${tabName}-tab-content`).show();
    }

    // ========== RENDER USERS TABLE ==========
    function renderUsersTable(data) {
        const tbody = mainContentContainer.find('#user-table-body');
        const roleSelect = mainContentContainer.find('#new-user-role');
        
        tbody.empty();
        roleSelect.empty();
        
        data.users.forEach((user) => {
            let roleOptions = '';
            data.roles.forEach(role => {
                const isSelected = user.MaVaiTro === role.MaVaiTro ? 'selected' : '';
                roleOptions += `<option value="${role.MaVaiTro}" ${isSelected}>${role.TenVaiTro}</option>`;
            });

            const isLocked = user.TrangThai == 0;
            const statusChecked = isLocked ? '' : 'checked';
            const lockClass = isLocked ? 'text-gray-400 line-through' : 'text-gray-900';
            
            const row = `
                <tr data-user-id="${user.UserID}" data-username="${user.HoTen}" class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium ${lockClass}">${user.HoTen}</td>
                    <td class="px-4 py-3">
                        <input type="text" class="user-details-input w-full border-gray-300 rounded-md shadow-sm text-sm p-2 focus:ring-blue-500 focus:border-blue-500" 
                               data-field="ChucVu" value="${user.ChucVu || ''}" ${isLocked ? 'disabled' : ''}>
                    </td>
                    <td class="px-4 py-3">
                        <input type="text" class="user-details-input w-full border-gray-300 rounded-md shadow-sm text-sm p-2 focus:ring-blue-500 focus:border-blue-500" 
                               data-field="SoDienThoai" value="${user.SoDienThoai || ''}" ${isLocked ? 'disabled' : ''}>
                    </td>
                    <td class="px-4 py-3 font-mono ${isLocked ? 'text-gray-400' : ''}">${user.TenDangNhap}</td>
                    <td class="px-4 py-3">
                        <select class="user-role-select w-full border-gray-300 rounded-md shadow-sm text-sm p-2 focus:ring-blue-500 focus:border-blue-500" ${isLocked ? 'disabled' : ''}>
                            ${roleOptions}
                        </select>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <label class="switch">
                            <input type="checkbox" class="lock-account-switch" ${statusChecked}>
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td class="px-4 py-3 text-center space-x-3">
                        <button class="change-password-btn text-blue-500 hover:text-blue-700" title="Đổi mật khẩu">
                            <i class="fas fa-key"></i>
                        </button>
                        <button class="delete-user-btn text-red-500 hover:text-red-700" title="Xóa người dùng">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            tbody.append(row);
        });

        // Populate role select
        data.roles.forEach(role => {
            if (role.MaVaiTro !== 'admin') {
                roleSelect.append(`<option value="${role.MaVaiTro}">${role.TenVaiTro}</option>`);
            }
        });
    }

    // ========== RENDER ROLES CARDS ==========
    function renderRolesCards(data) {
        const container = mainContentContainer.find('#roles-container');
        container.empty();

        data.roles.forEach((role) => {
            const isAdmin = role.MaVaiTro === 'admin';
            const cardClass = isAdmin ? 'role-card admin-card' : 'role-card';
            const deleteBtn = isAdmin ? '' : `
                <button class="delete-role-btn text-red-500 hover:text-red-700" data-role="${role.MaVaiTro}" title="Xóa vai trò">
                    <i class="fas fa-trash"></i>
                </button>`;
            const editBtn = isAdmin ? '' : `
                <button class="edit-role-btn text-blue-500 hover:text-blue-700" data-role="${role.MaVaiTro}" title="Sửa vai trò">
                    <i class="fas fa-edit"></i>
                </button>`;

            const card = `
                <div class="${cardClass}" data-role="${role.MaVaiTro}">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">${role.TenVaiTro}</h3>
                            <p class="text-xs text-gray-500 font-mono">${role.MaVaiTro}</p>
                        </div>
                        <div class="flex space-x-2">
                            ${editBtn}
                            ${deleteBtn}
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">${role.MoTa || 'Không có mô tả'}</p>
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-users mr-2"></i>
                        <span>${role.SoNguoiDung} người dùng</span>
                    </div>
                </div>`;
            container.append(card);
        });
    }

    // ========== RENDER PERMISSIONS TABLE ==========
    function renderPermissionsTable(data) {
        const colgroup = mainContentContainer.find('#permission-table-colgroup');
        const thead = mainContentContainer.find('#permission-table-head');
        const tbody = mainContentContainer.find('#permission-table-body');
        
        colgroup.empty();
        thead.empty();
        tbody.empty();

        colgroup.append('<col style="width: 300px; min-width: 300px;">');
        let headHtml = '<th class="text-left">Chức năng</th>';
        
        const roleColWidth = data.roles.length > 0 ? `${Math.max(120, 100 / data.roles.length)}px` : '120px';
        data.roles.forEach(role => {
            colgroup.append(`<col style="width: ${roleColWidth}; min-width: 100px;">`);
            headHtml += `<th class="text-center">${role.TenVaiTro}</th>`;
        });
        thead.html(headHtml);

        data.functions.forEach(func => {
            const isChild = func.ParentMaChucNang;
            const childClass = isChild ? 'function-child' : '';
            
            let rowHtml = `<td class="${childClass}">${func.TenChucNang}</td>`;
            
            data.roles.forEach(role => {
                const hasPermission = data.permissions[role.MaVaiTro] && 
                                    data.permissions[role.MaVaiTro].includes(func.MaChucNang);
                const isChecked = hasPermission ? 'checked' : '';
                const isDisabled = role.MaVaiTro === 'admin' ? 'disabled' : '';
                
                rowHtml += `
                    <td class="text-center">
                        <input type="checkbox" 
                               class="permission-checkbox" 
                               data-role="${role.MaVaiTro}" 
                               data-function="${func.MaChucNang}" 
                               ${isChecked} ${isDisabled}>
                    </td>`;
            });
            
            tbody.append(`<tr>${rowHtml}</tr>`);
        });
    }

    // ========== LOAD DATA ==========
    function loadData() {
        $.ajax({
            url: 'api/get_permission_data.php',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    currentData = response;
                    renderUsersTable(response);
                    renderRolesCards(response);
                    renderPermissionsTable(response);
                    
                    // Force show users tab
                    setTimeout(() => {
                        mainContentContainer.find('.tab-content').hide();
                        mainContentContainer.find('#users-tab-content').show();
                        
                        mainContentContainer.find('.tab-button')
                            .removeClass('active border-blue-500 text-blue-600')
                            .addClass('border-transparent text-gray-500');
                        mainContentContainer.find('.tab-button[data-tab="users"]')
                            .addClass('active border-blue-500 text-blue-600')
                            .removeClass('border-transparent text-gray-500');
                    }, 200);
                } else {
                    App.showMessageModal('Không thể tải dữ liệu phân quyền.', 'error');
                }
            },
            error: () => {
                App.showMessageModal('Lỗi kết nối khi tải dữ liệu phân quyền.', 'error');
            }
        });
    }

    // ========== EVENT HANDLERS ==========
    mainContentContainer.off();

    // Tab switching
    mainContentContainer.on('click', '.tab-button', function() {
        const tabName = $(this).data('tab');
        switchTab(tabName);
    });

    // ========== USER ACTIONS ==========
    mainContentContainer.on('click', '#add-user-btn', () => {
        $('#add-user-form')[0].reset();
        $('#add-user-modal').removeClass('hidden').addClass('flex');
    });

    mainContentContainer.on('click', '#cancel-add-user-btn', () => {
        $('#add-user-modal').addClass('hidden').removeClass('flex');
    });

    mainContentContainer.on('submit', '#add-user-form', function(e) {
        e.preventDefault();
        const password = $('#new-user-password').val();
        const confirmPassword = $('#new-user-confirm-password').val();
        
        if (password !== confirmPassword) {
            App.showMessageModal('Mật khẩu xác nhận không khớp!', 'error');
            return;
        }

        const userData = {
            action: 'add',
            hoTen: $('#new-user-hoten').val(),
            chucVu: $('#new-user-chucvu').val(),
            soDienThoai: $('#new-user-sodienthoai').val(),
            tenDangNhap: $('#new-user-tendangnhap').val(),
            email: $('#new-user-email').val(),
            password: password,
            maVaiTro: $('#new-user-role').val()
        };

        $.ajax({
            url: 'api/user_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(userData),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                    $('#add-user-modal').addClass('hidden').removeClass('flex');
                    loadData();
                } else {
                    App.showMessageModal(res.message, 'error');
                }
            },
            error: () => {
                App.showMessageModal('Lỗi kết nối khi thêm người dùng.', 'error');
            }
        });
    });

    mainContentContainer.on('click', '.change-password-btn', function() {
        const row = $(this).closest('tr');
        const userID = row.data('user-id');
        const username = row.data('username');
        $('#change-password-userid').val(userID);
        $('#change-password-username').text(username);
        $('#change-password-form')[0].reset();
        $('#change-password-modal').removeClass('hidden').addClass('flex');
    });

    mainContentContainer.on('click', '#cancel-change-password-btn', () => {
        $('#change-password-modal').addClass('hidden').removeClass('flex');
    });

    mainContentContainer.on('submit', '#change-password-form', function(e) {
        e.preventDefault();
        const newPassword = $('#change-password-new').val();
        const confirmPassword = $('#change-password-confirm').val();
        
        if (newPassword !== confirmPassword) {
            App.showMessageModal('Mật khẩu xác nhận không khớp!', 'error');
            return;
        }

        const payload = {
            action: 'change_password',
            userID: $('#change-password-userid').val(),
            newPassword: newPassword
        };

        $.ajax({
            url: 'api/user_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                    $('#change-password-modal').addClass('hidden').removeClass('flex');
                } else {
                    App.showMessageModal(res.message, 'error');
                }
            }
        });
    });

    mainContentContainer.on('change', '.user-role-select', function() {
        const userID = $(this).closest('tr').data('user-id');
        const maVaiTro = $(this).val();
        
        $.ajax({
            url: 'api/user_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'update_role', userID, maVaiTro }),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                } else {
                    App.showMessageModal(res.message, 'error');
                    loadData();
                }
            }
        });
    });

    mainContentContainer.on('change', '.user-details-input', function() {
        const input = $(this);
        const userID = input.closest('tr').data('user-id');
        const field = input.data('field');
        const value = input.val();
        
        $.ajax({
            url: 'api/user_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'update_info', userID, field, value }),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    input.css('background-color', '#dcfce7');
                    setTimeout(() => input.css('background-color', ''), 1500);
                } else {
                    App.showMessageModal(res.message, 'error');
                    loadData();
                }
            },
            error: () => {
                App.showMessageModal('Lỗi kết nối khi cập nhật thông tin.', 'error');
                loadData();
            }
        });
    });

    mainContentContainer.on('change', '.lock-account-switch', function() {
        const userID = $(this).closest('tr').data('user-id');
        const newStatus = $(this).is(':checked') ? 1 : 0;
        
        $.ajax({
            url: 'api/user_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'toggle_status', userID, newStatus }),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                    loadData();
                } else {
                    App.showMessageModal(res.message, 'error');
                    $(this).prop('checked', !$(this).prop('checked'));
                }
            }
        });
    });

    mainContentContainer.on('click', '.delete-user-btn', function() {
        const row = $(this).closest('tr');
        const userID = row.data('user-id');
        const username = row.data('username');
        
        App.showConfirmationModal(
            'Xác nhận xóa',
            `Bạn có chắc chắn muốn xóa người dùng "${username}"?`, 
            () => {
                $.ajax({
                    url: 'api/user_actions.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', userID }),
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            App.showMessageModal(res.message, 'success');
                            loadData();
                        } else {
                            App.showMessageModal(res.message, 'error');
                        }
                    }
                });
            }
        );
    });

    // ========== ROLE ACTIONS ==========
    mainContentContainer.on('click', '#add-role-btn', () => {
        $('#role-modal-title').text('Thêm Vai Trò Mới');
        $('#role-action').val('add');
        $('#role-form')[0].reset();
        $('#role-ma').prop('disabled', false);
        $('#role-modal').removeClass('hidden').addClass('flex');
    });

    mainContentContainer.on('click', '.edit-role-btn', function() {
        const maVaiTro = $(this).data('role');
        const role = currentData.roles.find(r => r.MaVaiTro === maVaiTro);
        
        if (role) {
            $('#role-modal-title').text('Sửa Vai Trò');
            $('#role-action').val('update');
            $('#role-ma').val(role.MaVaiTro).prop('disabled', true);
            $('#role-ten').val(role.TenVaiTro);
            $('#role-mota').val(role.MoTa || '');
            $('#role-modal').removeClass('hidden').addClass('flex');
        }
    });

    mainContentContainer.on('click', '#cancel-role-btn', () => {
        $('#role-modal').addClass('hidden').removeClass('flex');
    });

    mainContentContainer.on('submit', '#role-form', function(e) {
        e.preventDefault();
        const action = $('#role-action').val();
        const maVaiTro = $('#role-ma').val().toLowerCase().trim();
        const tenVaiTro = $('#role-ten').val().trim();
        const moTa = $('#role-mota').val().trim();

        if (!/^[a-z_]+$/.test(maVaiTro)) {
            App.showMessageModal('Mã vai trò chỉ được chứa chữ thường và dấu gạch dưới (_)', 'error');
            return;
        }

        const payload = {
            action: action,
            maVaiTro: maVaiTro,
            tenVaiTro: tenVaiTro,
            moTa: moTa
        };

        $.ajax({
            url: 'api/role_actions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: (res) => {
                if (res.success) {
                    App.showMessageModal(res.message, 'success');
                    $('#role-modal').addClass('hidden').removeClass('flex');
                    loadData();
                } else {
                    App.showMessageModal(res.message, 'error');
                }
            },
            error: () => {
                App.showMessageModal('Lỗi kết nối khi xử lý vai trò.', 'error');
            }
        });
    });

    mainContentContainer.on('click', '.delete-role-btn', function() {
        const maVaiTro = $(this).data('role');
        const role = currentData.roles.find(r => r.MaVaiTro === maVaiTro);
        
        if (role) {
            const message = role.SoNguoiDung > 0 
                ? `Vai trò "${role.TenVaiTro}" hiện có ${role.SoNguoiDung} người dùng. Bạn có chắc chắn muốn xóa?`
                : `Bạn có chắc chắn muốn xóa vai trò "${role.TenVaiTro}"?`;
            
            App.showConfirmationModal('Xác nhận xóa', message, () => {
                $.ajax({
                    url: 'api/role_actions.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ action: 'delete', maVaiTro: maVaiTro }),
                    dataType: 'json',
                    success: (res) => {
                        if (res.success) {
                            App.showMessageModal(res.message, 'success');
                            loadData();
                        } else {
                            App.showMessageModal(res.message, 'error');
                        }
                    },
                    error: () => {
                        App.showMessageModal('Lỗi kết nối khi xóa vai trò.', 'error');
                    }
                });
            });
        }
    });

    // ========== PERMISSIONS ACTIONS ==========
    mainContentContainer.on('click', '#save-permissions-btn', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        const updatedPermissions = {};
        
        currentData.roles.forEach(role => {
            updatedPermissions[role.MaVaiTro] = [];
        });
        
        mainContentContainer.find('.permission-checkbox:checked').each(function() {
            const role = $(this).data('role');
            const func = $(this).data('function');
            updatedPermissions[role].push(func);
        });
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
        
        $.ajax({
            url: 'api/save_permissions.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(updatedPermissions),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal(response.message, 'success');
                } else {
                    App.showMessageModal(response.message || 'Có lỗi xảy ra khi lưu.', 'error');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi kết nối server khi lưu phân quyền.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // ========== INITIALIZE ==========
    loadData();
}