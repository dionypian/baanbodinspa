<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บ้านบดินทรสปา - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>
<style>
    body {
        font-family: 'Sarabun', sans-serif;
    }
</style>

<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes/nav.php'; ?>

    <section class="relative w-full h-[55vh] min-h-[380px] bg-cover bg-center flex items-center justify-center"
        style="background-image: url('image/hero.png');">

        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-10 text-center text-[#FFF5E1] px-4 max-w-4xl mx-auto space-y-4">

            <h1 class="text-3xl md:text-5xl font-bold tracking-wide uppercase drop-shadow-md">
                BAAN BODIN SPA
            </h1>

            <p class="text-sm md:text-lg font-light opacity-90 max-w-2xl mx-auto leading-relaxed">
                สัมผัสประสบการณ์ผ่อนคลายแบบดั้งเดิมที่ บ้านบดินทรสปา
            </p>

            <!-- ปุ่ม Call to Action -->
            <div class="pt-4">
                <a href="spa.php"
                    class="inline-flex items-center space-x-2 bg-[#D2BFA6] text-[#382214] font-medium px-6 py-3 rounded hover:bg-[#FFF5E1] transition-all duration-300 shadow-lg hover:scale-105">
                    <img src="image/booking.png" alt="Booking Icon" class="h-5 w-5 object-contain">
                    <span>จองเลย</span>
                </a>
            </div>
        </div>
    </section>

    <!-- paragraph1 -->
    <main class="max-w-6xl mx-auto px-4 py-12 flex-grow w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">

            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <img src="image/lotus.png" alt="Thai Flower Icon" class="h-8 w-8 object-contain shrink-0">
                    <h4 class="text-2xl font-bold text-[#382214]">เกี่ยวกับเรา</h4>
                </div>

                <div class="pl-16 text-[#4A3B32] text-sm md:text-base leading-relaxed space-y-4">
                    <p>
                        <strong class="font-bold text-[#382214]">"บ้านบดินทรสปา"</strong>&nbsp;
                        ตลอดระยะเวลากว่า 18 ปี
                        บ้านบดินทรสปาได้มุ่งมั่นให้บริการนวดแผนไทยแบบดั้งเดิมและสปาด้วยความพิถีพิถัน
                        พร้อมสืบสานศาสตร์แห่งการดูแลสุขภาพที่ผสานภูมิปัญญาไทยเข้ากับการบริการอันอบอุ่น
                        เพื่อมอบประสบการณ์แห่งการผ่อนคลายอย่างแท้จริง
                        และดูแลทั้งร่างกายและจิตใจในทุกช่วงเวลาของการเข้ารับบริการ เพราะเราเชื่อว่า...
                        <strong class="font-bold text-[#382214]">การดูแลที่ดีที่สุด
                            เริ่มต้นจากความใส่ใจในทุกสัมผัส</strong>
                    </p>
                </div>
            </div>

            <div class="relative flex justify-center md:justify-end">
                <div class="relative overflow-hidden rounded-tl-[80px] rounded-br-[80px] shadow-lg w-full max-w-md">
                    <img src="image/paragraph1.jpg" alt="paragraph1"
                        class="w-full h-[380px] md:h-[420px] object-cover hover:scale-105 transition-transform duration-500">
                </div>
            </div>

        </div>
    </main>

    <!-- paragraph2 -->
    <section class="py-16 bg-[#FAF6F0] w-full">
        <div class="max-w-6xl mx-auto px-4">

            <div class="text-center mb-12 space-y-2">
                <div class="flex items-center justify-center space-x-2">
                    <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
                </div>
                <h3 class="text-2xl font-bold text-[#382214]">บริการแนะนำของบ้านบดินทรสปา</h3>
            </div>

            <!-- Grid 3 การ์ดบริการ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-4">

                <div
                    class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-center flex flex-col items-center justify-between space-y-6 hover:-translate-y-3 hover:shadow-xl hover:border-2 hover:border-[#D2BFA6] transition-all duration-300">
                    <div class="space-y-4 flex flex-col items-center">
                        <img src="image/thaimassage.png" alt="Thai Massage" class="h-16 w-16 object-contain">
                        <h4 class="text-xl font-bold text-[#382214]">นวดไทยยาหม่อง</h4>
                        <p class="text-sm text-[#6C5F57] leading-relaxed">
                            ศาสตร์แห่งการนวดแผนไทยที่ผสานยาหม่องสมุนไพรไทย ช่วยคลายความตึงของกล้ามเนื้อ
                            บรรเทาอาการปวดเมื่อย และลดความอ่อนล้าจากการใช้ชีวิตประจำวัน
                        </p>
                    </div>
                    <a href="massage.php"
                        class="inline-flex items-center space-x-2 text-sm font-semibold text-[#A67C52] hover:text-[#8C6239] transition-colors pt-4">
                        <span>ดูรายละเอียด</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div
                    class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-center flex flex-col items-center justify-between space-y-6 hover:-translate-y-3 hover:shadow-xl hover:border-2 hover:border-[#D2BFA6] transition-all duration-300">
                    <div class="space-y-4 flex flex-col items-center">
                        <img src="image/aroma.png" alt="Aroma Oil Massage" class="h-16 w-16 object-contain">
                        <h4 class="text-xl font-bold text-[#382214]">นวดน้ำมันอโรม่า</h4>
                        <p class="text-sm text-[#6C5F57] leading-relaxed">
                            การนวดด้วยน้ำมันหอมระเหยจากดอกไม้คุณภาพ ผสานเทคนิคการนวดอย่างนุ่มนวล
                            ลดความเหนื่อยล้าของกล้ามเนื้อและเพิ่มความผ่อนคลายด้วยกลิ่นหอมของอโรม่า
                        </p>
                    </div>
                    <a href="spa.php"
                        class="inline-flex items-center space-x-2 text-sm font-semibold text-[#A67C52] hover:text-[#8C6239] transition-colors pt-4">
                        <span>ดูรายละเอียด</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div
                    class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-center flex flex-col items-center justify-between space-y-6 hover:-translate-y-3 hover:shadow-xl hover:border-2 hover:border-[#D2BFA6] transition-all duration-300">
                    <div class="space-y-4 flex flex-col items-center">
                        <img src="image/aromaspa.png" alt="aroma spa" class="h-16 w-16 object-contain">
                        <h4 class="text-xl font-bold text-[#382214]">คอร์ส Aroma Spa</h4>
                        <p class="text-sm text-[#6C5F57] leading-relaxed">
                            คอร์สสปาสุดพิเศษที่ออกแบบมาเพื่อให้ลูกค้าได้สัมผัสประสบการณ์ผ่อนคลายขั้นสุด
                            กลิ่นหอมจากอโรม่าธรรมชาติ ผสานเทคนิคการนวดอย่างนุ่มนวล
                        </p>
                    </div>
                    <a href="spa.php"
                        class="inline-flex items-center space-x-2 text-sm font-semibold text-[#A67C52] hover:text-[#8C6239] transition-colors pt-4">
                        <span>ดูรายละเอียด</span>
                        <span>&rarr;</span>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!--paragraph3-->
    <section class="py-16 bg-[#FAF6F0] w-full">
        <div class="max-w-6xl mx-auto px-4">

            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center space-x-2">
                    <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
                    <h3 class="text-2xl font-bold text-[#382214]">ข่าวสารและโปรโมชั่น</h3>
                </div>
                <!-- ปุ่มดูทั้งหมด -->
                <a href="infoandpromo.php"
                    class="text-sm font-bold text-[#A67C52] hover:text-[#8C6239] transition-colors flex items-center space-x-1">
                    <span>ดูทั้งหมด</span>
                    <span>&rarr;</span>
                </a>
            </div>

            <!-- Grid 3 การ์ดข่าวสาร -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <!-- ข่าวที่ 1 -->
                <article class="flex flex-col group cursor-pointer">
                    <!-- ส่วนรูปภาพ + ป้ายหมวดหมู่ -->
                    <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                        <img src="image/info1.png" alt="News 1"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <!-- ป้ายหมวดหมู่สีน้ำตาลทองมุมซ้ายล่าง -->
                        <span
                            class="absolute bottom-0 left-0 bg-[#5F7464] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                            ข่าวสารทั่วไป
                        </span>
                    </div>

                    <!-- หัวข้อข่าว -->
                    <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                        สามารถจองบริการนวดและสปาออนไลน์ได้ที่ Gowabi
                    </h4>

                    <!-- ลิงก์อ่านต่อ -->
                    <a href="news-detail.php?id=1"
                        class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                        อ่านต่อ
                    </a>
                </article>
                
                <!-- โปรโมชั่นที่ 1 -->
                <article class="flex flex-col group cursor-pointer">
                        <div class="relative overflow-hidden rounded-md aspect-[16/10] mb-4">
                            <img src="image/promo.png" alt="Promo 1"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span
                                class="absolute bottom-0 left-0 bg-[#047857] text-white text-xs font-semibold px-3 py-1.5 uppercase tracking-wider">
                                โปรโมชั่นเดือนนี้
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-[#382214] transition-colors leading-snug mb-2 line-clamp-2">
                            โปรโมชั่นเล่มคูปอง
                        </h4>
                        <a href="news-detail.php?id=1"
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
                        <a href="news-detail.php?id=1"
                            class="text-ml font-bold text-[#A67C52] underline underline-offset-4 hover:text-[#8C6239] transition-colors mt-auto inline-block">
                            อ่านต่อ
                        </a>
                    </article>
            </div>
        </div>
    </section>

    <!-- paragraph4 -->
    <section class="py-16 bg-[#FAF6F0] w-full overflow-hidden">
        <div class="max-w-6xl mx-auto px-4">

            <div class="space-y-6">
                <!-- หัวข้อ -->
                <div class="flex items-center space-x-3">
                    <img src="image/lotus.png" alt="Thai Flower Icon" class="h-8 w-8 object-contain shrink-0">
                    <h4 class="text-2xl font-bold text-[#382214]">รีวิวจากลูกค้าของเรา</h4>
                </div>

                <!-- ส่วนรีวิว Slider Container -->
                <div class="px-2 md:px-6">

                    <!-- 💡 ครอบ relative เฉพาะส่วนที่มีการ์ด Swiper และปุ่มลูกศร -->
                    <div class="relative group">

                        <!-- ปุ่มเลื่อนซ้าย -->
                        <button
                            class="swiper-button-prev-custom absolute -left-4 sm:-left-10 top-1/2 -translate-y-1/2 z-10 text-[#382214] transition-all p-1">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <button
                            class="swiper-button-next-custom absolute -right-4 sm:-right-10 top-1/2 -translate-y-1/2 z-10 ext-[#382214] transition-all p-1">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Swiper Container -->
                        <div class="swiper reviewSwiper py-4">
                            <div class="swiper-wrapper">

                                <!-- การ์ดที่ 1 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        T
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">TaRi
                                                            TiRa
                                                        </h4>
                                                        <span class="text-xs text-gray-400">2 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                ใช้บริการมา 3 ปีแล้วเพราะว่าอยู่ใกล้บ้าน และบริการดีมีหมอนวดเยอะมาก
                                                หมอนวดมือเก่งๆทั้งนั้น ราคายุติธรรม ไม่ถูก ไม่แพง
                                                เป็นราคาที่จับต้องได้ทุกคน
                                                มีร้านคาร์แคร์อยู่ในบริเวณเดียวกันด้วยเป็นเจ้าของเดียวกัน
                                                ที่นี่มีที่จอดรถอยู่นิดหน่อย ตั้งอยู่ใกล้ makro food
                                                และใกล้โรงเรียนบดินทรเดชา เดินทะลุมานิดเดียวก็ถึง
                                                ผู้ปกครองมาพักผ่อนรอลูกที่นี่ได้เลย
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 2 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        N
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">Nene
                                                            Panyawong</h4>
                                                        <span class="text-xs text-gray-400">ปีที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                หมอนวดดีฝีมือดีมาก ต้องจำชื่อพี่เค้าไว้เลยในลิสต์ เลือกนวดไทยยาหม่อง
                                                จ่ายในราคา 300 บาท 🥹 ปังเกินค่ะ บริการ 5 ดาว มีเครื่องดื่มร้อน น้ำเย็น
                                                และผลไม้ สายนวดต้องมาลองค่ะ ห้องรวมก็มี หรือจะห้องส่วนตัวก็จองได้ …
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 3 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        A
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                                            Anongsri
                                                            Boon</h4>
                                                        <span class="text-xs text-gray-400">11 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                นวดดีมากค่ะ ร้านสะอาด เตียงไม่ได้นุ่มมากนะ แค่การตกแต่งให้ห้อง
                                                ด้วยโทนสีและลายต่างๆอาจจะแลดูเก่าๆไปหน่อย
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 4 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        G
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                                            Girlster
                                                            Survey</h4>
                                                        <span class="text-xs text-gray-400">5 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                มาขัดผิวสครับไวน์แดง กับนวดอโรม่าทองคำค่ะ ต้อนรับดีมากๆ สครับผิวแล้วนุ่ม
                                                และนวดผ่อนคลาย สบายๆ
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 5 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        N
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                                            namwaan s.
                                                        </h4>
                                                        <span class="text-xs text-gray-400">10 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                Great Thai massage with a relaxing atmosphere. The therapist was
                                                professional and very attentive. I felt refreshed, relaxed, and
                                                energized
                                                after the session. Highly recommend this place!
                                                นวดไทยดีมากกกก บรรยากาศดี หมอนวดมืออาชีพสุดๆ ใส่ใจมากๆค่ะ
                                                หลังนวดรู้สึกสบายตัว แนะนำร้านนี้เลยค่ะะ 💗✨
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 6 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        P
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                                            Patchara
                                                            Somprasert</h4>
                                                        <span class="text-xs text-gray-400">10 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                หมอนวดดีมาก พูดให้ความรู้ดี ว่าปวดตรงนี้ต้องกดคลายแบบไหน เจ็บนิด
                                                แต่นวดเสร็จคลายมาก มานวดอโรม่าประจำ มีคูปองเป็นเล่มคุ้มมาก แถมเยอะไปหมด
                                                แนะนำ พี่กี พี่นันค่ะ
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- การ์ดที่ 7 -->
                                <div class="swiper-slide h-auto">
                                    <div
                                        class="bg-[#FFFFFF] p-5 rounded-2xl flex flex-col justify-between h-full space-y-4 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 ease-out cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center space-x-3">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-[#718894] text-white font-bold flex items-center justify-center text-sm">
                                                        T
                                                    </div>
                                                    <div>
                                                        <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                                            Thunchanok
                                                            Sinsap</h4>
                                                        <span class="text-xs text-gray-400">8 เดือนที่แล้ว</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1.5 mb-2">
                                                <div class="flex text-amber-400 text-lg space-x-0.5">★ ★ ★ ★ ★</div>
                                            </div>

                                            <p class="text-xs text-gray-700 leading-relaxed">
                                                พอดีเจอใน ตต แล้วก็หาเพจต่อ เอ้าาา ร้านมีทั้งล้างรถ และนวด
                                                เลยแวะเข้ามาค่ะ
                                                รีวิวเรื่องล้างรถดีนะสะอาด กลิ่นหอมเลย ราคาไม่แรง ตามที่เขาแจ้งในเพจ
                                                ส่วนนวดเราสปาเท้า แล้วนวดเท้าตรงจุดอยู่ค่ะ คลายปวดช่วงขาได้เลย
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ส่วน Google Rating -->
                    <div class="flex flex-col items-center justify-center text-center space-y-2 pt-6">

                        <div class="flex space-x-1.5 text-amber-400 text-3xl justify-center">
                            <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                        </div>

                        <p class="text-xs font-semibold text-gray-700">
                            Based on <strong class="text-black">310 reviews</strong>
                        </p>

                        <div class="pt-1">
                            <span class="text-6xl font-bold tracking-tight">
                                <span class="text-[#4285F4]">G</span><span class="text-[#EA4335]">o</span><span
                                    class="text-[#FBBC05]">o</span><span class="text-[#4285F4]">g</span><span
                                    class="text-[#34A853]">l</span><span class="text-[#EA4335]">e</span>
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const swiper = new Swiper('.reviewSwiper', {
                slidesPerView: 1, // มือถือแสดง 1 การ์ด
                spaceBetween: 16, // ระยะห่างระหว่างการ์ด
                loop: true,       // สไลด์วนลูปไม่สิ้นสุด

                // ตั้งค่าการเลื่อนอัตโนมัติ
                autoplay: {
                    delay: 3500, // สไลด์ทุกๆ 3.5 วินาที
                    disableOnInteraction: false, // เมื่อกดปุ่มแล้วก็ยังเลื่อนต่อได้
                    pauseOnMouseEnter: true, // เมื่อเอาเมาส์ไปชี้การ์ด สไลเดอร์จะหยุดพักชั่วคราว
                },

                // ปุ่มควบคุม Left / Right
                navigation: {
                    nextEl: '.review-next',
                    prevEl: '.review-prev',
                },

                // Responsive ปรับจำนวนการ์ดตามความกว้างหน้าจอ
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                        spaceBetween: 16,
                    },
                    1024: {
                        slidesPerView: 3,
                        spaceBetween: 20,
                    },
                },
            });
        });
    </script>
</body>

<?php include 'includes/footer.php'; ?>

</html>