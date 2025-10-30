<?php
session_start();

if (!isset($_SESSION['user_id'])) { header('Location: pages/login.php'); exit;}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3i-Fix | Phần Mềm Quản lý</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .menu {
        background-color: #343a40;
        position: fixed;
    }

    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: #4caf50;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #45a049;
    }

    .sidebar-item.active,
    .sidebar-item:hover {
        background-color: #4caf50;
    }

    #sidebar-menu {
        overflow-y: auto;
        max-height: calc(80vh - 120px);
    }

    .sidebar-item {
        transition: background-color 0.3s;
    }

    footer.fixed {
        z-index: 1000;
    }

    @media print {
        aside.no-print {
            display: none !important;
        }

        body,
        .flex.h-screen.w-full {
            overflow: visible !important;
            height: auto !important;
            display: block !important;
        }

        #main-content-container {
            overflow-y: visible !important;
            height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    }
    
    
/* ============================================ */
/* SIDEBAR COLLAPSIBLE STYLES */
/* ============================================ */

/* Sidebar transitions */
aside {
    transition: width 0.3s ease;
}

aside.collapsed {
    width: 4rem; /* 64px */
}

/* Ẩn text khi sidebar thu gọn */
aside.collapsed .sidebar-text {
    display: none;
}

/* Căn giữa icon khi sidebar thu gọn */
aside.collapsed .sidebar-item {
    justify-content: center;
}

/* Ẩn chevron khi sidebar thu gọn */
aside.collapsed .fa-chevron-down {
    display: none;
}

/* Ẩn submenu khi sidebar thu gọn */
aside.collapsed .submenu {
    display: none !important;
}

/* Toggle button style */
.sidebar-toggle {
    transition: transform 0.3s ease;
}

aside.collapsed .sidebar-toggle {
    transform: rotate(180deg);
}

/* Tooltip cho sidebar thu gọn */
aside.collapsed .sidebar-item {
    position: relative;
}

aside.collapsed .sidebar-item:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background-color: #1f2937;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    white-space: nowrap;
    margin-left: 0.5rem;
    z-index: 1000;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* User info styling khi collapsed */
aside.collapsed #user-fullname,
aside.collapsed #user-role {
    display: none;
}

aside.collapsed .fa-user-circle {
    font-size: 1.5rem;
}

/* User guide button styling */
#user-guide-btn {
    position: relative;
}

aside.collapsed #user-guide-btn {
    padding: 0.5rem;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

aside.collapsed #user-guide-btn .fa-question-circle {
    margin-right: 0;
}

aside.collapsed #user-guide-btn:hover::after {
    content: "Hướng dẫn";
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background-color: #1f2937;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    white-space: nowrap;
    margin-left: 0.5rem;
    z-index: 1000;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
/* ============================================ */
/* BUG REPORT STYLES - GREEN THEME WITH TABS */
/* ============================================ */

/* Bug report button styling khi collapsed */
aside.collapsed #bug-report-btn {
    padding: 0.5rem;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

aside.collapsed #bug-report-btn .fa-bug {
    margin-right: 0;
}

aside.collapsed #bug-report-btn:hover::after {
    content: "Báo lỗi";
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background-color: #1f2937;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    white-space: nowrap;
    margin-left: 0.5rem;
    z-index: 1000;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Bug Report Modal Styles */
#bug-report-modal {
    z-index: 9999;
}

.bug-report-container {
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.bug-report-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 1.5rem;
    border-radius: 0.75rem 0.75rem 0 0;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
}

/* Tabs Navigation */
.tab-btn {
    position: relative;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    background: white;
}

.tab-btn.active {
    background: white;
    color: #047857;
}

/* Tab Content Wrapper */
.tab-content-wrapper {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.tab-content.active {
    display: flex;
    flex-direction: column;
    flex: 1;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Bug Report List - Tab 1 */
.bug-report-list {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: #f0fdf4;
    min-height: 400px;
    max-height: 500px;
}

.bug-report-item {
    background: white;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.bug-report-item:hover {
    box-shadow: 0 6px 12px rgba(16, 185, 129, 0.15);
    transform: translateY(-2px);
    border-color: #10b981;
}

.bug-report-item.expanded {
    background: #ecfdf5;
    border-color: #10b981;
}

/* Trạng thái badges */
.bug-report-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.bug-report-status::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

/* Trạng thái: Mới */
.bug-report-status.new {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #fbbf24;
}

.bug-report-status.new::before {
    background: #f59e0b;
    animation: pulse 2s infinite;
}

/* Trạng thái: Đã tiếp nhận */
.bug-report-status.received {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.bug-report-status.received::before {
    background: #3b82f6;
}

/* Trạng thái: Đang xử lý */
.bug-report-status.processing {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4338ca;
    border: 1px solid #6366f1;
}

.bug-report-status.processing::before {
    background: #6366f1;
    animation: pulse 1.5s infinite;
}

/* Trạng thái: Đã giải quyết */
.bug-report-status.resolved {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #10b981;
}

.bug-report-status.resolved::before {
    background: #10b981;
}

/* Trạng thái: Đã đóng */
.bug-report-status.closed {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
    border: 1px solid #9ca3af;
}

.bug-report-status.closed::before {
    background: #6b7280;
}

/* Priority badges */
.priority-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.priority-badge.low {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.priority-badge.medium {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fbbf24;
}

.priority-badge.high {
    background: #fed7aa;
    color: #9a3412;
    border: 1px solid #f97316;
}

.priority-badge.urgent {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
    animation: pulse 2s infinite;
}

/* Comment styles */
.bug-comment {
    padding: 0.875rem;
    margin-top: 0.75rem;
    border-left: 4px solid #10b981;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.bug-comment.user-comment {
    background: #ecfdf5;
    border-left-color: #059669;
}

.bug-comment.admin-comment {
    background: #fef3c7;
    border-left-color: #f59e0b;
}

.bug-comment .comment-author {
    font-weight: 600;
    color: #047857;
    font-size: 0.875rem;
}

.bug-comment.admin-comment .comment-author {
    color: #d97706;
}

.bug-comment.admin-comment::before {
    content: '🛠️ Admin phản hồi:';
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #d97706;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}

/* Admin Note section */
.admin-note-section {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 2px solid #fbbf24;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-top: 1rem;
}

.admin-note-section .note-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.admin-note-section .note-content {
    color: #78350f;
    font-size: 0.875rem;
    line-height: 1.6;
}

/* Form styles - Tab 2 */
.bug-report-form {
    padding: 2rem;
    overflow-y: auto;
    max-height: 550px;
    background: white;
}

.bug-report-form h3 {
    color: #047857;
}

.bug-report-form input,
.bug-report-form textarea,
.bug-report-form select {
    transition: all 0.3s ease;
}

.bug-report-form input:focus,
.bug-report-form textarea:focus,
.bug-report-form select:focus {
    outline: none;
}

/* Image preview */
.image-preview {
    position: relative;
    display: inline-block;
    margin-right: 0.5rem;
    margin-top: 0.5rem;
}

.image-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 0.75rem;
    border: 3px solid #d1fae5;
    transition: all 0.3s ease;
}

.image-preview:hover img {
    border-color: #10b981;
    transform: scale(1.05);
}

.image-preview .remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.image-preview .remove-image:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: scale(1.1);
}

/* Toggle icon */
.report-toggle {
    transition: transform 0.3s ease;
    color: #10b981;
}

.report-toggle.rotate-180 {
    transform: rotate(180deg);
}

/* Scrollbar */
.bug-report-list::-webkit-scrollbar,
.bug-report-form::-webkit-scrollbar {
    width: 8px;
}

.bug-report-list::-webkit-scrollbar-track,
.bug-report-form::-webkit-scrollbar-track {
    background: #d1fae5;
    border-radius: 10px;
}

.bug-report-list::-webkit-scrollbar-thumb,
.bug-report-form::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #10b981 0%, #059669 100%);
    border-radius: 10px;
}

.bug-report-list::-webkit-scrollbar-thumb:hover,
.bug-report-form::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #059669 0%, #047857 100%);
}

/* Loading animation */
.bug-loading {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 3rem;
    gap: 1rem;
    min-height: 300px;
}

.bug-loading i {
    font-size: 3rem;
    color: #10b981;
    animation: spin 1s linear infinite;
}

.bug-loading p {
    color: #059669;
    font-weight: 500;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #059669;
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.empty-state i {
    font-size: 4rem;
    color: #10b981;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1rem;
    font-weight: 500;
}

/* Buttons */
.btn-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.btn-green:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
}

.btn-green:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Animations */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .bug-report-container {
        max-width: 95%;
        margin: 0.5rem;
        max-height: 95vh;
    }
    
    .bug-report-list {
        max-height: 350px;
        padding: 1rem;
    }
    
    .bug-report-form {
        padding: 1.5rem;
        max-height: 400px;
    }
    
    .bug-report-item {
        padding: 1rem;
    }
    
    .image-preview img {
        width: 80px;
        height: 80px;
    }
    
    .tab-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    #bug-stats {
        grid-cols: 3;
        gap: 0.5rem;
    }
    
    #bug-stats > div {
        padding: 0.75rem;
    }
    
    #bug-stats .text-2xl {
        font-size: 1.5rem;
    }
}

/* Print styles */
@media print {
    #bug-report-modal {
        display: none !important;
    }
}
/* Bug report button styling khi collapsed */
aside.collapsed #bug-report-btn {
    padding: 0.5rem;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

aside.collapsed #bug-report-btn .fa-bug {
    margin-right: 0;
}

aside.collapsed #bug-report-btn:hover::after {
    content: "Báo lỗi";
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background-color: #1f2937;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    white-space: nowrap;
    margin-left: 0.5rem;
    z-index: 1000;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Responsive cho bug report modal */
@media (max-width: 768px) {
    .bug-report-container {
        max-width: 95%;
        margin: 0 auto;
    }
}
    </style>
    <!-- Canonical -->
<link rel="canonical" href="https://3igreen.com.vn/">

<!-- Open Graph (Facebook, Zalo…) -->
<meta property="og:title" content="3i-Fix | Phần mềm Quản lý 3I GREEN">
<meta property="og:description" content="Công ty TNHH Sản xuất và Ứng dụng Vật liệu xanh 3I">
<meta property="og:image" content="bg.png">
<meta property="og:url" content="https://3igreen.com.vn/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="3i-Fix">
<meta property="og:locale" content="vi_VN">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="3i-Fix | Phần mềm Quản lý 3I GREEN">
<meta name="twitter:description" content="Công ty TNHH Sản xuất và Ứng dụng Vật liệu xanh 3I">
<meta name="twitter:image" content="bg.png">

<!-- Favicon (tùy chọn) -->
<link rel="icon" href="https://3igreen.com.vn/favicon.ico">
<link rel="apple-touch-icon" href="logo.png">

</head>

<body class="bg-slate-100">

    <div class="flex h-screen w-full">
<aside class="w-64 bg-gray-800 text-white flex flex-col flex-shrink-0 no-print" id="sidebar">
    <!-- Header với nút toggle -->
    <div class="p-4 border-b border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold sidebar-text">3I GREEN</h1>
            <button id="sidebar-toggle-btn" class="sidebar-toggle text-white hover:text-green-400 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
  <!-- Thêm đoạn này vào trong phần sidebar, sau nút hướng dẫn -->
<div class="text-center">
    <i class="fas fa-user-circle text-4xl text-blue-300"></i>
    <p id="user-fullname" class="font-semibold mt-2 sidebar-text">Dev Mode</p>
    <p id="user-role" class="text-xs text-blue-300 uppercase sidebar-text">ADMIN</p>
    
    <!-- Icon hướng dẫn sử dụng -->
    <button id="user-guide-btn" 
            class="mt-3 inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
            data-tooltip="Hướng dẫn sử dụng"
            title="Hướng dẫn sử dụng">
        <i class="fas fa-question-circle mr-2"></i>
        <span class="sidebar-text">Hướng dẫn</span>
    </button>
    
    <!-- NÚT BÁO LỖI/GÓP Ý MỚI -->
    <button id="bug-report-btn" 
            class="mt-2 inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500"
            data-tooltip="Báo lỗi / Góp ý"
            title="Báo lỗi / Góp ý">
        <i class="fas fa-bug mr-2"></i>
        <span class="sidebar-text">Báo lỗi</span>
    </button>
</div>
    </div>

    <nav id="sidebar-menu" class="flex-1 p-2 space-y-1">
        <div class="text-center p-4 text-sm text-gray-400">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            <span class="sidebar-text">Đang tải menu...</span>
        </div>
    </nav>

    <div class="p-2 border-t border-gray-700">
        <a href="api/logout.php"
            class="sidebar-item flex items-center p-3 rounded-md text-red-300 hover:bg-red-600 hover:text-white transition-colors duration-200"
            data-tooltip="Đăng Xuất">
            <i class="fas fa-sign-out-alt w-6 text-center"></i>
            <span class="ml-3 sidebar-text">Đăng Xuất</span>
        </a>
    </div>
</aside>

        <main id="main-content-container" class="flex-1 p-4 lg:p-6 overflow-y-auto">
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function openNewWindow(url, windowName = '_blank', features = 'width=900,height=700,resizable=yes,scrollbars=yes') {
        window.open(url, windowName, features);
    }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>

    <script src="assets/js/kho.js"></script>
    <script src="assets/js/report_low_stock.js"></script>
    <script src="assets/js/xuatkho.js"></script>
    <script src="assets/js/chuanbi_hang_edit.js"></script>
    <script src="assets/js/xuatkho_module.js"></script>
    <script src="assets/js/donhang_management.js"></script>
    <script src="assets/js/baogia_management.js"></script>
    <script src="assets/js/nhapkho.js"></script>
    <script src="assets/js/permissions.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/production_management.js"></script>
    <script src="assets/js/reports.js"></script>
    <script src="assets/js/sanxuat_view.js"></script>
    <script src="assets/js/quote_list.js"></script>
    <script src="assets/js/xuatkho_create_init.js"></script>
    <script src="assets/js/xuatkho_general_create_init.js"></script>
    <script src="assets/js/xuatkho_issued_list_init.js"></script>
    <script src="assets/js/nhapkho_module.js"></script>
    <script src="assets/js/xuatkho_btp_ngoai.js"></script>
    <script src="assets/js/quanlysanpham.js"></script>
    <script src="assets/js/giaohang_module.js"></script>
    <script src="assets/js/project_management.js"></script>
    <script src="assets/js/chuanbi_hang_list.js"></script>
    <script src="assets/js/label_management.js"></script>
    <script src="assets/js/customer_management_module.js"></script>
    <script src="assets/js/nhapkho_btp_module.js"></script>
     <script src="assets/js/nhapkho_vattu_module.js"></script>
      <script src="assets/js/nhapkho_tp_module.js"></script>
      <script src="assets/js/xuatkho_btp_module.js"></script>
      <script src="assets/js/dinh_muc_cat.js"></script>
       <script src="assets/js/lenhsanxuat_create_stock.js"></script>
       <script src="assets/js/quanly_sanxuat_module.js"></script>
         <script src="assets/js/baocao_sanxuat.js"></script>
           <script src="assets/js/quanly_nhacungcap.js"></script>
           <script src="assets/js/quanly_cauhinh_sanxuat.js"></script>
           <script src="assets/js/nhapkho_btp_ngoai.js"></script>
           <script src="assets/js/congno_module.js"></script>
           <script src="assets/js/nhapkho_tp_ngoai.js"></script>
           <script src="assets/js/nhapkho_lk_module.js"></script>
        <script src="assets/js/bug_management.js"></script>   
           <script src="assets/js/so_quy.js"></script>
<script src="assets/js/phieu_thu.js"></script>
<script src="assets/js/phieu_chi.js"></script>
<script src="assets/js/bao_cao_cong_no.js"></script>
<script src="assets/js/bao_cao_doanh_thu.js"></script>
<script src="assets/js/bao_cao_chi_phi.js"></script>
<script src="assets/js/bao_cao_loi_nhuan.js"></script>
      <script src="assets/js/bug_report.js"></script>     
           
<script src="assets/js/inventory_sales_report.js"></script>           
                  <script src="assets/js/dinh_muc_dong_thung.js"></script>
                  
                   <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/tabulator-tables@5.6.1/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.tiny.cloud/1/0yfj2gur79vvlp9qku8q0dmwf5hy0oigecypgdeyh4jrw208/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
</body>

</html>
