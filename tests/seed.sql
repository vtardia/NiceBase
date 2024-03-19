insert into users (id, full_name, email, phone, password)
values (1, 'John Lennon', 'john@thebeatles.com', '07482123456', '$2y$10$5xro7habSENyN/UbF4WrveeIBD86maLs63roueRoSfnoalJ7Vpgoi'),
       (2, 'Paul McCartney', 'paul@thebeatles.com', '07482123457', '$2y$10$5xro7habSENyN/UbF4WrveeIBD86maLs63roueRoSfnoalJ7Vpgoi'),
       (3, 'George Harrison', 'george@thebeatles.com', '07482123458', '$2y$10$5xro7habSENyN/UbF4WrveeIBD86maLs63roueRoSfnoalJ7Vpgoi'),
       (4, 'Ringo Starr', 'ringo@thebeatles.com', '07482123459', '$2y$10$5xro7habSENyN/UbF4WrveeIBD86maLs63roueRoSfnoalJ7Vpgoi');

insert into tickets (id, user_id, ip_address, code)
values (1, 2, '192.168.1.2', 'ABC123456'),
       (2, 1, '192.168.1.3', 'ABC654321');

insert into enrollments (ticket_id, workshop_id)
values (1, 2), (1, 3), (2, 1), (2, 3);
