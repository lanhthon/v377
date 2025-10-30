<?php
/**
 * api/export_pdf.php
 * Endpoint để tạo và xuất file PDF cho một báo giá sử dụng thư viện mPDF.
 * Phiên bản này đọc CSS từ file bên ngoài và hỗ trợ song ngữ bằng cách lấy nhãn từ CSDL.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Lấy ID báo giá từ URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        die("Lỗi: ID báo giá không hợp lệ.");
    }
    $baoGiaID = (int)$_GET['id'];

    // Lấy nhãn từ CSDL
    $stmt_labels = $conn->prepare("SELECT label_key, label_vi, label_zh, label_en FROM quotation_labels");
    $stmt_labels->execute();
    $result_labels = $stmt_labels->get_result();
    $labels_raw = $result_labels->fetch_all(MYSQLI_ASSOC);
    $stmt_labels->close();

    $labels = [];
    foreach ($labels_raw as $row) {
        $labels[$row['label_key']] = [
            'vi' => $row['label_vi'],
            'zh' => $row['label_zh'],
            'en' => $row['label_en'],
        ];
    }

    // 2. Lấy thông tin chính của báo giá
    $stmt_quote = $conn->prepare("SELECT * FROM baogia WHERE BaoGiaID = ?");
    $stmt_quote->bind_param("i", $baoGiaID);
    $stmt_quote->execute();
    $result_quote = $stmt_quote->get_result();
    $quote_info = $result_quote->fetch_assoc();
    $stmt_quote->close();

    if (!$quote_info) {
        http_response_code(404);
        die("Lỗi: Không tìm thấy báo giá có ID là {$baoGiaID}.");
    }

    $quote_info['NgayBaoGiaFormatted'] = date('d/m/Y', strtotime($quote_info['NgayBaoGia']));

    // 3. Lấy chi tiết các sản phẩm trong báo giá
    $stmt_items = $conn->prepare("SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY ThuTuHienThi ASC");
    $stmt_items->bind_param("i", $baoGiaID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $quote_items = $result_items->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    $conn->close();

    // 4. Lấy nội dung HTML từ file template
    ob_start();
    $data = [
        'info' => $quote_info,
        'items' => $quote_items,
        'labels' => $labels,
    ];
    include __DIR__ . '/../templates/quote_pdf_template.php';
    $html = ob_get_clean();

    // 5. Khởi tạo mPDF
    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];
    $fontDirs = (new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp',
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/../ttfonts',
        ]),
        'fontdata' => $fontData + [
            'notosanssc' => [
                'R' => 'NotoSansSC-Regular.ttf',
                'useOTL' => 0xFF,
            ]
        ],
        'default_font' => 'notosanssc',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);

    // 6. Đọc nội dung CSS từ file bên ngoài
    $css = file_get_contents(__DIR__ . '/../css/quote_pdf_styles.css');

    // Thiết lập footer để hiển thị số trang trên TẤT CẢ các trang
    $mpdf->SetFooter('{PAGENO}/{nb}');

    // 7. Ghi CSS và HTML vào PDF
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    
    // 8. Xuất file PDF
    $pdfFileName = "BaoGia-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $quote_info['SoBaoGia']) . ".pdf";
    $mpdf->Output($pdfFileName, 'I');

} catch (Exception $e) {
    http_response_code(500);
    die("Lỗi server khi tạo PDF: " . $e->getMessage() . " tại file " . $e->getFile() . " dòng " . $e->getLine());
}
?>
