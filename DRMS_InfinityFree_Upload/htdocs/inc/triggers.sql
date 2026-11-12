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
