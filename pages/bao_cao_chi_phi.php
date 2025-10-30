<!-- ========================================
     FILE: pages/bao_cao_chi_phi.php
     ======================================== -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Chi Phí</title>
    <link href="https://unpkg.com/tabulator-tables@5.6.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .summary-card.danger { border-left-color: var(--danger-color); }
        .summary-card.warning { border-left-color: var(--warning-color); }
        .summary-card.success { border-left-color: var(--success-color); }
        .summary-card.info { border-left-color: var(--info-color); }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .summary-card .amount {
            font-size: 26px;
            font-weight: bold;
            margin: 0;
            color: #2c3e50;
        }
        
        .content-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .chart-container h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 18px;
        }
        
        #expense-chart {
            height: 300px;
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
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-controls select,
        .filter-controls input {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-controls input[type="date"] {
            min-width: 140px;
        }
        
        .filter-controls input[type="text"] {
            min-width: 200px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
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
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .table-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge.cho_duyet { background-color: #ffc107; color: #000; }
        .badge.da_duyet { background-color: #28a745; color: #fff; }
        .badge.da_huy { background-color: #6c757d; color: #fff; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .close-btn {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close-btn:hover {
            opacity: 0.8;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
            text-align: right;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        
        .form-grid div {
            padding: 10px 0;
        }
        
        .form-grid strong {
            color: #495057;
            font-size: 14px;
        }
        
        /* Toast Notification */
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        #toast.show {
            visibility: visible;
            opacity: 1;
        }
        
        #toast.success { background-color: #28a745; }
        #toast.error { background-color: #dc3545; }
        #toast.info { background-color: #17a2b8; }
        #toast.warning { background-color: #ffc107; color: #000; }
        
        /* Tabulator Custom Styles */
        .tabulator .tabulator-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tabulator .tabulator-header .tabulator-col {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        
        .tabulator-row:hover {
            background-color: #f1f3f5 !important;
        }
        
        .tabulator .tabulator-footer {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content-row {
                grid-template-columns: 1fr;
            }
            
            .controls-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-controls,
            .action-buttons {
                width: 100%;
                justify-content: space-between;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fa fa-chart-pie"></i> Báo Cáo Chi Phí</h1>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card danger">
                <h3><i class="fa fa-money-bill-wave"></i> Tổng Chi Phí</h3>
                <p class="amount" id="total-expense">0 ₫</p>
            </div>
            <div class="summary-card info">
                <h3><i class="fa fa-file-invoice"></i> Số Phiếu Chi</h3>
                <p class="amount" id="total-records">0</p>
            </div>
            <div class="summary-card warning">
                <h3><i class="fa fa-tags"></i> Loại Chi Phí</h3>
                <p class="amount" id="total-categories">0</p>
            </div>
            <div class="summary-card success">
                <h3><i class="fa fa-chart-line"></i> Chi Phí Cao Nhất</h3>
                <p class="amount" id="highest-category" style="font-size: 18px;">N/A</p>
            </div>
        </div>

        <!-- Chart and Summary -->
        <div class="content-row">
            <div class="chart-container">
                <h3><i class="fa fa-pie-chart"></i> Phân Bổ Chi Phí Theo Loại</h3>
                <canvas id="expense-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3><i class="fa fa-list"></i> Chi Tiết Theo Loại</h3>
                <div id="summary-list" style="max-height: 300px; overflow-y: auto;">
                    <p style="text-align: center; color: #999; padding: 20px;">
                        Đang tải dữ liệu...
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters and Controls -->
        <div class="controls-container">
            <div class="filter-controls">
                <input id="filter-date-from" type="date" placeholder="Từ ngày">
                <input id="filter-date-to" type="date" placeholder="Đến ngày">
                <select id="filter-loai-chi-phi">
                    <option value="">Tất cả loại chi phí</option>
                </select>
                <select id="filter-status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="cho_duyet">Chờ duyệt</option>
                    <option value="da_duyet">Đã duyệt</option>
                    <option value="da_huy">Đã hủy</option>
                </select>
                <input id="filter-search" type="text" placeholder="Tìm theo đối tượng, lý do...">
            </div>
            <div class="action-buttons">
                <button id="export-summary-btn" class="action-button" style="background-color: var(--warning-color); color: #000;">
                    <i class="fa fa-file-excel"></i> Xuất Tổng Hợp
                </button>
                <button id="export-btn" class="action-button" style="background-color: var(--success-color);">
                    <i class="fa fa-download"></i> Xuất Chi Tiết
                </button>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <div id="chi-phi-table"></div>
        </div>
    </div>

    <!-- Modal for Details -->
    <div id="chi-phi-modal" class="modal"></div>

    <!-- Toast Notification -->
    <div id="toast"></div>

    <!-- Scripts -->
    <script src="https://unpkg.com/tabulator-tables@5.6.0/dist/js/tabulator.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
   
</body>
</html>