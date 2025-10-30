<?php
/**
 * api/export_chuanbihang_pdf.php
 * Version: 5.5 (Hiển thị đúng SoCayPhaiCat đã lưu)
 * - [FIXED] Gỡ bỏ logic tính toán lại SoCayPhaiCat.
 * - File sẽ lấy trực tiếp giá trị SoCayPhaiCat đã được lưu trong CSDL để hiển thị,
 * phản ánh đúng quy trình người dùng có thể chỉnh sửa CanSanXuatCay.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_config.php';

// === CÁC HÀM HỖ TRỢ ===
function parsePurSku($maHang) { if (preg_match('/^PUR-(S|C)\s*(?:\d+\s*\/\s*)?(\d+x\d+|\d+)(?:x\d+)?(?:-([A-Z0-9]+))?/', $maHang, $matches)) { return ['type' => $matches[1], 'dimensions' => $matches[2], 'suffix' => $matches[3] ?? '']; } return null; }
function format_number_pdf($num) { return $num ? number_format(floatval($num), 0, ',', '.') : '0'; }

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { http_response_code(400); die("Lỗi: ID phiếu không hợp lệ."); }
    $cbhId = (int)$_GET['id'];
    $pdo = get_db_connection();

    $stmt_info = $pdo->prepare("SELECT cbh.*, dh.SoYCSX, dh.NgayGiaoDuKien, dh.TenDuAn, COALESCE(cbh.DiaDiemGiaoHang, dh.DiaChiGiaoHang) AS DiaDiemGiaoHang, COALESCE(cbh.NguoiNhanHang, dh.NguoiNhan) AS NguoiNhanHang FROM chuanbihang cbh JOIN donhang dh ON cbh.YCSX_ID = dh.YCSX_ID WHERE cbh.CBH_ID = :cbhId");
    $stmt_info->execute([':cbhId' => $cbhId]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    if (!$info) { throw new Exception('Không tìm thấy phiếu.'); }

    // [FIX] Thêm SoCayPhaiCat vào câu truy vấn
    $stmt_items = $pdo->prepare("SELECT ctcbh.*, v.variant_name, p.base_sku, vi.quantity AS TonKhoVatLy FROM chitietchuanbihang ctcbh LEFT JOIN variants v ON ctcbh.SanPhamID = v.variant_id LEFT JOIN products p ON v.product_id = p.product_id LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id WHERE ctcbh.CBH_ID = :cbhId ORDER BY ctcbh.ThuTuHienThi");
    $stmt_items->execute([':cbhId' => $cbhId]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_alloc_by_id = $pdo->prepare("SELECT COALESCE(SUM(SoLuongPhanBo), 0) FROM donhang_phanbo_tonkho WHERE SanPhamID = ? AND CBH_ID != ?");
    $stmt_get_btp_variant = $pdo->prepare("SELECT v.variant_id, vi.quantity FROM variants v LEFT JOIN variant_inventory vi ON v.variant_id = vi.variant_id WHERE v.variant_sku = ? LIMIT 1");

    $hangSanXuat = []; $hangChuanBi_ULA = []; $hangDeoTreo = [];
    foreach ($items as &$item) {
        $stmt_alloc_by_id->execute([$item['SanPhamID'], $cbhId]);
        $item['DaGan'] = (int)$stmt_alloc_by_id->fetchColumn();
        $item['TonKho'] = (int)($item['TonKhoVatLy'] ?? 0);
        $item['SoLuongCanSX'] = (int)$item['SoLuong'] - min((int)$item['SoLuong'], max(0, $item['TonKho'] - $item['DaGan']));
        $maHangUpper = strtoupper($item['MaHang'] ?? '');
        if (str_starts_with($maHangUpper, 'PUR')) {
            $hangSanXuat[] = $item;
        } elseif (str_starts_with($maHangUpper, 'ULA')) {
            $hangChuanBi_ULA[] = $item;
        } elseif (str_starts_with($maHangUpper, 'DT')) {
            $hangDeoTreo[] = $item;
        }
    }
    unset($item);
    
    // [FIX] Gỡ bỏ logic tính lại SoCayPhaiCat, chỉ tính toán các giá trị hiển thị
    foreach ($hangSanXuat as &$item) {
        $tonKhoCV = (int)($item['TonKhoCV'] ?? 0); $daGanCV = (int)($item['DaGanCV'] ?? 0); $khaDungCV = max(0, $tonKhoCV - $daGanCV);
        $item['tonKhoDisplayCV'] = sprintf('%s/%s/%s', format_number_pdf($tonKhoCV), format_number_pdf($daGanCV), format_number_pdf($khaDungCV));
        
        $tonKhoCT = (int)($item['TonKhoCT'] ?? 0); $daGanCT = (int)($item['DaGanCT'] ?? 0); $khaDungCT = max(0, $tonKhoCT - $daGanCT);
        $item['tonKhoDisplayCT'] = sprintf('%s/%s/%s', format_number_pdf($tonKhoCT), format_number_pdf($daGanCT), format_number_pdf($khaDungCT));

        $item['canSanXuatDisplayCV'] = (int)($item['CanSanXuatCV'] ?? 0);
        $item['canSanXuatDisplayCT'] = (int)($item['CanSanXuatCT'] ?? 0);
    }
    unset($item);
    
    $stmt_ecu = $pdo->prepare("SELECT *, TonKhoSnapshot as TonKho, DaGanSnapshot as DaGan FROM chitiet_ecu_cbh WHERE CBH_ID = :cbhId"); $stmt_ecu->execute([':cbhId' => $cbhId]); $vatTuKem_ECU = $stmt_ecu->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    $data_for_template = [ 'info' => $info, 'hangSanXuat' => $hangSanXuat, 'hangChuanBi_ULA' => $hangChuanBi_ULA, 'hangDeoTreo' => $hangDeoTreo, 'vatTuKem_ECU' => $vatTuKem_ECU ];
    include __DIR__ . '/../templates/chuanbihang_pdf_template.php'; 
    $html = ob_get_clean();

    $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => __DIR__ . '/../tmp', 'default_font' => 'dejavusans']);
    $css = file_get_contents(__DIR__ . '/../css/pdf_chuanbihang_style.css');
    $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->SetFooter('{PAGENO}/{nb}');
    $pdfFileName = "PhieuChuanBiHang-" . ($info['SoCBH'] ?? $cbhId) . ".pdf";
    $mpdf->Output($pdfFileName, 'I');

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Lỗi khi tạo PDF: " . $e->getMessage() . " tại " . $e->getFile() . " dòng " . $e->getLine());
    echo "Đã xảy ra lỗi trong quá trình tạo file PDF. Vui lòng thử lại sau.";
}