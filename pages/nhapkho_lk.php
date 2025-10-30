<?php
// pages/nhapkho_lk.php
?>
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-slate-800 font-bold">Nhập Kho từ Lệnh Sản Xuất (LK)</h1>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-sm border border-slate-200">
        <header class="px-5 py-4">
            <h2 class="font-semibold text-slate-800">Danh sách Lệnh Sản Xuất chờ nhập kho</h2>
        </header>
        <div class="p-3">
            <div class="overflow-x-auto">
                <table id="nhapkho-lk-list-table" class="table-auto w-full">
                    <thead class="text-xs font-semibold uppercase text-slate-500 bg-slate-50">
                        <tr>
                            <th class="p-2 whitespace-nowrap"><div class="font-semibold text-left">Số Lệnh SX</div></th>
                            <th class="p-2 whitespace-nowrap"><div class="font-semibold text-left">Ngày tạo</div></th>
                            <th class="p-2 whitespace-nowrap"><div class="font-semibold text-left">Trạng thái SX</div></th>
                            <th class="p-2 whitespace-nowrap"><div class="font-semibold text-left">Trạng thái Nhập kho</div></th>
                            <th class="p-2 whitespace-nowrap"><div class="font-semibold text-center">Hành động</div></th>
                        </tr>
                    </thead>
                    <tbody id="nhapkho-lk-list-body" class="text-sm divide-y divide-slate-100">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
