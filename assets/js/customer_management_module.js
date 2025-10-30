// =================================================================
// CÁC BIẾN VÀ HÀM Ở PHẠM VI MODULE (GLOBAL TO THIS FILE)
// =================================================================

let table; 
let globalClickHandler; 
let globalSubmitHandler; 

function destroyCustomerManagementPage() {
    console.log("Destroying Customer Management Page resources...");
    if (globalClickHandler) {
        document.body.removeEventListener('click', globalClickHandler);
        globalClickHandler = null;
    }
    if (globalSubmitHandler) {
        document.body.removeEventListener('submit', globalSubmitHandler);
        globalSubmitHandler = null;
    }
    if (table) {
        table.destroy();
        table = null;
    }
    console.log("Customer Management Page destroyed successfully.");
}

function initializeCustomerManagementPage() {
    console.log("Initializing Customer Management Page...");

    let originalTableData = [];
    let coCheGiaOptions = [];
    const customerGroups = ['Đại Lý', 'Chiến lược', 'Thân Thiết', 'Tiềm năng'];
    let currentUserName = 'Guest';

    // =================================================================
    // CÁC HÀM TIỆN ÍCH
    // =================================================================
    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        if (!toast) return;
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
    }

    async function fetchAPI(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok || (result && result.success === false)) {
                throw new Error(result.message || 'Lỗi không xác định từ server');
            }
            return result;
        } catch (error) {
            showToast(error.message, 'error');
            console.error('Lỗi Fetch:', error);
            throw error;
        }
    }

    async function loadCurrentUserInfo() {
        try {
            const response = await fetchAPI('api/get_current_user.php');
            if (response.success && response.HoTen) {
                currentUserName = response.HoTen;
            }
        } catch (error) {
            console.error('Không thể lấy thông tin người dùng hiện tại.');
        }
    }

    // =================================================================
    // QUẢN LÝ MODAL
    // =================================================================
    function openModal(modalId, title, content) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with id "${modalId}" not found.`);
            return;
        }
        modal.innerHTML = `
            <div class="modal-content large">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <span class="close-btn">&times;</span>
                </div>
                ${content}
            </div>`;
        modal.style.display = "flex";
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = "none";
    }

    // =================================================================
    // CÁC HÀM FORM VÀ POPUP XEM CHI TIẾT
    // =================================================================
    async function loadAndRenderDetailsComments(companyId) {
        const commentListContainer = document.getElementById('details-comment-list');
        if (!commentListContainer) return;

        try {
            const result = await fetchAPI(`api/customer_comment_actions.php?action=get_comments&CongTyID=${companyId}`);
            const comments = result.data;

            if (!comments || comments.length === 0) {
                commentListContainer.innerHTML = '<p>Chưa có lịch sử làm việc.</p>';
                return;
            }

            commentListContainer.innerHTML = comments.map(comment => {
                const commentDate = new Date(comment.NgayBinhLuan).toLocaleString('vi-VN');
                return `<div class="comment-item">
                            <div class="comment-header">
                                <strong class="comment-author">${comment.NguoiBinhLuan || 'System'}</strong>
                                <span class="comment-date">${commentDate}</span>
                            </div>
                            <p class="comment-body">${comment.NoiDung}</p>
                        </div>`;
            }).join('');

        } catch (error) {
            commentListContainer.innerHTML = '<p style="color:red;">Lỗi tải lịch sử làm việc.</p>';
        }
    }
    
    function showCompanyDetailsPopup(data) {
        const title = `Chi tiết công ty: ${data.TenCongTy}`;

        let contactsHtml = '<h4>Chưa có người liên hệ.</h4>';
        if (data._children && data._children.length > 0) {
            contactsHtml = `
                <ul class="contact-details-list">
                    ${data._children.map(contact => `
                        <li>
                            <strong>${contact.HoTen || 'N/A'}</strong> - <span>${contact.ChucVu || 'Chưa có chức vụ'}</span>
                            <div class="contact-info">
                                <i class="fa fa-phone"></i> ${contact.SoDiDong || 'N/A'}
                            </div>
                        </li>
                    `).join('')}
                </ul>`;
        }

        const content = `
            <div class="details-popup-content">
                <div class="details-section">
                    <h3>Thông tin chung</h3>
                    <div class="details-grid">
                        <div><strong>Mã Công ty:</strong> ${data.MaCongTy || 'N/A'}</div>
                        <div><strong>Tên Công ty:</strong> ${data.TenCongTy || 'N/A'}</div>
                        <div><strong>Nhóm KH:</strong> ${data.NhomKhachHang || 'N/A'}</div>
                        <div><strong>Mã số thuế:</strong> ${data.MaSoThue || 'N/A'}</div>
                        <div><strong>Điện thoại:</strong> ${data.SoDienThoaiChinh || 'N/A'}</div>
                        <div><strong>Website:</strong> ${data.Website ? `<a href="${!/^https?:\/\//i.test(data.Website) ? 'https://' + data.Website : data.Website}" target="_blank">${data.Website}</a>` : 'N/A'}</div>
                        <div class="full-span"><strong>Địa chỉ:</strong> ${data.DiaChi || 'N/A'}</div>
                        <div><strong>Cơ chế giá:</strong> ${data.TenCoChe ? `${data.TenCoChe} (${data.PhanTramDieuChinh || 0}%)` : 'Chưa chọn'}</div>
                        <div><strong>Số BG đã chốt:</strong> ${data.SoBaoGiaDaChot || 0}</div>
                    </div>
                </div>
                <div class="details-section">
                    <h3>Danh sách người liên hệ</h3>
                    ${contactsHtml}
                </div>
                <div class="details-section">
                    <h3>Lịch sử làm việc</h3>
                    <div id="details-comment-list" class="comment-list-container">
                        <p>Đang tải lịch sử...</p>
                    </div>
                </div>
            </div>`;
        
        openModal('details-modal', title, content);
        loadAndRenderDetailsComments(data.CongTyID);
    }

    function showCompanyForm(company = {}) {
        const isEdit = !!company.CongTyID;
        const title = isEdit ? `Sửa thông tin công ty: ${company.TenCongTy}` : 'Thêm Công Ty Mới';

        let coCheGiaSelectOptions = '<option value="">-- Chọn --</option>';
        coCheGiaOptions.forEach(opt => {
            const selected = opt.CoCheGiaID == company.CoCheGiaID ? 'selected' : '';
            coCheGiaSelectOptions += `<option value="${opt.CoCheGiaID}" ${selected}>${opt.TenCoChe}</option>`;
        });

        let customerGroupOptions = '<option value="">-- Chọn --</option>';
        customerGroups.forEach(group => {
            const selected = group === company.NhomKhachHang ? 'selected' : '';
            customerGroupOptions += `<option value="${group}" ${selected}>${group}</option>`;
        });

        const formContent = `
            <form id="company-form">
                <input type="hidden" name="CongTyID" value="${company.CongTyID || ''}">
                <div class="form-grid">
                    <div class="form-group"> <label>Mã Công Ty (*)</label> <input type="text" name="MaCongTy" value="${company.MaCongTy || ''}" required> </div>
                    <div class="form-group"> <label>Tên Công Ty (*)</label> <input type="text" name="TenCongTy" value="${company.TenCongTy || ''}" required> </div>
                    <div class="form-group"> <label>Nhóm Khách Hàng</label> <select name="NhomKhachHang">${customerGroupOptions}</select> </div>
                    <div class="form-group"> <label>Mã Số Thuế</label> <input type="text" name="MaSoThue" value="${company.MaSoThue || ''}"> </div>
                    <div class="form-group"> <label>Số Điện Thoại</label> <input type="text" name="SoDienThoaiChinh" value="${company.SoDienThoaiChinh || ''}"> </div>
                    <div class="form-group"> <label>Website</label> <input type="text" name="Website" value="${company.Website || ''}" placeholder="https://example.com"> </div>
                    <div class="form-group"> <label>Cơ Chế Giá</label> <select name="CoCheGiaID">${coCheGiaSelectOptions}</select> </div>
                    
                    <div class="form-group"> 
                        <label>Số Ngày Thanh Toán</label> 
                        <input type="number" name="SoNgayThanhToan" value="${company.SoNgayThanhToan || '30'}" required> 
                    </div>
                    <div class="form-group full-width"> <label>Địa Chỉ</label> <textarea name="DiaChi" rows="3">${company.DiaChi || ''}</textarea> </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="action-button" style="background-color: var(--primary-color);">Lưu</button>
                </div>
            </form>`;
        openModal('company-modal', title, formContent);
    }

    function showContactForm(contact = {}, companyId) {
        const isEdit = !!contact.NguoiLienHeID;
        const finalCompanyId = contact.CongTyID || companyId;
        const title = isEdit ? `Sửa người liên hệ: ${contact.HoTen}` : 'Thêm Người Liên Hệ Mới';
        const formContent = `
            <form id="contact-form">
                <input type="hidden" name="NguoiLienHeID" value="${contact.NguoiLienHeID || ''}">
                <input type="hidden" name="CongTyID" value="${finalCompanyId}">
                <div class="form-grid">
                    <div class="form-group"> <label>Họ Tên (*)</label> <input type="text" name="HoTen" value="${contact.HoTen || ''}" required> </div>
                    <div class="form-group"> <label>Chức Vụ</label> <input type="text" name="ChucVu" value="${contact.ChucVu || ''}"> </div>
                    <div class="form-group"> <label>Email</label> <input type="email" name="Email" value="${contact.Email || ''}"> </div>
                    <div class="form-group"> <label>Số Di Động</label> <input type="text" name="SoDiDong" value="${contact.SoDiDong || ''}"> </div>
                </div>
                <div class="modal-footer">
                     <button type="submit" class="action-button" style="background-color: var(--primary-color);">Lưu</button>
                </div>
            </form>`;
        openModal('contact-modal', title, formContent);
    }
    
    // =================================================================
    // QUẢN LÝ LỊCH SỬ LÀM VIỆC (COMMENT)
    // =================================================================
    async function loadComments(congTyID) {
        const commentList = document.getElementById('customer-comment-list');
        commentList.innerHTML = '<p>Đang tải lịch sử...</p>';
        try {
            const result = await fetchAPI(`api/customer_comment_actions.php?action=get_comments&CongTyID=${congTyID}`);
            renderComments(result.data);
        } catch (error) {
            commentList.innerHTML = '<p style="color:red;">Lỗi tải lịch sử.</p>';
        }
    }

    function renderComments(comments) {
        const commentList = document.getElementById('customer-comment-list');
        if (!comments || comments.length === 0) {
            commentList.innerHTML = '<p>Chưa có lịch sử làm việc.</p>';
            return;
        }
        commentList.innerHTML = comments.map(comment => {
            const commentDate = new Date(comment.NgayBinhLuan).toLocaleString('vi-VN');
            return `<div class="comment-item">
                        <div class="comment-header">
                            <strong class="comment-author">${comment.NguoiBinhLuan || 'System'}</strong>
                            <span class="comment-date">${commentDate}</span>
                        </div>
                        <p class="comment-body">${comment.NoiDung}</p>
                    </div>`;
        }).join('');
    }

    // =================================================================
    // QUẢN LÝ CƠ CHẾ GIÁ
    // =================================================================
    async function priceMechAPI(action, data = {}) {
        return await fetchAPI('api/handle_pricemech.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data })
        });
    }

    function renderPriceMechList(items) {
        const listElement = document.getElementById('pricemech-list');
        if (!listElement) return;
        listElement.innerHTML = !items || items.length === 0 ? '<li>Không có dữ liệu.</li>' : items.map(item => {
            const phanTram = item.PhanTramDieuChinh || '0.00';
            return `<li data-id="${item.CoCheGiaID}">
                <div class="item-content">
                    <span class="item-detail"><b>Mã:</b> <span class="data-MaCoChe">${item.MaCoChe}</span></span>
                    <span class="item-detail"><b>Tên:</b> <span class="data-TenCoChe">${item.TenCoChe}</span></span>
                    <span class="item-detail"><b>Điều chỉnh:</b> <span class="data-PhanTramDieuChinh">${phanTram}</span>%</span>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" title="Sửa"><i class="fa fa-pencil"></i></button>
                    <button class="btn-delete" title="Xóa"><i class="fa fa-trash"></i></button>
                </div>
            </li>`;
        }).join('');
    }

    async function loadPriceMechs() {
        try {
            const result = await priceMechAPI('get_all');
            renderPriceMechList(result.data);
        } catch (error) { /* Lỗi đã được xử lý trong fetchAPI */ }
    }


    // =================================================================
    // CẤU HÌNH VÀ VẬN HÀNH BẢNG TABULATOR
    // =================================================================
    function createColumnDefinitions() {
        return [
            {
                title: "Thao tác", minWidth: 200, frozen: true, headerSort: false, hozAlign: "center",
                formatter: (cell) => {
                    const isCompany = cell.getRow().getTreeParent() === false;
                    if (isCompany) {
                        return `<i class="fa-solid fa-eye action-icon icon-view" title="Xem chi tiết"></i>
                                <i class="fa-solid fa-pencil action-icon icon-edit" title="Sửa thông tin công ty"></i>
                                <i class="fa-solid fa-trash-can action-icon icon-delete" title="Xóa công ty"></i>
                                <i class="fa-solid fa-user-plus action-icon icon-add-contact" title="Thêm người liên hệ"></i>
                                <i class="fa-solid fa-plus action-icon icon-add-comment" title="Lịch sử làm việc"></i>`;
                    } else {
                        return `<i class="fa-solid fa-pencil action-icon icon-edit" title="Sửa người liên hệ"></i>
                                <i class="fa-solid fa-trash-can action-icon icon-delete" title="Xóa người liên hệ"></i>`;
                    }
                },
                cellClick: async (e, cell) => {
                    const data = cell.getRow().getData();
                    const isCompany = cell.getRow().getTreeParent() === false;
                    const target = e.target.closest('.action-icon');
                    if (!target) return;

                    if (target.classList.contains('icon-view')) {
                        showCompanyDetailsPopup(data);
                    } else if (target.classList.contains('icon-edit')) {
                        if (isCompany) showCompanyForm(data); else showContactForm(data);
                    } else if (target.classList.contains('icon-delete')) {
                        const entity = isCompany ? 'công ty' : 'người liên hệ';
                        const name = data.TenCongTy || data.HoTen;

                        if (!isCompany) {
                            if (confirm(`Bạn có chắc muốn xóa người liên hệ "${name}"?`)) {
                                try {
                                    await fetchAPI('api/delete_contact.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: data.NguoiLienHeID }) });
                                    showToast('Xóa người liên hệ thành công!', 'success');
                                    await reloadAndFilterData();
                                } catch (error) { /* Lỗi đã được xử lý */ }
                            }
                            return;
                        }
                        
                        if (isCompany) {
                            try {
                                const usageCheck = await fetchAPI('api/check_company_usage.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: data.CongTyID }) });
                                if (usageCheck.in_use) {
                                    alert(`Không thể xóa công ty "${name}".\nLý do: Công ty này đã tồn tại trong một hoặc nhiều báo giá.`);
                                    return;
                                }
                                if (confirm(`Bạn có chắc muốn xóa công ty "${name}"? Thao tác này không thể hoàn tác.`)) {
                                    await fetchAPI('api/delete_company.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: data.CongTyID }) });
                                    showToast('Xóa công ty thành công!', 'success');
                                    await reloadAndFilterData();
                                }
                            } catch (error) {
                                showToast('Đã có lỗi xảy ra khi kiểm tra hoặc xóa công ty.', 'error');
                            }
                        }
                    } else if (target.classList.contains('icon-add-contact')) {
                        showContactForm({}, data.CongTyID);
                    } else if (target.classList.contains('icon-add-comment')) {
                        const modal = document.getElementById('customer-comment-modal');
                        document.getElementById('customer-comment-modal-title').innerHTML = `Lịch sử làm việc: <strong>${data.TenCongTy}</strong>`;
                        document.getElementById('customer-comment-company-id').value = data.CongTyID;
                        document.getElementById('customer-comment-user-name').value = currentUserName;
                        document.getElementById('customer-comment-content').value = '';
                        modal.style.display = 'flex';
                        await loadComments(data.CongTyID);
                    }
                }
            },
            { title: "Mã Cty", field: "MaCongTy", width: 120, formatter: (cell) => cell.getRow().getTreeParent() === false ? `<strong class="text-indigo-600">${cell.getValue()}</strong>` : '' },
            { title: "Tên Công Ty", field: "TenCongTy", minWidth: 250, formatter: (cell) => cell.getRow().getTreeParent() === false ? `<strong style='color: #0056b3;'>${cell.getValue()}</strong>` : "" },
            {
                title: "Liên Hệ", field: "contactsToggle", width: 160, headerSort: false, hozAlign: "left",
                formatter: (cell) => {
                    const row = cell.getRow();
                    const data = row.getData();
                    const isCompany = row.getTreeParent() === false;
                    
                    if (isCompany) {
                        const childrenCount = data._children ? data._children.length : 0;
                        if (childrenCount > 0) {
                            const isExpanded = row.isTreeExpanded();
                            const iconClass = isExpanded ? 'fa-chevron-down' : 'fa-chevron-right';
                            return `<span class="custom-tree-toggle" style="cursor: pointer; font-size: 0.9em; color: #007bff; padding: 4px 8px; border-radius: 4px; background-color: #e7f3ff; display: inline-block; border: 1px solid #b3d7ff;">
                                        <i class="fa ${iconClass}" style="margin-right: 5px;"></i> Xem ${childrenCount} liên hệ
                                    </span>`;
                        }
                        return `<span style="color: #999; font-size: 0.9em;">Không có</span>`;
                    }
                    return "";
                },
                cellClick: (e, cell) => {
                    const row = cell.getRow();
                    const data = row.getData();
                    const childrenCount = data._children ? data._children.length : 0;
                    if (childrenCount > 0) {
                        row.treeToggle();
                    }
                }
            },
            {
                title: "Nhóm KH", field: "NhomKhachHang", width: 120, hozAlign: "center",
                formatter: (cell) => {
                    if (cell.getRow().getTreeParent() !== false) return "";
                    const group = cell.getValue();
                    if (!group) return "";
                    let className = 'group-tiem-nang';
                    if (group === 'Đại Lý') className = 'group-dai-ly';
                    else if (group === 'Chiến lược') className = 'group-chien-luoc';
                    else if (group === 'Thân Thiết') className = 'group-than-thiet';
                    return `<span class="customer-group-badge ${className}">${group}</span>`;
                }
            },
            {
                title: "Số BG Chốt", field: "SoBaoGiaDaChot", width: 120, hozAlign: "center",
                formatter: (cell) => {
                    if (cell.getRow().getTreeParent() !== false) return "";
                    const value = cell.getValue() || 0;
                    const data = cell.getRow().getData();
                    const currentGroup = data.NhomKhachHang;
                    
                    let suggestedGroup = '';
                    if (value >= 10) suggestedGroup = 'Chiến lược';
                    else if (value >= 5) suggestedGroup = 'Thân Thiết';
                    else suggestedGroup = 'Tiềm năng';

                    let displayValue = `<strong style="font-size: 1.1em; color: #0056b3;">${value}</strong>`;

                    if (currentGroup !== 'Đại Lý') {
                        if (suggestedGroup === 'Chiến lược' && currentGroup !== 'Chiến lược') {
                             displayValue += ` <i class="fa fa-arrow-up" style="color: var(--success-color);" title="Gợi ý nâng cấp lên: Chiến lược"></i>`;
                        } else if (suggestedGroup === 'Thân Thiết' && currentGroup === 'Tiềm năng') {
                             displayValue += ` <i class="fa fa-arrow-up" style="color: var(--success-color);" title="Gợi ý nâng cấp lên: Thân Thiết"></i>`;
                        }
                    }
                    return displayValue;
                }
            },
            { title: "Người Liên Hệ", field: "HoTen", width: 180 },
            { title: "Số Di Động", field: "SoDiDong", width: 150 },
            {
                title: "Website", field: "Website", width: 200, hozAlign: "left",
                formatter: (cell) => {
                    if (cell.getRow().getTreeParent() !== false) return "";
                    const url = cell.getValue();
                    if (!url) return "<span style='color:#999;'>--Chưa có--</span>";
                    let fullUrl = url;
                    if (!/^https?:\/\//i.test(url)) fullUrl = 'https://' + url;
                    return `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer" class="table-link">${url}</a>`;
                }
            },
            {
                title: "Cơ Chế Giá", field: "TenCoChe", width: 180,
                formatter: (cell) => {
                    const data = cell.getRow().getData();
                    if (cell.getRow().getTreeParent() !== false) return "";
                    if (!data.TenCoChe) return "<span style='color:#999;'>--Chưa chọn--</span>";
                    const percent = data.PhanTramDieuChinh || 0;
                    const color = parseFloat(percent) < 0 ? 'var(--danger-color)' : 'var(--success-color)';
                    return `${data.TenCoChe} <span style='color: ${color}; font-weight: bold;'>(${percent}%)</span>`;
                }
            },
            // ===== BẮT ĐẦU CỘT MỚI =====
            { 
                title: "Số Ngày TT", 
                field: "SoNgayThanhToan", 
                width: 120, 
                hozAlign: "center",
                tooltip: "Số ngày thanh toán", 
                formatter: (cell) => {
                    if (cell.getRow().getTreeParent() !== false) return "";
                    const value = cell.getValue();
                    return `<span style="font-weight: 500; color: var(--primary-color);">${value || '30'}</span>`;
                }
            },
            // ===== KẾT THÚC CỘT MỚI =====
            { 
                title: "Địa Chỉ", 
                field: "DiaChi", 
                width: 300, 
                tooltip: (cell) => cell.getValue()
            },
        ];
    }

    // =================================================================
    // LỌC VÀ TẢI LẠI DỮ LIỆU
    // =================================================================
    function applyTableFilters() {
        if (!originalTableData) return;

        const activeTab = document.querySelector('.tab-link.active');
        const activeGroup = activeTab ? activeTab.dataset.group : 'Tất cả';

        let dataToShow = originalTableData;
        if (activeGroup !== 'Tất cả') {
            dataToShow = originalTableData.filter(company => company.NhomKhachHang === activeGroup);
        }

        const value = document.getElementById("filter-value-input").value.trim();
        if (value) {
            const lowerCaseValue = value.toLowerCase();
            const filterFields = ["MaCongTy", "TenCongTy", "HoTen", "Email", "DiaChi", "MaSoThue", "Website"];

            dataToShow = dataToShow.filter(company => {
                let companyMatch = filterFields.some(f => 
                    company[f] && String(company[f]).toLowerCase().includes(lowerCaseValue)
                );
                if (companyMatch) return true;

                if (company._children) {
                    return company._children.some(contact => 
                        filterFields.some(f => 
                            contact[f] && String(contact[f]).toLowerCase().includes(lowerCaseValue)
                        )
                    );
                }
                return false;
            });
        }

        if (table) {
            table.setData(dataToShow);
        }
    }
    
    async function reloadAndFilterData() {
        try {
            const result = await fetchAPI("api/get_customers_tree.php");
            originalTableData = result.data;
            applyTableFilters();
        } catch (error) { 
            if(table) table.setPlaceholder("Lỗi tải dữ liệu.");
        }
    }

    // =================================================================
    // GÁN SỰ KIỆN VÀ KHỞI TẠO
    // =================================================================
    function setupEventListeners() {
        document.getElementById('add-company-btn').onclick = () => showCompanyForm();
        
        document.getElementById('manage-pricemech-btn').onclick = () => {
            document.getElementById('pricemech-modal').style.display = "flex";
            loadPriceMechs();
        };

        const tabsContainer = document.querySelector('.customer-tabs');
        if (tabsContainer) {
            tabsContainer.addEventListener('click', (event) => {
                if (event.target.classList.contains('tab-link')) {
                    document.querySelectorAll('.tab-link').forEach(tab => tab.classList.remove('active'));
                    event.target.classList.add('active');
                    applyTableFilters();
                }
            });
        }

        let filterTimeout;
        const triggerFilter = () => { clearTimeout(filterTimeout); filterTimeout = setTimeout(applyTableFilters, 300); };
        document.getElementById('filter-value-input').addEventListener('keyup', triggerFilter);
        document.getElementById('clear-filter-btn').addEventListener('click', () => {
            document.getElementById('filter-value-input').value = "";
            applyTableFilters();
        });

        globalClickHandler = function (event) {
            const target = event.target;

            if (target.classList.contains('close-btn') || target.classList.contains('modal')) {
                const closestModal = target.closest('.modal');
                if(closestModal) closestModal.style.display = 'none';
            }

            const btnEdit = target.closest('.btn-edit');
            const btnCancel = target.closest('.btn-cancel');
            const btnSaveEdit = target.closest('.btn-save-edit');
            const btnDelete = target.closest('.btn-delete');
            if (!btnEdit && !btnCancel && !btnSaveEdit && !btnDelete) return;

            const li = target.closest('li');
            if (!li) return;

            if (btnEdit) {
                const ma = li.querySelector('.data-MaCoChe').textContent;
                const ten = li.querySelector('.data-TenCoChe').textContent;
                const phanTram = parseFloat(li.querySelector('.data-PhanTramDieuChinh').textContent) || 0;
                li.innerHTML = `<div class="item-content">
                        <input type="text" class="edit-MaCoChe" value="${ma}" placeholder="Mã" style="width: 80px;">
                        <input type="text" class="edit-TenCoChe" value="${ten}" placeholder="Tên" style="flex-grow: 1;">
                        <input type="number" step="0.01" class="edit-PhanTramDieuChinh" value="${phanTram}" placeholder="%" style="width: 80px;">
                    </div><div class="item-actions">
                        <button class="btn-save-edit" title="Lưu"><i class="fa fa-check"></i></button>
                        <button class="btn-cancel" title="Hủy"><i class="fa fa-times"></i></button>
                    </div>`;
            } else if (btnCancel) { 
                loadPriceMechs(); 
            } else if (btnSaveEdit) {
                const data = { id: li.dataset.id, MaCoChe: li.querySelector('.edit-MaCoChe').value, TenCoChe: li.querySelector('.edit-TenCoChe').value, PhanTramDieuChinh: li.querySelector('.edit-PhanTramDieuChinh').value };
                priceMechAPI('update', data).then(async () => {
                    await loadPriceMechs();
                    const result = await fetchAPI('api/get_customer_options.php');
                    coCheGiaOptions = result.data;
                    await reloadAndFilterData();
                });
            } else if (btnDelete) {
                 if (confirm('Bạn có chắc muốn xóa cơ chế giá này?')) {
                     priceMechAPI('delete', { id: li.dataset.id }).then(async () => {
                         await loadPriceMechs();
                         const result = await fetchAPI('api/get_customer_options.php');
                         coCheGiaOptions = result.data;
                         await reloadAndFilterData();
                     });
                 }
            }
        };

        globalSubmitHandler = async function (event) {
            event.preventDefault();
            const form = event.target;
            const data = Object.fromEntries(new FormData(form).entries());

            if (form.id === 'company-form' || form.id === 'contact-form') {
                const isCompany = form.id === 'company-form';
                const url = isCompany ? (data.CongTyID ? 'api/update_company.php' : 'api/add_company.php') : (data.NguoiLienHeID ? 'api/update_contact.php' : 'api/add_contact.php');
                try {
                    await fetchAPI(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
                    showToast('Lưu thông tin thành công!', 'success');
                    closeModal(isCompany ? 'company-modal' : 'contact-modal');
                    await reloadAndFilterData();
                } catch (error) { /* Lỗi đã được xử lý */ }
            } 
            else if (form.id === 'customer-comment-form') {
                const commentData = {
                    CongTyID: document.getElementById('customer-comment-company-id').value,
                    NguoiBinhLuan: document.getElementById('customer-comment-user-name').value,
                    NoiDung: document.getElementById('customer-comment-content').value
                };
                try {
                    await fetchAPI('api/customer_comment_actions.php?action=add_comment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(commentData)
                    });
                    showToast('Lưu lịch sử thành công!', 'success');
                    document.getElementById('customer-comment-content').value = '';
                    await loadComments(commentData.CongTyID);
                } catch (error) { /* Lỗi đã được xử lý */ }
            }
            else if (form.id === 'form-add-pricemech') {
                const addData = { MaCoChe: document.getElementById('add-MaCoChe').value, TenCoChe: document.getElementById('add-TenCoChe').value, PhanTramDieuChinh: document.getElementById('add-PhanTramDieuChinh').value };
                await priceMechAPI('add', addData);
                form.reset();
                await loadPriceMechs();
                const result = await fetchAPI('api/get_customer_options.php');
                coCheGiaOptions = result.data;
                await reloadAndFilterData();
            }
        };

        document.body.addEventListener('click', globalClickHandler);
        document.body.addEventListener('submit', globalSubmitHandler);

        document.getElementById('export-excel-btn').addEventListener('click', function() {
            const activeTab = document.querySelector('.tab-link.active');
            const groupFilter = activeTab ? activeTab.dataset.group : 'Tất cả';
            const searchValue = document.getElementById('filter-value-input').value;
            const url = `api/export_customer_list_excel.php?group=${encodeURIComponent(groupFilter)}&search=${encodeURIComponent(searchValue)}`;
            window.location.href = url;
        });
    }

    async function initialize() {
        const tableElement = document.getElementById('customer-table');
        if (!tableElement) {
            console.error('Lỗi nghiêm trọng: Không tìm thấy phần tử #customer-table. Bảng không thể được tạo.');
            return;
        }

        await loadCurrentUserInfo();

        try {
            const optionsResult = await fetchAPI('api/get_customer_options.php');
            coCheGiaOptions = optionsResult.data;
        } catch (error) {
            console.error("Không thể tải cơ chế giá:", error);
        }

        table = new Tabulator("#customer-table", {
            height: "75vh",
            layout: "fitColumns",
            placeholder: "Đang tải dữ liệu...",
            dataTree: true,
            dataTreeStartExpanded: false,
            dataTreeChildField: "_children",
            dataTreeExpandElement: "", 
            dataTreeCollapseElement: "", 
            columns: createColumnDefinitions(),
        });

        table.on("treeExpanded", function(row){
            const cellElement = row.getCell("contactsToggle").getElement();
            if(cellElement){
                const icon = cellElement.querySelector('.custom-tree-toggle i');
                if(icon){
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                }
            }
        });

        table.on("treeCollapsed", function(row){
            const cellElement = row.getCell("contactsToggle").getElement();
            if(cellElement){
                const icon = cellElement.querySelector('.custom-tree-toggle i');
                if(icon){
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            }
        });
        
        document.getElementById('pricemech-modal').innerHTML = `<div class="modal-content">
                <div class="modal-header"><h2><i class="fa fa-dollar-sign"></i> Quản Lý Cơ Chế Giá</h2><span class="close-btn">&times;</span></div>
                <div class="manager-container">
                    <form id="form-add-pricemech" class="add-form">
                        <div class="form-group"><label>Mã Cơ Chế</label><input type="text" id="add-MaCoChe" required></div>
                        <div class="form-group"><label>Tên Cơ Chế</label><input type="text" id="add-TenCoChe" required></div>
                        <div class="form-group"><label>% Điều Chỉnh</label><input type="number" step="0.01" id="add-PhanTramDieuChinh" required></div>
                        <button type="submit"><i class="fa fa-plus"></i> Thêm</button>
                    </form>
                    <ul id="pricemech-list" class="manager-list"></ul>
                </div>
            </div>`;

        await reloadAndFilterData();
        setupEventListeners();
    }

    initialize();
}