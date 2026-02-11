DROP PROCEDURE IF EXISTS sp_refresh_persistence_cache;

CREATE PROCEDURE sp_refresh_persistence_cache()
BEGIN
    TRUNCATE TABLE tbl_persistence_cache;
    INSERT INTO tbl_persistence_cache
        (judiciary_id, judiciary_number, case_year, court_name, contract_id,
         customer_name, last_action_name, last_action_date, persistence_status,
         last_followup_date, last_job_check_date, lawyer_name, job_title, job_type)
    SELECT
        judiciary_id, judiciary_number, case_year, court_name, contract_id,
        customer_name, last_action_name, last_action_date, persistence_status,
        last_followup_date, last_job_check_date, lawyer_name, job_title, job_type
    FROM vw_persistence_report;
END;

SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS ev_refresh_persistence_cache;

CREATE EVENT ev_refresh_persistence_cache
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO CALL sp_refresh_persistence_cache();
