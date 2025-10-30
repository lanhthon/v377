<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Dự Án Toàn Diện</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        /* Tùy chỉnh thanh cuộn cho đẹp hơn trên trình duyệt Webkit (Chrome, Safari) */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;  /* Chiều rộng cho thanh cuộn dọc */
            height: 8px; /* Chiều cao cho thanh cuộn ngang */
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Class 'active' cho tab đang được chọn */
        .tab-button.active {
            border-color: #2563eb; /* Tương ứng với border-blue-600 */
            color: #2563eb;      /* Tương ứng với text-blue-600 */
            font-weight: 600;
        }

        /* Thêm đường viền cho bảng */
        .table-border-collapse {
            border-collapse: collapse;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<!-- SỬA LỖI: Bọc toàn bộ nội dung chính trong một div flex-col để kiểm soát layout -->
<div class="container mx-auto p-4 sm:p-6 h-screen flex flex-col">
    
    <!-- Phần nội dung không cuộn (tiêu đề, tabs, nút bấm) -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6">Quản Lý Dự Án Toàn Diện</h1>

        <div class="mb-6 border-b border-gray-200 overflow-x-auto custom-scrollbar">
            <ul class="flex flex-nowrap text-sm font-medium text-center" id="project-status-tabs">
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button active" data-status="all">Tất cả</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="mới">Mới</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="đang theo">Đang Theo</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="đấu thầu">Đấu Thầu</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="báo giá">Báo Giá</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="chốt đơn">Chốt Đơn</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="tạch">Tạch</button></li>
                <li class="mr-2 flex-shrink-0"><button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 tab-button" data-status="đã hoàn thiện">Đã Hoàn Thiện</button></li>
            </ul>
        </div>

        <div class="mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                 <button id="add-project-btn" class="w-full sm:w-auto bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 transition duration-300 shadow-lg flex items-center justify-center">
                     <i class="fas fa-plus mr-2"></i> Thêm Dự án
                </button>
                <button id="export-excel-btn" class="w-full sm:w-auto bg-green-600 text-white px-5 py-2 rounded-md hover:bg-green-700 transition duration-300 shadow-lg flex items-center justify-center">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </button>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                <div class="relative w-full sm:w-auto">
                     <select id="project-province-filter" class="w-full sm:w-48 border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
                         <option value="all">Tất cả tỉnh thành</option>
                    </select>
                </div>
                <div class="relative w-full sm:w-80">
                    <input type="text" id="project-search-input" placeholder="Tìm kiếm mọi thông tin..." class="w-full border border-gray-300 rounded-md py-2 px-4 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- SỬA LỖI: Div này sẽ tự co giãn và chứa bảng có scroll -->
    <div class="flex-grow overflow-auto bg-white rounded-lg shadow custom-scrollbar">
        <table class="min-w-full leading-normal text-sm table-border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase leading-normal">
                    <th class="py-3 px-4 text-center sticky left-0 bg-gray-200 z-10 w-[120px] border border-gray-300">Hành động</th>
                    <th class="py-3 px-4 text-left w-[100px] border border-gray-300">Mã</th>
                    <th class="py-3 px-4 text-left w-[250px] border border-gray-300">Tên Dự Án</th>
                    <th class="py-3 px-4 text-left w-[200px] border border-gray-300">Địa Chỉ</th>
                    <th class="py-3 px-4 text-left w-[150px] border border-gray-300">Tỉnh Thành</th>
                    <th class="py-3 px-4 text-left w-[120px] border border-gray-300">Loại Hình</th>
                    <th class="py-3 px-4 text-left w-[120px] border border-gray-300">Giá Trị Đầu Tư</th>
                    <th class="py-3 px-4 text-left w-[100px] border border-gray-300">Khởi Công</th>
                    <th class="py-3 px-4 text-left w-[100px] border border-gray-300">Hoàn Công</th>
                    <th class="py-3 px-4 text-left w-[150px] border border-gray-300">Chủ Đầu Tư</th>
                    <th class="py-3 px-4 text-left w-[150px] border border-gray-300">Tổng Thầu</th>
                    <th class="py-3 px-4 text-left w-[150px] border border-gray-300">Thầu MEP</th>
                    <th class="py-3 px-4 text-left w-[200px] border border-gray-300">Đầu Mối Làm Việc</th>
                    <th class="py-3 px-4 text-left w-[200px] border border-gray-300">Hạng Mục Báo Giá</th>
                    <th class="py-3 px-4 text-right w-[150px] border border-gray-300">Giá Trị Dự Kiến (VND)</th>
                    <th class="py-3 px-4 text-left w-[120px] border border-gray-300">Tiến Độ</th>
                    <th class="py-3 px-4 text-left w-[120px] border border-gray-300">Kết Quả</th>
                    <th class="py-3 px-4 text-left w-[150px] border border-gray-300">Sale Phụ Trách</th>
                </tr>
            </thead>
            <tbody id="project-list-body" class="text-gray-700">
                <tr class="hover:bg-gray-100">
                    <td colspan="18" class="text-center py-10 border border-gray-300">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- CÁC MODAL ĐƯỢC ĐẶT Ở NGOÀI CÙNG -->
<div id="project-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 hidden justify-center items-start z-50 overflow-y-auto py-10">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl w-full max-w-4xl m-4">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h2 id="project-modal-title" class="text-2xl font-bold text-gray-800">Thêm Dự Án Mới</h2>
            <button id="close-modal-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <form id="project-form">
            <input type="hidden" id="project-id">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="MaDuAn" class="block text-gray-700 text-sm font-bold mb-2">Mã Dự Án:</label>
                        <input type="text" id="MaDuAn" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="TenDuAn" class="block text-gray-700 text-sm font-bold mb-2">Tên Dự Án (*):</label>
                        <textarea id="TenDuAn" rows="3" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>
                    <div>
                        <label for="DiaChi" class="block text-gray-700 text-sm font-bold mb-2">Địa Chỉ:</label>
                        <textarea id="DiaChi" rows="3" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                     <div>
                        <label for="TinhThanh" class="block text-gray-700 text-sm font-bold mb-2">Tỉnh Thành:</label>
                        <input type="text" id="TinhThanh" placeholder="VD: Bắc Ninh" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="LoaiHinh" class="block text-gray-700 text-sm font-bold mb-2">Loại Hình:</label>
                        <select id="LoaiHinh" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn loại hình --</option>
                            <option value="Nhà máy Nhật">Nhà máy Nhật</option>
                            <option value="Nhà máy Hàn">Nhà máy Hàn</option>
                            <option value="Nhà máy TQ">Nhà máy TQ</option>
                            <option value="Nhà máy">Nhà máy</option>
                            <option value="Building">Building</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="GiaTriDauTu" class="block text-gray-700 text-sm font-bold mb-2">Giá Trị Đầu Tư:</label>
                        <input type="text" id="GiaTriDauTu" placeholder="VD: 15.5m USD" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="NgayKhoiCong" class="block text-gray-700 text-sm font-bold mb-2">Ngày Khởi Công:</label>
                        <input type="text" id="NgayKhoiCong" placeholder="YYYY-MM-DD hoặc text" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="NgayHoanCong" class="block text-gray-700 text-sm font-bold mb-2">Ngày Hoàn Công:</label>
                        <input type="text" id="NgayHoanCong" placeholder="YYYY-MM-DD hoặc text" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="ChuDauTu" class="block text-gray-700 text-sm font-bold mb-2">Chủ Đầu Tư:</label>
                        <input type="text" id="ChuDauTu" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                     <div>
                        <label for="TongThau" class="block text-gray-700 text-sm font-bold mb-2">Tổng Thầu:</label>
                        <input type="text" id="TongThau" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="ThauMEP" class="block text-gray-700 text-sm font-bold mb-2">Thầu MEP:</label>
                        <input type="text" id="ThauMEP" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="DauMoiLienHe" class="block text-gray-700 text-sm font-bold mb-2">Đầu Mối Làm Việc:</label>
                        <input type="text" id="DauMoiLienHe" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Hạng Mục Báo Giá:</label>
                        <div id="hang-muc-checkbox-container" class="mt-2 p-2 border rounded-md max-h-40 overflow-y-auto space-y-2 custom-scrollbar">
                            <!-- Checkboxes sẽ được tải vào đây bằng JavaScript -->
                        </div>
                    </div>
                    <div>
                        <label for="GiaTriDuKien" class="block text-gray-700 text-sm font-bold mb-2">Giá Trị Dự Kiến (VND):</label>
                        <input type="number" id="GiaTriDuKien" placeholder="Chỉ nhập số, ví dụ: 150000000" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="TienDo" class="block text-gray-700 text-sm font-bold mb-2">Tiến Độ:</label>
                        <select id="TienDo" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn tiến độ --</option>
                            <option value="Thiết kế">Thiết kế</option>
                            <option value="Đấu thầu">Đấu thầu</option>
                            <option value="Thi công">Thi công</option>
                        </select>
                    </div>
                    <div>
                        <label for="KetQua" class="block text-gray-700 text-sm font-bold mb-2">Kết Quả:</label>
                        <select id="KetQua" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn kết quả --</option>
                            <option value="Mới">Mới</option>
                            <option value="Đang theo">Đang Theo</option>
                            <option value="Đấu thầu">Đấu Thầu</option>
                            <option value="Báo giá">Báo giá</option>
                            <option value="Chốt đơn">Chốt đơn</option>
                            <option value="Tạch">Tạch</option>
                            <option value="Đã hoàn thiện">Đã Hoàn Thiện</option>
                        </select>
                    </div>
                     <div>
                        <label for="SalePhuTrach" class="block text-gray-700 text-sm font-bold mb-2">Sale Phụ Trách:</label>
                        <select id="SalePhuTrach" class="shadow-sm border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Đang tải danh sách --</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-4 mt-8 pt-4 border-t">
                <button type="button" id="cancel-project-btn" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">Hủy</button>
                <button type="submit" id="save-project-btn" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">Lưu</button>
            </div>
        </form>
    </div>
</div>
 
<!-- View Project Modal -->
<div id="view-project-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 hidden justify-center items-start z-50 overflow-y-auto py-10">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl w-full max-w-4xl m-4">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Xem Chi Tiết Dự Án</h2>
            <button id="close-view-modal-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            
            <div class="col-span-1 md:col-span-2 lg:col-span-3 border-b pb-2 mb-2">
                <h3 class="text-lg font-semibold text-blue-600" id="view-TenDuAn"></h3>
            </div>

            <div><strong class="text-gray-600 w-28 inline-block">Mã Dự Án:</strong> <span id="view-MaDuAn"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Loại Hình:</strong> <span id="view-LoaiHinh"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Giá Trị Đầu Tư:</strong> <span id="view-GiaTriDauTu"></span></div>
            
            <div class="col-span-1 md:col-span-2 lg:col-span-3"><strong class="text-gray-600 w-28 inline-block">Địa Chỉ:</strong> <span id="view-DiaChi"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Tỉnh Thành:</strong> <span id="view-TinhThanh"></span></div>
            
            <div class="col-span-1 md:col-span-2 lg:col-span-3 border-t pt-4 mt-2"></div>

            <div><strong class="text-gray-600 w-28 inline-block">Khởi Công:</strong> <span id="view-NgayKhoiCong"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Hoàn Công:</strong> <span id="view-NgayHoanCong"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Chủ Đầu Tư:</strong> <span id="view-ChuDauTu"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Tổng Thầu:</strong> <span id="view-TongThau"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Thầu MEP:</strong> <span id="view-ThauMEP"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Đầu Mối:</strong> <span id="view-DauMoiLienHe"></span></div>

            <div class="col-span-1 md:col-span-2 lg:col-span-3 border-t pt-4 mt-2"></div>

            <div><strong class="text-gray-600 w-28 inline-block">Giá Trị Dự Kiến:</strong> <span id="view-GiaTriDuKien" class="font-bold text-green-700"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Tiến Độ:</strong> <span id="view-TienDoLamViec"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Kết Quả:</strong> <span id="view-KetQua"></span></div>
            <div><strong class="text-gray-600 w-28 inline-block">Sale Phụ Trách:</strong> <span id="view-SalePhuTrach"></span></div>
            
            <div class="col-span-1 md:col-span-2 lg:col-span-3 mt-2">
                <strong class="text-gray-600 block mb-2">Hạng Mục Báo Giá:</strong>
                <div id="view-HangMucBaoGia" class="flex flex-wrap gap-2"></div>
            </div>

        </div>
    </div>
</div>
 
<!-- Comment Modal -->
<div id="comment-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 hidden justify-center items-start z-50 overflow-y-auto py-10">
    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl w-full max-w-2xl m-4">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h2 id="comment-modal-title" class="text-2xl font-bold text-gray-800">Theo dõi Dự án</h2>
            <button id="close-comment-modal-btn" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        <!-- Danh sách bình luận -->
        <div id="comment-list" class="mb-6 max-h-80 overflow-y-auto custom-scrollbar pr-2 space-y-4">
            <!-- Comments will be loaded here -->
            <p class="text-center text-gray-500">Đang tải bình luận...</p>
        </div>

        <!-- Form thêm bình luận mới -->
        <form id="comment-form">
            <input type="hidden" id="comment-project-id">
            <div class="space-y-4">
                <div>
                    <label for="NguoiBinhLuan" class="block text-gray-700 text-sm font-bold mb-2">Tên của bạn:</label>
                    <input type="text" id="NguoiBinhLuan" placeholder="Nhập tên của bạn" class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="NoiDung" class="block text-gray-700 text-sm font-bold mb-2">Nội dung theo dõi:</label>
                    <textarea id="NoiDung" rows="4" placeholder="Nhập nội dung bình luận..." class="shadow-sm appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end mt-6 pt-4 border-t">
                <button type="submit" id="save-comment-btn" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">Lưu Comment</button>
            </div>
        </form>
    </div>
</div>

<div id="confirmation-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 hidden justify-center items-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm mx-4">
        <h3 class="text-lg font-bold mb-4" id="confirmation-title">Bạn có chắc không?</h3>
        <p id="confirmation-message" class="text-gray-600 mb-6">Hành động này không thể hoàn tác.</p>
        <div class="flex justify-end space-x-4">
            <button id="confirm-cancel-btn" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">Hủy</button>
            <button id="confirm-action-btn" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">Xác nhận</button>
        </div>
    </div>
</div>

<div id="message-modal" class="fixed top-5 right-5 p-4 rounded-lg shadow-lg z-[100] hidden transition-transform duration-300" style="transform: translateX(200%);">
    <p id="message-text" class="text-white"></p>
</div>

</body>
</ht