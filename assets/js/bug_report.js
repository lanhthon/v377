/**
 * Bug Report Module - Green Theme với Tabs và Multi-File Upload
 * Quản lý việc báo lỗi và góp ý từ người dùng - Hỗ trợ nhiều loại file
 */

const BugReportModule = (function() {
    let selectedFiles = [];
    let currentExpandedReport = null;

    // Cấu hình file được phép
    const ALLOWED_FILE_TYPES = {
        // Images
        'image/jpeg': { ext: ['.jpg', '.jpeg'], icon: 'fa-image', color: 'text-blue-500', maxSize: 5 },
        'image/png': { ext: ['.png'], icon: 'fa-image', color: 'text-blue-500', maxSize: 5 },
        'image/gif': { ext: ['.gif'], icon: 'fa-image', color: 'text-blue-500', maxSize: 5 },
        'image/webp': { ext: ['.webp'], icon: 'fa-image', color: 'text-blue-500', maxSize: 5 },
        
        // Documents
        'application/pdf': { ext: ['.pdf'], icon: 'fa-file-pdf', color: 'text-red-500', maxSize: 10 },
        'application/msword': { ext: ['.doc'], icon: 'fa-file-word', color: 'text-blue-600', maxSize: 10 },
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': { ext: ['.docx'], icon: 'fa-file-word', color: 'text-blue-600', maxSize: 10 },
        
        // Spreadsheets
        'application/vnd.ms-excel': { ext: ['.xls'], icon: 'fa-file-excel', color: 'text-green-600', maxSize: 10 },
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': { ext: ['.xlsx'], icon: 'fa-file-excel', color: 'text-green-600', maxSize: 10 },
        'text/csv': { ext: ['.csv'], icon: 'fa-file-csv', color: 'text-green-500', maxSize: 5 },
        
        // Text
        'text/plain': { ext: ['.txt'], icon: 'fa-file-alt', color: 'text-gray-500', maxSize: 2 },
        
        // Archives
        'application/zip': { ext: ['.zip'], icon: 'fa-file-archive', color: 'text-yellow-600', maxSize: 20 },
        'application/x-rar-compressed': { ext: ['.rar'], icon: 'fa-file-archive', color: 'text-yellow-600', maxSize: 20 },
        'application/x-7z-compressed': { ext: ['.7z'], icon: 'fa-file-archive', color: 'text-yellow-600', maxSize: 20 },
        
        // Video (optional)
        'video/mp4': { ext: ['.mp4'], icon: 'fa-file-video', color: 'text-purple-500', maxSize: 50 },
        'video/quicktime': { ext: ['.mov'], icon: 'fa-file-video', color: 'text-purple-500', maxSize: 50 }
    };

    const MAX_FILES = 10;
    const ACCEPT_STRING = Object.values(ALLOWED_FILE_TYPES).flatMap(t => t.ext).join(',');

    // Khởi tạo modal HTML với Tabs
    function createBugReportModal() {
        const modalHTML = `
            <div id="bug-report-modal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
                <div class="bug-report-container bg-white rounded-lg shadow-2xl w-full mx-4">
                    <!-- Header -->
                    <div class="bug-report-header">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-white flex items-center">
                                    <i class="fas fa-bug mr-3"></i>
                                    Báo Lỗi / Góp Ý
                                </h2>
                                <p class="text-green-100 text-sm mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Đóng góp ý kiến..
                                </p>
                            </div>
                            <button id="close-bug-modal" class="text-white hover:text-red-300 transition-colors">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                        
                        <!-- Stats -->
                        <div class="mt-4 grid grid-cols-3 gap-3" id="bug-stats">
                            <div class="bg-white bg-opacity-20 rounded-lg p-2 text-center">
                                <div class="text-2xl font-bold text-white" id="stat-total">0</div>
                                <div class="text-xs text-green-100">Tổng báo cáo</div>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-lg p-2 text-center">
                                <div class="text-2xl font-bold text-yellow-300" id="stat-pending">0</div>
                                <div class="text-xs text-green-100">Đang xử lý</div>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-lg p-2 text-center">
                                <div class="text-2xl font-bold text-green-300" id="stat-resolved">0</div>
                                <div class="text-xs text-green-100">Đã giải quyết</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <div class="bg-gray-100 px-6 flex border-b border-gray-300">
                        <button class="tab-btn active px-6 py-3 font-semibold text-gray-700 border-b-2 border-green-600 hover:bg-white transition-all" data-tab="list">
                            <i class="fas fa-list mr-2"></i>Danh sách báo lỗi/Góp ý
                        </button>
                        <button class="tab-btn px-6 py-3 font-semibold text-gray-500 border-b-2 border-transparent hover:bg-white hover:text-gray-700 transition-all" data-tab="create">
                            <i class="fas fa-plus-circle mr-2"></i>Tạo báo lỗi mới/góp ý
                        </button>
                    </div>

                    <!-- Tab Content Container -->
                    <div class="tab-content-wrapper">
                        <!-- Tab 1: Danh sách báo lỗi -->
                        <div id="tab-list" class="tab-content active">
                            <div class="bug-report-list" id="bug-report-list">
                                <div class="bug-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Đang tải dữ liệu...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Form tạo báo lỗi mới -->
                        <div id="tab-create" class="tab-content hidden">
                            <div class="bug-report-form">
                                <div class="mb-4">
                                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                                        <i class="fas fa-edit text-green-600 mr-2"></i>
                                        Tạo Báo Cáo Mới
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1">Vui lòng mô tả chi tiết vấn đề bạn gặp phải/Góp ý</p>
                                </div>
                                
                                <form id="bug-report-form">
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-heading text-green-600 mr-1"></i>Tiêu đề 
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="bug-title" 
                                               class="w-full px-4 py-3 border-2 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                               placeholder="Ví dụ: Lỗi không tải được danh sách đơn hàng..." required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-align-left text-green-600 mr-1"></i>Chi tiết 
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="bug-description" rows="5"
                                                  class="w-full px-4 py-3 border-2 rounded-lg resize-none focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                                  placeholder="Mô tả chi tiết:&#10;- Bạn đang làm gì khi gặp lỗi?&#10;- Lỗi xảy ra như thế nào?&#10;- Bạn mong muốn hệ thống hoạt động ra sao?" required></textarea>
                                    </div>
                                    
                                    <!-- Priority Selection -->
                                    <div class="mb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-flag text-green-600 mr-1"></i>Mức độ ưu tiên
                                        </label>
                                        <select id="bug-priority" class="w-full px-4 py-3 border-2 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200">
                                            <option value="Thấp">🟢 Thấp - Không ảnh hưởng nhiều đến công việc</option>
                                            <option value="Trung bình" selected>🟡 Trung bình - Ảnh hưởng vừa phải, cần xử lý sớm</option>
                                            <option value="Cao">🟠 Cao - Ảnh hưởng nhiều, cần xử lý ngay</option>
                                            <option value="Khẩn cấp">🔴 Khẩn cấp - Gây gián đoạn nghiêm trọng</option>
                                        </select>
                                    </div>

                                    <!-- Multi-File Upload -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-paperclip text-green-600 mr-1"></i>Đính kèm file
                                            <span class="text-gray-500 text-xs font-normal">(Tùy chọn, tối đa ${MAX_FILES} file)</span>
                                        </label>
                                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-green-500 transition-all bg-gray-50">
                                            <div class="text-center">
                                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                                <label for="bug-files" class="cursor-pointer">
                                                    <span class="text-green-600 hover:text-green-700 font-semibold">
                                                        Click để chọn file
                                                    </span>
                                                    <span class="text-gray-600"> hoặc kéo thả file vào đây</span>
                                                </label>
                                                <input type="file" id="bug-files" accept="${ACCEPT_STRING}" multiple class="hidden">
                                            </div>
                                            <div class="mt-3 text-xs text-gray-500 space-y-1">
                                                <p><i class="fas fa-info-circle mr-1"></i><strong>Hỗ trợ:</strong> Ảnh (JPG, PNG, GIF), PDF, Word, Excel, Text, ZIP, Video</p>
                                                <p><i class="fas fa-exclamation-circle mr-1"></i><strong>Kích thước:</strong> Ảnh/Text: 5MB, Documents: 10MB, Video/ZIP: 50MB</p>
                                            </div>
                                        </div>
                                        <div id="file-preview-container" class="mt-4"></div>
                                    </div>

                                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                        <button type="button" id="back-to-list"
                                                class="px-5 py-2.5 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                            <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
                                        </button>
                                        <div class="flex gap-3">
                                            <button type="button" id="cancel-bug-report"
                                                    class="px-5 py-2.5 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-medium">
                                                <i class="fas fa-times mr-2"></i>Làm mới
                                            </button>
                                            <button type="submit" id="submit-bug-report"
                                                    class="px-6 py-2.5 btn-green rounded-lg transition-all font-medium">
                                                <i class="fas fa-paper-plane mr-2"></i>Gửi Báo Cáo
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHTML);
        
        initTabSwitching();
        initDragAndDrop();
    }

    // Xử lý chuyển tab
    function initTabSwitching() {
        $('.tab-btn').on('click', function() {
            const targetTab = $(this).data('tab');
            switchTab(targetTab);
        });
        
        $('#back-to-list').on('click', function() {
            switchTab('list');
        });
    }

    function switchTab(tabName) {
        $('.tab-btn').removeClass('active border-green-600 text-gray-700')
                     .addClass('border-transparent text-gray-500');
        $(`.tab-btn[data-tab="${tabName}"]`).addClass('active border-green-600 text-gray-700')
                                             .removeClass('border-transparent text-gray-500');
        
        $('.tab-content').removeClass('active').addClass('hidden');
        $(`#tab-${tabName}`).removeClass('hidden').addClass('active');
    }

    // Drag and Drop functionality
    function initDragAndDrop() {
        const dropZone = $('#bug-files').parent().parent();
        
        dropZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-green-500 bg-green-50');
        });

        dropZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-green-500 bg-green-50');
        });

        dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-green-500 bg-green-50');
            
            const files = e.originalEvent.dataTransfer.files;
            handleFileSelection(files);
        });
    }

    // Validate và xử lý file
    function validateFile(file) {
        const fileType = file.type || getMimeTypeFromExtension(file.name);
        const fileInfo = ALLOWED_FILE_TYPES[fileType];
        
        if (!fileInfo) {
            return { valid: false, error: `File "${file.name}" không được hỗ trợ` };
        }
        
        const maxSizeMB = fileInfo.maxSize;
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        
        if (file.size > maxSizeBytes) {
            return { valid: false, error: `File "${file.name}" vượt quá ${maxSizeMB}MB` };
        }
        
        return { valid: true, fileInfo: fileInfo };
    }

    function getMimeTypeFromExtension(filename) {
        const ext = filename.toLowerCase().match(/\.[^.]+$/)?.[0];
        for (const [mimeType, info] of Object.entries(ALLOWED_FILE_TYPES)) {
            if (info.ext.includes(ext)) {
                return mimeType;
            }
        }
        return null;
    }

    function handleFileSelection(files) {
        const fileArray = Array.from(files);
        const errors = [];
        
        if (selectedFiles.length + fileArray.length > MAX_FILES) {
            App.showMessageModal(`Chỉ được chọn tối đa ${MAX_FILES} file`, 'error');
            return;
        }
        
        fileArray.forEach(file => {
            const validation = validateFile(file);
            if (validation.valid) {
                selectedFiles.push({
                    file: file,
                    info: validation.fileInfo
                });
            } else {
                errors.push(validation.error);
            }
        });
        
        if (errors.length > 0) {
            App.showMessageModal(errors.join('<br>'), 'error');
        }
        
        updateFilePreview();
    }

    // Hiển thị preview file
    function updateFilePreview() {
        const container = $('#file-preview-container');
        container.empty();
        
        if (selectedFiles.length === 0) {
            return;
        }
        
        container.append(`
            <div class="mb-3 flex items-center justify-between">
                <h4 class="font-semibold text-gray-700">
                    <i class="fas fa-paperclip mr-2"></i>File đã chọn (${selectedFiles.length}/${MAX_FILES})
                </h4>
                <button type="button" class="text-red-500 hover:text-red-700 text-sm font-medium" id="clear-all-files">
                    <i class="fas fa-trash mr-1"></i>Xóa tất cả
                </button>
            </div>
        `);
        
        const grid = $('<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3"></div>');
        
        selectedFiles.forEach((item, index) => {
            const file = item.file;
            const info = item.info;
            const isImage = file.type.startsWith('image/');
            
            const preview = $(`
                <div class="file-preview-item border-2 border-gray-200 rounded-lg p-3 hover:border-green-500 transition-all bg-white relative group">
                    <button type="button" class="remove-file-btn absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity" data-index="${index}">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <div class="flex flex-col items-center">
                        ${isImage ? 
                            `<div class="w-full h-32 mb-2 overflow-hidden rounded-lg bg-gray-100 file-image-container" data-index="${index}">
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                </div>
                            </div>` :
                            `<div class="w-16 h-16 mb-2 flex items-center justify-center">
                                <i class="fas ${info.icon} text-5xl ${info.color}"></i>
                            </div>`
                        }
                        <p class="text-xs text-gray-700 font-medium text-center truncate w-full" title="${file.name}">
                            ${file.name}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            ${formatFileSize(file.size)}
                        </p>
                    </div>
                </div>
            `);
            
            grid.append(preview);
            
            // Load image preview
            if (isImage) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(`.file-image-container[data-index="${index}"]`).html(`
                        <img src="${e.target.result}" class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform" onclick="window.open('${e.target.result}', '_blank')">
                    `);
                };
                reader.readAsDataURL(file);
            }
        });
        
        container.append(grid);
    }

    // Remove file
    $(document).on('click', '.remove-file-btn', function(e) {
        e.stopPropagation();
        const index = $(this).data('index');
        selectedFiles.splice(index, 1);
        updateFilePreview();
    });

    $(document).on('click', '#clear-all-files', function() {
        selectedFiles = [];
        updateFilePreview();
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Load danh sách báo lỗi
    function loadBugReports() {
        $('#bug-report-list').html('<div class="bug-loading"><i class="fas fa-spinner fa-spin"></i><p>Đang tải dữ liệu...</p></div>');
        
        $.ajax({
            url: 'api/bug_reports.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayBugReports(response.data);
                    updateStats(response.data);
                } else {
                    $('#bug-report-list').html(`
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>${response.message || 'Không thể tải dữ liệu'}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#bug-report-list').html(`
                    <div class="empty-state text-red-500">
                        <i class="fas fa-times-circle"></i>
                        <p>Lỗi kết nối server</p>
                    </div>
                `);
            }
        });
    }

    // Update statistics
    function updateStats(reports) {
        const total = reports.length;
        const pending = reports.filter(r => ['Mới', 'Đã tiếp nhận', 'Đang xử lý'].includes(r.Status)).length;
        const resolved = reports.filter(r => r.Status === 'Đã giải quyết').length;
        
        $('#stat-total').text(total);
        $('#stat-pending').text(pending);
        $('#stat-resolved').text(resolved);
    }

    // Display bug reports
    function displayBugReports(reports) {
        if (!reports || reports.length === 0) {
            $('#bug-report-list').html(`
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có báo cáo nào</p>
                    <p class="text-sm mt-2">Hãy tạo báo cáo đầu tiên của bạn!</p>
                    <button class="mt-4 px-6 py-3 btn-green rounded-lg font-medium create-first-report-btn">
                        <i class="fas fa-plus-circle mr-2"></i>Tạo báo cáo đầu tiên
                    </button>
                </div>
            `);
            
            $('.create-first-report-btn').on('click', function() {
                switchTab('create');
            });
            return;
        }

        let html = '';
        reports.forEach(report => {
            const statusClass = getStatusClass(report.Status);
            const priorityClass = getPriorityClass(report.Priority);
            const statusIcon = getStatusIcon(report.Status);
            
            html += `
                <div class="bug-report-item" data-report-id="${report.BugReportID}">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-semibold text-gray-800 text-base">${escapeHtml(report.Title)}</h4>
                            </div>
                            <div class="flex items-center flex-wrap gap-2 text-xs text-gray-500">
                                <span><i class="far fa-clock mr-1"></i>${formatDate(report.CreatedAt)}</span>
                                <span class="bug-report-status ${statusClass}">
                                    ${statusIcon} ${report.Status}
                                </span>
                                <span class="priority-badge ${priorityClass}">
                                    ${getPriorityIcon(report.Priority)} ${report.Priority}
                                </span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform report-toggle"></i>
                    </div>
                    
                    <div class="report-details hidden mt-3">
                        <div class="mb-3">
                            <p class="text-sm font-medium text-gray-600 mb-1">
                                <i class="fas fa-info-circle text-green-600 mr-1"></i>Mô tả chi tiết:
                            </p>
                            <p class="text-gray-700 text-sm leading-relaxed">${escapeHtml(report.Description).replace(/\n/g, '<br>')}</p>
                        </div>
                        
                        ${report.ImagePath ? displayAttachments(report.ImagePath) : ''}
                        
                        ${report.AdminNote ? `
                            <div class="admin-note-section">
                                <div class="note-header">
                                    <i class="fas fa-user-shield"></i>
                                    Phản hồi từ Admin
                                </div>
                                <div class="note-content">
                                    ${escapeHtml(report.AdminNote).replace(/\n/g, '<br>')}
                                </div>
                                ${report.ResolvedAt ? `
                                    <div class="text-xs text-amber-700 mt-2">
                                        <i class="far fa-clock mr-1"></i>
                                        Giải quyết lúc: ${formatDateTime(report.ResolvedAt)}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        $('#bug-report-list').html(html);
        attachReportEvents();
    }

    // Display attachments with file type detection
    function displayAttachments(filePaths) {
        const files = filePaths.split(',').map(f => f.trim());
        let html = '<div class="mb-3"><p class="text-sm font-medium text-gray-600 mb-2"><i class="fas fa-paperclip text-green-600 mr-1"></i>File đính kèm:</p><div class="grid grid-cols-2 md:grid-cols-4 gap-2">';
        
        files.forEach(filePath => {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(filePath);
            const isPdf = /\.pdf$/i.test(filePath);
            const isDoc = /\.(doc|docx)$/i.test(filePath);
            const isExcel = /\.(xls|xlsx|csv)$/i.test(filePath);
            const isVideo = /\.(mp4|mov|avi)$/i.test(filePath);
            const isArchive = /\.(zip|rar|7z)$/i.test(filePath);
            
            if (isImage) {
                html += `
                    <img src="${filePath}" alt="Attachment" 
                         class="w-full h-24 object-cover rounded-lg border-2 border-green-200 cursor-pointer hover:border-green-500 transition-all"
                         onclick="window.open('${filePath}', '_blank')">
                `;
            } else {
                let icon = 'fa-file';
                let color = 'text-gray-500';
                
                if (isPdf) { icon = 'fa-file-pdf'; color = 'text-red-500'; }
                else if (isDoc) { icon = 'fa-file-word'; color = 'text-blue-600'; }
                else if (isExcel) { icon = 'fa-file-excel'; color = 'text-green-600'; }
                else if (isVideo) { icon = 'fa-file-video'; color = 'text-purple-500'; }
                else if (isArchive) { icon = 'fa-file-archive'; color = 'text-yellow-600'; }
                
                const fileName = filePath.split('/').pop();
                html += `
                    <a href="${filePath}" target="_blank" 
                       class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-lg hover:border-green-500 transition-all bg-white">
                        <i class="fas ${icon} text-3xl ${color} mb-2"></i>
                        <span class="text-xs text-gray-700 text-center truncate w-full" title="${fileName}">${fileName}</span>
                    </a>
                `;
            }
        });
        
        html += '</div></div>';
        return html;
    }

    // Attach events
    function attachReportEvents() {
        $('.bug-report-item').off('click').on('click', function(e) {
            const $details = $(this).find('.report-details');
            const $toggle = $(this).find('.report-toggle');
            
            $('.report-details').not($details).slideUp(200);
            $('.report-toggle').not($toggle).removeClass('rotate-180');
            $('.bug-report-item').not(this).removeClass('expanded');
            
            $details.slideToggle(200);
            $toggle.toggleClass('rotate-180');
            $(this).toggleClass('expanded');
        });
    }

    // Handle form submit
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const title = $('#bug-title').val().trim();
        const description = $('#bug-description').val().trim();
        const priority = $('#bug-priority').val();

        if (!title || !description) {
            App.showMessageModal('Vui lòng điền đầy đủ thông tin', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('title', title);
        formData.append('description', description);
        formData.append('priority', priority);
        
        selectedFiles.forEach((item, index) => {
            formData.append('files[]', item.file);
        });

        $('#submit-bug-report').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang gửi...');

        $.ajax({
            url: 'api/bug_reports.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal('Đã gửi báo cáo thành công! .', 'success');
                    $('#bug-report-form')[0].reset();
                    selectedFiles = [];
                    updateFilePreview();
                    loadBugReports();
                    switchTab('list');
                } else {
                    App.showMessageModal(response.message || 'Không thể gửi báo lỗi', 'error');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi kết nối server', 'error');
            },
            complete: function() {
                $('#submit-bug-report').prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Gửi Báo Cáo');
            }
        });
    }

    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'Mới': 'new',
            'Đã tiếp nhận': 'received',
            'Đang xử lý': 'processing',
            'Đã giải quyết': 'resolved',
            'Đã đóng': 'closed'
        };
        return classes[status] || 'new';
    }

    function getStatusIcon(status) {
        const icons = {
            'Mới': '🆕',
            'Đã tiếp nhận': '✅',
            'Đang xử lý': '⚙️',
            'Đã giải quyết': '✔️',
            'Đã đóng': '🔒'
        };
        return icons[status] || '📝';
    }

    function getPriorityClass(priority) {
        const classes = {
            'Thấp': 'low',
            'Trung bình': 'medium',
            'Cao': 'high',
            'Khẩn cấp': 'urgent'
        };
        return classes[priority] || 'medium';
    }

    function getPriorityIcon(priority) {
        const icons = {
            'Thấp': '🟢',
            'Trung bình': '🟡',
            'Cao': '🟠',
            'Khẩn cấp': '🔴'
        };
        return icons[priority] || '⚪';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Vừa xong';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' phút trước';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' giờ trước';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' ngày trước';
        
        return date.toLocaleDateString('vi-VN');
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Initialize module
    function init() {
        createBugReportModal();

        $('#bug-report-btn').on('click', function() {
            $('#bug-report-modal').removeClass('hidden').addClass('flex');
            loadBugReports();
            switchTab('list');
        });

        $('#close-bug-modal').on('click', function() {
            $('#bug-report-modal').removeClass('flex').addClass('hidden');
        });

        $('#cancel-bug-report').on('click', function() {
            $('#bug-report-form')[0].reset();
            selectedFiles = [];
            updateFilePreview();
        });

        $('#bug-report-form').on('submit', handleFormSubmit);
        $('#bug-files').on('change', function() {
            handleFileSelection(this.files);
        });

        $('#bug-report-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('flex').addClass('hidden');
            }
        });
    }

    return {
        init: init
    };
})();

$(document).ready(function() {
    BugReportModule.init();
});