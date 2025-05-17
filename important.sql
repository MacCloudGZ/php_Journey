-- Create the database (if not exists)
CREATE DATABASE IF NOT EXISTS act1;
USE act1;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    verification_token VARCHAR(64) DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE
);

-- Table: registrations (for IP-based registration limit)
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    date DATE NOT NULL
);

-- You may want to add indexes for performance
CREATE INDEX idx_registrations_ip_date ON registrations(ip_address, date);