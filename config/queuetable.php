<!-- TAB 1: ตารางคิวจองและสรุปรายรับประจำวัน -->
<section id="section-summary" class="space-y-6 hidden">
    
    <!-- ตารางการจองคิววันที่ :...                         export to excel -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- ฝั่งซ้าย: ตารางการจองคิววันที่ + ฟอร์มเลือกวันที่ + filter -->
        <div class="flex items-center gap-3">
            <label class="text-ml font-bold text-[#382214] whitespace-nowrap">ตารางการจองคิววันที่ :</label>
            <form id="summary_date_form" method="GET" action="dashboard.php" class="flex items-center gap-1">
                <input type="hidden" name="tab" value="summary">
                <input type="text" 
                    id="summary_date_picker" 
                    name="summary_date" 
                    value="<?= htmlspecialchars($summary_date ?? date('Y-m-d')) ?>" 
                    class="border border-gray-400 rounded-lg px-3 py-1.5 text-sm font-semibold text-[#382214] text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500">
            </form>

            <!-- Dropdown เลือกชื่อหมอ -->
            <select id="filter_staff" onchange="applyQueueFilters()" class="border border-gray-400 rounded-lg px-3 py-1.5 text-sm font-semibold text-[#382214] text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">-- หมอนวดทั้งหมด --</option>
                <?php if (!empty($summary_staffs)): ?>
                    <?php foreach ($summary_staffs as $st): 
                        $st_name = $st['nickname'] ?? $st['name'];
                    ?>
                        <option value="<?= htmlspecialchars($st_name) ?>">
                            หมอ <?= htmlspecialchars($st_name) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <!-- Dropdown ช่วงเวลา -->
            <select id="filter_time_range" onchange="applyQueueFilters()" class="border border-gray-400 rounded-lg px-3 py-1.5 text-sm font-semibold text-[#382214] text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">-- ช่วงเวลาทั้งหมด --</option>
                <option value="morning">รอบเช้า (10:00 - 13:00)</option>
                <option value="afternoon">รอบบ่าย (13:00 - 17:00)</option>
                <option value="evening">รอบเย็น (17:00 - 22:00)</option>
            </select>

            <!-- ปุ่มล้างตัวกรอง -->
            <button type="button" 
                    onclick="resetQueueFilters()" 
                    class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition-colors border border-gray-300">
                ล้างตัวกรอง
            </button>
        </div>
        
        <!-- ฝั่งขวา: ปุ่ม Export to Excel -->
        <div>
            <a href="export_excel.php?summary_date=<?= htmlspecialchars($summary_date ?? date('Y-m-d')) ?>" 
            class="inline-flex items-center gap-2 bg-[#047857] hover:bg-[#065f46] text-white px-4 py-2 rounded-xl text-sm font-medium shadow transition-all active:scale-95">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <span>Export to Excel</span>
            </a>
        </div>

    </div>

    <div class="text-center font-bold text-xl text-[#382214]">
        ตารางการจองคิววันที่ <?= date('d/m/Y', strtotime($summary_date ?? date('Y-m-d'))) ?>
    </div>

    <?php 
    $max_rounds = 0;
    if (!empty($summary_staffs)) {
        foreach ($summary_staffs as $st) {
            $st_id = $st['staff_id'];
            $q_list = $staff_queues[$st_id] ?? [];
            
            $count = count($q_list);
            if ($count > $max_rounds) {
                $max_rounds = $count;
            }
        }
    }

    if ($max_rounds === 0) {
        $max_rounds = 1;
    }
    ?>

    <!-- 2. ตารางสรุปตารางคิวและรายรับ -->
    <div class="bg-white shadow-sm border border-[#382214] overflow-x-auto">
        <table class="w-full text-center border-collapse border border-[#382214] text-base md:text-base">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-400 text-[#382214]">
                    <th class="border border-gray-400 p-2.5 w-12">No</th>
                    <th class="border border-gray-400 p-2.5 w-28">ชื่อหมอ</th>
                    <th class="border border-gray-400 p-2.5 w-28">เวลาเข้างาน</th>
                    
                    <!-- แสดงหัวตารางรอบที่ 1 ถึงรอบสูงสุดจริง Dynamic -->
                    <?php for ($r = 1; $r <= $max_rounds; $r++): ?>
                        <th class="border border-gray-400 p-2.5 min-w-[150px]">รอบที่ <?= $r ?></th>
                    <?php endfor; ?>
                    
                    <!-- สรุปรายรับแยกตามช่องทาง -->
                    <th class="border border-gray-400 p-0" colspan="4">
                        <div class="p-1.5 border-b border-gray-400 font-bold">รวมรายรับ</div>
                        <div class="grid grid-cols-4 divide-x divide-gray-400 text-sm font-semibold">
                            <span class="py-1">เงินสด</span>
                            <span class="py-1">โอน</span>
                            <span class="py-1">เครดิต</span>
                            <span class="py-1">คูปอง</span>
                        </div>
                    </th>
                    
                    <th class="border border-gray-400 p-2.5 w-28 font-bold">ค่ามือหมอ</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $grand_cash = 0;
                    $grand_transfer = 0;
                    $grand_credit = 0;
                    $grand_coupon = 0;
                    $grand_hand_fee = 0;
                    $no = 1;

                    if (!empty($summary_staffs)):
                        foreach ($summary_staffs as $st): 
                            $st_id = $st['staff_id'];
                            $q_list = $staff_queues[$st_id] ?? [];

                            $cash = 0; $transfer = 0; $credit = 0; $coupon = 0; $hand_fee = 0;

                            foreach ($q_list as $q) {
                                // 1. ดึงราคารวมของคิว
                                $price = (float)($q['total_price'] ?? $q['price'] ?? 0);
                                
                                // 2. ดึงวิธีชำระเงินและแปลงเป็นตัวพิมพ์เล็ก
                                $method = strtolower(trim($q['payment_method'] ?? ''));
                                // 3. ตรวจสอบแยกหมวดหมู่การชำระเงิน
                                if (empty($method)) {
                                    continue; 
                                }
                                if ($method === 'cash' || $method === 'เงินสด') {
                                    $cash += $price;
                                } elseif ($method === 'transfer' || $method === 'โอน' || $method === 'promptpay') {
                                    $transfer += $price;
                                } elseif ($method === 'credit' || $method === 'เครดิต' || $method === 'card') {
                                    $credit += $price;
                                } elseif ($method === 'coupon' || $method === 'คูปอง') {
                                    $coupon += $price;
                                } else {
                                    $cash += $price; 
                                }

                                // 4. ดึงค่ามือหมอรวมของคิว
                                // $hand_fee += (int)($q['total_hand_fee'] ?? $q['hand_fee_amount'] ?? $q['hand_fee'] ?? 0);
                                $hand_fee += (int)($q['hand_fee'] ?? 0);
                            }

                            $grand_cash += $cash;
                            $grand_transfer += $transfer;
                            $grand_credit += $credit;
                            $grand_coupon += $coupon;
                            $grand_hand_fee += $hand_fee;
                    ?>
                    <tr class="hover:bg-amber-50/30 transition-colors">
                        <td class="border border-gray-400 p-2 font-medium text-center"><?= $no++ ?></td>
                        <td class="border border-gray-400 p-2 font-bold text-gray-800">
                            <?= htmlspecialchars($st['nickname'] ?? $st['name']) ?>
                        </td>
                       <!-- แสดงเวลา Check-in  -->
                        <td class="border border-gray-400 p-2 font-bold text-gray-800">
                            <?php if (!empty($st['checkin_time'])): ?>
                                <div class="text-sm font-bold text-[#382214]    ">
                                    <?= date('H:i', strtotime($st['checkin_time'])) ?> น.
                                </div>
                            <?php else: ?>
                                <div class="text-xs font-bold text-red-500">
                                    ยังไม่เช็กอิน
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- แสดงคิวรอบต่างๆ -->
                        <?php for ($i = 0; $i < $max_rounds; $i++): ?>
                            <td class="queue-round-cell border border-gray-400 p-1.5 align-top">
                                <?php if (isset($q_list[$i])): 
                                        $qi = $q_list[$i];
                                        
                                        // 1. คำนวณเวลาเริ่ม - เวลาเสร็จ
                                        $start_t  = date('H:i', strtotime($qi['booking_time']));
                                        $duration = (int)($qi['total_duration'] ?? $qi['duration'] ?? 60);
                                        $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                        
                                        // 2. ชื่อบริการรวม และ ราคารวม
                                        $service_name = !empty($qi['service_names']) ? $qi['service_names'] : ($qi['service_name'] ?? 'ไม่ระบุบริการ');
                                        $price        = (float)($qi['total_price'] ?? $qi['price'] ?? 0);
                                        $method = strtolower(trim($qi['payment_method'] ?? ''));
                                        $coupon_code = !empty($qi['coupon_code']) ? trim($qi['coupon_code']) : '';
                                    ?>
                                        <div class="queue-booking overflow-hidden text-left <?= ($qi['status'] ?? '') === 'completed' ? 'bg-emerald-100 rounded-lg p-2' : '' ?>" data-booking-time="<?= htmlspecialchars($qi['booking_time'], ENT_QUOTES, 'UTF-8') ?>">
                                            <!-- เวลาเริ่ม - เสร็จ -->
                                            <div class="font-bold text-[18px] text-[#382214] flex justify-between items-center gap-1">
                                                <span class="whitespace-nowrap"><?= $start_t ?> - <?= $end_t ?></span>
                                                <?php if ($method === 'coupon' || $method === 'คูปอง'): ?>
                                                    <span class="text-amber-900 px-1.5 py-0.5 rounded font-bold whitespace-nowrap text-[18px]">
                                                        <?= !empty($coupon_code) ? htmlspecialchars($coupon_code) : '' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-[#047857] font-bold whitespace-nowrap"><?= number_format($price) ?>฿</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="p-0.5 space-y-2">
                                                <!-- ชื่อบริการรวม + ระยะเวลารวม -->
                                                <div class="text-base font-semibold text-gray-800 leading-tight">
                                                    <?= htmlspecialchars($service_name) ?> 
                                                    <span class="text-[#382214] font-bold"><?= $duration ?> นาที</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                            </td>
                        <?php endfor; ?>

                        <!-- รายรับ -->
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($cash) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($transfer) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($credit) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($coupon) ?></td>

                        <!-- ค่ามือหมอ -->
                        <td class="border border-gray-400 p-2 text-right w-24 font-bold text-amber-900"><?= number_format($hand_fee) ?></td>
                    </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                    <tr>
                        <td colspan="<?= 3 + $max_rounds + 5 ?>" class="p-6 text-center text-gray-500 font-bold">
                            ยังไม่มีคิวจองหรือพนักงานเช็กอินในวันที่ <?= date('d/m/Y', strtotime($summary_date ?? date('Y-m-d'))) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
            </tbody>

            <tfoot>
                <tr class="bg-gray-100 font-bold border-t border-gray-400 text-gray-800">
                    <td colspan="<?= 3 + $max_rounds ?>" class="border border-gray-400 p-2.5 text-center">รวม</td>
                    <td class="border border-gray-400 p-2 text-right text-emerald-700"><?= number_format($grand_cash) ?></td>
                    <td class="border border-gray-400 p-2 text-right text-emerald-700"><?= number_format($grand_transfer) ?></td>
                    <td class="border border-gray-400 p-2 text-right text-emerald-700"><?= number_format($grand_credit) ?></td>
                    <td class="border border-gray-400 p-2 text-right text-emerald-700"><?= number_format($grand_coupon) ?></td>
                    <td class="border border-gray-400 p-2 text-right text-amber-800"><?= number_format($grand_hand_fee) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
</section>