<?php
// File: pages/dinh_muc_cat_goi.php
// Công cụ tính toán định mức vật tư để cắt gối PU
?>
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Công cụ tính Định Mức Cắt Gối PU</h1>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1 border-r pr-6">
                <h3 class="font-semibold text-lg mb-4">Thông số đầu vào</h3>
                <div class="mb-4">
                    <label for="input-id" class="block text-sm font-medium text-gray-700">ID ống (mm)</label>
                    <input type="number" id="input-id"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label for="input-do-day" class="block text-sm font-medium text-gray-700">Độ dày gối (mm)</label>
                    <input type="number" id="input-do-day"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <button id="calculate-btn" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                    <i class="fas fa-calculator mr-2"></i>Tính toán
                </button>
            </div>

            <div class="md:col-span-2">
                <h3 class="font-semibold text-lg mb-4">Kết quả tính toán</h3>
                <div id="results-area" class="hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-500">Kích thước cắt
                                    'a' (mm)</td>
                                <td id="result-a" class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 font-bold">
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-500">Kích thước cắt
                                    'b' (mm)</td>
                                <td id="result-b" class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 font-bold">
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-500">Số lượng gối /
                                    1 tấm PU (cái)</td>
                                <td id="result-so-luong"
                                    class="px-3 py-2 whitespace-nowrap text-sm text-green-600 font-bold text-lg"></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-500">Định mức vật
                                    tư (m²/cái)</td>
                                <td id="result-dinh-muc"
                                    class="px-3 py-2 whitespace-nowrap text-sm text-red-600 font-bold text-lg"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 text-xs">
                        <p><strong>Lưu ý:</strong> Tính toán dựa trên kích thước tấm PU tiêu chuẩn:</p>
                        <ul class="list-disc list-inside ml-4">
                            <li>Diện tích (S tấm): <strong>0.72 m²</strong></li>
                            <li>Chiều dài: <strong>0.6 m (600 mm)</strong></li>
                            <li>Chiều rộng: <strong>1.2 m (1200 mm)</strong></li>
                        </ul>
                    </div>
                </div>
                <div id="instruction-area" class="text-center text-gray-500 pt-10">
                    <i class="fas fa-keyboard fa-2x mb-2"></i>
                    <p>Vui lòng nhập thông số và nhấn "Tính toán"</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calculateBtn = document.getElementById('calculate-btn');

    calculateBtn.addEventListener('click', function() {
        // Lấy giá trị đầu vào
        const id = parseFloat(document.getElementById('input-id').value);
        const doDay = parseFloat(document.getElementById('input-do-day').value);

        if (isNaN(id) || isNaN(doDay) || id <= 0 || doDay <= 0) {
            alert('Vui lòng nhập giá trị ID và Độ dày hợp lệ (lớn hơn 0).');
            return;
        }

        // --- Bắt đầu logic tính toán dựa trên ảnh ---

        // 1. Hằng số vật tư (tấm PU tiêu chuẩn)
        const sTam = 0.72; // m²
        const daiCayPU_mm = 600; // mm
        const rongCayPU_mm = 1200; // mm

        // 2. Tính kích thước cắt a, b
        const a = id + 5;
        const b = a + (2 * doDay);

        // 3. Tính số lượng gối trên mỗi tấm
        // Sử dụng Math.floor để làm tròn xuống, vì không thể cắt một phần của gối
        const soLuongTheoChieuDai = Math.floor(daiCayPU_mm / a);
        const soLuongTheoChieuRong = Math.floor(rongCayPU_mm / b);
        const soLuongGoiTrenTam = soLuongTheoChieuDai * soLuongTheoChieuRong;

        // 4. Tính định mức (m²/cái)
        let dinhMuc = 0;
        if (soLuongGoiTrenTam > 0) {
            dinhMuc = sTam / soLuongGoiTrenTam;
        }

        // --- Kết thúc logic tính toán ---

        // Hiển thị kết quả
        document.getElementById('results-area').classList.remove('hidden');
        document.getElementById('instruction-area').classList.add('hidden');

        document.getElementById('result-a').textContent = a.toFixed(2);
        document.getElementById('result-b').textContent = b.toFixed(2);
        document.getElementById('result-so-luong').textContent = soLuongGoiTrenTam;
        document.getElementById('result-dinh-muc').textContent = dinhMuc.toFixed(5);
    });
});
</script>