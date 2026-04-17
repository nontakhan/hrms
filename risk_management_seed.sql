SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `roles` (`id`, `role_code`, `role_name`)
VALUES
    (1, 'ADMIN', 'ผู้ดูแลระบบ'),
    (2, 'TEAM_LEAD', 'ทีมนำ'),
    (3, 'DEPARTMENT_HEAD', 'หัวหน้ากลุ่มงาน/หัวหน้างาน'),
    (4, 'DIRECTOR', 'ผู้อำนวยการ')
ON DUPLICATE KEY UPDATE
    `role_code` = VALUES(`role_code`),
    `role_name` = VALUES(`role_name`);

INSERT INTO `fiscal_years` (`id`, `year_label`, `year_short`, `date_start`, `date_end`, `is_active`)
VALUES
    (1, '2569', '69', '2025-10-01', '2026-09-30', 1)
ON DUPLICATE KEY UPDATE
    `year_label` = VALUES(`year_label`),
    `year_short` = VALUES(`year_short`),
    `date_start` = VALUES(`date_start`),
    `date_end` = VALUES(`date_end`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `departments` (`id`, `department_code`, `department_name`, `department_type`, `parent_department_id`, `is_nursing_group`, `is_active`)
VALUES
    (1, 'GEN', 'ไม่ระบุหน่วยงาน', 'general', NULL, 0, 1),
    (2, 'ER', 'ห้องฉุกเฉิน', 'clinical', NULL, 0, 1),
    (3, 'OPD', 'ผู้ป่วยนอก', 'clinical', NULL, 0, 1),
    (4, 'IPD', 'ผู้ป่วยใน', 'clinical', NULL, 0, 1),
    (5, 'NURSE', 'กลุ่มการพยาบาล', 'support', NULL, 1, 1),
    (6, 'PHARM', 'งานเภสัชกรรม', 'support', NULL, 0, 1)
ON DUPLICATE KEY UPDATE
    `department_name` = VALUES(`department_name`),
    `department_type` = VALUES(`department_type`),
    `parent_department_id` = VALUES(`parent_department_id`),
    `is_nursing_group` = VALUES(`is_nursing_group`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `teams` (`id`, `team_code`, `team_name`, `description`, `is_active`)
VALUES
    (1, 'PCT', 'ทีมนำ PCT', 'ทีมนำด้านการดูแลผู้ป่วย', 1),
    (2, 'IM', 'ทีมนำ IM', 'ทีมนำด้านข้อมูลและกระบวนการ', 1),
    (3, 'ENV', 'ทีมนำ ENV', 'ทีมนำด้านสิ่งแวดล้อมและความปลอดภัย', 1)
ON DUPLICATE KEY UPDATE
    `team_name` = VALUES(`team_name`),
    `description` = VALUES(`description`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role_id`, `department_id`, `team_id`, `head_level`, `is_active`)
VALUES
    (1, 'system', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'System User', 1, 1, NULL, NULL, 1),
    (2, 'admin', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'Administrator', 1, 1, NULL, NULL, 1),
    (3, 'team_pct', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'หัวหน้าทีมนำ PCT', 2, 3, 1, NULL, 1),
    (4, 'head_nurse', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'หัวหน้ากลุ่มการพยาบาล', 3, 5, NULL, 'group_head', 1),
    (5, 'director', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'ผู้อำนวยการโรงพยาบาล', 4, 1, NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
    `password_hash` = VALUES(`password_hash`),
    `full_name` = VALUES(`full_name`),
    `role_id` = VALUES(`role_id`),
    `department_id` = VALUES(`department_id`),
    `team_id` = VALUES(`team_id`),
    `head_level` = VALUES(`head_level`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
VALUES
    ('public_report_password_hash', '$2y$10$W9l1HE6yx1rS6UR3pvN8y.enXfQdM8Yr0JNCjm7Gv8Kj6U4vlNn4G', 'Default public report password hash', 2),
    ('active_fiscal_year_id', '1', 'Current active fiscal year', 2),
    ('system_user_id', '1', 'System user id for automated records', 2)
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `description` = VALUES(`description`),
    `updated_by` = VALUES(`updated_by`);

SET FOREIGN_KEY_CHECKS = 1;
