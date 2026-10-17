<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและโปรโมชั่น - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes/nav.php'; ?>

    <!-- Hero Banner -->
    <section class="relative w-full h-[55vh] min-h-[380px] bg-cover bg-center flex items-center justify-center"
        style="background-image: url('image/heroi.png');">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 text-center text-[#FFF5E1] px-4 max-w-4xl mx-auto space-y-4">
            <h1 class="text-3xl md:text-5xl font-bold tracking-wide uppercase drop-shadow-md">
                News And Promotions
            </h1>
            <p class="text-sm md:text-lg font-light opacity-90 max-w-2xl mx-auto leading-relaxed">
                ติดตามรับข่าวสาร
                <span class="block mt-1"></span>
                อัปเดตโปรแกรมดูแลสุขภาพ พร้อมรับสิทธิพิเศษและข้อเสนอสุดเอ็กซ์คลูซีฟก่อนใคร
            </p>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="py-16 bg-[#FAF6F0] w-full">
        <div class="max-w-6xl mx-auto px-4 space-y-16">
            
            <!-- ================= 1. ส่วนข่าวสาร ================= -->
            <div>
                <div class="text-center mb-8 space-y-2">
                    <div class="flex items-center justify-center space-x-2">
                        <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
                    </div>
                    <h3 class="text-2xl font-bold text-[#382214]">ข่าวสาร</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- ข่าวที่ 1 -->
                    <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/info1.png" alt="News 1"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                ข่าวสารทั่วไป
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            สามารถจองบริการนวดและสปาที่ Gowabi ได้แล้วตอนนี้!
                        </h4>
                        <a href="new1.php"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>

                    <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/new2.png" alt="News 2"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                ข่าวสารทั่วไป
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            นั่งหน้าคอมนานๆ ทำให้เป็นออฟฟิศซินโดรมโดยไม่รู้ตัว!
                        </h4>
                        <a href="new2.php"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>

                    <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/icebath.jpg" alt="News 3"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                ข่าวสารทั่วไป
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            ฟื้นฟูร่างกายขั้นสุด!ด้วยนวดสปอร์ต และแช่น้ำแข็ง
                        </h4>
                        <a href="new3.php"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>
                </div>
            </div>

            <!-- ================= 2. ส่วนโปรโมชั่น ================= -->
            <div>
                <div class="text-center mb-8 space-y-2">
                    <div class="flex items-center justify-center space-x-2">
                        <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
                    </div>
                    <h3 class="text-2xl font-bold text-[#382214]">โปรโมชั่น</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- โปรโมชั่นที่ 1 -->
                    <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/promo.png" alt="Promo 1"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                โปรโมชั่น
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            โปรโมชั่นเล่มคูปอง
                        </h4>
                        <a href="promo1.php"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>

                    <!-- โปรโมชั่นที่ 2 -->
                    <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/promo2.png" alt="Promo 2"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                โปรโมชั่น
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            โปรโมชั่นสุดพิเศษสำหรับทองคำ
                        </h4>
                        <a href="promo2.php"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>
                </div>
            </div>

        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>

</html>