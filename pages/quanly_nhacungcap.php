<link href="https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">

<style>
    /* Container chính của trang */
    .supplier-page-container {
        padding: 24px;
        background-color: #f9fafb; /* Màu nền xám rất nhạt */
    }

    /* Card chứa nội dung */
    .content-card {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 24px;
    }

    /* Phần header của card (tiêu đề và nút) */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 16px;
    }
    
    .card-header h1 {
        font-size: 1.875rem; /* 30px */
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
    }

    .card-header h1 .header-icon {
        margin-right: 12px;
        color: #3b82f6; /* Màu xanh dương */
    }

    /* Nút Thêm Mới */
    .add-new-btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 16px;
        background-color: #3b82f6;
        color: white;
        font-weight: 600;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .add-new-btn:hover {
        background-color: #2563eb;
    }

    .add-new-btn i {
        margin-right: 8px;
    }

    /* CSS cho bảng Tabulator để trông đẹp hơn */
    #supplier-table {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden; /* Giúp bo góc cho bảng */
    }

    /* Vùng chứa modal */
    #supplier-form-modal-placeholder {
        /* CSS cho modal sẽ được sinh ra bởi JavaScript */
    }
</style>

<div class="supplier-page-container">
    <div class="content-card">
        <div class="card-header">
            <h1>
                <i class="fas fa-truck-field header-icon"></i>
                Quản Lý Nhà Cung Cấp
            </h1>
            <button id="add-supplier-btn" class="add-new-btn">
                <i class="fas fa-plus"></i>Thêm Nhà Cung Cấp
            </button>
        </div>

        <div id="supplier-table"></div>
    </div>
    
    <div id="supplier-form-modal-placeholder"></div>
</div>