<?php
// กำหนดข้อมูลข่าวสารและโปรโมชั่น (สามารถเปลี่ยนไปดึงจาก Database ในอนาคตได้)
$news_title = "จองบริการนวดและสปาสุดผ่อนคลายกับเราผ่าน GoWabi ได้แล้ววันนี้!";
$news_date = date("d/m/Y"); // แสดงวันที่ปัจจุบัน
$shop_phone = "081-970-9933";
$shop_line = "@bodinspa";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชั่น - <?php echo $news_title; ?></title>
    <!-- Google Fonts & Tailwind CSS CDN เพื่อความสวยงาม -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes\nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header / Breadcrumb -->
        <div class="mb-6">
            <span class="bg-yellow-100 text-[#382214] text-xs font-semibold px-3 py-1 rounded-full uppercase">ข่าวสาร</span>
            <span class="text-gray-400 text-sm ml-2">เผยแพร่เมื่อ: <?php echo $news_date; ?></span>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Banner Section -->
            <div class="bg-[#D2BFA6] p-8 text-[#382214]">
                <h1 class="text-2xl md:text-3xl font-bold leading-tight mb-2">
                    ประกาศสำคัญ: <?php echo $news_title; ?>
                </h1>
                <p class="text-[#382214] text-sm md:text-base">
                    ยกระดับความสะดวกสบายในการดูแลตัวเอง เลือกชมเมนู ค้นหาคิวว่าง และจองแพ็กเกจผ่าน GoWabi ได้ตลอด 24 ชั่วโมง
                </p>
            </div>

            <div class="p-6 md:p-8 space-y-8">
                <!-- Highlights Section -->
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-[#382214] pl-3">
                        ไฮไลต์บริการที่เปิดให้จองผ่าน GoWabi
                    </h2>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <li class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <strong class="text-[#382214] block mb-1">🧘‍♀️ โปรแกรมนวดสลายไขมัน</strong>
                            <p class="text-sm text-gray-600">ลดไขมันเฉพาะจุดง่ายๆ ด้วยเครื่อง G5 RF ผ้าห่มอินฟราเรท</p>
                        </li>
                        <li class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <strong class="text-[#382214] block mb-1">🌿 นวดอโรมาและออยล์สปา</strong>
                            <p class="text-sm text-gray-600">ผ่อนคลายความเครียดด้วยน้ำมันหอมระเหยสูตรพิเศษ</p>
                        </li>
                        <li class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <strong class="text-[#382214] block mb-1">💻 นวดออฟฟิศซินโดรม</strong>
                            <p class="text-sm text-gray-600">เน้นเฉพาะจุด คอ บ่า ไหล่ เพื่อคนทำงานโดยเฉพาะ</p>
                        </li>
                        <li class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <strong class="text-[#382214] block mb-1">✨ ทรีตเมนต์สปาและขัดผิว</strong>
                            <p class="text-sm text-gray-600">ฟื้นฟูผิวให้เนียนนุ่มกระจ่างใสทั่วเรือนร่าง</p>
                        </li>
                    </ul>
                </div>

                <!-- Special Offers -->
                <div class="bg-[#FAF6F0] rounded-xl p-5">
                    <h3 class="font-bold text-[#382214] text-lg mb-2">สิทธิพิเศษช่วงเปิดตัวบน GoWabi</h3>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                        <li><strong>ส่วนลดพิเศษ:</strong> รับส่วนลดแพ็กเกจยอดนิยมสูงสุด 30% เมื่อจองผ่านแอป GoWabi</li>
                        <li><strong>Flash Sale & Cashback:</strong> รับเครดิตเงินคืนและโค้ดส่วนลดเพิ่มเติมตามแคมเปญประจำเดือน</li>
                    </ul>
                </div>

                <!-- How to Book -->
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-[#382214] pl-3">
                        ขั้นตอนการจองง่ายๆ ใน 3 สเต็ป
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div class="p-4 border rounded-xl">
                            <div class="w-10 h-10 bg-[#D2BFA6] text-[#382214] rounded-full flex items-center justify-center font-bold mx-auto mb-2">1</div>
                            <h4 class="font-semibold text-gray-800">ค้นหา</h4>
                            <p class="text-xs text-gray-500 mt-1">เปิดแอป GoWabi แล้วพิมพ์ค้นหาชื่อร้านของเรา</p>
                        </div>
                        <div class="p-4 border rounded-xl">
                            <div class="w-10 h-10 bg-[#D2BFA6] text-[#382214] rounded-full flex items-center justify-center font-bold mx-auto mb-2">2</div>
                            <h4 class="font-semibold text-gray-800">เลือกบริการ</h4>
                            <p class="text-xs text-gray-500 mt-1">เลือกแพ็กเกจ วัน และเวลาที่คุณสะดวก</p>
                        </div>
                        <div class="p-4 border rounded-xl">
                            <div class="w-10 h-10 bg-[#D2BFA6] text-[#382214] rounded-full flex items-center justify-center font-bold mx-auto mb-2">3</div>
                            <h4 class="font-semibold text-gray-800">ชำระเงิน</h4>
                            <p class="text-xs text-gray-500 mt-1">ยืนยันการชำระเงิน แล้วนำวอเชอร์มาแสดงหน้าร้าน</p>
                        </div>
                    </div>
                </div>

                <!-- Contact & Call to Action -->
                <div class="border-t pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600 space-y-1 text-center md:text-left">
                        <p><strong>สอบถามเพิ่มเติม:</strong> <?php echo $shop_phone; ?></p>
                        <p><strong>LINE Official:</strong> <?php echo $shop_line; ?></p>
                    </div>
                    <a href="https://www.gowabi.com/th/provider/baan-badin-spa" target="_blank" class="bg-[#D2BFA6] text-[#382214] font-semibold px-6 py-3 rounded-xl transition-all shadow-md hover:shadow-lg text-center w-full md:w-auto">
                        จองผ่าน GoWabi ได้เลยทันที →
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>