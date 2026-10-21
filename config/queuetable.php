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

    <!-- 2. ตารางสรุปตารางคิวและรายรับ -->
    <div class="bg-white shadow-sm border border-[#382214] overflow-x-auto">
        <table class="w-full text-center border-collapse border border-[#382214] text-base md:text-base">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-400 text-[#382214]">
                    <th class="border border-gray-400 p-2.5 w-12">No</th>
                    <th class="border border-gray-400 p-2.5 w-28">ชื่อหมอ</th>
                    <th class="border border-gray-400 p-2.5 w-28">เวลาเข้างาน</th>
                    
                    <th class="border border-gray-400 p-2.5" colspan="4">คิวจอง</th>
                    
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
                    $booking_payment_details = [];

                    if (!empty($summary_staffs)):
                        foreach ($summary_staffs as $st): 
                            $st_id = $st['staff_id'];
                            $q_list = $staff_queues[$st_id] ?? [];

                            $cash = 0; $transfer = 0; $credit = 0; $coupon = 0; $hand_fee = 0;

                            foreach ($q_list as $q) {
                                $booking_id = $q['booking_id'] ?? $q['id'];
                                $status     = $q['status'] ?? '';
                                $service_price = (float)($q['total_price'] ?? $q['price'] ?? 0);
                                $is_full_coverage_face_massage =
                                    (int)($q['service_id'] ?? 0) === 35
                                    && (int)($q['coupon_id'] ?? 0) === 3;

                                $stmt_pay = $pdo->prepare("
                                    SELECT payment_id, amount, payment_method, coupon_code
                                    FROM payments
                                    WHERE booking_id = :b_id
                                    ORDER BY payment_id ASC
                                ");
                                $stmt_pay->execute([':b_id' => $booking_id]);
                                $payment_records = $stmt_pay->fetchAll(PDO::FETCH_ASSOC);

                                $coupon_records = [];
                                $latest_channel_payments = [];
                                $pending_records = [];
                                foreach ($payment_records as $payment_record) {
                                    $amount = (float)$payment_record['amount'];
                                    $method = strtolower(trim($payment_record['payment_method'] ?? ''));

                                    if ($method === 'coupon' || $method === 'คูปอง') {
                                        $code = trim($payment_record['coupon_code'] ?? '');
                                        if ($amount <= 0 && $code !== '') {
                                            $amount = 300;
                                        }
                                        if ($is_full_coverage_face_massage && $code !== '') {
                                            $amount = $service_price;
                                        }
                                        $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', $code)))));
                                        $coupon_records[] = [
                                            'label' => $code === '' ? 'คูปอง' : 'คูปอง ' . $code,
                                            'amount' => $amount,
                                            'method' => 'coupon',
                                            'codes' => $codes,
                                            'payment_id' => (int)$payment_record['payment_id']
                                        ];
                                    } elseif ($method === 'pending' && !$is_full_coverage_face_massage) {
                                        $pending_records[] = [
                                            'label' => 'ค้างชำระ',
                                            'amount' => $amount,
                                            'method' => 'pending'
                                        ];
                                    } elseif (in_array($method, ['cash', 'เงินสด'], true)) {
                                        $latest_channel_payments['cash'] = [
                                            'label' => 'เงินสด',
                                            'amount' => $amount,
                                            'method' => 'cash'
                                        ];
                                    } elseif (in_array($method, ['transfer', 'โอน', 'promptpay'], true)) {
                                        $latest_channel_payments['transfer'] = [
                                            'label' => 'โอน',
                                            'amount' => $amount,
                                            'method' => 'transfer'
                                        ];
                                    } elseif (in_array($method, ['credit', 'เครดิต', 'card'], true)) {
                                        $latest_channel_payments['credit'] = [
                                            'label' => 'เครดิต',
                                            'amount' => $amount,
                                            'method' => 'credit'
                                        ];
                                    }
                                }

                                $unique_coupon_records = [];
                                foreach ($coupon_records as $coupon_record) {
                                    $codes = $coupon_record['codes'];
                                    sort($codes);
                                    $signature = empty($codes)
                                        ? 'payment-' . $coupon_record['payment_id']
                                        : implode(',', $codes);
                                    if (
                                        !isset($unique_coupon_records[$signature])
                                        || $coupon_record['payment_id'] > $unique_coupon_records[$signature]['payment_id']
                                    ) {
                                        $unique_coupon_records[$signature] = $coupon_record;
                                    }
                                }

                                $combined_coupon_codes = [];
                                foreach ($unique_coupon_records as $coupon_record) {
                                    if (count($coupon_record['codes']) > 1) {
                                        foreach ($coupon_record['codes'] as $code) {
                                            $combined_coupon_codes[$code] = true;
                                        }
                                    }
                                }

                                $payment_details = [];
                                foreach ($unique_coupon_records as $coupon_record) {
                                    if (
                                        count($coupon_record['codes']) === 1
                                        && isset($combined_coupon_codes[$coupon_record['codes'][0]])
                                    ) {
                                        continue;
                                    }
                                    $payment_details[] = [
                                        'label' => $coupon_record['label'],
                                        'amount' => $coupon_record['amount'],
                                        'method' => $coupon_record['method']
                                    ];
                                }
                                $coupon_amount = array_sum(array_column($payment_details, 'amount'));
                                $remaining_paid_amount = $status === 'completed'
                                    ? max(0, $service_price - $coupon_amount)
                                    : null;

                                foreach (array_reverse($latest_channel_payments) as $channel_payment) {
                                    if ($remaining_paid_amount !== null) {
                                        $channel_payment['amount'] = min($channel_payment['amount'], $remaining_paid_amount);
                                        $remaining_paid_amount -= $channel_payment['amount'];
                                    }
                                    if ($channel_payment['amount'] > 0) {
                                        $payment_details[] = $channel_payment;
                                    }
                                }

                                if ($status !== 'completed') {
                                    $payment_details = array_merge($payment_details, $pending_records);
                                }

                                if (empty($payment_records) && $status === 'completed') {
                                    $method = strtolower(trim($q['payment_method'] ?? 'cash'));
                                    $fallback = [
                                        'transfer' => ['label' => 'โอน', 'method' => 'transfer'],
                                        'โอน' => ['label' => 'โอน', 'method' => 'transfer'],
                                        'promptpay' => ['label' => 'โอน', 'method' => 'transfer'],
                                        'credit' => ['label' => 'เครดิต', 'method' => 'credit'],
                                        'เครดิต' => ['label' => 'เครดิต', 'method' => 'credit'],
                                        'card' => ['label' => 'เครดิต', 'method' => 'credit'],
                                        'coupon' => ['label' => 'คูปอง', 'method' => 'coupon'],
                                        'คูปอง' => ['label' => 'คูปอง', 'method' => 'coupon']
                                    ];
                                    $fallback_payment = $fallback[$method] ?? ['label' => 'เงินสด', 'method' => 'cash'];
                                    $payment_details[] = array_merge($fallback_payment, ['amount' => $service_price]);
                                }

                                $booking_payment_details[$booking_id] = $payment_details;

                                if ($status === 'completed') {
                                    foreach ($payment_details as $payment_detail) {
                                        if ($payment_detail['method'] === 'cash') {
                                            $cash += $payment_detail['amount'];
                                        } elseif ($payment_detail['method'] === 'transfer') {
                                            $transfer += $payment_detail['amount'];
                                        } elseif ($payment_detail['method'] === 'credit') {
                                            $credit += $payment_detail['amount'];
                                        } elseif ($payment_detail['method'] === 'coupon') {
                                            $coupon += $payment_detail['amount'];
                                        }
                                    }
                                }

                                // 🟢 2. ค่ามือหมอ (สามารถเลือกได้ว่าต้องการคิดเฉพาะ completed หรือทุกคิวที่ confirmed)
                                if ($status === 'completed' || $status === 'confirmed') {
                                    $hand_fee += (int)($q['hand_fee'] ?? 0);
                                }
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

                        <!-- แสดงคิวรอบต่างๆ แถวละ 4 รอบ -->
                        <td class="border border-gray-400 p-0 align-top" colspan="4">
                            <div class="grid grid-cols-4">
                                <?php foreach (array_values($q_list) as $i => $qi): ?>
                                    <div class="min-w-[150px] border-r border-b border-gray-300 p-1.5 text-left">
                                        <div class="mb-1 border-b border-gray-300 pb-1 text-center text-sm font-bold text-[#382214]">
                                            รอบที่ <?= $i + 1 ?>
                                        </div>
                                        <?php
                                        // 1. คำนวณเวลาเริ่ม - เวลาเสร็จ
                                        $start_t  = date('H:i', strtotime($qi['booking_time']));
                                        $duration = (int)($qi['total_duration'] ?? $qi['duration'] ?? 60);
                                        $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                        
                                        // 2. ชื่อบริการรวม และ ราคารวม
                                        $service_name = !empty($qi['service_names']) ? $qi['service_names'] : ($qi['service_name'] ?? 'ไม่ระบุบริการ');
                                        $price        = (float)($qi['total_price'] ?? $qi['price'] ?? 0);
                                        $booking_id   = $qi['booking_id'] ?? $qi['id'];
                                        ?>
                                        <div class="queue-booking overflow-hidden text-left <?= ($qi['status'] ?? '') === 'completed' ? 'bg-emerald-100 rounded-lg p-2' : '' ?>" data-booking-time="<?= htmlspecialchars($qi['booking_time'], ENT_QUOTES, 'UTF-8') ?>">
                                            <!-- เวลาเริ่ม - เสร็จ -->
                                            <div class="font-bold text-[18px] text-[#382214] flex justify-between items-center gap-1">
                                                <span class="whitespace-nowrap"><?= $start_t ?> - <?= $end_t ?></span>
                                                <span class="text-[#047857] font-bold whitespace-nowrap"><?= number_format($price) ?>฿</span>
                                            </div>
                                            <div class="p-0.5 space-y-2">
                                                <!-- ชื่อบริการรวม + ระยะเวลารวม -->
                                                <div class="text-base font-semibold text-gray-800 leading-tight">
                                                    <?= htmlspecialchars($service_name) ?> 
                                                    <span class="text-[#382214] font-bold"><?= $duration ?> นาที</span>
                                                </div>
                                                <?php $payment_details = $booking_payment_details[$booking_id] ?? []; ?>
                                                <?php if (!empty($payment_details)): ?>
                                                    <div class="flex flex-wrap gap-1">
                                                        <?php foreach ($payment_details as $payment_detail): ?>
                                                            <span class="rounded bg-white/80 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-700">
                                                                <?= htmlspecialchars($payment_detail['label'], ENT_QUOTES, 'UTF-8') ?>
                                                                <?= number_format($payment_detail['amount']) ?>฿
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>

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
                        <td colspan="12" class="p-6 text-center text-gray-500 font-bold">
                            ยังไม่มีคิวจองหรือพนักงานเช็กอินในวันที่ <?= date('d/m/Y', strtotime($summary_date ?? date('Y-m-d'))) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
            </tbody>

            <tfoot>
                <tr class="bg-gray-100 font-bold border-t border-gray-400 text-gray-800">
                    <td colspan="7" class="border border-gray-400 p-2.5 text-center">รวม</td>
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