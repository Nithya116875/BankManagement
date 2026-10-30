-- Banking Management System schema (MySQL 8+ / MariaDB 10.5+)
CREATE DATABASE IF NOT EXISTS bank_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bank_db;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  account_number CHAR(12) NOT NULL UNIQUE,
  account_type ENUM('savings','current') NOT NULL DEFAULT 'savings',
  balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  status ENUM('active','frozen') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_balance CHECK (balance >= 0),
  CONSTRAINT fk_acc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id INT UNSIGNED NOT NULL,
  type ENUM('deposit','withdrawal','transfer_in','transfer_out') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  balance_after DECIMAL(15,2) NOT NULL,
  counterparty CHAR(12) NULL,
  reference CHAR(16) NOT NULL,
  description VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_amount CHECK (amount > 0),
  CONSTRAINT fk_txn_acc FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,
  INDEX idx_acc_date (account_id, created_at),
  INDEX idx_ref (reference)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
