<!-- TAB 1: ตารางคิวจองและสรุปรายรับประจำวัน -->
<section id="section-summary" class="space-y-6 hidden">
    
    <div class="flex items-center gap-3">
        <label class="text-ml font-bold text-[#382214] whitespace-nowrap">ตารางการจองคิววันที่ :</label>
        <form id="summary_date_form" method="GET" action="dashboard.php" class="flex items-center gap-1">
            <input type="hidden" name="tab" value="summary">
            <input type="text" 
                id="summary_date_picker" 
                name="summary_date" 
                value="<?= htmlspecialchars($summary_date ?? date('Y-m-d')) ?>" 
                onchange="this.form.submit()"
                class="border border-gray-400 rounded-lg px-3 py-1.5 text-sm font-semibold text-[#382214] text-center w-36 cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500">
        </form>
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
            
            if (empty($q_list)) continue;

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
                    
                    <!-- 🟢 แสดงหัวตารางรอบที่ 1 ถึงรอบสูงสุดจริง Dynamic -->
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
                    $has_active_staff = false;

                    if (!empty($summary_staffs)):
                        foreach ($summary_staffs as $st): 
                            $st_id = $st['staff_id'];
                            $q_list = $staff_queues[$st_id] ?? [];

                            if (empty($q_list)) continue;

                            $has_active_staff = true;
                            $cash = 0; $transfer = 0; $credit = 0; $coupon = 0; $hand_fee = 0;

                            foreach ($q_list as $q) {
                                // 1. ดึงราคารวมของคิว
                                $price = (float)($q['total_price'] ?? $q['price'] ?? 0);
                                
                                // 2. ดึงวิธีชำระเงินและแปลงเป็นตัวพิมพ์เล็ก
                                $method = strtolower(trim($q['payment_method'] ?? 'cash'));

                                // 3. ตรวจสอบแยกหมวดหมู่การชำระเงิน
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
                                $hand_fee += (int)($q['total_hand_fee'] ?? $q['hand_fee_amount'] ?? $q['commission'] ?? 0);
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

                        <!-- แสดงคิวรอบต่างๆ -->
                        <?php for ($i = 0; $i < $max_rounds; $i++): ?>
                            <td class="border border-gray-400 p-1.5 align-top">
                                <?php if (isset($q_list[$i])): 
                                    $qi = $q_list[$i];
                                    
                                    // 1. คำนวณเวลาเริ่ม - เวลาเสร็จ
                                    $start_t  = date('H:i', strtotime($qi['booking_time']));
                                    $duration = (int)($qi['total_duration'] ?? $qi['duration'] ?? 60);
                                    $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                    
                                    // 2. ชื่อบริการ และ ราคารวม
                                    $service_name = !empty($qi['service_names']) ? $qi['service_names'] : ($qi['service_name'] ?? 'ไม่ระบุบริการ');
                                    $price        = (float)($qi['total_price'] ?? $qi['price'] ?? 0);
                                    $method = strtolower(trim($qi['payment_method'] ?? ''));
                                    $coupon_code = !empty($qi['coupon_code']) ? trim($qi['coupon_code']) : '';
                                ?>
                                    <div class="overflow-hidden text-left">
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
                                            <!-- ชื่อบริการ + ระยะเวลารวม -->
                                            <div class="text-base font-semibold text-gray-800 leading-tight">
                                                <?= htmlspecialchars($service_name) ?> 
                                                <span class="text-[#382214] font-bold"><?= $duration ?> นาที</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>

                        <!-- รายรับ (ใส่ 0 เสมอเมื่อไม่มีข้อมูล เพื่อให้คอลัมน์กว้างเท่ากัน) -->
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($cash) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($transfer) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($credit) ?></td>
                        <td class="border border-gray-400 p-2 text-right w-24 font-medium"><?= number_format($coupon) ?></td>

                        <!-- ค่ามือหมอ -->
                        <td class="border border-gray-400 p-2 text-right w-24 font-bold text-amber-900"><?= number_format($hand_fee) ?></td>
                    </tr>
                    <?php 
                        endforeach; 
                    endif;

                    // กรณีไม่มีหมอนวดคนไหนมีคิวเลยในวันที่เลือก
                    if (!$has_active_staff):
                    ?>
                    <tr>
                        <td colspan="<?= 2 + $max_rounds + 5 ?>" class="p-6 text-center text-gray-500">
                            ไม่มีรายการจองคิวในวันที่ <?= date('d/m/Y', strtotime($summary_date ?? date('Y-m-d'))) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
            </tbody>

            <tfoot>
                <tr class="bg-gray-100 font-bold border-t border-gray-400 text-gray-800">
                    <!-- 🟢 คำนวณ colspan Dynamic ตามจำนวนคอลัมน์ลำดับ + ชื่อหมอ + รอบคิวทั้งหมด -->
                    <td colspan="<?= 2 + $max_rounds ?>" class="border border-gray-400 p-2.5 text-center">รวม</td>
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