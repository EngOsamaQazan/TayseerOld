-- ══════════════════════════════════════════════════════════════════
--  Materialized View: كشف المثابره — جدول Cache محسّن
-- ══════════════════════════════════════════════════════════════════

-- 1) إنشاء الجدول المادي
DROP TABLE IF EXISTS tbl_persistence_cache;

CREATE TABLE tbl_persistence_cache (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judiciary_id      INT NOT NULL,
    judiciary_number  VARCHAR(50),
    case_year         VARCHAR(10),
    court_name        VARCHAR(255),
    contract_id       INT NOT NULL,
    customer_name     VARCHAR(255),
    last_action_name  VARCHAR(255),
    last_action_date  DATE,
    persistence_status VARCHAR(100),
    last_followup_date DATE,
    last_job_check_date VARCHAR(20),
    lawyer_name       VARCHAR(255),
    job_title         VARCHAR(255),
    job_type          VARCHAR(255),
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_persistence_status (persistence_status),
    INDEX idx_court_contract (court_name, contract_id),
    INDEX idx_judiciary_number (judiciary_number),
    INDEX idx_customer_name (customer_name),
    INDEX idx_lawyer_name (lawyer_name),
    INDEX idx_last_action_date (last_action_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) تعبئة أولية من VIEW
INSERT INTO tbl_persistence_cache
    (judiciary_id, judiciary_number, case_year, court_name, contract_id,
     customer_name, last_action_name, last_action_date, persistence_status,
     last_followup_date, last_job_check_date, lawyer_name, job_title, job_type)
SELECT
    judiciary_id, judiciary_number, case_year, court_name, contract_id,
    customer_name, last_action_name, last_action_date, persistence_status,
    last_followup_date, last_job_check_date, lawyer_name, job_title, job_type
FROM vw_persistence_report;
