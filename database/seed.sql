INSERT INTO services (id, name, description, duration_min, price_cents, sort_order) VALUES
(1, 'Klassieke knipbeurt', 'Knippen met schaar of tondeuse, wassen en stylen.', 30, 2800, 1),
(2, 'Skin fade', 'Strakke fade tot op de huid, afgewerkt met een scherpe contour.', 45, 3400, 2),
(3, 'Baard trimmen', 'Baard in model en trimmen, met hete handdoek en baardolie.', 15, 1800, 3),
(4, 'Knippen & baard', 'Het complete pakket: knippen plus baard in model.', 60, 4500, 4),
(5, 'Scheren met hete handdoek', 'Traditioneel scheren met het mes en hete handdoeken.', 30, 3000, 5),
(6, 'Kinderknipbeurt', 'Voor de kleintjes tot 12 jaar.', 30, 2000, 6);

INSERT INTO barbers (id, name, bio, sort_order) VALUES
(1, 'Daan', 'Eigenaar en fade-specialist. Twaalf jaar achter de stoel.', 1),
(2, 'Youssef', 'Precisiewerk met schaar en mes. Bekend om strakke contouren.', 2),
(3, 'Milan', 'Klassieke stijlen en baardwerk met een moderne twist.', 3);

INSERT INTO barber_services (barber_id, service_id) VALUES
(1,1),(1,2),(1,3),(1,4),(1,6),
(2,1),(2,2),(2,3),(2,4),(2,5),
(3,1),(3,3),(3,4),(3,5),(3,6);

INSERT INTO opening_hours (weekday, open_time, close_time, is_closed) VALUES
(1, NULL, NULL, 1),
(2, '09:00', '18:00', 0),
(3, '09:00', '18:00', 0),
(4, '09:00', '21:00', 0),
(5, '09:00', '18:00', 0),
(6, '09:00', '17:00', 0),
(7, NULL, NULL, 1);
