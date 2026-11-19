-- ============================================================================
-- CIVITAS: BARANGAY RESIDENT REGISTRY AND DOCUMENT REQUEST SYSTEM
-- Full setup: schema, sample data (San Francisco, Legazpi City, Albay),
-- view, stored procedures, and triggers.
--
-- Needs MariaDB 10.2+ (XAMPP) or MySQL 8.0.16+, so CHECK constraints are enforced.
-- Import as UTF-8. WARNING: the first line erases the old database.
-- ============================================================================

DROP DATABASE IF EXISTS `civitas_brgyregistry`;
CREATE DATABASE `civitas_brgyregistry`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `civitas_brgyregistry`;

-- ----------------------------------------------------------------------------
-- TABLES
-- ----------------------------------------------------------------------------

CREATE TABLE `Barangay` (
  `barangay_id` INT NOT NULL AUTO_INCREMENT,
  `barangay_name` VARCHAR(100) NOT NULL,
  `municipality` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NULL DEFAULT NULL,
  `province` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`barangay_id`)
) ENGINE = InnoDB;

CREATE TABLE `Purok` (
  `purok_id` INT NOT NULL AUTO_INCREMENT,
  `barangay_id` INT NOT NULL,
  `purok_name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`purok_id`),
  UNIQUE INDEX `uq_purok_name` (`barangay_id`, `purok_name`),
  CONSTRAINT `fk_purok_barangay`
    FOREIGN KEY (`barangay_id`) REFERENCES `Barangay` (`barangay_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `Household` (
  `household_id` INT NOT NULL AUTO_INCREMENT,
  `purok_id` INT NOT NULL,
  `house_number` VARCHAR(20) NOT NULL,
  `street` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`household_id`),
  UNIQUE INDEX `uq_household_address` (`purok_id`, `house_number`, `street`),
  CONSTRAINT `fk_household_purok`
    FOREIGN KEY (`purok_id`) REFERENCES `Purok` (`purok_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `Resident` (
  `resident_id` INT NOT NULL AUTO_INCREMENT,
  `household_id` INT NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) NOT NULL DEFAULT '',
  `birth_date` DATE NOT NULL,
  `contact_number` VARCHAR(20) NULL DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  `date_registered` DATE NOT NULL DEFAULT (CURRENT_DATE),
  PRIMARY KEY (`resident_id`),
  UNIQUE INDEX `uq_resident_identity` (`last_name`, `first_name`, `middle_name`, `birth_date`),
  CONSTRAINT `fk_resident_household`
    FOREIGN KEY (`household_id`) REFERENCES `Household` (`household_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_resident_status`
    CHECK (`status` IN ('Active', 'Inactive', 'Transferred', 'Deceased'))
) ENGINE = InnoDB;

CREATE TABLE `DocumentType` (
  `doctype_id` INT NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(50) NOT NULL,
  `fee` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`doctype_id`),
  UNIQUE INDEX `uq_doctype_name` (`type_name`),
  CONSTRAINT `chk_doctype_fee` CHECK (`fee` >= 0)
) ENGINE = InnoDB;

CREATE TABLE `DocumentRequest` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `resident_id` INT NOT NULL,
  `doctype_id` INT NOT NULL,
  `purpose` VARCHAR(150) NOT NULL,
  `fee_charged` DECIMAL(10,2) NOT NULL,
  `request_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
  `release_date` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  CONSTRAINT `fk_request_resident`
    FOREIGN KEY (`resident_id`) REFERENCES `Resident` (`resident_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_request_doctype`
    FOREIGN KEY (`doctype_id`) REFERENCES `DocumentType` (`doctype_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_request_fee_charged` CHECK (`fee_charged` >= 0),
  CONSTRAINT `chk_request_status` CHECK (`status` IN ('Pending', 'Processing', 'Released')),
  CONSTRAINT `chk_request_release_date` CHECK (`release_date` IS NULL OR `release_date` >= `request_date`)
) ENGINE = InnoDB;

CREATE TABLE `Payment` (
  `payment_id` INT NOT NULL AUTO_INCREMENT,
  `request_id` INT NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  PRIMARY KEY (`payment_id`),
  CONSTRAINT `fk_payment_request`
    FOREIGN KEY (`request_id`) REFERENCES `DocumentRequest` (`request_id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_payment_amount` CHECK (`amount_paid` > 0)
) ENGINE = InnoDB;

-- ----------------------------------------------------------------------------
-- SAMPLE DATA
-- ----------------------------------------------------------------------------

-- 1. Barangay (1 row)
INSERT INTO `Barangay` (`barangay_id`, `barangay_name`, `municipality`, `district`, `province`) VALUES
  (1, 'San Francisco', 'Legazpi City', '2nd District', 'Albay');

-- 2. Purok (7 rows)
INSERT INTO `Purok` (`purok_id`, `barangay_id`, `purok_name`) VALUES
  (1, 1, 'Purok 1'),
  (2, 1, 'Purok 2'),
  (3, 1, 'Purok 3'),
  (4, 1, 'Purok 4'),
  (5, 1, 'Purok 5'),
  (6, 1, 'Purok 6'),
  (7, 1, 'Purok 7');

-- 3. Household (28 rows)
INSERT INTO `Household` (`household_id`, `purok_id`, `house_number`, `street`) VALUES
  (1, 1, '16', 'Sitio Riverside'),
  (2, 2, '66', 'Quezon Street'),
  (3, 3, '115B', 'Sitio Maligaya'),
  (4, 4, '72A', 'Sitio Maligaya'),
  (5, 5, '71A', 'Sitio Maligaya'),
  (6, 6, '57', 'Rizal Street'),
  (7, 7, '79', 'Mabini Street'),
  (8, 1, '37', 'Sitio Looban'),
  (9, 2, '2A', 'Sitio Centro'),
  (10, 3, '41', 'Luna Street'),
  (11, 4, '33', 'Del Pilar Street'),
  (12, 5, '104A', 'Sitio Mayon View'),
  (13, 6, '66', 'Sitio Mayon View'),
  (14, 7, '44', 'Sitio Riverside'),
  (15, 1, '69', 'Bonifacio Street'),
  (16, 2, '59', 'Sitio Centro'),
  (17, 3, '91B', 'Bonifacio Street'),
  (18, 4, '4', 'Del Pilar Street'),
  (19, 5, '60A', 'Mabini Street'),
  (20, 6, '52B', 'Sitio Riverside'),
  (21, 7, '64', 'Luna Street'),
  (22, 1, '65B', 'Sitio Looban'),
  (23, 2, '51B', 'Bonifacio Street'),
  (24, 3, '109A', 'Sitio Maligaya'),
  (25, 4, '119A', 'Sitio Riverside'),
  (26, 5, '67', 'Sitio Maligaya'),
  (27, 6, '29', 'Rizal Street'),
  (28, 7, '91B', 'Sitio Centro');

-- 4. Resident (100 rows)
INSERT INTO `Resident` (`resident_id`, `household_id`, `last_name`, `first_name`, `middle_name`, `birth_date`, `contact_number`, `status`, `date_registered`) VALUES
  (1, 9, 'Bondad', 'Juan', 'Belmonte', '1954-09-13', '09182488619', 'Active', '2022-10-23'),
  (2, 2, 'Nacario', 'Angelica', 'Dioneda', '2003-03-21', '09882834728', 'Active', '2021-09-13'),
  (3, 7, 'Barcelona', 'Marites', 'Castillo', '1960-03-17', '09689832982', 'Active', '2026-08-11'),
  (4, 6, 'Abella', 'Grace', 'Gonzales', '1973-12-24', '09044224852', 'Active', '2019-09-08'),
  (5, 2, 'Nacario', 'Michelle', 'Abella', '1981-05-27', '09269780438', 'Active', '2019-08-08'),
  (6, 1, 'Arcilla', 'Rico', 'Gonzales', '2016-12-18', '09176252807', 'Active', '2023-01-31'),
  (7, 28, 'Realubit', 'Joel', 'Gonzales', '1983-01-06', '09450283166', 'Active', '2022-12-16'),
  (8, 12, 'Abella', 'Shiela', 'Rivera', '2003-06-18', '09373528667', 'Transferred', '2021-03-27'),
  (9, 19, 'Capistrano', 'Marites', 'Ordoñez', '2014-07-08', '09478971331', 'Active', '2023-01-27'),
  (10, 15, 'Briones', 'Lorna', 'Lopez', '2007-02-04', '09921236360', 'Inactive', '2019-11-19'),
  (11, 13, 'Gonzales', 'Mark', 'Rivera', '2019-09-05', '09445607874', 'Active', '2025-10-23'),
  (12, 19, 'Capistrano', 'Noel', 'Abella', '1988-11-21', NULL, 'Active', '2020-07-19'),
  (13, 15, 'Briones', 'Aileen', 'Marasigan', '2009-06-25', '09576990854', 'Active', '2020-08-12'),
  (14, 25, 'Paraiso', 'Joel', 'Fernandez', '2015-09-26', NULL, 'Active', '2026-08-25'),
  (15, 1, 'Arcilla', 'Daisy', 'Ordoñez', '1988-02-21', '09144019018', 'Active', '2024-11-22'),
  (16, 15, 'Briones', 'Jocelyn', 'Abella', '1950-01-04', '09979780252', 'Active', '2023-05-02'),
  (17, 14, 'Ordoñez', 'Michelle', 'Lopez', '1950-09-11', '09400198152', 'Active', '2020-01-12'),
  (18, 23, 'Tabuzo', 'Lorna', 'Villanueva', '1965-09-26', '09736385607', 'Active', '2022-06-11'),
  (19, 17, 'Capistrano', 'Marites', 'Calleja', '2005-05-25', '09197967807', 'Transferred', '2023-12-02'),
  (20, 1, 'Arcilla', 'Allan', 'Esplana', '1989-07-16', '09243854690', 'Active', '2019-05-20'),
  (21, 24, 'Abella', 'Rico', 'Balane', '2009-09-24', '09828195391', 'Active', '2022-12-18'),
  (22, 4, 'Paraiso', 'Michelle', 'Villanueva', '1985-02-21', '09166033042', 'Active', '2023-07-19'),
  (23, 22, 'Bondad', 'Rico', 'Tabuzo', '1980-01-08', NULL, 'Active', '2023-03-13'),
  (24, 9, 'Bondad', 'Paolo', 'Esplana', '1999-08-28', NULL, 'Active', '2020-11-17'),
  (25, 6, 'Abella', 'Arnel', 'Rellon', '1970-11-19', '09780578037', 'Active', '2026-01-28'),
  (26, 10, 'Rivera', 'Rhea', 'Tabuzo', '1983-07-18', '09430490741', 'Active', '2020-11-20'),
  (27, 24, 'Abella', 'Bryan', 'Rivera', '1972-09-23', '09370853867', 'Active', '2024-10-17'),
  (28, 1, 'Arcilla', 'Lorna', 'Rivera', '2015-11-10', '09066817922', 'Active', '2019-12-25'),
  (29, 17, 'Capistrano', 'Rhea', 'Villanueva', '1983-01-24', '09564271051', 'Active', '2021-03-27'),
  (30, 21, 'Calleja', 'Jose', 'Nacario', '2008-01-22', '09983725278', 'Inactive', '2020-06-17'),
  (31, 2, 'Nacario', 'Paolo', 'Cordial', '1982-12-16', NULL, 'Active', '2023-04-04'),
  (32, 19, 'Capistrano', 'Daisy', 'Abella', '2011-03-06', '09958519043', 'Active', '2024-08-01'),
  (33, 26, 'Gonzales', 'Arnel', 'Ordoñez', '1966-05-02', '09999248005', 'Active', '2021-09-29'),
  (34, 27, 'Realubit', 'Jennifer', 'Barcelona', '1993-07-12', '09729871120', 'Active', '2020-02-02'),
  (35, 10, 'Rivera', 'Rodel', 'Castillo', '1983-03-06', '09407942607', 'Active', '2020-08-27'),
  (36, 16, 'Nacario', 'Marites', 'Marasigan', '1993-06-20', '09716477447', 'Active', '2021-07-20'),
  (37, 5, 'Abella', 'Mary Ann', 'Alcantara', '1977-04-04', '09219803346', 'Active', '2022-04-29'),
  (38, 13, 'Gonzales', 'Joel', 'Arcilla', '2006-02-14', '09213629586', 'Active', '2021-07-09'),
  (39, 24, 'Abella', 'Rosa', 'Buenaventura', '1968-11-06', '09864573284', 'Active', '2022-05-25'),
  (40, 6, 'Abella', 'Jericho', 'Marasigan', '2005-08-19', '09692767094', 'Active', '2021-03-11'),
  (41, 2, 'Nacario', 'Paolo', 'Briones', '2018-08-12', '09858463884', 'Active', '2019-05-29'),
  (42, 21, 'Calleja', 'Shiela', 'Salazar', '1956-09-02', '09556830686', 'Active', '2026-06-16'),
  (43, 5, 'Abella', 'Rosa', 'Balane', '2015-05-22', '09538965013', 'Active', '2023-05-06'),
  (44, 8, 'Calleja', 'Arnel', 'Marasigan', '1957-08-15', '09268350811', 'Active', '2023-06-01'),
  (45, 23, 'Tabuzo', 'Christian', 'Nacario', '1962-06-12', '09581635401', 'Deceased', '2020-05-19'),
  (46, 20, 'Ordoñez', 'Paolo', 'Villanueva', '1984-11-18', '09611387592', 'Active', '2020-09-09'),
  (47, 10, 'Rivera', 'Angelo', 'Capistrano', '2005-07-10', '09718621076', 'Active', '2021-08-27'),
  (48, 8, 'Calleja', 'Jericho', 'Arcilla', '1965-08-04', '09510626148', 'Active', '2019-09-09'),
  (49, 10, 'Rivera', 'Bryan', 'Gonzales', '1954-10-20', '09202994461', 'Active', '2020-03-22'),
  (50, 4, 'Paraiso', 'Paolo', 'Realubit', '2017-06-04', '09161994569', 'Active', '2021-07-16'),
  (51, 7, 'Barcelona', 'Allan', 'Alcantara', '1998-02-13', '09640894874', 'Active', '2024-11-15'),
  (52, 11, 'Barcelona', 'Bryan', 'Buenaventura', '1966-08-16', '09972584441', 'Inactive', '2021-12-03'),
  (53, 18, 'Lopez', 'Mark', 'Gonzales', '1951-09-11', '09551566979', 'Inactive', '2021-03-22'),
  (54, 7, 'Barcelona', 'Jun', 'Dioneda', '2014-08-11', '09567959650', 'Active', '2023-03-22'),
  (55, 16, 'Nacario', 'Carlo', 'Ordoñez', '2018-07-22', '09547056709', 'Active', '2022-12-30'),
  (56, 14, 'Ordoñez', 'Joel', 'Briones', '2000-05-11', '09801564644', 'Active', '2019-04-05'),
  (57, 22, 'Bondad', 'Jose', 'Salazar', '1968-02-12', NULL, 'Inactive', '2019-08-25'),
  (58, 20, 'Ordoñez', 'Maria', 'Abella', '1984-03-08', '09987198890', 'Active', '2023-02-12'),
  (59, 23, 'Tabuzo', 'Paolo', 'Barcelona', '2019-10-10', '09631111960', 'Active', '2023-01-06'),
  (60, 25, 'Paraiso', 'Joy', 'Castillo', '1956-10-02', '09465873064', 'Active', '2019-04-09'),
  (61, 4, 'Paraiso', 'Jose', 'Almonte', '2015-03-15', '09829601796', 'Active', '2020-09-04'),
  (62, 8, 'Calleja', 'Aileen', 'Marasigan', '2011-10-11', '09204126347', 'Active', '2024-05-11'),
  (63, 25, 'Paraiso', 'Mark', 'Tabuzo', '1956-01-18', '09401376156', 'Active', '2021-04-21'),
  (64, 7, 'Barcelona', 'Juan', 'Abella', '2009-11-12', '09059148335', 'Active', '2019-11-02'),
  (65, 7, 'Barcelona', 'Rico', 'Cordial', '1958-10-10', '09137852223', 'Active', '2020-03-31'),
  (66, 11, 'Barcelona', 'Bryan', 'Bondad', '2006-02-28', '09975332571', 'Active', '2020-05-05'),
  (67, 15, 'Briones', 'Jericho', 'Cordial', '1954-01-14', '09808534704', 'Active', '2022-03-21'),
  (68, 22, 'Bondad', 'Joy', 'Paraiso', '1978-12-28', '09134146175', 'Transferred', '2023-05-28'),
  (69, 21, 'Calleja', 'Pedro', 'Salazar', '1958-11-19', '09461338005', 'Deceased', '2019-07-13'),
  (70, 12, 'Abella', 'Maria', 'Capistrano', '1959-11-06', NULL, 'Active', '2022-07-28'),
  (71, 18, 'Lopez', 'Jericho', 'Fernandez', '2001-02-02', '09589305977', 'Active', '2024-08-05'),
  (72, 16, 'Nacario', 'Juan', 'Zafe', '1991-12-20', '09384885681', 'Active', '2022-11-02'),
  (73, 12, 'Abella', 'Joel', 'Cordial', '1955-08-21', '09090727615', 'Deceased', '2019-03-27'),
  (74, 6, 'Abella', 'Marites', 'Ordoñez', '2014-05-03', '09363343701', 'Active', '2020-05-13'),
  (75, 8, 'Calleja', 'Shiela', 'Belmonte', '1955-03-19', '09262394246', 'Active', '2024-11-30'),
  (76, 5, 'Abella', 'Joel', 'Abella', '2001-02-17', '09316653989', 'Active', '2023-11-22'),
  (77, 11, 'Barcelona', 'Rhea', 'Rivera', '2012-02-17', '09745358134', 'Active', '2025-06-04'),
  (78, 25, 'Paraiso', 'Grace', 'Calleja', '2001-02-05', '09349677044', 'Active', '2020-12-21'),
  (79, 4, 'Paraiso', 'Rodel', 'Cordial', '1982-05-15', '09751688656', 'Active', '2022-02-04'),
  (80, 3, 'Paraiso', 'Miguel', 'Rivera', '1981-08-17', NULL, 'Transferred', '2022-02-21'),
  (81, 3, 'Paraiso', 'Mark', 'Dioneda', '2010-06-15', '09235953494', 'Active', '2019-08-14'),
  (82, 26, 'Gonzales', 'Aileen', 'Gonzales', '1965-03-10', '09474781824', 'Active', '2020-11-28'),
  (83, 21, 'Calleja', 'Rhea', 'Rellon', '2006-06-21', '09195680801', 'Active', '2021-01-04'),
  (84, 17, 'Capistrano', 'Paolo', 'Buenaventura', '1982-11-09', '09052007009', 'Active', '2019-08-13'),
  (85, 13, 'Gonzales', 'Angelica', 'Briones', '1962-10-10', '09927330570', 'Active', '2019-07-28'),
  (86, 9, 'Bondad', 'Jennifer', 'Zafe', '1952-01-21', '09778166114', 'Active', '2024-11-21'),
  (87, 19, 'Capistrano', 'Cherry', 'Cordial', '1990-10-05', NULL, 'Active', '2019-12-09'),
  (88, 20, 'Ordoñez', 'Marites', 'Briones', '2012-09-16', NULL, 'Active', '2021-01-30'),
  (89, 26, 'Gonzales', 'Grace', 'Belmonte', '2007-06-05', '09004030042', 'Active', '2024-09-26'),
  (90, 18, 'Lopez', 'Rowena', 'Marasigan', '1947-12-04', '09671552930', 'Active', '2023-12-20'),
  (91, 17, 'Capistrano', 'Ana', 'Buenaventura', '2002-05-23', '09708129770', 'Active', '2019-12-01'),
  (92, 13, 'Gonzales', 'Jericho', 'Gonzales', '1963-02-20', '09923981337', 'Active', '2020-12-10'),
  (93, 5, 'Abella', 'Pedro', 'Marasigan', '1978-10-18', '09186158129', 'Active', '2019-09-09'),
  (94, 23, 'Tabuzo', 'Cherry', 'Villanueva', '2018-07-05', '09470675548', 'Active', '2025-08-31'),
  (95, 11, 'Barcelona', 'Shiela', 'Esplana', '1969-03-11', '09351676825', 'Active', '2023-06-08'),
  (96, 22, 'Bondad', 'Dennis', 'Calleja', '2011-04-18', '09481241726', 'Active', '2025-07-10'),
  (97, 9, 'Bondad', 'Rhea', 'Belmonte', '2019-01-05', '09662829092', 'Active', '2021-12-27'),
  (98, 14, 'Ordoñez', 'Rodel', 'Nacario', '1953-06-24', '09422042628', 'Active', '2020-07-03'),
  (99, 3, 'Paraiso', 'Ana', 'Salazar', '1983-09-22', '09095060188', 'Active', '2021-07-05'),
  (100, 27, 'Realubit', 'Jun', 'Nacario', '1990-01-28', NULL, 'Active', '2024-01-17');

-- 5. DocumentType (3 rows)
INSERT INTO `DocumentType` (`doctype_id`, `type_name`, `fee`) VALUES
  (1, 'Barangay Clearance', 50.00),
  (2, 'Certificate of Residency', 30.00),
  (3, 'Certificate of Indigency', 0.00);

-- 6. DocumentRequest (72 rows)
INSERT INTO `DocumentRequest` (`request_id`, `resident_id`, `doctype_id`, `purpose`, `fee_charged`, `request_date`, `status`, `release_date`) VALUES
  (1, 92, 2, 'Voter registration', 30.00, '2025-01-12', 'Released', '2025-01-15'),
  (2, 35, 1, 'School requirement', 50.00, '2025-01-28', 'Released', '2025-02-01'),
  (3, 17, 1, 'Police clearance', 50.00, '2025-02-12', 'Processing', NULL),
  (4, 35, 2, 'School enrollment', 30.00, '2025-02-15', 'Released', '2025-02-17'),
  (5, 65, 3, 'Legal aid', 0.00, '2025-02-15', 'Released', '2025-02-19'),
  (6, 83, 2, 'Bank account opening', 30.00, '2025-02-22', 'Released', '2025-02-25'),
  (7, 99, 2, 'Passport application', 30.00, '2025-03-14', 'Released', '2025-03-20'),
  (8, 93, 1, 'Loan application', 50.00, '2025-04-01', 'Released', '2025-04-07'),
  (9, 100, 1, 'Employment', 50.00, '2025-04-01', 'Released', '2025-04-06'),
  (10, 27, 2, 'SSS/PhilHealth requirement', 30.00, '2025-04-08', 'Released', '2025-04-11'),
  (11, 12, 1, 'Police clearance', 50.00, '2025-04-18', 'Released', '2025-04-21'),
  (12, 33, 1, 'Loan application', 50.00, '2025-04-22', 'Released', '2025-04-28'),
  (13, 93, 1, 'Scholarship', 50.00, '2025-04-22', 'Released', '2025-04-28'),
  (14, 58, 2, 'SSS/PhilHealth requirement', 30.00, '2025-04-29', 'Released', '2025-05-04'),
  (15, 48, 2, 'School enrollment', 30.00, '2025-04-30', 'Released', '2025-05-04'),
  (16, 76, 1, 'Police clearance', 50.00, '2025-05-03', 'Released', '2025-05-06'),
  (17, 47, 2, 'Passport application', 30.00, '2025-05-11', 'Released', '2025-05-16'),
  (18, 24, 1, 'Loan application', 50.00, '2025-05-14', 'Released', '2025-05-18'),
  (19, 4, 1, 'Employment', 50.00, '2025-05-29', 'Released', '2025-06-05'),
  (20, 58, 1, 'Scholarship', 50.00, '2025-06-05', 'Released', '2025-06-11'),
  (21, 89, 2, 'Voter registration', 30.00, '2025-06-25', 'Pending', NULL),
  (22, 16, 3, 'Scholarship', 0.00, '2025-07-26', 'Released', '2025-07-31'),
  (23, 44, 2, 'Employment', 30.00, '2025-07-28', 'Released', '2025-07-29'),
  (24, 79, 1, 'Scholarship', 50.00, '2025-07-29', 'Released', '2025-07-31'),
  (25, 26, 2, 'Employment', 30.00, '2025-08-07', 'Released', '2025-08-14'),
  (26, 27, 3, 'Legal aid', 0.00, '2025-08-08', 'Pending', NULL),
  (27, 49, 1, 'Business permit', 50.00, '2025-08-15', 'Processing', NULL),
  (28, 51, 1, 'Business permit', 50.00, '2025-08-20', 'Released', '2025-08-26'),
  (29, 16, 1, 'Loan application', 50.00, '2025-08-21', 'Released', '2025-08-23'),
  (30, 67, 1, 'Travel', 50.00, '2025-09-17', 'Pending', NULL),
  (31, 18, 2, 'School enrollment', 30.00, '2025-10-06', 'Released', '2025-10-11'),
  (32, 84, 1, 'Travel', 50.00, '2025-10-16', 'Released', '2025-10-19'),
  (33, 99, 1, 'Police clearance', 50.00, '2025-11-14', 'Released', '2025-11-19'),
  (34, 83, 3, 'Medical assistance', 0.00, '2025-11-23', 'Released', '2025-11-30'),
  (35, 29, 3, 'Legal aid', 0.00, '2025-12-18', 'Processing', NULL),
  (36, 79, 1, 'Police clearance', 50.00, '2025-12-29', 'Pending', NULL),
  (37, 2, 1, 'Loan application', 50.00, '2026-01-04', 'Released', '2026-01-07'),
  (38, 1, 3, 'Medical assistance', 0.00, '2026-01-12', 'Released', '2026-01-12'),
  (39, 58, 2, 'Bank account opening', 30.00, '2026-02-01', 'Released', '2026-02-07'),
  (40, 34, 2, 'Passport application', 30.00, '2026-02-03', 'Released', '2026-02-06'),
  (41, 93, 2, 'Employment', 30.00, '2026-02-10', 'Released', '2026-02-12'),
  (42, 90, 1, 'Business permit', 50.00, '2026-02-18', 'Pending', NULL),
  (43, 90, 2, 'School enrollment', 30.00, '2026-02-24', 'Released', '2026-02-25'),
  (44, 17, 1, 'Business permit', 50.00, '2026-03-04', 'Released', '2026-03-08'),
  (45, 71, 1, 'Loan application', 50.00, '2026-03-14', 'Released', '2026-03-19'),
  (46, 91, 1, 'Employment', 50.00, '2026-03-17', 'Released', '2026-03-21'),
  (47, 12, 1, 'Police clearance', 50.00, '2026-03-23', 'Released', '2026-03-27'),
  (48, 76, 2, 'School enrollment', 30.00, '2026-03-24', 'Released', '2026-03-31'),
  (49, 15, 1, 'Loan application', 50.00, '2026-04-12', 'Pending', NULL),
  (50, 1, 1, 'School requirement', 50.00, '2026-05-04', 'Released', '2026-05-09'),
  (51, 93, 1, 'Business permit', 50.00, '2026-05-13', 'Pending', NULL),
  (52, 95, 3, 'Educational assistance', 0.00, '2026-06-23', 'Released', '2026-06-29'),
  (53, 37, 3, 'Legal aid', 0.00, '2026-06-26', 'Released', '2026-06-28'),
  (54, 25, 1, 'School requirement', 50.00, '2026-07-07', 'Released', '2026-07-14'),
  (55, 85, 1, 'Employment', 50.00, '2026-07-13', 'Released', '2026-07-15'),
  (56, 63, 1, 'School requirement', 50.00, '2026-07-14', 'Released', '2026-07-20'),
  (57, 5, 3, 'Scholarship', 0.00, '2026-09-01', 'Released', '2026-09-01'),
  (58, 83, 3, 'Educational assistance', 0.00, '2026-09-08', 'Released', '2026-09-08'),
  (59, 3, 2, 'Bank account opening', 30.00, '2026-09-16', 'Released', '2026-09-18'),
  (60, 33, 2, 'Employment', 30.00, '2026-09-21', 'Pending', NULL),
  (61, 84, 1, 'Employment', 50.00, '2026-10-06', 'Released', '2026-10-12'),
  (62, 17, 1, 'Scholarship', 50.00, '2026-10-23', 'Processing', NULL),
  (63, 3, 2, 'Passport application', 30.00, '2026-11-12', 'Processing', NULL),
  (64, 82, 1, 'Police clearance', 50.00, '2026-11-14', 'Pending', NULL),
  (65, 36, 2, 'Voter registration', 30.00, '2026-11-15', 'Pending', NULL),
  (66, 100, 2, 'Voter registration', 30.00, '2026-11-15', 'Processing', NULL),
  (67, 29, 2, 'Bank account opening', 30.00, '2026-11-16', 'Processing', NULL),
  (68, 79, 3, 'Educational assistance', 0.00, '2026-11-17', 'Pending', NULL),
  (69, 34, 1, 'Travel', 50.00, '2026-11-18', 'Processing', NULL),
  (70, 35, 2, 'School enrollment', 30.00, '2026-11-18', 'Pending', NULL),
  (71, 56, 1, 'Employment', 50.00, '2026-11-18', 'Pending', NULL),
  (72, 93, 1, 'Travel', 50.00, '2026-11-18', 'Released', '2026-11-19');

-- 7. Payment (61 rows)
INSERT INTO `Payment` (`payment_id`, `request_id`, `amount_paid`, `payment_date`) VALUES
  (1, 1, 30.00, '2025-01-15'),
  (2, 2, 50.00, '2025-01-31'),
  (3, 3, 50.00, '2025-02-14'),
  (4, 4, 30.00, '2025-02-15'),
  (5, 6, 30.00, '2025-02-25'),
  (6, 7, 30.00, '2025-03-17'),
  (7, 8, 50.00, '2025-04-02'),
  (8, 9, 50.00, '2025-04-03'),
  (9, 10, 30.00, '2025-04-08'),
  (10, 11, 30.00, '2025-04-18'),
  (11, 11, 20.00, '2025-04-21'),
  (12, 12, 50.00, '2025-04-22'),
  (13, 13, 30.00, '2025-04-25'),
  (14, 13, 20.00, '2025-04-27'),
  (15, 14, 30.00, '2025-05-01'),
  (16, 15, 30.00, '2025-05-02'),
  (17, 16, 50.00, '2025-05-05'),
  (18, 17, 30.00, '2025-05-14'),
  (19, 18, 50.00, '2025-05-14'),
  (20, 19, 50.00, '2025-05-29'),
  (21, 20, 50.00, '2025-06-08'),
  (22, 21, 30.00, '2025-06-28'),
  (23, 23, 30.00, '2025-07-29'),
  (24, 24, 50.00, '2025-07-29'),
  (25, 25, 30.00, '2025-08-09'),
  (26, 27, 20.00, '2025-08-17'),
  (27, 28, 50.00, '2025-08-22'),
  (28, 29, 50.00, '2025-08-22'),
  (29, 30, 20.00, '2025-09-17'),
  (30, 31, 30.00, '2025-10-06'),
  (31, 32, 50.00, '2025-10-19'),
  (32, 33, 50.00, '2025-11-17'),
  (33, 37, 30.00, '2026-01-06'),
  (34, 37, 20.00, '2026-01-07'),
  (35, 39, 30.00, '2026-02-02'),
  (36, 40, 30.00, '2026-02-03'),
  (37, 41, 30.00, '2026-02-12'),
  (38, 43, 30.00, '2026-02-25'),
  (39, 44, 50.00, '2026-03-07'),
  (40, 45, 30.00, '2026-03-16'),
  (41, 45, 20.00, '2026-03-19'),
  (42, 46, 30.00, '2026-03-19'),
  (43, 46, 20.00, '2026-03-21'),
  (44, 47, 50.00, '2026-03-26'),
  (45, 48, 30.00, '2026-03-26'),
  (46, 49, 30.00, '2026-04-12'),
  (47, 49, 20.00, '2026-04-13'),
  (48, 50, 30.00, '2026-05-05'),
  (49, 50, 20.00, '2026-05-07'),
  (50, 54, 50.00, '2026-07-10'),
  (51, 55, 50.00, '2026-07-15'),
  (52, 56, 50.00, '2026-07-17'),
  (53, 59, 30.00, '2026-09-18'),
  (54, 60, 20.00, '2026-09-23'),
  (55, 61, 50.00, '2026-10-07'),
  (56, 62, 50.00, '2026-10-23'),
  (57, 63, 30.00, '2026-11-14'),
  (58, 66, 30.00, '2026-11-16'),
  (59, 64, 30.00, '2026-11-17'),
  (60, 65, 20.00, '2026-11-17'),
  (61, 72, 50.00, '2026-11-19');

-- ----------------------------------------------------------------------------
-- VIEW: fee, total paid, and balance for every request
-- ----------------------------------------------------------------------------
CREATE VIEW `vw_request_balances` AS
SELECT
  dr.request_id,
  dr.resident_id,
  CONCAT(r.last_name, ', ', r.first_name) AS resident_name,
  dt.type_name,
  dr.purpose,
  dr.status,
  dr.request_date,
  dr.release_date,
  dr.fee_charged,
  COALESCE(SUM(p.amount_paid), 0) AS total_paid,
  dr.fee_charged - COALESCE(SUM(p.amount_paid), 0) AS balance
FROM DocumentRequest dr
JOIN Resident r ON r.resident_id = dr.resident_id
JOIN DocumentType dt ON dt.doctype_id = dr.doctype_id
LEFT JOIN Payment p ON p.request_id = dr.request_id
GROUP BY dr.request_id, dr.resident_id, r.last_name, r.first_name, dt.type_name,
         dr.purpose, dr.status, dr.request_date, dr.release_date, dr.fee_charged;

-- ----------------------------------------------------------------------------
-- STORED PROCEDURES, TRIGGERS
-- ----------------------------------------------------------------------------
DELIMITER //

-- Records a payment. Rejects zero or negative amounts and overpayments.
CREATE PROCEDURE `sp_record_payment`(
  IN p_request_id INT,
  IN p_amount DECIMAL(10,2),
  IN p_date DATE
)
BEGIN
  DECLARE v_balance DECIMAL(10,2) DEFAULT NULL;

  SELECT balance INTO v_balance FROM vw_request_balances WHERE request_id = p_request_id;

  IF v_balance IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Request does not exist';
  ELSEIF p_amount <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Amount must be greater than zero';
  ELSEIF p_amount > v_balance THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Payment exceeds remaining balance';
  ELSE
    INSERT INTO Payment (request_id, amount_paid, payment_date)
    VALUES (p_request_id, p_amount, p_date);
    SELECT * FROM vw_request_balances WHERE request_id = p_request_id;
  END IF;
END //

-- Files a request. Only Active residents may file, and the current fee is copied into fee_charged.
CREATE PROCEDURE `sp_create_request`(
  IN p_resident_id INT,
  IN p_doctype_id INT,
  IN p_purpose VARCHAR(150),
  IN p_request_date DATE
)
BEGIN
  DECLARE v_status VARCHAR(20) DEFAULT NULL;
  DECLARE v_fee DECIMAL(10,2) DEFAULT NULL;

  SELECT status INTO v_status FROM Resident WHERE resident_id = p_resident_id;
  SELECT fee INTO v_fee FROM DocumentType WHERE doctype_id = p_doctype_id;

  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resident does not exist';
  ELSEIF v_status <> 'Active' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only Active residents can file a request';
  ELSEIF v_fee IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document type does not exist';
  ELSE
    INSERT INTO DocumentRequest (resident_id, doctype_id, purpose, fee_charged, request_date)
    VALUES (p_resident_id, p_doctype_id, p_purpose, v_fee, p_request_date);
    SELECT LAST_INSERT_ID() AS request_id;
  END IF;
END //

-- Trigger 1: blocks deleting a resident who still has an unreleased request.
CREATE TRIGGER `trg_resident_before_delete`
BEFORE DELETE ON `Resident`
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM DocumentRequest
             WHERE resident_id = OLD.resident_id AND status <> 'Released') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete resident with unreleased requests';
  END IF;
END //

-- Trigger 2: fills in the release date when a request becomes Released.
CREATE TRIGGER `trg_request_before_update`
BEFORE UPDATE ON `DocumentRequest`
FOR EACH ROW
BEGIN
  IF NEW.status = 'Released' AND OLD.status <> 'Released' AND NEW.release_date IS NULL THEN
    SET NEW.release_date = CURRENT_DATE();
  END IF;
END //

DELIMITER ;
