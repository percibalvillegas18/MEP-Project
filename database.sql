-- MEP Projects Portal — Version 007.4 clean installer
-- Target engine: MySQL 8.0+ / MariaDB 10.4+
-- WARNING: this script intentionally removes application tables.

CREATE DATABASE IF NOT EXISTS `mep_database`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mep_database`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `rbac_outbox`;
DROP TABLE IF EXISTS `project_role_assignments`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `workplan_photos`;
DROP TABLE IF EXISTS `file_cleanup_queue`;
DROP TABLE IF EXISTS `workplan`;
DROP TABLE IF EXISTS `workflow_status_history`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `procurement`;
DROP TABLE IF EXISTS `submittals`;
DROP TABLE IF EXISTS `project_progress`;
DROP TABLE IF EXISTS `project_members`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `disciplines`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(180) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(40) NOT NULL DEFAULT 'user',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `auth_version` int unsigned NOT NULL DEFAULT 1,
  `last_logout` datetime DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `disciplines` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `dis_name` varchar(100) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#2563EB',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_disciplines_name` (`dis_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempts` (
  `client_key` char(64) NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_attempt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` binary(32) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requested_by` int unsigned DEFAULT NULL,
  `requester_ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_hash` (`token_hash`),
  KEY `idx_password_reset_user_active` (`user_id`,`used_at`,`expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_password_reset_actor` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schema_migrations` (
  `version` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL,
  `checksum` char(64) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT, `code` varchar(64) NOT NULL, `name` varchar(100) NOT NULL,
  `scope` enum('system','project') NOT NULL DEFAULT 'project', `description` varchar(500) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1, `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), `updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`), UNIQUE KEY `uq_roles_code` (`code`), KEY `idx_roles_scope_active` (`scope`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT, `code` varchar(100) NOT NULL, `module` varchar(64) NOT NULL, `action` varchar(64) NOT NULL,
  `description` varchar(500) DEFAULT NULL, `active` tinyint(1) NOT NULL DEFAULT 1, `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`), UNIQUE KEY `uq_permissions_code` (`code`), KEY `idx_permissions_module_active` (`module`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id` int unsigned NOT NULL, `permission_id` int unsigned NOT NULL, `granted_by` int unsigned DEFAULT NULL,
  `granted_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), PRIMARY KEY (`role_id`,`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_actor` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL DEFAULT '',
  `client` varchar(255) NOT NULL DEFAULT '',
  `general_contractor` varchar(255) NOT NULL DEFAULT '',
  `consultant` varchar(255) NOT NULL DEFAULT '',
  `project_manager` varchar(180) NOT NULL DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_status` (`status`),
  CONSTRAINT `fk_projects_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_projects_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_members` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `project_role` varchar(40) NOT NULL DEFAULT 'Viewer',
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_by` int unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_member` (`project_id`,`user_id`),
  KEY `idx_project_members_user` (`user_id`),
  CONSTRAINT `fk_project_members_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_members_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_role_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT, `project_id` int unsigned NOT NULL, `user_id` int unsigned NOT NULL, `role_id` int unsigned NOT NULL,
  `assigned_by` int unsigned NOT NULL, `assigned_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), `effective_from` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `effective_until` datetime(6) DEFAULT NULL, `active` tinyint(1) NOT NULL DEFAULT 1, `reason` varchar(500) NOT NULL,
  `revoked_by` int unsigned DEFAULT NULL, `revoked_at` datetime(6) DEFAULT NULL, `revoked_reason` varchar(500) DEFAULT NULL, `version` int unsigned NOT NULL DEFAULT 1,
  `active_user_id` int unsigned GENERATED ALWAYS AS (CASE WHEN `active`=1 THEN `user_id` ELSE NULL END) STORED,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), `updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`), UNIQUE KEY `uq_project_active_user` (`project_id`,`active_user_id`),
  KEY `idx_assignments_user_active_dates` (`user_id`,`active`,`effective_from`,`effective_until`), KEY `idx_assignments_project_role_active` (`project_id`,`role_id`,`active`),
  CONSTRAINT `fk_assignments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CHECK (`effective_until` IS NULL OR `effective_until`>`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rbac_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT, `event_uuid` char(16) NOT NULL, `event_type` varchar(80) NOT NULL, `aggregate_id` bigint unsigned NOT NULL,
  `payload` json NOT NULL, `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending', `attempts` smallint unsigned NOT NULL DEFAULT 0, `next_attempt_at` datetime(6) DEFAULT CURRENT_TIMESTAMP(6), `last_error` varchar(500) DEFAULT NULL, `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), `processed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_outbox_event_uuid` (`event_uuid`), KEY `idx_outbox_pending` (`processed_at`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `discipline` varchar(100) NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'Medium',
  `item_type` varchar(30) NOT NULL DEFAULT 'Measurable Item',
  `activity_weight` decimal(8,2) NOT NULL DEFAULT 1.00,
  `measurement_profile` varchar(60) NOT NULL DEFAULT 'Manual',
  `planned_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `percentage_complete` decimal(5,2) NOT NULL DEFAULT 0.00,
  `boq_no` varchar(120) NOT NULL DEFAULT '',
  `task` varchar(255) NOT NULL,
  `material_description` text,
  `material_quantity` decimal(12,2) DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT '',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `completion_date_source` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `start_date_source` varchar(40) NOT NULL DEFAULT 'Not Set',
  `start_source_reference` varchar(255) NOT NULL DEFAULT '',
  `installation_start_date` date DEFAULT NULL,
  `end_date_source` varchar(40) NOT NULL DEFAULT 'Not Set',
  `status` varchar(80) NOT NULL DEFAULT 'Not Started',
  `notes` text,
  `remarks` text,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_progress_project_boq` (`project_id`,`boq_no`),
  KEY `idx_progress_project_discipline` (`project_id`,`discipline`),
  CONSTRAINT `fk_progress_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_progress_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `submittals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `progress_id` int unsigned DEFAULT NULL,
  `discipline` varchar(100) NOT NULL DEFAULT '',
  `boq_ref_no` varchar(120) NOT NULL DEFAULT '',
  `submittal_reference` varchar(180) NOT NULL DEFAULT '',
  `material_description` text,
  `manufacturer` varchar(180) NOT NULL DEFAULT '',
  `country_origin` varchar(120) NOT NULL DEFAULT '',
  `submittal_revision_no` varchar(50) NOT NULL DEFAULT '',
  `submitted_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `status` enum('A','B','C','D','UR','P') NOT NULL DEFAULT 'P',
  `mas_file_link` varchar(1000) NOT NULL DEFAULT '',
  `consultant_comments` text,
  `notes` text,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_submittals_project_boq` (`project_id`,`boq_ref_no`),
  KEY `idx_submittals_progress` (`progress_id`),
  KEY `idx_submittals_status` (`project_id`,`status`),
  CONSTRAINT `fk_submittals_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submittals_progress` FOREIGN KEY (`progress_id`) REFERENCES `project_progress` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_submittals_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_submittals_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `procurement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `submittal_reference_id` int unsigned DEFAULT NULL,
  `material_description` text NOT NULL,
  `manufacturer` varchar(180) NOT NULL DEFAULT '',
  `approved_date` date DEFAULT NULL,
  `po_date` date DEFAULT NULL,
  `required_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'SAR',
  `boq_ref_no` varchar(120) NOT NULL DEFAULT '',
  `supplier` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(80) NOT NULL DEFAULT 'Not Started',
  `remarks` text,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_procurement_project_boq` (`project_id`,`boq_ref_no`),
  KEY `idx_procurement_expected_delivery` (`expected_delivery_date`),
  CONSTRAINT `fk_procurement_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_procurement_submittal` FOREIGN KEY (`submittal_reference_id`) REFERENCES `submittals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_procurement_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_procurement_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(180) NOT NULL DEFAULT '',
  `phone` varchar(60) NOT NULL DEFAULT '',
  `whatsapp` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `website` varchar(500) NOT NULL DEFAULT '',
  `category` varchar(120) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL DEFAULT '',
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_company` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workflow_status_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `project_id` int unsigned NOT NULL,
  `status_type` varchar(60) NOT NULL,
  `old_status` varchar(100) DEFAULT NULL,
  `new_status` varchar(100) NOT NULL,
  `remarks` text,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workflow_project` (`project_id`,`changed_at`),
  CONSTRAINT `fk_workflow_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workflow_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workplan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `responsible_user_id` int unsigned DEFAULT NULL,
  `progress_id` int unsigned DEFAULT NULL,
  `discipline` varchar(100) NOT NULL DEFAULT '',
  `boq_no` varchar(120) NOT NULL DEFAULT '',
  `mas_submittal_id` int unsigned DEFAULT NULL,
  `work_plan_stage` varchar(80) NOT NULL DEFAULT 'First Fix',
  `work_status_image_before` varchar(255) NOT NULL DEFAULT '',
  `work_status_image_before_checksum` char(64) DEFAULT NULL,
  `work_status_image_after` varchar(255) NOT NULL DEFAULT '',
  `installed_quantity` decimal(12,2) DEFAULT NULL,
  `planned_start` date DEFAULT NULL,
  `planned_finish` date DEFAULT NULL,
  `actual_start` date DEFAULT NULL,
  `actual_finish` date DEFAULT NULL,
  `work_plan_status` varchar(40) NOT NULL DEFAULT 'Work Pending',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remarks` text,
  `created_by` int unsigned DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workplan_project_boq` (`project_id`,`boq_no`),
  CONSTRAINT `fk_workplan_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workplan_responsible` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_workplan_progress` FOREIGN KEY (`progress_id`) REFERENCES `project_progress` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_workplan_mas` FOREIGN KEY (`mas_submittal_id`) REFERENCES `submittals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_workplan_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_workplan_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `workplan_photos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `workplan_id` int unsigned NOT NULL,
  `photo_type` varchar(20) NOT NULL DEFAULT 'after',
  `file_name` varchar(255) NOT NULL,
  `checksum` char(64) DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT 0,
  `uploaded_by` int unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_workplan_photos_workplan` (`workplan_id`,`sort_order`),
  CONSTRAINT `fk_workplan_photos_workplan` FOREIGN KEY (`workplan_id`) REFERENCES `workplan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workplan_photos_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `file_cleanup_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `relative_path` varchar(500) NOT NULL,
  `checksum` char(64) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `idempotency_key` char(64) NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `attempts` smallint unsigned NOT NULL DEFAULT 0,
  `last_error` varchar(500) DEFAULT NULL,
  `next_attempt_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_file_cleanup_idempotency` (`idempotency_key`),
  KEY `idx_file_cleanup_pending` (`completed_at`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `project_id` int unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `module` varchar(80) NOT NULL,
  `record_id` int unsigned DEFAULT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `event_uuid` char(16) DEFAULT NULL,
  `request_id` char(16) DEFAULT NULL,
  `event_type` varchar(80) DEFAULT NULL,
  `before_state` json DEFAULT NULL,
  `after_state` json DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `previous_hash` binary(32) DEFAULT NULL,
  `event_hash` binary(32) DEFAULT NULL,
  `occurred_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_project_created` (`project_id`,`created_at`),
  KEY `idx_audit_module` (`module`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disciplines` (`dis_name`,`color`) VALUES
  ('HVAC','#2563EB'),
  ('Electrical','#F59E0B'),
  ('Plumbing','#06B6D4'),
  ('Fire Fighting','#EF4444');

INSERT INTO `roles` (`code`,`name`,`scope`,`description`) VALUES
('project_manager','Project Manager','project','Manages project membership and all project modules'),
('project_engineer','Project Engineer','project','Manages engineering delivery and project records'),
('mep_engineer','MEP Engineer','project','Manages MEP technical and site records'),
('coordinator','Coordinator','project','Coordinates submittals, procurement, work plans and evidence'),
('viewer','Viewer','project','Read-only project access');

INSERT INTO `permissions` (`code`,`module`,`action`,`description`) VALUES
('project.view','project','view','View the project and its records'),('project.edit','project','edit','Edit project-owned records'),('dashboard.view','dashboard','view','View project dashboards'),
('boq.view','boq','view','View BOQ measurable items'),('boq.edit','boq','edit','Create and edit BOQ items'),('progress.update','progress','update','Update measurable-item progress'),
('submittal.view','submittal','view','View material submittals'),('submittal.create_edit','submittal','create_edit','Create and edit submittals'),
('procurement.view','procurement','view','View procurement records'),('procurement.create_edit','procurement','create_edit','Create and edit procurement records'),
('workplan.view','workplan','view','View work plans'),('workplan.create_edit','workplan','create_edit','Create and edit work plans'),('evidence.upload','evidence','upload','Upload work-plan evidence'),
('report.export','report','export','Export project reports'),('assignment.manage','assignment','manage','Assign, change and revoke project roles');

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r JOIN permissions p ON r.code='project_manager' OR
(r.code='project_engineer' AND p.code IN ('project.view','project.edit','dashboard.view','boq.view','boq.edit','progress.update','submittal.view','submittal.create_edit','procurement.view','procurement.create_edit','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='mep_engineer' AND p.code IN ('project.view','project.edit','dashboard.view','boq.view','boq.edit','progress.update','submittal.view','submittal.create_edit','procurement.view','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='coordinator' AND p.code IN ('project.view','dashboard.view','boq.view','submittal.view','submittal.create_edit','procurement.view','procurement.create_edit','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='viewer' AND p.code IN ('project.view','dashboard.view','boq.view','submittal.view','procurement.view','workplan.view','report.export'));

INSERT INTO `schema_migrations` (`version`,`description`) VALUES
('003_001','Project RBAC, audit evidence, secure reset tokens and reference integrity'),
('006_001','Evidence checksums, cleanup recovery and stable BOQ linkage'),
('007_001','Conversion readiness: deterministic progress, ETags, currency and queue workers');
