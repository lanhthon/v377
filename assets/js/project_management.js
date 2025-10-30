/**
 * Khởi tạo trang quản lý dự án.
 * Hàm này sẽ gắn tất cả các sự kiện và tải dữ liệu ban đầu.
 * @param {jQuery} mainContentContainer - jQuery object của thẻ div chứa toàn bộ nội dung trang.
 */
function initializeProjectManagementPage(mainContentContainer) {
    // === KHAI BÁO BIẾN ===
    const projectListBody = mainContentContainer.find('#project-list-body');
    const projectSearchInput = mainContentContainer.find('#project-search-input');
    const projectStatusTabs = mainContentContainer.find('#project-status-tabs');
    const projectProvinceFilter = mainContentContainer.find('#project-province-filter');
    
    const projectModal = $('#project-modal');
    const projectModalTitle = $('#project-modal-title');
    const projectForm = $('#project-form');
    const confirmationModal = $('#confirmation-modal');
    const messageModal = $('#message-modal');
    const hangMucCheckboxContainer = $('#hang-muc-checkbox-container');

    // BIẾN CHO COMMENT MODAL
    const commentModal = $('#comment-modal');
    const commentModalTitle = $('#comment-modal-title');
    const commentList = $('#comment-list');
    const commentForm = $('#comment-form');

    // BIẾN CHO VIEW MODAL
    const viewModal = $('#view-project-modal');

    let allProjects = [];
    let currentFilterStatus = 'all';
    let currentFilterProvince = 'all';
    let confirmCallback = null;
    let currentUserName = 'Guest';

    // === CÁC HÀM TIỆN ÍCH ===

    function formatCurrency(number) {
        if (number === null || number === undefined || isNaN(number) || number === "") return '';
        return new Intl.NumberFormat('vi-VN').format(number);
    }

    function showMessage(message, type = 'success') {
        $('#message-text').text(message);
        messageModal.removeClass('bg-green-500 bg-red-500 text-white').addClass(type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white');
        messageModal.show().css('transform', 'translateX(0)');
        setTimeout(() => {
            messageModal.css('transform', 'translateX(200%)');
            setTimeout(() => messageModal.hide(), 300);
        }, 5000);
    }

    function showConfirmation(message, callback) {
        $('#confirmation-message').text(message);
        confirmCallback = callback;
        confirmationModal.removeClass('hidden').addClass('flex');
    }
    
    function getProvinceFromProject(project) {
        if (project.TinhThanh) return project.TinhThanh;
        const locations = ['Hà Nội', 'Hải Phòng', 'Bắc Giang', 'Bắc Ninh', 'Hưng Yên', 'Thái Bình', 'Quảng Ninh', 'Vĩnh Phúc', 'Nha Trang', 'Quảng Trị'];
        const textToSearch = `${project.TenDuAn || ''} ${project.DiaChi || ''}`;
        for (const location of locations) {
            if (textToSearch.toLowerCase().includes(location.toLowerCase())) return location;
        }
        return null;
    }

    function populateProvinceFilter() {
        const provinces = new Set();
        allProjects.forEach(project => {
            const province = getProvinceFromProject(project);
            if (province) provinces.add(province);
        });
        projectProvinceFilter.html('<option value="all">Tất cả tỉnh thành</option>');
        const sortedProvinces = Array.from(provinces).sort((a, b) => a.localeCompare(b, 'vi'));
        sortedProvinces.forEach(province => {
            projectProvinceFilter.append(`<option value="${province}">${province}</option>`);
        });
    }


    // === CÁC HÀM XỬ LÝ DỮ LIỆU ===
    
    function getKetQuaClass(ketQua) {
        if (!ketQua) return 'bg-gray-200 text-gray-800';
        ketQua = ketQua.toLowerCase();
        if (ketQua.includes('mới')) return 'bg-gray-200 text-gray-800';
        if (ketQua.includes('đang theo')) return 'bg-blue-200 text-blue-800';
        if (ketQua.includes('đấu thầu')) return 'bg-purple-200 text-purple-800';
        if (ketQua.includes('báo giá')) return 'bg-yellow-200 text-yellow-800';
        if (ketQua.includes('chốt đơn')) return 'bg-green-200 text-green-800';
        if (ketQua.includes('tạch')) return 'bg-red-200 text-red-800';
        if (ketQua.includes('đã hoàn thiện')) return 'bg-teal-200 text-teal-800';
        return 'bg-gray-200 text-gray-800';
    }

    function getFilteredProjects() {
        const searchText = projectSearchInput.val().toLowerCase();
        return allProjects.filter(project => {
            const matchesStatus = currentFilterStatus === 'all' || (project.KetQua && project.KetQua.toLowerCase().includes(currentFilterStatus.toLowerCase()));
            if (!matchesStatus) return false;
            const projectProvince = getProvinceFromProject(project);
            const matchesProvince = currentFilterProvince === 'all' || (projectProvince && projectProvince === currentFilterProvince);
            if (!matchesProvince) return false;
            if (!searchText) return true;
            return Object.values(project).some(value => value && value.toString().toLowerCase().includes(searchText));
        });
    }


    function renderProjectList() {
        projectListBody.empty();
        const filteredProjects = getFilteredProjects();

        if (filteredProjects.length === 0) {
            projectListBody.append(`<tr><td colspan="18" class="text-center py-10 text-gray-500">Không tìm thấy dự án nào phù hợp.</td></tr>`);
            return;
        }

        filteredProjects.forEach(project => {
            const hangMucHtml = (project.HangMucBaoGia || '')
                .split(',')
                .map(item => item.trim())
                .filter(item => item)
                .map(item => `<span class="bg-gray-200 text-gray-700 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">${item}</span>`)
                .join(' ');

            const row = `
                <tr class="hover:bg-gray-50 border-b border-gray-200">
                    <td class="py-2 px-4 text-center sticky left-0 bg-white hover:bg-gray-50 z-10">
                        <div class="flex item-center justify-center space-x-2">
                            <button class="view-project-btn text-gray-600 hover:text-gray-800" data-id="${project.DuAnID}" title="Xem"><i class="fas fa-eye"></i></button>
                            <button class="add-comment-btn text-green-600 hover:text-green-800" data-id="${project.DuAnID}" data-name="${project.TenDuAn}" title="Theo dõi"><i class="fas fa-plus"></i></button>
                            <button class="edit-project-btn text-blue-600 hover:text-blue-800" data-id="${project.DuAnID}" title="Sửa"><i class="fas fa-edit"></i></button>
                            <button class="delete-project-btn text-red-600 hover:text-red-800" data-id="${project.DuAnID}" title="Xóa"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                    <td class="py-2 px-4">${project.MaDuAn || ''}</td>
                    <td class="py-2 px-4 font-semibold max-w-xs truncate" title="${project.TenDuAn || ''}">${project.TenDuAn || ''}</td>
                    <td class="py-2 px-4 max-w-md truncate" title="${project.DiaChi || ''}">${project.DiaChi || ''}</td>
                    <td class="py-2 px-4">${project.TinhThanh || ''}</td>
                    <td class="py-2 px-4">${project.LoaiHinh || ''}</td>
                    <td class="py-2 px-4">${project.GiaTriDauTu || ''}</td>
                    <td class="py-2 px-4">${project.NgayKhoiCong || ''}</td>
                    <td class="py-2 px-4">${project.NgayHoanCong || ''}</td>
                    <td class="py-2 px-4 max-w-xs truncate" title="${project.ChuDauTu || ''}">${project.ChuDauTu || ''}</td>
                    <td class="py-2 px-4 max-w-xs truncate" title="${project.TongThau || ''}">${project.TongThau || ''}</td>
                    <td class="py-2 px-4 max-w-xs truncate" title="${project.ThauMEP || ''}">${project.ThauMEP || ''}</td>
                    <td class="py-2 px-4 max-w-xs truncate" title="${project.DauMoiLienHe || ''}">${project.DauMoiLienHe || ''}</td>
                    <td class="py-2 px-4 max-w-sm truncate">${hangMucHtml}</td>
                    <td class="py-2 px-4 text-right text-green-700 font-medium">${formatCurrency(project.GiaTriDuKien)}</td>
                    <td class="py-2 px-4">${project.TienDoLamViec || ''}</td>
                    <td class="py-2 px-4"><span class="px-2 py-1 text-xs rounded-full ${getKetQuaClass(project.KetQua)}">${project.KetQua || ''}</span></td>
                    <td class="py-2 px-4">${project.SalePhuTrach || ''}</td>
                </tr>`;
            projectListBody.append(row);
        });
    }

    function loadProjects() {
        projectListBody.html(`<tr><td colspan="18" class="text-center py-10">Đang tải dữ liệu...</td></tr>`);
        $.ajax({
            url: 'api/get_projects_full.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (Array.isArray(response)) {
                    allProjects = response;
                    populateProvinceFilter();
                    renderProjectList();
                } else { showMessage('Lỗi: Dữ liệu nhận được không hợp lệ.', 'error'); }
            },
            error: () => {
                showMessage('Lỗi kết nối đến server. Không thể tải danh sách dự án.', 'error');
                projectListBody.html(`<tr><td colspan="18" class="text-center py-10 text-red-500">Lỗi kết nối. Vui lòng thử lại.</td></tr>`);
            }
        });
    }

    function loadCurrentUserInfo() {
        $.ajax({
            url: 'api/get_current_user.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => { if (response.success && response.HoTen) currentUserName = response.HoTen; },
            error: () => console.error('Không thể lấy thông tin người dùng hiện tại.')
        });
    }

    function loadSalesUsers() {
        const salePhuTrachSelect = $('#SalePhuTrach');
        $.ajax({
            url: 'api/get_users_by_project.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                salePhuTrachSelect.empty().append('<option value="">-- Chọn người phụ trách --</option>');
                if (response.success && Array.isArray(response.users)) {
                    response.users.forEach(user => salePhuTrachSelect.append(`<option value="${user.HoTen}">${user.HoTen}</option>`));
                } else { console.error('Lỗi tải danh sách người dùng:', response.message || 'Dữ liệu không hợp lệ.'); }
            },
            error: () => console.error('Không thể tải danh sách người dùng. Lỗi server.')
        });
    }

    function populateHangMucCheckboxes() {
        $.ajax({
            url: 'api/get_hangmuc.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                hangMucCheckboxContainer.empty();
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(item => {
                        const checkboxHtml = `<label class="flex items-center cursor-pointer"><input type="checkbox" name="HangMucBaoGia" value="${item.TenHangMuc}" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span class="ml-2 text-gray-700">${item.TenHangMuc}</span></label>`;
                        hangMucCheckboxContainer.append(checkboxHtml);
                    });
                } else { hangMucCheckboxContainer.html('<p class="text-gray-500">Không tải được danh sách hạng mục.</p>'); }
            },
            error: () => {
                console.error('Không thể tải danh sách hạng mục.');
                hangMucCheckboxContainer.html('<p class="text-red-500">Lỗi tải danh sách hạng mục.</p>');
            }
        });
    }

    function loadComments(duAnID) {
        commentList.html('<p class="text-center text-gray-500">Đang tải bình luận...</p>');
        $.ajax({
            url: `api/comment_actions.php?action=get_comments&DuAnID=${duAnID}`,
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success && Array.isArray(response.data)) {
                    renderComments(response.data);
                } else { commentList.html('<p class="text-center text-red-500">Không tải được bình luận.</p>'); }
            },
            error: () => commentList.html('<p class="text-center text-red-500">Lỗi kết nối server.</p>')
        });
    }

    function renderComments(comments) {
        commentList.empty();
        if (comments.length === 0) {
            commentList.html('<p class="text-center text-gray-500">Chưa có bình luận nào.</p>');
            return;
        }
        comments.forEach(comment => {
            const commentDate = new Date(comment.NgayBinhLuan).toLocaleString('vi-VN');
            const commentHtml = `<div class="bg-gray-100 p-3 rounded-lg"><div class="flex justify-between items-center mb-1"><p class="font-semibold text-blue-600">${comment.NguoiBinhLuan || 'System'}</p><p class="text-xs text-gray-500">${commentDate}</p></div><p class="text-gray-800 whitespace-pre-wrap">${comment.NoiDung}</p></div>`;
            commentList.append(commentHtml);
        });
    }


    // === GÁN CÁC SỰ KIỆN (EVENT LISTENERS) ===
    mainContentContainer.off('click input change submit');

    mainContentContainer.on('click', '#add-project-btn', function() {
        projectModalTitle.text('Thêm Dự Án Mới');
        projectForm[0].reset();
        $('#project-id').val('');
        $('#MaDuAn').prop('readonly', false);
        hangMucCheckboxContainer.find('input[type="checkbox"]').prop('checked', false);
        projectModal.removeClass('hidden').addClass('flex');
    });

    mainContentContainer.on('click', '#export-excel-btn', function() {
        const filteredData = getFilteredProjects();
        if (filteredData.length === 0) {
            showMessage('Không có dữ liệu để xuất.', 'error');
            return;
        }
        const mainDataForSheet = filteredData.map(p => ({"Mã": p.MaDuAn, "Tên Dự Án": p.TenDuAn, "Địa Chỉ": p.DiaChi, "Tỉnh Thành": p.TinhThanh, "Loại Hình": p.LoaiHinh, "Giá Trị Đầu Tư": p.GiaTriDauTu, "Khởi Công": p.NgayKhoiCong, "Hoàn Công": p.NgayHoanCong, "Chủ Đầu Tư": p.ChuDauTu, "Tổng Thầu": p.TongThau, "Thầu MEP": p.ThauMEP, "Đầu Mối Làm Việc": p.DauMoiLienHe, "Hạng Mục Báo Giá": p.HangMucBaoGia, "Giá Trị Dự Kiến (VND)": p.GiaTriDuKien, "Tiến Độ": p.TienDoLamViec, "Kết Quả": p.KetQua, "Sale Phụ Trách": p.SalePhuTrach}));
        const commentsForSheet = [];
        filteredData.forEach(p => {
            if (p.Comments) {
                p.Comments.split('\n').forEach(comment => {
                    commentsForSheet.push({"Mã Dự Án": p.MaDuAn, "Tên Dự Án": p.TenDuAn, "Nội dung theo dõi": comment});
                });
            }
        });
        const mainWorksheet = XLSX.utils.json_to_sheet(mainDataForSheet);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, mainWorksheet, "DanhSachDuAn");
        const mainColWidths = Object.keys(mainDataForSheet[0]).map(key => ({ wch: Math.max(...mainDataForSheet.map(item => (item[key] || "").toString().length), key.length) + 2 }));
        mainWorksheet["!cols"] = mainColWidths;
        if (commentsForSheet.length > 0) {
            const commentWorksheet = XLSX.utils.json_to_sheet(commentsForSheet);
            XLSX.utils.book_append_sheet(workbook, commentWorksheet, "TheoDoiDuAn");
            commentWorksheet["!cols"] = [{ wch: 15 }, { wch: 40 }, { wch: 80 }];
        }
        XLSX.writeFile(workbook, "Danh_sach_du_an.xlsx");
        showMessage('Đã xuất file Excel thành công!', 'success');
    });

    mainContentContainer.on('click', '.edit-project-btn', function() {
        const id = $(this).data('id');
        const project = allProjects.find(p => p.DuAnID == id);
        if (project) {
            projectModalTitle.text('Chỉnh Sửa Dự Án');
            projectForm[0].reset();
            Object.keys(project).forEach(key => {
                const formKey = (key === 'TienDoLamViec') ? 'TienDo' : key;
                if (formKey !== 'HangMucBaoGia' && formKey !== 'Comments') {
                    const input = projectForm.find(`#${formKey}`);
                    if (input.length) input.val(project[key]);
                }
            });
            hangMucCheckboxContainer.find('input[type="checkbox"]').prop('checked', false);
            const hangMucArray = (project.HangMucBaoGia || '').split(',').map(item => item.trim()).filter(item => item);
            hangMucArray.forEach(hm => hangMucCheckboxContainer.find(`input[value="${hm}"]`).prop('checked', true));
            $('#project-id').val(project.DuAnID);
            $('#MaDuAn').prop('readonly', true);
            projectModal.removeClass('hidden').addClass('flex');
        }
    });

    mainContentContainer.on('click', '.view-project-btn', function() {
        const id = $(this).data('id');
        const project = allProjects.find(p => p.DuAnID == id);
        if (project) {
            $('#view-TenDuAn').text(project.TenDuAn || 'N/A');
            $('#view-MaDuAn').text(project.MaDuAn || 'N/A');
            $('#view-LoaiHinh').text(project.LoaiHinh || 'N/A');
            $('#view-GiaTriDauTu').text(project.GiaTriDauTu || 'N/A');
            $('#view-DiaChi').text(project.DiaChi || 'N/A');
            $('#view-TinhThanh').text(project.TinhThanh || 'N/A');
            $('#view-NgayKhoiCong').text(project.NgayKhoiCong || 'N/A');
            $('#view-NgayHoanCong').text(project.NgayHoanCong || 'N/A');
            $('#view-ChuDauTu').text(project.ChuDauTu || 'N/A');
            $('#view-TongThau').text(project.TongThau || 'N/A');
            $('#view-ThauMEP').text(project.ThauMEP || 'N/A');
            $('#view-DauMoiLienHe').text(project.DauMoiLienHe || 'N/A');
            $('#view-GiaTriDuKien').text(formatCurrency(project.GiaTriDuKien));
            $('#view-TienDoLamViec').text(project.TienDoLamViec || 'N/A');
            $('#view-KetQua').text(project.KetQua || 'N/A');
            $('#view-SalePhuTrach').text(project.SalePhuTrach || 'N/A');
            const hangMucHtml = (project.HangMucBaoGia || '').split(',').map(item => item.trim()).filter(item => item).map(item => `<span class="bg-gray-200 text-gray-700 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">${item}</span>`).join('');
            $('#view-HangMucBaoGia').html(hangMucHtml || 'N/A');
            viewModal.removeClass('hidden').addClass('flex');
        }
    });

    mainContentContainer.on('click', '.delete-project-btn', function() {
        const id = $(this).data('id');
        showConfirmation('Bạn có chắc chắn muốn xóa dự án này? Hành động này không thể hoàn tác.', () => {
            $.ajax({
                url: 'api/project_actions_full.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete', DuAnID: id }),
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        showMessage(response.message, 'success');
                        loadProjects();
                    } else { showMessage(response.message, 'error'); }
                },
                error: (jqXHR) => {
                    let errorMessage = 'Lỗi kết nối server khi xóa dự án.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) errorMessage = jqXHR.responseJSON.message;
                    showMessage(errorMessage, 'error');
                }
            });
        });
    });

    mainContentContainer.on('click', '.add-comment-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        commentModalTitle.html(`Theo dõi Dự án: <span class="text-blue-600">${name}</span>`);
        $('#comment-project-id').val(id);
        commentForm[0].reset();
        $('#NguoiBinhLuan').val(currentUserName).prop('readonly', true);
        commentModal.removeClass('hidden').addClass('flex');
        loadComments(id);
    });
    
    mainContentContainer.on('click', '#project-status-tabs .tab-button', function() {
        projectStatusTabs.find('.tab-button').removeClass('active');
        $(this).addClass('active');
        currentFilterStatus = $(this).data('status');
        renderProjectList();
    });

    mainContentContainer.on('change', '#project-province-filter', function() {
        currentFilterProvince = $(this).val();
        renderProjectList();
    });

    let searchTimeout;
    mainContentContainer.on('input', '#project-search-input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => renderProjectList(), 300);
    });
    
    // === CÁC SỰ KIỆN CHO MODAL ===
    
    $('#close-modal-btn, #cancel-project-btn').off('click').on('click', () => projectModal.addClass('hidden').removeClass('flex'));
    $('#close-view-modal-btn').off('click').on('click', () => viewModal.addClass('hidden').removeClass('flex'));

    projectForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        const projectData = {};
        $(this).find('input[id], select[id], textarea[id]').each(function() {
            projectData[$(this).attr('id')] = $(this).val();
        });
        const hangMucValues = [];
        $('#hang-muc-checkbox-container input[name="HangMucBaoGia"]:checked').each(function() {
            hangMucValues.push($(this).val());
        });
        projectData.HangMucBaoGia = hangMucValues;
        if (projectData.hasOwnProperty('TienDo')) {
            projectData.TienDoLamViec = projectData.TienDo;
            delete projectData.TienDo;
        }
        projectData.action = $('#project-id').val() ? 'update' : 'add';
        projectData.DuAnID = $('#project-id').val();
        $.ajax({
            url: 'api/project_actions_full.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(projectData),
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    showMessage(response.message, 'success');
                    projectModal.addClass('hidden').removeClass('flex');
                    loadProjects();
                } else { showMessage(response.message || 'Có lỗi xảy ra.', 'error'); }
            },
            error: (jqXHR) => {
                let errorMessage = 'Lỗi kết nối server.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) errorMessage = jqXHR.responseJSON.message;
                else if (jqXHR.responseText) errorMessage = "Lỗi Server: " + jqXHR.responseText.substring(0, 200);
                showMessage(errorMessage, 'error');
            }
        });
    });

    $('#confirm-action-btn').off('click').on('click', () => {
        if (confirmCallback) confirmCallback();
        confirmationModal.addClass('hidden').removeClass('flex');
    });
    $('#confirm-cancel-btn').off('click').on('click', () => confirmationModal.addClass('hidden').removeClass('flex'));
    
    $('#close-comment-modal-btn').off('click').on('click', () => commentModal.addClass('hidden').removeClass('flex'));

    commentForm.off('submit').on('submit', function(e) {
        e.preventDefault();
        const commentData = {
            DuAnID: $('#comment-project-id').val(),
            NguoiBinhLuan: $('#NguoiBinhLuan').val(),
            NoiDung: $('#NoiDung').val()
        };
        $.ajax({
            url: 'api/comment_actions.php?action=add_comment',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(commentData),
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    showMessage('Thêm bình luận thành công!', 'success');
                    $('#NoiDung').val(''); 
                    loadComments(commentData.DuAnID);
                    const projectToUpdate = allProjects.find(p => p.DuAnID == commentData.DuAnID);
                    if (projectToUpdate) {
                        const newCommentText = `${commentData.NguoiBinhLuan} (${new Date().toLocaleString('vi-VN')}): ${commentData.NoiDung}`;
                        projectToUpdate.Comments = projectToUpdate.Comments ? `${newCommentText}\n${projectToUpdate.Comments}` : newCommentText;
                    }
                } else { showMessage(response.message || 'Có lỗi xảy ra.', 'error'); }
            },
            error: () => showMessage('Lỗi kết nối server.', 'error')
        });
    });

    // === KHỞI TẠO ===
    loadCurrentUserInfo();
    loadProjects();
    loadSalesUsers();
    populateHangMucCheckboxes();
}
