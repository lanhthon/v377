<?php
// api.php
error_reporting(0); // Thêm dòng này để ngăn các lỗi PHP không mong muốn làm hỏng JSON
// --- Tải thư viện PhpSpreadsheet (dùng cho việc nhập và xuất file) ---
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

// --- KẾT NỐI CSDL ---
require_once __DIR__ . '/../config/database.php'; // Đảm bảo đường dẫn này đúng
$conn->set_charset("utf8mb4");

// --- API ROUTER ---
$request_body = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $request_body['action'] ?? null;

if (!$action) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
    exit;
}

$response = ['success' => true, 'data' => [], 'message' => ''];

try {
    switch ($action) {

        // === [UPDATED] CASE LẤY DỮ LIỆU THỐNG KÊ SẢN PHẨM ===
        case 'get_product_stats':
            $sql = "
                SELECT
                    COUNT(v.variant_id) AS totalProducts,
                    SUM(CASE WHEN v.variant_sku LIKE 'PUR-%' THEN 1 ELSE 0 END) AS totalPUR,
                    (SELECT COUNT(DISTINCT v_ula.variant_id) FROM variants v_ula WHERE v_ula.variant_sku LIKE 'ULA%') as totalULA,
                    SUM(CASE
                        WHEN v.variant_sku LIKE 'ULA%' AND
                             (EXISTS (SELECT 1
                                      FROM variant_attributes va
                                      JOIN attribute_options ao ON va.option_id = ao.option_id
                                      JOIN attributes a ON ao.attribute_id = a.attribute_id
                                      WHERE va.variant_id = v.variant_id
                                        AND a.name = 'Định mức đóng thùng/tải'
                                        AND ao.value IS NOT NULL AND ao.value != ''))
                        THEN 1
                        ELSE 0
                    END) AS ulaWithDinhMucDongThung,
                    SUM(CASE
                        WHEN v.variant_sku LIKE 'ULA%' AND
                             (EXISTS (SELECT 1
                                      FROM variant_attributes va
                                      JOIN attribute_options ao ON va.option_id = ao.option_id
                                      JOIN attributes a ON ao.attribute_id = a.attribute_id
                                      WHERE va.variant_id = v.variant_id
                                        AND a.name = 'Định mức kg/ bộ'
                                        AND ao.value IS NOT NULL AND ao.value != ''))
                        THEN 1
                        ELSE 0
                    END) AS ulaWithDinhMucKgBo
                FROM
                    variants v
            ";
            $result = $conn->query($sql);
            $stats = $result->fetch_assoc();
            
            // Lấy tổng số thuộc tính (cột)
            $attr_result = $conn->query("SELECT COUNT(*) as totalAttributes FROM attributes");
            $attr_count = $attr_result->fetch_assoc();
            
            // Lấy tổng số sản phẩm gốc
            $base_product_result = $conn->query("SELECT COUNT(*) as totalBaseProducts FROM products");
            $base_product_count = $base_product_result->fetch_assoc();

            $response['data'] = [
                'totalProducts' => $stats['totalProducts'] ?? 0,
                'totalBaseProducts' => $base_product_count['totalBaseProducts'] ?? 0,
                'totalPUR' => $stats['totalPUR'] ?? 0,
                'totalULA' => $stats['totalULA'] ?? 0,
                'ulaWithDinhMucDongThung' => $stats['ulaWithDinhMucDongThung'] ?? 0,
                'ulaWithDinhMucKgBo' => $stats['ulaWithDinhMucKgBo'] ?? 0,
                'totalColumns' => $attr_count['totalAttributes'] ?? 0,
            ];
            break;

        // === [UPDATED] CASE XUẤT FILE EXCEL (.XLSX) - BỔ SUNG HƯỚNG DẪN CHI TIẾT ===
        case 'export_product_template':
            $spreadsheet = new Spreadsheet();
            
            // --- Tạo sheet hướng dẫn ---
            $instructionSheet = $spreadsheet->getActiveSheet();
            $instructionSheet->setTitle('Huong Dan Su Dung');
            
            // Định nghĩa các style sẽ dùng chung
            $header_style = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E2E2']]];
            $table_style = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]]];

            // Thêm nội dung và định dạng cho sheet hướng dẫn
            $instructionSheet->setCellValue('A1', 'HƯỚNG DẪN SỬ DỤNG FILE NHẬP LIỆU SẢN PHẨM');
            $instructionSheet->mergeCells('A1:C1');
            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $instructionSheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00');

            $instructions = [
                ['1. QUY TRÌNH LÀM VIỆC ĐÚNG:', ''],
                ['', '  BƯỚC 1: Luôn LUÔN tải file mẫu mới nhất từ phần mềm (`Tải file mẫu`).'],
                ['', '  BƯỚC 2: Mở file vừa tải về, chỉnh sửa thông tin, thêm sản phẩm mới.'],
                ['', '  BƯỚC 3: Lưu file và nhập lại vào phần mềm (`Nhập Excel`).'],
                ['', '  LƯU Ý QUAN TRỌNG: KHÔNG sử dụng lại file Excel cũ đã tải về từ trước đó. Việc này có thể làm MẤT DỮ LIỆU các sản phẩm mới được tạo trên phần mềm trong thời gian bạn chỉnh sửa file cũ.'],
                ['2. Mục đích:', 'File này dùng để CẬP NHẬT và THÊM MỚI hàng loạt sản phẩm chi tiết. Chức năng XÓA đã được tắt để đảm bảo an toàn dữ liệu.'],
                ['3. Cấu trúc file:', ''],
                ['', '- Sheet `DS Hien Dang Co`: Chứa toàn bộ dữ liệu sản phẩm. Đây là sheet chính để bạn chỉnh sửa.'],
                ['', '- Các sheet tham chiếu (`2-DS_SP_Goc`,...): Chứa danh sách các giá trị hợp lệ để bạn tham khảo.'],
                ['4. QUY TẮC BẮT BUỘC:', ''],
                ['', 'Cột `ID (Không sửa)`:'],
                ['', '  - Đây là mã định danh DUY NHẤT của sản phẩm trong hệ thống.'],
                ['', '  - TUYỆT ĐỐI KHÔNG SỬA, KHÔNG XÓA giá trị ở cột này đối với các sản phẩm đã có.'],
                ['', '  - Để trống cột ID nếu bạn muốn THÊM MỚI một sản phẩm.'],
                ['', '  - CẢNH BÁO: Không tự ý sửa hoặc xóa các sản phẩm đang được sử dụng trong các quy trình hoạt động của phần mềm để tránh gây lỗi hệ thống.'],
                ['', 'Cột `Mã SKU (*)`:'],
                ['', '  - BẮT BUỘC phải có đối với sản phẩm mới (dòng có ID để trống).'],
                ['', '  - Mã SKU phải là duy nhất cho mỗi sản phẩm. Hệ thống sẽ báo lỗi nếu SKU đã tồn tại.'],
                ['', 'Các cột có dấu `(*)` là các cột thông tin bắt buộc, không được để trống.'],
                ['5. CÁC THAO TÁC CHÍNH:', ''],
                ['', 'Để CẬP NHẬT sản phẩm:'],
                ['', '  - Tìm đến dòng sản phẩm, giữ nguyên ID và chỉnh sửa thông tin ở các cột khác.'],
                ['', 'Để THÊM MỚI sản phẩm:'],
                ['', '  - Thêm một dòng mới ở cuối, ĐỂ TRỐNG cột ID và điền đầy đủ thông tin.'],
                ['6. LƯU Ý VỀ MÃ SKU VÀ HẬU TỐ (SUFFIX):', ''],
                ['', 'Các Mã SKU được hỗ trợ đầy đủ bởi chương trình:'],
                ['', '  - Các mã có tiền tố PUR-S (vuông), PUR-C (tròn), ULA, DT, ECU, CV, CT được hỗ trợ đầy đủ cho các chức năng tính định mức và các nghiệp vụ liên quan.'],
                ['', '  - Các mã không có trong danh sách trên sẽ chỉ có giá trị quản lý xuất/nhập kho, không được sử dụng trong các tính toán phức tạp khác của hệ thống.'],
                ['', 'Quy tắc về Hậu tố (Suffix):'],
                ['', '  - Hậu tố dùng để phân biệt các biến thể có cùng thuộc tính nhưng khác nhau về nguồn gốc hoặc đặc tính khác (ví dụ: TQ - Trung Quốc, HT - Hàng Tốt).'],
                ['', '  - Nếu một Thành phẩm có điền hậu tố (ví dụ: PUR-S-021-TQ), hệ thống sẽ ưu tiên tìm các Bán thành phẩm có cùng hậu tố (-TQ) để tính định mức.'],
                ['', '  - Nếu không tìm thấy Bán thành phẩm có hậu tố tương ứng, hệ thống sẽ mặc định sử dụng Bán thành phẩm không có hậu tố nào.'],
                ['7. MẸO LÀM VIỆC VỚI EXCEL:', ''],
                ['', '- Bạn hoàn toàn có thể sử dụng các công cụ của Excel như copy, paste, kéo công thức để điền dữ liệu nhanh chóng. Việc này không ảnh hưởng đến quá trình nhập file, miễn là dữ liệu cuối cùng đúng định dạng.'],
                ['', '- KIỂM TRA MÃ SKU BỊ TRÙNG (QUAN TRỌNG): Hệ thống yêu cầu mỗi Mã SKU phải là duy nhất. Để tránh lỗi khi nhập file, bạn nên kiểm tra các mã bị trùng trước khi lưu. Dưới đây là cách thực hiện:'],
                ['', '  - Cách 1 (Dễ nhất - Tô màu mã trùng):'],
                ['', '    1. Chọn toàn bộ cột \'C\' (cột Mã SKU (*)).'],
                ['', '    2. Trên thanh công cụ, chọn \'Home\' -> \'Conditional Formatting\' -> \'Highlight Cells Rules\' -> \'Duplicate Values...\''],
                ['', '    3. Bấm \'OK\'. Excel sẽ tự động tô màu tất cả các ô chứa mã SKU bị trùng lặp, giúp bạn dễ dàng tìm và sửa lại.'],
                ['', '  - Cách 2 (Dùng công thức):'],
                ['', '    1. Tạo một cột phụ ở cuối bảng, ví dụ cột \'Z\'.'],
                ['', '    2. Tại ô Z2 (ngang hàng với dòng dữ liệu đầu tiên), nhập công thức: =COUNTIF(C:C; C2)>1'],
                ['', '    3. Kéo công thức này xuống cho toàn bộ các dòng. Những dòng nào có kết quả là \'TRUE\' thì đó chính là dòng có Mã SKU bị trùng lặp.'],
            ];

            $rowNum = 3;
            foreach ($instructions as $inst) {
                $instructionSheet->setCellValue('A' . $rowNum, $inst[0]);
                $instructionSheet->setCellValue('B' . $rowNum, $inst[1]);
                if (!empty($inst[0])) {
                    $instructionSheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
                }
                $rowNum++;
            }

            $instructionSheet->getColumnDimension('A')->setWidth(25);
            $instructionSheet->getColumnDimension('B')->setWidth(100);
            $instructionSheet->getStyle('B3:B' . ($rowNum - 1))->getAlignment()->setWrapText(true);
            $instructionSheet->getStyle('A1:B' . ($rowNum - 1))->getAlignment()->setVertical('top');
            $instructionSheet->getStyle('A3:B' . ($rowNum - 1))->applyFromArray($table_style);

            // --- Tạo sheet dữ liệu sản phẩm ---
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('DS Hien Dang Co');

            $attributes_result = $conn->query("SELECT name FROM attributes ORDER BY order_index ASC, name ASC");
            $attribute_names = [];
            while($row = $attributes_result->fetch_assoc()) {
                $attribute_names[] = $row['name'];
            }

            $base_headers = ['ID (Không sửa)', 'STT', 'Mã SKU (*)', 'Tên Biến Thể (*)', 'Tên SP Gốc (*)', 'Nhóm SP (*)', 'Loại Phân Loại (*)', 'Đơn vị tính', 'Giá', 'Tồn kho', 'Hậu tố SKU'];
            $full_headers = array_merge($base_headers, $attribute_names);
            
            $sheet->fromArray($full_headers, NULL, 'A1');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($header_style);

            $all_rows_data = [];
            $stt = 1;
            
            $sql_variants = "
                SELECT 
                    v.variant_id, v.variant_sku, v.variant_name, v.price, v.sku_suffix, 
                    p.name AS product_name, 
                    pg.name AS group_name, 
                    l.TenLoai AS loai_name, 
                    vi.quantity,
                    u.name as unit_name 
                FROM variants v 
                LEFT JOIN products p ON v.product_id = p.product_id 
                LEFT JOIN product_groups pg ON p.group_id = pg.group_id 
                LEFT JOIN loaisanpham l ON v.LoaiID = l.LoaiID 
                LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id
                LEFT JOIN units u ON p.base_unit_id = u.unit_id
                ORDER BY v.variant_id DESC";
            $variants_result = $conn->query($sql_variants);

            if ($variants_result) {
                $attr_stmt = $conn->prepare("SELECT a.name AS attribute_name, ao.value AS attribute_value FROM variant_attributes va JOIN attribute_options ao ON va.option_id = ao.option_id JOIN attributes a ON ao.attribute_id = a.attribute_id WHERE va.variant_id = ?");
                $header_map = array_flip($full_headers);

                while ($variant = $variants_result->fetch_assoc()) {
                    $row_data = array_fill(0, count($full_headers), '');
                    $row_data[$header_map['ID (Không sửa)']] = $variant['variant_id'];
                    $row_data[$header_map['STT']] = $stt++;
                    $row_data[$header_map['Mã SKU (*)']] = $variant['variant_sku'];
                    $row_data[$header_map['Tên Biến Thể (*)']] = $variant['variant_name'];
                    $row_data[$header_map['Tên SP Gốc (*)']] = $variant['product_name'];
                    $row_data[$header_map['Nhóm SP (*)']] = $variant['group_name'];
                    $row_data[$header_map['Loại Phân Loại (*)']] = $variant['loai_name'];
                    $row_data[$header_map['Đơn vị tính']] = $variant['unit_name'];
                    $row_data[$header_map['Giá']] = $variant['price'];
                    $row_data[$header_map['Tồn kho']] = $variant['quantity'];
                    $row_data[$header_map['Hậu tố SKU']] = $variant['sku_suffix'];

                    $attr_stmt->bind_param("i", $variant['variant_id']);
                    $attr_stmt->execute();
                    $attributes_for_variant = $attr_stmt->get_result();
                    while ($attr = $attributes_for_variant->fetch_assoc()) {
                        if (isset($header_map[$attr['attribute_name']])) {
                            $row_data[$header_map[$attr['attribute_name']]] = $attr['attribute_value'];
                        }
                    }
                    $all_rows_data[] = $row_data;
                }
                $attr_stmt->close();
            }
            
            $sheet->fromArray($all_rows_data, NULL, 'A2');

            // --- Cải tiến sheet dữ liệu ---
            // Tự động điều chỉnh độ rộng cột
            foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            // Cố định dòng tiêu đề và 3 cột đầu tiên
            $sheet->freezePane('D2');
            // Thêm bộ lọc tự động (auto-filter)
            $sheet->setAutoFilter('A1:' . $sheet->getHighestColumn() . '1');


            // --- Tạo các sheet tham chiếu ---
            $spGocSheet = $spreadsheet->createSheet();
            $spGocSheet->setTitle('2-DS_SP_Goc');
            $spGocSheet->setCellValue('A1', 'Danh sách Tên Sản Phẩm Gốc hợp lệ');
            $spGocSheet->getStyle('A1')->applyFromArray($header_style);
            $spGocResult = $conn->query("SELECT name FROM products ORDER BY name ASC");
            $spGocDataRaw = $spGocResult->fetch_all(MYSQLI_ASSOC);
            $spGocData = array_map(function($row) { return [$row['name']]; }, $spGocDataRaw);
            $spGocSheet->fromArray($spGocData, NULL, 'A2');
            $lastRow = $spGocSheet->getHighestRow();
            if ($lastRow > 1) { $spGocSheet->getStyle('A1:A'.$lastRow)->applyFromArray($table_style); }
            $spGocSheet->getColumnDimension('A')->setAutoSize(true);

            $nhomSpSheet = $spreadsheet->createSheet();
            $nhomSpSheet->setTitle('3-DS_NhomSP');
            $nhomSpSheet->setCellValue('A1', 'Danh sách Nhóm Sản Phẩm hợp lệ');
            $nhomSpSheet->getStyle('A1')->applyFromArray($header_style);
            $nhomSpResult = $conn->query("SELECT name FROM product_groups ORDER BY name ASC");
            $nhomSpDataRaw = $nhomSpResult->fetch_all(MYSQLI_ASSOC);
            $nhomSpData = array_map(function($row) { return [$row['name']]; }, $nhomSpDataRaw);
            $nhomSpSheet->fromArray($nhomSpData, NULL, 'A2');
            $lastRow = $nhomSpSheet->getHighestRow();
            if ($lastRow > 1) { $nhomSpSheet->getStyle('A1:A'.$lastRow)->applyFromArray($table_style); }
            $nhomSpSheet->getColumnDimension('A')->setAutoSize(true);

            $loaiSheet = $spreadsheet->createSheet();
            $loaiSheet->setTitle('4-DS_LoaiPhanLoai');
            $loaiSheet->setCellValue('A1', 'Danh sách Loại Phân Loại hợp lệ');
            $loaiSheet->getStyle('A1')->applyFromArray($header_style);
            $loaiResult = $conn->query("SELECT TenLoai FROM loaisanpham ORDER BY TenLoai ASC");
            $loaiDataRaw = $loaiResult->fetch_all(MYSQLI_ASSOC);
            $loaiData = array_map(function($row) { return [$row['TenLoai']]; }, $loaiDataRaw);
            $loaiSheet->fromArray($loaiData, NULL, 'A2');
            $lastRow = $loaiSheet->getHighestRow();
            if ($lastRow > 1) { $loaiSheet->getStyle('A1:A'.$lastRow)->applyFromArray($table_style); }
            $loaiSheet->getColumnDimension('A')->setAutoSize(true);

            // --- Hoàn tất và xuất file ---
            $spreadsheet->setActiveSheetIndex(0); // Đặt sheet hướng dẫn làm sheet mặc định khi mở file

            $filename = "DanhSachSanPham_" . date('Y-m-d') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();
            break;

        // === CASE NHẬP DỮ LIỆU (ĐỒNG BỘ HÓA: THÊM, SỬA) ===
        case 'import_variants':
            if (!isset($_FILES['import_file'])) {
                throw new Exception("Không có file nào được tải lên.");
            }
            
            $file_path = $_FILES['import_file']['tmp_name'];
            $file_name = $_FILES['import_file']['name'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $dataRows = [];

            if ($file_extension == 'xlsx') {
                $spreadsheet = IOFactory::load($file_path);
                // Lấy sheet có tên 'DS Hien Dang Co' thay vì sheet active
                $worksheet = $spreadsheet->getSheetByName('DS Hien Dang Co');
                if (!$worksheet) {
                    throw new Exception("Không tìm thấy sheet 'DS Hien Dang Co' trong file Excel.");
                }
                $dataRows = $worksheet->toArray(null, true, true, true);
            } elseif ($file_extension == 'csv') {
                $handle = fopen($file_path, "r");
                if ($handle === FALSE) throw new Exception("Không thể mở file CSV.");
                while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
                    $dataRows[] = array_combine(range('A', chr(ord('A') + count($data) - 1)), $data);
                }
                fclose($handle);
            } else {
                throw new Exception("Định dạng file không được hỗ trợ. Vui lòng sử dụng .xlsx hoặc .csv.");
            }

            $headerRowRaw = array_shift($dataRows);
            $headerRow = array_filter($headerRowRaw);

            $attributes_map = [];
            $attributes_result = $conn->query("SELECT attribute_id, name FROM attributes");
            while($row = $attributes_result->fetch_assoc()) { $attributes_map[$row['name']] = $row['attribute_id']; }

            $conn->begin_transaction();
            try {
                // --- BƯỚC 1: Bỏ qua việc xóa sản phẩm ---
                // Khối code xóa sản phẩm không có trong file đã được loại bỏ.

                // --- BƯỚC 2: THÊM MỚI HOẶC CẬP NHẬT SẢN PHẨM ---
                $row_count = 1;
                $added_count = 0;
                $updated_count = 0;

                foreach ($dataRows as $row) {
                    $row_count++;
                    if (empty(array_filter($row, fn($value) => $value !== null && $value !== ''))) continue;

                    $variant_id_from_file = trim($row['A'] ?? null);
                    $sku = trim($row['C'] ?? '');
                    
                    if (empty($sku) && empty($variant_id_from_file)) continue;

                    $variant_name = trim($row['D'] ?? '');
                    $base_product_name = trim($row['E'] ?? '');
                    $group_name = trim($row['F'] ?? '');
                    $type_name = trim($row['G'] ?? '');
                    $unit_name = trim($row['H'] ?? '');
                    $price = floatval(preg_replace('/[^\d.]/', '', $row['I'] ?? '0'));
                    $stock_text = trim($row['J'] ?? '0');
                    $stock = intval(preg_replace('/[^\d]/', '', $stock_text));
                    $sku_suffix = trim($row['K'] ?? null);

                    // XỬ LÝ TÌM HOẶC TẠO MỚI ĐƠN VỊ TÍNH... (giữ nguyên logic này)
                    $unit_id = null;
                    if (!empty($unit_name)) {
                        $stmt_unit = $conn->prepare("SELECT unit_id FROM units WHERE name = ?");
                        $stmt_unit->bind_param("s", $unit_name);
                        $stmt_unit->execute();
                        $result_unit = $stmt_unit->get_result();
                        if ($result_unit->num_rows > 0) {
                            $unit_id = $result_unit->fetch_assoc()['unit_id'];
                        } else {
                            $stmt_insert_unit = $conn->prepare("INSERT INTO units (name) VALUES (?)");
                            $stmt_insert_unit->bind_param("s", $unit_name);
                            $stmt_insert_unit->execute();
                            $unit_id = $stmt_insert_unit->insert_id;
                            $stmt_insert_unit->close();
                        }
                        $stmt_unit->close();
                    }

                    // XỬ LÝ NHÓM, LOẠI, SẢN PHẨM GỐC... (giữ nguyên logic này)
                    $stmt_group = $conn->prepare("SELECT group_id FROM product_groups WHERE name = ?");
                    $stmt_group->bind_param("s", $group_name); $stmt_group->execute();
                    $result_group = $stmt_group->get_result();
                    if ($result_group->num_rows > 0) { $group_id = $result_group->fetch_assoc()['group_id']; } 
                    else {
                        $stmt_insert = $conn->prepare("INSERT INTO product_groups (name) VALUES (?)");
                        $stmt_insert->bind_param("s", $group_name); $stmt_insert->execute();
                        $group_id = $stmt_insert->insert_id; $stmt_insert->close();
                    }
                    $stmt_group->close();

                    $stmt_type = $conn->prepare("SELECT LoaiID FROM loaisanpham WHERE TenLoai = ?");
                    $stmt_type->bind_param("s", $type_name); $stmt_type->execute();
                    $result_type = $stmt_type->get_result();
                    if ($result_type->num_rows > 0) { $type_id = $result_type->fetch_assoc()['LoaiID']; } 
                    else {
                        $stmt_insert = $conn->prepare("INSERT INTO loaisanpham (TenLoai) VALUES (?)");
                        $stmt_insert->bind_param("s", $type_name); $stmt_insert->execute();
                        $type_id = $stmt_insert->insert_id; $stmt_insert->close();
                    }
                    $stmt_type->close();
                    
                    $stmt_product = $conn->prepare("SELECT product_id FROM products WHERE name = ?");
                    $stmt_product->bind_param("s", $base_product_name); $stmt_product->execute();
                    $result_product = $stmt_product->get_result();
                    if ($result_product->num_rows > 0) { 
                        $product_data = $result_product->fetch_assoc();
                        $product_id = $product_data['product_id'];
                        $stmt_update_product_unit = $conn->prepare("UPDATE products SET base_unit_id = ? WHERE product_id = ?");
                        $stmt_update_product_unit->bind_param("ii", $unit_id, $product_id);
                        $stmt_update_product_unit->execute();
                        $stmt_update_product_unit->close();
                    } 
                    else {
                        $base_sku = "AUTO-" . preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($base_product_name));
                        $stmt_insert = $conn->prepare("INSERT INTO products (name, base_sku, group_id, base_unit_id) VALUES (?, ?, ?, ?)");
                        $stmt_insert->bind_param("ssii", $base_product_name, $base_sku, $group_id, $unit_id);
                        $stmt_insert->execute();
                        $product_id = $stmt_insert->insert_id; $stmt_insert->close();
                    }
                    $stmt_product->close();

                    $variant_id = null;
                    
                    // [MODIFIED] LOGIC INSERT/UPDATE DỰA VÀO ID
                    if (!empty($variant_id_from_file) && is_numeric($variant_id_from_file)) {
                        $variant_id = (int)$variant_id_from_file;
                        
                        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM variants WHERE variant_id = ?");
                        $stmt_check->bind_param("i", $variant_id);
                        $stmt_check->execute();
                        $id_exists = $stmt_check->get_result()->fetch_row()[0] > 0;
                        $stmt_check->close();

                        if ($id_exists) {
                            $stmt_update = $conn->prepare("UPDATE variants SET product_id = ?, LoaiID = ?, variant_sku = ?, variant_name = ?, price = ?, sku_suffix = ? WHERE variant_id = ?");
                            $stmt_update->bind_param("isssdsi", $product_id, $type_id, $sku, $variant_name, $price, $sku_suffix, $variant_id);
                            $stmt_update->execute();
                            $stmt_update->close();
                            $updated_count++;
                        } else {
                            throw new Exception("ID '{$variant_id}' ở dòng {$row_count} không tồn tại. Không thể cập nhật.");
                        }
                    } else {
                        if (empty($sku)) continue;

                        $stmt_check_sku = $conn->prepare("SELECT variant_id FROM variants WHERE variant_sku = ?");
                        $stmt_check_sku->bind_param("s", $sku);
                        $stmt_check_sku->execute();
                        $result_check_sku = $stmt_check_sku->get_result();

                        if ($result_check_sku->num_rows > 0) {
                            throw new Exception("Mã SKU '{$sku}' ở dòng {$row_count} đã tồn tại. Nếu muốn cập nhật, vui lòng xuất file để lấy ID chính xác.");
                        }
                        $stmt_check_sku->close();
                        
                        $stmt_insert = $conn->prepare("INSERT INTO variants (product_id, LoaiID, variant_sku, variant_name, price, sku_suffix) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("isssds", $product_id, $type_id, $sku, $variant_name, $price, $sku_suffix);
                        $stmt_insert->execute();
                        $variant_id = $stmt_insert->insert_id;
                        $stmt_insert->close();
                        $added_count++;
                    }

                    if ($variant_id) {
                        $stmt_inv = $conn->prepare("INSERT INTO variant_inventory (variant_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)");
                        $stmt_inv->bind_param("ii", $variant_id, $stock);
                        $stmt_inv->execute();
                        $stmt_inv->close();

                        $delete_attr_stmt = $conn->prepare("DELETE FROM variant_attributes WHERE variant_id = ?");
                        $delete_attr_stmt->bind_param("i", $variant_id);
                        $delete_attr_stmt->execute();
                        $delete_attr_stmt->close();

                        foreach ($headerRow as $colIndex => $colName) {
                            if (isset($attributes_map[$colName])) {
                                $attribute_id = $attributes_map[$colName];
                                $option_value = trim($row[$colIndex]);
                                if (isset($option_value) && $option_value !== '') {
                                    $stmt_opt = $conn->prepare("SELECT option_id FROM attribute_options WHERE attribute_id = ? AND value = ?");
                                    $stmt_opt->bind_param("is", $attribute_id, $option_value);
                                    $stmt_opt->execute();
                                    $result_opt = $stmt_opt->get_result();
                                    if ($result_opt->num_rows > 0) {
                                        $option_id = $result_opt->fetch_assoc()['option_id'];
                                    } else {
                                        $stmt_ins_opt = $conn->prepare("INSERT INTO attribute_options (attribute_id, value) VALUES (?, ?)");
                                        $stmt_ins_opt->bind_param("is", $attribute_id, $option_value);
                                        $stmt_ins_opt->execute();
                                        $option_id = $stmt_ins_opt->insert_id;
                                        $stmt_ins_opt->close();
                                    }
                                    $stmt_opt->close();
                                    
                                    $stmt_link = $conn->prepare("INSERT INTO variant_attributes (variant_id, option_id) VALUES (?, ?)");
                                    $stmt_link->bind_param("ii", $variant_id, $option_id);
                                    $stmt_link->execute();
                                    $stmt_link->close();
                                }
                            }
                        }
                    }
                }
                $conn->commit();
                
                $message = "Đồng bộ hóa hoàn tất. ";
                if ($added_count > 0) $message .= "Đã thêm mới {$added_count} sản phẩm. ";
                if ($updated_count > 0) $message .= "Đã cập nhật {$updated_count} sản phẩm. ";
                if ($added_count == 0 && $updated_count == 0) {
                    $message = "Không có thay đổi nào được thực hiện.";
                }
                $response['message'] = $message;

            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception("Lỗi ở dòng " . ($row_count > 1 ? $row_count : 2) . " trong file: " . $e->getMessage());
            }
            break;

        // --- CÁC CASE API GỐC CỦA BẠN ---
        case 'get_products':
            $sql = "SELECT p.*, g.name as group_name FROM products p LEFT JOIN product_groups g ON p.group_id = g.group_id ORDER BY p.name ASC";
            $result = $conn->query($sql);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;
        case 'get_all_base_products':
            $result = $conn->query("SELECT p.product_id, p.name, p.base_sku, p.sku_prefix, p.name_prefix, p.base_unit_id, p.attribute_config, p.sku_name_formula, u.name as base_unit_name FROM products p LEFT JOIN units u ON p.base_unit_id = u.unit_id ORDER BY p.name");
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;
        case 'get_all_base_products_list':
            $sql = "SELECT p.product_id, p.name, p.base_sku, pg.name as group_name FROM products p LEFT JOIN product_groups pg ON p.group_id = pg.group_id ORDER BY p.name ASC";
            $result = $conn->query($sql);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;
        case 'get_product_details':
            $product_id = $request_body['product_id'];
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id); $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            break;
        case 'save_product':
            $data = $request_body['data']; $product_id = $data['product_id'] ?: null;
            $name = $data['name']; $base_sku = $data['base_sku']; $group_id = $data['group_id'];
            $sku_prefix = $data['sku_prefix']; $name_prefix = $data['name_prefix'];
            $base_unit_id = $data['base_unit_id'] ?: null;
            $attribute_config = $data['attribute_config'] ?? null;
            $sku_name_formula = $data['sku_name_formula'] ?? null;

            $conn->begin_transaction();

            try {
                if ($product_id) {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, base_sku = ?, group_id = ?, sku_prefix = ?, name_prefix = ?, base_unit_id = ?, attribute_config = ?, sku_name_formula = ? WHERE product_id = ?");
                    $stmt->bind_param("ssississi", $name, $base_sku, $group_id, $sku_prefix, $name_prefix, $base_unit_id, $attribute_config, $sku_name_formula, $product_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO products (name, base_sku, group_id, sku_prefix, name_prefix, base_unit_id, attribute_config, sku_name_formula) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssississ", $name, $base_sku, $group_id, $sku_prefix, $name_prefix, $base_unit_id, $attribute_config, $sku_name_formula);
                }
                $stmt->execute();
                if (!$product_id) {
                    $product_id = $conn->insert_id;
                }
                $stmt->close();

                $variants_of_product_stmt = $conn->prepare("SELECT variant_id FROM variants WHERE product_id = ?");
                $variants_of_product_stmt->bind_param("i", $product_id);
                $variants_of_product_stmt->execute();
                $product_variants = $variants_of_product_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $variants_of_product_stmt->close();

                $insert_update_inventory_stmt = $conn->prepare("
                    INSERT INTO variant_inventory (variant_id, quantity, minimum_stock_level)
                    VALUES (?, 0, 0)
                    ON DUPLICATE KEY UPDATE variant_id = VALUES(variant_id)
                ");

                foreach ($product_variants as $variant_row) {
                    $variant_id_for_inv = $variant_row['variant_id'];
                    $insert_update_inventory_stmt->bind_param("i", $variant_id_for_inv);
                    $insert_update_inventory_stmt->execute();
                }
                $insert_update_inventory_stmt->close();

                $conn->commit();
                $response['message'] = "Đã lưu sản phẩm gốc.";
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        case 'delete_product':
            $product_id = $request_body['product_id'];
            if (empty($product_id)) {
                throw new Exception("Không có ID sản phẩm gốc nào được cung cấp.");
            }
            $conn->begin_transaction();
            try {
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM variants WHERE product_id = ?");
                $check_stmt->bind_param("i", $product_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if ($result['count'] > 0) {
                    throw new Exception("Không thể xóa. Sản phẩm gốc này đang có {$result['count']} sản phẩm chi tiết liên kết.");
                }

                $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
                $delete_stmt->bind_param("i", $product_id);
                $delete_stmt->execute();
                
                if ($delete_stmt->affected_rows > 0) {
                    $response['message'] = "Đã xóa sản phẩm gốc thành công.";
                } else {
                    throw new Exception("Không tìm thấy sản phẩm gốc để xóa hoặc đã có lỗi xảy ra.");
                }
                $delete_stmt->close();
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        case 'get_all_variants_flat':
            $sql = "
                SELECT
                    v.variant_id, v.variant_sku, v.variant_name, v.price, v.LoaiID, v.sku_suffix,
                    p.product_id, p.name AS product_name, p.base_unit_id,
                    pg.group_id, pg.name AS group_name,
                    l.TenLoai AS loai_name,
                    u.name AS base_unit_name
                FROM variants v
                LEFT JOIN products p ON v.product_id = p.product_id
                LEFT JOIN product_groups pg ON p.group_id = pg.group_id
                LEFT JOIN loaisanpham l ON v.LoaiID = l.LoaiID
                LEFT JOIN units u ON p.base_unit_id = u.unit_id
                ORDER BY v.variant_id DESC
            ";
            $variants = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
            $attr_stmt = $conn->prepare("SELECT a.name AS attribute_name, ao.value AS attribute_value FROM variant_attributes va JOIN attribute_options ao ON va.option_id = ao.option_id JOIN attributes a ON ao.attribute_id = a.attribute_id WHERE va.variant_id = ?");
            $get_specific_inventory_stmt = $conn->prepare("SELECT vi.quantity, vi.minimum_stock_level FROM variant_inventory vi WHERE vi.variant_id = ?");

            foreach ($variants as &$variant) {
                $attr_stmt->bind_param("i", $variant['variant_id']);
                $attr_stmt->execute();
                $attributes = $attr_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($attributes as $attr) {
                    $variant[$attr['attribute_name']] = $attr['attribute_value'];
                }
                $total_stock = 0;
                $minimum_stock = 0;
                $unit_for_display = $variant['base_unit_name'] ?? 'Chưa xác định';
                $get_specific_inventory_stmt->bind_param("i", $variant['variant_id']);
                $get_specific_inventory_stmt->execute();
                $inv_result = $get_specific_inventory_stmt->get_result();
                if ($inv_result->num_rows > 0) {
                    $inventory_data = $inv_result->fetch_assoc();
                    $total_stock = intval($inventory_data['quantity'] ?? 0);
                    $minimum_stock = intval($inventory_data['minimum_stock_level'] ?? 0);
                }
                $get_specific_inventory_stmt->reset();
                $variant['quantity_in_base_unit'] = $total_stock;
                $variant['minimum_stock_level'] = $minimum_stock;
                $variant['inventory_display'] = "{$total_stock} {$unit_for_display}";
            }
            $response['data'] = $variants;
            $attr_stmt->close();
            $get_specific_inventory_stmt->close();
            break;
        case 'get_variant_details':
            $variant_id = $request_body['variant_id'];
            $stmt = $conn->prepare("SELECT * FROM variants WHERE variant_id = ?");
            $stmt->bind_param("i", $variant_id); $stmt->execute();
            $variant_details = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $attr_stmt = $conn->prepare("SELECT option_id FROM variant_attributes WHERE variant_id = ?");
            $attr_stmt->bind_param("i", $variant_id); $attr_stmt->execute();
            $variant_details['selected_options'] = array_column($attr_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'option_id');
            $attr_stmt->close();
            $response['data'] = $variant_details;
            break;
        case 'save_variant':
            $data = $request_body['data']; 
            $variant_id = $data['variant_id'] ?: null; 
            $product_id = $data['product_id']; 
            $loai_id = $data['LoaiID'] ?: null;
            $sku = $data['variant_sku']; 
            $name = $data['variant_name']; 
            $price = $data['price']; 
            $option_ids = $data['option_ids'] ?? [];
            $sku_suffix = $data['sku_suffix'] ?? null;

            $conn->begin_transaction();
            try {
                if ($variant_id) {
                    $stmt = $conn->prepare("UPDATE variants SET product_id = ?, LoaiID = ?, variant_sku = ?, variant_name = ?, price = ?, sku_suffix = ? WHERE variant_id = ?");
                    $stmt->bind_param("iissssi", $product_id, $loai_id, $sku, $name, $price, $sku_suffix, $variant_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO variants (product_id, LoaiID, variant_sku, variant_name, price, sku_suffix) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissss", $product_id, $loai_id, $sku, $name, $price, $sku_suffix);
                }
                $stmt->execute();
                if (!$variant_id) $variant_id = $conn->insert_id;
                $stmt->close();

                $insert_update_inventory_stmt = $conn->prepare("
                    INSERT INTO variant_inventory (variant_id, quantity, minimum_stock_level)
                    VALUES (?, 0, 0)
                    ON DUPLICATE KEY UPDATE variant_id = VALUES(variant_id)
                ");
                $insert_update_inventory_stmt->bind_param("i", $variant_id);
                $insert_update_inventory_stmt->execute();
                $insert_update_inventory_stmt->close();

                $delete_stmt = $conn->prepare("DELETE FROM variant_attributes WHERE variant_id = ?");
                $delete_stmt->bind_param("i", $variant_id); $delete_stmt->execute(); $delete_stmt->close();
                if (!empty($option_ids)) {
                    $insert_attr_stmt = $conn->prepare("INSERT INTO variant_attributes (variant_id, option_id) VALUES (?, ?)");
                    foreach ($option_ids as $option_id) { if($option_id) { $insert_attr_stmt->bind_param("ii", $variant_id, $option_id); $insert_attr_stmt->execute(); } }
                    $insert_attr_stmt->close();
                }
                $conn->commit(); $response['message'] = 'Đã lưu biến thể thành công!';
            } catch (Exception $e) { $conn->rollback(); throw $e; }
            break;
        case 'delete_multiple_variants':
            $ids = $request_body['ids'] ?? [];
            if (empty($ids) || !is_array($ids)) throw new Exception("Không có ID sản phẩm nào được cung cấp.");
            $placeholders = implode(',', array_fill(0, count($ids), '?')); $types = str_repeat('i', count($ids));
            $stmt = $conn->prepare("DELETE FROM variants WHERE variant_id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids); $stmt->execute();
            $response['message'] = "Đã xóa thành công {$stmt->affected_rows} sản phẩm."; $stmt->close();
            break;
        case 'get_inventory_for_variant':
            $variant_id = $request_body['variant_id'];
            $product_base_unit_res = $conn->query("SELECT p.base_unit_id, u.name as unit_name FROM variants v JOIN products p ON v.product_id = p.product_id LEFT JOIN units u ON p.base_unit_id = u.unit_id WHERE v.variant_id = {$variant_id}");
            $product_base_unit_data = $product_base_unit_res->fetch_assoc();
            $base_unit_id_for_variant = $product_base_unit_data['base_unit_id'] ?? null;
            $unit_name_for_variant = $product_base_unit_data['unit_name'] ?? 'Chưa xác định';

            if (!$base_unit_id_for_variant && $product_base_unit_data['base_unit_id'] !== null) {
                $response['success'] = false;
                $response['message'] = "Không tìm thấy đơn vị cơ sở cho sản phẩm này. Vui lòng thiết lập đơn vị cơ sở trong phần chỉnh sửa sản phẩm gốc.";
                break;
            }
            
            $sql = "SELECT vi.quantity, vi.minimum_stock_level FROM variant_inventory vi WHERE vi.variant_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $variant_id);
            $stmt->execute();
            $inventory_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($inventory_data) {
                $response['data'] = array_merge($inventory_data, [
                    'unit_name' => $unit_name_for_variant,
                    'unit_id' => $base_unit_id_for_variant
                ]);
            } else {
                $response['data'] = [
                    'quantity' => 0,
                    'minimum_stock_level' => 0,
                    'unit_name' => $unit_name_for_variant,
                    'unit_id' => $base_unit_id_for_variant
                ];
            }
            break;
        case 'update_inventory':
            $variant_id = $request_body['variant_id'];
            $quantity = $request_body['quantity'];
            $minimum_stock_level = $request_body['minimum_stock_level'] ?? 0;
            
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    INSERT INTO variant_inventory (variant_id, quantity, minimum_stock_level)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), minimum_stock_level = VALUES(minimum_stock_level)
                ");
                $stmt->bind_param("iii", $variant_id, $quantity, $minimum_stock_level);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $response['message'] = "Cập nhật tồn kho thành công.";
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        case 'get_all_loai': $response['data'] = $conn->query("SELECT LoaiID, TenLoai FROM loaisanpham ORDER BY TenLoai")->fetch_all(MYSQLI_ASSOC); break;
        case 'get_product_groups': $response['data'] = $conn->query("SELECT group_id, name FROM product_groups ORDER BY name")->fetch_all(MYSQLI_ASSOC); break;
        case 'get_all_units': $response['data'] = $conn->query("SELECT * FROM units ORDER BY name")->fetch_all(MYSQLI_ASSOC); break;
        case 'get_all_attributes':
            $stmt = $conn->query("SELECT attribute_id, name FROM attributes ORDER BY order_index ASC, name ASC");
            $attributes = $stmt->fetch_all(MYSQLI_ASSOC);
            $option_stmt = $conn->prepare("SELECT option_id, value FROM attribute_options WHERE attribute_id = ? ORDER BY value");
            foreach ($attributes as &$attribute) {
                $option_stmt->bind_param("i", $attribute['attribute_id']);
                $option_stmt->execute();
                $attribute['options'] = $option_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            $response['data'] = $attributes; $option_stmt->close();
            break;
        case 'create_attribute_option':
            $attribute_id = $request_body['attribute_id']; $value = $request_body['value'];
            if (empty($attribute_id) || !isset($value)) throw new Exception("Dữ liệu không hợp lệ.");
            $stmt = $conn->prepare("INSERT INTO attribute_options (attribute_id, value) VALUES (?, ?)");
            $stmt->bind_param("is", $attribute_id, $value); $stmt->execute();
            $response['data'] = ['option_id' => $conn->insert_id, 'value' => $value];
            $stmt->close();
            break;
        case 'get_attributes_for_management':
            $response['data'] = $conn->query("SELECT attribute_id, name FROM attributes ORDER BY order_index ASC, name ASC")->fetch_all(MYSQLI_ASSOC);
            break;
        case 'get_options_for_attribute':
            $attribute_id = $request_body['attribute_id'];
            $stmt = $conn->prepare("SELECT option_id, value FROM attribute_options WHERE attribute_id = ? ORDER BY value");
            $stmt->bind_param("i", $attribute_id); $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
            break;
        case 'update_attribute_option':
            $option_id = $request_body['option_id']; $value = $request_body['value'];
            $stmt = $conn->prepare("UPDATE attribute_options SET value = ? WHERE option_id = ?");
            $stmt->bind_param("si", $value, $option_id); $stmt->execute();
            $response['message'] = "Đã cập nhật."; $stmt->close();
            break;
        case 'delete_attribute_option':
            $option_id = $request_body['option_id'];
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM variant_attributes WHERE option_id = ?");
            $check_stmt->bind_param("i", $option_id); $check_stmt->execute(); $result = $check_stmt->get_result()->fetch_assoc(); $check_stmt->close();
            if ($result['count'] > 0) throw new Exception("Không thể xóa. Tùy chọn này đang được {$result['count']} biến thể sử dụng.");
            $stmt = $conn->prepare("DELETE FROM attribute_options WHERE option_id = ?");
            $stmt->bind_param("i", $option_id); $stmt->execute();
            $response['message'] = "Đã xóa."; $stmt->close();
            break;
        case 'create_attribute':
            $name = $request_body['name'];
            if (empty($name)) {
                throw new Exception("Tên thuộc tính không được để trống.");
            }
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM attributes WHERE name = ?");
            $stmt_check->bind_param("s", $name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result()->fetch_row();
            if ($result_check[0] > 0) {
                throw new Exception("Thuộc tính '{$name}' đã tồn tại.");
            }
            $stmt_check->close();

            $stmt = $conn->prepare("INSERT INTO attributes (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $response['message'] = "Đã thêm thuộc tính mới.";
            $stmt->close();
            break;
        case 'delete_attribute':
            $attribute_id = $request_body['attribute_id'];
            if (empty($attribute_id)) {
                throw new Exception("Không có ID thuộc tính nào được cung cấp.");
            }
            $conn->begin_transaction();
            try {
                // Kiểm tra xem thuộc tính có đang được sử dụng trong bất kỳ sản phẩm gốc nào không
                $check_product_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE attribute_config LIKE ?");
                $like_pattern = "%\"{$attribute_id}\"%";
                $check_product_stmt->bind_param("s", $like_pattern);
                $check_product_stmt->execute();
                $result_product = $check_product_stmt->get_result()->fetch_assoc();
                $check_product_stmt->close();

                if ($result_product['count'] > 0) {
                    throw new Exception("Không thể xóa. Thuộc tính này đang được sử dụng trong {$result_product['count']} sản phẩm gốc.");
                }

                // Kiểm tra xem thuộc tính có đang được sử dụng trong bất kỳ tùy chọn nào không
                $check_option_stmt = $conn->prepare("SELECT COUNT(*) as count FROM attribute_options WHERE attribute_id = ?");
                $check_option_stmt->bind_param("i", $attribute_id);
                $check_option_stmt->execute();
                $result_option = $check_option_stmt->get_result()->fetch_assoc();
                $check_option_stmt->close();

                if ($result_option['count'] > 0) {
                    throw new Exception("Không thể xóa. Thuộc tính này có chứa {$result_option['count']} tùy chọn. Vui lòng xóa tất cả tùy chọn trước.");
                }

                $delete_stmt = $conn->prepare("DELETE FROM attributes WHERE attribute_id = ?");
                $delete_stmt->bind_param("i", $attribute_id);
                $delete_stmt->execute();
                
                if ($delete_stmt->affected_rows > 0) {
                    $response['message'] = "Đã xóa thuộc tính thành công.";
                } else {
                    throw new Exception("Không tìm thấy thuộc tính để xóa hoặc đã có lỗi xảy ra.");
                }
                $delete_stmt->close();
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
        case 'save_data_item':
            $item_id = $request_body['id']; $value = $request_body['value']; $type = $request_body['type'];
            if ($type === 'group') { $table = 'product_groups'; $id_col = 'group_id'; $name_col = 'name'; }
            elseif ($type === 'type') { $table = 'loaisanpham'; $id_col = 'LoaiID'; $name_col = 'TenLoai'; }
            else { $table = 'units'; $id_col = 'unit_id'; $name_col = 'name'; }
            $stmt = $conn->prepare("UPDATE {$table} SET {$name_col} = ? WHERE {$id_col} = ?");
            $stmt->bind_param("si", $item_id); $stmt->execute();
            $response['message'] = "Đã cập nhật."; $stmt->close();
            break;
        case 'create_data_item':
            $value = $request_body['value']; $type = $request_body['type'];
            if ($type === 'group') { $table = 'product_groups'; $name_col = 'name'; }
            elseif ($type === 'type') { $table = 'loaisanpham'; $name_col = 'TenLoai'; }
            else { $table = 'units'; $name_col = 'name'; }
            $stmt = $conn->prepare("INSERT INTO {$table} ({$name_col}) VALUES (?)");
            $stmt->bind_param("s", $value); $stmt->execute();
            $response['message'] = "Đã tạo mới."; $stmt->close();
            break;
        case 'delete_data_item':
            $item_id = $request_body['id']; $type = $request_body['type'];
            if ($type === 'group') { $table_to_check = 'products'; $id_col_to_check = 'group_id'; }
            elseif ($type === 'type') { $table_to_check = 'variants'; $id_col_to_check = 'LoaiID'; }
            else {
                $table_to_check = 'products'; $id_col_to_check = 'base_unit_id';
            }
            
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$table_to_check} WHERE {$id_col_to_check} = ?");
            $check_stmt->bind_param("i", $item_id); $check_stmt->execute();
            $result = $check_stmt->get_result()->fetch_assoc(); $check_stmt->close();
            if ($result['count'] > 0) throw new Exception("Không thể xóa. Mục này đang được sử dụng.");
            
            if ($type === 'group') { $table_to_delete = 'product_groups'; $id_col_to_delete = 'group_id'; }
            elseif ($type === 'type') { $table_to_delete = 'loaisanpham'; $id_col_to_delete = 'LoaiID'; }
            else { $table_to_delete = 'units'; $id_col_to_delete = 'unit_id'; }

            $stmt = $conn->prepare("DELETE FROM {$table_to_delete} WHERE {$id_col_to_delete} = ?");
            $stmt->bind_param("i", $item_id); $stmt->execute();
            $response['message'] = "Đã xóa."; $stmt->close();
            break;
            
        default:
            throw new Exception("Hành động không hợp lệ.");
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

if ($action != 'export_product_template') {
    header('Content-Type: application/json');
    echo json_encode($response);
}
$conn->close();
?>

