<?php
/**
 * api/export_pdf_TQ.php
 * Endpoint để tạo và xuất file PDF song ngữ (Việt-Trung) cho một báo giá.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/number_converter.php'; // Helper để đọc số thành chữ

try {
    // 1. Lấy ID báo giá từ URL
    $baoGiaID = (int)($_GET['id'] ?? 0);
    if ($baoGiaID <= 0) {
        http_response_code(400);
        die("Lỗi: ID báo giá không hợp lệ.");
    }

    // 2. Lấy thông tin báo giá và chi tiết sản phẩm
    $stmt_quote = $conn->prepare("SELECT * FROM baogia WHERE BaoGiaID = ?");
    $stmt_quote->bind_param("i", $baoGiaID);
    $stmt_quote->execute();
    $quote_info = $stmt_quote->get_result()->fetch_assoc();
    $stmt_quote->close();

    if (!$quote_info) {
        http_response_code(404);
        die("Lỗi: Không tìm thấy báo giá có ID là {$baoGiaID}.");
    }

    // Định dạng lại ngày báo giá
    $quote_info['NgayBaoGiaFormatted'] = date('d/m/Y', strtotime($quote_info['NgayBaoGia']));

    $stmt_items = $conn->prepare("SELECT * FROM chitietbaogia WHERE BaoGiaID = ? ORDER BY ThuTuHienThi ASC");
    $stmt_items->bind_param("i", $baoGiaID);
    $stmt_items->execute();
    $quote_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    // 3. Lấy tất cả các nhãn (labels) cho việc hiển thị song ngữ
    $stmt_labels = $conn->prepare("SELECT label_key, label_vi, label_zh, label_en FROM quotation_labels");
    $stmt_labels->execute();
    $result_labels = $stmt_labels->get_result();
    $labels = [];
    while ($row = $result_labels->fetch_assoc()) {
        $labels[$row['label_key']] = ['vi' => $row['label_vi'], 'zh' => $row['label_zh'], 'en' => $row['label_en']];
    }
    $stmt_labels->close();

    // 4. Lấy thông tin ngôn ngữ (đơn vị tiền tệ, hậu tố)
    $stmt_lang = $conn->prepare("SELECT lang_code, currency_name_native, currency_suffix FROM languages");
    $stmt_lang->execute();
    $result_lang = $stmt_lang->get_result();
    $languages_info = [];
    while ($row = $result_lang->fetch_assoc()) {
        $languages_info[$row['lang_code']] = $row;
    }
    $stmt_lang->close();
    $conn->close();

    // 5. Xử lý logic đọc số thành chữ cho cả tiếng Việt và tiếng Trung
    $amount = $quote_info['TongTienSauThue'] ?? 0;
    $totalInWords_vi = trim(convertNumberToWordsByLang($amount, 'vi') . ' ' . ($languages_info['vi']['currency_name_native'] ?? '') . ' ' . ($languages_info['vi']['currency_suffix'] ?? ''));
    $totalInWords_zh = trim(convertNumberToWordsByLang($amount, 'zh') . ' ' . ($languages_info['zh']['currency_name_native'] ?? '') . ' ' . ($languages_info['zh']['currency_suffix'] ?? ''));

    // 6. Chuẩn bị dữ liệu và render HTML từ file template
    ob_start();
    $data = [
        'info' => $quote_info,
        'items' => $quote_items,
        'labels' => $labels,
        'totalInWords' => [
            'vi' => $totalInWords_vi,
            'zh' => $totalInWords_zh
        ]
    ];
    include __DIR__ . '/../templates/quote_TQ_pdf_template.php';
    $html = ob_get_clean();

    // 7. Khởi tạo mPDF và cấu hình font
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => __DIR__ . '/../tmp',
        'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [__DIR__ . '/../ttfonts']),
        'fontdata' => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
            'notosanssc' => ['R' => 'NotoSansSC-Regular.ttf', 'useOTL' => 0xFF]
        ],
        'default_font' => 'notosanssc',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);

    // Đọc file CSS
    $css = file_get_contents(__DIR__ . '/../css/quote_pdf_styles_TQ.css');

    // ===== THAY ĐỔI: THÊM SỐ TRANG VÀO FOOTER =====
    $mpdf->SetFooter('{PAGENO}/{nb}');
    // ===== KẾT THÚC THAY ĐỔI =====

    // Ghi nội dung vào PDF
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    // 8. Xuất file PDF
    $pdfFileName = "BaoGia-TQ-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $quote_info['SoBaoGia']) . ".pdf";
    $mpdf->Output($pdfFileName, 'I');

} catch (Exception $e) {
    http_response_code(500);
    die("Lỗi server khi tạo PDF: " . $e->getMessage());
}
?>
