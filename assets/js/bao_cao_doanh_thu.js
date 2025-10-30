
function initializeBaoCaoDoanhThuPage(mainContentContainer) {
    let revenueChart = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.className = `show ${type}`;
        setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', { 
            style: 'currency', 
            currency: 'VND' 
        }).format(amount);
    }

    async function fetchAPI(url) {
        try {
            const response = await fetch(url);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Lỗi từ máy chủ');
            }
            return result;
        } catch (error) {
            showToast(error.message, 'error');
            throw error;
        }
    }

    async function loadRevenueData(year, period) {
        showToast("Đang tải dữ liệu...", "info");
        try {
            const result = await fetchAPI(`api/get_bao_cao_doanh_thu.php?year=${year}&period=${period}`);
            showToast("Tải dữ liệu thành công!", "success");
            return result;
        } catch (error) {
            showToast("Lỗi khi tải dữ liệu.", "error");
            return null;
        }
    }

    function updateSummaryCards(data) {
        document.getElementById('doanh-thu-thang').textContent = formatCurrency(data.summary.doanhThuThang);
        document.getElementById('doanh-thu-nam').textContent = formatCurrency(data.summary.doanhThuNam);
        document.getElementById('trung-binh-thang').textContent = formatCurrency(data.summary.trungBinhThang);
        document.getElementById('thang-cao-nhat').textContent = formatCurrency(data.summary.thangCaoNhat);
    }

    function createChart(data) {
        const ctx = document.getElementById('revenue-chart').getContext('2d');
        
        if (revenueChart) {
            revenueChart.destroy();
        }

        revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Doanh Thu (₫)',
                    data: data.values,
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' ₫';
                            }
                        }
                    }
                }
            }
        });
    }

    async function refreshData() {
        const year = document.getElementById('filter-year').value;
        const period = document.getElementById('filter-period').value;
        
        const data = await loadRevenueData(year, period);
        if (data) {
            updateSummaryCards(data);
            createChart(data.chartData);
        }
    }

    function setupEventListeners() {
        document.getElementById('refresh-btn').onclick = refreshData;
        document.getElementById('export-btn').onclick = () => {
            showToast("Đã xuất file Excel!", "success");
        };
        document.getElementById('filter-year').addEventListener('change', refreshData);
        document.getElementById('filter-period').addEventListener('change', refreshData);
    }

    async function initialize() {
        // Set năm hiện tại
        const currentYear = new Date().getFullYear();
        document.getElementById('filter-year').value = currentYear;
        
        setupEventListeners();
        await refreshData();
    }

    initialize();
}

window.initializeBaoCaoDoanhThuPage = initializeBaoCaoDoanhThuPage;
