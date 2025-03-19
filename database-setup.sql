-- AS THE GUIDELINE FOR CONSTRUING THE DB


-- FOR DATABASE STRUCTURE SETUP
-- THIS VERSION MODIFY THE FOREIGN KEY THAT USER ID CAN BE MODIFIED, STILL AN EXPERIMENTAL VER.

CREATE DATABASE IF NOT EXISTS user_auth;

USE user_auth;

-- First, drop the tables in reverse order of dependencies
DROP TABLE IF EXISTS analysis_results;
DROP TABLE IF EXISTS ct_scans;
DROP TABLE IF EXISTS users;

-- Then recreate them with ON DELETE CASCADE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE ct_scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    image_data LONGBLOB NOT NULL,
    image_name VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE analysis_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ct_scan_id INT NOT NULL,
    patient_id VARCHAR(50) NOT NULL,
    classification VARCHAR(100) NOT NULL,
    confidence DECIMAL(5,2) NOT NULL,
    accuracy DECIMAL(5,2) NOT NULL,
    contour_image LONGBLOB NOT NULL,
    edge_image LONGBLOB NOT NULL,
    threshold_mask_image LONGBLOB NOT NULL,
    damage_overlay_image LONGBLOB NOT NULL,
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ct_scan_id) REFERENCES ct_scans(id) ON DELETE CASCADE
);
