<?php
// ข้อมูลบทความข่าวสาร
$article_title = "ฟื้นฟูร่างกายขั้นสุด! นวดสปอร์ต (Sports Massage) และ แช่น้ำแข็ง (Ice Bath)";
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
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Banner Section -->
            <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 p-8 md:p-10 text-white relative">
                <h1 class="text-2xl md:text-3xl font-bold leading-tight mb-3">
                    ❄️ <?php echo $article_title; ?>
                </h1>
                <p class="text-cyan-100 text-sm md:text-base font-light">
                    เคล็ดลับการฟื้นฟูกล้ามเนื้อ ปลดล็อกอาการล้า และเร่งกระบวนการซ่อมแซมร่างกายของนักกีฬาและสายรักสุขภาพ
                </p>
            </div>

            <!-- Content Area -->
            <div class="p-6 md:p-10 space-y-8 leading-relaxed">
                
                <!-- Introduction -->
                <div>
                    <p class="text-[#382214]">
                        ไม่ว่าคุณจะเป็นนักกีฬา สายออกกำลังกาย หรือผู้ที่มีอาการกล้ามเนื้อตึงสะสมจากการใช้งานหนัก ร่างกายมักเกิดการอักเสบระดับเล็กน้อยในมัดกล้ามเนื้อ การผสมผสานระหว่างการนวดสปอร์ต (Sports Massage) และการแช่น้ำแข็ง (Ice Bath / Cold Therapy) จึงเป็นหนึ่งในวิธีฟื้นฟูที่ได้รับความนิยมสูงที่สุดในปัจจุบัน
                    </p>
                </div>

                <!-- Section 1: Sports Massage -->
                <div class="bg-[#FAF6F0] p-6 rounded-2xl">
                    <h2 class="text-xl font-bold slate-800 mb-3 flex items-center gap-2">
                        <span>💆‍♂️</span> นวดสปอร์ต (Sports Massage) ดีอย่างไร?
                    </h2>
                    <p class="text-sm text-[#382214] mb-4">
                        เป็นการนวดเน้นน้ำหนักเพื่อรีดพังผืด สลายปมกล้ามเนื้อ (Trigger Points) และกระตุ้นการไหลเวียนของเลือดในกล้ามเนื้อชั้นลึก
                    </p>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-300">
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-cyan-300 block mb-1">คลายพังผืดและปมกล้ามเนื้อ</strong>
                            ลดอาการเกร็ง ตึง และเพิ่มความยืดหยุ่นให้ข้อต่อ
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-cyan-300 block mb-1">สลายกรดแลกติก (Lactic Acid)</strong>
                            เร่งการขับของเสียออกจากมัดกล้ามเนื้อหลังใช้งานหนัก
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-cyan-300 block mb-1">ลดความเสี่ยงการบาดเจ็บ</strong>
                            เตรียมสภาพกล้ามเนื้อให้พร้อมก่อนและหลังเล่นกีฬา
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-cyan-300 block mb-1">เพิ่มช่วงการเคลื่อนไหว (ROM)</strong>
                            ช่วยให้ขยับร่างกายได้คล่องตัวยิ่งขึ้น
                        </li>
                    </ul>
                </div>

                <!-- Section 2: Ice Bath -->
                <div class="bg-[#FAF6F0] p-6 rounded-2xl">
                    <h2 class="text-xl font-bold text-slate-800 mb-3 flex items-center gap-2">
                        <span>🧊</span> การแช่น้ำแข็ง (Ice Bath / Cold Therapy)
                    </h2>
                    <p class="text-sm text-[#382214] mb-4">
                        การจุ่มตัวในน้ำเย็นจัด (อุณหภูมิประมาณ 10-15°C เป็นเวลา 10-15 นาที) เพื่ออาศัยความเย็นในการชะลอและบำบัดกล้ามเนื้อ
                    </p>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-300">
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-blue-300 block mb-1">ลดการอักเสบและบวม</strong>
                            ความเย็นช่วยให้หลอดเลือดหดตัว ลดอาการระบมหลังออกกำลังกาย
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-blue-300 block mb-1">ลดอาการปวดกล้ามเนื้อล่าช้า (DOMS)</strong>
                            บรรเทาอาการปวดระบมที่มักจะเกิดหลังซ้อมหนัก 24-48 ชม.
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-blue-300 block mb-1">กระตุ้นระบบประสาทและรีเฟรช</strong>
                            ช่วยให้ร่างกายสดชื่น ตื่นตัว และนอนหลับได้ดีขึ้น
                        </li>
                        <li class="bg-slate-800 p-3 rounded-xl border border-slate-700/50">
                            <strong class="text-blue-300 block mb-1">Flush ของเสียในเลือด</strong>
                            เมื่อลุกออกจากน้ำเย็น หลอดเลือดจะขยายตัวและสูบฉีดเลือดใหม่เข้าสู่กล้ามเนื้อ
                        </li>
                    </ul>
                </div>

                <!-- Combo Benefit -->
                <div class="bg-[#FAF6F0] p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-800 mb-2">
                        ⚡ พลังคูณสอง: ทำไมการรวม "นวดสปอร์ต + แช่น้ำแข็ง" ถึงทรงพลังที่สุด?
                    </h3>
                    <p class="text-xs text-[#382214] leading-relaxed">
                        การแช่น้ำแข็งจะช่วยลดการอักเสบ ความร้อน และอาการระบมของกล้ามเนื้อก่อน จากนั้นตามด้วยการนวดสปอร์ต เพื่อคลายปมพังผืดและรีดเส้นตึงสะสม การทำควบคู่กันจะช่วยรีเซ็ตร่างกายให้กลับมาฟิตเต็มร้อยได้รวดเร็วยิ่งขึ้น!
                    </p>
                </div>

                <!-- Footer Contact -->
                <div class="border-t border-slate-700/60 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                    <div>
                        <p>📞 <strong>สอบถาม/สำรองคิว:</strong> <?php echo $shop_phone; ?></p>
                        <p>💬 <strong>LINE Official:</strong> <?php echo $shop_line; ?></p>
                    </div>
                    <p class="italic text-slate-500">* เหมาะสำหรับนักกีฬา ผู้ที่เล่นเวทเทรนนิ่ง วิ่ง มาราธอน และผู้มีอาการเกร็งตึงเรื้อรัง</p>
                </div>

            </div>
        </div>
    </div>
    <?php include 'includes\footer.php'; ?>

</body>
</html>