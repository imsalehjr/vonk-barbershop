USE vonk_barbershop;

INSERT INTO services (id, name, description, duration_min, price_cents, sort_order) VALUES
(1, 'Classic Cut', 'Scissor or clipper cut, washed and styled.', 30, 2800, 1),
(2, 'Skin Fade', 'Sharp fade down to the skin, finished with a razor line-up.', 45, 3400, 2),
(3, 'Beard Trim', 'Shape-up and trim with hot towel and beard oil.', 15, 1800, 3),
(4, 'Cut & Beard', 'The full treatment: haircut plus beard shape-up.', 60, 4500, 4),
(5, 'Hot Towel Shave', 'Traditional straight razor shave with hot towels.', 30, 3000, 5),
(6, 'Kids Cut', 'For the young ones up to 12 years.', 30, 2000, 6);

INSERT INTO barbers (id, name, bio, sort_order) VALUES
(1, 'Daan', 'Owner and fade specialist. Twelve years behind the chair.', 1),
(2, 'Youssef', 'Precision cuts and razor work. Known for clean line-ups.', 2),
(3, 'Milan', 'Classic styles and beard sculpting with a modern twist.', 3);

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
