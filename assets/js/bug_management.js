/**
 * Bug Management Module - Admin/Dev
 * Quản lý và xử lý các báo lỗi từ user
 */

function initializeBugManagementPage() {
    let allBugs = [];
    let filteredBugs = [];

    // Load bugs khi vào trang
    loadBugs();

    // Event listeners
    $('#refresh-bugs').on('click', loadBugs);
    $('#filter-status').on('change', applyFilters);
    $('#filter-priority').on('change', applyFilters);
    $('#search-bugs').on('input', debounce(applyFilters, 300));

    // Click stat cards để filter
    $('.stat-card').on('click', function() {
        const status = $(this).hasClass('stat-new') ? 'Mới' :
                      $(this).hasClass('stat-received') ? 'Đã tiếp nhận' :
                      $(this).hasClass('stat-processing') ? 'Đang xử lý' :
                      $(this).hasClass('stat-resolved') ? 'Đã giải quyết' : '';
        $('#filter-status').val(status);
        applyFilters();
    });

    /**
     * Load tất cả báo lỗi
     */
    function loadBugs() {
        $('#bug-list-container').html(`
            <div class="loading-container">
                <i class="fas fa-spinner fa-spin text-4xl text-green-600"></i>
                <p class="text-gray-600 mt-3">Đang tải dữ liệu...</p>
            </div>
        `);

        $.ajax({
            url: 'api/bug_reports_admin.php',
            method: 'GET',
            data: { action: 'get_all' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    allBugs = response.data;
                    updateStats(allBugs);
                    applyFilters();
                } else {
                    showError(response.message || 'Không thể tải dữ liệu');
                }
            },
            error: function() {
                showError('Lỗi kết nối server');
            }
        });
    }

    /**
     * Update statistics
     */
    function updateStats(bugs) {
        const stats = {
            new: bugs.filter(b => b.Status === 'Mới').length,
            received: bugs.filter(b => b.Status === 'Đã tiếp nhận').length,
            processing: bugs.filter(b => b.Status === 'Đang xử lý').length,
            resolved: bugs.filter(b => b.Status === 'Đã giải quyết').length
        };

        $('#stat-new').text(stats.new);
        $('#stat-received').text(stats.received);
        $('#stat-processing').text(stats.processing);
        $('#stat-resolved').text(stats.resolved);
    }

    /**
     * Apply filters
     */
    function applyFilters() {
        const statusFilter = $('#filter-status').val();
        const priorityFilter = $('#filter-priority').val();
        const searchText = $('#search-bugs').val().toLowerCase();

        filteredBugs = allBugs.filter(bug => {
            const matchStatus = !statusFilter || bug.Status === statusFilter;
            const matchPriority = !priorityFilter || bug.Priority === priorityFilter;
            const matchSearch = !searchText || 
                bug.Title.toLowerCase().includes(searchText) ||
                bug.Description.toLowerCase().includes(searchText);

            return matchStatus && matchPriority && matchSearch;
        });

        displayBugs(filteredBugs);
    }

    /**
     * Display bug list
     */
    function displayBugs(bugs) {
        const container = $('#bug-list-container');

        if (!bugs || bugs.length === 0) {
            container.html(`
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p class="text-lg font-semibold">Không tìm thấy báo lỗi nào</p>
                    <p class="text-sm mt-2">Thử thay đổi bộ lọc hoặc tìm kiếm</p>
                </div>
            `);
            return;
        }

        let html = '';
        bugs.forEach(bug => {
            const statusClass = getStatusClass(bug.Status);
            const priorityClass = getPriorityClass(bug.Priority);
            const timeAgo = formatTimeAgo(bug.CreatedAt);

            html += `
                <div class="bug-card" data-bug-id="${bug.BugReportID}">
                    <div class="bug-card-header">
                        <h3 class="bug-title">${escapeHtml(bug.Title)}</h3>
                    </div>
                    
                    <div class="bug-meta">
                        <span class="bug-user">
                            <i class="fas fa-user text-blue-600"></i>
                            ${escapeHtml(bug.UserName)}
                        </span>
                        
                        <span class="bug-report-status ${statusClass}">
                            ${getStatusIcon(bug.Status)} ${bug.Status}
                        </span>
                        
                        <span class="priority-badge ${priorityClass}">
                            ${getPriorityIcon(bug.Priority)} ${bug.Priority}
                        </span>
                        
                        <span class="text-gray-500">
                            <i class="far fa-clock mr-1"></i>${timeAgo}
                        </span>
                        
                        ${bug.Comments && bug.Comments.length > 0 ? `
                            <span class="text-gray-500">
                                <i class="fas fa-comments mr-1"></i>${bug.Comments.length}
                            </span>
                        ` : ''}
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">
                        ${escapeHtml(bug.Description)}
                    </p>
                </div>
            `;
        });

        container.html(html);

        // Attach click event to bug cards
        $('.bug-card').on('click', function() {
            const bugId = $(this).data('bug-id');
            showBugDetail(bugId);
        });
    }

    /**
     * Show bug detail modal
     */
    function showBugDetail(bugId) {
        const bug = allBugs.find(b => b.BugReportID == bugId);
        if (!bug) return;

        const modalContent = `
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 rounded-t-lg">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-white mb-2">${escapeHtml(bug.Title)}</h2>
                        <div class="flex flex-wrap gap-2">
                            <span class="bug-report-status ${getStatusClass(bug.Status)} bg-white">
                                ${getStatusIcon(bug.Status)} ${bug.Status}
                            </span>
                            <span class="priority-badge ${getPriorityClass(bug.Priority)} bg-white">
                                ${getPriorityIcon(bug.Priority)} ${bug.Priority}
                            </span>
                        </div>
                    </div>
                    <button onclick="$('#bug-detail-modal').addClass('hidden')" 
                            class="text-white hover:text-red-300 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <!-- User Info -->
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-user-circle text-3xl text-blue-600"></i>
                        <div>
                            <p class="font-semibold text-gray-800">${escapeHtml(bug.UserName)}</p>
                            <p class="text-sm text-gray-600">
                                <i class="far fa-clock mr-1"></i>
                                ${formatDateTime(bug.CreatedAt)}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                        <i class="fas fa-info-circle text-green-600 mr-2"></i>
                        Mô tả chi tiết
                    </h3>
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">${escapeHtml(bug.Description)}</p>
                </div>

                <!-- Images -->
                ${bug.ImagePath ? `
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-images text-green-600 mr-2"></i>
                            Ảnh đính kèm
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            ${bug.ImagePath.split(',').map(img => `
                                <img src="${img.trim()}" 
                                     class="w-full h-32 object-cover rounded-lg border-2 border-green-200 cursor-pointer hover:border-green-500 transition-all"
                                     onclick="window.open('${img.trim()}', '_blank')">
                            `).join('')}
                        </div>
                    </div>
                ` : ''}

                <!-- Admin Note -->
                ${bug.AdminNote ? `
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded mb-4">
                        <h3 class="font-semibold text-yellow-800 mb-2 flex items-center">
                            <i class="fas fa-user-shield mr-2"></i>
                            Ghi chú của Admin
                        </h3>
                        <p class="text-gray-700 whitespace-pre-wrap">${escapeHtml(bug.AdminNote)}</p>
                        ${bug.ResolvedAt ? `
                            <p class="text-sm text-yellow-700 mt-2">
                                <i class="far fa-clock mr-1"></i>
                                Giải quyết lúc: ${formatDateTime(bug.ResolvedAt)}
                            </p>
                        ` : ''}
                    </div>
                ` : ''}

                <!-- Comments -->
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-comments text-green-600 mr-2"></i>
                        Bình luận (${(bug.Comments || []).length})
                    </h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto mb-3">
                        ${displayComments(bug.Comments || [])}
                    </div>
                    
                    <!-- Add Comment Form -->
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <textarea id="admin-comment-text" rows="3" 
                                  class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                  placeholder="Thêm bình luận..."></textarea>
                        <div class="flex justify-between items-center mt-2">
                            <input type="file" id="admin-comment-image" accept="image/*" class="text-sm">
                            <button onclick="addAdminComment(${bug.BugReportID})" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-comment mr-1"></i>Gửi
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-cog text-green-600 mr-2"></i>
                        Cập nhật trạng thái
                    </h3>
                    
                    <div class="grid md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái</label>
                            <select id="update-status" class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500">
                                <option value="Mới" ${bug.Status === 'Mới' ? 'selected' : ''}>🆕 Mới</option>
                                <option value="Đã tiếp nhận" ${bug.Status === 'Đã tiếp nhận' ? 'selected' : ''}>✅ Đã tiếp nhận</option>
                                <option value="Đang xử lý" ${bug.Status === 'Đang xử lý' ? 'selected' : ''}>⚙️ Đang xử lý</option>
                                <option value="Đã giải quyết" ${bug.Status === 'Đã giải quyết' ? 'selected' : ''}>✔️ Đã giải quyết</option>
                                <option value="Đã đóng" ${bug.Status === 'Đã đóng' ? 'selected' : ''}>🔒 Đã đóng</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ưu tiên</label>
                            <select id="update-priority" class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500">
                                <option value="Thấp" ${bug.Priority === 'Thấp' ? 'selected' : ''}>🟢 Thấp</option>
                                <option value="Trung bình" ${bug.Priority === 'Trung bình' ? 'selected' : ''}>🟡 Trung bình</option>
                                <option value="Cao" ${bug.Priority === 'Cao' ? 'selected' : ''}>🟠 Cao</option>
                                <option value="Khẩn cấp" ${bug.Priority === 'Khẩn cấp' ? 'selected' : ''}>🔴 Khẩn cấp</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Ghi chú Admin</label>
                        <textarea id="admin-note" rows="3" 
                                  class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                                  placeholder="Thêm ghi chú cho user...">${bug.AdminNote || ''}</textarea>
                    </div>
                    
                    <button onclick="updateBugStatus(${bug.BugReportID})" 
                            class="w-full px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Lưu thay đổi
                    </button>
                </div>
            </div>
        `;

        $('#bug-detail-content').html(modalContent);
        $('#bug-detail-modal').removeClass('hidden');
    }

    /**
     * Display comments
     */
    function displayComments(comments) {
        if (!comments || comments.length === 0) {
            return '<p class="text-gray-500 text-sm italic text-center py-4">Chưa có bình luận nào</p>';
        }

        return comments.map(comment => {
            const isAdmin = comment.IsAdmin == 1;
            return `
                <div class="bug-comment ${isAdmin ? 'admin-comment' : 'user-comment'}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="comment-author">
                            ${isAdmin ? '<i class="fas fa-shield-alt mr-1"></i>' : '<i class="fas fa-user mr-1"></i>'}
                            ${escapeHtml(comment.HoTen)}
                        </span>
                        <span class="text-xs text-gray-500">${formatDateTime(comment.CreatedAt)}</span>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(comment.Comment)}</p>
                    ${comment.ImagePath ? `
                        <img src="${comment.ImagePath}" 
                             class="mt-2 max-w-xs h-auto rounded-lg border-2 border-gray-200 cursor-pointer"
                             onclick="window.open('${comment.ImagePath}', '_blank')">
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    /**
     * Add admin comment
     */
    window.addAdminComment = function(bugId) {
        const comment = $('#admin-comment-text').val().trim();
        const imageFile = $('#admin-comment-image')[0].files[0];

        if (!comment) {
            App.showMessageModal('Vui lòng nhập nội dung bình luận', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('report_id', bugId);
        formData.append('comment', comment);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        $.ajax({
            url: 'api/bug_reports_admin.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal('Đã thêm bình luận', 'success');
                    $('#bug-detail-modal').addClass('hidden');
                    loadBugs();
                } else {
                    App.showMessageModal(response.message || 'Không thể thêm bình luận', 'error');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi kết nối server', 'error');
            }
        });
    };

    /**
     * Update bug status
     */
    window.updateBugStatus = function(bugId) {
        const status = $('#update-status').val();
        const priority = $('#update-priority').val();
        const adminNote = $('#admin-note').val().trim();

        $.ajax({
            url: 'api/bug_reports_admin.php',
            method: 'POST',
            data: {
                action: 'update_status',
                report_id: bugId,
                status: status,
                priority: priority,
                admin_note: adminNote
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    App.showMessageModal('Đã cập nhật thành công', 'success');
                    $('#bug-detail-modal').addClass('hidden');
                    loadBugs();
                } else {
                    App.showMessageModal(response.message || 'Không thể cập nhật', 'error');
                }
            },
            error: function() {
                App.showMessageModal('Lỗi kết nối server', 'error');
            }
        });
    };

    /**
     * Helper functions
     */
    function showError(message) {
        $('#bug-list-container').html(`
            <div class="empty-state text-red-500">
                <i class="fas fa-exclamation-triangle"></i>
                <p class="text-lg font-semibold">${message}</p>
            </div>
        `);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

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

    function formatTimeAgo(dateString) {
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

    // Close modal when clicking outside
    $('#bug-detail-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden');
        }
    });
}