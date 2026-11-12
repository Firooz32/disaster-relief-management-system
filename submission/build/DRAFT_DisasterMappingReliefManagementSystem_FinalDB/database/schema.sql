-- Disaster Mapping & Relief Management System - schema (DDL only)
-- 25 tables with primary keys, foreign keys (ON DELETE / ON UPDATE rules), UNIQUE keys, indexes and CHECK constraints,
-- followed by 8 integrity triggers. Tables are created parents-first, so foreign key checks stay ON.
-- Requires MySQL 8.0.16+ (CHECK constraints, functional index). Load sample_data.sql afterwards.

CREATE DATABASE IF NOT EXISTS `disaster_and_relief_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `disaster_and_relief_management`;

-- 1. area
CREATE TABLE IF NOT EXISTS `area` (
  `Area_ID` int NOT NULL AUTO_INCREMENT,
  `Area_Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Risk_Level` enum('Low','Medium','High','Very High') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Latitude` decimal(9,6) DEFAULT NULL,
  `Longitude` decimal(9,6) DEFAULT NULL,
  PRIMARY KEY (`Area_ID`),
  UNIQUE KEY `uq_area_name_location` (`Area_Name`,`Location`),
  CONSTRAINT `chk_area_lat` CHECK ((`Latitude` between -(90) and 90)),
  CONSTRAINT `chk_area_lng` CHECK ((`Longitude` between -(180) and 180))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. audit_log
CREATE TABLE IF NOT EXISTS `audit_log` (
  `Log_ID` int NOT NULL AUTO_INCREMENT,
  `User_Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Action` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Table_Name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Record_Key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Log_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. emergency_item
CREATE TABLE IF NOT EXISTS `emergency_item` (
  `Item_ID` int NOT NULL AUTO_INCREMENT,
  `Item_Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`Item_ID`),
  UNIQUE KEY `uq_emergency_item_name` (`Item_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. relief_item
CREATE TABLE IF NOT EXISTS `relief_item` (
  `Relief_ID` int NOT NULL AUTO_INCREMENT,
  `Item_Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`Relief_ID`),
  UNIQUE KEY `uq_relief_item_name` (`Item_Name`),
  CONSTRAINT `chk_relief_qty` CHECK ((`Quantity` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. rescue_org
CREATE TABLE IF NOT EXISTS `rescue_org` (
  `Org_ID` int NOT NULL AUTO_INCREMENT,
  `Org_Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Org_Type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`Org_ID`),
  UNIQUE KEY `uq_rescue_org_name` (`Org_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. rescue_org_relief_item (references: relief_item, rescue_org)
CREATE TABLE IF NOT EXISTS `rescue_org_relief_item` (
  `Org_ID` int NOT NULL,
  `Relief_ID` int NOT NULL,
  PRIMARY KEY (`Org_ID`,`Relief_ID`),
  KEY `fk_org_relief_item` (`Relief_ID`),
  CONSTRAINT `fk_org_relief_item` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_org_relief_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. users
CREATE TABLE IF NOT EXISTS `users` (
  `User_ID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password_Hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('admin','editor','viewer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `Failed_Logins` int NOT NULL DEFAULT '0',
  `Locked_Until` datetime DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `uq_users_username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. affected_person (references: area)
CREATE TABLE IF NOT EXISTS `affected_person` (
  `Person_ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Family_Size` int NOT NULL DEFAULT '1',
  `Contact` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Area_ID` int DEFAULT NULL,
  PRIMARY KEY (`Person_ID`),
  KEY `fk_person_area` (`Area_ID`),
  CONSTRAINT `fk_person_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_person_family` CHECK ((`Family_Size` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. affected_person_emergency_item (references: emergency_item, affected_person)
CREATE TABLE IF NOT EXISTS `affected_person_emergency_item` (
  `Person_ID` int NOT NULL,
  `Item_ID` int NOT NULL,
  PRIMARY KEY (`Person_ID`,`Item_ID`),
  KEY `fk_person_item_item` (`Item_ID`),
  CONSTRAINT `fk_person_item_item` FOREIGN KEY (`Item_ID`) REFERENCES `emergency_item` (`Item_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_person_item_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. disaster (references: users)
CREATE TABLE IF NOT EXISTS `disaster` (
  `Disaster_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_Type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Disaster_Date` date NOT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Status` enum('Reported','Active','Under control','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Reported',
  `Incident_Lead_ID` int DEFAULT NULL,
  `Status_Changed_At` datetime DEFAULT NULL,
  `Closed_At` datetime DEFAULT NULL,
  PRIMARY KEY (`Disaster_ID`),
  KEY `fk_disaster_lead` (`Incident_Lead_ID`),
  CONSTRAINT `fk_disaster_lead` FOREIGN KEY (`Incident_Lead_ID`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. disaster_affected_person (references: disaster, affected_person)
CREATE TABLE IF NOT EXISTS `disaster_affected_person` (
  `Disaster_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Person_ID`),
  KEY `fk_disaster_person_person` (`Person_ID`),
  CONSTRAINT `fk_disaster_person_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_person_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. disaster_area (references: area, disaster)
CREATE TABLE IF NOT EXISTS `disaster_area` (
  `Disaster_ID` int NOT NULL,
  `Area_ID` int NOT NULL,
  `Relief_Shortage` enum('No','Yes') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  PRIMARY KEY (`Disaster_ID`,`Area_ID`),
  KEY `fk_disaster_area_area` (`Area_ID`),
  CONSTRAINT `fk_disaster_area_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_area_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. disaster_rescue_org (references: disaster, rescue_org)
CREATE TABLE IF NOT EXISTS `disaster_rescue_org` (
  `Disaster_ID` int NOT NULL,
  `Org_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Org_ID`),
  KEY `fk_disaster_rescue_org` (`Org_ID`),
  CONSTRAINT `fk_disaster_rescue_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_rescue_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. disaster_track (references: disaster)
CREATE TABLE IF NOT EXISTS `disaster_track` (
  `Track_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Point_Time` datetime NOT NULL,
  `Point_Type` enum('Actual','Forecast') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Actual',
  `Latitude` decimal(9,6) NOT NULL,
  `Longitude` decimal(9,6) NOT NULL,
  `Radius_Km` int NOT NULL DEFAULT '25',
  `Wind_Speed_Kmh` int DEFAULT NULL,
  PRIMARY KEY (`Track_ID`),
  UNIQUE KEY `uq_track_disaster_time` (`Disaster_ID`,`Point_Time`),
  CONSTRAINT `fk_track_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_track_lat` CHECK ((`Latitude` between -(90) and 90)),
  CONSTRAINT `chk_track_lng` CHECK ((`Longitude` between -(180) and 180)),
  CONSTRAINT `chk_track_radius` CHECK ((`Radius_Km` > 0)),
  CONSTRAINT `chk_track_wind` CHECK ((`Wind_Speed_Kmh` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. early_warning (references: disaster)
CREATE TABLE IF NOT EXISTS `early_warning` (
  `Alert_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Alert_Type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Signal_Level` tinyint DEFAULT NULL,
  `Alert_DateTime` datetime NOT NULL,
  `Message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`Alert_ID`),
  KEY `fk_early_warning_disaster` (`Disaster_ID`),
  CONSTRAINT `fk_early_warning_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_warning_signal` CHECK ((`Signal_Level` between 1 and 10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. incident_task (references: users, disaster)
CREATE TABLE IF NOT EXISTS `incident_task` (
  `Task_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Task_Title` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Assigned_To` int DEFAULT NULL,
  `Due_At` datetime DEFAULT NULL,
  `Status` enum('Open','In progress','Blocked','Done') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Open',
  `Created_By` int NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Task_ID`),
  KEY `fk_task_disaster` (`Disaster_ID`),
  KEY `fk_task_assigned` (`Assigned_To`),
  KEY `fk_task_creator` (`Created_By`),
  KEY `ix_task_due_status` (`Status`,`Due_At`),
  CONSTRAINT `fk_task_assigned` FOREIGN KEY (`Assigned_To`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_task_creator` FOREIGN KEY (`Created_By`) REFERENCES `users` (`User_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_task_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. incident_update (references: area, disaster, users)
CREATE TABLE IF NOT EXISTS `incident_update` (
  `Update_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Area_ID` int DEFAULT NULL,
  `Update_Type` enum('Situation','Shelter','Medical','Supplies','Safety') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Summary` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Observed_At` datetime NOT NULL,
  `Reported_By` int NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Update_ID`),
  KEY `ix_update_disaster_time` (`Disaster_ID`,`Observed_At`),
  KEY `fk_update_area` (`Area_ID`),
  KEY `fk_update_reporter` (`Reported_By`),
  CONSTRAINT `fk_update_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_update_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_update_reporter` FOREIGN KEY (`Reported_By`) REFERENCES `users` (`User_ID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. relief_batch (references: users, relief_item)
CREATE TABLE IF NOT EXISTS `relief_batch` (
  `Batch_ID` int NOT NULL AUTO_INCREMENT,
  `Relief_ID` int NOT NULL,
  `Batch_Code` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Quantity_Received` int NOT NULL,
  `Received_At` date DEFAULT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `Source` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Storage_Location` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Received_By` int DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Batch_ID`),
  UNIQUE KEY `uq_relief_batch_code` (`Batch_Code`),
  KEY `fk_batch_relief` (`Relief_ID`),
  KEY `fk_batch_receiver` (`Received_By`),
  KEY `ix_batch_expiry` (`Expiry_Date`),
  CONSTRAINT `fk_batch_receiver` FOREIGN KEY (`Received_By`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_batch_relief` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_batch_dates` CHECK (((`Expiry_Date` is null) or (`Received_At` is null) or (`Expiry_Date` >= `Received_At`))),
  CONSTRAINT `chk_batch_quantity` CHECK ((`Quantity_Received` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. shelter (references: area, users)
CREATE TABLE IF NOT EXISTS `shelter` (
  `Shelter_ID` int NOT NULL AUTO_INCREMENT,
  `Shelter_Name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Capacity` int NOT NULL,
  `Area_ID` int NOT NULL,
  `Latitude` decimal(9,6) DEFAULT NULL,
  `Longitude` decimal(9,6) DEFAULT NULL,
  `Services` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Accessibility_Notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Capacity_Verified_At` datetime DEFAULT NULL,
  `Capacity_Verified_By` int DEFAULT NULL,
  PRIMARY KEY (`Shelter_ID`),
  UNIQUE KEY `uq_shelter_name` (`Shelter_Name`),
  KEY `fk_shelter_area` (`Area_ID`),
  KEY `fk_shelter_capacity_verifier` (`Capacity_Verified_By`),
  CONSTRAINT `fk_shelter_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_shelter_capacity_verifier` FOREIGN KEY (`Capacity_Verified_By`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_shelter_capacity` CHECK ((`Capacity` > 0)),
  CONSTRAINT `chk_shelter_lat` CHECK ((`Latitude` between -(90) and 90)),
  CONSTRAINT `chk_shelter_lng` CHECK ((`Longitude` between -(180) and 180))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. shelter_assignment (references: disaster, affected_person, shelter)
CREATE TABLE IF NOT EXISTS `shelter_assignment` (
  `Assignment_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  `Shelter_ID` int NOT NULL,
  `Check_In` date NOT NULL,
  `Check_Out` date DEFAULT NULL,
  PRIMARY KEY (`Assignment_ID`),
  UNIQUE KEY `uq_assignment_open_stay` ((if((`Check_Out` is null),`Person_ID`,NULL))),
  KEY `ix_assignment_disaster_person` (`Disaster_ID`,`Person_ID`),
  KEY `fk_assignment_person` (`Person_ID`),
  KEY `fk_assignment_shelter` (`Shelter_ID`),
  CONSTRAINT `fk_assignment_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_assignment_dates` CHECK (((`Check_Out` is null) or (`Check_Out` >= `Check_In`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. shelter_emergency_item (references: emergency_item, shelter)
CREATE TABLE IF NOT EXISTS `shelter_emergency_item` (
  `Shelter_ID` int NOT NULL,
  `Item_ID` int NOT NULL,
  PRIMARY KEY (`Shelter_ID`,`Item_ID`),
  KEY `fk_shelter_item_item` (`Item_ID`),
  CONSTRAINT `fk_shelter_item_item` FOREIGN KEY (`Item_ID`) REFERENCES `emergency_item` (`Item_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shelter_item_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. damage (references: area, disaster)
CREATE TABLE IF NOT EXISTS `damage` (
  `Damage_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Damage_Type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Area_ID` int DEFAULT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `Quantity` int NOT NULL DEFAULT '0',
  `Estimated_Loss` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`Damage_ID`),
  KEY `fk_damage_disaster` (`Disaster_ID`),
  KEY `fk_damage_area` (`Area_ID`),
  CONSTRAINT `fk_damage_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_damage_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_damage_loss` CHECK ((`Estimated_Loss` >= 0)),
  CONSTRAINT `chk_damage_qty` CHECK ((`Quantity` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. disaster_shelter (references: disaster, shelter)
CREATE TABLE IF NOT EXISTS `disaster_shelter` (
  `Disaster_ID` int NOT NULL,
  `Shelter_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Shelter_ID`),
  KEY `fk_disaster_shelter_shelter` (`Shelter_ID`),
  CONSTRAINT `fk_disaster_shelter_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_disaster_shelter_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. relief_distribution (references: relief_batch, disaster, rescue_org, relief_item, shelter)
CREATE TABLE IF NOT EXISTS `relief_distribution` (
  `Distribution_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Relief_ID` int NOT NULL,
  `Batch_ID` int NOT NULL,
  `Shelter_ID` int NOT NULL,
  `Org_ID` int DEFAULT NULL,
  `Quantity` int NOT NULL,
  `Distribution_Date` date NOT NULL,
  PRIMARY KEY (`Distribution_ID`),
  KEY `fk_distribution_disaster` (`Disaster_ID`),
  KEY `fk_distribution_relief` (`Relief_ID`),
  KEY `fk_distribution_batch` (`Batch_ID`),
  KEY `fk_distribution_shelter` (`Shelter_ID`),
  KEY `fk_distribution_org` (`Org_ID`),
  CONSTRAINT `fk_distribution_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `relief_batch` (`Batch_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_distribution_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_distribution_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_distribution_relief` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_distribution_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_distribution_qty` CHECK ((`Quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. relief_recipient (references: relief_distribution, affected_person)
CREATE TABLE IF NOT EXISTS `relief_recipient` (
  `Distribution_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  PRIMARY KEY (`Distribution_ID`,`Person_ID`),
  KEY `fk_recipient_person` (`Person_ID`),
  CONSTRAINT `fk_recipient_distribution` FOREIGN KEY (`Distribution_ID`) REFERENCES `relief_distribution` (`Distribution_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_recipient_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- triggers
-- Database-level integrity rules that foreign keys and CHECK constraints cannot express.
-- They also protect the data when it is changed outside the web pages (phpMyAdmin, scripts).
-- Capacity and stock limits stay in inc/rules.php because they need the friendly messages and the "edit this same row" logic.
-- This file is run by inc/upgrade.php and appended to disaster_and_relief_management.sql. It uses DELIMITER like phpMyAdmin / the mysql client;
-- run_sql_script() in db.php understands it too.

DELIMITER $$

DROP TRIGGER IF EXISTS trg_assignment_ins$$
CREATE TRIGGER trg_assignment_ins BEFORE INSERT ON shelter_assignment FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM disaster_shelter WHERE Disaster_ID = NEW.Disaster_ID AND Shelter_ID = NEW.Shelter_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
ELSEIF NOT EXISTS (SELECT 1 FROM disaster_affected_person WHERE Disaster_ID = NEW.Disaster_ID AND Person_ID = NEW.Person_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That person is not recorded as affected by this disaster. Add them in "Disaster Affected Person" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_assignment_upd$$
CREATE TRIGGER trg_assignment_upd BEFORE UPDATE ON shelter_assignment FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM disaster_shelter WHERE Disaster_ID = NEW.Disaster_ID AND Shelter_ID = NEW.Shelter_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
ELSEIF NOT EXISTS (SELECT 1 FROM disaster_affected_person WHERE Disaster_ID = NEW.Disaster_ID AND Person_ID = NEW.Person_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That person is not recorded as affected by this disaster. Add them in "Disaster Affected Person" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_distribution_ins$$
CREATE TRIGGER trg_distribution_ins BEFORE INSERT ON relief_distribution FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM disaster_shelter WHERE Disaster_ID = NEW.Disaster_ID AND Shelter_ID = NEW.Shelter_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
ELSEIF NEW.Org_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_rescue_org WHERE Disaster_ID = NEW.Disaster_ID AND Org_ID = NEW.Org_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That organisation did not take part in this disaster. Add it in "Disaster Rescue Org" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_distribution_upd$$
CREATE TRIGGER trg_distribution_upd BEFORE UPDATE ON relief_distribution FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM disaster_shelter WHERE Disaster_ID = NEW.Disaster_ID AND Shelter_ID = NEW.Shelter_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That shelter is not used for this disaster. Add it in "Disaster Shelter" first.';
ELSEIF NEW.Org_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_rescue_org WHERE Disaster_ID = NEW.Disaster_ID AND Org_ID = NEW.Org_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That organisation did not take part in this disaster. Add it in "Disaster Rescue Org" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_recipient_ins$$
CREATE TRIGGER trg_recipient_ins BEFORE INSERT ON relief_recipient FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM relief_distribution x JOIN disaster_affected_person dp ON dp.Disaster_ID = x.Disaster_ID
                WHERE x.Distribution_ID = NEW.Distribution_ID AND dp.Person_ID = NEW.Person_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That person is not recorded as affected by the disaster of this distribution. Add them in "Disaster Affected Person" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_recipient_upd$$
CREATE TRIGGER trg_recipient_upd BEFORE UPDATE ON relief_recipient FOR EACH ROW
IF NOT EXISTS (SELECT 1 FROM relief_distribution x JOIN disaster_affected_person dp ON dp.Disaster_ID = x.Disaster_ID
                WHERE x.Distribution_ID = NEW.Distribution_ID AND dp.Person_ID = NEW.Person_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That person is not recorded as affected by the disaster of this distribution. Add them in "Disaster Affected Person" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_damage_ins$$
CREATE TRIGGER trg_damage_ins BEFORE INSERT ON damage FOR EACH ROW
IF NEW.Area_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_area WHERE Disaster_ID = NEW.Disaster_ID AND Area_ID = NEW.Area_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That area is not affected by this disaster. Add it in "Disaster Area" first.';
END IF$$

DROP TRIGGER IF EXISTS trg_damage_upd$$
CREATE TRIGGER trg_damage_upd BEFORE UPDATE ON damage FOR EACH ROW
IF NEW.Area_ID IS NOT NULL AND NOT EXISTS (SELECT 1 FROM disaster_area WHERE Disaster_ID = NEW.Disaster_ID AND Area_ID = NEW.Area_ID) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'That area is not affected by this disaster. Add it in "Disaster Area" first.';
END IF$$

DELIMITER ;
