# HRMS2 Risk Management Roadmap

## Project Goal

พัฒนาระบบรายงานความเสี่ยงของ รพ.เทพา ด้วย PHP, MySQL, HTML, JS, TailwindCSS, DataTables, Chart.js และ SweetAlert

## Current Status

สถานะล่าสุด: เริ่มลงมือพัฒนาโครงระบบจริง

## Completed

- สรุป requirement และ flow การทำงาน
- ออกแบบ schema หลักของระบบ
- สร้างไฟล์ออกแบบระบบ `risk_management_design.md`
- สร้างไฟล์ schema เริ่มต้น `risk_management_schema.sql`
- สร้างโครงโปรเจกต์จริงชุดแรก (`config`, `app`, `partials`, `public`, `actions`, `storage`)
- สร้างหน้าเริ่มต้น, หน้า login, dashboard เริ่มต้น และ flow public report เบื้องต้น
- เพิ่ม role guard, logout, helper จัดการ settings และ query หน่วยงาน
- เพิ่มหน้า admin สำหรับตั้งค่า password กลาง
- เพิ่มไฟล์ seed เริ่มต้นของระบบ `risk_management_seed.sql`
- เพิ่มหน้ารายการรายงานของ admin พร้อม DataTables
- เพิ่มหน้ารายละเอียดรายงานของ admin พร้อมแก้ไขข้อมูลเบื้องต้นและส่งต่อไปยังทีมนำ
- เพิ่ม action สำหรับอัปเดตรายงานและสร้าง assignment พร้อมเลขรันแยกตามทีม/ปีงบ
- เพิ่มหน้ารายการงานของทีมนำ
- เพิ่มหน้ารายละเอียด assignment ของทีมนำ พร้อมฟอร์มบันทึกผลการพิจารณา
- เพิ่ม action สำหรับทีมนำในการส่งกลับ admin หรือส่งต่อหัวหน้ากลุ่มงาน/หัวหน้างาน
- เพิ่ม seed user ตัวอย่างสำหรับทีมนำ, หัวหน้ากลุ่มงาน และผู้อำนวยการ
- เพิ่มหน้ารายการงานของหัวหน้ากลุ่มงาน/หัวหน้างาน
- เพิ่มหน้ารายละเอียดงานของหัวหน้ากลุ่มงาน/หัวหน้างาน
- เพิ่ม action สำหรับบันทึกแนวทางแก้ไขและส่งคืนกลับทีมนำ

## In Progress

- วางเมนูและหน้าหลักสำหรับ role อื่น
- เตรียมฐานสำหรับ severity history และ route log บนหน้าจอ detail
- ทำ flow ของผู้อำนวยการและ dashboard สรุป

## Next

- สร้างหน้าจัดการทีมนำและหน่วยงาน
- เพิ่มฐาน layout สำหรับเมนูแยกตาม role
- เพิ่ม validation และข้อจำกัดชนิดไฟล์แนบ
- สร้าง dashboard สรุปสำหรับผู้อำนวยการ
- เพิ่ม timeline ของ severity history และ route log ในหน้ารายละเอียด

## Pending Milestones

- Milestone 1: Project foundation และ routing เบื้องต้น
- Milestone 2: Public reporting flow
- Milestone 3: Admin assignment flow
- Milestone 4: Team lead review flow
- Milestone 5: Department head review flow
- Milestone 6: Director dashboard และสรุปรายงาน
- Milestone 7: Hardening, validation, logs และ polish UI

## Notes

- ทุกครั้งที่มีการเปลี่ยนแปลงสำคัญ ให้ update ไฟล์นี้
- ถ้ามีการเปลี่ยน requirement ระหว่างทาง ให้เพิ่มไว้ในส่วน Notes หรือ Completed/Next ตามความเหมาะสม
- เริ่มสร้างโครงโปรเจกต์จริงแล้ว: มี `config`, `app`, `partials`, `public`, `actions`, `storage`
- มีหน้าเริ่มต้น `index.php`, หน้า login, หน้ายืนยัน password กลาง, และฟอร์มรายงานสาธารณะ
- password กลางถูกออกแบบให้ตรวจผ่าน `system_settings.public_report_password_hash`
- ฟอร์มรายงานเลือก `incident_department_id` จากฐานข้อมูลแล้ว
- `changed_by_user_id` ในการสร้าง history ครั้งแรกจะอิงจาก `system_settings.system_user_id`
- seed ปัจจุบันเตรียม user เริ่มต้น `admin` และ `system` โดยใช้ hash เดียวกับรหัสเริ่มต้นสำหรับตั้งระบบ
- ฝั่ง admin ตอนนี้เริ่มใช้งานได้ 3 ส่วน: รายการรายงาน, ดูรายละเอียด, ส่งต่อไปทีมนำ
- ปัจจุบันหน้า detail แสดง assignment ที่ส่งต่อแล้ว แต่ยังไม่ได้แสดง severity history และ route log แบบ timeline
- ฝั่งทีมนำตอนนี้รองรับการเลือก category ของทีม, แก้ระดับความรุนแรง, และตัดสินใจส่งกลับ admin หรือส่งต่อหัวหน้ากลุ่มงาน/หัวหน้างาน
- การจัดการ category ของทีมนำยังไม่มีหน้าจอหลังบ้าน ต้องทำใน milestone ถัดไป
- ฝั่งหัวหน้ากลุ่มงาน/หัวหน้างานตอนนี้รับงาน, บันทึกแนวทางแก้ไข, และส่งคืนกลับทีมนำได้แล้ว
