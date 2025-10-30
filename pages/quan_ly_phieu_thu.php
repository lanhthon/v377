<!-- ========================================
     FILE 1: pages/quan_ly_phieu_thu.php
     ======================================== -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Phiếu Thu</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .filter-controls { display: flex; gap: 10px; }
        .filter-controls input, .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.cho-duyet { background-color: #ffc107; color: #000; }
        .badge.da-duyet { background-color: #28a745; color: #fff; }
        .badge.da-huy { background-color: #dc3545; color: #fff; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 2% auto;
            padding: 25px;
            width: 90%;
            max-width: 900px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .close-btn {
            color: #6c757d;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .modal-footer {
            border-top: 1px solid #dee2e6;
            margin-top: 25px;
            padding-top: 20px;
            text-align: right;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        #toast {
            visibility: hidden;
            min-width: 280px;
            margin-left: -140px;
            background-color: #343a40;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 16px;
            position: fixed;
            z-index: 2000;
            left: 50%;
            bottom: 30px;
            transition: all 0.5s ease;
            opacity: 0;
        }
        #toast.show { visibility: visible; opacity: 1; }
        #toast.success { background-color: #28a745; }
        #toast.error { background-color: #dc3545; }
        #toast.info { background-color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa fa-receipt"></i> Quản Lý Phiếu Thu</h1>

        <div class="controls-container">
            <div class="filter-controls">
                <input id="filter-date-from" type="date">
                <input id="filter-date-to" type="date">
                <select id="filter-status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="cho_duyet">Chờ duyệt</option>
                    <option value="da_duyet">Đã duyệt</option>
                    <option value="da_huy">Đã hủy</option>
                </select>
                <input id="filter-search" type="text" placeholder="Tìm theo số phiếu, đối tượng...">
            </div>
            <div class="action-buttons">
                <button id="add-btn" class="action-button" style="background-color: var(--success-color);">
                    <i class="fa fa-plus"></i> Thêm Phiếu Thu
                </button>
                <button id="export-btn" class="action-button" style="background-color: var(--primary-color);">
                    <i class="fa fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>

        <div id="phieu-thu-table"></div>
    </div>

    <div id="phieu-thu-modal" class="modal"></div>
    <div id="toast"></div>

    <script src="https://unpkg.com/tabulator-tables@5.6.0/dist/js/tabulator.min.js"></script>
</body>
</html>

