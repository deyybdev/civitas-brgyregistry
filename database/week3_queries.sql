-- ============================================================================
-- WEEK 3 EVIDENCE: 5 queries, view, procedures, triggers (run after the setup file)
-- ============================================================================
USE `civitas_brgyregistry`;

-- Query 1: residents in alphabetical order, with household and purok
SELECT r.resident_id, CONCAT(r.last_name, ', ', r.first_name, ' ', r.middle_name) AS full_name,
       r.status, p.purok_name, CONCAT(h.house_number, ' ', h.street) AS address
FROM Resident r
JOIN Household h ON h.household_id = r.household_id
JOIN Purok p ON p.purok_id = h.purok_id
ORDER BY r.last_name, r.first_name;

-- Query 2: requests not yet Released, with resident name and status
SELECT dr.request_id, CONCAT(r.last_name, ', ', r.first_name) AS resident_name,
       dt.type_name, dr.request_date, dr.status
FROM DocumentRequest dr
JOIN Resident r ON r.resident_id = dr.resident_id
JOIN DocumentType dt ON dt.doctype_id = dr.doctype_id
WHERE dr.status <> 'Released'
ORDER BY dr.request_date;

-- Query 3: total payments collected per day
SELECT payment_date, COUNT(*) AS payments, SUM(amount_paid) AS total_collected
FROM Payment
GROUP BY payment_date
ORDER BY payment_date;

-- Query 4: unpaid or partially paid requests with remaining balance (uses the view)
SELECT request_id, resident_name, type_name, status, fee_charged, total_paid, balance
FROM vw_request_balances
WHERE balance > 0
ORDER BY balance DESC, request_id;

-- Query 5: number of requests and fees collected per document type
SELECT dt.type_name, COUNT(DISTINCT dr.request_id) AS requests,
       COALESCE(SUM(p.amount_paid), 0) AS fees_collected
FROM DocumentType dt
LEFT JOIN DocumentRequest dr ON dr.doctype_id = dt.doctype_id
LEFT JOIN Payment p ON p.request_id = dr.request_id
GROUP BY dt.doctype_id, dt.type_name;

-- Bonus: Active residents per purok (non-Active residents are excluded)
SELECT p.purok_name, COUNT(r.resident_id) AS active_residents
FROM Purok p
LEFT JOIN Household h ON h.purok_id = p.purok_id
LEFT JOIN Resident r ON r.household_id = h.household_id AND r.status = 'Active'
GROUP BY p.purok_id, p.purok_name
ORDER BY p.purok_id;

-- ----------------------------------------------------------------------------
-- DEMOS (take a screenshot of each result)
-- ----------------------------------------------------------------------------

-- View
SELECT * FROM vw_request_balances ORDER BY request_id;

-- Stored procedure: request 36 is unpaid with a fee of 50.00
SELECT * FROM vw_request_balances WHERE request_id = 36;   -- before
CALL sp_record_payment(36, 20.00, CURDATE());               -- accepted, balance becomes 30.00
CALL sp_record_payment(36, 60.00, CURDATE());               -- rejected: Payment exceeds remaining balance
CALL sp_record_payment(36, 0, CURDATE());                   -- rejected: Amount must be greater than zero

-- Stored procedure: file a request (fee is copied into fee_charged)
CALL sp_create_request(6, 1, 'Employment', CURDATE());      -- accepted (resident 6 is Active)
CALL sp_create_request(10, 1, 'Employment', CURDATE());     -- rejected: resident 10 is Inactive

-- Trigger 1: resident 3 has an unreleased request, so the delete is blocked
DELETE FROM Resident WHERE resident_id = 3;                 -- rejected: Cannot delete resident with unreleased requests
-- A resident with no requests can be deleted (insert a test resident first, then delete it)
INSERT INTO Resident (household_id, last_name, first_name, middle_name, birth_date)
VALUES (1, 'Test', 'Delete', '', '2000-01-01');
DELETE FROM Resident WHERE last_name = 'Test' AND first_name = 'Delete';   -- accepted

-- Trigger 2: marking a request Released fills in the release date automatically
SELECT request_id, status, release_date FROM DocumentRequest WHERE status = 'Processing' LIMIT 1;
UPDATE DocumentRequest SET status = 'Released'
WHERE request_id = (SELECT request_id FROM (SELECT request_id FROM DocumentRequest WHERE status = 'Processing' LIMIT 1) t);
SELECT request_id, status, release_date FROM DocumentRequest WHERE release_date = CURDATE();
