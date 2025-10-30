<!-- ========================================
     FILE: pages/bao_cao_doanh_thu.php
     ======================================== -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Doanh Thu</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --info-color: #17a2b8;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--success-color);
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .summary-card .amount {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: var(--success-color);
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
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
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
            opacity: 0;
        }
        #toast.show { visibility: visible; opacity: 1; }
        #toast.success { background-color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fa fa-chart-line"></i> Báo Cáo Doanh Thu</h1>

        <div class="summary-cards">
            <div class="summary-card">
                <h3><i class="fa fa-dollar-sign"></i> Doanh Thu Tháng Này</h3>
                <p class="amount" id="doanh-thu-thang">0 ₫</p>
            </div>
            <div class="summary-card">
                <h3><i class="fa fa-calendar"></i> Doanh Thu Năm Nay</h3>
                <p class="amount" id="doanh-thu-nam">0 ₫</p>
            </div>
            <div class="summary-card">
                <h3><i class="fa fa-chart-bar"></i> Trung Bình/Tháng</h3>
                <p class="amount" id="trung-binh-thang">0 ₫</p>
            </div>
            <div class="summary-card">
                <h3><i class="fa fa-trophy"></i> Tháng Cao Nhất</h3>
                <p class="amount" id="thang-cao-nhat">0 ₫</p>
            </div>
        </div>

        <div class="controls-container">
            <div class="filter-controls">
                <select id="filter-year">
                    <option value="2025">Năm 2025</option>
                    <option value="2024">Năm 2024</option>
                    <option value="2023">Năm 2023</option>
                </select>
                <select id="filter-period">
                    <option value="monthly">Theo Tháng</option>
                    <option value="quarterly">Theo Quý</option>
                    <option value="yearly">Theo Năm</option>
                </select>
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

        <div class="chart-container">
            <h3>Biểu Đồ Doanh Thu</h3>
            <div class="chart-wrapper">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>
    </div>

    <div id="toast"></div>
</body>
</html>

