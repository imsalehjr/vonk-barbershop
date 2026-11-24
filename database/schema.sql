SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS vonk_barbershop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vonk_barbershop;

DROP TABLE IF EXISTS appointment_slots;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS barber_services;
DROP TABLE IF EXISTS schedule_exceptions;
DROP TABLE IF EXISTS opening_hours;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS barbers;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS admin_users;

CREATE TABLE services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    duration_min SMALLINT UNSIGNED NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT chk_duration CHECK (duration_min > 0 AND duration_min % 15 = 0)
) ENGINE=InnoDB;

CREATE TABLE barbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    bio VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE barber_services (
    barber_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (barber_id, service_id),
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE opening_hours (
    weekday TINYINT UNSIGNED PRIMARY KEY,
    open_time TIME NULL,
    close_time TIME NULL,
    is_closed TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT chk_weekday CHECK (weekday BETWEEN 1 AND 7)
) ENGINE=InnoDB;

CREATE TABLE schedule_exceptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    barber_id INT UNSIGNED NULL,
    is_closed TINYINT(1) NOT NULL DEFAULT 1,
    open_time TIME NULL,
    close_time TIME NULL,
    note VARCHAR(150) NOT NULL DEFAULT '',
    INDEX idx_date (date),
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barber_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL DEFAULT '',
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    status ENUM('booked','cancelled','completed','no_show') NOT NULL DEFAULT 'booked',
    manage_token_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_token (manage_token_hash),
    INDEX idx_start (start_at),
    INDEX idx_barber_start (barber_id, start_at),
    FOREIGN KEY (barber_id) REFERENCES barbers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

CREATE TABLE appointment_slots (
    appointment_id INT UNSIGNED NOT NULL,
    barber_id INT UNSIGNED NOT NULL,
    slot_start DATETIME NOT NULL,
    PRIMARY KEY (appointment_id, slot_start),
    UNIQUE KEY uq_barber_slot (barber_id, slot_start),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id)
) ENGINE=InnoDB;

CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    email VARCHAR(190) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB;
