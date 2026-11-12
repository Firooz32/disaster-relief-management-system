-- Disaster Mapping & Relief Management System - sample data (DML only)
-- Run AFTER schema.sql. 3-5 realistic (fictional) rows per table, inserted parents-first, so FOREIGN_KEY_CHECKS stays ON
-- and every trigger / CHECK constraint is satisfied.
--
-- Scenario (Khulna division, Bangladesh, October 2026):
--   #1 a severe cyclone is crossing the coast near Koyra (Active, great danger signal 10, mapped path),
--   #2 a flash flood in Sonadanga is Under control, #3 an earlier cyclone over Dacope is Closed.
-- What the screens show with this data:
--   * Dacope Union Primary School Shelter is full (11 of 11 places) -> red "No space available";
--   * Paikgachha is short of relief (flagged by a coordinator, 0 of 4 people reached) -> red "Shortage";
--   * Rahim Uddin's family moved from Koyra Government Shelter to Paikgachha Relief Shelter during cyclone #1;
--   * on the path map Paikgachha and Dacope are being crossed now, Koyra was passed, Sonadanga is in the forecast path.
--
-- Demo sign-in accounts (change or delete them before any real use):
--   demo_admin / DemoAdmin2026   (admin)   demo_editor / DemoEditor2026   (editor)   demo_viewer / DemoViewer2026   (viewer)

USE `disaster_and_relief_management`;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------- independent tables
INSERT INTO `users` (`User_ID`, `Username`, `Password_Hash`, `Role`) VALUES
(1, 'demo_admin',  '$2y$10$.HFVQJBvn2acWwA6zZV8Hefct3A/Xlbjr0FJNyJDjwovaeopOYxki', 'admin'),
(2, 'demo_editor', '$2y$10$WC01toyKKS.Gdpk5RVT7zeKXfRs62fGZhbG94BlwlrlWIe/H50vPy', 'editor'),
(3, 'demo_viewer', '$2y$10$W5lviT5TFHR.6N6TDOfVwuyrkptKjcgsS3CdVd9sxVv5ldefa9Upq', 'viewer');

INSERT INTO `area` (`Area_ID`, `Area_Name`, `Location`, `Risk_Level`, `Latitude`, `Longitude`) VALUES
(1, 'Koyra',      'Khulna, Bangladesh', 'Very High', 22.340000, 89.310000),
(2, 'Dacope',     'Khulna, Bangladesh', 'Very High', 22.570000, 89.515000),
(3, 'Paikgachha', 'Khulna, Bangladesh', 'High',      22.578000, 89.330000),
(4, 'Sonadanga',  'Khulna, Bangladesh', 'High',      22.798000, 89.539000);

INSERT INTO `emergency_item` (`Item_ID`, `Item_Name`, `Category`) VALUES
(1, 'Insulin',              'Medicine'),
(2, 'Wheelchair',           'Mobility aid'),
(3, 'Infant formula',       'Child care'),
(4, 'Blood pressure tablets', 'Medicine');

INSERT INTO `relief_item` (`Relief_ID`, `Item_Name`, `Category`, `Quantity`) VALUES
(1, 'Rice (kg)',                 'Food',     1200),
(2, 'Drinking water (1 L bottle)', 'Water',  3000),
(3, 'Oral rehydration saline',   'Medicine', 1500),
(4, 'Tarpaulin sheet',           'Shelter',   400);

INSERT INTO `rescue_org` (`Org_ID`, `Org_Name`, `Org_Type`, `Contact`) VALUES
(1, 'Bangladesh Red Crescent Society - Khulna Unit', 'NGO',        '+880-41-0000101'),
(2, 'Fire Service and Civil Defence, Khulna',        'Government', '+880-41-0000102'),
(3, 'Coastal Volunteers Network',                    'Volunteer',  '+880-1700-000103');

INSERT INTO `audit_log` (`Log_ID`, `User_Name`, `Action`, `Table_Name`, `Record_Key`, `Details`, `Created_At`) VALUES
(1, 'demo_admin',  'add',    'disaster',           'Disaster_ID=1', 'Disaster_Type = Cyclone; Disaster_Date = 2026-10-24; Status = Reported', '2026-10-22 08:10:00'),
(2, 'demo_admin',  'edit',   'disaster',           'Disaster_ID=1', 'Status: Reported -> Active', '2026-10-22 12:05:00'),
(3, 'demo_editor', 'add',    'shelter_assignment', 'Assignment_ID=3', 'Disaster_ID = 1; Person_ID = 2; Shelter_ID = 5; Check_In = 2026-10-23', '2026-10-23 09:40:00'),
(4, 'demo_editor', 'verify', 'shelter',            'Shelter_ID=5', 'Shelter capacity and operational details reviewed and confirmed current.', '2026-10-24 07:30:00');

-- ---------------------------------------------------------------- tables that reference the ones above
INSERT INTO `rescue_org_relief_item` (`Org_ID`, `Relief_ID`) VALUES
(1, 1), (1, 2), (1, 3), (3, 1), (3, 4);

INSERT INTO `affected_person` (`Person_ID`, `Name`, `Family_Size`, `Contact`, `Area_ID`) VALUES
(1, 'Rahim Uddin',    5, '+880-1711-000201', 1),
(2, 'Fatema Begum',   6, '+880-1811-000202', 2),
(3, 'Abdul Karim',    5, '+880-1911-000203', 2),
(4, 'Nasima Khatun',  4, NULL,               3),
(5, 'Jamal Hossain',  3, '+880-1611-000205', 4);

INSERT INTO `affected_person_emergency_item` (`Person_ID`, `Item_ID`) VALUES
(1, 1), (2, 3), (3, 4), (4, 2);

INSERT INTO `disaster` (`Disaster_ID`, `Disaster_Type`, `Disaster_Date`, `Description`, `Status`, `Incident_Lead_ID`, `Status_Changed_At`, `Closed_At`) VALUES
(1, 'Cyclone', '2026-10-24', 'A severe cyclonic storm crossed the coast near Koyra and is moving north over Paikgachha and Dacope towards Khulna city.', 'Active', 1, '2026-10-22 12:05:00', NULL),
(2, 'Flood',   '2026-10-02', 'Heavy rain and blocked drains caused waterlogging and flash flooding in low-lying wards of Sonadanga.', 'Under control', 2, '2026-10-08 17:00:00', NULL),
(3, 'Cyclone', '2026-09-18', 'A cyclone damaged embankments and power lines in Dacope overnight; all families have returned home.', 'Closed', 1, '2026-09-30 10:00:00', '2026-09-30 10:00:00');

INSERT INTO `disaster_affected_person` (`Disaster_ID`, `Person_ID`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (2, 5);

INSERT INTO `disaster_area` (`Disaster_ID`, `Area_ID`, `Relief_Shortage`) VALUES
(1, 1, 'No'), (1, 2, 'No'), (1, 3, 'Yes'), (2, 4, 'No'), (3, 2, 'No');

INSERT INTO `disaster_rescue_org` (`Disaster_ID`, `Org_ID`) VALUES
(1, 1), (1, 2), (1, 3), (2, 1);

INSERT INTO `disaster_track` (`Track_ID`, `Disaster_ID`, `Point_Time`, `Point_Type`, `Latitude`, `Longitude`, `Radius_Km`, `Wind_Speed_Kmh`) VALUES
(1, 1, '2026-10-24 00:00:00', 'Actual',   22.200000, 89.450000, 30, 140),
(2, 1, '2026-10-24 06:00:00', 'Actual',   22.380000, 89.360000, 25, 135),
(3, 1, '2026-10-24 12:00:00', 'Actual',   22.520000, 89.430000, 20, 115),
(4, 1, '2026-10-24 18:00:00', 'Forecast', 22.680000, 89.520000, 20,  90),
(5, 1, '2026-10-25 00:00:00', 'Forecast', 22.840000, 89.560000, 20,  70);

INSERT INTO `early_warning` (`Alert_ID`, `Disaster_ID`, `Alert_Type`, `Signal_Level`, `Alert_DateTime`, `Message`) VALUES
(1, 1, 'Cyclone Warning',     4,  '2026-10-22 12:00:00', 'The cyclone is moving north. Local warning signal 4 for Mongla port. Fishing boats must return to shore.'),
(2, 1, 'Danger Signal',       7,  '2026-10-23 10:00:00', 'The cyclone may cross the Khulna coast within 24 hours. Move children, elderly and disabled people to shelters now.'),
(3, 1, 'Great Danger Signal', 10, '2026-10-24 03:00:00', 'A severe cyclone is crossing the coast near Koyra. Great danger signal 10: everyone in coastal unions must be in a shelter.'),
(4, 2, 'Flood Warning',       5,  '2026-10-02 07:30:00', 'Water level crossed the danger mark in Sonadanga. Move families from ground floors to higher places.'),
(5, 3, 'Evacuation Order',    9,  '2026-09-18 05:15:00', 'Landfall expected within hours. All Dacope coastal families must leave for cyclone shelters.');

INSERT INTO `incident_task` (`Task_ID`, `Disaster_ID`, `Task_Title`, `Details`, `Assigned_To`, `Due_At`, `Status`, `Created_By`, `Created_At`) VALUES
(1, 1, 'Evacuate remaining families from Koyra embankment', 'Two boats from Fire Service; check the list of elderly residents first.', 2, '2026-10-24 06:00:00', 'Done',        1, '2026-10-23 11:00:00'),
(2, 1, 'Deliver oral saline to Paikgachha Relief Shelter',  'Coordinator reports diarrhoea cases; 300 packets needed.',               2, '2026-10-25 12:00:00', 'In progress', 1, '2026-10-24 13:00:00'),
(3, 1, 'Re-check capacity of Dacope Union Primary School',  'Shelter reported full; confirm whether the second floor can be opened.', 1, '2026-10-25 09:00:00', 'Open',        2, '2026-10-24 14:30:00'),
(4, 2, 'Clear blocked drains on Sonadanga main road',       'Waiting for the city corporation pump.',                                 2, '2026-10-06 18:00:00', 'Blocked',     1, '2026-10-03 09:00:00');

INSERT INTO `incident_update` (`Update_ID`, `Disaster_ID`, `Area_ID`, `Update_Type`, `Summary`, `Details`, `Observed_At`, `Reported_By`, `Created_At`) VALUES
(1, 1, 1, 'Safety',   'Koyra embankment breached at two points', 'Water entered three villages behind the embankment; all residents were moved before 06:00.', '2026-10-24 07:15:00', 2, '2026-10-24 07:20:00'),
(2, 1, 2, 'Shelter',  'Dacope school shelter is full',           'Eleven people from two families are staying; new arrivals are sent to Dacope Cyclone Shelter.', '2026-10-24 09:00:00', 2, '2026-10-24 09:05:00'),
(3, 1, 3, 'Supplies', 'No relief reached Paikgachha yet',        'Road to Paikgachha is cut by fallen trees; relief is short until it is cleared.',             '2026-10-24 13:30:00', 1, '2026-10-24 13:35:00'),
(4, 2, 4, 'Situation', 'Water receding in Sonadanga',            'Main road is passable again; drains still blocked in two wards.',                               '2026-10-08 16:00:00', 2, '2026-10-08 16:10:00');

INSERT INTO `relief_batch` (`Batch_ID`, `Relief_ID`, `Batch_Code`, `Quantity_Received`, `Received_At`, `Expiry_Date`, `Source`, `Storage_Location`, `Notes`, `Received_By`) VALUES
(1, 1, 'RICE-2026-10-A', 1200, '2026-10-01', '2027-04-30', 'District relief office',      'Khulna central warehouse', NULL,                     1),
(2, 2, 'WATR-2026-10-A', 3000, '2026-10-02', '2027-10-01', 'Bangladesh Red Crescent Society', 'Khulna central warehouse', NULL,                 2),
(3, 3, 'ORS-2026-10-A',  1500, '2026-10-20', '2028-03-31', 'Upazila health complex',      'Koyra field store',        'Keep dry',               2),
(4, 4, 'TARP-2026-10-A',  400, '2026-10-21', NULL,         'Coastal Volunteers Network',  'Dacope field store',       'No expiry for tarpaulin', 1);

INSERT INTO `shelter` (`Shelter_ID`, `Shelter_Name`, `Location`, `Capacity`, `Area_ID`, `Latitude`, `Longitude`, `Services`, `Accessibility_Notes`, `Capacity_Verified_At`, `Capacity_Verified_By`) VALUES
(1, 'Koyra Government Shelter',            'Koyra Sadar, Khulna',      700, 1, 22.345000, 89.315000, 'Drinking water, toilets, first aid',             'Ramp at the main entrance',         '2026-10-23 08:00:00', 2),
(2, 'Dacope Cyclone Shelter',              'Chalna, Dacope, Khulna',   800, 2, 22.575000, 89.520000, 'Drinking water, toilets, generator',             'Ground floor reserved for elderly', '2026-10-23 08:30:00', 2),
(3, 'Paikgachha Relief Shelter',           'Paikgachha Sadar, Khulna', 450, 3, 22.583000, 89.335000, 'Toilets, first aid',                             NULL,                                '2026-10-23 09:00:00', 1),
(4, 'Sonadanga Community Shelter',         'Sonadanga, Khulna city',   500, 4, 22.803000, 89.544000, 'Drinking water, toilets, kitchen, first aid',    'Lift to the first floor',           '2026-10-03 10:00:00', 1),
(5, 'Dacope Union Primary School Shelter', 'Bajua, Dacope, Khulna',     11, 2, 22.565200, 89.508900, 'Toilets only',                                   'Stairs only, no ramp',              '2026-10-24 07:30:00', 2);

-- ---------------------------------------------------------------- tables that need a shelter
INSERT INTO `shelter_emergency_item` (`Shelter_ID`, `Item_ID`) VALUES
(1, 1), (2, 2), (4, 4), (5, 3);

INSERT INTO `disaster_shelter` (`Disaster_ID`, `Shelter_ID`) VALUES
(1, 1), (1, 3), (1, 5), (2, 4), (3, 2);

-- Rahim Uddin's family (5) moved from Koyra to Paikgachha when the embankment broke; the school shelter holds 6 + 5 = 11 of 11.
INSERT INTO `shelter_assignment` (`Assignment_ID`, `Disaster_ID`, `Person_ID`, `Shelter_ID`, `Check_In`, `Check_Out`) VALUES
(1, 1, 1, 1, '2026-10-23', '2026-10-24'),
(2, 1, 1, 3, '2026-10-24', NULL),
(3, 1, 2, 5, '2026-10-23', NULL),
(4, 1, 3, 5, '2026-10-23', NULL),
(5, 2, 5, 4, '2026-10-02', '2026-10-08');

INSERT INTO `damage` (`Damage_ID`, `Disaster_ID`, `Damage_Type`, `Area_ID`, `Description`, `Quantity`, `Estimated_Loss`) VALUES
(1, 1, 'Houses destroyed',     1, 'Mud houses behind the breached embankment collapsed.',   120, 36000000.00),
(2, 1, 'Embankment breached',  2, 'Two sections of the polder embankment washed away.',       2, 25000000.00),
(3, 1, 'Shrimp farms flooded', 3, 'Saline water entered shrimp and fish enclosures.',       450, 54000000.00),
(4, 2, 'Roads damaged',        4, 'Waterlogging broke the surface of three ward roads.',      3,  6500000.00),
(5, 3, 'Power lines down',     2, 'Poles and lines fell in four unions of Dacope.',          40,  8000000.00);

-- ---------------------------------------------------------------- relief given out (stock is never exceeded)
INSERT INTO `relief_distribution` (`Distribution_ID`, `Disaster_ID`, `Relief_ID`, `Batch_ID`, `Shelter_ID`, `Org_ID`, `Quantity`, `Distribution_Date`) VALUES
(1, 1, 3, 3, 1, 1, 200, '2026-10-23'),
(2, 1, 2, 2, 5, 1, 600, '2026-10-24'),
(3, 1, 1, 1, 5, 3, 300, '2026-10-24'),
(4, 2, 1, 1, 4, 1, 150, '2026-10-04');

INSERT INTO `relief_recipient` (`Distribution_ID`, `Person_ID`) VALUES
(1, 1), (2, 2), (2, 3), (4, 5);
