<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Định Mức Cắt</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* CSS không thay đổi */
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
        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
            margin-left: 10px;
        }
        #add-btn {
            background-color: var(--success-color);
        }
        /* Thêm style cho nút export */
        #export-excel-btn {
            background-color: var(--primary-color);
        }
        .tabulator-cell .action-icon {
            cursor: pointer;
            margin: 0 8px;
            font-size: 16px;
            transition: transform 0.2s, color 0.2s;
        }
        .icon-delete {
            color: var(--danger-color);
        }
        .icon-save {
            color: var(--primary-color);
        }
        .icon-delete:hover {
            color: #a71d2a;
            transform: scale(1.2);
        }
        .icon-save:hover {
            color: #0056b3;
            transform: scale(1.2);
        }
        .filter-controls input {
            padding: 8px 12px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            min-width: 350px;
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
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border: none;
            width: 90%;
            max-width: 600px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--medium-gray);
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
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            box-sizing: border-box;
        }
        .modal-footer {
            border-top: 1px solid var(--medium-gray);
            margin-top: 25px;
            padding-top: 20px;
            text-align: right;
        }
        #toast {
            visibility: hidden;
            min-width: 280px;
            margin-left: -140px;
            background-color: var(--dark-gray);
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
    </style>
</head>

<body>
    <div class="container">
        <h1><i class="fa fa-ruler-combined"></i> Trang Quản Lý Định Mức Cắt</h1>
        <div class="controls-container">
            <div class="filter-controls">
                <input id="filter-field" type="text" placeholder="Tìm theo Tên nhóm DN...">
            </div>
            <div class="action-buttons">
                <button id="export-excel-btn" class="action-button"><i class="fa fa-file-excel"></i> Xuất Excel</button>
                <button id="add-btn" class="action-button"><i class="fa fa-plus"></i> Thêm mới</button>
            </div>
        </div>
        <div id="dinh-muc-table"></div>
    </div>

    <div id="dinh-muc-modal" class="modal"></div>
    <div id="toast"></div>

    <script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>

    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.6.0/dist/js/tabulator.min.js"></script>
    </body>
</html>