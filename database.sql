CREATE TABLE blood_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL
);
INSERT INTO blood_groups (name) VALUES
('A+'), ('A-'), ('B+'), ('B-'),
('O+'), ('O-'), ('AB+'), ('AB-');

CREATE TABLE donors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255),
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255) NOT NULL,
    blood_group_id INT NOT NULL,
    approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id)
);

CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address VARCHAR(255) NOT NULL,
    blood_group_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id)
);

    ALTER TABLE donors ADD last_donation_date DATE NULL;
