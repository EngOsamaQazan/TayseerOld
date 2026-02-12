-- ═══════════════════════════════════════════════════
-- OCP Tables — Operational Control Panel
-- ═══════════════════════════════════════════════════

-- 1. Kanban Tasks
CREATE TABLE IF NOT EXISTS `os_follow_up_tasks` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `stage` ENUM('new','first_call','promise','post_promise','late','escalation','legal','closed') NOT NULL DEFAULT 'new',
    `priority` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    `due_date` DATE DEFAULT NULL,
    `assigned_to` INT(11) DEFAULT NULL,
    `action_type` VARCHAR(50) DEFAULT NULL COMMENT 'call/promise/visit/sms/legal/review/note',
    `status` ENUM('pending','in_progress','done','overdue','cancelled') NOT NULL DEFAULT 'pending',
    `sort_order` INT(11) DEFAULT 0,
    `escalation_reason` TEXT DEFAULT NULL,
    `escalation_type` VARCHAR(50) DEFAULT NULL COMMENT 'field_collection/warning/legal',
    `requires_approval` TINYINT(1) DEFAULT 0,
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_contract` (`contract_id`),
    KEY `idx_task_stage` (`stage`),
    KEY `idx_task_status` (`status`),
    KEY `idx_task_due` (`due_date`),
    KEY `idx_task_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. AI Recommendations
CREATE TABLE IF NOT EXISTS `os_ai_recommendations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL,
    `recommendation_type` ENUM('next_best_action','alternative','playbook') NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `reasons` TEXT DEFAULT NULL COMMENT 'JSON array of reasons',
    `confidence` TINYINT UNSIGNED DEFAULT 0 COMMENT '0-100',
    `risk_impact` ENUM('low','medium','high') DEFAULT 'medium',
    `playbook_id` VARCHAR(20) DEFAULT NULL,
    `input_signals` TEXT DEFAULT NULL COMMENT 'JSON: signals used for decision',
    `user_feedback` ENUM('executed','rejected','not_applicable') DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `executed_by` INT(11) DEFAULT NULL,
    `executed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ai_contract` (`contract_id`),
    KEY `idx_ai_type` (`recommendation_type`),
    KEY `idx_ai_feedback` (`user_feedback`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Playbook Runs
CREATE TABLE IF NOT EXISTS `os_playbook_runs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL,
    `playbook_id` VARCHAR(20) NOT NULL COMMENT 'A/B/C/D',
    `playbook_name` VARCHAR(100) NOT NULL,
    `current_step` INT(11) DEFAULT 1,
    `total_steps` INT(11) DEFAULT 1,
    `status` ENUM('active','completed','aborted','paused') NOT NULL DEFAULT 'active',
    `steps_data` TEXT DEFAULT NULL COMMENT 'JSON: step definitions and status',
    `started_by` INT(11) NOT NULL,
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pb_contract` (`contract_id`),
    KEY `idx_pb_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. SLA Status Tracking
CREATE TABLE IF NOT EXISTS `os_sla_status` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL,
    `rule_code` VARCHAR(50) NOT NULL COMMENT 'promise_followup_24h/high_risk_48h/legal_restriction',
    `rule_description` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('compliant','warning','breached') NOT NULL DEFAULT 'compliant',
    `due_at` DATETIME DEFAULT NULL,
    `breached_at` DATETIME DEFAULT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `resolved_by` INT(11) DEFAULT NULL,
    `resolution_note` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sla_contract` (`contract_id`),
    KEY `idx_sla_status` (`status`),
    KEY `idx_sla_rule` (`rule_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Audit Log (Event-based tracking)
CREATE TABLE IF NOT EXISTS `os_ocp_audit_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_id` INT(11) NOT NULL,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'task_created/task_moved/promise_added/escalated/status_changed',
    `event_data` TEXT DEFAULT NULL COMMENT 'JSON payload',
    `old_value` VARCHAR(255) DEFAULT NULL,
    `new_value` VARCHAR(255) DEFAULT NULL,
    `performed_by` INT(11) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_contract` (`contract_id`),
    KEY `idx_audit_type` (`event_type`),
    KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
