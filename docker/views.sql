-- ══════════════════════════════════════════════════════════════════
--  Database Views — يتم تنفيذها عند بدء تشغيل الحاوية
-- ══════════════════════════════════════════════════════════════════

-- كشف المثابره: VIEW محسّن لتقرير مؤشّر المثابرة
-- يقلل الحمل على PHP بنقل كامل منطق الاستعلام إلى قاعدة البيانات

CREATE OR REPLACE VIEW vw_persistence_report AS
SELECT
    j.id AS judiciary_id,
    j.judiciary_number,
    j.`year` AS case_year,
    co.name AS court_name,
    c.id AS contract_id,

    SUBSTRING_INDEX(
        GROUP_CONCAT(cu.name ORDER BY jca.action_date DESC, jca.id DESC SEPARATOR '||'),
        '||', 1
    ) AS customer_name,

    SUBSTRING_INDEX(
        GROUP_CONCAT(ja.name ORDER BY jca.action_date DESC, jca.id DESC SEPARATOR '||'),
        '||', 1
    ) AS last_action_name,

    DATE(MAX(jca.action_date)) AS last_action_date,

    CASE
      WHEN TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) > 11
        OR (TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) = 11
            AND DATEDIFF(CURDATE(), DATE_ADD(DATE(MAX(jca.action_date)),
                INTERVAL TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) MONTH)) > 25)
        THEN 'red_renew'
      WHEN TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) = 11
        AND DATEDIFF(CURDATE(), DATE_ADD(DATE(MAX(jca.action_date)),
            INTERVAL TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) MONTH)) BETWEEN 0 AND 25
        THEN 'red_due'
      WHEN TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) = 10
        THEN 'orange_due'
      WHEN TIMESTAMPDIFF(MONTH, MAX(jca.action_date), CURDATE()) >= 9
        THEN 'green_due'
      ELSE CONCAT('remaining_',
        GREATEST(0, TIMESTAMPDIFF(MONTH, CURDATE(), DATE_ADD(MAX(jca.action_date), INTERVAL 9 MONTH))),
        '_',
        GREATEST(0, DATEDIFF(
            DATE_ADD(MAX(jca.action_date), INTERVAL 9 MONTH),
            DATE_ADD(CURDATE(), INTERVAL TIMESTAMPDIFF(MONTH, CURDATE(),
                DATE_ADD(MAX(jca.action_date), INTERVAL 9 MONTH)) MONTH)
        ))
      )
    END AS persistence_status,

    DATE(lfp.latest_follow_up_date) AS last_followup_date,

    SUBSTRING_INDEX(
        GROUP_CONCAT(DATE(cu.last_job_query_date) ORDER BY jca.action_date DESC, jca.id DESC SEPARATOR '||'),
        '||', 1
    ) AS last_job_check_date,

    lw.name AS lawyer_name,

    SUBSTRING_INDEX(
        GROUP_CONCAT(IFNULL(jb.name,'') ORDER BY jca.action_date DESC, jca.id DESC SEPARATOR '||'),
        '||', 1
    ) AS job_title,

    SUBSTRING_INDEX(
        GROUP_CONCAT(IFNULL(jbt.name,'') ORDER BY jca.action_date DESC, jca.id DESC SEPARATOR '||'),
        '||', 1
    ) AS job_type

FROM os_contracts c
JOIN os_judiciary j ON j.contract_id = c.id
JOIN os_court co ON co.id = j.court_id
JOIN os_lawyers lw ON lw.id = j.lawyer_id
JOIN os_judiciary_customers_actions jca ON jca.judiciary_id = j.id AND jca.is_deleted = 0
JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
JOIN os_customers cu ON cu.id = jca.customers_id
LEFT JOIN os_jobs jb ON jb.id = cu.job_title
LEFT JOIN os_jobs_type jbt ON jbt.id = jb.job_type
LEFT JOIN (
    SELECT contract_id, MAX(date_time) AS latest_follow_up_date
    FROM os_follow_up
    GROUP BY contract_id
) lfp ON lfp.contract_id = c.id
WHERE c.status IN ('pending','active','reconciliation','judiciary','legal_department','settlement')
  AND j.is_deleted = 0
GROUP BY j.id, j.judiciary_number, j.`year`, co.name, c.id, lw.name
ORDER BY co.name, c.id, j.judiciary_number;
