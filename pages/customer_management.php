<link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    :root {
        --primary-color: #007bff;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --info-color: #17a2b8;
        --light-gray: #f8f9fa;
        --medium-gray: #dee2e6;
        --dark-gray: #343a40;
        --text-color: #495057;
        --border-radius: 8px;
    }

    .container {
        max-width: 98%;
        margin: auto;
    }

    .controls-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 8px 12px;
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        margin-left: 8px;
    }

    #add-company-btn {
        background-color: var(--success-color);
    }

    #manage-pricemech-btn {
        background-color: var(--info-color);
    }

    .filter-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-controls input,
    .filter-controls select {
        padding: 6px 10px;
        border: 1px solid var(--medium-gray);
        border-radius: 4px;
        font-size: 13px;
    }

    .filter-controls input {
        min-width: 250px;
    }

    .tabulator {
        font-size: 13px;
    }

    .tabulator-row {
        min-height: 30px;
        height: auto; /* Allow row height to adjust */
    }

    .tabulator-cell {
        padding: 8px; /* Increase padding for better spacing */
    }

    .tabulator-row.tabulator-tree-level-1 {
        background-color: #f8f9fa !important;
    }

    .tabulator-cell .action-icon {
        cursor: pointer;
        margin: 0 5px; /* Increase margin */
        font-size: 15px; /* Slightly larger icons */
        transition: transform 0.2s, color 0.2s;
    }
    .icon-view { color: var(--info-color); }
    .icon-add-contact { color: var(--success-color); }
    .icon-edit { color: var(--primary-color); }
    .icon-delete { color: var(--danger-color); }
    .icon-add-comment { color: #6c757d; }

    .tabulator-cell .table-link {
        color: var(--success-color);
        font-weight: 500;
        text-decoration: none;
    }
    .tabulator-cell .table-link:hover {
        text-decoration: underline;
    }


    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border: none;
        width: 90%;
        max-width: 700px;
        border-radius: var(--border-radius);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .modal-content.large {
        max-width: 900px; /* Wider modal for details */
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--medium-gray);
        padding-bottom: 12px;
        margin-bottom: 15px;
    }

    .close-btn {
        color: #6c757d;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 13px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid var(--medium-gray);
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 13px;
    }

    .modal-footer {
        border-top: 1px solid var(--medium-gray);
        margin-top: 20px;
        padding-top: 15px;
        text-align: right;
    }
    
    /* Styles for Details Popup */
    .details-popup-content .details-section {
        margin-bottom: 25px;
    }
    .details-popup-content h3 {
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 5px;
        margin-bottom: 15px;
        color: var(--primary-color);
    }
    .details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 20px;
        font-size: 14px;
    }
    .details-grid .full-span {
        grid-column: 1 / -1;
    }
    .contact-details-list {
        list-style: none;
        padding: 0;
    }
    .contact-details-list li {
        background-color: var(--light-gray);
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 8px;
        border-left: 4px solid var(--info-color);
    }
    .contact-details-list .contact-info {
        font-size: 0.9em;
        color: #6c757d;
        margin-top: 4px;
    }

    /* THÊM MỚI: Styles for comment list inside details popup */
    .details-popup-content .comment-list-container {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid var(--medium-gray);
        padding: 15px;
        border-radius: var(--border-radius);
        background-color: #fdfdfd;
    }
    .details-popup-content .comment-item {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    .details-popup-content .comment-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .details-popup-content .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.9em;
    }
    .details-popup-content .comment-author {
        font-weight: 600;
        color: var(--primary-color);
    }
    .details-popup-content .comment-date {
        color: #6c757d;
        font-style: italic;
    }
    .details-popup-content .comment-body {
        margin: 0;
        font-size: 0.95em;
        white-space: pre-wrap;
        word-wrap: break-word;
    }


    #toast {
        visibility: hidden;
        min-width: 250px;
        margin-left: -125px;
        background-color: var(--dark-gray);
        color: #fff;
        text-align: center;
        border-radius: 4px;
        padding: 12px;
        position: fixed;
        z-index: 2000;
        left: 50%;
        bottom: 25px;
        transition: all 0.5s ease;
        opacity: 0;
    }

    #toast.show {
        visibility: visible;
        opacity: 1;
    }

    #toast.success {
        background-color: var(--success-color);
    }

    #toast.error {
        background-color: var(--danger-color);
    }

    .manager-container { padding: 8px; }
    .add-form {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
        align-items: flex-end;
    }
    .add-form .form-group { flex-grow: 1; margin-bottom: 0; }
    .add-form button {
        padding: 6px 12px;
        height: 32px;
        color: white;
        background-color: var(--success-color);
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 13px;
    }
    .manager-list {
        list-style-type: none;
        padding: 0;
        max-height: 40vh;
        overflow-y: auto;
    }
    .manager-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    .manager-list li:nth-child(even) { background-color: #f9f9f9; }
    .manager-list li:hover { background-color: #f1f3f5; }
    .item-content { flex-grow: 1; display: flex; align-items: center; gap: 12px; }
    .item-actions button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        font-size: 14px;
        margin-left: 6px;
    }
    .btn-edit { color: var(--primary-color); }
    .btn-delete { color: var(--danger-color); }
    .btn-save-edit { color: var(--success-color); }
    .btn-cancel { color: var(--dark-gray); }
    .customer-tabs {
        display: flex;
        border-bottom: 1px solid var(--medium-gray);
        margin-bottom: 15px;
    }
    .tab-link {
        background-color: transparent;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-color);
        margin-bottom: -1px;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease-in-out;
    }
    .tab-link:hover { color: var(--primary-color); }
    .tab-link.active {
        color: var(--primary-color);
        font-weight: 600;
        border-bottom-color: var(--primary-color);
    }
    .filter-controls input { min-width: 350px; }
</style>
<div class="container">
    <h1><i class="fa fa-users"></i> Trang Quản Lý Khách Hàng</h1>

    <div class="customer-tabs">
        <button class="tab-link active" data-group="Tất cả">Tất cả</button>
        <button class="tab-link" data-group="Đại Lý">Đại Lý</button>
        <button class="tab-link" data-group="Chiến lược">Chiến lược</button>
        <button class="tab-link" data-group="Thân Thiết">Thân Thiết</button>
        <button class="tab-link" data-group="Tiềm năng">Tiềm năng</button>
    </div>

    <div class="controls-container">
        <div class="filter-controls">
            <input id="filter-value-input" type="text" placeholder="Tìm kiếm theo mã, tên công ty, liên hệ, email...">
            <button id="clear-filter-btn" class="action-button" style="background-color: var(--dark-gray); margin-left: 0;">
                <i class="fa fa-times"></i> Xóa Lọc
            </button>
        </div>
        
        <div class="action-buttons">
            <button id="export-excel-btn" class="action-button" style="background-color: #1D6F42;"><i class="fa fa-file-excel"></i> Xuất Excel</button>
            <button id="manage-pricemech-btn" class="action-button"><i class="fa fa-dollar-sign"></i> Quản lý Cơ chế giá</button>
            <button id="add-company-btn" class="action-button"><i class="fa fa-plus"></i> Thêm Công Ty</button>
        </div>
    </div>
    <div id="customer-table"></div>
</div>

<!-- Placeholders for Modals -->
<div id="company-modal" class="modal"></div>
<div id="contact-modal" class="modal"></div>
<div id="pricemech-modal" class="modal"></div>
<div id="details-modal" class="modal"></div>
<div id="toast"></div>

<!-- Customer Comment Modal -->
<div id="customer-comment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="customer-comment-modal-title">Lịch sử làm việc</h2>
            <span class="close-btn">&times;</span>
        </div>
        
        <div id="customer-comment-list" class="comment-list-container">
            <p class="text-center text-gray-500">Đang tải lịch sử...</p>
        </div>

        <form id="customer-comment-form">
            <input type="hidden" id="customer-comment-company-id">
            <div class="form-grid" style="grid-template-columns: 1fr;">
                <div class="form-group">
                    <label>Tên của bạn</label>
                    <input type="text" id="customer-comment-user-name" readonly>
                </div>
                <div class="form-group">
                    <label>Nội dung (*)</label>
                    <textarea id="customer-comment-content" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="action-button" style="background-color: var(--primary-color);">Lưu</button>
            </div>
        </form>
    </div>
</div>
