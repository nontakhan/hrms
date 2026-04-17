# ระบบรายงานความเสี่ยง รพ.เทพา

## สรุป Requirement

ระบบมีผู้ใช้งาน 5 กลุ่ม

1. ผู้ใช้ทั่วไป
- ไม่ต้อง login
- ต้องกรอก password กลางเพื่อเข้าใช้งานหน้ารายงาน
- password กลางใช้ตรวจสิทธิ์เข้าใช้งานหน้าจอรายงานเท่านั้น และไม่เก็บค่าที่กรอกลงฐานข้อมูลรายงาน

2. Admin ระบบ
- รับเรื่องจากผู้รายงาน
- ตรวจสอบและแก้ไขข้อมูลรายงาน
- แก้ไขระดับความรุนแรงได้
- ส่งต่อรายงานไปยังทีมนำได้หลายทีมในรายงานเดียว
- ตั้งค่าทีมนำ, กลุ่มงาน, หัวหน้ากลุ่มงาน, หัวหน้างาน, ปีงบประมาณ, password กลาง และข้อมูลหลังบ้านอื่น
- ปิดงานเมื่อทุก assignment ที่เกี่ยวข้องเสร็จสิ้นแล้ว

3. ทีมนำ
- รับรายงานจาก admin
- จัดประเภทความเสี่ยงของทีมตนเองได้แบบยืดหยุ่น
- แก้ไขระดับความรุนแรงได้
- ถ้าแก้เองได้ ให้บันทึกแนวทางแก้ไขและส่งกลับ admin
- ถ้าแก้เองไม่ได้ ให้ส่งต่อไปหัวหน้ากลุ่มงานหรือหัวหน้างานได้เพียง 1 ปลายทางต่อ assignment พร้อมระบุเหตุผล

4. หัวหน้ากลุ่มงาน / หัวหน้างาน
- รับรายงานจากทีมนำ
- บันทึกแนวทางแก้ไข
- ส่งคืนให้ทีมนำ
- กรณีกลุ่มงานการพยาบาล ถ้าส่งไปหัวหน้างาน หัวหน้ากลุ่มงานพยาบาลต้องเห็นรายงานนั้นด้วย

5. ผอ.
- ดูรายงานทั้งหมดได้
- ดูภาพรวม สรุป และ dashboard ได้
- ไม่มีสิทธิ์แก้ไขหรือส่งต่อ

## หลักการสำคัญของระบบ

- 1 รายงาน สามารถถูกส่งไปหลายทีมนำได้
- 1 รายงาน เมื่อถูกส่งไปหลายทีมนำ จะมีเลขรันแยกตามทีมนำ
- เลขรันของแต่ละทีมจะเริ่มใหม่ทุกปีงบประมาณ
- ถ้ารายงานเดียวกันถูกส่งไปหลายทีม งานของแต่ละทีมถือว่าแยกจากกัน
- สถานะภาพรวมของรายงานจะยังไม่ปิดจนกว่าทุก assignment ที่ถูกส่งต่อจะเสร็จ
- ทุกการส่งต่อจะต้องระบุเหตุผล
- ทุกการเปลี่ยนระดับความรุนแรงต้องเก็บประวัติ
- ผู้รายงานเลือกความรุนแรงตอนแรกตามประเภท Clinical หรือ Non-Clinical

## ระดับความรุนแรง

Clinical:
- A, B, C, D, E, F, G, H, I

Non-Clinical:
- 1, 2, 3, 4

ผู้รายงานต้องเลือกประเภทเหตุการณ์ก่อน แล้วจึงเลือกความรุนแรงในชุดที่ถูกต้อง

## การเก็บเวลาความล่าช้าในการรายงาน

ระบบต้องเก็บ:
- วันที่เวลาเกิดเหตุ
- วันที่เวลารายงาน
- เวลาห่างระหว่างเกิดเหตุถึงรายงาน

ข้อเสนอ:
- เก็บเป็น `report_delay_minutes`
- เวลาแสดงผลค่อย format เป็น วัน / ชั่วโมง / นาที

## Flow การทำงาน

1. ผู้ใช้ทั่วไปกรอก password กลางแล้วเข้าหน้ารายงาน
2. ผู้ใช้กรอกรายงานความเสี่ยงและบันทึกเข้าสู่ระบบ
3. Admin รับเรื่อง ตรวจสอบ และส่งต่อไปทีมนำได้หลายทีม
4. แต่ละทีมนำจะได้ assignment ของตนเอง พร้อมเลขรันของทีม เช่น `IM 1/69`
5. ทีมนำบันทึกประเภทความเสี่ยงและพิจารณาแนวทางแก้ไข
6. ถ้าแก้เองไม่ได้ ทีมนำส่งต่อไปหัวหน้ากลุ่มงานหรือหัวหน้างานได้ 1 ปลายทางต่อ assignment พร้อมระบุเหตุผล
7. หัวหน้ากลุ่มงานหรือหัวหน้างานแก้ไขและส่งคืนให้ทีมนำ
8. ทีมนำสรุปและส่งคืน admin
9. Admin ปิด assignment ของทีมนั้น
10. เมื่อทุก assignment เสร็จ ระบบจึงปิดรายงานหลักได้

## แนวคิดสิทธิ์ใช้งาน

เพื่อให้ code ดูแลง่ายในอนาคต เสนอให้ใช้ role กลางสำหรับผู้ใช้งานที่ login และแยกชนิดหัวหน้าด้วย field เพิ่มเติม

- ADMIN
- TEAM_LEAD
- DEPARTMENT_HEAD
- DIRECTOR

หมายเหตุ:
- ผู้ใช้ทั่วไปไม่ต้องมี account
- หัวหน้ากลุ่มงานกับหัวหน้างานใช้ role เดียวกันคือ `DEPARTMENT_HEAD`
- แยกชนิดด้วย `head_level` เช่น `group_head`, `unit_head`
- วิธีนี้จะทำให้เพิ่มกติกาในอนาคตได้ง่ายโดยไม่ต้องแตก role เยอะเกินจำเป็น

## ER Diagram (เชิงข้อความ)

```text
users
  -> role_id -> roles.id
  -> department_id -> departments.id
  -> team_id -> teams.id

departments
  -> parent_department_id -> departments.id

incident_reports
  -> reporter_department_id -> departments.id
  -> incident_department_id -> departments.id
  -> related_department_id -> departments.id
  -> incident_type_id -> incident_types.id
  -> reported_severity_id -> severity_levels.id
  -> current_severity_id -> severity_levels.id

incident_attachments
  -> report_id -> incident_reports.id

report_severity_histories
  -> report_id -> incident_reports.id
  -> old_severity_id -> severity_levels.id
  -> new_severity_id -> severity_levels.id
  -> changed_by_user_id -> users.id

report_assignments
  -> report_id -> incident_reports.id
  -> target_team_id -> teams.id
  -> from_user_id -> users.id
  -> target_head_user_id -> users.id
  -> fiscal_year_id -> fiscal_years.id

team_running_numbers
  -> team_id -> teams.id
  -> fiscal_year_id -> fiscal_years.id

risk_categories
  -> team_id -> teams.id
  -> parent_id -> risk_categories.id
  -> created_by -> users.id

team_reviews
  -> report_id -> incident_reports.id
  -> assignment_id -> report_assignments.id
  -> team_id -> teams.id
  -> selected_category_id -> risk_categories.id
  -> reviewed_by -> users.id

department_head_reviews
  -> report_id -> incident_reports.id
  -> assignment_id -> report_assignments.id
  -> department_id -> departments.id
  -> reviewed_by -> users.id

report_status_logs
  -> report_id -> incident_reports.id
  -> changed_by -> users.id

assignment_route_logs
  -> report_id -> incident_reports.id
  -> assignment_id -> report_assignments.id
  -> from_user_id -> users.id
  -> to_user_id -> users.id
  -> to_team_id -> teams.id
  -> to_department_id -> departments.id
```

## ตารางหลักที่เสนอ

### 1) roles
- เก็บชนิดผู้ใช้ที่ login

### 2) users
- เก็บผู้ใช้งานในระบบ

### 3) system_settings
- เก็บ password กลาง, ปีงบประมาณ active และ config สำคัญ

### 4) fiscal_years
- เก็บปีงบประมาณที่ admin ตั้งค่า

### 5) departments
- เก็บกลุ่มงาน / งาน / หน่วยงาน
- รองรับแบบ parent-child

### 6) teams
- เก็บทีมนำ

### 7) team_department_visibility
- ใช้กำหนดสิทธิ์มองเห็นเพิ่มเติม
- รองรับกรณีพยาบาลที่หัวหน้ากลุ่มงานต้องเห็นรายการที่ส่งไปหัวหน้างานในสายเดียวกัน

### 8) incident_types
- Clinical / Non-Clinical

### 9) severity_levels
- เก็บระดับความรุนแรงของแต่ละประเภท

### 10) incident_reports
- เก็บรายงานหลักจากผู้ใช้ทั่วไป

### 11) incident_attachments
- เก็บไฟล์แนบ

### 12) report_severity_histories
- เก็บประวัติการแก้ระดับความรุนแรงทุกครั้ง

### 13) report_assignments
- เก็บการส่งต่อแต่ละเส้นทาง
- 1 รายงานมีหลาย assignment ได้
- 1 assignment ผูกกับ 1 ทีมนำ

### 14) team_running_numbers
- เก็บเลขรันล่าสุดของแต่ละทีมต่อปีงบประมาณ

### 15) risk_categories
- เก็บประเภทความเสี่ยงของทีมนำแบบ tree
- ไม่ล็อกจำนวนชั้น แต่รองรับการใช้งานแบบยืดหยุ่น

### 16) team_reviews
- เก็บผลการพิจารณาของทีมนำ

### 17) department_head_reviews
- เก็บผลการดำเนินการของหัวหน้ากลุ่มงาน/หัวหน้างาน

### 18) assignment_route_logs
- เก็บเหตุผลและประวัติการส่งต่อทุกครั้ง

### 19) report_status_logs
- เก็บประวัติการเปลี่ยนสถานะของรายงานหลัก

### 20) audit_logs
- เก็บ log การใช้งานสำคัญ

## สถานะที่แนะนำ

สถานะรายงานหลัก (`incident_reports.status`)
- `pending`
- `admin_review`
- `in_progress`
- `completed`
- `cancelled`

สถานะ assignment (`report_assignments.assignment_status`)
- `sent_to_team`
- `team_in_progress`
- `sent_to_department_head`
- `department_head_in_progress`
- `returned_to_team`
- `returned_to_admin`
- `completed`
- `cancelled`

## หลักการปิดงาน

- Admin ปิด assignment ได้เมื่อทีมนำส่งกลับมาครบในเส้นงานนั้น
- รายงานหลักจะ `completed` ได้เมื่อทุก assignment ของรายงานนั้นอยู่สถานะ `completed`

## หมายเหตุด้าน implementation

- password กลางควรเก็บใน `system_settings` แบบ hash ไม่เก็บ plain text
- ตอนผู้ใช้ทั่วไปกรอก password กลาง ให้ verify แล้วค่อยเปิด form
- ไม่ต้องบันทึก password ที่ผู้ใช้กรอกในรายงาน
- ใช้ soft delete หรือ `is_active` กับข้อมูลตั้งค่า เช่น teams, departments, users
- ทุก query ต้องใช้ prepared statements
- หน้า dashboard ของ ผอ. ใช้ข้อมูลจากรายงานหลัก + assignment + severity history เพื่อสรุปภาพรวม
