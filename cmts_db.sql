cmts -- Database
CREATE DATABASE cmts_db;
USE cmts_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner','mechanic','admin') DEFAULT 'owner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cars table
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(50) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    service_date DATE NOT NULL,
    mileage INT,
    notes TEXT,
    next_service_date DATE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

  -- Scheduled script checking the services table 
 (SELECT u.email, u.username, c.make, c.model, s.service_type, s.next_service_date
   FROM services s
   JOIN cars c ON s.car_id = c.id
   JOIN users u ON c.user_id = u.id
   WHERE s.next_service_date <= CURDATE() + INTERVAL 7 DAY;
  );

-- Assignments table (link between cars and mechanics)
CREATE TABLE car_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Update services to cars assigned to the mechanic
CREATE TABLE service_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    service_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Services Booking
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    owner_id INT NOT NULL,
    request_type VARCHAR(100) NOT NULL,
    description TEXT,
    request_date DATE NOT NULL,
    status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
    mechanic_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES users(id) ON DELETE SET NULL
);

--Brute-force blocking
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    email VARCHAR(100),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Cleaning up brute-force in database
CREATE EVENT IF NOT EXISTS clean_login_attempts
ON SCHEDULE EVERY 1 DAY
DO
  DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 1 DAY);

--track how many failed attempts a user has made
ALTER TABLE users ADD failed_attempts INT DEFAULT 0;
ALTER TABLE users ADD last_failed_login DATETIME NULL;

--admin services
SELECT sr.id, sr.service_type, sr.description, sr.service_date, sr.created_at, 
               c.make, c.model, c.license_plate, 
               o.username AS owner_name, m.username AS mechanic_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users o ON c.user_id = o.id
        JOIN users m ON sr.mechanic_id = m.id
        ORDER BY sr.service_date DESC

--mechanic/admin see all upcoming services
SELECT s.*, c.make, c.model, c.license_plate, u.username 
            FROM services s 
            JOIN cars c ON s.car_id = c.id 
            JOIN users u ON c.user_id = u.id
            WHERE s.next_service_date IS NOT NULL 
              AND s.next_service_date <= ?

--cars table to store the image filename
ALTER TABLE cars ADD COLUMN car_image VARCHAR(255) DEFAULT NULL;

--garage_type column in your cars table
ALTER TABLE cars ADD COLUMN garage_type ENUM('truck', 'vehicle', 'tractor') NOT NULL DEFAULT 'vehicle';
 
 --delete car requests
 CREATE TABLE delete_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  car_id INT NOT NULL,
  verification_code VARCHAR(10) NOT NULL,
  expires_at DATETIME NOT NULL,
  status ENUM('pending','used','expired') DEFAULT 'pending',
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (car_id) REFERENCES cars(id)
);


--delete related records of deleted cars
ALTER TABLE delete_requests 
DROP FOREIGN KEY delete_requests_ibfk_2;

ALTER TABLE delete_requests
ADD CONSTRAINT delete_requests_ibfk_2 
FOREIGN KEY (car_id) REFERENCES cars(id) 
ON DELETE CASCADE;

--mising columns on services (user, status, last_reminder)
ALTER TABLE services
ADD COLUMN user_id INT(11) NOT NULL AFTER car_id,
ADD COLUMN status VARCHAR(50) DEFAULT 'Pending' AFTER service_date,
ADD COLUMN last_reminder DATETIME NULL AFTER status,
ADD CONSTRAINT services_ibfk_2 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

--Daily Auto-update Event
DELIMITER //

CREATE EVENT IF NOT EXISTS update_service_status
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE services
    SET status = 'Overdue'
    WHERE service_date < CURDATE()
      AND status = 'Pending';
END//

DELIMITER ;

--reminder event every 3 days
DELIMITER //

CREATE EVENT IF NOT EXISTS send_service_reminders
ON SCHEDULE EVERY 3 DAY
DO
BEGIN
    UPDATE services
    SET last_reminder = NOW()
    WHERE service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND (last_reminder IS NULL OR last_reminder < DATE_SUB(NOW(), INTERVAL 3 DAY));
END//

DELIMITER ;



