<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang'])) {
    $allowed_langs = ['th', 'en'];
    if (in_array($_GET['lang'], $allowed_langs)) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    $clean_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $clean_url);
    exit();
}

$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'th';

$flags = [
    'th' => 'https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/th.svg',
    'en' => 'https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/gb.svg' 
];

// ดึงชื่อไฟล์ปัจจุบัน (เช่น index.php, massage.php)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-[#D2BFA6] shadow-md sticky top-0 z-50">
    <div class="w-full px-8 md:px-12 flex justify-between items-center h-20">
        <a href="index.php" class="flex items-center justify-center">
            <img src="image/logo1.png" alt="Baan Bodin Spa Logo"
                class="h-12 w-auto object-contain transition-transform duration-300 ">
        </a>

        <div class="flex items-center space-x-6">
            
            <!-- 1. หน้าแรก -->
            <a href="index.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'index.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                หน้าแรก
            </a>

            <!-- 2. นวด -->
            <a href="massage.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'massage.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                นวด
            </a>

            <!-- 3. สปา -->
            <a href="spa.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'spa.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                สปา
            </a>

            <!-- 4. ข่าวสารและโปรโมชั่น -->
            <a href="infoandpromo.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'infoandpromo.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                ข่าวสารและโปรโมชั่น
            </a>

            <!-- 5. ติดต่อเรา -->
            <a href="contact.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'contact.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                ติดต่อเรา
            </a>

            <a href="login.php"
                class="bg-[#047857] text-[#FFFFFF] px-5 py-2 rounded font-medium hover:bg-[#FFF5E1] hover:text-[#382214] transition-all duration-300">
                BOOKING
            </a>
        </div>
    </div>
</nav>