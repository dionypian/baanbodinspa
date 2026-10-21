# คู่มือแก้โค้ด PHP, JavaScript และหน้าตาเว็บ

ฉบับนี้เขียนสำหรับใช้ทำความเข้าใจและแก้โจทย์โค้ดทีละจุด ไม่จำเป็นต้องจำทุกบรรทัด ให้เริ่มจากรู้ว่าโค้ดส่วนไหนทำงานที่ไหน แล้วไล่ตามข้อมูลจากฟอร์มไปจนถึงหน้าจอหรือฐานข้อมูล

> เลขบรรทัดเป็นจุดเริ่มต้นจากไฟล์ปัจจุบัน อาจเปลี่ยนเมื่อมีการแก้โค้ด ให้ค้นหาชื่อตัวแปร ฟังก์ชัน หรือ `name` ของช่องฟอร์มควบคู่กัน

## 1. จำภาพนี้ก่อน: เว็บทำงานเป็นรอบ

```text
ผู้ใช้เห็นหน้าเว็บ
   ↓ กรอกฟอร์ม / กดปุ่ม
HTML ส่งข้อมูลด้วย GET หรือ POST
   ↓
PHP อ่านข้อมูล → ตรวจเงื่อนไข → อาจอ่าน/เขียนฐานข้อมูล
   ↓
PHP สร้าง HTML กลับมา หรือ redirect ไปหน้าอื่น
   ↓
JavaScript ช่วยโต้ตอบบนหน้า เช่น เปิด modal / ตรวจช่อง / ซ่อนแสดงข้อมูล
```

- **HTML** กำหนดโครงสร้าง เช่น หัวข้อ ปุ่ม ตาราง และฟอร์ม
- **CSS / Tailwind** กำหนดสี ระยะห่าง ขนาด และการจัดวาง
- **PHP** ทำงานบนเซิร์ฟเวอร์: รับคำขอ ตรวจข้อมูล ติดต่อ MySQL และสร้าง HTML
- **JavaScript** ทำงานในเบราว์เซอร์: ตอบสนองต่อการกด/เปลี่ยนค่า และปรับหน้าโดยไม่ต้องโหลดใหม่
- **MySQL** เก็บข้อมูลถาวร ส่วน `$_SESSION` ใช้จำข้อมูลของผู้ใช้ระหว่างหน้า/ขั้นตอน

โดยทั่วไป PHP ทำงานก่อนส่งหน้าไปยังเบราว์เซอร์ ส่วน JavaScript ทำงานหลังจากหน้าโหลดแล้ว ดังนั้น JavaScript เปลี่ยนค่าที่เห็นได้ แต่ไม่ได้เขียนข้อมูลลงฐานข้อมูลเอง เว้นแต่จะส่งคำขอกลับไปยัง PHP

## 2. ถ้าต้องแก้หน้าตา ให้เริ่มตรงไหน

### สี ขนาด ระยะห่าง

ในโปรเจกต์ใช้ Tailwind CSS เป็นหลัก โดยระบุ class บน HTML เช่น:

```html
<button class="bg-emerald-700 text-white px-4 py-2 rounded-lg">
    บันทึก
</button>
```

- เปลี่ยนสีพื้นหลัง: `bg-...`
- เปลี่ยนสีข้อความ: `text-...`
- ระยะห่างด้านใน: `p-...`, `px-...`, `py-...`
- ระยะห่างด้านนอก: `m-...`, `mt-...`, `mb-...`
- ความโค้ง: `rounded-...`
- ขนาดตัวอักษร: `text-sm`, `text-lg`, `text-2xl`
- ซ่อน/แสดง: `hidden`, `flex`, `block`

ตัวอย่างจริงดูได้ใน `config/index.php`, `config/booking.php` และไฟล์ส่วนประกอบใน `config/includes/` หากแก้สีหรือตำแหน่ง ให้แก้ class ที่ HTML ของส่วนนั้นก่อน ไม่จำเป็นต้องแก้ PHP ที่บันทึกข้อมูล

### เปลี่ยนข้อความ รูป หรือข้อมูลหน้า

- โลโก้/ภาพประกอบ: ไฟล์ใน [`config/image/`](./config/image/)
- เมนูที่แสดงหลายหน้า: [`config/includes/nav.php`](./config/includes/nav.php)
- Footer ที่แสดงหลายหน้า: [`config/includes/footer.php`](./config/includes/footer.php)
- ข้อมูลเมนูนวด: [`config/includes/data-massage.php`](./config/includes/data-massage.php)
- ข้อมูลเมนูสปา: [`config/includes/data-spa.php`](./config/includes/data-spa.php)
- หน้ารายละเอียดข่าวและโปรโมชัน: `config/new1.php`–`new3.php`, `config/promo1.php`–`promo2.php`

`data-massage.php` และ `data-spa.php` เป็น Array ที่นำไปวนแสดงผลภายหลัง ถ้าจะแก้ชื่อ ราคาเริ่มต้น หรือคำอธิบายหน้าเมนู ให้แก้ข้อมูลใน Array; ถ้าเป็นราคาจองจริงให้ตรวจ `allservices` และหน้าจัดการบริการด้วย เพราะข้อมูลหน้าแนะนำกับข้อมูลจองอาจเป็นคนละแหล่ง

## 3. ถ้าต้องแก้ Logic PHP ให้ไล่จากฟอร์ม

### คำศัพท์ที่ต้องรู้

```php
$_GET['filter_date']       // ค่าที่ส่งมากับ URL เช่น ?filter_date=...
$_POST['booking_date']     // ค่าที่ส่งจากฟอร์มแบบ POST
$_SESSION['booking']       // ข้อมูลที่จำข้ามหน้าใน session
$_FILES['payment_slip']    // ไฟล์ที่ส่งจากฟอร์มอัปโหลด
```

- `if (...) { ... }` เลือกทำงานเมื่อเงื่อนไขเป็นจริง
- `foreach ($items as $item) { ... }` ทำซ้ำกับข้อมูลทุกชิ้นใน Array
- `function name(...) { ... }` รวม Logic ที่เรียกใช้ซ้ำได้
- `require_once` / `include` นำไฟล์อื่นมาใช้
- `header("Location: ..."); exit;` เปลี่ยนหน้าและหยุดประมวลผล
- `->prepare()` เตรียม SQL; `->execute([...])` ส่งค่าลง SQL
- `beginTransaction()`, `commit()`, `rollBack()` ทำให้การเขียนหลายขั้นสำเร็จหรือย้อนกลับพร้อมกัน

### ตัวอย่างเส้นทางจากฟอร์มไป PHP

แบบฟอร์มจองลูกค้าอยู่ใน [`config/booking.php`](./config/booking.php) ช่วงประมาณบรรทัด 300–445:

```html
<form action="booking.php" method="POST">
    <input name="booking_date">
    <button type="submit">ถัดไป</button>
</form>
```

`action` คือไฟล์รับข้อมูล, `method="POST"` คือส่งค่าใน request body และ `name` คือกุญแจที่ PHP ใช้อ่าน เช่น `$_POST['booking_date']` ตรวจ handler ขั้นแรกใน `booking.php` ช่วงบรรทัด 35–60

ข้อมูลเลือกบริการใช้ชื่อแบบ Array เช่น `items[0][staff_id]` และ `items[0][service_ids][]` (เริ่มดู form ขั้นที่ 2 แถวบรรทัด 404 เป็นต้นไป) PHP จึงได้รับข้อมูลซ้อนกันหลายระดับใน `$_POST['items']`

### ตัวอย่าง SQL แบบปลอดภัยที่ใช้ในโปรเจกต์

```php
$stmt = $pdo->prepare(
    "UPDATE customers SET phone = :phone WHERE cus_id = :cus_id"
);
$stmt->execute([
    ':phone' => $phone,
    ':cus_id' => $cusId
]);
```

ค่า `:phone` และ `:cus_id` เป็น placeholder ไม่ควรนำข้อความจากฟอร์มไปต่อ SQL ตรง ๆ การเชื่อมต่อ PDO อยู่ใน [`config/db-connect.php`](./config/db-connect.php); ตัวอย่างการอ่านและอัปเดตข้อมูลลูกค้าอยู่ใน [`config/profile.php`](./config/profile.php) ช่วงบรรทัด 15–72

### เวลาแสดงค่าจาก PHP ใน HTML

```php
<?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?>
```

ใช้ `htmlspecialchars()` เมื่อแสดงข้อความที่มาจากฐานข้อมูลหรือผู้ใช้ เพื่อไม่ให้ข้อความถูกตีความเป็น HTML; รูปแบบนี้พบได้ทั่วไปในหน้าเว็บของโปรเจกต์

## 4. ถ้าต้องแก้ JavaScript ให้เริ่มตรงไหน

### หาเหตุการณ์กับฟังก์ชัน

```html
<button onclick="openModal('editModal')">แก้ไข</button>
```

`onclick` เรียก JavaScript เมื่อคลิก ปกติค้นหาชื่อฟังก์ชันนั้นในไฟล์เดียวกัน เช่น `openModal`, `openEditBookingModal`

```javascript
select.addEventListener('change', function () {
    // ทำงานเมื่อผู้ใช้เปลี่ยนค่าของ select
});
```

`addEventListener('change', ...)` ทำงานเมื่อค่าเปลี่ยน; `addEventListener('click', ...)` ทำงานเมื่อคลิก; `document.querySelector(...)` หา element; `classList.add/remove/toggle(...)` เปลี่ยน class เพื่อซ่อน/แสดงหรือเปลี่ยนหน้าตา

### จุด JavaScript ที่ควรรู้ในโปรเจกต์

| ความต้องการ | จุดเริ่มต้น |
|---|---|
| สลับแท็บ Dashboard | [`config/dashboard.php`](./config/dashboard.php): ฟังก์ชัน `switchTab()` แถว 410 เป็นต้นไป |
| เปิด/ปิด modal ทั่วไป | [`config/dashboard.php`](./config/dashboard.php): `openModal()` / `closeModal()` แถว 467 เป็นต้นไป |
| จัดการ modal จ่ายเงินและแสดงช่องคูปอง | [`config/queuemanage.php`](./config/queuemanage.php): `openCompleteModal()` / `handlePaymentMethodChange()` แถว 653 เป็นต้นไป |
| เลือก/จำกัดคูปองที่ใช้ได้ในขั้นจอง | [`config/booking.php`](./config/booking.php): script ท้ายไฟล์ แถว 798–850 |
| ตรวจไม่ให้เลือกหมอนวดซ้ำ | [`config/booking.php`](./config/booking.php): listener ของ `staffSelects` ใน script ท้ายไฟล์ |
| ตรวจทักษะของพนักงานกับบริการที่เลือก | [`config/booking.php`](./config/booking.php): `isStaffQualified()` ใน `x-data` แถว 236 เป็นต้นไป |
| เพิ่ม/แก้ไขรายชื่อพนักงานใน modal | [`config/staffmanage.php`](./config/staffmanage.php): ฟังก์ชันช่วงท้ายไฟล์ แถว 515 เป็นต้นไป |
| กรองคิวในตารางตามชื่อ/เวลา | [`config/dashboard.php`](./config/dashboard.php): `applyQueueFilters()` แถว 652 เป็นต้นไป |
| คำนวณ/เลือกบริการชั่วคราวใน modal จอง | [`config/booking.php`](./config/booking.php): modal เลือกบริการและ `x-data` แถว 635 เป็นต้นไป |

### Alpine.js ที่ใช้กับ `x-data`

บางส่วนใช้ Alpine.js ซึ่งเขียน Logic ไว้ใน HTML:

```html
<div x-data="{ open: false }">
    <button @click="open = !open">เปิด/ปิด</button>
    <div x-show="open">เนื้อหา</div>
</div>
```

- `x-data` สร้าง state/ตัวแปรให้ส่วน HTML นั้น
- `@click` ทำงานเมื่อคลิก (เขียนแทน `x-on:click`)
- `x-show` ซ่อนหรือแสดงตามค่า
- `:value` หรือ `x-bind:value` ผูกค่ากับ input

การจองใช้ Alpine ใน `booking.php` เพื่อเก็บบริการที่เลือก เปิด modal ตรวจทักษะพนักงาน และเรียก `submitBooking()`; ถ้าแก้ปุ่มแล้วไม่ทำงาน ให้ตรวจว่าชื่อ method ใน `@click` ตรงกับ method ใน `x-data` หรือไม่

## 5. JavaScript กับ PHP ทำงานร่วมกันอย่างไร

ตัวอย่างจากการจอง:

1. PHP อ่านข้อมูลบริการ/พนักงานจากฐานข้อมูล
2. PHP แปลง Array เป็น JSON ฝังลง `x-data` หรือ `<script>` ด้วย `json_encode()`
3. JavaScript/Alpine ใช้ข้อมูลนั้นแสดงตัวเลือกและตรวจเบื้องต้น
4. เมื่อ submit ฟอร์ม ค่า `name` ของ input ถูกส่งไป PHP
5. PHP ต้องตรวจค่าซ้ำอีกครั้ง แล้วจึงบันทึกฐานข้อมูล

สิ่งสำคัญ: **การตรวจใน JavaScript ช่วย UX แต่ไม่ใช่กฎบังคับที่ปลอดภัย** เพราะผู้ใช้สามารถปิด JavaScript หรือส่ง request เองได้ เงื่อนไขสำคัญ เช่น ราคา สิทธิ์ผู้ใช้ วันเวลา จำนวนบริการ ทักษะพนักงาน และคูปอง ต้องตรวจที่ PHP ก่อนเขียนฐานข้อมูลด้วย

เมื่อฝังข้อมูล PHP ลง JavaScript ให้ใช้ `json_encode()` และ flags สำหรับ escape ตามรูปแบบที่ไฟล์นั้นใช้ หลีกเลี่ยงการต่อ string จากข้อมูลผู้ใช้ลง JavaScript โดยตรง

## 6. ตัวอย่างโจทย์ที่อาจเจอ และวิธีไล่

### โจทย์: เปลี่ยนช่วงเวลาที่ลูกค้าเลือกจอง

1. แก้ตัวแปร `$opentime`, `$closetime` ใน `booking.php` แถว 24–27
2. แก้ช่วงสร้างตัวเลือกเวลาในฟอร์ม แถวประมาณ 328–340
3. เช็ก validation ฝั่ง PHP แถว 35–60
4. ตรวจเวลาสิ้นสุดบริการเทียบ 23:00 ที่ `booking.php` แถว 71–125 และ `queuemanage.php` ช่วงสร้างคิวแอดมิน
5. ถ้าเปลี่ยนเวลาเปิด/ปิดจริง ให้ค้นหาค่าเดิมใน `config/` เพื่อให้เส้นทางลูกค้าและแอดมินใช้กฎตรงกัน

### โจทย์: เปลี่ยนจำนวนบริการสูงสุดต่อคน

1. ค้น `count($service_ids) > 3` ใน `booking.php` ช่วง 71–125
2. เปลี่ยนจำนวนที่ตรวจ
3. ตรวจข้อจำกัดหรือข้อความเดียวกันใน JavaScript/หน้าสรุป
4. ทดสอบทั้งฝั่งฟอร์มและส่ง POST ที่มีจำนวนมากกว่ากำหนด เพราะ PHP ต้องเป็นตัวตัดสินจริง

### โจทย์: เปลี่ยนเงื่อนไขหมอนวดที่เลือกได้

1. ดูรายชื่อและเงื่อนไขแสดงตัวเลือกใน `booking.php` ขั้นที่ 2
2. ดู `isStaffQualified()` ใน `x-data` ว่าตรวจทักษะจาก `staffSkillsMap` อย่างไร
3. ดูการจัดจริงใน `auto_assign.php` โดยเฉพาะ `planSameDayStaffAssignments()` และ `isStaffAvailable()`
4. ถ้าเป็นกฎที่มีผลต่อการยืนยันจริง อย่าแก้เฉพาะ JavaScript; ตรวจ PHP และการจัดอัตโนมัติให้สอดคล้องกัน

### โจทย์: เปลี่ยนสิ่งที่เกิดเมื่อกดยืนยันการจอง

1. ปุ่มยืนยันเรียก `submitBooking()` ใน `booking.php` (ดู `x-data` แถว 236 เป็นต้นไป)
2. ฟอร์มซ่อนส่งไป `savebooking.php` แถวประมาณ 582
3. `savebooking.php` ตรวจข้อมูลและบันทึก `bookings`, `booking_items`, `payments` ใน Transaction
4. ถ้าบันทึกล้มเหลว ให้ดู `catch`, `rollBack()` และ redirect กลับขั้นที่ถูกต้อง

### โจทย์: เปลี่ยนข้อความหรือข้อมูลในหน้าเมนูนวด

1. ตรวจหน้าแสดงผล `massage.php`
2. ตรวจ Array ใน `includes/data-massage.php` ว่าฟิลด์ชื่ออะไร เช่น `title_th`, `start_price`, `image`, `description`
3. แก้ค่าของรายการที่ต้องการ โดยคงชื่อ key เดิมไว้
4. ถ้าต้องแก้หน้าตาการ์ด ให้แก้ HTML/Tailwind ใน `massage.php`; ถ้าแก้ราคาในระบบจอง ให้แก้รายการบริการในฐานข้อมูล/หน้า `manageservice.php` ด้วย

## 7. หลักการแก้ Logic แบบไม่หลง

ก่อนแก้ ให้ตอบ 4 คำถาม:

1. **เริ่มจากผู้ใช้ทำอะไร?** คลิกปุ่ม กรอกฟอร์ม หรือเปิด URL
2. **ข้อมูลส่งไปไหน?** ดู `action`, `method`, `name`, `onclick` หรือ `@click`
3. **กฎตัดสินใจอยู่ไหน?** ค้นชื่อ field/function หรือข้อความ error ใน PHP/JavaScript
4. **ผลลัพธ์เปลี่ยนตรงไหน?** HTML ที่แสดง, Session, SQL/ตาราง, หรือ URL redirect

หลังแก้ ให้ทดสอบ:

- กรณีข้อมูลถูกต้อง
- กรณีค่าว่าง/เกินขอบเขต/ไม่ตรงเงื่อนไข
- ผู้ใช้กดกลับหรือส่งซ้ำ
- สถานะก่อนและหลังในฐานข้อมูล (ถ้า Logic เขียนข้อมูล)
- หน้าและเส้นทางอื่นที่ใช้กฎเดียวกัน

ถ้าหน้าไม่เปลี่ยนตามที่แก้ ให้เช็กว่ากำลังแก้ไฟล์ที่ถูก include หรือไม่; ถ้าปุ่มไม่ทำงาน ให้เช็กชื่อฟังก์ชันและ `id`; ถ้าข้อมูลไม่เข้า PHP ให้เช็ก `name`, `method` และ `action`; ถ้าข้อมูลไม่เข้า DB ให้ดู SQL, parameter, Transaction และ error

## 8. คำศัพท์สั้น ๆ สำหรับทบทวน

| คำ | ความหมาย |
|---|---|
| Front-end | ส่วนหน้าเว็บที่ผู้ใช้เห็นและโต้ตอบ |
| Back-end | PHP และฐานข้อมูลที่ทำงานฝั่งเซิร์ฟเวอร์ |
| Request | คำขอจากเบราว์เซอร์ไปยัง PHP |
| Response | HTML/ข้อมูลที่ PHP ส่งกลับ |
| GET | ส่งค่าผ่าน URL มักใช้ค้นหา/กรองข้อมูล |
| POST | ส่งค่าจากฟอร์มเพื่อประมวลผล/บันทึก |
| Session | ข้อมูลชั่วคราวที่ผูกกับผู้ใช้ระหว่างหน้า |
| Validation | ตรวจว่าข้อมูลถูกต้องตามกฎหรือไม่ |
| Event | เหตุการณ์ เช่น click, change, submit |
| DOM | โครงสร้าง HTML ที่ JavaScript เข้าถึงและปรับได้ |
| State | ค่าปัจจุบันของ UI เช่น modal เปิดอยู่หรือไม่ |
| Transaction | ชุดคำสั่งฐานข้อมูลที่ commit พร้อมกันหรือ rollback |

## 9. สิ่งที่ไม่ควรแก้โดยไม่จำเป็น

- อย่าแก้ไฟล์ใน `vendor/` เพื่อเปลี่ยน Logic ของเว็บ ให้แก้ไฟล์ใน `config/`
- อย่าแก้เฉพาะ JavaScript ถ้ากฎนั้นต้องป้องกันการบันทึกข้อมูลผิด
- อย่าเปลี่ยน `name` ของ input โดยไม่แก้ชื่อที่ PHP อ่านใน `$_POST`
- อย่าเปลี่ยนชื่อคอลัมน์ SQL โดยไม่ตรวจว่าตาราง/ไฟล์อื่นใช้คอลัมน์นั้นด้วย
- อย่าลบ `commit()`, `rollBack()` หรือ `exit()` โดยไม่เข้าใจผล
- ค่ารหัสผ่าน/Client Secret และค่าตั้งค่าลับควรเก็บอย่างปลอดภัย ไม่ควรคัดลอกไปใส่เอกสารหรือส่งให้ผู้อื่น
