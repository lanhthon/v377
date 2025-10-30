$(document).ready(function () {
    // =================================================================
    // KHAI BÁO BIẾN TOÀN CỤC VÀ DOM ELEMENTS
    // =================================================================
    const mainContentContainer = $('#main-content-container');
    const sidebarMenu = $('#sidebar-menu');

    // THÊM MỚI: Biến để lưu trữ hàm dọn dẹp (destroy) của trang hiện tại.
    // Nó sẽ được gọi trước khi chuyển sang trang mới.
    let currentPageCleanup = null;

    // --- HÀM HELPER NỘI BỘ (Không cần truy cập từ bên ngoài) ---
    function createModal(id, title, content, type = 'info', showCancel = false) {
        $(`#${id}`).remove();
        const typeConfig = {
            success: { icon: 'fa-check', color: 'green' },
            error: { icon: 'fa-exclamation-triangle', color: 'red' },
            info: { icon: 'fa-info-circle', color: 'blue' }
        };
        const config = typeConfig[type] || typeConfig['info'];
        const modalHtml = `
            <div id="${id}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center no-print">
                <div class="relative p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-${config.color}-100">
                           <i class="fas ${config.icon} text-${config.color}-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">${title}</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">${content}</p>
                        </div>
                        <div class="items-center px-4 py-3 space-y-2 md:space-y-0 md:space-x-4 md:flex md:justify-center">
                            <button id="${id}-ok-btn" class="w-full md:w-auto px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                OK
                            </button>
                            ${showCancel ? `<button id="${id}-cancel-btn" class="w-full md:w-auto px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Hủy
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
    }

    // Biến lưu trữ dữ liệu và các hàm tiện ích dùng chung của ứng dụng
    window.App = {
        productList: [],
        customerList: [],
        projectList: [],
        currentUser: {},
        
        formatNumber: (num) => {
            if (isNaN(num) || num === null || num === undefined) return '0';
            return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
        },
        
        parseNumber: (str) => {
            const stringValue = String(str || '');
            return parseFloat(stringValue.replace(/,/g, '')) || 0;
        },

        showMessageModal: function (message, type = 'info') {
            const title = type === 'success' ? 'Thành công!' : (type === 'error' ? 'Đã có lỗi' : 'Thông báo');
            createModal('message-modal', title, message, type, false);
            $('#message-modal').removeClass('hidden').addClass('flex');
            $('#message-modal-ok-btn').off('click').on('click', function () {
                $('#message-modal').remove();
            });
        },

        showConfirmationModal: function (title, message, callback) {
            createModal('confirmation-modal', title, message, 'info', true);
            $('#confirmation-modal').removeClass('hidden').addClass('flex');
            $('#confirmation-modal-ok-btn').off('click').on('click', function () {
                $('#confirmation-modal').remove();
                if (callback) callback();
            });
            $('#confirmation-modal-cancel-btn').off('click').on('click', function () {
                $('#confirmation-modal').remove();
            });
        },
        
        navigateTo: function (pageName) {
            history.pushState({ page: pageName }, '', `?page=${pageName}`);
            this.handleRouting();
        },
        
        handleRouting: null 
    };
    // =================================================================
    // CÁC HÀM LÕI CỦA ỨNG DỤNG
    // =================================================================

    function loadPage(pageUrl, initializer) {
        mainContentContainer.html('<div class="flex justify-center items-center h-full"><i class="fas fa-spinner fa-spin text-3xl text-gray-500"></i></div>');
        mainContentContainer.load(pageUrl, function (response, status, xhr) {
            if (status === "error") {
                mainContentContainer.html(`<div class="text-red-500 p-4"><strong>Lỗi khi tải trang!</strong><br>Không thể tải nội dung từ: ${pageUrl}<br>Mã lỗi: ${xhr.status} ${xhr.statusText}</div>`);
            } else if (typeof initializer === 'function') {
                try {
                    initializer(mainContentContainer);
                } catch (e) {
                    console.error(`Lỗi khi thực thi hàm khởi tạo cho trang ${pageUrl}:`, e);
                    mainContentContainer.html(`<div class="text-red-500 p-4"><strong>Đã xảy ra lỗi JavaScript!</strong><br>Vui lòng kiểm tra Console (F12) để biết chi tiết.</div>`);
                }
            }
        });
    }

    function buildSidebarMenu(permissions) {
        sidebarMenu.empty();

        if (!permissions || permissions.length === 0) {
            sidebarMenu.html('<p class="p-4 text-sm text-gray-400">Bạn không có quyền truy cập chức năng nào.</p>');
            return;
        }

        const baseBgClass = 'bg-slate-800';
        const hoverClass = 'hover:bg-green-600';
        const activeClass = 'bg-green-700 font-bold';

        const menuItems = {};
        const menuTree = [];

        permissions.forEach(p => {
            menuItems[p.MaChucNang] = { ...p, children: [] };
        });

        permissions.forEach(p => {
            if (p.ParentMaChucNang && menuItems[p.ParentMaChucNang]) {
                menuItems[p.ParentMaChucNang].children.push(menuItems[p.MaChucNang]);
            } else {
                menuTree.push(menuItems[p.MaChucNang]);
            }
        });

function generateMenuHtml(items, isSubmenu = false) {
    let html = isSubmenu
        ? '<ul class="pl-4 mt-1 space-y-1 hidden submenu">'
        : '<div class="space-y-2">';

    items.forEach(item => {
        if (item.children && item.children.length > 0) {
            html += `
                <div>
                    <a href="#" class="menu-parent-trigger flex items-center p-3 rounded-md transition-colors duration-200 ${baseBgClass} ${hoverClass}" 
                       data-machucnang="${item.MaChucNang}"
                       data-tooltip="${item.TenChucNang}">
                        <i class="${item.Icon} w-6 text-center text-white"></i>
                        <span class="ml-3 text-white flex-1 sidebar-text">${item.TenChucNang}</span>
                        <i class="fas fa-chevron-down text-white text-xs transform transition-transform duration-200"></i>
                    </a>
                    ${generateMenuHtml(item.children, true)}
                </div>`;
        } else {
            const tag = isSubmenu ? 'li' : 'div';
            html += `
                <${tag}>
                    <a href="#" class="sidebar-item flex items-center p-3 rounded-md transition-colors duration-200 ${baseBgClass} ${hoverClass}"
                       data-page="${item.Url}"
                       data-machucnang="${item.MaChucNang}"
                       data-active-class="${activeClass}"
                       data-tooltip="${item.TenChucNang}">
                        <i class="${item.Icon} w-6 text-center text-white"></i>
                        <span class="ml-3 text-white sidebar-text">${item.TenChucNang}</span>
                    </a>
                </${tag}>`;
        }
    });

    html += isSubmenu ? '</ul>' : '</div>';
    return html;
}

        const menuHtml = generateMenuHtml(menuTree);
        sidebarMenu.html(menuHtml);
    }

    /**
     * Xử lý định tuyến (routing) dựa trên URL
     */
    const actualHandleRouting = function () {
        // THÊM MỚI: Luôn chạy hàm dọn dẹp của trang CŨ trước khi tải trang MỚI
        if (typeof currentPageCleanup === 'function') {
            try {
                // Gọi hàm destroy của module cũ (ví dụ: destroyCustomerManagementPage)
                currentPageCleanup();
            } catch (e) {
                console.error("Lỗi khi chạy hàm dọn dẹp của trang cũ:", e);
            }
        }
        // Đặt lại hàm dọn dẹp, chờ được gán bởi trang mới (nếu có)
        currentPageCleanup = null;

        const params = new URLSearchParams(window.location.search);
        const page = params.get('page') || '';
        const id = params.get('id');
        const pxkId = params.get('pxk_id');
        const viewMode = params.get('view') === 'true';
        const bbghId = params.get('bbgh_id');
        const ccclId = params.get('cccl_id');
        const pnkId = params.get('pnk_id');
        const lsxId = params.get('lsx_id');
        const cbhId = params.get('cbh_id'); 
        const donhangId = params.get('donhang_id');

        const ycsxId = params.get('ycsx_id'); // THÊM MỚI
        let pageUrl = '';
        let initializer = null;
        let activeSidebarItem = '';

        switch (page) {
            // THAY ĐỔI: Gán cả hàm `initializer` và `currentPageCleanup`
            case 'customer_management':
                pageUrl = 'pages/customer_management.php';
                initializer = initializeCustomerManagementPage; // Hàm khởi tạo
                currentPageCleanup = destroyCustomerManagementPage; // Hàm dọn dẹp
                activeSidebarItem = 'pages/customer_management.php';
                break;
            
            // Gợi ý: Bạn nên áp dụng mô hình này cho các trang phức tạp khác
            // Ví dụ:
            // case 'donhang_list':
            //     pageUrl = 'pages/donhang_list.php';
            //     initializer = initializeDonHangListPage;
            //     currentPageCleanup = destroyDonHangListPage; // <-- Bạn sẽ cần tạo hàm này trong file donhang_management.js
            //     activeSidebarItem = 'pages/donhang_list.php';
            //     break;
            
            // --- Các case khác giữ nguyên, chỉ cần thêm `currentPageCleanup = null` nếu chúng đơn giản ---
             case 'quanly_congno':
        pageUrl = 'pages/quanly_congno.php';
        initializer = initializeCongNoPage; // Hàm từ file congno_module.js
        activeSidebarItem = 'pages/quanly_congno.php';
        break;
        
case 'quan_ly_so_quy':
    pageUrl = 'pages/quan_ly_so_quy.php';
    initializer = initializeSoQuyPage;
    activeSidebarItem = 'pages/quan_ly_so_quy.php';
    break;

case 'quan_ly_phieu_thu':
    pageUrl = 'pages/quan_ly_phieu_thu.php';
    initializer = initializePhieuThuPage;
    activeSidebarItem = 'pages/quan_ly_phieu_thu.php';
    break;

case 'quan_ly_phieu_chi':
    pageUrl = 'pages/quan_ly_phieu_chi.php';
    initializer = initializePhieuChiPage;
    activeSidebarItem = 'pages/quan_ly_phieu_chi.php';
    break;

case 'bao_cao_cong_no':
    pageUrl = 'pages/bao_cao_cong_no.php';
    initializer = initializeBaoCaoCongNoPage;
    activeSidebarItem = 'pages/bao_cao_cong_no.php';
    break;

case 'bao_cao_doanh_thu':
    pageUrl = 'pages/bao_cao_doanh_thu.php';
    initializer = initializeBaoCaoDoanhThuPage;
    activeSidebarItem = 'pages/bao_cao_doanh_thu.php';
    break;

case 'bao_cao_chi_phi':
    pageUrl = 'pages/bao_cao_chi_phi.php';
    initializer = initializeBaoCaoChiPhiPage;
    activeSidebarItem = 'pages/bao_cao_chi_phi.php';
    break;
// Dán đoạn code này vào bên trong switch(page) trong file main.js
case 'inventory_sales_report':
    pageUrl = 'pages/inventory_sales_report.php';
    // Đảm bảo bạn đã nạp file inventory_sales_report.js trong file index chính
    initializer = initializeInventorySalesReportPage; 
    activeSidebarItem = 'pages/inventory_sales_report.php';
    break;
case 'bao_cao_loi_nhuan':
    pageUrl = 'pages/bao_cao_loi_nhuan.php';
    initializer = initializeBaoCaoLoiNhuanPage;
    activeSidebarItem = 'pages/bao_cao_loi_nhuan.php';
    break;
         case 'xuat_hoa_don':
                pageUrl = `pages/xuat_hoa_don.php?ycsx_id=${ycsxId}`;
                initializer = null; // Trang này tự xử lý logic
                activeSidebarItem = 'pages/quanly_congno.php'; // Highlight menu công nợ
                break;
            case 'nhapkho_tp_ngoai_create':
                pageUrl = 'pages/nhapkho_tp_ngoai_create.php';
                initializer = initializeNhapKhoTPNgoaiPage;
                activeSidebarItem = 'pages/danhsach_pnk_tp.php';
                break;
            case 'xuatkho_btp_ngoai_create':
                pageUrl = 'pages/xuatkho_btp_ngoai_create.php';
                initializer = initializeXuatKhoBTPNgoaiPage;
                activeSidebarItem = 'pages/xuatkho_btp_list.php'; 
                break;
            case 'nhapkho_btp_ngoai_create':
                pageUrl = `pages/nhapkho_btp_ngoai_create.php`;
                initializer = initializeNhapKhoBTPNgoaiPage; 
                activeSidebarItem = 'pages/danhsach_pnk_btp.php'; 
                break;
            case 'project_management':
                pageUrl = 'pages/project_management.php';
                initializer = initializeProjectManagementPage;
                activeSidebarItem = 'pages/project_management.php';
                break;
            case 'lenhsanxuat_create_stock':
                pageUrl = 'pages/lenhsanxuat_create_stock.php';
                initializer = initializeCreateStockPOPage; 
                activeSidebarItem = 'pages/lenhsanxuat_create_stock.php';
                break;
            case 'baocao_sanxuat':
                pageUrl = 'pages/baocao_sanxuat.php';
                initializer = initializeProductionReportPage;
                activeSidebarItem = 'pages/baocao_sanxuat.php'; 
                break;
            case 'quanly_sanxuat_list': 
                pageUrl = 'pages/quanly_sanxuat_list.php';
                initializer = initializeProductionOrderListPage;
                activeSidebarItem = 'pages/quanly_sanxuat_list.php'; 
                break;
            case 'quanly_nhacungcap': 
                pageUrl = 'pages/quanly_nhacungcap.php';
                initializer = initializeSupplierManagementPage;
                activeSidebarItem = 'pages/quanly_nhacungcap.php';
                break;
            case 'quanly_cauhinh_sanxuat':
                pageUrl = 'pages/quanly_cauhinh_sanxuat.php';
                initializer = initializeProductionConfigPage;
                activeSidebarItem = 'pages/quanly_cauhinh_sanxuat.php';
                break;
            case 'delivery_plan_create':
                pageUrl = `pages/delivery_plan_create.php?donhang_id=${donhangId}`;
                initializer = initializeDeliveryPlanCreatePage;
                activeSidebarItem = 'pages/donhang_list.php';
                break;
            case 'delivery_plan_view':
                pageUrl = `pages/delivery_plan_view.php?id=${params.get('id')}`;
                initializer = initializeDeliveryPlanViewPage;
                activeSidebarItem = 'pages/donhang_list.php'; 
                break;
            case 'label_management':
                pageUrl = 'pages/label_management.php';
                initializer = initializeLabelManagementPage;
                activeSidebarItem = 'pages/label_management.php';
                break;
            case 'quote_list':
                pageUrl = 'pages/quote_list.php';
                initializer = initializeQuoteListPage;
                activeSidebarItem = 'pages/quote_list.php';
                break;
            case 'quote_create':
                pageUrl = `pages/quote_create.php` + (id ? `?id=${id}` : '');
                initializer = initializeQuoteCreatePage;
                activeSidebarItem = 'pages/quote_create.php';
                break;
            case 'donhang_list':
                pageUrl = 'pages/donhang_list.php';
                initializer = initializeDonHangListPage;
                activeSidebarItem = 'pages/donhang_list.php';
                break;
            case 'quanly_sanpham':
                pageUrl = 'pages/quanly_sanpham.php';
                initializer = initializeQuanLySanPhamPage;
                activeSidebarItem = 'pages/quanly_sanpham.php';
                break;
            case 'donhang_view':
                pageUrl = `pages/donhang_view.php?id=${id}`;
                initializer = initializeDonHangViewPage;
                activeSidebarItem = 'pages/donhang_list.php';
                break;
            case 'chuanbi_hang_list':
                pageUrl = 'pages/chuanbi_hang_list.php';
                initializer = initializeChuanBiHangListPage;
                activeSidebarItem = 'pages/chuanbi_hang_list.php';
                break;
            case 'chuanbi_hang_edit':
                pageUrl = `pages/chuanbi_hang_edit.php?id=${id}`;
                initializer = initializeChuanBiHangEditPage;
                activeSidebarItem = 'pages/chuanbi_hang_list.php';
                break;
            case 'sanxuat_view':
                pageUrl = `pages/sanxuat_view.php?id=${id}`;
                initializer = initializeSanXuatViewPage;
                activeSidebarItem = 'pages/quanly_sanxuat.php';
                break;
            case 'quanly_kho':
                pageUrl = 'pages/quanly_kho.php';
                initializer = initializeKhoPage;
                activeSidebarItem = 'pages/quanly_kho.php';
                break;
            case 'quanly_sanxuat':
                pageUrl = 'pages/quanly_sanxuat.php';
                initializer = initializeProductionManagementPage;
                activeSidebarItem = 'pages/quanly_sanxuat.php';
                break;
            case 'danhsach_nhapkho':
                pageUrl = 'pages/danhsach_nhapkho.php';
                initializer = initializeDanhSachNhapKhoPage;
                activeSidebarItem = 'pages/danhsach_nhapkho.php';
                break;
            case 'nhapkho_lk':
                pageUrl = 'pages/nhapkho_lk.php';
                initializer = initializeNhapKhoLKListPage;
                activeSidebarItem = 'pages/nhapkho_lk.php';
                break;
            case 'nhapkho_lk_create':
                pageUrl = `pages/nhapkho_lk_create.php?lsx_id=${params.get('lsx_id') || ''}`;
                initializer = initializeNhapKhoLKCreatePage;
                activeSidebarItem = 'pages/nhapkho_lk.php';
                break;
            case 'nhapkho_vattu_list':
                pageUrl = 'pages/nhapkho_vattu_list.php';
                initializer = initializeNhapKhoVatTuListPage;
                activeSidebarItem = 'pages/nhapkho_vattu_list.php';
                break;
            case 'nhapkho_vattu_create':
                pageUrl = `pages/nhapkho_vattu_create.php?pnk_id=${pnkId || ''}`;
                initializer = initializeNhapKhoVatTuCreatePage;
                activeSidebarItem = 'pages/nhapkho_vattu_list.php';
                break;
            case 'nhapkho_btp_list':
                pageUrl = 'pages/nhapkho_btp_list.php';
                initializer = initializeNhapKhoBTPListPage;
                activeSidebarItem = 'pages/nhapkho_btp_list.php';
                break;
            case 'nhapkho_btp_create':
                pageUrl = `pages/nhapkho_btp_create.php?lsx_id=${lsxId || ''}`;
                initializer = initializeNhapKhoBTPCreatePage;
                activeSidebarItem = 'pages/nhapkho_btp_list.php';
                break;
            case 'danhsach_pnk_btp':
                pageUrl = 'pages/danhsach_pnk_btp.php';
                initializer = initializeDanhSachPNKBTPPage;
                activeSidebarItem = 'pages/danhsach_pnk_btp.php';
                break;
            case 'nhapkho_tp_list':
                pageUrl = 'pages/nhapkho_tp_list.php';
                initializer = initializeNhapKhoTPListPage;
                activeSidebarItem = 'pages/nhapkho_tp_list.php';
                break;
            case 'danhsach_pnk_tp':
                pageUrl = 'pages/danhsach_pnk_tp.php';
                initializer = initializeDanhSachPNKTPPage;
                activeSidebarItem = 'pages/danhsach_pnk_tp.php';
                break;
            case 'nhapkho_tp_create':
                pageUrl = `pages/nhapkho_tp_create.php?cbh_id=${cbhId || ''}`;
                initializer = initializeNhapKhoTPCreatePage;
                activeSidebarItem = 'pages/nhapkho_tp_list.php';
                break;
            case 'xuatkho_list':
                pageUrl = 'pages/xuatkho_list.php';
                initializer = initializeXuatKhoListPage;
                activeSidebarItem = 'pages/xuatkho_list.php';
                break;
            case 'xuatkho_issued_list':
                pageUrl = 'pages/xuatkho_issued_list.php';
                initializer = initializeXuatKhoIssuedListPage;
                activeSidebarItem = 'pages/xuatkho_issued_list.php';
                break;
            case 'xuatkho_create':
                pageUrl = `pages/xuatkho_create.php` + (id ? `?id=${id}` : '') + (pxkId ? (id ? `&pxk_id=${pxkId}` : `?pxk_id=${pxkId}`) : '');
                initializer = initializeXuatKhoCreatePage;
                activeSidebarItem = 'pages/xuatkho_list.php';
                break;
            case 'xuatkho_btp_list':
                pageUrl = 'pages/xuatkho_btp_list.php';
                initializer = initializeXuatKhoBTPListPage;
                activeSidebarItem = 'pages/xuatkho_btp_list.php';
                break;
            case 'xuatkho_btp_view':
                pageUrl = `pages/xuatkho_btp_view.php?pxk_id=${pxkId || ''}`;
                initializer = initializeXuatKhoBTPViewPage;
                activeSidebarItem = 'pages/xuatkho_btp_list.php';
                break;
            case 'xuatkho_general_create':
                pageUrl = `pages/xuatkho_general_create.php` + (pxkId ? `?pxk_id=${pxkId}` : '');
                initializer = initializeXuatKhoGeneralCreatePage;
                activeSidebarItem = 'pages/xuatkho_list.php';
                break;
            case 'bbgh_list':
                pageUrl = 'pages/bbgh_list.php';
                initializer = initializeBbghListPage;
                activeSidebarItem = 'pages/bbgh_list.php';
                break;
            case 'bbgh_view':
                pageUrl = `pages/bbgh_view.php?id=${id}`;
                initializer = initializeBbghViewPage;
                activeSidebarItem = 'pages/xuatkho_issued_list.php';
                break;
            case 'cccl_view':
                pageUrl = `pages/cccl_view.php?id=${id}`;
                initializer = initializeCcclViewPage;
                activeSidebarItem = 'pages/xuatkho_issued_list.php';
                break;
            case 'report_low_stock':
                pageUrl = 'pages/report_low_stock.php';
                initializer = initializeLowStockReportPage;
                activeSidebarItem = 'pages/report_low_stock.php';
                break;
            case 'permission_management':
                pageUrl = 'pages/permission_management.php';
                initializer = initializePermissionPage;
                activeSidebarItem = 'pages/permission_management.php';
                break;
            case 'user_management':
                pageUrl = 'pages/user_management.php';
                activeSidebarItem = 'pages/user_management.php';
                break;
            case 'reports':
                pageUrl = 'pages/reports.php';
                initializer = initializeReportsPage;
                activeSidebarItem = 'pages/reports.php';
                break;
            case 'quan_ly_dinh_muc_cat': 
                pageUrl = 'pages/quan_ly_dinh_muc_cat.php';
                initializer = initializeDinhMucCatPage;
                activeSidebarItem = 'pages/quan_ly_dinh_muc_cat.php'; 
                break;
                 case 'quan_ly_dinh_muc_dong_thung': 
                pageUrl = 'pages/quan_ly_dinh_muc_dong_thung.php';
                initializer = initializeDinhMucDongThungPage;
                activeSidebarItem = 'pages/quan_ly_dinh_muc_dong_thung.php'; 
                break;
                
                
                case 'bug_management':
    pageUrl = 'pages/bug_management.php';
    initializer = initializeBugManagementPage;
    activeSidebarItem = 'pages/bug_management.php';
    break;
            default:
                pageUrl = '';
                initializer = null;
                activeSidebarItem = '';
                break;
        }


        if (pageUrl) {
            if (initializer === null) {
                console.warn(`Initializer for page '${page}' is not defined. Page will load without specific JS logic.`);
                loadPage(pageUrl, null);
                return;
            }

            const initializerFunction = typeof initializer === 'function' ? initializer : window[initializer.name];

            if (typeof initializerFunction !== 'function') {
                console.error(`Hàm khởi tạo ${initializer ? initializer.name : 'Unknown'} không được định nghĩa. Bạn đã quên tạo hoặc nạp tệp JS tương ứng chưa?`);
                mainContentContainer.html(`<div class="text-red-500 p-4">Lỗi cấu hình: Hàm <strong>${initializer ? initializer.name : 'Unknown'}</strong> không tồn tại.</div>`);
                return;
            }

            loadPage(pageUrl, initializerFunction);

            // Cập nhật trạng thái active cho menu
            $('.sidebar-item').removeClass(function (index, className) {
                return (className.match(/(^|\s)bg-\w+-\d+/g) || []).join(' ') + ' font-bold';
            });
            $('.submenu').hide();
            $('.fa-chevron-down').removeClass('rotate-180');

            const activeItem = $(`.sidebar-item[data-page="${activeSidebarItem}"]`);

            if (activeItem.length > 0) {
                activeItem.addClass(activeItem.data('active-class'));
                const parentSubmenu = activeItem.closest('.submenu');
                if (parentSubmenu.length > 0) {
                    parentSubmenu.show();
                    parentSubmenu.prev('.menu-parent-trigger').find('.fa-chevron-down').addClass('rotate-180');
                }
            }
            
        } else if (page === '') {
            const firstGrantedPage = $('.sidebar-item:not(.hidden):first').data('page');
            if (firstGrantedPage) {
                const pageName = firstGrantedPage.split('/').pop().replace('.php', '');
                history.replaceState({ page: pageName }, '', `?page=${pageName}`);
                actualHandleRouting();
            } else {
                mainContentContainer.html(`
                    <div class="text-center p-8">
                        <h1 class="text-2xl font-bold mb-4">Chào mừng!</h1>
                        <p>Bạn không được cấp quyền truy cập vào bất kỳ chức năng nào. Vui lòng liên hệ quản trị viên.</p>
                    </div>`);
            }
        } else {
            mainContentContainer.html(`<div class="text-red-500 p-4">Trang <strong>${page}</strong> không tồn tại.</div>`);
        }
    };
    window.App.handleRouting = actualHandleRouting;

    function initializeApp() {
        Promise.all([
            $.ajax({ url: 'api/get_user_permissions.php', dataType: 'json' }),
            $.ajax({ url: 'api/get_products.php', dataType: 'json' }),
            $.ajax({ url: 'api/get_customers.php', dataType: 'json' }),
            $.ajax({ url: 'api/get_projects.php', dataType: 'json' })
        ]).then(function (results) {
            const [permissionResponse, productData, customerData, projectData] = results;

            window.App.productList = productData || [];
            window.App.customerList = customerData || [];
            window.App.projectList = projectData || [];

            if (permissionResponse.success) {
                window.App.currentUser = permissionResponse.user;
                $('#user-fullname').text(permissionResponse.user.fullName);
                $('#user-role').text(permissionResponse.user.role);
                buildSidebarMenu(permissionResponse.permissions);
                window.App.handleRouting();
            } else {
                window.location.href = 'login.php';
            }
        }).catch(function (jqXHR) {
            let errorMessage = "Không xác định";
            if (jqXHR) {
                errorMessage = `Status: ${jqXHR.status}, Response: ${jqXHR.responseText || '(không có)'}`;
            }
            console.error("LỖI KHI TẢI DỮ LIỆU BAN ĐẦU:", errorMessage, jqXHR);
            if (jqXHR && jqXHR.status === 401) {
                window.location.href = 'login.php';
            } else {
                App.showMessageModal("Lỗi nghiêm trọng khi tải dữ liệu từ server. Vui lòng kiểm tra console (F12).", 'error');
            }
        });
    }

    // =================================================================
    // GÁN SỰ KIỆN (EVENT LISTENERS)
    // =================================================================

    sidebarMenu.on('click', '.sidebar-item', function (e) {
        e.preventDefault();
        const pageUrlAttr = $(this).data('page');
        if (pageUrlAttr) {
            const pageName = pageUrlAttr.split('/').pop().replace('.php', '');
            history.pushState({ page: pageName }, '', `?page=${pageName}`);
            window.App.handleRouting();
        }
    });

    sidebarMenu.on('click', '.menu-parent-trigger', function(e) {
        e.preventDefault();
        const submenu = $(this).next('.submenu');
        const arrowIcon = $(this).find('.fa-chevron-down');
        
        $(this).parent().siblings().find('.submenu').slideUp(200);
        $(this).parent().siblings().find('.fa-chevron-down').removeClass('rotate-180');
        
        submenu.slideToggle(200);
        arrowIcon.toggleClass('rotate-180');
    });

    window.onpopstate = function (event) {
        window.App.handleRouting();
    };
    
    // =================================================================
// XỬ LÝ THU GỌN SIDEBAR
// =================================================================

const sidebar = $('#sidebar');
const toggleBtn = $('#sidebar-toggle-btn');

// Khôi phục trạng thái sidebar từ localStorage
const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
if (sidebarCollapsed) {
    sidebar.addClass('collapsed');
}

// Xử lý sự kiện click nút toggle
toggleBtn.on('click', function(e) {
    e.preventDefault();
    sidebar.toggleClass('collapsed');
    
    // Lưu trạng thái vào localStorage
    const isCollapsed = sidebar.hasClass('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
    
    // Đóng tất cả submenu khi thu gọn
    if (isCollapsed) {
        $('.submenu').slideUp(200);
        $('.fa-chevron-down').removeClass('rotate-180');
    }
});

// Cập nhật sự kiện click menu parent để không mở khi collapsed
const originalParentTriggerHandler = sidebarMenu.off('click', '.menu-parent-trigger');
sidebarMenu.on('click', '.menu-parent-trigger', function(e) {
    e.preventDefault();
    
    // Không cho phép mở submenu khi sidebar thu gọn
    if (sidebar.hasClass('collapsed')) {
        return;
    }
    
    const submenu = $(this).next('.submenu');
    const arrowIcon = $(this).find('.fa-chevron-down');
    
    $(this).parent().siblings().find('.submenu').slideUp(200);
    $(this).parent().siblings().find('.fa-chevron-down').removeClass('rotate-180');
    
    submenu.slideToggle(200);
    arrowIcon.toggleClass('rotate-180');
});

    // =================================================================
    // XỬ LÝ NÚT HƯỚNG DẪN SỬ DỤNG
    // =================================================================
    $('body').on('click', '#user-guide-btn', function() {
        const userRoleCode = window.App.currentUser.roleCode;
        if (!userRoleCode) {
            console.error("Không tìm thấy mã vai trò của người dùng.");
            return;
        }

        let guideUrl = '';
        switch (userRoleCode) {
            case 'admin':
            case 'manager':
                guideUrl = 'guides/HDADMIN.html';
                break;
            case 'sales':
                guideUrl = 'guides/HDBG.html';
                break;
            case 'warehouse':
                guideUrl = 'guides/HDK.html';
                break;
            case 'production':
                guideUrl = 'guides/HDSX.html';
                break;
            case 'accountant':
            case 'ke_toan': // Hỗ trợ cả hai mã vai trò cho kế toán
                guideUrl = 'guides/HDKT.html';
                break;
            default:
                // Thông báo cho người dùng nếu không có tệp hướng dẫn
                App.showMessageModal('Chưa có tài liệu hướng dẫn cho vai trò của bạn.', 'info');
                break;
        }

        // Nếu có URL, mở nó trong tab mới
        if (guideUrl) {
            window.open(guideUrl, '_blank');
        }
    });

    // --- BẮT ĐẦU ỨNG DỤNG ---
    initializeApp();
});