SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `report_status_logs`;
DROP TABLE IF EXISTS `assignment_route_logs`;
DROP TABLE IF EXISTS `department_head_reviews`;
DROP TABLE IF EXISTS `team_reviews`;
DROP TABLE IF EXISTS `risk_categories`;
DROP TABLE IF EXISTS `team_running_numbers`;
DROP TABLE IF EXISTS `report_assignments`;
DROP TABLE IF EXISTS `report_severity_histories`;
DROP TABLE IF EXISTS `incident_attachments`;
DROP TABLE IF EXISTS `incident_reports`;
DROP TABLE IF EXISTS `severity_levels`;
DROP TABLE IF EXISTS `incident_types`;
DROP TABLE IF EXISTS `team_department_visibility`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `fiscal_years`;
DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(50) NOT NULL,
  `role_name` varchar(150) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fiscal_years` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `year_label` varchar(10) NOT NULL,
  `year_short` varchar(10) NOT NULL,
  `date_start` date NOT NULL,
  `date_end` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year_label` (`year_label`),
  UNIQUE KEY `uk_year_short` (`year_short`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(50) DEFAULT NULL,
  `department_name` varchar(255) NOT NULL,
  `department_type` varchar(50) DEFAULT NULL,
  `parent_department_id` int unsigned DEFAULT NULL,
  `is_nursing_group` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_department_code` (`department_code`),
  KEY `idx_department_parent` (`parent_department_id`),
  CONSTRAINT `fk_departments_parent` FOREIGN KEY (`parent_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teams` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `team_code` varchar(20) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_code` (`team_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role_id` int unsigned NOT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `team_id` int unsigned DEFAULT NULL,
  `head_level` enum('group_head','unit_head') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_department` (`department_id`),
  KEY `idx_users_team` (`team_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`),
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `team_department_visibility` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int unsigned NOT NULL,
  `department_id` int unsigned NOT NULL,
  `viewer_user_id` int unsigned NOT NULL,
  `visibility_type` enum('direct','supervisor') NOT NULL DEFAULT 'direct',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_department_viewer` (`team_id`,`department_id`,`viewer_user_id`),
  CONSTRAINT `fk_tdv_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tdv_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tdv_viewer` FOREIGN KEY (`viewer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) NOT NULL,
  `type_name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_incident_type_code` (`type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `severity_levels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `incident_type_id` int unsigned NOT NULL,
  `level_code` varchar(10) NOT NULL,
  `level_name` varchar(150) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_level` (`incident_type_id`,`level_code`),
  CONSTRAINT `fk_severity_levels_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_no` varchar(30) DEFAULT NULL,
  `reporter_name` varchar(255) DEFAULT NULL,
  `reporter_phone` varchar(50) DEFAULT NULL,
  `reporter_department_id` int unsigned DEFAULT NULL,
  `incident_department_id` int unsigned NOT NULL,
  `related_department_id` int unsigned DEFAULT NULL,
  `incident_type_id` int unsigned NOT NULL,
  `reported_severity_id` int unsigned NOT NULL,
  `current_severity_id` int unsigned NOT NULL,
  `incident_title` varchar(255) NOT NULL,
  `incident_detail` text NOT NULL,
  `initial_action` text DEFAULT NULL,
  `incident_datetime` datetime NOT NULL,
  `reported_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `report_delay_minutes` int unsigned DEFAULT NULL,
  `status` enum('pending','admin_review','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_report_no` (`report_no`),
  KEY `idx_ir_status` (`status`),
  KEY `idx_ir_incident_department` (`incident_department_id`),
  KEY `idx_ir_related_department` (`related_department_id`),
  KEY `idx_ir_type` (`incident_type_id`),
  KEY `idx_ir_current_severity` (`current_severity_id`),
  KEY `idx_ir_reported_severity` (`reported_severity_id`),
  CONSTRAINT `fk_ir_reporter_department` FOREIGN KEY (`reporter_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_incident_department` FOREIGN KEY (`incident_department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_related_department` FOREIGN KEY (`related_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_type` FOREIGN KEY (`incident_type_id`) REFERENCES `incident_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_reported_severity` FOREIGN KEY (`reported_severity_id`) REFERENCES `severity_levels` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ir_current_severity` FOREIGN KEY (`current_severity_id`) REFERENCES `severity_levels` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `incident_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ia_report` (`report_id`),
  CONSTRAINT `fk_ia_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `report_severity_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `old_severity_id` int unsigned DEFAULT NULL,
  `new_severity_id` int unsigned NOT NULL,
  `changed_by_user_id` int unsigned NOT NULL,
  `changed_role_code` varchar(50) NOT NULL,
  `change_reason` varchar(500) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rsh_report` (`report_id`),
  KEY `idx_rsh_changed_by` (`changed_by_user_id`),
  CONSTRAINT `fk_rsh_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rsh_old_severity` FOREIGN KEY (`old_severity_id`) REFERENCES `severity_levels` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rsh_new_severity` FOREIGN KEY (`new_severity_id`) REFERENCES `severity_levels` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rsh_changed_by` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `report_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `target_team_id` int unsigned NOT NULL,
  `target_head_user_id` int unsigned DEFAULT NULL,
  `assignment_no` varchar(30) DEFAULT NULL,
  `fiscal_year_id` int unsigned DEFAULT NULL,
  `running_no` int unsigned DEFAULT NULL,
  `from_user_id` int unsigned NOT NULL,
  `sent_reason` varchar(500) NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `received_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `assignment_status` enum('sent_to_team','team_in_progress','sent_to_department_head','department_head_in_progress','returned_to_team','returned_to_admin','completed','cancelled') NOT NULL DEFAULT 'sent_to_team',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_assignment_no` (`assignment_no`),
  KEY `idx_ra_report` (`report_id`),
  KEY `idx_ra_team` (`target_team_id`),
  KEY `idx_ra_head_user` (`target_head_user_id`),
  KEY `idx_ra_fiscal_year` (`fiscal_year_id`),
  KEY `idx_ra_status` (`assignment_status`),
  CONSTRAINT `fk_ra_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_team` FOREIGN KEY (`target_team_id`) REFERENCES `teams` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_head_user` FOREIGN KEY (`target_head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_fiscal_year` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ra_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `team_running_numbers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int unsigned NOT NULL,
  `fiscal_year_id` int unsigned NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_team_fiscal_year` (`team_id`,`fiscal_year_id`),
  CONSTRAINT `fk_trn_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trn_fiscal_year` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `risk_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rc_team` (`team_id`),
  KEY `idx_rc_parent` (`parent_id`),
  CONSTRAINT `fk_rc_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rc_parent` FOREIGN KEY (`parent_id`) REFERENCES `risk_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rc_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `team_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `team_id` int unsigned NOT NULL,
  `selected_category_id` bigint unsigned DEFAULT NULL,
  `problem_analysis` text DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `preventive_action` text DEFAULT NULL,
  `decision_type` enum('resolved_by_team','forward_to_department_head') NOT NULL,
  `decision_reason` varchar(500) DEFAULT NULL,
  `reviewed_by` int unsigned NOT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tr_report` (`report_id`),
  KEY `idx_tr_assignment` (`assignment_id`),
  KEY `idx_tr_team` (`team_id`),
  KEY `idx_tr_category` (`selected_category_id`),
  CONSTRAINT `fk_tr_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `report_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_category` FOREIGN KEY (`selected_category_id`) REFERENCES `risk_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `department_head_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `department_id` int unsigned NOT NULL,
  `review_action` text NOT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_by` int unsigned NOT NULL,
  `reviewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `returned_to_team_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dhr_report` (`report_id`),
  KEY `idx_dhr_assignment` (`assignment_id`),
  KEY `idx_dhr_department` (`department_id`),
  CONSTRAINT `fk_dhr_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dhr_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `report_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dhr_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dhr_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `assignment_route_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `assignment_id` bigint unsigned NOT NULL,
  `from_user_id` int unsigned NOT NULL,
  `to_user_id` int unsigned DEFAULT NULL,
  `to_team_id` int unsigned DEFAULT NULL,
  `to_department_id` int unsigned DEFAULT NULL,
  `route_action` enum('admin_to_team','team_to_department_head','department_head_to_team','team_to_admin','redirect','cancel') NOT NULL,
  `route_reason` varchar(500) NOT NULL,
  `route_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_arl_report` (`report_id`),
  KEY `idx_arl_assignment` (`assignment_id`),
  CONSTRAINT `fk_arl_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_arl_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `report_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_arl_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_arl_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_arl_to_team` FOREIGN KEY (`to_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_arl_to_department` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `report_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int unsigned NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rsl_report` (`report_id`),
  CONSTRAINT `fk_rsl_report` FOREIGN KEY (`report_id`) REFERENCES `incident_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rsl_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` varchar(100) NOT NULL,
  `detail_json` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`role_code`, `role_name`) VALUES
('ADMIN', 'ผู้ดูแลระบบ'),
('TEAM_LEAD', 'ทีมนำ'),
('DEPARTMENT_HEAD', 'หัวหน้ากลุ่มงานหรือหัวหน้างาน'),
('DIRECTOR', 'ผู้อำนวยการ');

INSERT INTO `incident_types` (`type_code`, `type_name`) VALUES
('CLINICAL', 'ความเสี่ยงทางคลินิก'),
('NON_CLINICAL', 'ความเสี่ยงไม่ใช่ทางคลินิก');

INSERT INTO `severity_levels` (`incident_type_id`, `level_code`, `level_name`, `sort_order`)
SELECT it.id, s.level_code, s.level_name, s.sort_order
FROM `incident_types` it
JOIN (
  SELECT 'CLINICAL' AS type_code, 'A' AS level_code, 'ระดับ A' AS level_name, 1 AS sort_order UNION ALL
  SELECT 'CLINICAL', 'B', 'ระดับ B', 2 UNION ALL
  SELECT 'CLINICAL', 'C', 'ระดับ C', 3 UNION ALL
  SELECT 'CLINICAL', 'D', 'ระดับ D', 4 UNION ALL
  SELECT 'CLINICAL', 'E', 'ระดับ E', 5 UNION ALL
  SELECT 'CLINICAL', 'F', 'ระดับ F', 6 UNION ALL
  SELECT 'CLINICAL', 'G', 'ระดับ G', 7 UNION ALL
  SELECT 'CLINICAL', 'H', 'ระดับ H', 8 UNION ALL
  SELECT 'CLINICAL', 'I', 'ระดับ I', 9 UNION ALL
  SELECT 'NON_CLINICAL', '1', 'ระดับ 1', 1 UNION ALL
  SELECT 'NON_CLINICAL', '2', 'ระดับ 2', 2 UNION ALL
  SELECT 'NON_CLINICAL', '3', 'ระดับ 3', 3 UNION ALL
  SELECT 'NON_CLINICAL', '4', 'ระดับ 4', 4
) s ON s.type_code = it.type_code;

SET FOREIGN_KEY_CHECKS = 1;
