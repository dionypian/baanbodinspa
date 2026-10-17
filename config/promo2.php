<?php
// ข้อมูลโปรโมชั่นทองคำ
$promotion_title = "Gold Treatment Special Promotion";
$promotion_subtitle = "ปรนนิบัติผิวสวยเปล่งประกาย ผ่อนคลายขั้นสุดด้วยแพ็กเกจทรีตเมนต์ทองคำ";
$publish_date = date("d/m/Y");

// รายการแพ็กเกจทองคำ
$gold_packages = [
    [
        "title" => "นวดอโรม่าทองคำ",
        "duration" => "1 ชั่วโมง",
        "original_price" => 800,
        "special_price" => 700,
        "highlight" => "ผ่อนคลายกล้ามเนื้อ ฟื้นฟูผิวเปล่งประกาย"
    ],
    [
        "title" => "นวดอโรม่าทองคำ",
        "duration" => "2 ชั่วโมง",
        "original_price" => 1500,
        "special_price" => 1200,
        "highlight" => "นวดเน้นผ่อนคลายลึกทั่วร่างกาย ยาวนานเต็มอิ่ม"
    ],
    [
        "title" => "ขัดผิว + อโรม่าทองคำ",
        "duration" => "2 ชั่วโมง",
        "original_price" => 1250,
        "special_price" => 1000,
        "highlight" => "ผลัดเซลล์ผิวเก่า พร้อมบำรุงด้วยออยล์ทองคำ"
    ],
    [
        "title" => "ขัดผิวทองคำ + อโรม่าทองคำ",
        "duration" => "2 ชั่วโมง",
        "original_price" => 1600,
        "special_price" => 1390,
        "highlight" => "ที่สุดแห่งการบำรุงผิวคู่ขนาน ดุจเจ้าหญิง"
    ]
];

$shop_phone = "081-970-9933";
$shop_line = "@bodinspa";
$gowabi_url = "https://www.gowabi.com"; // ใส่ URL ร้านใน GoWabi ของคุณ
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชั่น - <?php echo $promotion_title; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes\nav.php'; ?>

    <main class="flex-grow max-w-5xl mx-auto w-full px-4 py-8 md:py-12">
    <div class="max-w-4xl mx-auto">
        <!-- Header / Tag -->
        <div class="mb-6">
            <span class="bg-yellow-100 text-[#382214] text-xs font-semibold px-3 py-1 rounded-full uppercase">โปรโมชั่น</span>
            <span class="text-gray-400 text-sm ml-2">เผยแพร่เมื่อ: <?php echo $publish_date; ?></span>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Banner Section -->
            <div class="bg-gradient-to-r from-amber-600 via-yellow-600 to-amber-700 p-8 text-white relative">
                <div class="relative z-10">
                    <h1 class="text-2xl md:text-4xl font-extrabold leading-tight mb-3 text-[#382214]">
                        <?php echo $promotion_title; ?>
                    </h1>
                    <p class="text-[#382214] text-sm md:text-base font-light">
                        <?php echo $promotion_subtitle; ?>
                    </p>
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-6 md:p-8 space-y-8">
                <!-- Package Pricing Grid -->
                <div>
                    <h2 class="text-xl font-bold text-[#382214] mb-6 flex items-center gap-2">
                        <span></span> แพ็กเกจราคาสุดคุ้ม (Gold Treatment Packages)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($gold_packages as $pkg): ?>
                            <div class="bg-[#FAF6F0] border border-amber-500/20 rounded-2xl p-5 hover:border-amber-500/50 transition-all">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold text-lg text-[#382214]"><?php echo $pkg['title']; ?></h3>
                                        <span class="inline-block text-xs bg-amber-500/10 text-[#382214] px-2.5 py-0.5 rounded-md mt-1">
                                            ⏱️ <?php echo $pkg['duration']; ?>
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-400 line-through block">
                                            <?php echo number_format($pkg['original_price']); ?>.-
                                        </span>
                                        <span class="text-xl font-bold text-[#382214]">
                                            <?php echo number_format($pkg['special_price']); ?>.-
                                        </span>
                                    </div>
                                </div>
                                <p class="text-xs text-[#382214] mt-3 pt-3 border-t border-gray-800">
                                    <?php echo $pkg['highlight']; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Banner GoWabi Notification -->
                <div class="bg-[#FAF6F0] rounded-xl p-5 rounded-2xl p-5 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-[#382214] text-base mb-1">
                            สามารถจองได้แล้ววันนี้!
                        </h4>
                        <p class="text-xs text-[#382214]">
                            สามารถกดเลือกบริการ เช็กคิวว่าง และจองแพ็กเกจทองคำราคาพิเศษได้ที่เว็บไซต์ บ้านบดินทรสปา ตลอด 24 ชม.
                        </p>
                    </div>
                    <a href="login.php" target="_blank" class="bg-[#047857] text-[#FFFFFF] px-5 py-2 rounded font-medium hover:bg-[#FFF5E1] hover:text-[#382214] transition-all duration-300">
                        BOOKING
                    </a>
                </div>

                <!-- Footer Contact -->
                <div class="border-t border-gray-700/60 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-400">
                    <div>
                        <p><strong>เบอร์โทรศัพท์:</strong> <?php echo $shop_phone; ?></p>
                        <p><strong>LINE Official:</strong> <?php echo $shop_line; ?></p>
                    </div>
                    <p class="text-[#382214] italic">* เงื่อนไขเป็นไปตามที่ร้านกำหนด ขอสงวนสิทธิ์ในการเปลี่ยนแปลงโดยไม่ต้องแจ้งให้ทราบล่วงหน้า</p>
                </div>
            </div>
        </div>
    </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>