

<div class="bug-management-container">
    <!-- Header -->
    <div class="bug-mgmt-header">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                    <i class="fas fa-bug text-green-600 mr-2"></i>
                    Quản Lý Báo Lỗi
                </h1>
                <p class="text-sm text-gray-600 mt-1">Xem và xử lý các báo lỗi từ người dùng</p>
            </div>
            <button id="refresh-bugs" class="btn-refresh">
                <i class="fas fa-sync-alt mr-2"></i>
                <span class="hidden md:inline">Làm mới</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-new">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-new">0</div>
                    <div class="stat-label">Mới</div>
                </div>
            </div>
            
            <div class="stat-card stat-received">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-received">0</div>
                    <div class="stat-label">Đã tiếp nhận</div>
                </div>
            </div>
            
            <div class="stat-card stat-processing">
                <div class="stat-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-processing">0</div>
                    <div class="stat-label">Đang xử lý</div>
                </div>
            </div>
            
            <div class="stat-card stat-resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="stat-resolved">0</div>
                    <div class="stat-label">Đã giải quyết</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-filter mr-1"></i>Trạng thái
                </label>
                <select id="filter-status" class="filter-select">
                    <option value="">Tất cả</option>
                    <option value="Mới">Mới</option>
                    <option value="Đã tiếp nhận">Đã tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Đã giải quyết">Đã giải quyết</option>
                    <option value="Đã đóng">Đã đóng</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-flag mr-1"></i>Ưu tiên
                </label>
                <select id="filter-priority" class="filter-select">
                    <option value="">Tất cả</option>
                    <option value="Khẩn cấp">Khẩn cấp</option>
                    <option value="Cao">Cao</option>
                    <option value="Trung bình">Trung bình</option>
                    <option value="Thấp">Thấp</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-search mr-1"></i>Tìm kiếm
                </label>
                <input type="text" id="search-bugs" class="filter-input" placeholder="Tìm theo tiêu đề...">
            </div>
        </div>
    </div>

    <!-- Bug List -->
    <div class="bug-list-container" id="bug-list-container">
        <div class="loading-container">
            <i class="fas fa-spinner fa-spin text-4xl text-green-600"></i>
            <p class="text-gray-600 mt-3">Đang tải dữ liệu...</p>
        </div>
    </div>
</div>

<!-- Modal Chi Tiết Báo Lỗi -->
<div id="bug-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div id="bug-detail-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Bug Management Styles */
.bug-management-container {
    padding: 1rem;
}

.bug-mgmt-header {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-refresh {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.btn-refresh:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.stat-card.stat-new {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #fbbf24;
}

.stat-card.stat-received {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 2px solid #3b82f6;
}

.stat-card.stat-processing {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border: 2px solid #6366f1;
}

.stat-card.stat-resolved {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 2px solid #10b981;
}

.stat-icon {
    font-size: 2rem;
}

.stat-card.stat-new .stat-icon { color: #d97706; }
.stat-card.stat-received .stat-icon { color: #2563eb; }
.stat-card.stat-processing .stat-icon { color: #4f46e5; }
.stat-card.stat-resolved .stat-icon { color: #059669; }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.25rem;
    opacity: 0.8;
}

/* Filters */
.filters-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e5e7eb;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.filter-select,
.filter-input {
    padding: 0.75rem;
    border: 2px solid #d1fae5;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

/* Bug List */
.bug-list-container {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    min-height: 400px;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.bug-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.bug-card:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    transform: translateY(-2px);
}

.bug-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.75rem;
}

.bug-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    flex: 1;
}

.bug-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    font-size: 0.875rem;
    color: #6b7280;
}

.bug-user {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: #f3f4f6;
    border-radius: 9999px;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .bug-management-container {
        padding: 0.5rem;
    }

    .bug-mgmt-header {
        padding: 1rem;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        font-size: 1.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .filters-section {
        grid-template-columns: 1fr;
    }

    .bug-card {
        padding: 1rem;
    }

    .bug-title {
        font-size: 1rem;
    }
}
</style>