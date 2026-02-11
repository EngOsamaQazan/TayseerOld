-- ============================================================
-- RISK ENGINE TABLES — Smart Customer Onboarding
-- ML-Ready Decision Engine Schema
-- ============================================================

-- 1) Risk Assessments — سجل تقييم المخاطر
CREATE TABLE IF NOT EXISTS os_risk_assessments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    
    -- Scores
    rule_score      DECIMAL(5,2) NOT NULL DEFAULT 0,
    ml_score        DECIMAL(5,2) DEFAULT NULL COMMENT 'NULL until ML Phase B',
    final_score     DECIMAL(5,2) NOT NULL DEFAULT 0,
    score_weights   JSON DEFAULT NULL COMMENT '{"rule":1.0,"ml":0.0}',
    
    -- Decision
    risk_tier       ENUM('approved','conditional','high_risk','rejected') NOT NULL DEFAULT 'conditional',
    decision        ENUM('approved','conditional','rejected','draft','pending') NOT NULL DEFAULT 'pending',
    decision_by     INT UNSIGNED DEFAULT NULL COMMENT 'User who made final decision',
    decision_at     DATETIME DEFAULT NULL,
    decision_notes  TEXT DEFAULT NULL,
    
    -- Explainability
    top_factors     JSON DEFAULT NULL COMMENT 'Top 5 risk factors with scores',
    all_factors     JSON DEFAULT NULL COMMENT 'Complete factor breakdown',
    reasons         JSON DEFAULT NULL COMMENT 'Human-readable reasons',
    
    -- Input snapshot for ML training
    input_snapshot  JSON NOT NULL COMMENT 'All customer data at assessment time',
    
    -- Completeness
    profile_pct     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Profile completeness 0-100',
    
    -- Recommendations
    max_financing   DECIMAL(12,2) DEFAULT NULL,
    max_installment DECIMAL(10,2) DEFAULT NULL,
    max_months      SMALLINT UNSIGNED DEFAULT NULL,
    
    -- Versioning
    rules_version   VARCHAR(20) NOT NULL DEFAULT '1.0',
    model_version   VARCHAR(20) DEFAULT NULL,
    engine_mode     ENUM('rules_only','ml_only','ensemble') NOT NULL DEFAULT 'rules_only',
    
    -- Smart alerts at assessment time
    alerts          JSON DEFAULT NULL COMMENT 'Warnings and recommendations',
    
    -- Audit
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_customer (customer_id),
    KEY idx_decision (decision),
    KEY idx_risk_tier (risk_tier),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Risk Engine Config — إعدادات محرك القرار
CREATE TABLE IF NOT EXISTS os_risk_engine_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key      VARCHAR(100) NOT NULL UNIQUE,
    config_value    JSON NOT NULL,
    description_ar  VARCHAR(255) DEFAULT NULL,
    updated_by      INT UNSIGNED DEFAULT NULL,
    updated_at      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default config values
INSERT INTO os_risk_engine_config (config_key, config_value, description_ar) VALUES
('engine_mode', '"rules_only"', 'وضع المحرك: rules_only / ml_only / ensemble'),
('score_weights', '{"rule":1.0,"ml":0.0}', 'أوزان النقاط: قواعد vs ذكاء'),
('tier_thresholds', '{"approved":25,"conditional":45,"high_risk":65}', 'حدود مستويات المخاطر'),
('default_label_days', '90', 'أيام التأخر لتصنيف التعثر'),
('default_label_count', '3', 'عدد الأقساط المتأخرة للتعثر'),
('max_dti_ratio', '0.50', 'أقصى نسبة دين للدخل'),
('financing_multipliers', '{"approved":0.60,"conditional":0.40,"high_risk":0.20}', 'مضاعفات سقف التمويل من الدخل السنوي'),
('max_term_months', '{"approved":36,"conditional":24,"high_risk":12}', 'أقصى مدة بالأشهر حسب المستوى')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- 3) Customer Financial Profile — الملف المالي
CREATE TABLE IF NOT EXISTS os_customer_financials (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id             INT NOT NULL UNIQUE,
    monthly_income          DECIMAL(10,2) DEFAULT 0,
    additional_income       DECIMAL(10,2) DEFAULT 0,
    income_source           VARCHAR(100) DEFAULT NULL,
    monthly_obligations     DECIMAL(10,2) DEFAULT 0 COMMENT 'التزامات شهرية حالية',
    obligation_details      TEXT DEFAULT NULL,
    dependents_count        TINYINT UNSIGNED DEFAULT 0,
    years_at_current_job    DECIMAL(4,1) DEFAULT NULL,
    employment_type         ENUM('government','private','self_employed','retired','military','unemployed','other') DEFAULT NULL,
    employer_name           VARCHAR(255) DEFAULT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Outcome Labels — تصنيفات التعثر/الانتظام (ML Training)
CREATE TABLE IF NOT EXISTS os_outcome_labels (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    contract_id     INT DEFAULT NULL,
    label           ENUM('good','partial_default','default','write_off') NOT NULL DEFAULT 'good',
    label_reason    VARCHAR(255) DEFAULT NULL,
    days_past_due   INT DEFAULT 0,
    missed_payments INT DEFAULT 0,
    label_date      DATE NOT NULL,
    auto_generated  TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_contract (contract_id),
    KEY idx_label (label),
    KEY idx_date (label_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) ML Model Registry — سجل نماذج الذكاء الاصطناعي
CREATE TABLE IF NOT EXISTS os_ml_models (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    model_name      VARCHAR(100) NOT NULL,
    version         VARCHAR(20) NOT NULL,
    model_type      VARCHAR(50) NOT NULL COMMENT 'logistic_regression / gradient_boosting / etc',
    features_used   JSON NOT NULL,
    metrics         JSON DEFAULT NULL COMMENT '{"auc":0.85,"accuracy":0.82,"precision":0.79}',
    threshold       DECIMAL(5,2) DEFAULT 0.50,
    is_active       TINYINT(1) NOT NULL DEFAULT 0,
    trained_at      DATETIME DEFAULT NULL,
    training_samples INT DEFAULT 0,
    model_path      VARCHAR(500) DEFAULT NULL COMMENT 'Path to serialized model file',
    notes           TEXT DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_model_version (model_name, version),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
