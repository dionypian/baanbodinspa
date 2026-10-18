<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ดึงชื่อไฟล์ปัจจุบัน
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-[#D2BFA6] shadow-md sticky top-0 z-50">
    <div class="w-full px-8 md:px-12 flex justify-between items-center h-20">
        
        <!-- โลโก้ร้าน (คลิกแล้วไปหน้าแรก index.php) -->
        <a class="flex items-center justify-center">
            <img src="image/logo1.png" alt="Baan Bodin Spa Logo"
                class="h-12 w-auto object-contain transition-transform duration-300 hover:scale-105">
        </a>

        <!-- เมนูด้านขวาสำหรับสมาชิก -->
        <div class="flex items-center space-x-6">
            
            <!-- 1. ปุ่มทำการจอง -->
            <a href="booking.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'booking.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                ทำการจอง
            </a>

            <!-- 2. ปุ่มประวัติการจอง -->
            <a href="bookdetail.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'bookdetail.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                ประวัติการจอง
            </a>

            <!-- 3. ปุ่มประวัติการจอง -->
            <a href="mycoupon.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'mycoupon.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                คูปองของฉัน
            </a>

            <!-- 4. ปุ่มโปรไฟล์ -->
            <a href="profile.php"
                class="relative text-[#4A3B32] font-medium py-1 after:content-[''] after:absolute after:h-[2px] after:bottom-0 after:left-1/2 after:transform after:-translate-x-1/2 after:bg-[#4A3B32] after:transition-all after:duration-300 hover:after:w-full 
                <?php echo ($current_page == 'profile.php') ? 'after:w-full font-bold' : 'after:w-0'; ?>">
                โปรไฟล์
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
            <!-- ผู้ใช้ล็อกอินแล้ว -->
            <div class="flex items-center gap-3">  
                <span class="text-sm font-medium text-gray-700">
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>

                <!-- ปุ่ม Logout -->
                <a href="logout.php" 
                   class="bg-[#8B0000] text-[#FFFFFF] px-5 py-2 rounded font-medium hover:bg-[#A52A2A] transition-all duration-300 text-sm shadow-sm">
                    ออกจากระบบ
                </a>
            </div>
        <?php else: ?>
            <!-- ผู้ใช้ยังไม่ได้ล็อกอิน -->
            <a href="login.php" 
               class="px-4 py-2 text-sm bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-md transition">
                เข้าสู่ระบบ
            </a>
        <?php endif; ?>
        </div>
    </div>
</nav>