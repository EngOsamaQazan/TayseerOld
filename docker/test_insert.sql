INSERT INTO os_diwan_transactions (transaction_type, from_employee_id, to_employee_id, receipt_number, transaction_date, created_by, updated_by, created_at, updated_at) VALUES ('استلام', 75, 1, 'TEST-001', NOW(), 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SELECT * FROM os_diwan_transactions;
