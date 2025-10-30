<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Cấu Hình Sản Xuất</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: sans-serif; margin: 0; }
        .config-page-container { padding: 24px; background-color: #f9fafb; }
        .config-card { background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .config-header { padding: 24px; border-bottom: 1px solid #e5e7eb; }
        .config-header h1 { font-size: 1.875rem; font-weight: 700; color: #111827; display: flex; align-items: center; }
        .config-header h1 i { margin-right: 12px; color: #10b981; }
        .config-body { padding: 24px; }
        .config-form-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
        @media (min-width: 1024px) { .config-form-grid { grid-template-columns: 1fr 1fr; } } /* 2 cột trên màn hình lớn */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .form-group .description { font-size: 0.875rem; color: #6b7280; margin-bottom: 8px; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"] { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        .config-footer { padding: 16px 24px; background-color: #f9fafb; border-top: 1px solid #e5e7eb; text-align: right; border-radius: 0 0 8px 8px; }
        .save-btn { padding: 10px 24px; background-color: #10b981; color: white; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; transition: background-color 0.2s; }
        .save-btn:hover { background-color: #059669; }
        .mr-2 { margin-right: 0.5rem; }

        /* CSS CHO DANH SÁCH NGÀY NGHỈ */
        .holiday-manager .date-list { list-style: none; padding: 0; margin-top: 12px; max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
        .holiday-manager .date-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .holiday-manager .date-item:last-child { border-bottom: none; }
        .holiday-manager .delete-date-btn { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.25rem; }
        .holiday-manager .add-date-form { display: flex; gap: 8px; margin-top: 12px; }
        .holiday-manager .add-date-form input[type="date"] { flex-grow: 1; }
        .holiday-manager .add-date-btn { padding: 0 16px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; }
        .holiday-manager .add-date-btn:hover { background-color: #2563eb; }

        /* CSS ĐÃ BỔ SUNG CHO KEY-VALUE EDITOR */
        .kv-manager .kv-list { list-style: none; padding: 0; margin-top: 12px; max-height: 250px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
        .kv-manager .kv-item { display: flex; gap: 8px; align-items: center; padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        .kv-manager .kv-item:last-child { border-bottom: none; }
        .kv-manager .kv-key, .kv-manager .kv-value { flex: 1; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; }
        .kv-manager .delete-kv-btn { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.25rem; padding: 0 4px; }
        .kv-manager .add-kv-btn { margin-top: 12px; padding: 8px 16px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 500; cursor: pointer; }
        .kv-manager .add-kv-btn:hover { background-color: #2563eb; }
        
        /* CSS cho Modal thông báo */
        .app-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 400px; border-radius: 8px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-content h2 { margin-top: 0; }
        .modal-close-btn { margin-top: 15px; padding: 10px 20px; border-radius: 5px; cursor: pointer; border: 1px solid #ccc; background-color: #f0f0f0; }
    </style>
</head>
<body>

    <div class="config-page-container">
        <form id="config-form" class="config-card">
            <div class="config-header">
                <h1><i class="fas fa-cogs"></i>Quản Lý Cấu Hình Sản Xuất</h1>
            </div>
            <div class="config-body">
                <div id="config-form-grid" class="config-form-grid">
                    <p>Đang tải dữ liệu cấu hình...</p>
                </div>
            </div>
            <div class="config-footer">
                <button type="submit" class="save-btn">
                    <i class="fas fa-save mr-2"></i>Lưu Thay Đổi
                </button>
            </div>
        </form>
    </div>

    <div id="app-modal" class="app-modal">
      <div class="modal-content">
        <h2 id="modal-title"></h2>
        <p id="modal-message"></p>
        <button id="modal-close-btn" class="modal-close-btn">Đóng</button>
      </div>
    </div>



</body>
</html>