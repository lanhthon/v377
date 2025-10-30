/**
 * =================================================================================
 * BÁO GIÁ MANAGEMENT SCRIPT (VERSION 4.0 - LẤY MÃ CÔNG TY)
 * =================================================================================
 * - Bắt buộc chọn khách hàng từ danh sách gợi ý để lấy ID và Mã Công Ty.
 * - Số báo giá được tạo tự động dựa trên Mã Công Ty thật, không phải tên viết tắt.
 * - Nếu người dùng sửa tên công ty sau khi đã chọn, lựa chọn sẽ bị hủy.
 * - Thêm bước kiểm tra khách hàng hợp lệ trước khi lưu báo giá.
 * =================================================================================
 */

// =================================================================
// CÁC HÀM TIỆN ÍCH (HELPERS)
// =================================================================

function docSo(so) {
    if (so === null || so === undefined || isNaN(so) || so === 0) return "Không đồng chẵn";
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
            chuoi = " lẻ";
        }
        if (donvi == 5 && chuc > 1) {
            chuoi += " lăm";
        } else if (donvi > 1 || (donvi == 1 && chuc == 0)) {
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
    if (so == 0) return "Không đồng chẵn";
    var chuoi = "", hauto = "";
    do {
        var ty = so % 1000000000;
        so = Math.floor(so / 1000000000);
        if (so > 0) {
            chuoi = dochangtrieu(ty, true) + hauto + chuoi;
        } else {
            chuoi = dochangtrieu(ty, false) + hauto + chuoi;
        }
        hauto = " tỷ";
    } while (so > 0);
    chuoi = chuoi.trim();
    if (chuoi.length > 0) {
        chuoi = chuoi[0].toUpperCase() + chuoi.substr(1);
    }
    return chuoi + " đồng chẵn";
}

function formatNumber(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function parseNumber(str) {
    if (typeof str === 'number') return str;
    if (!str) return 0;
    return parseFloat(String(str).replace(/,/g, '')) || 0;
}


// Làm tròn LÊN theo bậc cố định (mặc định: hàng chục = 10).
// Nếu muốn hàng trăm thì đổi ROUND_STEP = 100.
const ROUND_STEP = 10;
function roundUpToStep(v, step = ROUND_STEP) {
    v = parseNumber(v);
    if (!v || v <= 0) return 0;
    return Math.ceil(v / step) * step;
}


// Hiển thị gợi ý 'mặc định' cạnh ô % điều chỉnh
function showAdjustmentDefaultHint(defPercent) {
    try {
        const $inp = $('#price-adjustment-percentage-input');
        if ($inp.length === 0) return;
        $inp.attr('placeholder', `mặc định: ${defPercent}%`);
        let $hint = $('#adjustment-default-hint');
        if ($hint.length === 0) {
            $hint = $('<small id="adjustment-default-hint" class="text-gray-500 ml-2 italic"></small>');
            $inp.after($hint);
        }
        $hint.text(`(mặc định: ${defPercent}%)`);
    } catch (e) { /* no-op */ }
}
function getDeliveryTime(total) {
    if (total < 20000000) return "2-3 ngày sau khi nhận được xác nhận đặt hàng.";
    if (total >= 20000000 && total < 50000000) return "3-5 ngày sau khi nhận được xác nhận đặt hàng.";
    if (total >= 50000000 && total < 100000000) return "4-6 ngày sau khi nhận được xác nhận đặt hàng.";
    if (total >= 100000000 && total < 200000000) return "5-7 ngày sau khi nhận được xác nhận đặt hàng.";
    if (total >= 200000000) return "Chia giao 2-3 đợt. Đợt 1: 3-5 ngày, Đợt 2: 4-6 ngày tiếp theo sau khi nhận được xác nhận đặt hàng.";
    return "Theo thỏa thuận";
}

// =================================================================
// HÀM KHỞI TẠO CHÍNH CỦA TRANG
// =================================================================

function initializeQuoteCreatePage(mainContentContainer) {
    let selectedCustomer = null;
    let currentUser;
    let productList = [], customerList = [], projectList = [], productCategories = [];
    let priceSchemas = [];
    let productSuggestionBox = $('#product-suggestion-box'),
        customerSuggestionBox = $('#customer-suggestion-box'),
        projectSuggestionBox = $('#project-suggestion-box');
    let currentActiveProductInput = null;
    let filteredProducts = [], filteredCustomers = [], filteredProjects = [];

    function enableViewOnlyMode() {
        $('#page-title').text(`Xem Báo Giá: ${$('#quote-number-input').val()}`);
        $('input, select').prop('disabled', true);
        $('.remove-row-btn, #add-pur-row-btn, #add-ula-row-btn, #save-quote-btn').hide();
        $('#image-preview, #qr-preview').css('pointer-events', 'none').prop('title', '');
        $('.group-header div[contenteditable="true"]').prop('contenteditable', false);
    }

    function startInitialization() {
        const userPermissionsCall = $.ajax({ url: 'api/get_user_permissions.php', dataType: 'json' });
        const productsCall = $.ajax({ url: 'api/get_products.php', dataType: 'json' });
        const customersCall = $.ajax({ url: 'api/get_customers.php', dataType: 'json' });
        const projectsCall = $.ajax({ url: 'api/get_projects.php', dataType: 'json' });
        const categoriesCall = $.ajax({ url: 'api/get_product_categories.php', dataType: 'json' });
        const schemasCall = $.ajax({ url: 'api/get_price_schemas.php', dataType: 'json' });

        $.when(userPermissionsCall, productsCall, customersCall, projectsCall, categoriesCall, schemasCall).done(function (
            userRes, productsRes, customersRes, projectsRes, categoriesRes, schemasRes
        ) {
            if (userRes[0].success && userRes[0].user) {
                currentUser = userRes[0].user;
            }
            productList = productsRes[0] || [];
            customerList = customersRes[0] || [];
            projectList = projectsRes[0] || [];
            if (categoriesRes[0].success) {
                productCategories = categoriesRes[0].categories || [];
            }
            priceSchemas = schemasRes[0] || [];
            
            const priceSchemaSelect = $('#price-schema');
            priceSchemaSelect.empty();
            if (priceSchemas.length > 0) {
                priceSchemas.forEach(schema => {
                    const optionText = `${schema.MaCoChe.toUpperCase()} - ${schema.TenCoChe}`;
                    priceSchemaSelect.append($('<option>', { value: schema.MaCoChe.toLowerCase(), text: optionText }));
                });
            } else {
                priceSchemaSelect.append($('<option>', { value: 'p0', text: 'P0 - Giá gốc' }));
            }

            const urlParams = new URLSearchParams(window.location.search);
            const quoteId = urlParams.get('id');
            if (quoteId) {
                loadQuoteForEditing(quoteId);
            } else {
                initializeForm();
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('Lỗi khi tải dữ liệu cần thiết:', textStatus, errorThrown, jqXHR.responseText);
            showMessageModal("Đã xảy ra lỗi khi tải dữ liệu cần thiết. Vui lòng tải lại trang.", 'error');
        });
    }

    function createRow(type, data = {}) {
        if (type === 'group') {
            return `<tr class="group-header" data-category-id="${data.id}"><td colspan="11"><div contenteditable="true">${data.name || 'Nhóm sản phẩm'}</div></td></tr>`;
        }
        return `<tr class="bom-item-row">
            <td class="stt"></td>
            <td><input type="text" class="product-code" placeholder="Gõ mã hàng..."></td>
            <td class="product-id-thongso"></td>
            <td class="dim-thickness"></td>
            <td class="dim-width"></td>
            <td>Bộ</td>
            <td><input type="text" class="product-quantity text-right" value="1"></td>
            <td><input type="text" class="unit-price text-right" value="0"></td>
            <td class="line-total text-right font-semibold">0</td>
            <td><input type="text" class="note" placeholder="Ghi chú..."></td>
            <td class="no-print">
                <button class="remove-row-btn text-red-500 hover:text-red-700 p-1">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    }

    function createShippingRow() {
        const groupHeader = '<tr class="group-header"><td colspan="11"><div contenteditable="true">Phí vận chuyển</div></td></tr>';
        const shippingRow = `
            <tr class="shipping-fee-row">
                <td class="stt" style="width: 3%;">1</td>
                <td style="width: 15%; text-align:left; padding-left: 10px;">Chuyến</td>
                <td style="width: 10%;"></td>
                <td style="width: 10%;"></td>
                <td style="width: 10%;"></td>
                <td style="width: 5%;"></td>
                <td style="width: 7%;">
                    <input type="text" id="shipping-quantity-input" class="product-quantity text-right" value="1">
                </td>
                <td style="width: 10%;">
                    <input type="text" id="shipping-unit-price-input" class="unit-price text-right" value="0">
                </td>
                <td id="shipping-line-total" class="line-total text-right font-semibold" style="width: 12%;">0</td>
                <td style="width: auto;"><input type="text" class="note" placeholder="Ghi chú vận chuyển..."></td>
                <td class="no-print" style="width: 3%;"></td>
            </tr>`;
        return groupHeader + shippingRow;
    }

    function renumberRows() {
        const renumberTable = (tbodySelector) => {
            let stt = 1;
            $(tbodySelector).children('tr').each(function () {
                const row = $(this);
                if (row.hasClass('group-header')) {
                    stt = 1;
                } else if (row.hasClass('bom-item-row')) {
                    row.find('td.stt').text(stt++);
                }
            });
        };
        renumberTable('#pur-items-bom');
        renumberTable('#ula-items-bom');
    }

    function addEmptyRow(targetTableSelector) {
        const lastRow = $(targetTableSelector).find('tr:last');
        if (lastRow.length === 0 || (!lastRow.hasClass('group-header') && lastRow.find('.product-code').val() !== '')) {
            $(targetTableSelector).append(createRow('item'));
            renumberRows();
        }
    }

    function updateRowTotal(row) {
        const quantity = parseNumber(row.find('.product-quantity').val());
        const unitPrice = parseNumber(row.find('.unit-price').val());
        row.find('.line-total').text(formatNumber(quantity * unitPrice));
        updateAllTotals();
    }

    function updateAllTotals() {
        let subtotal = 0;
        $('#pur-items-bom tr.bom-item-row, #ula-items-bom tr.bom-item-row').each(function () {
            subtotal += parseNumber($(this).find('.line-total').text());
        });
        const shippingQuantity = parseNumber($('#shipping-quantity-input').val());
        const shippingUnitPrice = parseNumber($('#shipping-unit-price-input').val());
        const totalShippingFee = shippingQuantity * shippingUnitPrice;
        $('#shipping-line-total').text(formatNumber(totalShippingFee));
        const totalBeforeVAT = subtotal + totalShippingFee;
        const vat = totalBeforeVAT * 0.1;
        const total = totalBeforeVAT + vat;
        $('#subtotal').text(formatNumber(Math.round(subtotal)));
        $('#vat').text(formatNumber(Math.round(vat)));
        $('#total').text(formatNumber(Math.round(total)));
        $('#amount-in-words').text(docSo(Math.round(total)));
        $('#delivery-conditions-input').val(getDeliveryTime(Math.round(total)));
    }

    function applyPriceAdjustment() {
        const adjustmentPercentage = parseNumber($('#price-adjustment-percentage-input').val());
        const adjustmentFactor = (100 + adjustmentPercentage) / 100;
        const currentSchemaCode = ($('#price-schema').val() || 'p0').toLowerCase();
        $('#pur-items-bom tr.bom-item-row, #ula-items-bom tr.bom-item-row').each(function () {
            const row = $(this);
            const productData = row.data('product-data');
            if (productData && productData.price) {
                const basePrice = productData.price[currentSchemaCode] !== undefined ? productData.price[currentSchemaCode] : 0;
                const adjustedPrice = basePrice * adjustmentFactor;
                const rounded = roundUpToStep(adjustedPrice);
                row.find('.unit-price').val(formatNumber(rounded));
                updateRowTotal(row);
            }
        });
    }

    function generateQuoteNumber() {
        const quoteId = new URLSearchParams(window.location.search).get('id');
        if (quoteId) return;

        if (!selectedCustomer || !selectedCustomer.MaCongTy) {
            $('#quote-number-input').val('');
            return;
        }

        const customerCode = selectedCustomer.MaCongTy.toUpperCase();
        const priceSchema = $('#price-schema').val();
        const dateVal = $('#quote_date').val();

        if (!priceSchema || !dateVal) {
            $('#quote-number-input').val('');
            return;
        }

        const priceCode = priceSchema.toUpperCase();
        const dateParts = dateVal.split('/');
        let formattedDate = '';
        if (dateParts.length === 3) {
            formattedDate = dateParts[2].slice(-2) + dateParts[1] + dateParts[0];
        } else {
            return;
        }
        const uniqueCode = String(Date.now()).slice(-4);
        const quoteNumber = `3iG/${priceCode}-${formattedDate}/${uniqueCode}/${customerCode}`;
        $('#quote-number-input').val(quoteNumber);
    }

    function updateRowWithProductData(row, productData) {
        const existingCodes = [];
        $('#pur-items-bom tr.bom-item-row, #ula-items-bom tr.bom-item-row').each(function () {
            const code = $(this).find('.product-code').val().toUpperCase();
            if (code && row[0] !== $(this)[0]) {
                existingCodes.push(code);
            }
        });
        if (productData.code && existingCodes.includes(productData.code.toUpperCase())) {
            showMessageModal(`Mã hàng "${productData.code}" đã tồn tại trong báo giá. Vui lòng chọn mã hàng khác.`, 'warning');
            return;
        }

        let targetTbodySelector = (productData.code && productData.code.toUpperCase().startsWith('PUR')) ? '#pur-items-bom' : '#ula-items-bom';
        const category = productCategories.find(cat => cat.categoryId === productData.categoryId);
        const categoryName = category ? category.categoryName : 'Sản phẩm khác';
        const categoryId = category ? category.categoryId : 'other';

        let targetGroup = $(targetTbodySelector).find(`.group-header[data-category-id="${categoryId}"]`);
        if (targetGroup.length === 0) {
            targetGroup = $(createRow('group', { id: categoryId, name: categoryName }));
            $(targetTbodySelector).append(targetGroup);
        }

        const priceSchemaCode = ($('#price-schema').val() || 'p0').toLowerCase();
        const basePrice = (productData.price && productData.price[priceSchemaCode] !== undefined) ? productData.price[priceSchemaCode] : 0;
        const adjustmentPercentage = parseNumber($('#price-adjustment-percentage-input').val());
        const adjustmentFactor = (100 + adjustmentPercentage) / 100;
        const finalPrice = basePrice * adjustmentFactor;

        row.data('product-data', productData);
        row.find('.product-code').val(productData.code || '');
        row.find('.product-id-thongso').text(productData.id_thongso || '');
        row.find('.dim-thickness').text(productData.thickness || '');
        row.find('.dim-width').text(productData.width || '');
        row.find('.unit-price').val(formatNumber(roundUpToStep(finalPrice)));
        row.find('.product-quantity').val('1');

        const lastItemInGroup = targetGroup.nextUntil('.group-header').last();
        if (lastItemInGroup.length > 0) {
            row.insertAfter(lastItemInGroup);
        } else {
            row.insertAfter(targetGroup);
        }
        addEmptyRow(targetTbodySelector);
        hideProductSuggestions();
        updateRowTotal(row);
        renumberRows();
        row.find('.product-quantity').select();
    }

    function getQuoteDataFromForm() {
        const quoteData = {
            baoGiaID: $('#quote-id-input').val() || 0,
            quoteInfo: {
                congTyID: $('#company-id-input').val() || null,
                nguoiLienHeID: $('#contact-id-input').val() || null,
                userID: (currentUser && currentUser.userID) || null,
                duAnID: $('#project-id-input').val() || null,
                tenCongTy: $('#customer-name-input').val(),
                diaChiKhach: $('#customer-address-input').val(),
                nguoiNhan: $('#recipient-name-input').val(),
                soDiDongKhach: $('#recipient-phone-input').val(),
                hangMuc: $('#category-input').val(),
                tenDuAn: $('#project-name-input').val(),
                nguoiBaoGia: $('#quote-person-input').val(),
                chucVuNguoiBaoGia: $('#position-input').val(),
                diDongNguoiBaoGia: $('#mobile-input').val(),
                hieuLucBaoGia: $('#quote-validity-input').val(),
                soBaoGia: $('#quote-number-input').val(),
                ngayBaoGia: $('#quote_date').val(),
                xuatXu: $('#origin').val(),
                thoiGianGiaoHang: $('#delivery-conditions-input').val(),
                dieuKienThanhToan: $('#payment-terms-input').val(),
                diaChiGiaoHang: $('#delivery-location-input').val(),
                hinhAnh1: $('#image-path').val(),
                hinhAnh2: $('#qr-path').val(),
                phiVanChuyen: parseNumber($('#shipping-unit-price-input').val()),
                soLuongVanChuyen: parseNumber($('#shipping-quantity-input').val()),
                ghiChuVanChuyen: $('#shipping-fee-bom .note').val(),
                coCheGia: ($('#price-schema').val() || 'p0').toUpperCase(),
                trangThai: $('#quote-status-select').val() || 'Mới tạo',
                phanTramDieuChinh: parseNumber($('#price-adjustment-percentage-input').val())
            },
            items: [],
            totals: {
                subtotal: parseNumber($('#subtotal').text()),
                vat: parseNumber($('#vat').text()),
                total: parseNumber($('#total').text())
            }
        };
        let itemOrder = 0;
        const processTable = (tbodySelector) => {
            $(tbodySelector).find('tr.group-header').each(function () {
                const groupRow = $(this);
                const groupName = groupRow.find('div[contenteditable="true"]').text().trim();
                const categoryId = groupRow.data('category-id');
                groupRow.nextUntil('.group-header').each(function () {
                    const row = $(this);
                    if (row.hasClass('bom-item-row')) {
                        const productData = row.data('product-data');
                        if (productData && productData.productId) {
                            quoteData.items.push({
                                productId: productData.productId,
                                code: productData.code || '',
                                name: productData.name || '',
                                groupName: groupName,
                                order: ++itemOrder,
                                id_thongso: productData.id_thongso || null,
                                thickness: productData.thickness || null,
                                width: productData.width || null,
                                unit: 'Bộ',
                                quantity: parseNumber(row.find('.product-quantity').val()),
                                unitPrice: parseNumber(row.find('.unit-price').val()),
                                lineTotal: parseNumber(row.find('.line-total').text()),
                                note: row.find('.note').val(),
                            });
                        }
                    }
                });
            });
        };
        processTable('#pur-items-bom');
        processTable('#ula-items-bom');
        return quoteData;
    }

    function populateFormWithData(quote) {
        const info = quote.info;
        const defaultProductImage = 'uploads/default_image.png';
        const defaultQrImage = 'uploads/qr.png';
        $('#quote-id-input').val(info.BaoGiaID);
        $('#company-id-input').val(info.CongTyID);
        $('#customer-name-input').val(info.TenCongTy);
        $('#customer-address-input').val(info.DiaChiKhach);
        $('#recipient-name-input').val(info.NguoiNhan);
        $('#recipient-phone-input').val(info.SoDiDongKhach);
        $('#category-input').val(info.HangMuc);
        $('#project-name-input').val(info.TenDuAn);
        $('#quote-person-input').val(info.NguoiBaoGia);
        $('#position-input').val(info.ChucVuNguoiBaoGia);
        $('#mobile-input').val(info.DiDongNguoiBaoGia);
        $('#quote-validity-input').val(info.HieuLucBaoGia);
        $('#quote-number-input').val(info.SoBaoGia);
        if (info.NgayBaoGia) {
            const parts = info.NgayBaoGia.split('-');
            if (parts.length === 3) $('#quote_date').val(`${parts[2]}/${parts[1]}/${parts[0]}`);
        }
        $('#origin').val(info.XuatXu);
        $('#delivery-conditions-input').val(info.ThoiGianGiaoHang || getDeliveryTime(info.total));
        $('#payment-terms-input').val(info.DieuKienThanhToan);
        $('#delivery-location-input').val(info.DiaChiGiaoHang);
        $('#image-path').val(info.HinhAnh1 || defaultProductImage);
        $('#image-preview').attr('src', info.HinhAnh1 || defaultProductImage);
        $('#qr-path').val(info.HinhAnh2 || defaultQrImage);
        $('#qr-preview').attr('src', info.HinhAnh2 || defaultQrImage);
        $('#price-schema').val((info.CoCheGiaApDung || 'p0').toLowerCase());
        $('#price-adjustment-percentage-input').val(info.PhanTramDieuChinh !== undefined ? info.PhanTramDieuChinh : 0);
        $('#quote-status-select').val(info.TrangThai || 'Mới tạo');
        $('#shipping-fee-bom').empty();
        if (info.PhiVanChuyen !== undefined && info.SoLuongVanChuyen !== undefined) {
            $('#shipping-fee-bom').html(createShippingRow()); // Create a new one
            $('#shipping-quantity-input').val(formatNumber(info.SoLuongVanChuyen));
            $('#shipping-unit-price-input').val(formatNumber(info.PhiVanChuyen));
            $('#shipping-line-total').text(formatNumber(info.SoLuongVanChuyen * info.PhiVanChuyen));
            $('#shipping-fee-bom .note').val(info.GhiChuVanChuyen || '');
        } else {
            $('#shipping-fee-bom').html(createShippingRow());
        }
        $('#pur-items-bom, #ula-items-bom').empty();
        if (quote.items && quote.items.length > 0) {
            const groupedItems = quote.items.reduce((acc, item) => {
                const groupName = item.TenNhom || 'Sản phẩm khác';
                if (!acc[groupName]) acc[groupName] = [];
                acc[groupName].push(item);
                return acc;
            }, {});
            for (const groupName in groupedItems) {
                const itemsInGroup = groupedItems[groupName];
                if (itemsInGroup.length > 0) {
                    const firstItem = itemsInGroup[0];
                    const tbodySelector = firstItem.MaHang && firstItem.MaHang.toUpperCase().startsWith('PUR') ? '#pur-items-bom' : '#ula-items-bom';
                    const productInfo = productList.find(p => p.productId == firstItem.SanPhamID);
                    const categoryId = productInfo ? productInfo.categoryId : 'other';
                    $(tbodySelector).append(createRow('group', { id: categoryId, name: groupName }));
                    itemsInGroup.forEach(item => {
                        const fullProductData = productList.find(p => p.productId == item.SanPhamID) || { /* default object */ };
                        const newRow = $(createRow('item'));
                        newRow.data('product-data', fullProductData);
                        newRow.find('.product-code').val(item.MaHang || '');
                        newRow.find('.product-id-thongso').text(item.ID_ThongSo || '');
                        newRow.find('.dim-thickness').text(item.DoDay || '');
                        newRow.find('.dim-width').text(item.ChieuRong || '');
                        newRow.find('.product-quantity').val(formatNumber(item.SoLuong));
                        newRow.find('.unit-price').val(formatNumber(item.DonGia));
                        newRow.find('.line-total').text(formatNumber(item.ThanhTien));
                        newRow.find('.note').val(item.GhiChu);
                        $(tbodySelector).append(newRow);
                    });
                }
            }
        }
        addEmptyRow('#pur-items-bom');
        addEmptyRow('#ula-items-bom');
        renumberRows();
        updateAllTotals();
    }

    function initializeForm() {
        selectedCustomer = null;
        const defaultProductImage = 'uploads/default_image.png';
        const defaultQrImage = 'uploads/qr.png';
        $('input[type="text"], input[type="hidden"]').not('#quote-validity-input, #category-input, #origin, #delivery-conditions-input, #payment-terms-input, #quote_date, #quote-person-input, #position-input, #mobile-input, #price-adjustment-percentage-input').val('');
        $('#image-path').val(defaultProductImage);
        $('#image-preview').attr('src', defaultProductImage);
        $('#qr-path').val(defaultQrImage);
        $('#qr-preview').attr('src', defaultQrImage);
        $('#pur-items-bom, #ula-items-bom').empty();
        addEmptyRow('#pur-items-bom');
        addEmptyRow('#ula-items-bom');
        $('#shipping-fee-bom').html(createShippingRow());
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        $('#quote_date').val(`${day}/${month}/${year}`);
        if (currentUser) {
            $('#quote-person-input').val(currentUser.fullName || '');
            $('#position-input').val(currentUser.position || '');
            $('#mobile-input').val(currentUser.phone || '');
        }
        $('#price-schema').val('p0');
        $('#quote-status-select').val('Mới tạo');
        $('#price-adjustment-percentage-input').val(0);
        updateAllTotals();
    }

    function loadQuoteForEditing(id) {
        const isViewMode = new URLSearchParams(window.location.search).get('view') === 'true';
        $.ajax({
            url: `api/get_quote_details.php?id=${id}`,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    populateFormWithData(res.quote);
                    // Manually set selectedCustomer after populating
                    const customer = customerList.find(c => c.CongTyID === res.quote.info.CongTyID);
                    if (customer) {
                        selectedCustomer = customer;
                    }
                    $('#page-title').text(`Chỉnh Sửa Báo Giá: ${res.quote.info.SoBaoGia}`);
                    const readOnlyStates = ['Chốt', 'Tạch'];
                    if (isViewMode || readOnlyStates.includes(res.quote.info.TrangThai)) {
                        enableViewOnlyMode();
                    }
                } else {
                    showMessageModal('Lỗi khi tải dữ liệu báo giá: ' + res.message, 'error');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Lỗi Ajax khi tải dữ liệu báo giá:', textStatus, errorThrown, jqXHR.responseText);
                showMessageModal('Không thể kết nối đến máy chủ để tải dữ liệu báo giá.', 'error');
            }
        });
    }

    function saveQuote() {
        const quoteData = getQuoteDataFromForm();
        if (!quoteData.quoteInfo.congTyID) {
            showMessageModal('Bạn phải chọn một khách hàng hợp lệ từ danh sách.', 'error');
            return;
        }
        if (!quoteData.quoteInfo.soBaoGia) {
            showMessageModal('Số báo giá không được để trống.', 'error');
            return;
        }
        if (quoteData.items.length === 0) {
            showMessageModal('Vui lòng thêm ít nhất một sản phẩm vào báo giá.', 'error');
            return;
        }
        const isFinalizing = quoteData.quoteInfo.trangThai === 'Chốt';
        const proceedWithSave = () => {
            $('#save-quote-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');
            $.ajax({
                url: 'api/save_quote.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(quoteData),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        const savedQuoteId = response.baoGiaID;
                        if (savedQuoteId) {
                            $('#quote-id-input').val(savedQuoteId);
                            const newUrl = `?page=quote_create&id=${savedQuoteId}`;
                            history.pushState({ page: newUrl }, '', newUrl);
                            $('#page-title').text(`Chỉnh Sửa Báo Giá: ${quoteData.quoteInfo.soBaoGia}`);
                        }
                        if (isFinalizing) {
                            createOrderFromQuote(savedQuoteId);
                        } else {
                            showMessageModal(response.message, 'success');
                            $('#save-quote-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu');
                        }
                    } else {
                        showMessageModal('Lỗi khi lưu: ' + (response.message || 'Lỗi không xác định.'), 'error');
                        $('#save-quote-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Lỗi AJAX khi lưu báo giá:', textStatus, errorThrown, jqXHR.responseText);
                    showMessageModal('Đã có lỗi xảy ra khi kết nối đến server.', 'error');
                    $('#save-quote-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu');
                }
            });
        };
        if (isFinalizing) {
            showConfirmationModal("Bạn có chắc chắn muốn chốt báo giá này và tạo đơn hàng không? Hành động này không thể hoàn tác.", proceedWithSave);
        } else {
            proceedWithSave();
        }
    }
    
    function createOrderFromQuote(quoteId) {
        $('#save-quote-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang tạo đơn hàng...');
        $.ajax({
            url: 'api/create_donhang.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ baoGiaID: quoteId }),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    showMessageModal(`${response.message} (Mã đơn hàng: ${response.soYCSX})`, 'success');
                    enableViewOnlyMode();
                } else {
                    showMessageModal('Lỗi tạo đơn hàng: ' + response.message, 'error');
                    $('#save-quote-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Lỗi AJAX khi tạo đơn hàng:', textStatus, errorThrown, jqXHR.responseText);
                showMessageModal('Đã có lỗi xảy ra khi kết nối đến server để tạo đơn hàng.', 'error');
                $('#save-quote-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Lưu');
            }
        });
    }

    function showProductSuggestions() {
        if (!currentActiveProductInput) return;
        const query = currentActiveProductInput.val().toLowerCase();
        if (query.length < 2) { hideProductSuggestions(); return; }
        const terms = query.split(' ').filter(t => t.length > 0);
        filteredProducts = productList.filter(p => {
            const searchString = `${(p.code || '').toLowerCase()} ${(p.name || '').toLowerCase()}`;
            return terms.every(term => searchString.includes(term));
        });
        if (filteredProducts.length === 0) { hideProductSuggestions(); return; }
        const list = filteredProducts.slice(0, 12).map((p, index) => `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center"><div><b>${p.code || ''}</b> <small>${p.name || ''}</small></div><span class="f-key-hint">F${index + 1}</span></li>`).join('');
        productSuggestionBox.html(`<ul>${list}</ul>`).show().css({ top: currentActiveProductInput.offset().top + currentActiveProductInput.outerHeight(), left: currentActiveProductInput.offset().left, width: '450px' });
    }

    function hideProductSuggestions() { productSuggestionBox.hide().empty(); }

    function showCustomerSuggestions(input) {
        const query = input.val().toLowerCase();
        if (query.length < 1) { hideCustomerSuggestions(); return; }
        filteredCustomers = customerList.filter(c => (c.TenCongTy || '').toLowerCase().includes(query) || (c.TenNguoiLienHe && c.TenNguoiLienHe.toLowerCase().includes(query)));
        if (filteredCustomers.length === 0) { hideCustomerSuggestions(); return; }
        const list = filteredCustomers.slice(0, 12).map((c, index) => `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center"><div>${c.TenNguoiLienHe ? `<b>${c.TenNguoiLienHe}</b>` : `<i class="text-gray-400">(Công ty)</i>`}<small class="text-gray-500"> - ${c.TenCongTy || ''}</small></div><span class="f-key-hint">F${index + 1}</span></li>`).join('');
        customerSuggestionBox.html(`<ul>${list}</ul>`).show().css({ top: input.offset().top + input.outerHeight(), left: input.offset().left, width: Math.max(input.outerWidth(), 450) });
    }

    function hideCustomerSuggestions() { customerSuggestionBox.hide().empty(); }

    function updateFormWithCustomerData(customer) {
        selectedCustomer = customer;
        $('#company-id-input').val(customer.CongTyID || '');
        $('#contact-id-input').val(customer.NguoiLienHeID || '');
        $('#customer-name-input').val(customer.TenCongTy || '');
        $('#recipient-name-input').val(customer.TenNguoiLienHe || '');
        $('#customer-address-input').val(customer.DiaChi || '');
        $('#recipient-phone-input').val(customer.SoDiDong || '');
        hideCustomerSuggestions();
        generateQuoteNumber();
        if (customer.MaCoChe) {
            const desiredSchemaValue = String(customer.MaCoChe).toLowerCase();
            const priceSchemaSelect = $('#price-schema');
            if (priceSchemaSelect.find(`option[value="${desiredSchemaValue}"]`).length > 0) {
                priceSchemaSelect.val(desiredSchemaValue).trigger('change');
            }
        }
        $('#recipient-name-input').focus();
    }

    function showProjectSuggestions(input) {
        const query = input.val().toLowerCase();
        if (query.length < 1) { hideProjectSuggestions(); return; }
        filteredProjects = projectList.filter(p => (p.TenDuAn || '').toLowerCase().includes(query) || (p.MaDuAn && p.MaDuAn.toLowerCase().includes(query)));
        if (filteredProjects.length === 0) { hideProjectSuggestions(); return; }
        const list = filteredProjects.slice(0, 12).map((p, index) => `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center"><div><b>${p.MaDuAn || ''}</b> <small>${p.TenDuAn || ''}</small></div><span class="f-key-hint">F${index + 1}</span></li>`).join('');
        projectSuggestionBox.html(`<ul>${list}</ul>`).show().css({ top: input.offset().top + input.outerHeight(), left: input.offset().left, width: Math.max(input.outerWidth(), 450) });
    }

    function hideProjectSuggestions() { projectSuggestionBox.hide().empty(); }

    function updateFormWithProjectData(project) {
        $('#project-id-input').val(project.DuAnID || '');
        $('#project-name-input').val(project.MaDuAn || project.TenDuAn || '');
        hideProjectSuggestions();
    }

    function handleImageUpload(inputId, previewId, pathId) {
        const file = document.getElementById(inputId).files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('imageFile', file);
        $.ajax({
            url: 'api/upload_image.php', type: 'POST', data: formData,
            processData: false, contentType: false, dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $(`#${previewId}`).attr('src', response.filePath);
                    $(`#${pathId}`).val(response.filePath);
                    showMessageModal('Tải ảnh lên thành công!', 'success');
                } else {
                    showMessageModal('Lỗi khi tải ảnh: ' + response.message, 'error');
                }
            },
            error: function (jqXHR) {
                console.error('Lỗi AJAX:', jqXHR.responseText);
                showMessageModal('Lỗi kết nối máy chủ khi tải ảnh.', 'error');
            }
        });
    }

    // --- KHỐI LẮNG NGHE SỰ KIỆN CHÍNH ---
    mainContentContainer
        .off('.quoteCreate')
        .on('click.quoteCreate', '#add-pur-row-btn', () => addEmptyRow('#pur-items-bom'))
        .on('click.quoteCreate', '#add-ula-row-btn', () => addEmptyRow('#ula-items-bom'))
        .on('click.quoteCreate', '.remove-row-btn', function () {
            const row = $(this).closest('tr');
            const groupHeader = row.prevAll('.group-header:first');
            row.remove();
            if (groupHeader.length > 0) {
                const nextElement = groupHeader.next();
                if (!nextElement.length || nextElement.hasClass('group-header')) {
                    groupHeader.remove();
                }
            }
            updateAllTotals();
            renumberRows();
        })
        .on('click.quoteCreate', '#save-quote-btn', saveQuote)
        .on('input.quoteCreate', '.product-quantity, .unit-price, #shipping-quantity-input, #shipping-unit-price-input', function () {
            const el = this;
            const cursorPos = el.selectionStart;
            const originalLength = el.value.length;
            el.value = formatNumber(parseNumber(el.value));
            const newLength = el.value.length;
            if (cursorPos !== null) {
                el.setSelectionRange(cursorPos + newLength - originalLength, cursorPos + newLength - originalLength);
            }
            updateRowTotal($(this).closest('tr'));
        })
        .on('input.quoteCreate', '#price-adjustment-percentage-input', applyPriceAdjustment)
        .on('focus.quoteCreate', '.product-code', function () { currentActiveProductInput = $(this); })
        .on('keyup.quoteCreate', '.product-code', function (e) {
            if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) showProductSuggestions();
        })
        .on('keyup.quoteCreate', '#customer-name-input', function (e) {
            if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) {
                const currentInputName = $(this).val();
                if (selectedCustomer && selectedCustomer.TenCongTy !== currentInputName) {
                    selectedCustomer = null;
                    $('#company-id-input').val('');
                    $('#contact-id-input').val('');
                }
                showCustomerSuggestions($(this));
                generateQuoteNumber();
            }
        })
        .on('change.quoteCreate', '#price-schema', function () {
            const selectedSchemaCode = ($(this).val() || 'p0').toLowerCase();
            const selectedSchema = priceSchemas.find(s => s.MaCoChe.toLowerCase() === selectedSchemaCode);
            
            const defaultPercent = selectedSchema ? (selectedSchema.PhanTramDieuChinh || 0) : 0;
            $('#price-adjustment-percentage-input').val(defaultPercent);
            showAdjustmentDefaultHint(defaultPercent);
            applyPriceAdjustment();
            generateQuoteNumber();
        }
        .on('blur.quoteCreate', '#quote_date', generateQuoteNumber)
        .on('keyup.quoteCreate', '#project-name-input', function (e) {
            if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) showProjectSuggestions($(this));
        })
        .on('keydown.quoteCreate', '.product-code', function (e) {
            if (!productSuggestionBox.is(':visible')) return;
            if (e.keyCode >= 112 && e.keyCode <= 123) {
                e.preventDefault();
                const selectedIndex = e.keyCode - 112;
                if (filteredProducts[selectedIndex]) updateRowWithProductData($(this).closest('tr'), filteredProducts[selectedIndex]);
            } else if (e.keyCode === 27) { e.preventDefault(); hideProductSuggestions(); }
        })
        .on('keydown.quoteCreate', '#customer-name-input', function (e) {
            if (!customerSuggestionBox.is(':visible')) return;
            if (e.keyCode >= 112 && e.keyCode <= 123) {
                e.preventDefault();
                const selectedIndex = e.keyCode - 112;
                if (filteredCustomers[selectedIndex]) updateFormWithCustomerData(filteredCustomers[selectedIndex]);
            } else if (e.keyCode === 27) { e.preventDefault(); hideCustomerSuggestions(); }
        })
        .on('keydown.quoteCreate', '#project-name-input', function (e) {
            if (!projectSuggestionBox.is(':visible')) return;
            if (e.keyCode >= 112 && e.keyCode <= 123) {
                e.preventDefault();
                const selectedIndex = e.keyCode - 112;
                if (filteredProjects[selectedIndex]) updateFormWithProjectData(filteredProjects[selectedIndex]);
            } else if (e.keyCode === 27) { e.preventDefault(); hideProjectSuggestions(); }
        })
        .on('click.quoteCreate', '#image-preview', () => $('#image-upload-input').click())
        .on('change.quoteCreate', '#image-upload-input', () => handleImageUpload('image-upload-input', 'image-preview', 'image-path'))
        .on('click.quoteCreate', '#qr-preview', () => $('#qr-upload-input').click())
        .on('change.quoteCreate', '#qr-upload-input', () => handleImageUpload('qr-upload-input', 'qr-preview', 'qr-path'));

    $('.export-btn').on('click', function(e) {
        e.stopPropagation();
        let dropdown = $(this).siblings('.dropdown-menu');
        $('.dropdown-menu').not(dropdown).addClass('hidden');
        dropdown.toggleClass('hidden');
    });
    
        $('#add_KH').on('click', function(e) {
      history.pushState({ page: 'customer_management' }, '', '?page=customer_management');
            window.App.handleRouting();
    });
          $('#add_DA').on('click', function(e) {
      history.pushState({ page: 'project_management' }, '', '?page=project_management');
            window.App.handleRouting();
    });
    
    

    $(document).off('click.dropdown').on('click.dropdown', '.dropdown-item', function() {
        $(this).closest('.dropdown-menu').addClass('hidden');
    });

    $(document).off('click.closeDropdowns').on('click.closeDropdowns', function() {
        $('.dropdown-menu').addClass('hidden');
    });

    $(document).on('click.quoteCreate', '#export-pdf-btn, #export-pdf-TQ-btn, #export-excel-btn, #export-excel-TQ-btn', function(e) {
        e.preventDefault();
        const quoteId = $('#quote-id-input').val();
        if (!quoteId) {
            showMessageModal('Vui lòng lưu báo giá trước khi xuất file.', 'warning');
            return;
        }
        
        const id = $(this).attr('id');
        let url, windowName, windowFeatures;

        if (id.includes('pdf')) {
            url = id.includes('TQ') ? `api/export_pdf_TQ.php?id=${quoteId}` : `api/export_pdf.php?id=${quoteId}`;
            windowName = `BaoGiaPDF_${id.includes('TQ') ? 'TQ_' : ''}${quoteId}`;
            windowFeatures = 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=850,height=1100';
            window.open(url, windowName, windowFeatures);
        } else if (id.includes('excel')) {
            url = id.includes('TQ') ? `api/export_excel_TQ.php?id=${quoteId}` : `api/export_excel.php?id=${quoteId}`;
            window.location.href = url;
        }
    });

    productSuggestionBox.off('click').on('click', 'li', function () {
        const product = filteredProducts[$(this).data('index')];
        if (product && currentActiveProductInput) updateRowWithProductData(currentActiveProductInput.closest('tr'), product);
    });
    
    customerSuggestionBox.off('click').on('click', 'li', function () {
        const customer = filteredCustomers[$(this).data('index')];
        if (customer) updateFormWithCustomerData(customer);
    });

    projectSuggestionBox.off('click').on('click', 'li', function () {
        const project = filteredProjects[$(this).data('index')];
        if (project) updateFormWithProjectData(project);
    });

    $(document).off('click.hideSuggestions').on('click.hideSuggestions', function (e) {
        if (!$(e.target).closest('.product-code, #product-suggestion-box').length) hideProductSuggestions();
        if (!$(e.target).closest('#customer-name-input, #customer-suggestion-box').length) hideCustomerSuggestions();
        if (!$(e.target).closest('#project-name-input, #project-suggestion-box').length) hideProjectSuggestions();
    });

    startInitialization();
}