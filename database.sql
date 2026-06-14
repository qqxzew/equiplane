CREATE DATABASE IF NOT EXISTS equiplane;
USE equiplane;

CREATE TABLE IF NOT EXISTS companies
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    address    VARCHAR(255) NOT NULL,
    ico        VARCHAR(20)  NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    company_id    INT                   DEFAULT NULL,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(50)  NOT NULL,
    hourly_rate   INT          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP             DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipment
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    company_id    INT          NOT NULL,
    name          VARCHAR(255) NOT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    client_id     INT            NOT NULL,
    engineer_id   INT                     DEFAULT NULL,
    equipment_id  INT            NOT NULL,
    subject       VARCHAR(255)   NOT NULL,
    description   TEXT           NOT NULL,
    status        VARCHAR(50)    NOT NULL DEFAULT 'new',
    priority      VARCHAR(50)    NOT NULL DEFAULT 'low',
    cost_of_parts DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    hours_spent   DECIMAL(5, 2)  NOT NULL DEFAULT 0.00,
    created_at    TIMESTAMP               DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (engineer_id) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipment (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_logs
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT  NOT NULL,
    action_text TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;