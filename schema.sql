CREATE DATABASE IF NOT EXISTS brgy_sanjuan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE brgy_sanjuan;

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, username VARCHAR(80) NOT NULL UNIQUE,
 email VARCHAR(160) NULL UNIQUE, phone VARCHAR(40) NULL, password_hash VARCHAR(255) NOT NULL,
 role ENUM('admin','captain','secretary','health_worker','tanod','als_teacher','resident') NOT NULL DEFAULT 'resident',
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS residents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, resident_code VARCHAR(30) NOT NULL UNIQUE, first_name VARCHAR(90) NOT NULL,
 middle_name VARCHAR(90) NULL, last_name VARCHAR(90) NOT NULL, suffix VARCHAR(20) NULL, birth_date DATE NULL,
 gender ENUM('Female','Male','Other','Prefer not to say') NOT NULL DEFAULT 'Prefer not to say', civil_status VARCHAR(30) NULL,
 occupation VARCHAR(120) NULL, phone VARCHAR(40) NULL, email VARCHAR(160) NULL, purok VARCHAR(80) NULL,
 address TEXT NULL, household_code VARCHAR(40) NULL, household_role VARCHAR(40) NULL, voter_status TINYINT(1) NOT NULL DEFAULT 0,
 sectors JSON NULL, photo_path VARCHAR(255) NULL, notes TEXT NULL, user_id INT UNSIGNED NULL UNIQUE,
 status ENUM('Active','Moved','Deceased') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_resident_name(last_name, first_name), INDEX idx_household(household_code), INDEX idx_purok(purok),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS address_history (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, resident_id INT UNSIGNED NOT NULL, address TEXT NOT NULL, purok VARCHAR(80) NULL,
 moved_in DATE NULL, moved_out DATE NULL, recorded_by INT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (resident_id) REFERENCES residents(id), FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS documents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, document_no VARCHAR(40) NOT NULL UNIQUE,
 resident_id INT UNSIGNED NULL, resident_name VARCHAR(200) NOT NULL,
 document_type ENUM('Barangay Clearance','Certificate of Indigency','Certificate of Residency','First Time Job Seeker','Other') NOT NULL,
 purpose VARCHAR(255) NULL, fee DECIMAL(10,2) NOT NULL DEFAULT 0, or_no VARCHAR(40) NULL,
 issued_by INT UNSIGNED NULL, status ENUM('Pending','Ready','Released','Cancelled') NOT NULL DEFAULT 'Pending',
 verification_token CHAR(64) NOT NULL UNIQUE, issued_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL, FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS cases (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_no VARCHAR(40) NOT NULL UNIQUE, case_type VARCHAR(100) NOT NULL,
 complainant VARCHAR(200) NOT NULL, respondent VARCHAR(200) NOT NULL, incident_date DATE NULL, location VARCHAR(255) NULL,
 narrative TEXT NULL, status ENUM('Filed','For mediation','Scheduled','Settled','Referred','Closed') NOT NULL DEFAULT 'Filed',
 lupon_officer VARCHAR(120) NULL, cfa_no VARCHAR(40) NULL, created_by INT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS hearings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id INT UNSIGNED NOT NULL, hearing_at DATETIME NOT NULL,
 hearing_type ENUM('Mediation','Conciliation','Arbitration','Follow-up') NOT NULL DEFAULT 'Mediation', venue VARCHAR(160) NULL,
 notes TEXT NULL, outcome VARCHAR(255) NULL, status ENUM('Scheduled','Completed','Rescheduled','Cancelled') NOT NULL DEFAULT 'Scheduled',
 FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS shelters (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(140) NOT NULL, location VARCHAR(255) NULL, latitude DECIMAL(10,7) NULL, longitude DECIMAL(10,7) NULL, capacity INT UNSIGNED NOT NULL DEFAULT 0,
 occupied INT UNSIGNED NOT NULL DEFAULT 0, status ENUM('Open','Full','Closed') NOT NULL DEFAULT 'Open', updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS displacements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, household_code VARCHAR(40) NULL, family_name VARCHAR(160) NOT NULL,
 family_size INT UNSIGNED NOT NULL DEFAULT 1, origin_purok VARCHAR(80) NULL, shelter_id INT UNSIGNED NULL,
 displaced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, returned_at DATETIME NULL, notes TEXT NULL,
 FOREIGN KEY (shelter_id) REFERENCES shelters(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS relief_distributions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference_no VARCHAR(40) NOT NULL UNIQUE, household_code VARCHAR(40) NULL,
 recipient_name VARCHAR(160) NOT NULL, purok VARCHAR(80) NULL, package_type VARCHAR(120) NOT NULL, quantity INT UNSIGNED NOT NULL DEFAULT 1,
 distributed_by INT UNSIGNED NULL, signature_name VARCHAR(160) NULL, distributed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_relief_claim(household_code, package_type), FOREIGN KEY (distributed_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS health_records (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, resident_id INT UNSIGNED NULL, patient_name VARCHAR(180) NOT NULL,
 service_type ENUM('Immunization','Maternal checkup','Consultation','Medicine','Other') NOT NULL DEFAULT 'Consultation',
 service_date DATE NOT NULL, details TEXT NULL, health_worker VARCHAR(120) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS medicines (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(140) NOT NULL, category VARCHAR(100) NULL, quantity INT UNSIGNED NOT NULL DEFAULT 0,
 unit VARCHAR(30) NOT NULL DEFAULT 'units', reorder_level INT UNSIGNED NOT NULL DEFAULT 10, expiry_date DATE NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS aid_distributions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference_no VARCHAR(40) NOT NULL UNIQUE, recipient_name VARCHAR(180) NOT NULL,
 household_code VARCHAR(40) NULL, aid_program VARCHAR(140) NOT NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0,
 assistance_date DATE NOT NULL, signed_by VARCHAR(160) NULL, notes TEXT NULL, created_by INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, username VARCHAR(80) NULL, action VARCHAR(80) NOT NULL,
 entity VARCHAR(80) NOT NULL, entity_id VARCHAR(80) NULL, details JSON NULL, ip_address VARCHAR(45) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_audit_created(created_at), INDEX idx_audit_entity(entity, entity_id)
);
CREATE TABLE IF NOT EXISTS announcements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, body TEXT NOT NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NULL, location VARCHAR(180) NULL,
 author_id INT UNSIGNED NULL, author_role ENUM('admin','captain','secretary','health_worker','tanod','als_teacher') NOT NULL,
 status ENUM('Published','Cancelled') NOT NULL DEFAULT 'Published',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_announcements_start(starts_at), FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Activity logs are append-only';
CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Activity logs are append-only';

INSERT INTO shelters (name,location,capacity,occupied,status) SELECT 'Evacuation Center 1','Purok 2',180,0,'Open' WHERE NOT EXISTS (SELECT 1 FROM shelters);
INSERT INTO shelters (name,location,capacity,occupied,status) SELECT 'Evacuation Center 2','Purok 1',90,0,'Open' WHERE (SELECT COUNT(*) FROM shelters)=1;
INSERT INTO medicines (name,category,quantity,unit,reorder_level,expiry_date) SELECT 'Paracetamol 500mg','Analgesic',240,'tablets',50,'2027-10-31' WHERE NOT EXISTS (SELECT 1 FROM medicines);
INSERT INTO medicines (name,category,quantity,unit,reorder_level,expiry_date) SELECT 'Amoxicillin 250mg','Antibiotic',18,'capsules',30,'2027-03-30' WHERE (SELECT COUNT(*) FROM medicines)=1;
