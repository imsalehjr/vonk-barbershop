INSERT INTO appointments (barber_id, service_id, customer_name, customer_email, customer_phone, start_at, end_at, service_name, price_cents, status, manage_token_hash)
SELECT d.barber_id, s.id, d.customer_name, d.customer_email, d.customer_phone, d.start_at,
       d.start_at + INTERVAL s.duration_min MINUTE, s.name, s.price_cents, d.status,
       SHA2(CONCAT(UUID(), RAND()), 256)
FROM (
    SELECT 2 AS barber_id, 1 AS service_id, 'Jesse de Vries' AS customer_name, 'jesse@example.com' AS customer_email, '0612345001' AS customer_phone, CURDATE() + INTERVAL 570 MINUTE AS start_at, 'completed' AS status
    UNION ALL SELECT 1, 2, 'Sem Bakker', 'sem@example.com', '', CURDATE() + INTERVAL 600 MINUTE, 'booked'
    UNION ALL SELECT 3, 3, 'Luuk Jansen', 'luuk@example.com', '0612345003', CURDATE() + INTERVAL 630 MINUTE, 'booked'
    UNION ALL SELECT 1, 4, 'Noah Visser', 'noah@example.com', '0612345004', CURDATE() + INTERVAL 660 MINUTE, 'booked'
    UNION ALL SELECT 2, 5, 'Finn Smit', 'finn@example.com', '', CURDATE() + INTERVAL 780 MINUTE, 'booked'
    UNION ALL SELECT 3, 6, 'Mila Meijer', 'mila@example.com', '0612345006', CURDATE() + INTERVAL 900 MINUTE, 'booked'
    UNION ALL SELECT 1, 1, 'Daan Mulder', 'dmulder@example.com', '', CURDATE() + INTERVAL 1 DAY + INTERVAL 540 MINUTE, 'booked'
    UNION ALL SELECT 2, 4, 'Levi de Boer', 'levi@example.com', '0612345008', CURDATE() + INTERVAL 1 DAY + INTERVAL 960 MINUTE, 'booked'
    UNION ALL SELECT 1, 2, 'Milan Bos', 'mbos@example.com', '', CURDATE() + INTERVAL 1 DAY + INTERVAL 1110 MINUTE, 'booked'
    UNION ALL SELECT 3, 1, 'Thomas Peters', 'thomas@example.com', '0612345010', CURDATE() + INTERVAL 1 DAY + INTERVAL 1140 MINUTE, 'booked'
    UNION ALL SELECT 2, 2, 'Lucas Hendriks', 'lucas@example.com', '', CURDATE() + INTERVAL 2 DAY + INTERVAL 600 MINUTE, 'cancelled'
    UNION ALL SELECT 1, 1, 'Bram Dekker', 'bram@example.com', '0612345012', CURDATE() + INTERVAL 2 DAY + INTERVAL 720 MINUTE, 'booked'
    UNION ALL SELECT 3, 4, 'Ruben van Dijk', 'ruben@example.com', '', CURDATE() + INTERVAL 3 DAY + INTERVAL 660 MINUTE, 'booked'
) AS d
JOIN services s ON s.id = d.service_id;

INSERT INTO appointment_slots (appointment_id, barber_id, slot_start)
SELECT a.id, a.barber_id, a.start_at + INTERVAL n.i MINUTE
FROM appointments a
JOIN (
    SELECT 0 AS i UNION ALL SELECT 15 UNION ALL SELECT 30 UNION ALL SELECT 45
    UNION ALL SELECT 60 UNION ALL SELECT 75 UNION ALL SELECT 90 UNION ALL SELECT 105
    UNION ALL SELECT 120 UNION ALL SELECT 135 UNION ALL SELECT 150 UNION ALL SELECT 165
    UNION ALL SELECT 180 UNION ALL SELECT 195 UNION ALL SELECT 210 UNION ALL SELECT 225
) AS n ON n.i < TIMESTAMPDIFF(MINUTE, a.start_at, a.end_at)
WHERE a.status IN ('booked', 'completed')
  AND NOT EXISTS (SELECT 1 FROM appointment_slots x WHERE x.appointment_id = a.id);
