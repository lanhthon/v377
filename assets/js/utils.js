// js/utils.js
// Chứa các hàm tiện ích dùng chung (modal, format số, đọc số,...)
function goBackAndReload() {
       window.history.back();
    }
function formatNumber(num) {
    if (isNaN(num) || num === null || num === undefined) return '0';
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}
function goBack() {
    window.history.back();
}

function parseNumber(str) {
    const stringValue = String(str || '');
    return parseFloat(stringValue.replace(/,/g, '')) || 0;
}

function docSo(so) {
    if (isNaN(so) || so === 0) return "Không đồng";

    var mangso = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    function dochangchuc(so, daydu) {
        var chuoi = "";
        var chuc = Math.floor(so / 10);
        var donvi = so % 10;
        if (chuc > 1) {
            chuoi = " " + mangso[chuc] + " mươi";
            if (donvi == 1) {
                chuoi += " mốt";
            }
        } else if (chuc == 1) {
            chuoi = " mười";
            if (donvi == 1) {
                chuoi += " một";
            }
        } else if (daydu && donvi > 0) {
            chuoi = " linh";
        }
        if (donvi > 1 || (donvi == 1 && chuc == 0)) {
            chuoi += " " + mangso[donvi];
        }
        return chuoi;
    }

    function docblock(so, daydu) {
        var chuoi = "";
        var tram = Math.floor(so / 100);
        so = so % 100;
        if (daydu || tram > 0) {
            chuoi = " " + mangso[tram] + " trăm";
            chuoi += dochangchuc(so, true);
        } else {
            chuoi = dochangchuc(so, false);
        }
        return chuoi;
    }

    function dochangtrieu(so, daydu) {
        var chuoi = "";
        var trieu = Math.floor(so / 1000000);
        so = so % 1000000;
        if (trieu > 0) {
            chuoi = docblock(trieu, daydu) + " triệu";
            daydu = true;
        }
        var nghin = Math.floor(so / 1000);
        so = so % 1000;
        if (nghin > 0) {
            chuoi += docblock(nghin, daydu) + " nghìn";
            daydu = true;
        }
        if (so > 0) {
            chuoi += docblock(so, daydu);
        }
        return chuoi;
    }

    var ty = Math.floor(so / 1000000000);
    so = so % 1000000000;
    var chuoi = "";
    if (ty > 0) {
        chuoi = docblock(ty, false) + " tỷ";
        chuoi += dochangtrieu(so, true);
    } else {
        chuoi = dochangtrieu(so, false);
    }

    chuoi = chuoi.trim();
    if (chuoi.length > 0) {
        chuoi = chuoi.charAt(0).toUpperCase() + chuoi.slice(1) + " đồng chẵn.";
    }

    return chuoi;
}

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

function showMessageModal(message, type = 'info') {
    const title = type === 'success' ? 'Thành công!' : (type === 'error' ? 'Đã có lỗi' : 'Thông báo');
    createModal('message-modal', title, message, type, false);
    $('#message-modal').removeClass('hidden').addClass('flex');
    $('#message-modal-ok-btn').off('click').on('click', function () {
        $('#message-modal').remove();
    });
}

function showConfirmationModal(message, callback) {
    createModal('confirmation-modal', 'Xác nhận hành động', message, 'info', true);
    $('#confirmation-modal').removeClass('hidden').addClass('flex');
    $('#confirmation-modal-ok-btn').off('click').on('click', function () {
        $('#confirmation-modal').remove();
        if (callback) callback();
    });
    $('#confirmation-modal-cancel-btn').off('click').on('click', function () {
        $('#confirmation-modal').remove();
    });
}