/**
 * =================================================================================
 * BÁO GIÁ MANAGEMENT SCRIPT (VERSION 8.3 - PROJECT VALIDATION)
 * =================================================================================
 * - THAY ĐỔI MỚI (v8.3):
 * 1. Thêm validation bắt buộc chọn dự án từ danh sách (nếu nhập)
 * 2. Thêm visual feedback cho dự án (viền xanh/cam/đỏ)
 * 3. Thêm event blur để kiểm tra realtime
 * 4. Cảnh báo khi sửa tên dự án sau khi chọn
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

const ROUND_STEP = 10;
function roundUpToStep(v, step = ROUND_STEP) {
  v = parseNumber(v);
  if (!v || v <= 0) return 0;
  return Math.ceil(v / step) * step;
}

function getDeliveryTime(total) {
  return "3-5 ngày sau khi nhận được xác nhận đặt hàng.";
}

const groupBy = (arr, key) => arr.reduce((acc, item) => {
    const groupKey = item[key] || 'no-area';
    (acc[groupKey] = acc[groupKey] || []).push(item);
    return acc;
}, {});

// =================================================================
// HÀM QUẢN LÝ POPUP THÊM NHANH
// =================================================================

function showQuickAddModal(options) {
    $('#quick-add-modal').remove();

    const modalHTML = `
        <div id="quick-add-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">${options.title}</h3>
                    <button id="quick-add-modal-close" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
                <div class="mt-2">
                    <form id="quick-add-form">
                        ${options.formHTML}
                        <div class="flex items-center justify-end p-2 border-t border-gray-200 rounded-b mt-4">
                            <button id="quick-add-save-btn" type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    const $modal = $('#quick-add-modal');
    
    $modal.find('#quick-add-modal-close').on('click', () => $modal.remove());
    
    $modal.find('#quick-add-form').on('submit', function(e) {
        e.preventDefault();
        const $saveBtn = $(this).find('#quick-add-save-btn');
        $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...');

        const formData = {};
        $(this).serializeArray().forEach(item => {
            formData[item.name] = item.value;
        });
        
        options.onSubmit(formData, () => {
            $modal.remove();
        }, () => {
             $saveBtn.prop('disabled', false).text('Lưu');
        });
    });
    
    $modal.show();
    $modal.find('input:first').focus();
}

// =================================================================
// HÀM KHỞI TẠO CHÍNH CỦA TRANG
// =================================================================

function initializeQuoteCreatePage(mainContentContainer) {
  let selectedCustomer = null;
  let selectedProject = null;
  let currentUser;
  let productList = [], customerList = [], projectList = [], productCategories = [];
  let priceSchemas = [];
  let productSuggestionBox = $('#product-suggestion-box'),
    customerSuggestionBox = $('#customer-suggestion-box'),
    projectSuggestionBox = $('#project-suggestion-box');
  let currentActiveProductInput = null;
  let filteredProducts = [], filteredCustomers = [], filteredProjects = [];
  let activeProductCategoryFilter = 'all'; 

  function enableViewOnlyMode() {
    $('#page-title').text(`Xem Báo Giá: ${$('#quote-number-input').val()}`);
    $('input, select').prop('disabled', true);
    $('.remove-row-btn, #add-pur-row-btn, #add-ula-row-btn, #save-quote-btn, #add_KH, #add_DA, #add-area-btn, .remove-area-btn').hide();
    $('#image-preview, #qr-preview').css('pointer-events', 'none').prop('title', '');
    $('.group-header div[contenteditable="true"], .area-block-header').prop('contenteditable', false);
    $('#product-category-filter-bar').hide();
  }
  
  function setupProductCategoryFilters(categories) {
      const filterSelect = $('#product-category-filter-select');
      if (!filterSelect.length) {
          return;
      }
      filterSelect.empty();
      filterSelect.append('<option value="" disabled>-- Vui lòng chọn một nhóm --</option>');
      filterSelect.append('<option value="all" selected>Tất cả các nhóm</option>');
      
      categories.forEach(cat => {
          if (cat.categoryName) {
              filterSelect.append(`<option value="${cat.categoryId}">${cat.categoryName}</option>`);
          }
      });
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
      
      setupProductCategoryFilters(productCategories);
      activeProductCategoryFilter = 'all';
      $('#product-category-filter-select').val('all');

      const priceSchemaSelect = $('#price-schema');
      priceSchemaSelect.empty();
      if (priceSchemas.length > 0) {
        priceSchemas.forEach(schema => {
          const optionText = `${schema.MaCoChe.toUpperCase()} - ${schema.TenCoChe}`;
          priceSchemaSelect.append($('<option>', { value: schema.MaCoChe.toLowerCase(), text: optionText }));
        });
      } else {
        priceSchemaSelect.append($('<option>', { value: 'p1', text: 'P1 - Giá gốc' }));
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
  
  function createAreaBlock(areaName = 'Nhập khu vực') {
      const areaId = 'area-' + Date.now();
      const html = `
        <div class="area-block" id="${areaId}">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-yellow-400">
                 <h3 class="text-xl font-bold text-yellow-800 area-block-header" contenteditable="true">${areaName}</h3>
                 <button class="remove-area-btn text-red-500 hover:text-red-700 p-1 no-print" title="Xóa toàn bộ khu vực này">
                    <i class="fas fa-trash-alt fa-lg"></i>
                </button>
            </div>

            <table class="product-table">
                 <thead>
                    <tr>
                        <th rowspan="2" style="width: 3%;">Stt.</th>
                        <th rowspan="2" style="width: 15%;">Mã hàng</th>
                        <th colspan="3">Kích thước PUR (mm)</th>
                        <th rowspan="2" style="width: 5%;">Đơn vị</th>
                        <th rowspan="2" style="width: 7%;">Số lượng</th>
                        <th style="width: 10%;">Đơn giá</th>
                        <th style="width: 12%;">Thành tiền</th>
                        <th rowspan="2">Ghi chú</th>
                        <th rowspan="2" class="no-print" style="width: 3%;"></th>
                    </tr>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 10%;">(T) <br>Độ dày</th>
                        <th style="width: 10%;">(L)<br> Bản rộng</th>
                        <th>VNĐ</th>
                        <th>VNĐ</th>
                    </tr>
                </thead>
                <tbody class="pur-items-bom"></tbody>
            </table>

            <table class="product-table mt-4">
                 <thead>
                    <tr>
                        <th rowspan="2" style="width: 3%;">Stt.</th>
                        <th rowspan="2" style="width: 15%;">Mã hàng</th>
                        <th colspan="3">Kích thước ULA (mm)</th>
                        <th rowspan="2" style="width: 5%;">Đơn vị</th>
                        <th rowspan="2" style="width: 7%;">Số lượng</th>
                         <th style="width: 10%;">Đơn giá</th>
                        <th style="width: 12%;">Thành tiền</th>
                        <th rowspan="2">Ghi chú</th>
                        <th rowspan="2" class="no-print" style="width: 3%;"></th>
                    </tr>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 10%;">(t) <br>Độ dày</th>
                        <th style="width: 10%;">(w) <br>Bản rộng</th>
                        <th>VNĐ</th>
                        <th>VNĐ</th>
                    </tr>
                </thead>
                <tbody class="ula-items-bom"></tbody>
            </table>

             <div class="mt-2 no-print flex items-center space-x-2">
                <button class="add-pur-row-area px-3 py-1 text-xs bg-blue-500 text-white rounded-md hover:bg-blue-600 shadow-sm"><i class="fas fa-plus mr-1"></i>Thêm PUR</button>
                <button class="add-ula-row-area px-3 py-1 text-xs bg-green-500 text-white rounded-md hover:bg-green-600 shadow-sm"><i class="fas fa-plus mr-1"></i>Thêm ULA</button>
            </div>
        </div>
      `;
      return html;
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
    renumberTableBody($('#default-products-container').find('.pur-items-bom'));
    renumberTableBody($('#default-products-container').find('.ula-items-bom'));

    $('.area-block').each(function() {
        renumberTableBody($(this).find('.pur-items-bom'));
        renumberTableBody($(this).find('.ula-items-bom'));
    });
  }

  function renumberTableBody(tbody) {
      let stt = 1;
      tbody.children('tr').each(function() {
          const row = $(this);
          if (row.hasClass('group-header')) {
              stt = 1;
          } else if (row.hasClass('bom-item-row')) {
              row.find('td.stt').text(stt++);
          }
      });
  }

  function addEmptyRow(tbody) {
      const lastRow = tbody.find('tr:last');
      if (lastRow.length === 0 || (!lastRow.hasClass('group-header') && lastRow.find('.product-code').val() !== '')) {
          tbody.append(createRow('item'));
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
    let productSubtotal = 0;
    $('.bom-item-row').each(function () {
      productSubtotal += parseNumber($(this).find('.line-total').text());
    });

    const shippingQuantity = parseNumber($('#shipping-quantity-input').val());
    const shippingUnitPrice = parseNumber($('#shipping-unit-price-input').val());
    const totalShippingFee = shippingQuantity * shippingUnitPrice;
    $('#shipping-line-total').text(formatNumber(totalShippingFee));
    
    const subtotalBeforeTax = productSubtotal + totalShippingFee;

    const taxPercentage = parseNumber($('#tax-percentage-input').val()) / 100;
    const vat = subtotalBeforeTax * taxPercentage;
    
    const total = subtotalBeforeTax + vat;

    $('#subtotal').text(formatNumber(Math.round(subtotalBeforeTax))); 
    $('#vat').text(formatNumber(Math.round(vat)));
    $('#total').text(formatNumber(Math.round(total)));
    $('#amount-in-words').text(docSo(Math.round(total)));
  }
  
  function calculateFinalPrice(productData) {
    if (!productData || !productData.price) {
      return 0;
    }

    const selectedSchema = ($('#price-schema').val() || 'p1').toLowerCase();
    const tierPrice = productData.price[selectedSchema] !== undefined 
      ? productData.price[selectedSchema] 
      : productData.basePrice;

    const manualAdjustmentPercent = parseNumber($('#price-adjustment-percentage-input').val());
    const finalPrice = tierPrice * (1 + manualAdjustmentPercent / 100);

    return finalPrice;
  }

  function applyPriceAdjustment() {
    $('#printable-quote-area').find('.bom-item-row').each(function () {
      const row = $(this);
      const productData = row.data('product-data');

      if (productData) {
        const finalPrice = calculateFinalPrice(productData);
        const roundedPrice = roundUpToStep(finalPrice);

        row.find('.unit-price').val(formatNumber(roundedPrice));
        updateRowTotal(row);
      }
    });
  }

  function generateQuoteNumber() {
    const quoteId = new URLSearchParams(window.location.search).get('id');
    const existingQuoteNumber = $('#quote-number-input').val();
    const priceSchema = $('#price-schema').val();

    if (!priceSchema) {
        return;
    }
    const priceCode = priceSchema.toUpperCase();

    if (quoteId && existingQuoteNumber) {
        const parts = existingQuoteNumber.split('/');
        if (parts.length === 4) {
            const oldPriceAndDate = parts[1];
            const datePart = oldPriceAndDate.split('-')[1] || '';
            
            parts[1] = `${priceCode}-${datePart}`;
            
            const newQuoteNumber = parts.join('/');
            $('#quote-number-input').val(newQuoteNumber);
        }
        return;
    }

    if (!selectedCustomer || !selectedCustomer.MaCongTy) {
        $('#quote-number-input').val('');
        return;
    }

    const customerCode = selectedCustomer.MaCongTy.toUpperCase();
    const dateVal = $('#quote_date').val();

    if (!dateVal) {
        $('#quote-number-input').val('');
        return;
    }
    
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
    const productCodeUpper = (productData.code || '').toUpperCase();
    let isPur = productCodeUpper.startsWith('PUR');
    
    let targetTbody;
    if (isPur) {
      targetTbody = row.closest('.area-block, #default-products-container').find('.pur-items-bom');
    } else {
      targetTbody = row.closest('.area-block, #default-products-container').find('.ula-items-bom');
    }
    
    if (!row.parent().is(targetTbody)) {
        row.detach().appendTo(targetTbody);
    }
    
    if (targetTbody.length > 0) {
        const category = productCategories.find(cat => cat.categoryId === productData.categoryId);
        const categoryName = category ? category.categoryName : 'Sản phẩm khác';
        const categoryId = category ? category.categoryId : 'other';
        
        let targetGroup = targetTbody.find(`.group-header[data-category-id="${categoryId}"]`);
        if (targetGroup.length === 0) {
          targetGroup = $(createRow('group', { id: categoryId, name: categoryName }));
          targetTbody.append(targetGroup);
        }

        const lastItemInGroup = targetGroup.nextUntil('.group-header').last();
        if (lastItemInGroup.length > 0) {
          row.insertAfter(lastItemInGroup);
        } else {
          row.insertAfter(targetGroup);
        }
    }

    const finalPrice = calculateFinalPrice(productData);

    row.data('product-data', productData);
    row.find('.product-code').val(productData.code || '');
    row.find('.product-id-thongso').text(productData.id_thongso || '');
    row.find('.dim-thickness').text(productData.thickness || '');
    row.find('.dim-width').text(productData.width || '');
    row.find('.unit-price').val(formatNumber(roundUpToStep(finalPrice)));
    row.find('.product-quantity').val('1');

    if (isPur) {
      findAndAddMatchingUla(productData, row);
    }

    addEmptyRow(row.closest('tbody'));
    hideProductSuggestions();
    updateRowTotal(row);
    renumberRows();
    
    row.next('.bom-item-row').find('.product-code').focus();
  }
  
  function findAndAddMatchingUla(purProductData, purRow) {
    const getDimensionKey = (code) => {
      if (!code) return null;
      const match = code.match(/\s(\d+(?:\/\d+)?x\d+)/);
      return match ? match[1] : null;
    };

    const purCode = purProductData.code;
    const purDimensionKey = getDimensionKey(purCode);
    const purQuantity = parseNumber(purRow.find('.product-quantity').val());

    if (!purDimensionKey) {
      console.log(`Không thể trích xuất kích thước từ mã PUR: ${purCode}`);
      return;
    }

    const purSuffix = purProductData.sku_suffix ? purProductData.sku_suffix.trim() : "";
    
    const matchingUla = productList.find(p => {
      if (!p.code || !p.code.toUpperCase().startsWith('ULA')) return false;
      const ulaDimensionKey = getDimensionKey(p.code);
      if (ulaDimensionKey !== purDimensionKey) return false;
      const ulaSuffix = p.sku_suffix ? p.sku_suffix.trim() : "";
      return ulaSuffix === purSuffix;
    });

    if (matchingUla) {
        const areaBlock = purRow.closest('.area-block');
        const targetUlaTbody = areaBlock.length > 0 ? areaBlock.find('.ula-items-bom') : $('#default-products-container').find('.ula-items-bom');
        
        let targetRow = targetUlaTbody.find('.product-code').filter(function() { return $(this).val() === ''; }).first().closest('tr');
        
        if (targetRow.length === 0) {
            addEmptyRow(targetUlaTbody);
            targetRow = targetUlaTbody.find('tr.bom-item-row:last');
        }
      
        addUlaProductRow(targetRow, matchingUla, purQuantity);
    } else {
      console.log(`Không tìm thấy ULA tương ứng cho PUR: ${purProductData.code}`);
    }
  }

  function addUlaProductRow(row, ulaData, quantity) {
    const tbody = row.closest('tbody');

    const category = productCategories.find(cat => cat.categoryId === ulaData.categoryId);
    const categoryName = category ? category.categoryName : 'Sản phẩm khác';
    const categoryId = category ? category.categoryId : 'other';

    let targetGroup = tbody.find(`.group-header[data-category-id="${categoryId}"]`);
    if (targetGroup.length === 0) {
      targetGroup = $(createRow('group', { id: categoryId, name: categoryName }));
      tbody.append(targetGroup);
    }

    const lastItemInGroup = targetGroup.nextUntil('.group-header').last();
    if (lastItemInGroup.length > 0) {
        row.insertAfter(lastItemInGroup);
    } else {
        row.insertAfter(targetGroup);
    }
    
    const finalPrice = calculateFinalPrice(ulaData);

    row.data('product-data', ulaData);
    row.find('.product-code').val(ulaData.code || '');
    row.find('.product-id-thongso').text(ulaData.id_thongso || '');
    row.find('.dim-thickness').text(ulaData.thickness || '');
    row.find('.dim-width').text(ulaData.width || '');
    row.find('.unit-price').val(formatNumber(roundUpToStep(finalPrice)));
    row.find('.product-quantity').val(quantity);

    addEmptyRow(tbody);
    updateRowTotal(row);
    renumberRows();
    
    showMessageModal('Đã tự động thêm cùm ULA tương ứng. Vui lòng kiểm tra lại.', 'info');
  }

  function getQuoteDataFromForm() {
    const quoteData = {
      baoGiaID: $('#quote-id-input').val() || 0,
      quoteInfo: {},
      items: [],
      totals: {}
    };
    
    quoteData.quoteInfo = {
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
        coCheGia: ($('#price-schema').val() || 'p1').toUpperCase(),
        trangThai: $('#quote-status-select').val() || 'Mới tạo',
        phanTramDieuChinh: parseNumber($('#price-adjustment-percentage-input').val()),
        thuePhanTram: parseNumber($('#tax-percentage-input').val())
    };
    
    quoteData.totals = {
        subtotal: parseNumber($('#subtotal').text()),
        vat: parseNumber($('#vat').text()),
        total: parseNumber($('#total').text())
    };

    let itemOrder = 0;
    
    const processContainer = (container, areaName) => {
        let currentGroupPUR = null;
        container.find('.pur-items-bom > tr').each(function() {
            const row = $(this);
            if (row.hasClass('group-header')) {
                currentGroupPUR = row.find('div[contenteditable]').text().trim();
            } else if (row.hasClass('bom-item-row')) {
                pushItemData(row, areaName, currentGroupPUR);
            }
        });

        let currentGroupULA = null;
        container.find('.ula-items-bom > tr').each(function() {
            const row = $(this);
            if (row.hasClass('group-header')) {
                currentGroupULA = row.find('div[contenteditable]').text().trim();
            } else if (row.hasClass('bom-item-row')) {
                pushItemData(row, areaName, currentGroupULA);
            }
        });
    };

    const pushItemData = (row, areaName, groupName) => {
        const productCode = row.find('.product-code').val().trim();
        const productData = row.data('product-data');

        if (productCode) {
            quoteData.items.push({
                productId: productData ? (productData.productId || null) : null,
                code: productCode,
                name: productData ? (productData.name || '') : '',
                groupName: groupName,
                order: ++itemOrder,
                id_thongso: productData ? (productData.id_thongso || null) : row.find('.product-id-thongso').text(),
                thickness: productData ? (productData.thickness || null) : row.find('.dim-thickness').text(),
                width: productData ? (productData.width || null) : row.find('.dim-width').text(),
                unit: 'Bộ',
                quantity: parseNumber(row.find('.product-quantity').val()),
                unitPrice: parseNumber(row.find('.unit-price').val()),
                lineTotal: parseNumber(row.find('.line-total').text()),
                note: row.find('.note').val(),
                khuVuc: areaName, 
            });
        }
    };

    $('.area-block').each(function() {
        const areaName = $(this).find('.area-block-header').text().trim();
        processContainer($(this), areaName);
    });

    processContainer($('#default-products-container'), null);

    return quoteData;
  }

  function populateFormWithData(quote) {
    const info = quote.info;
    $('#quote-id-input').val(info.BaoGiaID);
    const defaultProductImage = 'uploads/default_image.png';
    const defaultQrImage = 'uploads/qr.png';
    $('#company-id-input').val(info.CongTyID);
    $('#customer-name-input').val(info.TenCongTy);
    $('#customer-address-input').val(info.DiaChiKhach);
    $('#recipient-name-input').val(info.NguoiNhan);
    $('#recipient-phone-input').val(info.SoDiDongKhach);
    $('#category-input').val(info.HangMuc);
    
   // === XỬ LÝ DỰ ÁN (ĐÃ SỬA LỖI) ===
    $('#project-id-input').val(info.DuAnID || '');
    $('#project-name-input').val(info.TenDuAn || '');
    $('#project-address-input').val(''); // Đặt lại địa chỉ trước
    selectedProject = null; // Đặt lại dự án đã chọn trước

    if (info.DuAnID) {
        // Ưu tiên tìm theo ID nếu có (hành vi cũ, vẫn đúng)
        const projectById = projectList.find(p => p.DuAnID == info.DuAnID);
        if (projectById) {
            selectedProject = projectById;
            $('#project-address-input').val(projectById.DiaChi || '');
        }
    } else if (info.TenDuAn) {
        // MỚI: Nếu không có ID nhưng có Tên, thử tìm theo tên
        // Điều này xử lý các báo giá cũ
        const projectNameTrimmed = info.TenDuAn.trim();
        const projectByName = projectList.find(p => p.TenDuAn === projectNameTrimmed);
        
        if (projectByName) {
            // Nếu tìm thấy một dự án khớp chính xác với tên đã lưu
            selectedProject = projectByName;
            // Cập nhật lại các trường bị thiếu để đồng bộ
            $('#project-id-input').val(projectByName.DuAnID);
            $('#project-address-input').val(projectByName.DiaChi || '');
        }
    }
    
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
    $('#payment-terms-input').val(info.DieuKienThanhToan);
    $('#delivery-location-input').val(info.DiaChiGiaoHang);
    $('#image-path').val(info.HinhAnh1 || defaultProductImage);
    $('#image-preview').attr('src', info.HinhAnh1 || defaultProductImage);
    $('#qr-path').val(info.HinhAnh2 || defaultQrImage);
    $('#qr-preview').attr('src', info.HinhAnh2 || defaultQrImage);
    $('#price-schema').val((info.CoCheGiaApDung || 'p1').toLowerCase());
    $('#tax-percentage-input').val(info.ThuePhanTram !== null && info.ThuePhanTram !== undefined ? info.ThuePhanTram : 8);
    $('#price-adjustment-percentage-input').val(info.PhanTramDieuChinh !== undefined ? info.PhanTramDieuChinh : 0);
    $('#quote-status-select').val(info.TrangThai || 'Mới tạo');
    
    $('#shipping-fee-bom').empty().html(createShippingRow());
    if (info.DonGiaVanChuyen !== undefined && info.SoLuongVanChuyen !== undefined) {
      $('#shipping-quantity-input').val(formatNumber(info.SoLuongVanChuyen));
      $('#shipping-unit-price-input').val(formatNumber(info.DonGiaVanChuyen));
      $('#shipping-line-total').text(formatNumber(info.SoLuongVanChuyen * info.DonGiaVanChuyen));
      $('#shipping-fee-bom .note').val(info.GhiChuVanChuyen || '');
    }

    $('#areas-container, .pur-items-bom, .ula-items-bom').empty();
    
    if (quote.items && quote.items.length > 0) {
        const itemsByArea = groupBy(quote.items, 'KhuVuc');
        let hasAreas = false;

        for (const areaName in itemsByArea) {
            const itemsInArea = itemsByArea[areaName];
            let purTbody, ulaTbody;

            if (areaName === 'no-area' || areaName === 'null' || !areaName) {
                purTbody = $('#default-products-container .pur-items-bom');
                ulaTbody = $('#default-products-container .ula-items-bom');
            } else {
                hasAreas = true;
                const areaBlockHtml = createAreaBlock(areaName);
                const areaBlock = $(areaBlockHtml).appendTo('#areas-container');
                purTbody = areaBlock.find('.pur-items-bom');
                ulaTbody = areaBlock.find('.ula-items-bom');
            }
            
            const ulaItems = itemsInArea.filter(item => (item.MaHang || '').toUpperCase().startsWith('ULA'));
            const ulaItemIds = ulaItems.map(item => item.ChiTietID);
            const purItems = itemsInArea.filter(item => !ulaItemIds.includes(item.ChiTietID));

            populateTable(purItems, purTbody);
            populateTable(ulaItems, ulaTbody);
        }

        if(hasAreas) {
            $('#default-products-container').hide();
        } else {
            $('#default-products-container').show();
        }
    }
    
    function populateTable(items, tbody) {
        const itemsByGroup = groupBy(items, 'TenNhom');
        for (const groupName in itemsByGroup) {
            const itemsInGroup = itemsByGroup[groupName];
            if (groupName && groupName !== 'null' && groupName !== 'undefined' && itemsInGroup.length > 0) {
                const productInfo = productList.find(p => p.code == itemsInGroup[0].MaHang);
                const categoryId = productInfo ? productInfo.categoryId : 'other';
                tbody.append(createRow('group', { id: categoryId, name: groupName }));
            }
            itemsInGroup.forEach(item => {
                const fullProductData = productList.find(p => p.code == item.MaHang) || {};
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
                tbody.append(newRow);
            });
        }
    }

    addEmptyRow($('#default-products-container .pur-items-bom'));
    addEmptyRow($('#default-products-container .ula-items-bom'));
    $('.area-block').each(function() {
        addEmptyRow($(this).find('.pur-items-bom'));
        addEmptyRow($(this).find('.ula-items-bom'));
    });
    renumberRows();
    updateAllTotals();

    if (info.ThoiGianGiaoHang) {
        $('#delivery-conditions-input').val(info.ThoiGianGiaoHang);
    }
  }

  function initializeForm() {
    selectedCustomer = null;
    selectedProject = null;
    const defaultProductImage = 'uploads/default_image.png';
    const defaultQrImage = 'uploads/qr.png';
    $('input[type="text"], input[type="hidden"]').not('#quote-validity-input, #category-input, #origin, #delivery-conditions-input, #payment-terms-input, #quote_date, #quote-person-input, #position-input, #mobile-input, #price-adjustment-percentage-input, #tax-percentage-input').val('');
    $('#image-path').val(defaultProductImage);
    $('#image-preview').attr('src', defaultProductImage);
    $('#qr-path').val(defaultQrImage);
    $('#qr-preview').attr('src', defaultQrImage);
    $('#areas-container, .pur-items-bom, .ula-items-bom').empty();
    $('#default-products-container').show();
    addEmptyRow($('#default-products-container .pur-items-bom'));
    addEmptyRow($('#default-products-container .ula-items-bom'));
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
    $('#price-schema').val('p1');
    $('#quote-status-select').val('Mới tạo');
    $('#price-adjustment-percentage-input').val(0);
    $('#tax-percentage-input').val(8);
    
    $('#delivery-conditions-input').val(getDeliveryTime(0));

    activeProductCategoryFilter = 'all';
    $('#product-category-filter-select').val('all');

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
          const customer = customerList.find(c => c.CongTyID === res.quote.info.CongTyID);
          if (customer) {
            selectedCustomer = customer;
          }
          $('#page-title').text(`Chỉnh Sửa Báo Giá: ${res.quote.info.SoBaoGia}`);
          const readOnlyStates = ['Chốt', 'Tách'];
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

  // ⭐ CẬP NHẬT HÀM saveQuote() - THÊM VALIDATION DỰ ÁN
  function saveQuote() {
    const quoteData = getQuoteDataFromForm();
    
    // Validation 1: Khách hàng bắt buộc
    if (!quoteData.quoteInfo.congTyID) {
      showMessageModal('Bạn phải chọn một khách hàng hợp lệ từ danh sách.', 'error');
      $('#customer-name-input').focus().css('border', '2px solid red');
      setTimeout(() => $('#customer-name-input').css('border', ''), 2000);
      return;
    }
    
    // Validation 2: Số báo giá
    if (!quoteData.quoteInfo.soBaoGia) {
      showMessageModal('Số báo giá không được để trống.', 'error');
      return;
    }
    
    // Validation 3: Sản phẩm
    if (quoteData.items.length === 0) {
      showMessageModal('Vui lòng thêm ít nhất một sản phẩm vào báo giá.', 'error');
      return;
    }
    
    // ⭐ VALIDATION 4: DỰ ÁN (MỚI)
    const projectNameInput = $('#project-name-input').val().trim();
    
    // Nếu có nhập dự án nhưng không chọn từ danh sách
    if (projectNameInput !== '' && !selectedProject) {
      showMessageModal(
        'Dự án không hợp lệ! Vui lòng chọn dự án từ danh sách gợi ý hoặc xóa để bỏ trống.', 
        'error'
      );
      $('#project-name-input').focus().css('border', '2px solid red');
      setTimeout(() => $('#project-name-input').css('border', ''), 2000);
      return;
    }
    
    // Nếu có selectedProject nhưng tên không khớp (người dùng sửa sau khi chọn)
    if (selectedProject && selectedProject.TenDuAn !== projectNameInput) {
      showMessageModal(
        'Tên dự án đã bị thay đổi! Vui lòng chọn lại từ danh sách.', 
        'error'
      );
      $('#project-name-input').focus().css('border', '2px solid red');
      setTimeout(() => $('#project-name-input').css('border', ''), 2000);
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
      showConfirmationModal("Bạn có chắc chắn muốn chốt báo giá này và tạo đơn hàng không? Hành động này không thể hoàn tác. <p style='color:red'>Nếu bạn chắc chắn dữ liệu đúng, vui lòng nhấn OK.</p>", proceedWithSave);
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
    
    if (query.length < 1 && activeProductCategoryFilter !== 'all') { 
        hideProductSuggestions(); 
        return; 
    }

    let sourceProductList = productList;
    const categoryFilterId = activeProductCategoryFilter;

    if (categoryFilterId && categoryFilterId !== 'all') {
      sourceProductList = productList.filter(p => p.categoryId == categoryFilterId);
    }

    const terms = query.split(' ').filter(t => t.length > 0);
    if (terms.length > 0) {
      filteredProducts = sourceProductList.filter(p => {
        const searchString = `${(p.code || '').toLowerCase()} ${(p.name || '').toLowerCase()}`;
        return terms.every(term => searchString.includes(term));
      });
    } else {
      filteredProducts = sourceProductList;
    }
    
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
    const list = filteredCustomers.slice(0, 12).map((c, index) => {
      const namePart = c.TenNguoiLienHe ? ('<b>' + c.TenNguoiLienHe + '</b>') : '<i class="text-gray-400">(Công ty)</i>';
      const companyPart = c.TenCongTy || '';
      return `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center"><div>${namePart}<small class="text-gray-500"> - ${companyPart}</small></div><span class="f-key-hint">F${index + 1}</span></li>`;
    }).join('');
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
        priceSchemaSelect.val(desiredSchemaValue);
      }
    }
    
    $('#price-adjustment-percentage-input').val(0);
    applyPriceAdjustment();
    
    $('#recipient-name-input').focus();
  }

  // =================================================================
  // XỬ LÝ DỰ ÁN
  // =================================================================
  
  function showProjectSuggestions(input) {
    const query = input.val().toLowerCase();
    if (query.length < 1) { 
      hideProjectSuggestions(); 
      return; 
    }
    
    filteredProjects = projectList.filter(p => 
      (p.TenDuAn || '').toLowerCase().includes(query) || 
      (p.MaDuAn && p.MaDuAn.toLowerCase().includes(query))
    );
    
    if (filteredProjects.length === 0) { 
      hideProjectSuggestions(); 
      return; 
    }
    
    const list = filteredProjects.slice(0, 12).map((p, index) => {
      const diaChi = p.DiaChi ? `<small class="text-gray-500"> - ${p.DiaChi}</small>` : '';
      return `<li data-index="${index}" class="p-2 hover:bg-blue-100 cursor-pointer flex justify-between items-center">
        <div>
          <b>${p.MaDuAn || ''}</b> 
          <small>${p.TenDuAn || ''}</small>
          ${diaChi}
        </div>
        <span class="f-key-hint">F${index + 1}</span>
      </li>`;
    }).join('');
    
    projectSuggestionBox
      .html(`<ul>${list}</ul>`)
      .show()
      .css({ 
        top: input.offset().top + input.outerHeight(), 
        left: input.offset().left, 
        width: Math.max(input.outerWidth(), 450) 
      });
  }

  function hideProjectSuggestions() { 
    projectSuggestionBox.hide().empty(); 
  }

  // ⭐ CẬP NHẬT HÀM updateFormWithProjectData - THÊM VISUAL FEEDBACK
  function updateFormWithProjectData(project) {
    selectedProject = project;
    $('#project-id-input').val(project.DuAnID || '');
    $('#project-name-input').val(project.TenDuAn || '');
    $('#project-address-input').val(project.DiaChi || '');
    
    // Visual feedback
    $('#project-name-input').css('border', '2px solid green');
    setTimeout(() => {
        $('#project-name-input').css('border', '');
    }, 1000);
    
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
    .on('click.quoteCreate', '#add-pur-row-btn', () => addEmptyRow($('#default-products-container .pur-items-bom')))
    .on('click.quoteCreate', '#add-ula-row-btn', () => addEmptyRow($('#default-products-container .ula-items-bom')))
    .on('click.quoteCreate', '#add-area-btn', function() {
        $('#default-products-container').hide();
        const newArea = $(createAreaBlock()).appendTo('#areas-container');
        addEmptyRow(newArea.find('.pur-items-bom'));
        addEmptyRow(newArea.find('.ula-items-bom'));
    })
    .on('click.quoteCreate', '.add-pur-row-area', function() {
        const purTbody = $(this).closest('.area-block').find('.pur-items-bom');
        addEmptyRow(purTbody);
    })
    .on('click.quoteCreate', '.add-ula-row-area', function() {
        const ulaTbody = $(this).closest('.area-block').find('.ula-items-bom');
        addEmptyRow(ulaTbody);
    })
    .on('click.quoteCreate', '.remove-row-btn', function () {
      const row = $(this).closest('tr');
      const groupHeader = row.prevAll('.group-header:first');
      row.remove();
      if (groupHeader.length > 0) {
        const nextElement = groupHeader.next();
        if (!nextElement.length || nextElement.hasClass('group-header') || nextElement.hasClass('area-block')) {
          groupHeader.remove();
        }
      }
      updateAllTotals();
      renumberRows();
    })
    .on('click.quoteCreate', '.remove-area-btn', function() {
        $(this).closest('.area-block').remove();
        if ($('#areas-container .area-block').length === 0) {
            $('#default-products-container').show();
        }
        updateAllTotals();
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
    .on('input.quoteCreate', '#tax-percentage-input, .area-block-header', updateAllTotals)
    .on('input.quoteCreate', '#price-adjustment-percentage-input', applyPriceAdjustment)
    .on('focus.quoteCreate', '.product-code', function () { currentActiveProductInput = $(this); showProductSuggestions(); })
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
    .on('blur.quoteCreate', '#quote_date', generateQuoteNumber)
    .on('keyup.quoteCreate', '#project-name-input', function (e) {
      if (![13, 27, 38, 40].includes(e.keyCode) && !(e.keyCode >= 112 && e.keyCode <= 123)) {
        const currentInputName = $(this).val().trim();
        if (selectedProject && selectedProject.TenDuAn !== currentInputName) {
          selectedProject = null;
          $('#project-id-input').val('');
          $('#project-address-input').val('');
        }
        showProjectSuggestions($(this));
      }
    })
    // ⭐ THÊM EVENT BLUR CHO DỰ ÁN - VALIDATION REALTIME
    .on('blur.quoteCreate', '#project-name-input', function () {
      const projectName = $(this).val().trim();
      
      if (projectName !== '') {
        if (!selectedProject) {
          // Tìm xem có dự án nào khớp chính xác không
          const exactMatch = projectList.find(p => 
            p.TenDuAn === projectName || p.MaDuAn === projectName
          );
          
          if (exactMatch) {
            updateFormWithProjectData(exactMatch);
          } else {
            $(this).css('border', '2px solid orange');
            showMessageModal(
              'Dự án "' + projectName + '" không có trong danh sách. Vui lòng chọn từ gợi ý hoặc nhấn dấu + để thêm mới.',
              'warning'
            );
            setTimeout(() => {
              $(this).css('border', '');
            }, 3000);
          }
        } else if (selectedProject.TenDuAn !== projectName) {
          $(this).css('border', '2px solid red');
          showMessageModal(
            'Tên dự án đã bị thay đổi! Vui lòng chọn lại từ danh sách.',
            'error'
          );
          
          setTimeout(() => {
            if (selectedProject) {
              $(this).val(selectedProject.TenDuAn);
            }
            $(this).css('border', '');
          }, 2000);
        }
      } else {
        selectedProject = null;
        $('#project-id-input').val('');
        $('#project-address-input').val('');
        $(this).css('border', '');
      }
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

  $(document).off('change.priceSchemaHandler').on('change.priceSchemaHandler', '#price-schema', function () {
      $('#price-adjustment-percentage-input').val(0);
      applyPriceAdjustment(); 
      generateQuoteNumber();
  });

  mainContentContainer.on('click.quoteCreate', '#add_KH', function() {
      showQuickAddModal({
          title: 'Thêm Nhanh Khách Hàng',
          formHTML: `
              <div class="mb-4">
                  <label for="MaCongTy" class="block mb-2 text-sm font-medium text-gray-900">Mã Khách Hàng</label>
                  <input type="text" name="MaCongTy" id="MaCongTy" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
              </div>
              <div>
                  <label for="TenCongTy" class="block mb-2 text-sm font-medium text-gray-900">Tên Khách Hàng</label>
                  <input type="text" name="TenCongTy" id="TenCongTy" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
              </div>
          `,
          onSubmit: function(formData, closeModal, enableButton) {
              $.ajax({
                  url: 'api/create_company_quick.php',
                  method: 'POST',
                  contentType: 'application/json',
                  data: JSON.stringify(formData),
                  dataType: 'json',
                  success: function (response) {
                      if (response.success) {
                          showMessageModal(response.message, 'success');
                          customerList.push(response.newCustomer);
                          updateFormWithCustomerData(response.newCustomer);
                          closeModal();
                      } else {
                          showMessageModal('Lỗi: ' + response.message, 'error');
                          enableButton();
                      }
                  },
                  error: function () {
                      showMessageModal('Lỗi kết nối máy chủ.', 'error');
                      enableButton();
                  }
              });
          }
      });
  });

  mainContentContainer.on('click.quoteCreate', '#add_DA', function() {
      showQuickAddModal({
          title: 'Thêm Nhanh Dự Án',
          formHTML: `
              <div class="mb-4">
                  <label for="MaDuAn" class="block mb-2 text-sm font-medium text-gray-900">
                      Mã Dự Án <span class="text-red-500">*</span>
                  </label>
                  <input type="text" name="MaDuAn" id="MaDuAn" 
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                      placeholder="Nhập mã dự án..." required>
              </div>
              <div class="mb-4">
                  <label for="TenDuAn" class="block mb-2 text-sm font-medium text-gray-900">
                      Tên Dự Án <span class="text-red-500">*</span>
                  </label>
                  <input type="text" name="TenDuAn" id="TenDuAn" 
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                      placeholder="Nhập tên dự án..." required>
              </div>
              <div>
                  <label for="DiaChi" class="block mb-2 text-sm font-medium text-gray-900">
                      Địa chỉ Dự Án
                  </label>
                  <textarea name="DiaChi" id="DiaChi" rows="3"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                      placeholder="Nhập địa chỉ dự án (không bắt buộc)..."></textarea>
              </div>
          `,
          onSubmit: function(formData, closeModal, enableButton) {
              $.ajax({
                  url: 'api/create_project_quick.php',
                  method: 'POST',
                  contentType: 'application/json',
                  data: JSON.stringify(formData),
                  dataType: 'json',
                  success: function (response) {
                      if (response.success) {
                          showMessageModal(response.message, 'success');
                          projectList.push(response.newProject);
                          updateFormWithProjectData(response.newProject);
                          closeModal();
                      } else {
                          showMessageModal('Lỗi: ' + response.message, 'error');
                          enableButton();
                      }
                  },
                  error: function () {
                      showMessageModal('Lỗi kết nối máy chủ.', 'error');
                      enableButton();
                  }
              });
          }
      });
  });
    
  mainContentContainer.off('change.quoteCreateFilter').on('change.quoteCreateFilter', '#product-category-filter-select', function() {
    const categoryId = $(this).val();
    if (!categoryId) return;

    activeProductCategoryFilter = categoryId;
    
    if(currentActiveProductInput && currentActiveProductInput.is(':focus')) {
        showProductSuggestions();
    } else {
        const firstEmptyInput = $('#pur-items-bom, #ula-items-bom').find('.product-code').filter(function() {
            return $(this).val() === '';
        }).first();
        
        if (firstEmptyInput.length > 0) {
            firstEmptyInput.focus();
        }
    }
  });

  $('.export-btn').on('click', function(e) {
    e.stopPropagation();
    let dropdown = $(this).siblings('.dropdown-menu');
    $('.dropdown-menu').not(dropdown).addClass('hidden');
    dropdown.toggleClass('hidden');
  });
  
  $(document).off('click.dropdown').on('click.dropdown', '.dropdown-item', function() {
    $(this).closest('.dropdown-menu').addClass('hidden');
  });

  $(document).off('click.closeDropdowns').on('click.closeDropdowns', function() {
    $('.dropdown-menu').addClass('hidden');
  });

  $(document).on('click.quoteCreate', '#export-pdf-bg-btn, #export-pdf-TQ-btn, #export-excel-btn, #export-excel-TQ-btn', function(e) {
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