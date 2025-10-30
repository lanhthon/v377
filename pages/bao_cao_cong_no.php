<!-- ========================================
     FILE: pages/bao_cao_cong_no.php
     ======================================== -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Công Nợ</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        .summary-card.danger { border-left-color: var(--danger-color); }
        .summary-card.warning { border-left-color: var(--warning-color); }
        .summary-card.success { border-left-color: var(--success-color); }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .summary-card .amount {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
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
        .filter-controls select, .filter-controls input {
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
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.qua-han { background-color: #dc3545; color: #fff; }
        .badge.sap-het-han { background-color: #ffc107; color: #000; }
        .badge.da-thanh-toan { background-color: #28a745; color: #fff; }
        .badge.con-han { background-color: #17a2b8; color: #fff; }
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
        <h1><i class="fa fa-chart-bar"></i> Báo Cáo Công Nợ Khách Hàng</h1>

        <div class="summary-cards">
            <div class="summary-card danger">
                <h3><i class="fa fa-exclamation-triangle"></i> Tổng Công Nợ</h3>
                <p class="amount" id="tong-cong-no">0 ₫</p>
            </div>
            <div class="summary-card warning">
                <h3><i class="fa fa-clock"></i> Quá Hạn</h3>
                <p class="amount" id="tong-qua-han">0 ₫</p>
            </div>
            <div class="summary-card success">
                <h3><i class="fa fa-check-circle"></i> Đã Thu</h3>
                <p class="amount" id="tong-da-thu">0 ₫</p>
            </div>
        </div>

        <div class="controls-container">
            <div class="filter-controls">
                <select id="filter-status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="chua_thanh_toan">Chưa thanh toán</option>
                    <option value="qua_han">Quá hạn</option>
                    <option value="da_thanh_toan">Đã thanh toán</option>
                </select>
                <input id="filter-search" type="text" placeholder="Tìm theo tên công ty...">
            </div>
            <div class="action-buttons">
                <button id="refresh-btn" class="action-button" style="background-color: var(--primary-color);">
                    <i class="fa fa-sync"></i> Làm mới
                </button>
                <button id="export-btn" class="action-button" style="background-color: var(--success-color);">
                    <i class="fa fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>

        <div id="congno-table"></div>
    </div>

    <div id="toast"></div>

    <script src="https://unpkg.com/tabulator-tables@5.6.0/dist/js/tabulator.min.js"></script>
</body>
</html>

