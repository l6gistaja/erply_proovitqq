CREATE DATABASE erply_proovitqq;

USE erply_proovitqq;

CREATE TABLE IF NOT EXISTS erply_log (
    session_id VARCHAR(50) NOT NULL,
    t0 INT NOT NULL,
    t1 INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    error INT NOT NULL DEFAULT 0
);

CREATE USER 'erply_proovitqq'@'localhost' IDENTIFIED BY 'erply_proovitqq';
GRANT ALL PRIVILEGES ON erply_proovitqq.* TO 'erply_proovitqq'@'localhost';
FLUSH PRIVILEGES;
