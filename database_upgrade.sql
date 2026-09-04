-- MEP Projects Portal Stage 2 Version 003
-- Upgrade an existing legacy database without deleting operational data.
-- Back up mep_database before importing this file in phpMyAdmin.

USE `mep_database`;
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Idempotent compatibility repairs for databases that were only partly upgraded.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `auth_version` int unsigned NOT NULL DEFAULT 1 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `last_logout` datetime DEFAULT NULL AFTER `auth_version`;

ALTER TABLE `project_progress`
  ADD COLUMN IF NOT EXISTS `completion_date_source` enum('auto','manual') NOT NULL DEFAULT 'auto' AFTER `actual_end_date`;

ALTER TABLE `procurement`
  ADD COLUMN IF NOT EXISTS `currency` char(3) NOT NULL DEFAULT 'SAR' AFTER `actual_delivery_date`;

ALTER TABLE `procurement`
  ADD COLUMN IF NOT EXISTS `manufacturer` varchar(180) NOT NULL DEFAULT '' AFTER `material_description`,
  ADD COLUMN IF NOT EXISTS `po_date` date DEFAULT NULL AFTER `approved_date`,
  ADD COLUMN IF NOT EXISTS `required_date` date DEFAULT NULL AFTER `po_date`,
  ADD COLUMN IF NOT EXISTS `expected_delivery_date` date DEFAULT NULL AFTER `required_date`,
  ADD COLUMN IF NOT EXISTS `actual_delivery_date` date DEFAULT NULL AFTER `expected_delivery_date`;

ALTER TABLE `workplan`
  ADD COLUMN IF NOT EXISTS `installed_quantity` decimal(12,2) DEFAULT NULL AFTER `work_status_image_after`;

CREATE TABLE IF NOT EXISTS `workplan_photos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,`workplan_id` int unsigned NOT NULL,`photo_type` varchar(20) NOT NULL DEFAULT 'after',
  `file_name` varchar(255) NOT NULL,`sort_order` int unsigned NOT NULL DEFAULT 0,`uploaded_by` int unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`),KEY `idx_workplan_photos_workplan` (`workplan_id`,`sort_order`),
  CONSTRAINT `fk_workplan_photos_workplan` FOREIGN KEY (`workplan_id`) REFERENCES `workplan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_workplan_photos_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(64) NOT NULL,`description` varchar(255) NOT NULL,`checksum` char(64) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,`code` varchar(64) NOT NULL,`name` varchar(100) NOT NULL,
  `scope` enum('system','project') NOT NULL DEFAULT 'project',`description` varchar(500) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,`active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),`updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),UNIQUE KEY `uq_roles_code` (`code`),KEY `idx_roles_scope_active` (`scope`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,`code` varchar(100) NOT NULL,`module` varchar(64) NOT NULL,`action` varchar(64) NOT NULL,
  `description` varchar(500) DEFAULT NULL,`active` tinyint(1) NOT NULL DEFAULT 1,`created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),UNIQUE KEY `uq_permissions_code` (`code`),KEY `idx_permissions_module_active` (`module`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int unsigned NOT NULL,`permission_id` int unsigned NOT NULL,`granted_by` int unsigned DEFAULT NULL,
  `granted_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),PRIMARY KEY (`role_id`,`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_actor` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_role_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,`project_id` int unsigned NOT NULL,`user_id` int unsigned NOT NULL,`role_id` int unsigned NOT NULL,
  `assigned_by` int unsigned NOT NULL,`assigned_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),`effective_from` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `effective_until` datetime(6) DEFAULT NULL,`active` tinyint(1) NOT NULL DEFAULT 1,`reason` varchar(500) NOT NULL,
  `revoked_by` int unsigned DEFAULT NULL,`revoked_at` datetime(6) DEFAULT NULL,`revoked_reason` varchar(500) DEFAULT NULL,`version` int unsigned NOT NULL DEFAULT 1,
  `active_user_id` int unsigned GENERATED ALWAYS AS (CASE WHEN `active`=1 THEN `user_id` ELSE NULL END) STORED,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),`updated_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),UNIQUE KEY `uq_project_active_user` (`project_id`,`active_user_id`),
  KEY `idx_assignments_user_active_dates` (`user_id`,`active`,`effective_from`,`effective_until`),KEY `idx_assignments_project_role_active` (`project_id`,`role_id`,`active`),
  CONSTRAINT `fk_assignments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_assignments_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CHECK (`effective_until` IS NULL OR `effective_until`>`effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rbac_outbox` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,`event_uuid` char(16) NOT NULL,`event_type` varchar(80) NOT NULL,`aggregate_id` bigint unsigned NOT NULL,
  `payload` json NOT NULL,`created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),`processed_at` datetime(6) DEFAULT NULL,`attempts` smallint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),UNIQUE KEY `uq_outbox_event_uuid` (`event_uuid`),KEY `idx_outbox_pending` (`processed_at`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,`user_id` int unsigned NOT NULL,`token_hash` binary(32) NOT NULL,
  `expires_at` datetime NOT NULL,`used_at` datetime DEFAULT NULL,`requested_by` int unsigned DEFAULT NULL,`requester_ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`),UNIQUE KEY `uq_password_reset_hash` (`token_hash`),
  KEY `idx_password_reset_user_active` (`user_id`,`used_at`,`expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_password_reset_actor` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `audit_logs`
  ADD COLUMN IF NOT EXISTS `event_uuid` char(16) DEFAULT NULL AFTER `user_agent`,
  ADD COLUMN IF NOT EXISTS `request_id` char(16) DEFAULT NULL AFTER `event_uuid`,
  ADD COLUMN IF NOT EXISTS `event_type` varchar(80) DEFAULT NULL AFTER `request_id`,
  ADD COLUMN IF NOT EXISTS `before_state` json DEFAULT NULL AFTER `event_type`,
  ADD COLUMN IF NOT EXISTS `after_state` json DEFAULT NULL AFTER `before_state`,
  ADD COLUMN IF NOT EXISTS `reason` varchar(500) DEFAULT NULL AFTER `after_state`,
  ADD COLUMN IF NOT EXISTS `previous_hash` binary(32) DEFAULT NULL AFTER `reason`,
  ADD COLUMN IF NOT EXISTS `event_hash` binary(32) DEFAULT NULL AFTER `previous_hash`,
  ADD COLUMN IF NOT EXISTS `occurred_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) AFTER `event_hash`;

ALTER TABLE `workplan` MODIFY `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00;

INSERT INTO `roles` (`code`,`name`,`scope`,`description`) VALUES
('project_manager','Project Manager','project','Manages project membership and all project modules'),
('project_engineer','Project Engineer','project','Manages engineering delivery and project records'),
('mep_engineer','MEP Engineer','project','Manages MEP technical and site records'),
('coordinator','Coordinator','project','Coordinates submittals, procurement, work plans and evidence'),
('viewer','Viewer','project','Read-only project access')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`description`=VALUES(`description`),`active`=1;

INSERT INTO `permissions` (`code`,`module`,`action`,`description`) VALUES
('project.view','project','view','View the project and its records'),('project.edit','project','edit','Edit project-owned records'),('dashboard.view','dashboard','view','View project dashboards'),
('boq.view','boq','view','View BOQ measurable items'),('boq.edit','boq','edit','Create and edit BOQ items'),('progress.update','progress','update','Update measurable-item progress'),
('submittal.view','submittal','view','View material submittals'),('submittal.create_edit','submittal','create_edit','Create and edit submittals'),
('procurement.view','procurement','view','View procurement records'),('procurement.create_edit','procurement','create_edit','Create and edit procurement records'),
('workplan.view','workplan','view','View work plans'),('workplan.create_edit','workplan','create_edit','Create and edit work plans'),('evidence.upload','evidence','upload','Upload work-plan evidence'),
('report.export','report','export','Export project reports'),('assignment.manage','assignment','manage','Assign, change and revoke project roles')
ON DUPLICATE KEY UPDATE `module`=VALUES(`module`),`action`=VALUES(`action`),`description`=VALUES(`description`),`active`=1;

INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r JOIN permissions p ON r.code='project_manager' OR
(r.code='project_engineer' AND p.code IN ('project.view','project.edit','dashboard.view','boq.view','boq.edit','progress.update','submittal.view','submittal.create_edit','procurement.view','procurement.create_edit','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='mep_engineer' AND p.code IN ('project.view','project.edit','dashboard.view','boq.view','boq.edit','progress.update','submittal.view','submittal.create_edit','procurement.view','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='coordinator' AND p.code IN ('project.view','dashboard.view','boq.view','submittal.view','submittal.create_edit','procurement.view','procurement.create_edit','workplan.view','workplan.create_edit','evidence.upload','report.export')) OR
(r.code='viewer' AND p.code IN ('project.view','dashboard.view','boq.view','submittal.view','procurement.view','workplan.view','report.export'));

INSERT IGNORE INTO `project_role_assignments` (`project_id`,`user_id`,`role_id`,`assigned_by`,`assigned_at`,`effective_from`,`active`,`reason`)
SELECT pm.project_id,pm.user_id,r.id,COALESCE(pm.assigned_by,pm.user_id),pm.assigned_at,pm.assigned_at,1,'Migrated from legacy project membership'
FROM project_members pm JOIN roles r ON r.name=pm.project_role;

INSERT INTO `schema_migrations` (`version`,`description`) VALUES ('003_001','Project role assignments, permission catalogue, audit evidence and outbox')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Verification: all legacy memberships must be represented.
SELECT COUNT(*) AS unmigrated_memberships FROM project_members pm
LEFT JOIN project_role_assignments a ON a.project_id=pm.project_id AND a.user_id=pm.user_id AND a.active=1
WHERE a.id IS NULL;

-- Version 006 evidence integrity and stable BOQ linkage.
ALTER TABLE `workplan`
  ADD COLUMN IF NOT EXISTS `work_status_image_before_checksum` char(64) DEFAULT NULL AFTER `work_status_image_before`;
ALTER TABLE `workplan_photos`
  ADD COLUMN IF NOT EXISTS `checksum` char(64) DEFAULT NULL AFTER `file_name`;
ALTER TABLE `submittals`
  ADD COLUMN IF NOT EXISTS `progress_id` int unsigned DEFAULT NULL AFTER `project_id`;

DROP PROCEDURE IF EXISTS ensure_006_submittal_links;
DELIMITER //
CREATE PROCEDURE ensure_006_submittal_links()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='submittals' AND INDEX_NAME='idx_submittals_progress') THEN
    ALTER TABLE `submittals` ADD INDEX `idx_submittals_progress` (`progress_id`);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='submittals' AND CONSTRAINT_NAME='fk_submittals_progress') THEN
    ALTER TABLE `submittals` ADD CONSTRAINT `fk_submittals_progress` FOREIGN KEY (`progress_id`) REFERENCES `project_progress` (`id`) ON DELETE SET NULL;
  END IF;
END//
DELIMITER ;
CALL ensure_006_submittal_links();
DROP PROCEDURE ensure_006_submittal_links;

CREATE TABLE IF NOT EXISTS `file_cleanup_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,`relative_path` varchar(500) NOT NULL,`checksum` char(64) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,`attempts` smallint unsigned NOT NULL DEFAULT 0,`last_error` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,`completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),KEY `idx_file_cleanup_pending` (`completed_at`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `rbac_outbox`
  ADD COLUMN IF NOT EXISTS `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending' AFTER `payload`,
  ADD COLUMN IF NOT EXISTS `next_attempt_at` datetime(6) DEFAULT CURRENT_TIMESTAMP(6) AFTER `attempts`,
  ADD COLUMN IF NOT EXISTS `last_error` varchar(500) DEFAULT NULL AFTER `next_attempt_at`;

ALTER TABLE `file_cleanup_queue`
  ADD COLUMN IF NOT EXISTS `idempotency_key` char(64) DEFAULT NULL AFTER `reason`,
  ADD COLUMN IF NOT EXISTS `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending' AFTER `idempotency_key`,
  ADD COLUMN IF NOT EXISTS `next_attempt_at` datetime DEFAULT CURRENT_TIMESTAMP AFTER `last_error`;
UPDATE `file_cleanup_queue` SET `idempotency_key`=SHA2(CONCAT(`relative_path`,'|',COALESCE(`checksum`,''),'|',`reason`),256) WHERE `idempotency_key` IS NULL;
ALTER TABLE `file_cleanup_queue` MODIFY `idempotency_key` char(64) NOT NULL;

UPDATE `submittals` s
JOIN (SELECT project_id,boq_no,MIN(id) id FROM project_progress WHERE item_type='Measurable Item' AND boq_no<>'' GROUP BY project_id,boq_no HAVING COUNT(*)=1) p
  ON p.project_id=s.project_id AND p.boq_no=s.boq_ref_no
SET s.progress_id=p.id WHERE s.progress_id IS NULL;

INSERT INTO `schema_migrations` (`version`,`description`)
VALUES ('006_001','Evidence checksums, cleanup recovery and stable BOQ linkage')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

INSERT INTO `schema_migrations` (`version`,`description`)
VALUES ('007_001','Conversion readiness: deterministic progress, ETags, currency and queue workers')
ON DUPLICATE KEY UPDATE `description`=VALUES(`description`);

-- Final verification: this result must be 0 before enabling role editing.
SELECT COUNT(*) AS unmigrated_memberships FROM project_members pm
LEFT JOIN project_role_assignments a ON a.project_id=pm.project_id AND a.user_id=pm.user_id AND a.active=1
WHERE a.id IS NULL;
