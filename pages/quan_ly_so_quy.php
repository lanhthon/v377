<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sổ Quỹ</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --light-gray: #f8f9fa;
            --medium-gray: #dee2e6;
            --dark-gray: #343a40;
            --border-radius: 8px;
        }

        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-controls input,
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid var(--medium-gray);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        #add-thu-btn { background-color: var(--success-color); }
        #add-chi-btn { background-color: var(--danger-color); }
        #export-btn { background-color: var(--info-color); }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }

        .summary-card.thu { border-left-color: var(--success-color); }
        .summary-card.chi { border-left-color: var(--danger-color); }
        .summary-card.ton { border-left-color: var(--info-color); }
        .summary-card.loi-nhuan { border-left-color: var(--warning-color); }

        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }

        .summary-card .amount {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
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
            margin: 3% auto;
            padding: 25px;
            border: none;
            width: 90%;
            max-width: 700px;
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

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
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

        #toast.show { visibility: visible; opacity: 1; }
        #toast.success { background-color: var(--success-color); }
        #toast.error { background-color: var(--danger-color); }
        #toast.info { background-color: var(--info-color); }

        .tabulator-cell.positive { color: var(--success-color); font-weight: bold; }
        .tabulator-cell.negative { color: var(--danger-color); font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa fa-book"></i> Quản Lý Sổ Quỹ Tiền Mặt</h1>

        <!-- Tổng quan -->
        <div class="summary-cards">
            <div class="summary-card thu">
                <h3><i class="fa fa-arrow-down"></i> Tổng Thu</h3>
                <p class="amount" id="total-thu">0 ₫</p>
            </div>
            <div class="summary-card chi">
                <h3><i class="fa fa-arrow-up"></i> Tổng Chi</h3>
                <p class="amount" id="total-chi">0 ₫</p>
            </div>
            <div class="summary-card ton">
                <h3><i class="fa fa-wallet"></i> Tồn Quỹ</h3>
                <p class="amount" id="ton-quy">0 ₫</p>
            </div>
            <div class="summary-card loi-nhuan">
                <h3><i class="fa fa-chart-line"></i> Lợi Nhuận</h3>
                <p class="amount" id="loi-nhuan">0 ₫</p>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls-container">
            <div class="filter-controls">
                <input id="filter-date-from" type="date" placeholder="Từ ngày">
                <input id="filter-date-to" type="date" placeholder="Đến ngày">
                <select id="filter-loai">
                    <option value="">Tất cả</option>
                    <option value="thu">Phiếu Thu</option>
                    <option value="chi">Phiếu Chi</option>
                </select>
                <input id="filter-search" type="text" placeholder="Tìm theo nội dung...">
            </div>
            <div class="action-buttons">
                <button id="add-thu-btn" class="action-button"><i class="fa fa-plus"></i> Phiếu Thu</button>
                <button id="add-chi-btn" class="action-button"><i class="fa fa-minus"></i> Phiếu Chi</button>
                <button id="export-btn" class="action-button"><i class="fa fa-file-excel"></i> Xuất Excel</button>
            </div>
        </div>

        <!-- Table -->
        <div id="so-quy-table"></div>
    </div>

    <!-- Modal -->
    <div id="so-quy-modal" class="modal"></div>
    <div id="toast"></div>

    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.6.0/dist/js/tabulator.min.js"></script>
</body>
</html>