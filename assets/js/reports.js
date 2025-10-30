function initializeReportsPage() {
    // =================================================================
    // KHAI BÁO BIẾN VÀ DOM ELEMENTS
    // =================================================================
    const customerSelect = $('#report-customer-select');
    const startDateInput = $('#report-start-date');
    const endDateInput = $('#report-end-date');
    const viewQuoteReportBtn = $('#view-quote-report-btn');
    const exportExcelBtn = $('#export-excel-btn');

    // Containers
    const dashboardContainer = $('#dashboard-stats-container');
    const quoteReportContainer = $('#quote-report-results-container');
    const productionReportContainer = $('#production-report-container');
    const inventoryReportContainer = $('#inventory-report-container');
    const customerReportContainer = $('#customer-report-container');
    const topProductsReportContainer = $('#top-products-report-container');

    // Chart variables
    const chartPlaceholder = $('#chart-placeholder');
    const chartCanvasWrapper = $('#chart-canvas-wrapper');
    let monthlyRevenueChart = null;
    let topCustomerChart = null;
    
    // Mục tiêu doanh thu năm (sẽ được lấy từ database) và biến cho biểu đồ mục tiêu
    let ANNUAL_REVENUE_TARGET = 10000000000; // Mặc định 10 tỷ, sẽ được cập nhật từ API
    let annualTargetChart = null;

    // =================================================================
    // HÀM TIỆN ÍCH
    // =================================================================
    const formatCurrency = (value) => App.formatNumber(Math.round(value)) + ' ₫';

    function fetchData(url, params = {}) {
        return $.ajax({
            url: url,
            type: 'GET',
            data: params,
            dataType: 'json',
            cache: false
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(`Lỗi AJAX khi gọi ${url}:`, textStatus, errorThrown);
            return { success: false, message: `Lỗi kết nối đến server.` };
        });
    }

    function showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            info: 'bg-blue-500',
            warning: 'bg-yellow-500'
        };
        
        const notification = $(`
            <div class="fixed top-4 right-4 z-50 p-4 rounded-lg text-white ${colors[type]} shadow-lg transform translate-x-full transition-transform duration-300">
                <div class="flex items-center">
                    <span class="mr-2">${message}</span>
                    <button class="ml-2 text-white hover:text-gray-200">×</button>
                </div>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => notification.removeClass('translate-x-full'), 100);
        setTimeout(() => {
            notification.addClass('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        notification.find('button').on('click', () => {
            notification.addClass('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        });
    }

    // Hàm lấy dữ liệu doanh thu theo tháng cho PUR và ULA
    function fetchMonthlyRevenueData() {
        return $.ajax({
            url: 'api/get_monthly_revenue_by_product.php',
            type: 'GET',
            dataType: 'json',
            cache: false
        }).fail(function() {
            console.warn('Không thể tải dữ liệu doanh thu theo tháng. Sử dụng dữ liệu giả lập.');
            return {
                success: true,
                data: [
                    { month: 1, pur_revenue: 150000000, ula_revenue: 200000000 },
                    { month: 2, pur_revenue: 180000000, ula_revenue: 220000000 },
                    { month: 3, pur_revenue: 200000000, ula_revenue: 250000000 },
                    { month: 4, pur_revenue: 170000000, ula_revenue: 180000000 },
                    { month: 5, pur_revenue: 190000000, ula_revenue: 280000000 },
                    { month: 6, pur_revenue: 220000000, ula_revenue: 300000000 },
                    { month: 7, pur_revenue: 210000000, ula_revenue: 290000000 },
                    { month: 8, pur_revenue: 240000000, ula_revenue: 320000000 },
                    { month: 9, pur_revenue: 260000000, ula_revenue: 310000000 },
                    { month: 10, pur_revenue: 280000000, ula_revenue: 340000000 },
                    { month: 11, pur_revenue: 300000000, ula_revenue: 360000000 },
                    { month: 12, pur_revenue: 320000000, ula_revenue: 380000000 }
                ]
            };
        });
    }

    // =================================================================
    // REVENUE PLAN MANAGEMENT FUNCTIONS
    // =================================================================

    // Modal controls
    $('#manage-revenue-plan-btn').on('click', function() {
        $('#revenue-plan-modal').removeClass('hidden');
        initializeRevenuePlanModal();
    });

    $('#close-revenue-plan-modal').on('click', function() {
        $('#revenue-plan-modal').addClass('hidden');
    });

    // Close modal when clicking outside
    $('#revenue-plan-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden');
        }
    });

    // Initialize revenue plan modal
    function initializeRevenuePlanModal() {
        const currentYear = new Date().getFullYear();
        const yearSelect = $('#yearSelect');
        yearSelect.empty();
        
        for (let year = currentYear - 2; year <= currentYear + 5; year++) {
            yearSelect.append(`<option value="${year}" ${year === currentYear ? 'selected' : ''}>${year}</option>`);
        }
        
        loadRevenuePlan(currentYear);
        setupRevenuePlanEventListeners();
    }

    function setupRevenuePlanEventListeners() {
        // Remove existing event listeners to prevent duplicates
        $('#loadPlanBtn, #savePlanBtn, #autoDistributeBtn').off();
        $('#annualTarget, .monthly-target, #yearSelect').off();
        
        $('#loadPlanBtn').on('click', function() {
            const year = $('#yearSelect').val();
            loadRevenuePlan(year);
        });
        
        $('#savePlanBtn').on('click', saveRevenuePlan);
        $('#autoDistributeBtn').on('click', autoDistributeMonthlyTargets);
        $('#annualTarget').on('input', updateMonthlyCalculations);
        $('.monthly-target').on('input', updateMonthlyCalculations);
        
        $('#yearSelect').on('change', function() {
            const year = $(this).val();
            loadRevenuePlan(year);
        });
    }

    function loadRevenuePlan(year) {
        const button = $('#loadPlanBtn');
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');
        
        $.ajax({
            url: 'api/revenue_plan.php',
            type: 'GET',
            data: { year: year },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                populateRevenuePlanForm(response.data);
                if (response.is_new) {
                    showNotification('Chưa có kế hoạch cho năm ' + year + '. Bạn có thể tạo mới.', 'info');
                } else {
                    showNotification('Tải kế hoạch doanh thu thành công!', 'success');
                }
            } else {
                showNotification('Lỗi khi tải kế hoạch: ' + response.message, 'error');
            }
        }).fail(function() {
            showNotification('Không thể kết nối đến server!', 'error');
        }).always(function() {
            button.prop('disabled', false).html(originalHtml);
        });
    }

    function populateRevenuePlanForm(data) {
        $('#annualTarget').val(data.MucTieuDoanhthu || 0);
        $('#planNotes').val(data.GhiChu || '');
        
        for (let i = 1; i <= 12; i++) {
            $(`#month${i}`).val(data[`MucTieuThang${i}`] || 0);
        }
        
        updateMonthlyCalculations();
    }

    function saveRevenuePlan() {
        const button = $('#savePlanBtn');
        const originalHtml = button.html();
        
        const annualTarget = parseFloat($('#annualTarget').val() || 0);
        if (annualTarget <= 0) {
            showNotification('Vui lòng nhập mục tiêu doanh thu năm hợp lệ!', 'error');
            return;
        }
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
        
        const formData = {
            Nam: parseInt($('#yearSelect').val()),
            MucTieuDoanhthu: annualTarget,
            GhiChu: $('#planNotes').val()
        };
        
        for (let i = 1; i <= 12; i++) {
            formData[`MucTieuThang${i}`] = parseFloat($(`#month${i}`).val() || 0);
        }
        
        $.ajax({
            url: 'api/revenue_plan.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData)
        }).done(function(response) {
            if (response.success) {
                showNotification('Lưu kế hoạch doanh thu thành công!', 'success');
                loadInitialData(); // Refresh dashboard
            } else {
                showNotification('Lỗi khi lưu kế hoạch: ' + response.message, 'error');
            }
        }).fail(function() {
            showNotification('Không thể kết nối đến server!', 'error');
        }).always(function() {
            button.prop('disabled', false).html(originalHtml);
        });
    }

    function autoDistributeMonthlyTargets() {
        const annualTarget = parseFloat($('#annualTarget').val() || 0);
        if (annualTarget <= 0) {
            showNotification('Vui lòng nhập mục tiêu doanh thu năm trước!', 'error');
            return;
        }
        
        // Distribution pattern (seasonal adjustment)
        const seasonalWeights = [
            0.075, // Jan - 7.5% (Tết)
            0.070, // Feb - 7.0% (Post-Tết slow)
            0.085, // Mar - 8.5% (Recovery)
            0.090, // Apr - 9.0% (Q1 push)
            0.095, // May - 9.5% (Peak)
            0.100, // Jun - 10.0% (Mid-year push)
            0.085, // Jul - 8.5% (Summer slowdown)
            0.080, // Aug - 8.0% (Continued slow)
            0.085, // Sep - 8.5% (Back to school)
            0.095, // Oct - 9.5% (Q4 preparation)
            0.100, // Nov - 10.0% (Year-end push)
            0.120  // Dec - 12.0% (Year-end closing)
        ];
        
        for (let i = 1; i <= 12; i++) {
            const monthlyTarget = Math.round(annualTarget * seasonalWeights[i-1]);
            $(`#month${i}`).val(monthlyTarget);
        }
        
        updateMonthlyCalculations();
        showNotification('Đã phân bổ tự động mục tiêu theo tháng!', 'success');
    }

    function updateMonthlyCalculations() {
        const annualTarget = parseFloat($('#annualTarget').val() || 0);
        let monthlyTotal = 0;
        
        for (let i = 1; i <= 12; i++) {
            monthlyTotal += parseFloat($(`#month${i}`).val() || 0);
        }
        
        $('#monthlyTotal').text(formatCurrency(monthlyTotal));
        
        const difference = monthlyTotal - annualTarget;
        const differenceElement = $('#difference');
        differenceElement.text(formatCurrency(Math.abs(difference)));
        
        if (difference > 0) {
            differenceElement.removeClass('text-green-600').addClass('text-red-600');
            differenceElement.prev().text('Vượt mục tiêu năm:');
        } else if (difference < 0) {
            differenceElement.removeClass('text-red-600').addClass('text-orange-600');
            differenceElement.prev().text('Thiếu so với mục tiêu:');
        } else {
            differenceElement.removeClass('text-red-600 text-orange-600').addClass('text-green-600');
            differenceElement.prev().text('Khớp với mục tiêu năm');
        }
    }

    // =================================================================
    // CÁC HÀM RENDER (HIỂN THỊ DỮ LIỆU)
    // =================================================================

    function renderDashboardStats(response) {
        if (!response || !response.success) {
            dashboardContainer.html(`<p class="text-red-500 col-span-4 text-center">Không thể tải dữ liệu tổng quan.</p>`);
            return;
        }
        const stats = response.data || {};
        
        // Cập nhật mục tiêu doanh thu từ API
        if (stats.annual_revenue_target) {
            ANNUAL_REVENUE_TARGET = stats.annual_revenue_target;
        }
        
        dashboardContainer.find('.animate-pulse').remove();
        
        dashboardContainer.html(`
            <div class="p-5 bg-white rounded-xl shadow-md"><p class="text-sm font-medium text-gray-500">Tổng Doanh Thu (Đơn Chốt)</p><p class="text-3xl font-bold text-green-600 mt-2">${formatCurrency(stats.total_revenue || 0)}</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md"><p class="text-sm font-medium text-gray-500">Đơn Hàng Mới (Tháng Này)</p><p class="text-3xl font-bold text-blue-600 mt-2">${App.formatNumber(stats.new_orders_this_month || 0)}</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md"><p class="text-sm font-medium text-gray-500">Lệnh SX Đang Chờ</p><p class="text-3xl font-bold text-yellow-600 mt-2">${App.formatNumber(stats.pending_production_orders || 0)}</p></div>
            <div class="p-5 bg-white rounded-xl shadow-md"><p class="text-sm font-medium text-gray-500">Sản Phẩm Tồn Kho Thấp</p><p class="text-3xl font-bold text-red-600 mt-2">${App.formatNumber(stats.low_stock_items || 0)}</p></div>
        `);
        
        const currentRevenue = stats.total_revenue || 0;
        renderAnnualTargetChart(currentRevenue, stats);
    }
    
    function renderAnnualTargetChart(currentRevenue, stats = {}) {
        const ctx = document.getElementById('annualTargetChart');
        if (!ctx) return; 

        const targetRevenue = stats.annual_revenue_target || ANNUAL_REVENUE_TARGET;
        const remaining = Math.max(0, targetRevenue - currentRevenue);
        const percentage = targetRevenue > 0 ? ((currentRevenue / targetRevenue) * 100).toFixed(1) : 0;
        const isOverTarget = currentRevenue >= targetRevenue;

        const labels = isOverTarget 
            ? [`DS hoàn thành: ${formatCurrency(currentRevenue)} (Vượt mục tiêu)`]
            : [`DS hoàn thành: ${formatCurrency(currentRevenue)}`, `Còn lại: ${formatCurrency(remaining)}`];

        const data = {
            labels: labels,
            datasets: [{
                data: isOverTarget ? [currentRevenue] : [currentRevenue, remaining],
                backgroundColor: isOverTarget ? ['#22C55E'] : ['#22C55E', '#fde047'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        };

        if (annualTargetChart) {
            annualTargetChart.destroy();
        }

        annualTargetChart = new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { padding: 20, font: { size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (context.parsed !== null) {
                                    const valuePercent = targetRevenue > 0 ? ((context.parsed / targetRevenue) * 100).toFixed(1) : 0;
                                    label += `: ${valuePercent}%`;
                                }
                                return label;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: `Tiến độ Doanh thu Năm (Mục tiêu: ${formatCurrency(targetRevenue)})`,
                        font: { size: 14, weight: 'bold' },
                        padding: { top: 10, bottom: 20 }
                    }
                }
            }
        });

        updateTargetInfo(currentRevenue, percentage, isOverTarget, stats);
    }

    function updateTargetInfo(currentRevenue, percentage, isOverTarget, stats = {}) {
        const progressBar = $('#progress-bar');
        const progressPercentageText = $('#progress-percentage');
        const progressTargetValue = $('#progress-target-value');
        const targetRevenue = stats.annual_revenue_target || ANNUAL_REVENUE_TARGET;

        if (progressBar.length > 0) {
            const displayPercentage = Math.min(100, parseFloat(percentage));
            progressBar.css('width', displayPercentage + '%');
            
            if (displayPercentage >= 80) progressBar.removeClass('bg-blue-500 bg-yellow-500 bg-red-500').addClass('bg-green-500');
            else if (displayPercentage >= 60) progressBar.removeClass('bg-blue-500 bg-green-500 bg-red-500').addClass('bg-yellow-500');
            else if (displayPercentage >= 40) progressBar.removeClass('bg-blue-500 bg-green-500 bg-yellow-500').addClass('bg-orange-500');
            else progressBar.removeClass('bg-blue-500 bg-green-500 bg-yellow-500 bg-orange-500').addClass('bg-red-500');
        }
        
        if (progressPercentageText.length > 0) progressPercentageText.text(percentage + '%');
        if (progressTargetValue.length > 0) progressTargetValue.text(formatCurrency(targetRevenue).replace(' ₫', ''));

        const targetInfoContainer = $('#annual-target-info');
        if (targetInfoContainer.length === 0) return;

        const currentMonth = new Date().getMonth() + 1;
        const cumulativeTarget = stats.cumulative_target_to_current_month || ((targetRevenue / 12) * currentMonth);
        const monthlyProgress = cumulativeTarget > 0 ? ((currentRevenue / cumulativeTarget) * 100).toFixed(1) : 0;

        let statusClass = 'text-red-600', statusText = 'Dưới kế hoạch', statusIcon = '🔴';
        if (monthlyProgress >= 100) { statusClass = 'text-green-600'; statusText = 'Đúng tiến độ'; statusIcon = '🟢'; } 
        else if (monthlyProgress >= 80) { statusClass = 'text-yellow-600'; statusText = 'Gần đạt kế hoạch'; statusIcon = '🟡'; }

        const remainingAmount = Math.max(0, targetRevenue - currentRevenue);
        const monthsRemaining = 12 - currentMonth + 1;
        const avgMonthlyNeeded = monthsRemaining > 0 ? remainingAmount / monthsRemaining : 0;

        const infoHtml = `
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-blue-50 p-3 rounded-lg"><p class="text-xs text-blue-600 font-medium">Mục tiêu năm</p><p class="font-bold text-blue-800">${formatCurrency(targetRevenue)}</p></div>
                    <div class="bg-green-50 p-3 rounded-lg"><p class="text-xs text-green-600 font-medium">Đã đạt được</p><p class="font-bold text-green-800">${formatCurrency(currentRevenue)}</p></div>
                    <div class="bg-gray-50 p-3 rounded-lg"><p class="text-xs text-gray-600 font-medium">Kế hoạch T${currentMonth}</p><p class="font-bold ${statusClass}">${formatCurrency(cumulativeTarget)}</p></div>
                    <div class="bg-purple-50 p-3 rounded-lg"><p class="text-xs text-purple-600 font-medium">Còn lại</p><p class="font-bold text-purple-800">${formatCurrency(remainingAmount)}</p></div>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg">
                    <div class="flex justify-between items-center"><span class="text-sm font-medium">Tiến độ theo tháng:</span><span class="font-bold ${statusClass}">${statusIcon} ${monthlyProgress}%</span></div>
                    <div class="text-xs text-gray-600 mt-1">${statusText}</div>
                </div>
                ${!isOverTarget ? `<div class="bg-orange-50 p-3 rounded-lg border-l-4 border-orange-400"><p class="text-xs text-orange-600 font-medium">TB cần đạt/tháng còn lại:</p><p class="font-bold text-orange-800">${formatCurrency(avgMonthlyNeeded)}</p></div>` : ''}
                ${isOverTarget ? `<div class="bg-green-100 p-3 rounded-lg text-center border-2 border-green-400"><div class="text-green-800 font-bold">🎉 XUẤT SẮC!</div><div class="text-sm text-green-700">Đã vượt mục tiêu năm</div><div class="text-xs text-green-600 mt-1">Vượt: ${formatCurrency(currentRevenue - targetRevenue)}</div></div>` : ''}
            </div>`;
        targetInfoContainer.html(infoHtml);
    }

    function renderMonthlyRevenueChart(response) {
        if (!response || !response.success || response.data.length === 0) return;
        
        const monthlyData = response.data;
        const monthNames = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
        const purData = [], ulaData = [], totalData = [];
        
        for (let month = 1; month <= 12; month++) {
            const monthData = monthlyData.find(item => item.month === month);
            const purRevenue = monthData ? parseFloat(monthData.pur_revenue) : 0;
            const ulaRevenue = monthData ? parseFloat(monthData.ula_revenue) : 0;
            purData.push(purRevenue);
            ulaData.push(ulaRevenue);
            totalData.push(purRevenue + ulaRevenue);
        }

        const ctx = document.getElementById('revenueChart').getContext('2d');
        if (monthlyRevenueChart) monthlyRevenueChart.destroy();

        monthlyRevenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthNames,
                datasets: [
                    { label: 'PUR (Gối)', data: purData, backgroundColor: 'rgba(255, 159, 64, 0.8)', borderColor: 'rgba(255, 159, 64, 1)', borderWidth: 1, order: 2 },
                    { label: 'ULA (Cùm)', data: ulaData, backgroundColor: 'rgba(153, 102, 255, 0.8)', borderColor: 'rgba(153, 102, 255, 1)', borderWidth: 1, order: 2 },
                    { label: 'Xu hướng tổng doanh thu', data: totalData, type: 'line', backgroundColor: 'rgba(75, 192, 192, 0.2)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 3, fill: false, tension: 0.4, pointRadius: 5, pointHoverRadius: 8, pointBackgroundColor: 'rgba(75, 192, 192, 1)', pointBorderColor: '#fff', pointBorderWidth: 2, order: 1 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { title: { display: true, text: 'Tháng', font: { size: 14, weight: 'bold' } } },
                    y: { beginAtZero: true, title: { display: true, text: 'Doanh thu (VND)', font: { size: 14, weight: 'bold' } }, ticks: { callback: value => formatCurrency(value) } }
                },
                plugins: {
                    title: { display: true, text: 'Doanh Thu Theo Tháng - PUR & ULA', font: { size: 16, weight: 'bold' }, padding: 20 },
                    legend: { display: true, position: 'top', labels: { padding: 20, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)', titleColor: '#fff', bodyColor: '#fff',
                        callbacks: {
                            label: context => `${context.dataset.label || ''}: ${formatCurrency(context.parsed.y)}`,
                            footer: items => { let total = items.reduce((sum, item) => item.dataset.type !== 'line' ? sum + item.parsed.y : sum, 0); return total > 0 ? `Tổng: ${formatCurrency(total)}` : ''; }
                        }
                    }
                }
            }
        });
    }

    function renderQuoteReport(response) {
        quoteReportContainer.empty().addClass('hidden');
        if (!response || !response.success) {
            quoteReportContainer.html(`<p class="text-red-500 font-semibold text-center p-4">${response.message || 'Có lỗi xảy ra.'}</p>`).removeClass('hidden');
            return;
        }
        quoteReportContainer.removeClass('hidden');
        const { summary, data } = response;
        const statusColors = { 'Chốt': 'green', 'Tạch': 'red', 'Đàm phán': 'cyan', 'Đấu thầu': 'yellow', 'Mới tạo': 'blue' };
        const conversionRate = (summary.total_quotes > 0) ? (((summary.status_counts['Chốt'] || 0) / summary.total_quotes) * 100).toFixed(1) : 0;
        let summaryHtml = `<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6"><div class="p-4 bg-gray-100 rounded-lg text-center"><p class="text-sm font-medium text-gray-600">Tổng số báo giá</p><p class="text-2xl font-bold text-gray-800">${summary.total_quotes}</p></div><div class="p-4 bg-gray-100 rounded-lg text-center col-span-2 md:col-span-1"><p class="text-sm font-medium text-gray-600">Tổng giá trị</p><p class="text-2xl font-bold text-gray-800">${formatCurrency(summary.total_value)}</p></div><div class="p-4 bg-teal-100 rounded-lg text-center"><p class="text-sm font-medium text-teal-600">Tỷ lệ chốt</p><p class="text-2xl font-bold text-teal-800">${conversionRate}%</p></div>`;
        Object.entries(summary.status_counts).forEach(([status, count]) => { const color = statusColors[status] || 'gray'; summaryHtml += `<div class="p-4 bg-${color}-100 rounded-lg text-center"><p class="text-sm font-medium text-${color}-600">${status}</p><p class="text-2xl font-bold text-${color}-800">${count}</p></div>`; });
        summaryHtml += `</div>`;
        let tableRows = data.length > 0 ? data.map(quote => { const color = statusColors[quote.TrangThai] || 'gray'; return `<tr><td class="py-3 px-4 border-b">${quote.SoBaoGia}</td><td class="py-3 px-4 border-b">${new Date(quote.NgayBaoGia).toLocaleDateString('vi-VN')}</td><td class="py-3 px-4 border-b">${quote.TenCongTy}</td><td class="py-3 px-4 border-b text-right">${formatCurrency(quote.TongTienSauThue)}</td><td class="py-3 px-4 border-b text-center"><span class="px-3 py-1 text-xs font-bold leading-tight rounded-full bg-${color}-100 text-${color}-800">${quote.TrangThai}</span></td></tr>`; }).join('') : '<tr><td colspan="5" class="text-center p-6 text-gray-500">Không có dữ liệu báo giá trong khoảng thời gian này.</td></tr>';
        quoteReportContainer.html(summaryHtml + `<div class="overflow-x-auto border rounded-lg"><table class="min-w-full bg-white text-sm"><thead class="bg-green-100"><tr><th class="text-left py-3 px-4 font-semibold text-green-800">Số báo giá</th><th class="text-left py-3 px-4 font-semibold text-green-800">Ngày</th><th class="text-left py-3 px-4 font-semibold text-green-800">Khách hàng</th><th class="text-right py-3 px-4 font-semibold text-green-800">Tổng tiền</th><th class="text-center py-3 px-4 font-semibold text-green-800">Trạng thái</th></tr></thead><tbody class="text-gray-700">${tableRows}</tbody></table></div>`);
    }

    function renderProductionReport(response) {
        if (!response || !response.success) {
            productionReportContainer.html(`<p class="text-red-500 text-center">Không thể tải dữ liệu sản xuất.</p>`);
            return;
        }
        const { summary, recent_orders } = response.data;
        let summaryHtml = '<div class="flex flex-wrap gap-4 mb-4">';
        Object.entries(summary).forEach(([status, count]) => { summaryHtml += `<div class="flex-1 text-center p-3 bg-gray-100 rounded-lg"><p class="font-semibold text-gray-700">${status}</p><p class="text-2xl font-bold">${count}</p></div>`; });
        summaryHtml += '</div>';
        let tableRows = recent_orders.length > 0 ? recent_orders.map(order => `<tr class="hover:bg-gray-50"><td class="p-2 border-t">${order.SoLenhSX}</td><td class="p-2 border-t">${new Date(order.NgayTao).toLocaleDateString('vi-VN')}</td><td class="p-2 border-t">${order.LoaiLSX || 'N/A'}</td><td class="p-2 border-t">${order.TrangThai}</td></tr>`).join('') : '<tr><td colspan="4" class="text-center p-4 text-gray-500">Không có lệnh sản xuất gần đây.</td></tr>';
        productionReportContainer.html(summaryHtml + `<h3 class="font-semibold text-gray-600 mt-6 mb-2 text-sm">10 Lệnh Sản Xuất Gần Nhất</h3><div class="overflow-auto border rounded-lg" style="max-height: 250px;"><table class="min-w-full text-xs"><thead class="bg-gray-100 sticky top-0"><tr><th class="p-2 text-left font-semibold">Số LSX</th><th class="p-2 text-left font-semibold">Ngày tạo</th><th class="p-2 text-left font-semibold">Loại</th><th class="p-2 text-left font-semibold">Trạng thái</th></tr></thead><tbody>${tableRows}</tbody></table></div>`);
    }

    function renderInventoryReport(response) {
        if (!response || !response.success) {
            inventoryReportContainer.html(`<p class="text-red-500 text-center">Không thể tải dữ liệu tồn kho.</p>`);
            return;
        }
        const items = response.data;
        let tableRows = items.length > 0 ? items.map(item => `<tr class="hover:bg-gray-50"><td class="p-2 border-t">${item.variant_sku}</td><td class="p-2 border-t">${item.variant_name}</td><td class="p-2 border-t text-center font-bold text-red-600">${item.quantity}</td><td class="p-2 border-t text-center">${item.minimum_stock_level}</td></tr>`).join('') : '<tr><td colspan="4" class="text-center p-4 text-gray-500">Không có sản phẩm nào dưới mức tồn kho.</td></tr>';
        inventoryReportContainer.html(`<div class="overflow-auto border rounded-lg" style="max-height: 250px;"><table class="min-w-full text-xs"><thead class="bg-gray-100 sticky top-0"><tr><th class="p-2 text-left font-semibold">Mã hàng</th><th class="p-2 text-left font-semibold">Tên sản phẩm</th><th class="p-2 text-center font-semibold">Tồn kho</th><th class="p-2 text-center font-semibold">Tối thiểu</th></tr></thead><tbody>${tableRows}</tbody></table></div>`);
    }

    function renderCustomerReport(response) {
        if (!response || !response.success || response.data.length === 0) {
            customerReportContainer.html(`<p class="text-center p-4 text-gray-500">Không có dữ liệu khách hàng.</p>`);
            return;
        }
        const customers = response.data;
        let tableRows = customers.map((customer, index) => {
            const rankClass = index < 3 ? 'text-yellow-600 font-bold' : 'text-gray-600';
            const rankIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : (index + 1);
            return `<tr class="hover:bg-gray-50"><td class="p-3 border-t text-center ${rankClass}">${rankIcon}</td><td class="p-3 border-t font-medium text-gray-700">${customer.MaKhachHang || 'N/A'}</td><td class="p-3 border-t font-medium">${customer.TenCongTy}</td><td class="p-3 border-t text-right font-semibold text-green-600">${formatCurrency(customer.total_value)}</td></tr>`;
        }).join('');
        customerReportContainer.html(`<div class="overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50 sticky top-0"><tr><th class="p-3 text-center font-semibold text-gray-700 w-16">Hạng</th><th class="p-3 text-left font-semibold text-gray-700 w-40">Mã KH</th><th class="p-3 text-left font-semibold text-gray-700">Tên Công Ty</th><th class="p-3 text-right font-semibold text-gray-700 w-48">Tổng Doanh Số</th></tr></thead><tbody class="text-sm">${tableRows}</tbody></table></div>`);
    }
    
    function renderTopProductsReport(response) {
        if (!response || !response.success) {
            topProductsReportContainer.html(`<p class="text-red-500 text-center">Không thể tải dữ liệu sản phẩm.</p>`);
            return;
        }
        const products = response.data;
        let listHtml = products.length > 0 ? products.map(prod => `<li class="flex justify-between items-center p-2 hover:bg-blue-50 rounded"><div><p class="font-medium text-gray-800 text-sm">${prod.TenSanPham}</p><p class="text-xs text-gray-500">${prod.MaHang}</p></div><span class="font-bold text-blue-700 text-sm">${App.formatNumber(prod.quote_count)} lượt</span></li>`).join('') : '<li class="text-center p-4 text-gray-500">Chưa có dữ liệu.</li>';
        topProductsReportContainer.html(`<ul class="divide-y">${listHtml}</ul>`);
    }

    function loadInitialData() {
        if (App.customerList && App.customerList.length > 0) {
            const uniqueCompanyNames = [...new Set(App.customerList.map(c => c.TenCongTy))].sort();
            customerSelect.html('<option value="">-- Xem tất cả khách hàng --</option>');
            uniqueCompanyNames.forEach(name => { customerSelect.append(`<option value="${name}">${name}</option>`); });
        }
        fetchData('api/get_dashboard_stats.php').done(renderDashboardStats);
        fetchData('api/get_production_report.php').done(renderProductionReport);
        fetchData('api/get_inventory_report.php').done(renderInventoryReport);
        fetchData('api/get_customer_report.php').done(renderCustomerReport);
        fetchData('api/get_top_products_report.php').done(renderTopProductsReport);
        fetchMonthlyRevenueData().done(renderMonthlyRevenueChart);
    }
    
    function exportAllReportsToExcel() {
        const button = exportExcelBtn;
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xuất...');
        const quoteParams = { customer_name: customerSelect.val(), start_date: startDateInput.val(), end_date: endDateInput.val() };

        function createStyledSheet(jsonData) {
            if (!jsonData || jsonData.length === 0) return null;
            const ws = XLSX.utils.json_to_sheet(jsonData);
            const objectMaxLength = [];
            jsonData.forEach(item => { Object.keys(item).forEach((key, i) => { const len = (item[key] ? String(item[key]).length : 0) + 2; objectMaxLength[i] = Math.max(objectMaxLength[i] || 0, len); }); });
            Object.keys(jsonData[0]).forEach((key, i) => { const len = (key ? String(key).length : 0) + 2; objectMaxLength[i] = Math.max(objectMaxLength[i] || 0, len); });
            ws['!cols'] = objectMaxLength.map(w => ({ wch: w }));
            const headerStyle = { font: { bold: true, color: { rgb: "FFFFFFFF" } }, fill: { fgColor: { rgb: "FF107C41" } }, alignment: { horizontal: "center", vertical: "center" } };
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let C = range.s.c; C <= range.e.c; ++C) { const address = XLSX.utils.encode_cell({ c: C, r: 0 }); if (ws[address]) ws[address].s = headerStyle; }
            return ws;
        }

        $.when(
            fetchData('api/get_quote_report.php', quoteParams),
            fetchData('api/get_production_report.php'),
            fetchData('api/get_inventory_report.php'),
            fetchData('api/get_customer_report.php'),
            fetchData('api/get_top_products_report.php'),
            fetchMonthlyRevenueData()
        ).done(function(quoteRes, prodRes, invRes, custRes, topProdRes, monthlyRes) {
            const wb = XLSX.utils.book_new();
            const summaryData = [ { "Thông Tin": "Tên Báo Cáo", "Giá Trị": "Báo Cáo Tổng Hợp Kinh Doanh" }, { "Thông Tin": "Ngày Xuất", "Giá Trị": new Date().toLocaleString('vi-VN') }, { "Thông Tin": "Phạm Vi Dữ Liệu", "Giá Trị": `Từ ${startDateInput.val() || '...'} đến ${endDateInput.val() || '...'}` }, { "Thông Tin": "Lọc Theo Khách Hàng", "Giá Trị": customerSelect.val() || "Tất cả khách hàng" } ];
            XLSX.utils.book_append_sheet(wb, createStyledSheet(summaryData), "Thông Tin Báo Cáo");
            
            if (quoteRes[0]?.success && quoteRes[0].data.length > 0) { const data = quoteRes[0].data.map(q => ({ "Số Báo Giá": q.SoBaoGia, "Ngày Báo Giá": new Date(q.NgayBaoGia).toLocaleDateString('vi-VN'), "Tên Công Ty": q.TenCongTy, "Tổng Tiền": parseFloat(q.TongTienSauThue), "Trạng Thái": q.TrangThai })); XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Báo Cáo Báo Giá"); }
            if (prodRes[0]?.success && prodRes[0].data.recent_orders.length > 0) { const data = prodRes[0].data.recent_orders.map(o => ({ "Số Lệnh SX": o.SoLenhSX, "Ngày Tạo": new Date(o.NgayTao).toLocaleDateString('vi-VN'), "Loại LSX": o.LoaiLSX, "Trạng Thái": o.TrangThai })); XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Báo Cáo Sản Xuất"); }
            if (invRes[0]?.success && invRes[0].data.length > 0) { const data = invRes[0].data.map(i => ({ "Mã Hàng": i.variant_sku, "Tên Sản Phẩm": i.variant_name, "Tồn Kho": parseInt(i.quantity), "Tồn Kho Tối Thiểu": parseInt(i.minimum_stock_level) })); XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Báo Cáo Tồn Kho"); }
            if (custRes[0]?.success && custRes[0].data.length > 0) { const data = custRes[0].data.map((c, index) => ({ "Hạng": index + 1, "Mã Khách Hàng": c.MaKhachHang, "Tên Công Ty": c.TenCongTy, "Tổng Doanh Thu": parseFloat(c.total_value) })); XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Top Khách Hàng"); }
            if (topProdRes[0]?.success && topProdRes[0].data.length > 0) { const data = topProdRes[0].data.map(p => ({ "Mã Hàng": p.MaHang, "Tên Sản Phẩm": p.TenSanPham, "Số Lần Báo Giá": parseInt(p.quote_count) })); XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Top Sản Phẩm"); }
            if (monthlyRes[0]?.success && monthlyRes[0].data.length > 0) { const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12']; const data = []; for (let month = 1; month <= 12; month++) { const monthData = monthlyRes[0].data.find(item => item.month === month); data.push({ "Tháng": monthNames[month - 1], "Doanh Thu PUR (Gối)": monthData ? parseFloat(monthData.pur_revenue) : 0, "Doanh Thu ULA (Cùm)": monthData ? parseFloat(monthData.ula_revenue) : 0, "Tổng Doanh Thu": monthData ? (parseFloat(monthData.pur_revenue) + parseFloat(monthData.ula_revenue)) : 0 }); } XLSX.utils.book_append_sheet(wb, createStyledSheet(data), "Doanh Thu Theo Tháng"); }
            
            XLSX.writeFile(wb, `BaoCaoTongHop_${new Date().toISOString().slice(0,10)}.xlsx`);
        }).fail(() => alert("Đã có lỗi xảy ra trong quá trình xuất báo cáo. Vui lòng thử lại.")).always(() => button.prop('disabled', false).html(originalHtml));
    }

    // =================================================================
    // EVENT LISTENERS
    // =================================================================
    
    viewQuoteReportBtn.on('click', function () {
        const button = $(this);
        const originalHtml = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tải...');
        const params = { customer_name: customerSelect.val(), start_date: startDateInput.val(), end_date: endDateInput.val() };
        fetchData('api/get_quote_report.php', params)
            .done(renderQuoteReport)
            .fail(() => quoteReportContainer.html('<p class="text-red-500 font-semibold text-center p-4">Không thể tải dữ liệu báo cáo. Vui lòng thử lại.</p>').removeClass('hidden'))
            .always(() => button.prop('disabled', false).html(originalHtml));
    });

    exportExcelBtn.on('click', exportAllReportsToExcel);

    // =================================================================
    // KHỞI CHẠY
    // =================================================================
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    startDateInput.val(startOfMonth.toISOString().split('T')[0]);
    endDateInput.val(today.toISOString().split('T')[0]);
    
    loadInitialData();
}