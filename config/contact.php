<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-[#FAF6F0] min-h-screen flex flex-col">

    <?php include 'includes/nav.php'; ?>

    <section class="relative w-full h-[55vh] min-h-[380px] bg-cover bg-center flex items-center justify-center"
        style="background-image: url('image/heroc.png');">

        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-10 text-center text-[#FFF5E1] px-4 max-w-4xl mx-auto space-y-4">

            <h1 class="text-3xl md:text-5xl font-bold tracking-wide uppercase drop-shadow-md">
                contact us
            </h1>

            <p class="text-sm md:text-lg font-light opacity-90 max-w-2xl mx-auto leading-relaxed">
                สัมผัสประสบการณ์ผ่อนคลายแบบดั้งเดิมที่ บ้านบดินทรสปา
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-16 flex-grow w-full space-y-16">

        <!-- Section 1: Form & Map (แบบฟอร์มติดต่อ + แผนที่) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

            <!-- ฝั่งซ้าย: ข้อมูลที่อยู่ (กินพื้นที่ 5/12) -->
            <div class="space-y-4 lg:col-span-5">
                <div class="flex items-center space-x-3">
                    <img src="image/lotus.png" alt="Thai Flower Icon" class="h-8 w-8 object-contain shrink-0">
                    <h4 class="text-2xl font-bold text-[#382214]">ที่อยู่</h4>
                </div>
                <div class="space-y-3">
                    <h5 class="text-xl font-bold text-[#6d4c41]">
                        บ้านบดินทรสปา
                    </h5>
                    <p class="text-base text-gray-700 leading-relaxed font-normal">
                        เลขที่ 531/7 ซอยคุณประเสนีย์ ถนนอินทราภรณ์ <br class="hidden sm:inline">
                        แขวงพลับพลา เขตวังทองหลาง กรุงเทพมหานคร 10310.
                    </p>
                    <div class="text-gray-700 flex items-center justify-start space-x-2.5 pt-1">
                        <img src="image/time.png" alt="Time Icon" class="h-4 w-4 object-contain shrink-0">
                        <span class="text-sm md:text-base">เปิดบริการทุกวัน 10:00 - 23:00 น.</span>
                    </div>
                </div>
                <div class="pt-2">
                    <a href="spa.php"
                        class="inline-flex items-center space-x-2 bg-[#D2BFA6] text-[#382214] font-medium px-6 py-3 rounded hover:bg-[#FFF5E1] transition-all duration-300 shadow-lg hover:scale-105">
                        <img src="image/booking.png" alt="Booking Icon" class="h-5 w-5 object-contain">
                        <span>จองเลย</span>
                    </a>
                </div>
            </div>

            <!-- ฝั่งขวา: แผนที่ Google Maps (กินพื้นที่ 7/12 - กว้างขึ้นชัดเจน) -->
            <div
                class="w-full bg-white p-2 rounded-2xl shadow-sm border border-black/5 overflow-hidden h-[380px] lg:h-[450px] lg:col-span-7">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7750.274178345806!2d100.61111987596382!3d13.770605286623276!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29e0406ec1b59%3A0x197e442529dc6bb0!2z4Lia4LmJ4Liy4LiZ4Lia4LiU4Li04LiZ4LiX4Lij4Liq4Lib4Liy!5e0!3m2!1sth!2sth!4v1785832632141!5m2!1sth!2sth"
                    class="w-full h-full rounded-xl border-0" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

        </div>
        <br>
        <!-- Contact Cards (ช่องทางการติดต่อ) -->
        <div class="space-y-10">

            <div class="text-center space-y-2">
                <div class="flex items-center justify-center space-x-2">
                    <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
                </div>
                <h3 class="text-2xl font-bold text-[#382214]">ช่องทางการติดต่อ</h3>
            </div>

            <!-- 2. ส่วนการ์ดติดต่อ 4 ใบ (เรียง 4 คอลัมน์ด้านล่าง) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Facebook -->
                <div
                    class="group bg-white p-6 rounded-2xl shadow-sm border border-black/5 flex flex-col items-center text-center space-y-6 
                    transform transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-xl hover:border-[#D2BFA6] cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-full bg-[#FAF6F0] text-[#382214] flex items-center justify-center text-2xl 
                        transition-transform duration-300 ease-out group-hover:scale-110 group-hover:bg-emerald-50 group-hover:text-emerald-700">
                        <img src="image/facebook.png" alt="Facebook Icon"
                            class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-[#382214] text-lg transition-colors duration-300">
                            Facebook
                        </h3>
                        <p class="text-sm text-gray-800 leading-relaxed">
                            บ้านบดินทรสปา นวดเท้า นวดไทย
                        </p>
                    </div>
                </div>

                <!-- Line -->
                <div
                    class="group bg-white p-6 rounded-2xl shadow-sm border border-black/5 flex flex-col items-center text-center space-y-6 
                    transform transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-xl hover:border-[#D2BFA6] cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-full bg-[#FAF6F0] text-[#382214] flex items-center justify-center text-2xl 
                        transition-transform duration-300 ease-out group-hover:scale-110 group-hover:bg-emerald-50 group-hover:text-emerald-700">
                        <img src="image/line.png" alt="Line Icon"
                            class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-[#382214] text-lg transition-colors duration-300">
                            Line Official
                        </h3>
                        <p class="text-sm text-gray-800 leading-relaxed">
                            Add Line : @bodinspa
                        </p>
                    </div>
                </div>

                <!-- Phone -->
                <div
                    class="group bg-white p-6 rounded-2xl shadow-sm border border-black/5 flex flex-col items-center text-center space-y-6 
                    transform transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-xl hover:border-[#D2BFA6] cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-full bg-[#FAF6F0] text-[#382214] flex items-center justify-center text-2xl 
                        transition-transform duration-300 ease-out group-hover:scale-110 group-hover:bg-emerald-50 group-hover:text-emerald-700">
                        <img src="image/phone-call.png" alt="Phone Icon"
                            class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-[#382214] text-lg transition-colors duration-300">
                            Phone Number
                        </h3>
                        <p class="text-sm text-gray-800 leading-relaxed">
                            (+66) 81 970 9933
                        </p>
                    </div>
                </div>

                <!-- Email -->
                <div
                    class="group bg-white p-6 rounded-2xl shadow-sm border border-black/5 flex flex-col items-center text-center space-y-6 
                    transform transition-all duration-300 ease-out hover:-translate-y-2 hover:shadow-xl hover:border-[#D2BFA6] cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-full bg-[#FAF6F0] text-[#382214] flex items-center justify-center text-2xl 
                        transition-transform duration-300 ease-out group-hover:scale-110 group-hover:bg-emerald-50 group-hover:text-emerald-700">
                        <img src="image/email2.png" alt="Email Icon"
                            class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold text-[#382214] text-lg transition-colors duration-300">
                            Email
                        </h3>
                        <p class="text-xs text-gray-800 leading-relaxed break-all">
                            baanbodinspaandcarcare@gmail.com
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <section class="space-y-6 max-w-6xl mx-auto px-4 py-8">

        <div class="text-center space-y-2">
            <div class="flex items-center justify-center space-x-2">
                <img src="image/lotus.png" alt="Icon" class="h-8 w-8 object-contain">
            </div>
            <h3 class="text-2xl font-bold text-[#382214]">วิธีเดินทางมาที่ร้าน</h3>
        </div>


        <!-- ส่วนสไลด์รูปภาพ (Swiper Container) -->
        <div class="relative group max-w-4xl mx-auto">
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-black/5">
                <div class="swiper mySwiper rounded-2xl shadow-md border border-black/5 overflow-hidden">
                    <div class="swiper-wrapper">

                        <!-- รูปที่ 1 -->
                        <div class="swiper-slide">
                            <img src="image/location1.jpg" alt="วิธีเดินทาง 1"
                                class="w-full h-auto max-h-[450px] object-cover">
                        </div>

                        <!-- รูปที่ 2 -->
                        <div class="swiper-slide">
                            <img src="image/location2.jpg" alt="วิธีเดินทาง 2"
                                class="w-full h-auto max-h-[450px] object-cover">
                        </div>

                        <!-- รูปที่ 3 -->
                        <div class="swiper-slide">
                            <img src="image/location3.jpg" alt="วิธีเดินทาง 3"
                                class="w-full h-auto max-h-[450px] object-cover">
                        </div>

                        <div class="swiper-slide">
                            <img src="image/location4.jpg" alt="วิธีเดินทาง 4"
                                class="w-full h-auto max-h-[450px] object-cover">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="swiper-pagination !relative !bottom-0 pt-4"></div>

            <!-- ปุ่มกด ซ้าย - ขวา (แต่งสไตล์ให้อยู่ข้างสไลด์แบบรูปตัวอย่าง) -->
            <button
                class="swiper-button-prev-custom absolute -left-4 sm:-left-10 top-1/2 -translate-y-1/2 z-10 text-[#382214] transition-all p-1">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <button
                class="swiper-button-next-custom absolute -right-4 sm:-right-10 top-1/2 -translate-y-1/2 z-10 text-[#382214] transition-all p-1">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <style>
        /* แต่งสีจุดไข่ปลาให้เข้ากับโทนสีร้าน */
        .swiper-pagination-bullet-active {
            background-color: #382214 !important;
        }
    </style>

    <script>
        const swiper = new Swiper('.mySwiper', {
            loop: true,
            autoplay: {
                delay: 3500, // สไลด์เปลี่ยนเองทุกๆ 3.5 วินาที
                disableOnInteraction: false, // แม้ผู้ใช้จะกดเลื่อนเอง ก็ยังสไลด์ต่อให้อัตโนมัติ
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true, // สามารถคลิกจุดเพื่อเปลี่ยนรูปได้
            },
            navigation: {
                nextEl: '.swiper-button-next-custom',
                prevEl: '.swiper-button-prev-custom',
            },
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>