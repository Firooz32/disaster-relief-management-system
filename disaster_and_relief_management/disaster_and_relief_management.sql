-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Disaster Mapping and Relief Management System
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `disaster_and_relief_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `disaster_and_relief_management`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `disaster_and_relief_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `affected_person`
--

DROP TABLE IF EXISTS `affected_person`;
CREATE TABLE IF NOT EXISTS `affected_person` (
  `Person_ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Family_Size` int NOT NULL DEFAULT '1',
  `Contact` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Area_ID` int DEFAULT NULL,
  PRIMARY KEY (`Person_ID`),
  KEY `fk_person_area` (`Area_ID`),
  CONSTRAINT `chk_person_family` CHECK (`Family_Size` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affected_person`
--

INSERT INTO `affected_person` (`Person_ID`, `Name`, `Family_Size`, `Contact`, `Area_ID`) VALUES
(1, 'Rahim Uddin', 5, '01811000001', 3),
(2, 'Karim Ahmed', 4, '01811000002', 1),
(3, 'Salma Begum', 6, '01811000003', 3),
(4, 'Rina Akter', 3, '01811000004', 6),
(5, 'Hasan Ali', 7, '01811000005', 6),
(6, 'Mina Khatun', 4, '01811000006', 6),
(7, 'Jamal Hossain', 5, '01811000007', 1),
(8, 'Nasima Begum', 3, '01811000008', 6),
(9, 'Amena Khatun', 7, '01758464506', 16),
(10, 'Popy Parvin', 2, '01954146590', 4),
(11, 'Sukumar Mia', 6, '01938012911', 2),
(12, 'Bellal Howlader', 2, '01885940692', 12),
(13, 'Selim Das', 4, '01666363228', 6),
(14, 'Hasina Sultana', 2, '01388642300', 4),
(15, 'Shahin Bepari', 4, '01576452912', 18),
(16, 'Babul Mia', 6, '01784585003', 3),
(17, 'Mojibur Biswas', 5, '01738265384', 12),
(18, 'Anita Sultana', 4, '01855778794', 18),
(19, 'Popy Sultana', 4, '01862705860', 2),
(20, 'Mojibur Molla', 7, '01814584210', 3),
(21, 'Amena Sultana', 6, '01894906688', 12),
(22, 'Sufia Sultana', 7, '01328112658', 12),
(23, 'Popy Begum', 4, '01909324484', 1),
(24, 'Hasina Khatun', 4, '01863097658', 1),
(25, 'Anwara Das', 3, '01380258220', 1),
(26, 'Anwar Gazi', 3, '01505154382', 3),
(27, 'Delwar Bepari', 6, '01532157116', 3),
(28, 'Faruk Uddin', 4, '01541577188', 6),
(29, 'Nargis Begum', 4, '01979511174', 2),
(30, 'Rokeya Mondal', 4, '01332568855', 15),
(31, 'Rubel Halder', 8, '01376523735', 17),
(32, 'Mostafa Hossain', 4, '01696327926', 7),
(33, 'Zakir Mondal', 4, '01915474284', 9),
(34, 'Rokeya Khatun', 4, '01928144976', 3),
(35, 'Jesmin Mondal', 5, '01785651788', 12),
(36, 'Sumi Sultana', 4, '01655062048', 1),
(37, 'Rehana Khatun', 8, '01925996775', 18),
(38, 'Sukumar Das', 2, '01731450378', 17),
(39, 'Nargis Mondal', 4, '01551838012', 20),
(40, 'Babul Hossain', 4, '01873924196', 3),
(41, 'Fatema Begum', 8, '01598210460', 2),
(42, 'Delwar Biswas', 3, '01583755559', 7),
(43, 'Sirajul Mondal', 5, '01975407207', 3),
(44, 'Ashraf Gazi', 4, '01973548643', 1),
(45, 'Ashraf Khan', 6, '01761884791', 1),
(46, 'Laila Das', 6, '01595755521', 2),
(47, 'Rehana Sultana', 6, '01848861930', 3),
(48, 'Sohel Mondal', 5, '01836882834', 6),
(49, 'Rafiqul Bepari', 8, '01628556898', 15),
(50, 'Jahangir Sheikh', 4, '01898222900', 7),
(51, 'Rojina Bibi', 4, '01922485272', 11),
(52, 'Morjina Khatun', 3, '01302269780', 2),
(53, 'Kamal Bepari', 4, '01885817625', 1),
(54, 'Jesmin Khatun', 3, '01380700159', 1),
(55, 'Iqbal Sheikh', 8, '01345028316', 2),
(56, 'Kohinoor Parvin', 7, '01632337352', 1),
(57, 'Abdul Ahmed', 6, '01667534789', 10),
(58, 'Jahanara Mondal', 5, '01733115921', 16),
(59, 'Amena Begum', 3, '01863608244', 9),
(60, 'Halima Sultana', 8, '01960787457', 11);

-- --------------------------------------------------------

--
-- Table structure for table `affected_person_emergency_item`
--

DROP TABLE IF EXISTS `affected_person_emergency_item`;
CREATE TABLE IF NOT EXISTS `affected_person_emergency_item` (
  `Person_ID` int NOT NULL,
  `Item_ID` int NOT NULL,
  PRIMARY KEY (`Person_ID`,`Item_ID`),
  KEY `fk_person_item_item` (`Item_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `affected_person_emergency_item`
--

INSERT INTO `affected_person_emergency_item` (`Person_ID`, `Item_ID`) VALUES
(1, 1),
(2, 1),
(4, 1),
(6, 1),
(7, 1),
(1, 2),
(3, 2),
(4, 2),
(6, 2),
(8, 2),
(1, 3),
(5, 3),
(7, 3),
(3, 4),
(5, 4),
(8, 4),
(2, 5),
(3, 5),
(6, 5),
(4, 6),
(8, 6),
(5, 7),
(2, 8),
(7, 8),
(9, 1),
(9, 5),
(9, 13),
(10, 3),
(10, 7),
(10, 10),
(10, 11),
(11, 2),
(11, 3),
(11, 14),
(12, 3),
(12, 7),
(12, 10),
(12, 13),
(13, 6),
(13, 8),
(13, 12),
(14, 2),
(14, 3),
(14, 8),
(14, 13),
(15, 2),
(15, 12),
(16, 5),
(16, 9),
(16, 14),
(17, 10),
(17, 12),
(17, 16),
(18, 1),
(18, 6),
(18, 14),
(19, 3),
(19, 11),
(19, 14),
(20, 1),
(20, 8),
(20, 11),
(20, 15),
(21, 2),
(21, 15),
(22, 6),
(22, 13),
(22, 14),
(23, 8),
(23, 11),
(23, 13),
(24, 4),
(24, 6),
(25, 1),
(25, 8),
(25, 13),
(26, 5),
(26, 7),
(27, 5),
(27, 10),
(27, 11),
(27, 13),
(28, 4),
(28, 6),
(29, 4),
(29, 9),
(30, 11),
(30, 14),
(31, 1),
(31, 2),
(31, 7),
(31, 12),
(32, 3),
(32, 5),
(32, 8),
(32, 13),
(33, 4),
(33, 11),
(33, 14),
(34, 9),
(34, 10),
(34, 14),
(35, 5),
(35, 9),
(36, 5),
(36, 9),
(36, 11),
(37, 3),
(37, 10),
(37, 15),
(38, 10),
(38, 11),
(38, 12),
(39, 6),
(39, 10),
(40, 1),
(40, 3),
(40, 8),
(40, 13),
(41, 2),
(41, 7),
(41, 12),
(41, 16),
(42, 7),
(42, 8),
(42, 9),
(42, 12),
(43, 1),
(43, 11),
(44, 6),
(44, 15),
(45, 9),
(45, 14),
(46, 8),
(46, 11),
(47, 1),
(47, 6),
(47, 12),
(47, 14),
(48, 7),
(48, 13),
(49, 2),
(49, 3),
(49, 7),
(49, 16),
(50, 8),
(50, 10),
(50, 13),
(51, 6),
(51, 8),
(51, 13),
(51, 16),
(52, 1),
(52, 8),
(52, 16),
(53, 1),
(53, 4),
(53, 9),
(53, 16),
(54, 2),
(54, 9),
(55, 6),
(55, 8),
(55, 15),
(56, 5),
(56, 7),
(57, 3),
(57, 6),
(57, 9),
(58, 4),
(58, 7),
(58, 10),
(59, 1),
(59, 2),
(59, 4),
(59, 5),
(60, 10),
(60, 12);

-- --------------------------------------------------------

--
-- Table structure for table `area`
--

DROP TABLE IF EXISTS `area`;
CREATE TABLE IF NOT EXISTS `area` (
  `Area_ID` int NOT NULL AUTO_INCREMENT,
  `Area_Name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Risk_Level` enum('Low','Medium','High','Very High') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Latitude` decimal(9,6) DEFAULT NULL,
  `Longitude` decimal(9,6) DEFAULT NULL,
  PRIMARY KEY (`Area_ID`),
  UNIQUE KEY `uq_area_name_location` (`Area_Name`,`Location`),
  CONSTRAINT `chk_area_lat` CHECK (`Latitude` BETWEEN -90 AND 90),
  CONSTRAINT `chk_area_lng` CHECK (`Longitude` BETWEEN -180 AND 180)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `area`
--

INSERT INTO `area` (`Area_ID`, `Area_Name`, `Location`, `Risk_Level`, `Latitude`, `Longitude`) VALUES
(1, 'Sonadanga', 'Khulna, Bangladesh', 'High', 22.798000, 89.539000),
(2, 'Dacope', 'Khulna, Bangladesh', 'Very High', 22.570000, 89.515000),
(3, 'Koyra', 'Khulna, Bangladesh', 'Very High', 22.340000, 89.310000),
(4, 'Shyamnagar', 'Satkhira, Bangladesh', 'High', 22.330000, 89.100000),
(5, 'Kaliganj', 'Satkhira, Bangladesh', 'Medium', 22.450000, 89.040000),
(6, 'Paikgachha', 'Khulna, Bangladesh', 'High', 22.578000, 89.330000),
(7, 'Mongla', 'Bagerhat, Bangladesh', 'Very High', 22.475000, 89.600000),
(8, 'Morrelganj', 'Bagerhat, Bangladesh', 'High', 22.450000, 89.860000),
(9, 'Batiaghata', 'Khulna, Bangladesh', 'Medium', 22.700000, 89.530000),
(10, 'Dumuria', 'Khulna, Bangladesh', 'Medium', 22.800000, 89.420000),
(11, 'Rupsha', 'Khulna, Bangladesh', 'Medium', 22.770000, 89.590000),
(12, 'Terokhada', 'Khulna, Bangladesh', 'Low', 22.930000, 89.600000),
(13, 'Assasuni', 'Satkhira, Bangladesh', 'Very High', 22.550000, 89.170000),
(14, 'Debhata', 'Satkhira, Bangladesh', 'High', 22.580000, 89.070000),
(15, 'Tala', 'Satkhira, Bangladesh', 'Medium', 22.720000, 89.250000),
(16, 'Satkhira Sadar', 'Satkhira, Bangladesh', 'Low', 22.710000, 89.070000),
(17, 'Sarankhola', 'Bagerhat, Bangladesh', 'Very High', 22.320000, 89.800000),
(18, 'Rampal', 'Bagerhat, Bangladesh', 'High', 22.570000, 89.670000),
(19, 'Bagerhat Sadar', 'Bagerhat, Bangladesh', 'Medium', 22.660000, 89.790000),
(20, 'Mollahat', 'Bagerhat, Bangladesh', 'Low', 22.930000, 89.780000);

-- --------------------------------------------------------

--
-- Table structure for table `damage`
--

DROP TABLE IF EXISTS `damage`;
CREATE TABLE IF NOT EXISTS `damage` (
  `Damage_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Damage_Type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Area_ID` int DEFAULT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Quantity` int NOT NULL DEFAULT '0',
  `Estimated_Loss` decimal(15,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`Damage_ID`),
  KEY `fk_damage_disaster` (`Disaster_ID`),
  KEY `fk_damage_area` (`Area_ID`),
  CONSTRAINT `chk_damage_qty` CHECK (`Quantity` >= 0),
  CONSTRAINT `chk_damage_loss` CHECK (`Estimated_Loss` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `damage`
--

INSERT INTO `damage` (`Damage_ID`, `Disaster_ID`, `Damage_Type`, `Area_ID`, `Description`, `Quantity`, `Estimated_Loss`) VALUES
(1, 1, 'House Damage', 1, 'Residential houses were partially damaged by floodwater.', 120, 4500000.00),
(2, 1, 'Road Damage', 6, 'Several roads became unusable because of waterlogging.', 18, 1200000.00),
(3, 2, 'House Damage', 2, 'Roofs and walls of houses were damaged by strong winds.', 85, 3200000.00),
(4, 2, 'Electricity Damage', 3, 'Electric poles and power lines were damaged.', 35, 1800000.00),
(5, 3, 'Land Damage', 3, 'Agricultural land was lost due to riverbank erosion.', 65, 2500000.00),
(6, 4, 'Infrastructure Damage', 4, 'Roads and coastal infrastructure were damaged by tsunami waves.', 12, 5000000.00),
(7, 5, 'House Damage', 6, 'Flash flooding damaged houses and household property.', 60, 2100000.00),
(8, 1, 'Shrimp Gher Damage', 6, 'Shrimp and fish farms were flooded and the stock was lost.', 83, 15170000.00),
(9, 2, 'Electricity Damage', 3, 'Electric poles and power lines were broken.', 33, 1460000.00),
(10, 3, 'Road Damage', 8, 'Village and union roads were cut or became unusable (quantity in km).', 21, 23910000.00),
(11, 3, 'Business Loss', 3, 'Small shops and markets were flooded and goods were lost.', 68, 4470000.00),
(12, 4, 'Shrimp Gher Damage', 7, 'Shrimp and fish farms were flooded and the stock was lost.', 81, 10840000.00),
(13, 4, 'Embankment Breach', 7, 'Sections of the embankment were breached or overtopped (quantity in sections).', 7, 19780000.00),
(14, 5, 'Road Damage', 5, 'Village and union roads were cut or became unusable (quantity in km).', 12, 14600000.00),
(15, 6, 'Electricity Damage', 7, 'Electric poles and power lines were broken.', 63, 3000000.00),
(16, 6, 'Tube-well Damage', 18, 'Tube-wells and water points were contaminated or damaged.', 139, 3650000.00),
(17, 6, 'School Damage', 18, 'School buildings were damaged and classes suspended.', 3, 1220000.00),
(18, 6, 'Shrimp Gher Damage', 7, 'Shrimp and fish farms were flooded and the stock was lost.', 64, 7920000.00),
(19, 6, 'Livestock Loss', 2, 'Cattle, goats and poultry died or were lost.', 88, 2730000.00),
(20, 7, 'Tube-well Damage', 1, 'Tube-wells and water points were contaminated or damaged.', 177, 3190000.00),
(21, 7, 'Crop Damage', 1, 'Standing paddy and vegetable crops were destroyed (quantity in acres).', 223, 11190000.00),
(22, 7, 'School Damage', 16, 'School buildings were damaged and classes suspended.', 7, 5530000.00),
(23, 7, 'House Damage', 1, 'Houses and household belongings were damaged or washed away.', 109, 5330000.00),
(24, 7, 'Livestock Loss', 9, 'Cattle, goats and poultry died or were lost.', 230, 9020000.00),
(25, 8, 'Business Loss', 15, 'Small shops and markets were flooded and goods were lost.', 22, 2670000.00),
(26, 8, 'House Damage', 10, 'Houses and household belongings were damaged or washed away.', 211, 11560000.00),
(27, 8, 'Crop Damage', 6, 'Standing paddy and vegetable crops were destroyed (quantity in acres).', 194, 12170000.00),
(28, 9, 'Embankment Breach', 3, 'Sections of the embankment were breached or overtopped (quantity in sections).', 5, 11870000.00),
(29, 9, 'Shrimp Gher Damage', 14, 'Shrimp and fish farms were flooded and the stock was lost.', 48, 9130000.00),
(30, 9, 'Tube-well Damage', 13, 'Tube-wells and water points were contaminated or damaged.', 80, 1690000.00),
(31, 9, 'House Damage', 14, 'Houses and household belongings were damaged or washed away.', 191, 7750000.00),
(32, 10, 'Land Damage', 8, 'Agricultural and homestead land was lost to the river (quantity in acres).', 46, 3550000.00),
(33, 10, 'House Damage', 12, 'Houses and household belongings were damaged or washed away.', 43, 1410000.00),
(34, 10, 'Road Damage', 12, 'Village and union roads were cut or became unusable (quantity in km).', 4, 5550000.00),
(35, 10, 'Business Loss', 12, 'Small shops and markets were flooded and goods were lost.', 69, 7660000.00),
(36, 11, 'Livestock Loss', 18, 'Cattle, goats and poultry died or were lost.', 268, 14200000.00),
(37, 11, 'Shrimp Gher Damage', 5, 'Shrimp and fish farms were flooded and the stock was lost.', 79, 13440000.00),
(38, 11, 'Electricity Damage', 5, 'Electric poles and power lines were broken.', 19, 1000000.00),
(39, 11, 'School Damage', 3, 'School buildings were damaged and classes suspended.', 8, 4030000.00),
(40, 12, 'Road Damage', 15, 'Village and union roads were cut or became unusable (quantity in km).', 21, 25980000.00),
(41, 12, 'Livestock Loss', 12, 'Cattle, goats and poultry died or were lost.', 182, 4920000.00),
(42, 12, 'School Damage', 9, 'School buildings were damaged and classes suspended.', 8, 5700000.00),
(43, 12, 'House Damage', 11, 'Houses and household belongings were damaged or washed away.', 220, 12190000.00),
(44, 13, 'House Damage', 3, 'Preliminary report: kutcha houses were flattened by wind and the storm surge.', 140, 6200000.00),
(45, 13, 'Embankment Breach', 2, 'The embankment was breached at two points and saline water entered villages (quantity in sections).', 2, 8500000.00);

-- --------------------------------------------------------

--
-- Table structure for table `disaster`
--

DROP TABLE IF EXISTS `disaster`;
CREATE TABLE IF NOT EXISTS `disaster` (
  `Disaster_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_Type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Disaster_Date` date NOT NULL,
  `Description` text COLLATE utf8mb4_unicode_ci,
  `Status` enum('Reported','Active','Under control','Closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Reported',
  `Incident_Lead_ID` int DEFAULT NULL,
  `Status_Changed_At` datetime DEFAULT NULL,
  `Closed_At` datetime DEFAULT NULL,
  PRIMARY KEY (`Disaster_ID`),
  KEY `fk_disaster_lead` (`Incident_Lead_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disaster`
--

INSERT INTO `disaster` (`Disaster_ID`, `Disaster_Type`, `Disaster_Date`, `Description`, `Status`) VALUES
(1, 'Flood', '2026-07-15', 'Severe flooding caused by heavy monsoon rainfall and river overflow.', 'Closed'),
(2, 'Cyclone', '2026-05-28', 'A strong cyclone affected coastal areas with high winds and heavy rainfall.', 'Closed'),
(3, 'River Erosion', '2026-08-10', 'Riverbank erosion damaged houses and agricultural land.', 'Closed'),
(4, 'Tsunami', '2026-06-20', 'A tsunami caused coastal flooding and damage to infrastructure.', 'Closed'),
(5, 'Flood', '2026-08-25', 'Flash flooding affected several villages after continuous heavy rainfall.', 'Closed'),
(6, 'Cyclone', '2025-05-26', 'A severe cyclonic storm made landfall near the Sundarbans with 110 km/h winds and a 3 m storm surge.', 'Closed'),
(7, 'Flood', '2025-08-14', 'Monsoon rivers crossed the danger level and flooded low-lying unions for nearly two weeks.', 'Closed'),
(8, 'Waterlogging', '2025-09-20', 'Continuous heavy rain and blocked drainage kept town and village roads under knee-deep water.', 'Closed'),
(9, 'Tidal Surge', '2025-10-22', 'A spring tide pushed 2 m above the normal level and overtopped weak coastal embankments.', 'Closed'),
(10, 'River Erosion', '2025-12-08', 'Winter riverbank erosion swallowed homesteads, a market and farmland along the river.', 'Closed'),
(11, 'Cyclone', '2026-09-18', 'A cyclone crossed the south-west coast overnight, damaging embankments, shrimp farms and power lines.', 'Closed'),
(12, 'Flood', '2026-10-02', 'Late-season heavy rainfall and upstream water caused flash flooding in several unions.', 'Active'),
(13, 'Cyclone', '2026-10-24', 'A severe cyclonic storm formed over the Bay of Bengal, crossed the coast near Koyra and is moving north over Dacope and Paikgachha towards Khulna city.', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_affected_person`
--

DROP TABLE IF EXISTS `disaster_affected_person`;
CREATE TABLE IF NOT EXISTS `disaster_affected_person` (
  `Disaster_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Person_ID`),
  KEY `fk_disaster_person_person` (`Person_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disaster_affected_person`
--

INSERT INTO `disaster_affected_person` (`Disaster_ID`, `Person_ID`) VALUES
(1, 1),
(3, 1),
(1, 2),
(5, 2),
(1, 3),
(4, 3),
(2, 4),
(5, 4),
(2, 5),
(3, 5),
(1, 6),
(4, 6),
(2, 7),
(5, 7),
(3, 8),
(5, 8),
(1, 16),
(1, 18),
(1, 26),
(1, 35),
(1, 60),
(2, 6),
(2, 27),
(2, 41),
(2, 43),
(2, 58),
(3, 28),
(3, 35),
(3, 47),
(3, 56),
(4, 12),
(4, 14),
(4, 30),
(4, 58),
(5, 11),
(5, 18),
(5, 30),
(5, 44),
(5, 53),
(6, 12),
(6, 15),
(6, 20),
(6, 21),
(6, 23),
(6, 37),
(6, 44),
(6, 51),
(6, 54),
(6, 55),
(6, 57),
(7, 9),
(7, 11),
(7, 16),
(7, 21),
(7, 25),
(7, 33),
(7, 36),
(7, 45),
(7, 48),
(7, 49),
(7, 51),
(7, 54),
(8, 12),
(8, 13),
(8, 16),
(8, 23),
(8, 24),
(8, 47),
(8, 48),
(8, 49),
(8, 53),
(9, 10),
(9, 11),
(9, 18),
(9, 26),
(9, 33),
(9, 35),
(9, 38),
(9, 40),
(9, 53),
(9, 57),
(10, 21),
(10, 26),
(10, 39),
(10, 43),
(10, 44),
(10, 46),
(10, 48),
(10, 60),
(11, 10),
(11, 11),
(11, 16),
(11, 18),
(11, 23),
(11, 28),
(11, 31),
(11, 34),
(11, 38),
(11, 40),
(11, 46),
(11, 55),
(11, 57),
(12, 12),
(12, 17),
(12, 21),
(12, 22),
(12, 30),
(12, 33),
(12, 35),
(12, 49),
(12, 56),
(12, 57),
(12, 58),
(12, 60),
(13, 1),
(13, 3),
(13, 5),
(13, 6),
(13, 13),
(13, 15),
(13, 19),
(13, 20),
(13, 26),
(13, 27),
(13, 29),
(13, 32),
(13, 37),
(13, 41),
(13, 42),
(13, 50),
(13, 52);

-- --------------------------------------------------------

--
-- Table structure for table `disaster_area`
--

DROP TABLE IF EXISTS `disaster_area`;
CREATE TABLE IF NOT EXISTS `disaster_area` (
  `Disaster_ID` int NOT NULL,
  `Area_ID` int NOT NULL,
  `Relief_Shortage` enum('No','Yes') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  PRIMARY KEY (`Disaster_ID`,`Area_ID`),
  KEY `fk_disaster_area_area` (`Area_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disaster_area`
--

INSERT INTO `disaster_area` (`Disaster_ID`, `Area_ID`, `Relief_Shortage`) VALUES
(1, 1, 'No'),
(5, 1, 'No'),
(1, 2, 'No'),
(2, 2, 'No'),
(1, 3, 'No'),
(2, 3, 'No'),
(3, 3, 'No'),
(2, 4, 'No'),
(4, 4, 'No'),
(5, 5, 'No'),
(1, 6, 'No'),
(3, 6, 'No'),
(5, 6, 'No'),
(2, 7, 'No'),
(4, 7, 'No'),
(3, 8, 'No'),
(6, 2, 'No'),
(6, 3, 'No'),
(6, 4, 'No'),
(6, 7, 'No'),
(6, 13, 'No'),
(6, 17, 'No'),
(6, 18, 'No'),
(7, 1, 'No'),
(7, 9, 'No'),
(7, 10, 'No'),
(7, 11, 'No'),
(7, 12, 'No'),
(7, 16, 'No'),
(8, 1, 'No'),
(8, 6, 'No'),
(8, 10, 'No'),
(8, 14, 'No'),
(8, 15, 'No'),
(9, 3, 'No'),
(9, 4, 'No'),
(9, 13, 'No'),
(9, 14, 'No'),
(9, 17, 'No'),
(10, 8, 'No'),
(10, 12, 'No'),
(10, 19, 'No'),
(10, 20, 'No'),
(11, 2, 'No'),
(11, 3, 'No'),
(11, 4, 'No'),
(11, 7, 'No'),
(11, 13, 'No'),
(11, 17, 'No'),
(11, 18, 'No'),
(11, 5, 'No'),
(12, 1, 'No'),
(12, 9, 'No'),
(12, 10, 'No'),
(12, 11, 'No'),
(12, 12, 'No'),
(12, 15, 'No'),
(12, 16, 'No'),
(13, 1, 'No'),
(13, 2, 'No'),
(13, 3, 'No'),
(13, 6, 'Yes'),
(13, 7, 'No'),
(13, 9, 'No'),
(13, 11, 'No'),
(13, 18, 'No');

-- --------------------------------------------------------

--
-- Table structure for table `disaster_rescue_org`
--

DROP TABLE IF EXISTS `disaster_rescue_org`;
CREATE TABLE IF NOT EXISTS `disaster_rescue_org` (
  `Disaster_ID` int NOT NULL,
  `Org_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Org_ID`),
  KEY `fk_disaster_rescue_org` (`Org_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disaster_rescue_org`
--

INSERT INTO `disaster_rescue_org` (`Disaster_ID`, `Org_ID`) VALUES
(1, 1),
(2, 1),
(4, 1),
(1, 2),
(2, 2),
(4, 2),
(5, 2),
(1, 3),
(3, 3),
(5, 3),
(2, 4),
(1, 5),
(3, 5),
(5, 5),
(2, 6),
(4, 6),
(1, 8),
(1, 10),
(2, 7),
(2, 8),
(3, 11),
(4, 7),
(4, 8),
(5, 10),
(5, 11),
(6, 1),
(6, 2),
(6, 3),
(6, 5),
(6, 7),
(6, 8),
(6, 9),
(7, 1),
(7, 3),
(7, 5),
(7, 10),
(7, 11),
(7, 12),
(8, 2),
(8, 10),
(8, 11),
(8, 5),
(9, 1),
(9, 6),
(9, 7),
(9, 8),
(9, 9),
(10, 3),
(10, 5),
(10, 12),
(10, 10),
(11, 1),
(11, 2),
(11, 3),
(11, 7),
(11, 8),
(11, 9),
(11, 6),
(11, 4),
(12, 1),
(12, 3),
(12, 5),
(12, 10),
(12, 11),
(12, 12),
(12, 9),
(13, 1),
(13, 2),
(13, 7),
(13, 8),
(13, 10);

-- --------------------------------------------------------

--
-- Table structure for table `disaster_shelter`
--

DROP TABLE IF EXISTS `disaster_shelter`;
CREATE TABLE IF NOT EXISTS `disaster_shelter` (
  `Disaster_ID` int NOT NULL,
  `Shelter_ID` int NOT NULL,
  PRIMARY KEY (`Disaster_ID`,`Shelter_ID`),
  KEY `fk_disaster_shelter_shelter` (`Shelter_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disaster_shelter`
--

INSERT INTO `disaster_shelter` (`Disaster_ID`, `Shelter_ID`) VALUES
(1, 1),
(5, 1),
(1, 2),
(2, 2),
(1, 3),
(2, 3),
(3, 3),
(2, 4),
(4, 4),
(1, 5),
(3, 5),
(5, 5),
(2, 6),
(4, 6),
(3, 7),
(6, 2),
(6, 3),
(6, 4),
(6, 6),
(6, 12),
(6, 15),
(6, 16),
(6, 20),
(7, 1),
(7, 8),
(7, 9),
(7, 10),
(7, 11),
(7, 14),
(7, 19),
(8, 1),
(8, 5),
(8, 9),
(8, 13),
(8, 19),
(9, 3),
(9, 4),
(9, 12),
(9, 15),
(9, 20),
(10, 7),
(10, 11),
(10, 17),
(10, 18),
(11, 2),
(11, 3),
(11, 4),
(11, 6),
(11, 12),
(11, 15),
(11, 16),
(11, 20),
(12, 1),
(12, 8),
(12, 9),
(12, 10),
(12, 11),
(12, 13),
(12, 14),
(12, 19),
(13, 1),
(13, 2),
(13, 3),
(13, 5),
(13, 6),
(13, 8),
(13, 10),
(13, 16),
(13, 20),
(13, 21);

-- --------------------------------------------------------

--
-- Table structure for table `early_warning`
--

DROP TABLE IF EXISTS `early_warning`;
CREATE TABLE IF NOT EXISTS `early_warning` (
  `Alert_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Alert_Type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Signal_Level` tinyint DEFAULT NULL,
  `Alert_DateTime` datetime NOT NULL,
  `Message` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`Alert_ID`),
  KEY `fk_early_warning_disaster` (`Disaster_ID`),
  CONSTRAINT `chk_warning_signal` CHECK (`Signal_Level` BETWEEN 1 AND 10)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `early_warning`
--

INSERT INTO `early_warning` (`Alert_ID`, `Disaster_ID`, `Alert_Type`, `Signal_Level`, `Alert_DateTime`, `Message`) VALUES
(1, 1, 'Flood Warning', 6, '2026-07-15 06:30:00', 'Residents near riverbanks are advised to move to safe shelters.'),
(2, 2, 'Cyclone Warning', 7, '2026-05-28 08:00:00', 'Residents in coastal areas are advised to evacuate immediately.'),
(3, 3, 'Erosion Warning', NULL, '2026-08-10 10:15:00', 'Residents living close to the riverbank are advised to relocate.'),
(4, 4, 'Tsunami Warning', 10, '2026-06-20 14:20:00', 'Move to higher ground immediately and stay away from coastal areas.'),
(5, 5, 'Flash Flood Warning', 6, '2026-08-25 05:45:00', 'Avoid flooded roads and move to designated emergency shelters.'),
(6, 1, 'Evacuation Notice', 8, '2026-07-15 09:00:00', 'Water level is still rising. Families near the river must evacuate to the nearest shelter now.'),
(7, 6, 'Cyclone Alert', 4, '2025-05-24 06:00:00', 'A deep depression has become a cyclone. Fishing boats must return to shore.'),
(8, 6, 'Cyclone Warning', 7, '2025-05-25 09:30:00', 'Warning signal raised for the coast. Prepare to move to cyclone shelters with food and water.'),
(9, 6, 'Evacuation Order', 10, '2025-05-26 05:15:00', 'Landfall expected within hours. All coastal families must leave for shelters immediately.'),
(10, 7, 'Flood Watch', 3, '2025-08-12 08:00:00', 'Rivers are rising fast. Keep documents, medicine and dry food ready.'),
(11, 7, 'Flood Warning', 6, '2025-08-13 07:30:00', 'Water level crossed the danger mark. Move livestock and families to higher ground.'),
(12, 8, 'Heavy Rain Alert', 2, '2025-09-19 10:00:00', 'Continuous rain is expected for 48 hours. Avoid low roads and clear drains.'),
(13, 9, 'Tidal Surge Warning', 7, '2025-10-21 11:00:00', 'Spring tide with strong winds may overtop embankments tonight. Stay away from the coast.'),
(14, 10, 'Erosion Warning', NULL, '2025-12-05 09:00:00', 'Cracks have appeared along the riverbank. Families close to the edge should relocate.'),
(15, 10, 'Erosion Alert', NULL, '2025-12-08 08:30:00', 'Bank collapse has started. Leave the area immediately.'),
(16, 11, 'Cyclone Alert', 4, '2026-09-16 06:00:00', 'A deep depression has become a cyclone. Fishing boats must return to shore.'),
(17, 11, 'Cyclone Warning', 7, '2026-09-17 09:30:00', 'Warning signal raised for the coast. Prepare to move to cyclone shelters with food and water.'),
(18, 11, 'Evacuation Order', 9, '2026-09-18 05:15:00', 'Landfall expected within hours. All coastal families must leave for shelters immediately.'),
(19, 12, 'Flood Watch', 3, '2026-09-30 08:00:00', 'Rivers are rising fast. Keep documents, medicine and dry food ready.'),
(20, 12, 'Flood Warning', 6, '2026-10-01 07:30:00', 'Water level crossed the danger mark. Move livestock and families to higher ground.'),
(21, 1, 'Flood Watch', 3, '2026-07-13 08:00:00', 'Heavy upstream rainfall. Rivers near the city are rising. Be ready to move.'),
(22, 2, 'Cyclone Alert', 4, '2026-05-26 06:00:00', 'A cyclone is forming in the Bay of Bengal and may reach the coast in two days.'),
(23, 4, 'Coastal Surge Alert', 5, '2026-06-19 12:00:00', 'Unusual sea behaviour reported. Fishermen and coastal families must stay alert.'),
(24, 5, 'Heavy Rain Alert', 2, '2026-08-24 18:00:00', 'Very heavy rain expected overnight. Flash flooding is possible in low areas.'),
(25, 12, 'Flood Receding', 2, '2026-10-10 08:00:00', 'Rivers have fallen below the danger mark. Families may return home in daylight but should stay alert.'),
(26, 13, 'Cyclone Alert', 2, '2026-10-21 09:00:00', 'A depression over the south Bay of Bengal may become a cyclone. Distant cautionary signal 2 for the ports.'),
(27, 13, 'Cyclone Warning', 4, '2026-10-22 12:00:00', 'The cyclone is moving north. Local warning signal 4 for Mongla port. Fishing boats must return to shore.'),
(28, 13, 'Danger Signal', 7, '2026-10-23 10:00:00', 'The cyclone may cross the Khulna coast within 24 hours. Danger signal 7. Move children, the elderly and livestock to cyclone shelters.'),
(29, 13, 'Great Danger Signal', 10, '2026-10-24 03:00:00', 'A severe cyclone is crossing the coast near Koyra. Great danger signal 10. Everyone in the coastal unions must be in a shelter now.'),
(30, 12, 'Flood Warning', 5, '2026-10-24 06:30:00', 'Fresh upstream rain: the rivers are rising again. Families in low-lying unions should keep to shelters and avoid flooded roads.');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_item`
--

DROP TABLE IF EXISTS `emergency_item`;
CREATE TABLE IF NOT EXISTS `emergency_item` (
  `Item_ID` int NOT NULL AUTO_INCREMENT,
  `Item_Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`Item_ID`),
  UNIQUE KEY `uq_emergency_item_name` (`Item_Name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `emergency_item`
--

INSERT INTO `emergency_item` (`Item_ID`, `Item_Name`, `Category`) VALUES
(1, 'Rice Package', 'Food'),
(2, 'Water Bottle', 'Water'),
(3, 'Blanket', 'Shelter'),
(4, 'First Aid Box', 'Medical'),
(5, 'Oral Saline', 'Medicine'),
(6, 'Torch Light', 'Emergency Equipment'),
(7, 'Life Jacket', 'Rescue Equipment'),
(8, 'Dry Food Packet', 'Food'),
(9, 'Baby Formula', 'Food'),
(10, 'Sanitary Pad Pack', 'Health'),
(11, 'Power Bank', 'Emergency Equipment'),
(12, 'Water Purification Tablet', 'Water'),
(13, 'Tarpaulin Sheet', 'Shelter'),
(14, 'Candle and Matchbox', 'Emergency Equipment'),
(15, 'Cooking Utensil Set', 'Household'),
(16, 'Whistle', 'Rescue Equipment');

-- --------------------------------------------------------

--
-- Table structure for table `relief_item`
--

DROP TABLE IF EXISTS `relief_item`;
CREATE TABLE IF NOT EXISTS `relief_item` (
  `Relief_ID` int NOT NULL AUTO_INCREMENT,
  `Item_Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`Relief_ID`),
  UNIQUE KEY `uq_relief_item_name` (`Item_Name`),
  CONSTRAINT `chk_relief_qty` CHECK (`Quantity` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `relief_item`
--

INSERT INTO `relief_item` (`Relief_ID`, `Item_Name`, `Category`, `Quantity`) VALUES
(1, 'Rice', 'Food', 1900),
(2, 'Lentils', 'Food', 95),
(3, 'Drinking Water', 'Water', 11350),
(4, 'Blanket', 'Shelter', 150),
(5, 'Mosquito Net', 'Health', 300),
(6, 'Oral Saline', 'Medicine', 435),
(7, 'First Aid Kit', 'Medical', 115),
(8, 'Dry Food Package', 'Food', 500),
(9, 'Tent and Tarpaulin', 'Shelter', 150),
(10, 'Water Purification Tablets', 'Water', 950),
(11, 'Hygiene Kit', 'Health', 300),
(12, 'Baby Food', 'Food', 300),
(13, 'Cooking Set', 'Household', 250),
(14, 'Candles and Matches', 'Emergency Equipment', 750),
(15, 'Sanitary Pads', 'Health', 650),
(16, 'Biscuits', 'Food', 1100);

-- --------------------------------------------------------
--
-- Table structure for table `relief_batch`
--

DROP TABLE IF EXISTS `relief_batch`;
CREATE TABLE IF NOT EXISTS `relief_batch` (
  `Batch_ID` int NOT NULL AUTO_INCREMENT,
  `Relief_ID` int NOT NULL,
  `Batch_Code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Quantity_Received` int NOT NULL,
  `Received_At` date DEFAULT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `Source` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Storage_Location` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Notes` text COLLATE utf8mb4_unicode_ci,
  `Received_By` int DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Batch_ID`),
  UNIQUE KEY `uq_relief_batch_code` (`Batch_Code`),
  KEY `fk_batch_relief` (`Relief_ID`),
  KEY `fk_batch_receiver` (`Received_By`),
  KEY `ix_batch_expiry` (`Expiry_Date`),
  CONSTRAINT `chk_batch_quantity` CHECK (`Quantity_Received` > 0),
  CONSTRAINT `chk_batch_dates` CHECK (`Expiry_Date` IS NULL OR `Received_At` IS NULL OR `Expiry_Date` >= `Received_At`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `relief_batch` (`Relief_ID`, `Batch_Code`, `Quantity_Received`, `Source`)
SELECT `Relief_ID`, CONCAT('LEGACY-', `Relief_ID`), `Quantity`, 'Imported opening stock; original receipt date unknown'
  FROM `relief_item` WHERE `Quantity` > 0;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_org`
--

DROP TABLE IF EXISTS `rescue_org`;
CREATE TABLE IF NOT EXISTS `rescue_org` (
  `Org_ID` int NOT NULL AUTO_INCREMENT,
  `Org_Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Org_Type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Contact` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`Org_ID`),
  UNIQUE KEY `uq_rescue_org_name` (`Org_Name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rescue_org`
--

INSERT INTO `rescue_org` (`Org_ID`, `Org_Name`, `Org_Type`, `Contact`) VALUES
(1, 'Bangladesh Red Crescent Society', 'Humanitarian Organization', '01711000001'),
(2, 'Bangladesh Fire Service', 'Government Emergency Service', '16163'),
(3, 'BRAC', 'NGO', '01711000002'),
(4, 'JAAGO Foundation', 'NGO', '01711000003'),
(5, 'Khulna Volunteer Rescue Team', 'Volunteer Organization', '01711000004'),
(6, 'Coastal Relief Foundation', 'NGO', '01711000005'),
(7, 'Bangladesh Coast Guard', 'Government Emergency Service', '01711000006'),
(8, 'Cyclone Preparedness Programme (CPP)', 'Volunteer Organization', '01711000007'),
(9, 'Islamic Relief Bangladesh', 'NGO', '01711000008'),
(10, 'Khulna City Corporation Relief Cell', 'Local Government', '01711000009'),
(11, 'Satkhira Youth Relief Society', 'Volunteer Organization', '01711000010'),
(12, 'Bagerhat Medical Relief Team', 'Humanitarian Organization', '01711000011');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_org_relief_item`
--

DROP TABLE IF EXISTS `rescue_org_relief_item`;
CREATE TABLE IF NOT EXISTS `rescue_org_relief_item` (
  `Org_ID` int NOT NULL,
  `Relief_ID` int NOT NULL,
  PRIMARY KEY (`Org_ID`,`Relief_ID`),
  KEY `fk_org_relief_item` (`Relief_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rescue_org_relief_item`
--

INSERT INTO `rescue_org_relief_item` (`Org_ID`, `Relief_ID`) VALUES
(1, 1),
(3, 1),
(5, 1),
(3, 2),
(1, 3),
(2, 3),
(5, 3),
(6, 3),
(1, 4),
(4, 4),
(6, 4),
(4, 5),
(1, 6),
(6, 6),
(2, 7),
(5, 7),
(3, 8),
(4, 8),
(1, 9),
(1, 11),
(2, 10),
(2, 14),
(3, 12),
(3, 16),
(4, 11),
(4, 15),
(5, 9),
(5, 14),
(6, 13),
(6, 16),
(7, 3),
(7, 7),
(7, 9),
(8, 1),
(8, 3),
(8, 6),
(8, 14),
(8, 10),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(9, 12),
(9, 16),
(10, 1),
(10, 3),
(10, 8),
(10, 13),
(10, 15),
(11, 1),
(11, 2),
(11, 8),
(11, 16),
(11, 3),
(12, 6),
(12, 7),
(12, 11),
(12, 15);

-- --------------------------------------------------------

--
-- Table structure for table `shelter`
--

DROP TABLE IF EXISTS `shelter`;
CREATE TABLE IF NOT EXISTS `shelter` (
  `Shelter_ID` int NOT NULL AUTO_INCREMENT,
  `Shelter_Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Capacity` int NOT NULL,
  `Area_ID` int NOT NULL,
  `Latitude` decimal(9,6) DEFAULT NULL,
  `Longitude` decimal(9,6) DEFAULT NULL,
  `Services` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Accessibility_Notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Capacity_Verified_At` datetime DEFAULT NULL,
  `Capacity_Verified_By` int DEFAULT NULL,
  PRIMARY KEY (`Shelter_ID`),
  UNIQUE KEY `uq_shelter_name` (`Shelter_Name`),
  KEY `fk_shelter_area` (`Area_ID`),
  KEY `fk_shelter_capacity_verifier` (`Capacity_Verified_By`),
  CONSTRAINT `chk_shelter_capacity` CHECK (`Capacity` > 0),
  CONSTRAINT `chk_shelter_lat` CHECK (`Latitude` BETWEEN -90 AND 90),
  CONSTRAINT `chk_shelter_lng` CHECK (`Longitude` BETWEEN -180 AND 180)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelter`
--

INSERT INTO `shelter` (`Shelter_ID`, `Shelter_Name`, `Location`, `Capacity`, `Area_ID`, `Latitude`, `Longitude`) VALUES
(1, 'Sonadanga Community Shelter', 'Sonadanga, Khulna', 500, 1, 22.803000, 89.544000),
(2, 'Dacope Cyclone Shelter', 'Dacope, Khulna', 800, 2, 22.575000, 89.520000),
(3, 'Koyra Government Shelter', 'Koyra, Khulna', 700, 3, 22.345000, 89.315000),
(4, 'Shyamnagar Emergency Shelter', 'Shyamnagar, Satkhira', 600, 4, 22.335000, 89.105000),
(5, 'Paikgachha Relief Shelter', 'Paikgachha, Khulna', 450, 6, 22.583000, 89.335000),
(6, 'Mongla Coastal Shelter', 'Mongla, Bagerhat', 900, 7, 22.480000, 89.605000),
(7, 'Morrelganj Community Shelter', 'Morrelganj, Bagerhat', 550, 8, 22.455000, 89.865000),
(8, 'Batiaghata Cyclone Shelter', 'Batiaghata, Khulna', 600, 9, 22.690859, 89.530060),
(9, 'Dumuria Union Parishad Shelter', 'Dumuria, Khulna', 450, 10, 22.800284, 89.428640),
(10, 'Rupsha Riverside Shelter', 'Rupsha, Khulna', 350, 11, 22.760463, 89.583359),
(11, 'Terokhada Primary School Shelter', 'Terokhada, Khulna', 27, 12, 22.932425, 89.601357),
(12, 'Assasuni Coastal Cyclone Shelter', 'Assasuni, Satkhira', 850, 13, 22.556801, 89.171147),
(13, 'Tala Government Shelter', 'Tala, Satkhira', 400, 15, 22.725533, 89.256435),
(14, 'Satkhira Town Community Shelter', 'Satkhira Sadar, Satkhira', 700, 16, 22.716025, 89.072077),
(15, 'Sarankhola Mangrove Shelter', 'Sarankhola, Bagerhat', 750, 17, 22.313758, 89.802741),
(16, 'Rampal Cyclone Shelter', 'Rampal, Bagerhat', 650, 18, 22.560658, 89.677602),
(17, 'Bagerhat Sadar Relief Shelter', 'Bagerhat Sadar, Bagerhat', 500, 19, 22.658792, 89.797554),
(18, 'Mollahat High School Shelter', 'Mollahat, Bagerhat', 300, 20, 22.934444, 89.784305),
(19, 'Sonadanga Mosque Annex Shelter', 'Sonadanga, Khulna', 250, 1, 22.791045, 89.533036),
(20, 'Koyra Embankment Shelter', 'Koyra, Khulna', 600, 3, 22.351518, 89.320304),
(21, 'Dacope Union Primary School Shelter', 'Dacope, Khulna', 16, 2, 22.565200, 89.508900);

-- --------------------------------------------------------

--
-- Table structure for table `shelter_emergency_item`
--

DROP TABLE IF EXISTS `shelter_emergency_item`;
CREATE TABLE IF NOT EXISTS `shelter_emergency_item` (
  `Shelter_ID` int NOT NULL,
  `Item_ID` int NOT NULL,
  PRIMARY KEY (`Shelter_ID`,`Item_ID`),
  KEY `fk_shelter_item_item` (`Item_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelter_emergency_item`
--

INSERT INTO `shelter_emergency_item` (`Shelter_ID`, `Item_ID`) VALUES
(1, 1),
(2, 1),
(3, 1),
(5, 1),
(7, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(1, 3),
(4, 3),
(6, 3),
(1, 4),
(3, 4),
(5, 4),
(2, 5),
(4, 5),
(3, 6),
(7, 6),
(2, 7),
(4, 7),
(6, 7),
(8, 3),
(8, 7),
(8, 16),
(9, 9),
(9, 11),
(9, 15),
(10, 2),
(10, 6),
(10, 15),
(10, 16),
(11, 3),
(11, 6),
(11, 7),
(11, 11),
(12, 6),
(12, 8),
(12, 11),
(12, 12),
(12, 13),
(12, 16),
(13, 1),
(13, 6),
(13, 9),
(13, 14),
(14, 9),
(14, 12),
(14, 13),
(15, 3),
(15, 8),
(15, 10),
(15, 14),
(16, 1),
(16, 8),
(16, 15),
(17, 2),
(17, 8),
(17, 16),
(18, 1),
(18, 2),
(18, 7),
(18, 15),
(18, 16),
(19, 5),
(19, 7),
(19, 8),
(19, 12),
(19, 13),
(19, 15),
(20, 4),
(20, 7),
(20, 8),
(20, 9),
(20, 15),
(20, 16);

-- --------------------------------------------------------

--
-- Table structure for table `shelter_assignment`
-- Which affected person stayed in which shelter during which disaster (an empty Check_Out = still staying).
--

DROP TABLE IF EXISTS `shelter_assignment`;
CREATE TABLE IF NOT EXISTS `shelter_assignment` (
  `Assignment_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  `Shelter_ID` int NOT NULL,
  `Check_In` date NOT NULL,
  `Check_Out` date DEFAULT NULL,
  PRIMARY KEY (`Assignment_ID`),
  UNIQUE KEY `uq_assignment_disaster_person` (`Disaster_ID`,`Person_ID`),
  KEY `fk_assignment_person` (`Person_ID`),
  KEY `fk_assignment_shelter` (`Shelter_ID`),
  CONSTRAINT `chk_assignment_dates` CHECK (`Check_Out` IS NULL OR `Check_Out` >= `Check_In`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shelter_assignment` (`Assignment_ID`, `Disaster_ID`, `Person_ID`, `Shelter_ID`, `Check_In`, `Check_Out`) VALUES
(1, 1, 1, 1, '2026-07-15', '2026-07-30'),
(2, 1, 2, 2, '2026-07-15', '2026-07-29'),
(3, 1, 3, 3, '2026-07-16', '2026-08-02'),
(4, 1, 6, 5, '2026-07-16', '2026-07-31'),
(5, 2, 4, 2, '2026-05-28', '2026-06-10'),
(6, 2, 5, 3, '2026-05-28', '2026-06-12'),
(7, 2, 7, 4, '2026-05-29', '2026-06-11'),
(8, 3, 1, 3, '2026-08-10', '2026-08-24'),
(9, 3, 5, 5, '2026-08-10', '2026-08-22'),
(10, 3, 8, 7, '2026-08-11', '2026-08-25'),
(11, 4, 3, 4, '2026-06-20', '2026-07-03'),
(12, 4, 6, 6, '2026-06-20', '2026-07-05'),
(13, 5, 2, 1, '2026-08-25', NULL),
(14, 5, 4, 5, '2026-08-25', NULL),
(15, 5, 7, 1, '2026-08-26', NULL),
(16, 5, 8, 5, '2026-08-26', NULL),
(17, 6, 12, 6, '2025-05-28', '2025-06-19'),
(18, 6, 15, 16, '2025-05-26', '2025-06-16'),
(19, 6, 20, 20, '2025-05-27', '2025-06-14'),
(20, 6, 23, 15, '2025-05-27', '2025-06-07'),
(21, 6, 37, 16, '2025-05-28', '2025-06-13'),
(22, 6, 44, 4, '2025-05-27', '2025-06-06'),
(23, 6, 51, 16, '2025-05-27', '2025-06-04'),
(24, 6, 54, 4, '2025-05-28', '2025-06-18'),
(25, 6, 55, 20, '2025-05-27', '2025-06-14'),
(26, 7, 9, 14, '2025-08-15', '2025-08-23'),
(27, 7, 11, 8, '2025-08-15', '2025-08-22'),
(28, 7, 16, 9, '2025-08-14', '2025-08-29'),
(29, 7, 21, 9, '2025-08-15', '2025-08-21'),
(30, 7, 25, 1, '2025-08-15', '2025-09-03'),
(31, 7, 33, 8, '2025-08-14', '2025-08-26'),
(32, 7, 36, 19, '2025-08-16', '2025-08-27'),
(33, 7, 45, 19, '2025-08-15', '2025-09-04'),
(34, 7, 49, 11, '2025-08-15', '2025-08-30'),
(35, 7, 51, 10, '2025-08-16', '2025-08-31'),
(36, 7, 54, 1, '2025-08-14', '2025-09-03'),
(37, 8, 12, 9, '2025-09-21', '2025-10-14'),
(38, 8, 13, 5, '2025-09-21', '2025-10-09'),
(39, 8, 16, 1, '2025-09-21', '2025-10-07'),
(40, 8, 23, 19, '2025-09-21', '2025-10-08'),
(41, 8, 47, 19, '2025-09-20', '2025-10-11'),
(42, 8, 48, 5, '2025-09-20', '2025-10-03'),
(43, 8, 49, 5, '2025-09-20', '2025-09-27'),
(44, 8, 53, 19, '2025-09-21', '2025-10-10'),
(45, 9, 10, 4, '2025-10-24', '2025-11-14'),
(46, 9, 26, 20, '2025-10-24', '2025-11-14'),
(47, 9, 33, 3, '2025-10-22', '2025-11-02'),
(48, 9, 35, 4, '2025-10-22', '2025-10-28'),
(49, 9, 38, 3, '2025-10-22', '2025-11-10'),
(50, 9, 40, 4, '2025-10-24', '2025-11-15'),
(51, 9, 53, 4, '2025-10-23', '2025-11-14'),
(52, 9, 57, 15, '2025-10-24', '2025-11-03'),
(53, 10, 21, 17, '2025-12-08', '2025-12-16'),
(54, 10, 26, 18, '2025-12-09', '2025-12-28'),
(55, 10, 39, 18, '2025-12-09', '2025-12-21'),
(56, 10, 43, 11, '2025-12-08', '2025-12-27'),
(57, 10, 44, 18, '2025-12-09', '2025-12-20'),
(58, 10, 60, 11, '2025-12-09', '2025-12-24'),
(59, 2, 6, 6, '2026-05-29', '2026-06-08'),
(60, 2, 27, 3, '2026-05-28', '2026-06-20'),
(61, 2, 41, 2, '2026-05-28', '2026-06-09'),
(62, 2, 43, 3, '2026-05-30', '2026-06-18'),
(63, 2, 58, 6, '2026-05-30', '2026-06-10'),
(64, 4, 12, 4, '2026-06-21', '2026-06-29'),
(65, 4, 14, 4, '2026-06-20', '2026-07-01'),
(66, 4, 30, 6, '2026-06-22', '2026-07-13'),
(67, 1, 16, 2, '2026-07-17', '2026-08-03'),
(68, 1, 18, 2, '2026-07-15', '2026-08-05'),
(69, 1, 26, 3, '2026-07-16', '2026-08-01'),
(70, 1, 35, 2, '2026-07-16', '2026-07-25'),
(71, 1, 60, 5, '2026-07-15', '2026-07-30'),
(72, 3, 28, 5, '2026-08-10', '2026-08-29'),
(73, 3, 35, 5, '2026-08-11', '2026-08-17'),
(74, 3, 47, 3, '2026-08-10', '2026-08-25'),
(75, 3, 56, 5, '2026-08-11', '2026-08-18'),
(76, 5, 11, 5, '2026-08-26', NULL),
(77, 5, 18, 1, '2026-08-25', NULL),
(78, 5, 30, 5, '2026-08-25', NULL),
(79, 5, 44, 1, '2026-08-25', NULL),
(80, 5, 53, 1, '2026-08-26', NULL),
(81, 11, 11, 2, '2026-09-20', '2026-10-02'),
(82, 11, 16, 3, '2026-09-20', NULL),
(83, 11, 18, 16, '2026-09-18', '2026-09-24'),
(84, 11, 31, 15, '2026-09-20', NULL),
(85, 11, 34, 3, '2026-09-19', NULL),
(86, 11, 38, 15, '2026-09-20', NULL),
(87, 11, 40, 3, '2026-09-18', '2026-09-29'),
(88, 11, 46, 2, '2026-09-20', NULL),
(89, 11, 55, 2, '2026-09-19', NULL),
(90, 11, 57, 3, '2026-09-19', '2026-09-30'),
(91, 12, 12, 11, '2026-10-04', NULL),
(92, 12, 17, 11, '2026-10-04', NULL),
(93, 12, 21, 11, '2026-10-03', NULL),
(94, 12, 22, 11, '2026-10-02', NULL),
(95, 12, 35, 11, '2026-10-04', NULL),
(96, 12, 49, 13, '2026-10-03', NULL),
(97, 12, 56, 19, '2026-10-02', NULL),
(98, 12, 57, 9, '2026-10-03', NULL),
(99, 12, 60, 10, '2026-10-04', NULL),
(100, 13, 1, 3, '2026-10-23', NULL),
(101, 13, 3, 20, '2026-10-23', NULL),
(102, 13, 20, 20, '2026-10-23', NULL),
(103, 13, 27, 3, '2026-10-24', NULL),
(104, 13, 41, 21, '2026-10-23', NULL),
(105, 13, 19, 21, '2026-10-23', NULL),
(106, 13, 29, 21, '2026-10-24', NULL),
(107, 13, 52, 2, '2026-10-24', NULL),
(108, 13, 5, 5, '2026-10-23', NULL),
(109, 13, 13, 5, '2026-10-24', NULL),
(110, 13, 32, 6, '2026-10-23', NULL),
(111, 13, 50, 6, '2026-10-24', NULL),
(112, 13, 37, 16, '2026-10-23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `relief_distribution`
-- Relief stock handed out: stock left of an item = relief_item.Quantity - SUM(relief_distribution.Quantity).
--

DROP TABLE IF EXISTS `relief_distribution`;
CREATE TABLE IF NOT EXISTS `relief_distribution` (
  `Distribution_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Relief_ID` int NOT NULL,
  `Batch_ID` int DEFAULT NULL,
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
  CONSTRAINT `chk_distribution_qty` CHECK (`Quantity` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `relief_distribution` (`Distribution_ID`, `Disaster_ID`, `Relief_ID`, `Shelter_ID`, `Org_ID`, `Quantity`, `Distribution_Date`) VALUES
(1, 1, 1, 1, 1, 200, '2026-07-16'),
(2, 1, 3, 2, 2, 300, '2026-07-16'),
(3, 1, 4, 3, 1, 80, '2026-07-17'),
(4, 2, 6, 4, 1, 150, '2026-05-29'),
(5, 2, 3, 6, 6, 250, '2026-05-29'),
(6, 3, 1, 5, 5, 100, '2026-08-11'),
(7, 4, 7, 6, 2, 20, '2026-06-21'),
(8, 5, 8, 1, 3, 120, '2026-08-26'),
(9, 5, 3, 5, 5, 200, '2026-08-26'),
(10, 1, 11, 3, 1, 40, '2026-07-22'),
(11, 1, 10, 5, 2, 110, '2026-07-21'),
(12, 1, 14, 3, 5, 165, '2026-07-18'),
(13, 2, 14, 2, 8, 140, '2026-06-08'),
(14, 2, 3, 3, 8, 675, '2026-05-30'),
(15, 2, 3, 4, 7, 230, '2026-06-01'),
(16, 3, 9, 5, 5, 35, '2026-08-18'),
(17, 3, 16, 7, 3, 165, '2026-08-21'),
(18, 3, 16, 3, 11, 60, '2026-08-19'),
(19, 4, 3, 6, 6, 435, '2026-06-22'),
(20, 4, 3, 6, 6, 850, '2026-06-30'),
(21, 4, 13, 4, 6, 30, '2026-06-22'),
(22, 5, 3, 5, 11, 610, '2026-09-06'),
(23, 5, 3, 5, 2, 590, '2026-08-31'),
(24, 6, 3, 20, 7, 485, '2025-05-29'),
(25, 6, 10, 16, 8, 140, '2025-05-31'),
(26, 6, 7, 2, 2, 25, '2025-05-29'),
(27, 6, 3, 15, 9, 885, '2025-05-29'),
(28, 6, 16, 2, 3, 230, '2025-05-29'),
(29, 7, 15, 19, 12, 165, '2025-08-24'),
(30, 7, 1, 19, 11, 275, '2025-08-22'),
(31, 7, 16, 10, 11, 125, '2025-08-23'),
(32, 7, 16, 11, 11, 255, '2025-08-21'),
(33, 7, 6, 10, 12, 175, '2025-08-24'),
(34, 8, 3, 13, 11, 620, '2025-09-26'),
(35, 8, 1, 9, 5, 230, '2025-09-26'),
(36, 8, 13, 13, 10, 50, '2025-09-21'),
(37, 8, 14, 9, 5, 75, '2025-09-24'),
(38, 8, 3, 9, 5, 730, '2025-09-25'),
(39, 8, 1, 13, 5, 215, '2025-09-28'),
(40, 9, 13, 12, 6, 25, '2025-10-23'),
(41, 9, 3, 3, 6, 290, '2025-11-01'),
(42, 9, 3, 4, 8, 255, '2025-11-01'),
(43, 9, 6, 15, 6, 70, '2025-10-28'),
(44, 9, 9, 3, 7, 40, '2025-10-30'),
(45, 9, 14, 12, 8, 135, '2025-11-03'),
(46, 10, 15, 7, 10, 80, '2025-12-09'),
(47, 10, 15, 11, 10, 105, '2025-12-18'),
(48, 10, 3, 18, 5, 695, '2025-12-10'),
(49, 10, 11, 7, 12, 65, '2025-12-20'),
(50, 10, 7, 7, 5, 20, '2025-12-10'),
(51, 11, 7, 20, 7, 20, '2026-09-22'),
(52, 11, 13, 12, 6, 20, '2026-09-26'),
(53, 11, 10, 6, 8, 215, '2026-09-21'),
(54, 11, 7, 12, 2, 15, '2026-09-24'),
(55, 11, 11, 3, 4, 50, '2026-09-22'),
(56, 11, 8, 12, 4, 115, '2026-09-19'),
(57, 12, 1, 14, 9, 170, '2026-10-07'),
(58, 12, 2, 11, 11, 80, '2026-10-06'),
(59, 12, 3, 10, 5, 790, '2026-10-08'),
(60, 12, 9, 10, 1, 15, '2026-10-11'),
(61, 13, 1, 3, 8, 120, '2026-10-24'),
(62, 13, 3, 20, 1, 400, '2026-10-24'),
(63, 13, 9, 16, 7, 10, '2026-10-24'),
(64, 13, 10, 21, 2, 60, '2026-10-24'),
(65, 13, 3, 6, 7, 150, '2026-10-24'),
(66, 13, 6, 5, 1, 30, '2026-10-24');

UPDATE `relief_distribution` d
JOIN `relief_batch` b ON b.`Relief_ID` = d.`Relief_ID` AND b.`Batch_Code` = CONCAT('LEGACY-', d.`Relief_ID`)
SET d.`Batch_ID` = b.`Batch_ID`;
ALTER TABLE `relief_distribution` MODIFY COLUMN `Batch_ID` int NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `relief_recipient`
-- Which affected family received which relief distribution. Families of a disaster with no row here have not received relief yet.
--

DROP TABLE IF EXISTS `relief_recipient`;
CREATE TABLE IF NOT EXISTS `relief_recipient` (
  `Distribution_ID` int NOT NULL,
  `Person_ID` int NOT NULL,
  PRIMARY KEY (`Distribution_ID`,`Person_ID`),
  KEY `fk_recipient_person` (`Person_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `relief_recipient` (`Distribution_ID`, `Person_ID`) VALUES
(57, 30),
(57, 49),
(57, 58),
(58, 12),
(58, 17),
(58, 21),
(58, 22),
(59, 33),
(59, 57),
(59, 60),
(61, 1),
(61, 26),
(61, 27),
(62, 3),
(62, 20),
(63, 15),
(63, 37),
(64, 41),
(65, 32),
(65, 50),
(66, 5),
(66, 6),
(66, 13);

-- --------------------------------------------------------

--
-- Table structure for table `disaster_track`
-- Path of a moving disaster (e.g. a cyclone): observed positions (Actual) and predicted ones (Forecast), with the radius of its impact.
--

DROP TABLE IF EXISTS `disaster_track`;
CREATE TABLE IF NOT EXISTS `disaster_track` (
  `Track_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Point_Time` datetime NOT NULL,
  `Point_Type` enum('Actual','Forecast') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Actual',
  `Latitude` decimal(9,6) NOT NULL,
  `Longitude` decimal(9,6) NOT NULL,
  `Radius_Km` int NOT NULL DEFAULT '25',
  `Wind_Speed_Kmh` int DEFAULT NULL,
  PRIMARY KEY (`Track_ID`),
  UNIQUE KEY `uq_track_disaster_time` (`Disaster_ID`,`Point_Time`),
  CONSTRAINT `chk_track_lat` CHECK (`Latitude` BETWEEN -90 AND 90),
  CONSTRAINT `chk_track_lng` CHECK (`Longitude` BETWEEN -180 AND 180),
  CONSTRAINT `chk_track_radius` CHECK (`Radius_Km` > 0),
  CONSTRAINT `chk_track_wind` CHECK (`Wind_Speed_Kmh` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disaster_track` (`Track_ID`, `Disaster_ID`, `Point_Time`, `Point_Type`, `Latitude`, `Longitude`, `Radius_Km`, `Wind_Speed_Kmh`) VALUES
(1, 13, '2026-10-22 06:00:00', 'Actual', 20.600000, 89.950000, 60, 75),
(2, 13, '2026-10-22 18:00:00', 'Actual', 21.050000, 89.850000, 55, 90),
(3, 13, '2026-10-23 06:00:00', 'Actual', 21.500000, 89.720000, 50, 110),
(4, 13, '2026-10-23 18:00:00', 'Actual', 21.950000, 89.580000, 40, 130),
(5, 13, '2026-10-24 00:00:00', 'Actual', 22.200000, 89.450000, 30, 140),
(6, 13, '2026-10-24 06:00:00', 'Actual', 22.380000, 89.360000, 25, 135),
(7, 13, '2026-10-24 12:00:00', 'Actual', 22.520000, 89.430000, 20, 115),
(8, 13, '2026-10-24 18:00:00', 'Forecast', 22.680000, 89.520000, 20, 90),
(9, 13, '2026-10-25 00:00:00', 'Forecast', 22.840000, 89.560000, 20, 70),
(10, 13, '2026-10-25 06:00:00', 'Forecast', 23.050000, 89.620000, 20, 55),
(11, 13, '2026-10-25 12:00:00', 'Forecast', 23.300000, 89.700000, 20, 40);

-- --------------------------------------------------------

--
-- Table structure for table `users` (sign-in accounts; created from the first-run screen, never edited through the generic table pages)
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `User_ID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password_Hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Role` enum('admin','editor','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `Failed_Logins` int NOT NULL DEFAULT '0',
  `Locked_Until` datetime DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`User_ID`),
  UNIQUE KEY `uq_users_username` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_task`
--

DROP TABLE IF EXISTS `incident_task`;
CREATE TABLE IF NOT EXISTS `incident_task` (
  `Task_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Task_Title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Assigned_To` int DEFAULT NULL,
  `Due_At` datetime DEFAULT NULL,
  `Status` enum('Open','In progress','Blocked','Done') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Open',
  `Created_By` int NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Task_ID`),
  KEY `fk_task_disaster` (`Disaster_ID`),
  KEY `fk_task_assigned` (`Assigned_To`),
  KEY `fk_task_creator` (`Created_By`),
  KEY `ix_task_due_status` (`Status`, `Due_At`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_update`
--

DROP TABLE IF EXISTS `incident_update`;
CREATE TABLE IF NOT EXISTS `incident_update` (
  `Update_ID` int NOT NULL AUTO_INCREMENT,
  `Disaster_ID` int NOT NULL,
  `Area_ID` int DEFAULT NULL,
  `Update_Type` enum('Situation','Shelter','Medical','Supplies','Safety') COLLATE utf8mb4_unicode_ci NOT NULL,
  `Summary` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Observed_At` datetime NOT NULL,
  `Reported_By` int NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Update_ID`),
  KEY `ix_update_disaster_time` (`Disaster_ID`, `Observed_At`),
  KEY `fk_update_area` (`Area_ID`),
  KEY `fk_update_reporter` (`Reported_By`),
  CONSTRAINT `fk_update_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_update_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_update_reporter` FOREIGN KEY (`Reported_By`) REFERENCES `users` (`User_ID`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log` (who added / changed / deleted what; read-only in the web pages)
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE IF NOT EXISTS `audit_log` (
  `Log_ID` int NOT NULL AUTO_INCREMENT,
  `User_Name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Action` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Table_Name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Record_Key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Log_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affected_person`
--
ALTER TABLE `affected_person`
  ADD CONSTRAINT `fk_person_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `affected_person_emergency_item`
--
ALTER TABLE `affected_person_emergency_item`
  ADD CONSTRAINT `fk_person_item_item` FOREIGN KEY (`Item_ID`) REFERENCES `emergency_item` (`Item_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_person_item_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `damage`
--
ALTER TABLE `damage`
  ADD CONSTRAINT `fk_damage_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_damage_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `disaster`
--
ALTER TABLE `disaster`
  ADD CONSTRAINT `fk_disaster_lead` FOREIGN KEY (`Incident_Lead_ID`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `incident_task`
--
ALTER TABLE `incident_task`
  ADD CONSTRAINT `fk_task_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_task_assigned` FOREIGN KEY (`Assigned_To`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_task_creator` FOREIGN KEY (`Created_By`) REFERENCES `users` (`User_ID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `disaster_affected_person`
--
ALTER TABLE `disaster_affected_person`
  ADD CONSTRAINT `fk_disaster_person_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disaster_person_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `disaster_area`
--
ALTER TABLE `disaster_area`
  ADD CONSTRAINT `fk_disaster_area_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disaster_area_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `disaster_rescue_org`
--
ALTER TABLE `disaster_rescue_org`
  ADD CONSTRAINT `fk_disaster_rescue_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disaster_rescue_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `disaster_shelter`
--
ALTER TABLE `disaster_shelter`
  ADD CONSTRAINT `fk_disaster_shelter_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disaster_shelter_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `disaster_track`
--
ALTER TABLE `disaster_track`
  ADD CONSTRAINT `fk_track_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `early_warning`
--
ALTER TABLE `early_warning`
  ADD CONSTRAINT `fk_early_warning_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `relief_distribution`
--
ALTER TABLE `relief_distribution`
  ADD CONSTRAINT `fk_distribution_batch` FOREIGN KEY (`Batch_ID`) REFERENCES `relief_batch` (`Batch_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_distribution_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_distribution_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_distribution_relief` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_distribution_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `relief_batch`
--
ALTER TABLE `relief_batch`
  ADD CONSTRAINT `fk_batch_relief` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_batch_receiver` FOREIGN KEY (`Received_By`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `relief_recipient`
--
ALTER TABLE `relief_recipient`
  ADD CONSTRAINT `fk_recipient_distribution` FOREIGN KEY (`Distribution_ID`) REFERENCES `relief_distribution` (`Distribution_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recipient_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shelter_assignment`
--
ALTER TABLE `shelter_assignment`
  ADD CONSTRAINT `fk_assignment_disaster` FOREIGN KEY (`Disaster_ID`) REFERENCES `disaster` (`Disaster_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_person` FOREIGN KEY (`Person_ID`) REFERENCES `affected_person` (`Person_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assignment_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `rescue_org_relief_item`
--
ALTER TABLE `rescue_org_relief_item`
  ADD CONSTRAINT `fk_org_relief_item` FOREIGN KEY (`Relief_ID`) REFERENCES `relief_item` (`Relief_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_org_relief_org` FOREIGN KEY (`Org_ID`) REFERENCES `rescue_org` (`Org_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shelter`
--
ALTER TABLE `shelter`
  ADD CONSTRAINT `fk_shelter_area` FOREIGN KEY (`Area_ID`) REFERENCES `area` (`Area_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shelter_capacity_verifier` FOREIGN KEY (`Capacity_Verified_By`) REFERENCES `users` (`User_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `shelter_emergency_item`
--
ALTER TABLE `shelter_emergency_item`
  ADD CONSTRAINT `fk_shelter_item_item` FOREIGN KEY (`Item_ID`) REFERENCES `emergency_item` (`Item_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_shelter_item_shelter` FOREIGN KEY (`Shelter_ID`) REFERENCES `shelter` (`Shelter_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

--
-- Integrity triggers (the same text as inc/triggers.sql)
--

SET SQL_MODE = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
