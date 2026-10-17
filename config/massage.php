<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการนวด - Baanbodinspa</title>
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
    <?php require_once 'includes/data-massage.php'; ?>

    <!-- Banner Section -->
    <section class="relative w-full h-[55vh] min-h-[380px] bg-cover bg-center flex items-center justify-center"
        style="background-image: url('image/herom.png');">

        <div class="absolute inset-0 bg-black/40"></div>

        <div class="relative z-10 text-center text-[#FFF5E1] px-4 max-w-4xl mx-auto space-y-4">
            <h1 class="text-3xl md:text-5xl font-bold tracking-wide uppercase drop-shadow-md">
                Traditional Thai Massage
            </h1>

            <p class="text-sm md:text-lg font-light opacity-90 max-w-4xl mx-auto leading-relaxed">
                ศาสตร์แห่งการนวดที่สืบทอดจากภูมิปัญญาไทยมาอย่างยาวนาน <br>ไม่เพียงเป็นศาสตร์แห่งการบำบัด
                แต่ยังเป็นศิลปะแห่งการดูแลสุขภาพที่มุ่งเน้นการฟื้นฟูทั้งร่างกายและจิตใจ
            </p>
        </div>
    </section>

    <main class="max-w-6xl mx-auto px-4 pt-10 pb-16 flex-grow w-full space-y-16">

        <!-- ================= SECTION 1: รายการนวดปกติ ================= -->
        <section class="space-y-6">
            <div class="flex items-center space-x-3">
                <img src="image/lotus.png" alt="Thai Flower Icon" class="h-8 w-8 object-contain shrink-0">
                <h4 class="text-2xl font-bold text-[#382214]">รายการนวด</h4>
            </div>

            <!-- Grid Layout การ์ดเมนูนวด -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($massage_menu as $item): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col transition-all duration-300 ease-out hover:shadow-2xl hover:-translate-y-2 border border-black/5">
                        <div class="relative h-64 bg-cover bg-center flex items-center justify-center p-4" style="background-image: url('<?= $item['image']; ?>');">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative z-10 text-center text-white">
                                <h3 class="text-xl md:text-2xl font-sarabun font-bold uppercase tracking-wider mb-2">
                                    <?= $item['title_en']; ?>
                                </h3>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col justify-between flex-grow space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xl font-bold text-[#382214]"><?= $item['title_th']; ?></h4>
                                <p class="text-emerald-700 font-bold text-xl shrink-0"><?= $item['start_price']; ?></p>
                            </div>

                            <p class="text-sm text-gray-500">ระยะเวลา : <?= $item['duration']; ?></p>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button onclick="openModal('modal-<?= $item['id']; ?>')" class="w-full py-2 px-3 text-center text-sm font-semibold text-white bg-emerald-700 border border-emerald-700 rounded-md hover:bg-emerald-800 transition-colors">
                                    More Details
                                </button>
                                <a href="login.php" class="w-full py-2 px-3 text-center text-sm font-semibold text-white bg-emerald-700 border border-emerald-700 rounded-md hover:bg-emerald-800 transition-colors">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>


        <!-- SECTION 2: คอร์ส (Course) -->
        <?php if (!empty($course_menu)): ?>
        <section class="space-y-6">
            <div class="flex items-center space-x-3">
                <img src="image/lotus.png" alt="Thai Flower Icon" class="h-8 w-8 object-contain shrink-0">
                <h4 class="text-2xl font-bold text-[#382214]">คอร์สพิเศษ</h4>
            </div>

            <!-- Grid Layout การ์ดคอร์ส (ใช้โครงสร้างเดิม) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($course_menu as $item): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col transition-all duration-300 ease-out hover:shadow-2xl hover:-translate-y-2 border border-black/5">
                        <div class="relative h-64 bg-cover bg-center flex items-center justify-center p-4" style="background-image: url('<?= $item['image']; ?>');">
                            <div class="absolute inset-0 bg-black/40"></div>
                            <div class="relative z-10 text-center text-white">
                                <h3 class="text-xl md:text-2xl font-sarabun font-bold uppercase tracking-wider mb-2">
                                    <?= $item['title_en']; ?>
                                </h3>
                            </div>
                        </div>

                        <div class="p-6 flex flex-col justify-between flex-grow space-y-2">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xl font-bold text-[#382214]"><?= $item['title_th']; ?></h4>
                                <p class="text-emerald-700 font-bold text-xl shrink-0"><?= $item['start_price']; ?></p>
                            </div>

                            <p class="text-sm text-gray-500">ระยะเวลา : <?= $item['duration']; ?></p>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button onclick="openModal('modal-<?= $item['id']; ?>')" class="w-full py-2 px-3 text-center text-sm font-semibold text-[#FFFFFF] bg-emerald-700 border border-emerald-700 rounded-md hover:bg-emerald-800 transition-colors">
                                    More Details
                                </button>
                                <a href="login.php" class="w-full py-2 px-3 text-center text-sm font-semibold text-white bg-emerald-700 border border-emerald-700 rounded-md hover:bg-emerald-800 transition-colors">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <!-- MODALS SECTION -->
    <?php 
        $all_items = array_merge($massage_menu ?? [], $course_menu ?? []);
        foreach ($all_items as $item): 
    ?>
        <div id="modal-<?= $item['id']; ?>"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 ease-out">

            <div id="modal-content-<?= $item['id']; ?>"
                class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden p-6 md:p-8 transform scale-95 translate-y-4 transition-all duration-300 ease-out my-8 max-h-[90vh] overflow-y-auto">

                <button onclick="closeModal('modal-<?= $item['id']; ?>')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold z-10 p-2 transition-colors">
                    &times;
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div class="w-full h-52 md:h-64 rounded-xl overflow-hidden bg-black shadow-inner">
                        <img src="<?= $item['image']; ?>" alt="<?= $item['title_th']; ?>" class="w-full h-full object-cover">
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-2xl font-bold text-[#4A3B32]"><?= $item['title_th']; ?></h3>

                        <div class="space-y-2 text-sm text-gray-700 font-medium">
                            <?php foreach ($item['prices'] as $p): ?>
                                <div class="flex justify-between items-center pb-1 border-b border-gray-100">
                                    <span><?= $p['time']; ?> - <?= $p['price']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p class="text-xs md:text-sm text-gray-600 leading-relaxed pt-1">
                            <?= $item['description']; ?>
                        </p>
                    </div>
                </div>

                <div class="mt-8 text-center pt-2">
                    <a href="login.php" class="inline-block py-3 px-8 text-center text-base font-semibold text-white bg-emerald-700 border border-emerald-700 rounded-md hover:bg-emerald-800 transition-colors">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include 'includes/footer.php'; ?>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const modalContent = document.getElementById(modalId.replace('modal-', 'modal-content-'));

            if (modal) {
                modal.classList.remove('opacity-0', 'pointer-events-none');
                modal.classList.add('opacity-100');

                if (modalContent) {
                    modalContent.classList.remove('scale-95', 'translate-y-4');
                    modalContent.classList.add('scale-100', 'translate-y-0');
                }

                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const modalContent = document.getElementById(modalId.replace('modal-', 'modal-content-'));

            if (modal) {
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0', 'pointer-events-none');

                if (modalContent) {
                    modalContent.classList.remove('scale-100', 'translate-y-0');
                    modalContent.classList.add('scale-95', 'translate-y-4');
                }

                document.body.style.overflow = 'auto';
            }
        }

        window.onclick = function (event) {
            if (event.target.classList.contains('fixed')) {
                closeModal(event.target.id);
            }
        }
    </script>
</body>

</html>