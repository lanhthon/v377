<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3i-Fix - Phiếu Chuẩn Bị Hàng</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css"> <!-- Giả sử bạn có file này để chứa modal -->

    <style>
    body {
        font-family: sans-serif;
        background-color: #f0f2f5;
    }

    .container {
        max-width: 1200px;
    }

    .table-subheader {
        background-color: #e0f2fe;
        color: #0c4a6e;
        font-weight: bold;
    }

    th,
    td {
        padding: 6px 4px;
        border: 1px solid #d1d5db;
        text-align: center;
        font-size: 11px;
        vertical-align: middle;
    }

    th {
        font-weight: bold;
        background-color: #f3f4f6;
    }

    input.header-input {
        width: 100%;
        border: 1px solid transparent;
        padding: 2px 4px;
        border-radius: 3px;
        transition: all 0.2s;
    }

    input.header-input:hover {
        border-color: #cbd5e1;
    }

    input.header-input:focus {
        outline: 2px solid #3b82f6;
        border-color: transparent;
        background-color: #eff6ff;
    }

    input.table-input {
        width: 100%;
        border: 1px solid #e5e7eb;
        text-align: center;
        padding: 4px 2px;
        border-radius: 3px;
    }

    input.table-input:focus {
        outline: 2px solid #3b82f6;
        border-color: transparent;
    }

    input:read-only,
    input.table-input:read-only {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }

    .group-header th {
        background-color: #d1fae5;
        color: #065f46;
        text-align: left;
        padding-left: 1rem;
    }

    .no-print {
        display: block;
    }

    @media print {
        body {
            background-color: white;
        }

        .no-print {
            display: none !important;
        }

        input,
        input.header-input,
        input.table-input {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            padding: 2px;
        }

        td,
        th {
            font-size: 10px;
        }

        .container {
            box-shadow: none;
            border: none;
        }
    }
    </style>
</head>

<body class="p-4">
    <div id="app" class="container mx-auto bg-white p-6 shadow-lg rounded-lg">
        <!-- ... (Phần HTML header và form giữ nguyên) ... -->
        <div class="flex justify-between items-center mb-4 no-print">
            <h2 class="text-2xl font-bold text-gray-900">PHIẾU CHUẨN BỊ HÀNG</h2>
            <div>
                <button id="save-chuanbi-btn"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-sm transition-colors flex items-center float-left mr-2">
                    <i class="fas fa-save mr-2"></i>Lưu Phiếu
                </button>
                <button onclick="window.print()"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 shadow-sm transition-colors flex items-center float-left">
                    <i class="fas fa-print mr-2"></i>In Phiếu
                </button>
            </div>
        </div>

        <header class="flex justify-between items-center border-b-2 pb-4">
            <div>
                <h1 class="text-lg font-bold text-gray-800">CÔNG TY TNHH SẢN XUẤT VÀ ỨNG DỤNG VẬT LIỆU 3i</h1>
                <p class="text-xs">Office: Số 14 Lô D31 - BT12 tại khu D, KĐT Mới Hai Bên Đường Lê Trọng Tấn, Hà Đông
                </p>
                <p class="text-xs">Hotline: 0973098338 - MST: 0110886479</p>
            </div>
            <div>
            </div>
        </header>

        <section class="mt-4 text-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Bộ phận:</span> <input
                        id="info-bophan" class="header-input"></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Ngày gửi YCSX:</span>
                    <input type="date" id="info-ngaygui" class="header-input">
                </div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Phụ trách:</span>
                    <input id="info-phutrach" class="header-input">
                </div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Ngày
                        giao:</span><input type="date" id="info-ngaygiao" class="header-input font-bold text-red-600">
                </div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Người nhận
                        hàng:</span> <input id="info-nguoinhan" class="header-input"></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Đăng kí công
                        trường:</span> <input id="info-congtrinh" class="header-input"></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Số đơn:</span> <input
                        id="info-sodon" class="header-input font-bold"></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Địa điểm giao
                        hàng:</span> <input id="info-diadiem" class="header-input"></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Mã đơn:</span> <input
                        id="info-madon" class="header-input font-bold" readonly></div>
                <div class="grid grid-cols-[120px,1fr] items-center"><span class="font-bold mr-2">Quy cách thùng:</span>
                    <input id="info-quycachthung" class="header-input">
                </div>
                <div class="grid grid-cols-[120px,1fr] items-center col-span-1 md:col-span-2">
                    <span class="font-bold mr-2">Loại xe:</span>
                    <div class="flex items-center space-x-4">
                        <span>Xe grap: <input id="info-xegrap" class="header-input w-24"></span>
                        <span>Xe tải (tấn): <input id="info-xetai" class="header-input w-24"></span>
                        <span>Số lái xe: <input id="info-solaixe" class="header-input w-32"></span>
                    </div>
                </div>
            </div>
        </section>

        <main class="mt-4 space-y-6">
            <table id="sanxuat-table" class="w-full border-collapse" style="display:none;">
                <thead class="table-subheader">
                    <tr>
                        <th colspan="11">HÀNG SẢN XUẤT (GỐI ĐỠ PU FOAM)</th>
                    </tr>
                    <tr>
                        <th rowspan="2" class="w-8">Stt.</th>
                        <th rowspan="2">Mã hàng</th>
                        <th colspan="3">Kích thước (mm)</th>
                        <th rowspan="2">SL (bộ)</th>
                        <th rowspan="2">Tồn kho (bộ)</th>
                        <th rowspan="2">Đóng thùng</th>
                        <th rowspan="2">SL cần SX</th>
                        <th rowspan="2">Cây cắt</th>
                        <th rowspan="2">Ghi chú</th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>Đ.Dày</th>
                        <th>B.Rộng</th>
                    </tr>
                </thead>
                <tbody id="sanxuat-table-body"></tbody>
            </table>

            <table id="nhap-table" class="w-full border-collapse" style="display:none;">
                <thead class="table-subheader">
                    <tr>
                        <th colspan="10">HÀNG NHẬP (CÙM, PHỤ KIỆN...)</th>
                    </tr>
                    <tr>
                        <th rowspan="2" class="w-8">Stt.</th>
                        <th rowspan="2">Mã hàng</th>
                        <th colspan="3">Kích thước (mm)</th>
                        <th rowspan="2">SL (bộ)</th>
                        <th rowspan="2">Tồn kho</th>
                        <th rowspan="2">Đóng tải</th>
                        <th rowspan="2">Cần đặt thêm</th>
                        <th rowspan="2">Ghi chú</th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>Đ.Dày</th>
                        <th>B.Rộng</th>
                    </tr>
                </thead>
                <tbody id="nhap-table-body"></tbody>
            </table>

            <table id="ecu-for-clamp-table" class="w-full border-collapse" style="display:none;">
                <thead class="table-subheader">
                    <tr>
                        <th colspan="8" id="ecu-table-title">VẬT TƯ ĐI KÈM</th>
                    </tr>
                    <tr>
                        <th>Stt.</th>
                        <th class="text-left">Tên vật tư</th>
                        <th id="ecu-qty-header">SL cần</th>
                        <th>Tồn kho</th>
                        <th>Cần lấy/đặt</th>
                        <th>Đóng tải</th>
                        <th>Số Kg</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="ecu-for-clamp-table-body"></tbody>
            </table>

            <table id="ecu-table" class="w-full border-collapse" style="display:none;">
                <thead class="table-subheader">
                    <tr>
                        <th colspan="6">ECU / SẢN PHẨM KHÁC (THEO ĐƠN)</th>
                    </tr>
                    <tr>
                        <th class="w-8">Stt.</th>
                        <th class="text-left">Tên sản phẩm</th>
                        <th>SL YC</th>
                        <th>Đóng tải</th>
                        <th>Số Kg</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="ecu-table-body"></tbody>
            </table>
        </main>
    </div>

    <!-- Modal for messages -->
    <div id="message-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div id="modal-icon"
                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Thành công!</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500" id="modal-message">Hành động đã được thực hiện thành công.</p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="modal-close-btn"
                        class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/utils.js"></script> <!-- Giả sử bạn có file này để chứa hàm showMessageModal -->

    <script>
    $(document).ready(function() {
        const params = new URLSearchParams(window.location.search);
        // ID có thể là ID đơn hàng (YCSX_ID) hoặc ID phiếu chuẩn bị (CBH_ID)
        const id = params.get('id');
        if (!id) {
            showMessageModal('Không tìm thấy ID trong URL.', 'error');
            return;
        }

        // --- CÁC HÀM POPULATE VÀ GENERATE ROW GIỮ NGUYÊN NHƯ TRONG FILE GỐC ---
        function populateSavedEcuTable(items) {
            const tableBody = $('#ecu-for-clamp-table-body');
            const table = tableBody.closest('table');
            tableBody.empty();
            if (!items || items.length === 0) return table.hide();

            table.find('#ecu-table-title').text('VẬT TƯ ĐI KÈM (ĐÃ LƯU)');
            table.find('#ecu-qty-header').text('SL đã lưu');
            table.find('thead th:nth-child(4), thead th:nth-child(5)').hide();

            items.forEach((item, index) => {
                const rowHtml = `
                    <tr data-chitietsaveid="${item.ChiTietEcuCBH_ID}">
                        <td>${index + 1}</td>
                        <td class="text-left"><input class="table-input text-left" value="${item.TenSanPhamEcu || ''}"></td>
                        <td><input type="number" class="table-input" value="${item.SoLuongEcu || 0}"></td>
                        <td style="display:none;"><input class="table-input" value="N/A" readonly></td>
                        <td style="display:none;"><input class="table-input" value="N/A" readonly></td>
                        <td><input type="text" class="table-input" value="${item.DongGoiEcu || ''}"></td>
                        <td><input type="number" step="0.01" class="table-input" value="${parseFloat(item.SoKgEcu || 0).toFixed(2)}"></td>
                        <td><input type="text" class="table-input" value="${item.GhiChuEcu || ''}"></td>
                    </tr>`;
                tableBody.append(rowHtml);
            });
            table.show();
        }

        function populateDerivedEcuTable(items) {
            const tableBody = $('#ecu-for-clamp-table-body');
            const table = tableBody.closest('table');
            tableBody.empty();
            if (!items || items.length === 0) return table.hide();

            table.find('#ecu-table-title').text('VẬT TƯ ĐI KÈM (TÍNH TOÁN)');
            table.find('#ecu-qty-header').text('SL cần');
            table.find('thead th:nth-child(4), thead th:nth-child(5)').show();

            items.forEach((item, index) => {
                const canLay = Math.max(0, (item.SoLuongEcuTong || 0) - (item.TonKhoEcu || 0));
                const rowHtml = `
                    <tr data-ecu-variant-id="${item.EcuVariantID}">
                        <td>${index + 1}</td>
                        <td class="text-left">${item.TenEcu}</td>
                        <td class="font-bold">${item.SoLuongEcuTong}</td>
                        <td>${item.TonKhoEcu}</td>
                        <td><input type="number" class="table-input" value="${canLay}"></td>
                        <td><input type="text" class="table-input"></td>
                        <td><input type="number" step="0.01" class="table-input" value="${(item.SoKgUocTinh || 0).toFixed(2)}"></td>
                        <td><input type="text" class="table-input"></td>
                    </tr>`;
                tableBody.append(rowHtml);
            });
            table.show();
        }

        function generateRow(item, index, type) {
            const ghiChuDaLuu = item.GhiChu || '';
            const tonKhoVatLy = item.TonKhoVatLy || 0;
            const soLuongCanSX = item.SoLuongCanSX || 0;

            if (type === 'sanxuat') {
                const dongThungDaLuu = item.SoThung || ''; // Lấy từ chitietchuanbihang
                const cayCatDaLuu = item.CayCat ?? (soLuongCanSX > 0 ? (item.SoCayPhaiCat || '') : '');
                return `
                    <tr data-mahang="${item.MaHang}" data-id-thongso="${item.ID_ThongSo}">
                        <td>${index}</td><td>${item.MaHang || ''}</td><td>${item.ID_ThongSo || ''}</td>
                        <td>${item.DoDayItem || ''}</td><td>${item.BanRongItem || ''}</td>
                        <td class="font-semibold">${item.SoLuong || 0}</td><td>${tonKhoVatLy}</td>
                        <td><input type="text" class="table-input" value="${dongThungDaLuu}"></td>
                        <td>${soLuongCanSX}</td>
                        <td><input class="table-input" value="${cayCatDaLuu}"></td>
                        <td><input type="text" class="table-input" value="${ghiChuDaLuu}"></td>
                    </tr>`;
            }
            if (type === 'nhap' || type === 'ecu_donhang') {
                const dongGoiDaLuu = item.DongGoi || ''; // Lấy từ chitietchuanbihang
                const datThemDaLuu = item.DatThem ?? soLuongCanSX;
                const soKgDaLuu = item.SoKg || '0.00';

                const commonDataAttrs = `data-mahang="${item.MaHang}" data-id-thongso="${item.ID_ThongSo}"`;

                if (type === 'nhap') {
                    return `
                        <tr ${commonDataAttrs}>
                            <td>${index}</td><td>${item.MaHang || ''}</td><td>${item.ID_ThongSo || ''}</td>
                            <td>${item.DoDayItem || ''}</td><td>${item.BanRongItem || ''}</td>
                            <td class="font-semibold">${item.SoLuong || 0}</td><td>${tonKhoVatLy}</td>
                            <td><input type="text" class="table-input" value="${dongGoiDaLuu}"></td>
                            <td><input type="number" class="table-input" value="${datThemDaLuu}"></td>
                            <td><input type="text" class="table-input" value="${ghiChuDaLuu}"></td>
                        </tr>`;
                } else { // Ecu theo đơn hàng
                    return `
                        <tr ${commonDataAttrs}>
                            <td>${index}</td><td class="text-left">${item.TenSanPham || ''}</td>
                            <td class="font-semibold">${item.SoLuong || 0}</td>
                            <td><input type="text" class="table-input" value="${dongGoiDaLuu}"></td>
                            <td><input type="number" step="0.01" class="table-input" value="${soKgDaLuu}"></td>
                            <td><input type="text" class="table-input" value="${ghiChuDaLuu}"></td>
                        </tr>`;
                }
            }
            return '';
        }

        function populateForm(data) {
            const {
                info,
                items
            } = data;
            // Cập nhật tiêu đề trang
            document.title = `CBH - ${info.SoYCSX || info.SoDon}`;

            // Điền thông tin chung
            $('#info-bophan').val(info.BoPhan || 'Kho - Logistic');
            $('#info-ngaygui').val(info.NgayGuiYCSX ? info.NgayGuiYCSX.split(' ')[0] : (info.NgayTao ? info
                .NgayTao.split(' ')[0] : ''));
            $('#info-phutrach').val(info.PhuTrach || info.NguoiBaoGia);
            $('#info-ngaygiao').val(info.NgayGiao ? info.NgayGiao.split(' ')[0] : (info.NgayGiaoDuKien ? info
                .NgayGiaoDuKien.split(' ')[0] : ''));
            $('#info-nguoinhan').val(info.NguoiNhanHang || info.NguoiNhan || info.TenCongTy);
            $('#info-congtrinh').val(info.DangKiCongTruong || info.TenDuAn);
            $('#info-sodon').val(info.SoDon || info.SoYCSX);
            $('#info-diadiem').val(info.DiaDiemGiaoHang || info.DiaChiGiaoHang);
            $('#info-madon').val(info.DonHangID || info.YCSX_ID); // Quan trọng: Lấy ID đơn hàng
            $('#info-quycachthung').val(info.QuyCachThung || '');
            $('#info-xegrap').val(info.XeGrap || '');
            $('#info-xetai').val(info.XeTai || '');
            $('#info-solaixe').val(info.SoLaiXe || '');

            $('#sanxuat-table-body, #nhap-table-body, #ecu-table-body').empty();
            $('#sanxuat-table, #nhap-table, #ecu-table').hide();

            let sxIdx = 1,
                nIdx = 1,
                ecuIdx = 1;
            let sxGroup = '',
                nGroup = '',
                ecuGroup = '';

            items.forEach(item => {
                const {
                    ProductBaseSKU = '', TenNhom = 'Sản phẩm khác'
                } = item;
                if (ProductBaseSKU.toUpperCase().includes('ECU')) {
                    if (TenNhom !== ecuGroup) {
                        $('#ecu-table-body').append(
                            `<tr class="group-header"><th colspan="6">${TenNhom}</th></tr>`);
                        ecuGroup = TenNhom;
                    }
                    $('#ecu-table-body').append(generateRow(item, ecuIdx++, 'ecu_donhang')).closest(
                        'table').show();
                } else if (ProductBaseSKU.startsWith("PUR-")) {
                    if (TenNhom !== sxGroup) {
                        $('#sanxuat-table-body').append(
                            `<tr class="group-header"><th colspan="11">${TenNhom}</th></tr>`);
                        sxGroup = TenNhom;
                    }
                    $('#sanxuat-table-body').append(generateRow(item, sxIdx++, 'sanxuat')).closest(
                        'table').show();
                } else {
                    if (TenNhom !== nGroup) {
                        $('#nhap-table-body').append(
                            `<tr class="group-header"><th colspan="10">${TenNhom}</th></tr>`);
                        nGroup = TenNhom;
                    }
                    $('#nhap-table-body').append(generateRow(item, nIdx++, 'nhap')).closest('table')
                        .show();
                }
            });
        }

        function loadData() {
            // API endpoint sẽ dựa vào việc đây là phiếu mới hay phiếu cũ
            // Chúng ta dùng chung API `get_donhang_details.php` cho đơn giản,
            // vì nó đã chứa đủ thông tin cần thiết để điền form.
            // Nếu là phiếu cũ, API này cần được sửa để join và lấy thêm dữ liệu đã lưu từ `chitietchuanbihang`.
            $.ajax({
                url: `api/get_donhang_details.php?id=${id}`, // ID ở đây là DonHangID hoặc CBH_ID
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.donhang) {
                        populateForm(response.donhang);

                        if (response.donhang.saved_ecu_items && response.donhang.saved_ecu_items
                            .length > 0) {
                            populateSavedEcuTable(response.donhang.saved_ecu_items);
                        } else if (response.donhang.derived_ecu_items && response.donhang
                            .derived_ecu_items.length > 0) {
                            populateDerivedEcuTable(response.donhang.derived_ecu_items);
                        }
                    } else {
                        showMessageModal(response.message || 'Lỗi tải dữ liệu.', 'error');
                    }
                },
                error: (xhr) => {
                    console.error("Lỗi AJAX:", xhr.responseText);
                    showMessageModal('Lỗi kết nối đến máy chủ.', 'error');
                }
            });
        }

        loadData();

        // CẬP NHẬT: LOGIC CHO NÚT LƯU
        $('#save-chuanbi-btn').on('click', function() {
            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

            const dataToSave = collectDataForSave();

            $.ajax({
                url: 'api/save_phieu_chuan_bi.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(dataToSave),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showMessageModal(response.message || 'Lưu phiếu thành công!',
                            'success');
                        // Nếu là phiếu mới, chuyển hướng đến trang chỉnh sửa với ID mới
                        // để tránh tạo phiếu trùng lặp khi nhấn F5 hoặc Lưu lần nữa.
                        const currentUrl = new URL(window.location.href);
                        if (!currentUrl.searchParams.get('id').startsWith('CBH-') &&
                            response.cbh_id) {
                            // Giả sử ID phiếu cũ không bắt đầu bằng CBH-
                            setTimeout(() => {
                                window.location.href =
                                    `?page=chuanbi_hang_edit&id=${response.cbh_id}`;
                            }, 1500);
                        }
                    } else {
                        showMessageModal('Lỗi khi lưu: ' + (response.message ||
                            'Lỗi không xác định.'), 'error');
                    }
                },
                error: function(xhr) {
                    console.error("Lỗi AJAX khi lưu:", xhr.responseText);
                    showMessageModal('Lỗi kết nối server khi lưu.', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        /**
         * CẬP NHẬT: Hàm thu thập dữ liệu từ form để gửi đi
         */
        function collectDataForSave() {
            const donHangID = $('#info-madon').val();

            const data = {
                donHangID: donHangID,
                thongTinChung: {
                    boPhan: $('#info-bophan').val(),
                    ngayGui: $('#info-ngaygui').val(),
                    phuTrach: $('#info-phutrach').val(),
                    ngayGiao: $('#info-ngaygiao').val(),
                    nguoiNhan: $('#info-nguoinhan').val(),
                    congTrinh: $('#info-congtrinh').val(),
                    soDon: $('#info-sodon').val(),
                    diaDiem: $('#info-diadiem').val(),
                    maDon: $('#info-madon').val(),
                    quyCachThung: $('#info-quycachthung').val(),
                    xeGrap: $('#info-xegrap').val(),
                    xeTai: $('#info-xetai').val(),
                    soLaiXe: $('#info-solaixe').val(),
                },
                itemsSanXuat: [],
                itemsNhap: [],
                itemsEcuDonHang: [],
                itemsEcuMoi: [],
                itemsEcuDaLuu: []
            };

            // Thu thập dữ liệu từ các bảng sản phẩm chính
            $('#sanxuat-table-body tr:not(.group-header)').each(function() {
                const row = $(this);
                const inputs = row.find('input');
                data.itemsSanXuat.push({
                    maHang: row.data('mahang'),
                    tenSanPham: row.find('td:nth-child(2)').text(), // Lấy tên từ cell
                    idThongSo: row.data('id-thongso'),
                    soLuong: parseFloat(row.find('td:nth-child(6)').text()),
                    soThung: $(inputs[0]).val(),
                    cayCat: $(inputs[1]).val(),
                    ghiChu: $(inputs[2]).val(),
                    datThem: null, // Không áp dụng
                    soKg: null, // Không áp dụng
                    dongGoi: null, // Không áp dụng
                });
            });

            $('#nhap-table-body tr:not(.group-header)').each(function() {
                const row = $(this);
                const inputs = row.find('input');
                data.itemsNhap.push({
                    maHang: row.data('mahang'),
                    tenSanPham: row.find('td:nth-child(2)').text(),
                    idThongSo: row.data('id-thongso'),
                    soLuong: parseFloat(row.find('td:nth-child(6)').text()),
                    dongGoi: $(inputs[0]).val(),
                    datThem: $(inputs[1]).val(),
                    ghiChu: $(inputs[2]).val(),
                    soThung: null, // Không áp dụng
                    cayCat: null, // Không áp dụng
                    soKg: null, // Không áp dụng
                });
            });

            $('#ecu-table-body tr:not(.group-header)').each(function() {
                const row = $(this);
                const inputs = row.find('input');
                data.itemsEcuDonHang.push({
                    maHang: row.data('mahang'),
                    tenSanPham: row.find('td:nth-child(2)').text(),
                    idThongSo: row.data('id-thongso'),
                    soLuong: parseFloat(row.find('td:nth-child(3)').text()),
                    dongGoi: $(inputs[0]).val(),
                    soKg: $(inputs[1]).val(),
                    ghiChu: $(inputs[2]).val(),
                    soThung: null, // Không áp dụng
                    cayCat: null, // Không áp dụng
                    datThem: null, // Không áp dụng
                });
            });

            // Thu thập dữ liệu từ bảng vật tư đi kèm
            $('#ecu-for-clamp-table-body tr').each(function() {
                const row = $(this);
                const inputs = row.find('input');
                if (row.data('chitietsaveid')) {
                    data.itemsEcuDaLuu.push({
                        chiTietID: row.data('chitietsaveid'),
                        tenEcu: $(inputs[0]).val(),
                        soLuong: $(inputs[1]).val(),
                        dongGoi: $(inputs[2]).val(),
                        soKg: $(inputs[3]).val(),
                        ghiChu: $(inputs[4]).val(),
                    });
                } else {
                    data.itemsEcuMoi.push({
                        tenEcu: row.find('td:nth-child(2)').text(),
                        canLay: $(inputs[0]).val(),
                        dongGoi: $(inputs[1]).val(),
                        soKg: $(inputs[2]).val(),
                        ghiChu: $(inputs[3]).val(),
                    });
                }
            });

            console.log("Data collected for saving:", data);
            return data;
        }
    });
    </script>
</body>

</html>