<?php
// ข้อมูลบทความข่าวสาร
$article_title = "นั่งทำงานหน้าคอมนานๆ ระวัง! ออฟฟิศซินโดรม (Office Syndrome) ถามหา";
$article_category = "ข่าวสาร";
$publish_date = date("d/m/Y");

$shop_phone = "081-970-9933";
$shop_line = "@bodinspa";
$gowabi_url = "https://www.gowabi.com"; // ลิงก์ร้านของคุณบน GoWabi
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชั่น - Baanbodinspa</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes\nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header / Category -->
        <div class="mb-4 flex items-center gap-3">
            <span class="bg-yellow-100 text-[#382214] text-xs font-semibold px-3 py-1 rounded-full uppercase">
                <?php echo $article_category; ?>
            </span>
            <span class="text-slate-400 text-sm">เผยแพร่เมื่อ: <?php echo $publish_date; ?></span>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            <!-- Banner Section -->
            <div class="bg-[#D2BFA6] p-8 md:p-10 text-[#382214]">
                <h1 class="text-2xl md:text-3xl font-bold leading-tight mb-3">
                    🖥️ <?php echo $article_title; ?>
                </h1>
                <p class="text-[#382214] text-sm md:text-base font-light">
                    ทำความรู้จักกับภัยเงียบคนทำงาน พร้อมเช็กสัญญาณเตือน และวิธีดูแลฟื้นฟูร่างกายอย่างถูกวิธี
                </p>
            </div>

            <!-- Content Area -->
            <div class="p-6 md:p-10 space-y-8 leading-relaxed">
                
                <!-- Introduction -->
                <div>
                    <p class="text-slate-600">
                        ในยุคปัจจุบันที่หลายคนต้องนั่งทำงานหน้าจอคอมพิวเตอร์ต่อเนื่องวันละ 6-8 ชั่วโมงขึ้นไป โดยไม่ได้เปลี่ยนอิริยาบถ รู้หรือไม่ว่าพฤติกรรมนี้กำลังทำร้ายร่างกายของคุณโดยไม่รู้ตัว และเป็นสาเหตุหลักของการเกิด <strong>"โรคออฟฟิศซินโดรม (Office Syndrome)"</strong>
                    </p>
                </div>

                <!-- Symptoms Check -->
                <div class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl">
                    <h2 class="text-lg font-bold text-amber-900 mb-2 flex items-center gap-2">
                        <span>⚠️</span> เช็กสัญญาณเตือน! คุณกำลังเสี่ยงออฟฟิศซินโดรมหรือไม่?
                    </h2>
                    <ul class="list-disc list-inside text-sm text-amber-900/90 space-y-1.5">
                        <li>ตึง ปวด เม็ดเกร็ง บริเวณคอ บ่า ไหล่ หรือสะบัก</li>
                        <li>ปวดหลังล่างจากการนั่งท่าเดิมนานๆ หรือนั่งหลังค่อม</li>
                        <li>มีอาการปวดศีรษะ ร้าวขึ้นขมับ หรือปวดกระบอกตา</li>
                        <li>ชาลงแขน มือ หรือนิ้วมือ ล้าสายตาจากการจ้องจอนาน</li>
                    </ul>
                </div>

                <!-- Causes Section -->
                <div>
                    <h2 class="text-xl font-bold text-slate-900 mb-4 border-l-4 border-teal-600 pl-3">
                        สาเหตุหลักที่ทำให้เกิดอาการ
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <strong class="text-teal-700 block mb-1">1. ท่าทางการนั่งที่ไม่ถูกต้อง</strong>
                            <p class="text-slate-600">นั่งหลังค่อม ก้มคอจ้องจอ ยื่นคอไปข้างหน้า หรือยักไหล่ขณะพิมพ์งาน</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <strong class="text-teal-700 block mb-1">2. นั่งเกร็งท่าเดิมนานเกินไป</strong>
                            <p class="text-slate-600">กล้ามเนื้อไม่ได้รับการผ่อนคลาย ขาดการขยับขยายหรือยืดเหยียด</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <strong class="text-teal-700 block mb-1">3. สภาพแวดล้อมไม่เหมาะสม</strong>
                            <p class="text-slate-600">ระดับโต๊ะ เก้าอี้ หรือความสูงของหน้าจอคอมพิวเตอร์ไม่อยู่ในระดับสายตา</p>
                        </div>
                    </div>
                </div>

                <!-- Prevention Tips -->
                <div>
                    <h2 class="text-xl font-bold text-slate-900 mb-4 border-l-4 border-teal-600 pl-3">
                        💡 3 วิธีป้องกันและบรรเทาเบื้องต้น
                    </h2>
                    <ol class="list-decimal list-inside space-y-2 text-slate-700 text-sm md:text-base">
                        <li><strong>ปรับระดับโต๊ะ-เก้าอี้:</strong> ตั้งหน้าจอให้อยู่ในระดับสายตา หลังพิงเบาะให้พอดี ไม่ก้มหรือเงยคอมากเกินไป</li>
                        <li><strong>พักทุกๆ 1 ชั่วโมง:</strong> ลุกขึ้นยืน เดิน หรือยืดเหยียดกล้ามเนื้อคอ บ่า แขน อย่างน้อย 3-5 นาที</li>
                        <li><strong>นวดคลายกล้ามเนื้อสม่ำเสมอ:</strong> การนวดเน้นจุดคลายพังผืดจะช่วยลดการสะสมของความตึงเครียดในกล้ามเนื้อชั้นลึกได้เป็นอย่างดี</li>
                    </ol>
                </div>

                <!-- Promotion Callout Section -->
                <div class="bg-[#FAF6F0] rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-[#382214] mb-2 flex items-center gap-2">
                        ฟื้นฟูร่างกายจากออฟฟิศซินโดรมกับบริการนวดของเรา
                    </h3>
                    <p class="text-sm text-slate-600 mb-4">
                        อย่าปล่อยให้อาการปวดสะสมจนกลายเป็นปัญหาเรื้อรัง มาผ่อนคลายและแก้อาการกับคอร์สนวดออฟฟิศซินโดรมและนวดอโรม่าโดยผู้เชี่ยวชาญ
                    </p>

                    <!-- GoWabi Booking Banner -->
                    <div class="bg-white p-4 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4">
                        <div>
                            <span class="text-xs bg-yellow-100 text-[#382214] font-bold px-2.5 py-0.5 rounded-full">เปิดให้จองคิวแล้ว</span>
                            <p class="text-sm font-semibold text-slate-800 mt-1">
                                เลือกชมแพ็กเกจ เช็กคิวว่าง และจองคิวเลย!
                            </p>
                        </div>
                        <a href="login.php" target="_blank" class="bg-[#047857] text-[#FFFFFF] px-5 py-2 rounded font-medium hover:bg-[#FFF5E1] hover:text-[#382214] transition-all duration-300">
                            BOOKING
                        </a>
                    </div>
                </div>

                <!-- Footer Contact -->
                <div class="border-t pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                    <div>
                        <p><strong>ปรึกษาหรือสอบถามเพิ่มเติม:</strong> <?php echo $shop_phone; ?></p>
                        <p><strong>LINE Official:</strong> <?php echo $shop_line; ?></p>
                    </div>
                    <p class="italic">ดูแลสุขภาพของคุณวันนี้ ก่อนที่ออฟฟิศซินโดรมจะรบกวนการทำงาน</p>
                </div>

            </div>
        </div>
    </div>

    <?php include 'includes\footer.php'; ?>

</body>
</html>