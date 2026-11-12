# Relational Schema

Generated from the database by `tools/make_erd.php` (do not edit by hand).

25 tables, 40 foreign keys (8 ON DELETE SET NULL, 24 ON DELETE CASCADE, 8 ON DELETE RESTRICT), 18 CHECK constraints, 8 triggers. Notation: underlined = primary key, *italic* = foreign key.

### affected_person

affected_person(<u>Person_ID</u>, Name, Family_Size, Contact, *Area_ID*)

- FK `Area_ID` -> `area(Area_ID)` ON DELETE SET NULL
- CHECK (Family_Size > 0)

### affected_person_emergency_item

affected_person_emergency_item(*<u>Person_ID</u>*, *<u>Item_ID</u>*)

- FK `Item_ID` -> `emergency_item(Item_ID)` ON DELETE CASCADE
- FK `Person_ID` -> `affected_person(Person_ID)` ON DELETE CASCADE

### area

area(<u>Area_ID</u>, Area_Name, Location, Risk_Level, Latitude, Longitude)

- UNIQUE (Area_Name,Location)
- CHECK (Latitude between -(90) and 90)
- CHECK (Longitude between -(180) and 180)

### audit_log

audit_log(<u>Log_ID</u>, User_Name, Action, Table_Name, Record_Key, Details, Created_At)


### damage

damage(<u>Damage_ID</u>, *Disaster_ID*, Damage_Type, *Area_ID*, Description, Quantity, Estimated_Loss)

- FK `Area_ID` -> `area(Area_ID)` ON DELETE SET NULL
- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- CHECK (Estimated_Loss >= 0)
- CHECK (Quantity >= 0)

### disaster

disaster(<u>Disaster_ID</u>, Disaster_Type, Disaster_Date, Description, Status, *Incident_Lead_ID*, Status_Changed_At, Closed_At)

- FK `Incident_Lead_ID` -> `users(User_ID)` ON DELETE SET NULL

### disaster_affected_person

disaster_affected_person(*<u>Disaster_ID</u>*, *<u>Person_ID</u>*)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Person_ID` -> `affected_person(Person_ID)` ON DELETE CASCADE

### disaster_area

disaster_area(*<u>Disaster_ID</u>*, *<u>Area_ID</u>*, Relief_Shortage)

- FK `Area_ID` -> `area(Area_ID)` ON DELETE CASCADE
- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE

### disaster_rescue_org

disaster_rescue_org(*<u>Disaster_ID</u>*, *<u>Org_ID</u>*)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Org_ID` -> `rescue_org(Org_ID)` ON DELETE CASCADE

### disaster_shelter

disaster_shelter(*<u>Disaster_ID</u>*, *<u>Shelter_ID</u>*)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Shelter_ID` -> `shelter(Shelter_ID)` ON DELETE CASCADE

### disaster_track

disaster_track(<u>Track_ID</u>, *Disaster_ID*, Point_Time, Point_Type, Latitude, Longitude, Radius_Km, Wind_Speed_Kmh)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- UNIQUE (Disaster_ID,Point_Time)
- CHECK (Latitude between -(90) and 90)
- CHECK (Longitude between -(180) and 180)
- CHECK (Radius_Km > 0)
- CHECK (Wind_Speed_Kmh >= 0)

### early_warning

early_warning(<u>Alert_ID</u>, *Disaster_ID*, Alert_Type, Signal_Level, Alert_DateTime, Message)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- CHECK (Signal_Level between 1 and 10)

### emergency_item

emergency_item(<u>Item_ID</u>, Item_Name, Category)

- UNIQUE (Item_Name)

### incident_task

incident_task(<u>Task_ID</u>, *Disaster_ID*, Task_Title, Details, *Assigned_To*, Due_At, Status, *Created_By*, Created_At, Updated_At)

- FK `Assigned_To` -> `users(User_ID)` ON DELETE SET NULL
- FK `Created_By` -> `users(User_ID)` ON DELETE RESTRICT
- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE

### incident_update

incident_update(<u>Update_ID</u>, *Disaster_ID*, *Area_ID*, Update_Type, Summary, Details, Observed_At, *Reported_By*, Created_At)

- FK `Area_ID` -> `area(Area_ID)` ON DELETE SET NULL
- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Reported_By` -> `users(User_ID)` ON DELETE RESTRICT

### relief_batch

relief_batch(<u>Batch_ID</u>, *Relief_ID*, Batch_Code, Quantity_Received, Received_At, Expiry_Date, Source, Storage_Location, Notes, *Received_By*, Created_At)

- FK `Received_By` -> `users(User_ID)` ON DELETE SET NULL
- FK `Relief_ID` -> `relief_item(Relief_ID)` ON DELETE RESTRICT
- UNIQUE (Batch_Code)
- CHECK ((Expiry_Date is null) or (Received_At is null) or (Expiry_Date >= Received_At))
- CHECK (Quantity_Received > 0)

### relief_distribution

relief_distribution(<u>Distribution_ID</u>, *Disaster_ID*, *Relief_ID*, *Batch_ID*, *Shelter_ID*, *Org_ID*, Quantity, Distribution_Date)

- FK `Batch_ID` -> `relief_batch(Batch_ID)` ON DELETE RESTRICT
- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Org_ID` -> `rescue_org(Org_ID)` ON DELETE SET NULL
- FK `Relief_ID` -> `relief_item(Relief_ID)` ON DELETE RESTRICT
- FK `Shelter_ID` -> `shelter(Shelter_ID)` ON DELETE RESTRICT
- CHECK (Quantity > 0)

### relief_item

relief_item(<u>Relief_ID</u>, Item_Name, Category, Quantity)

- UNIQUE (Item_Name)
- CHECK (Quantity >= 0)

### relief_recipient

relief_recipient(*<u>Distribution_ID</u>*, *<u>Person_ID</u>*)

- FK `Distribution_ID` -> `relief_distribution(Distribution_ID)` ON DELETE CASCADE
- FK `Person_ID` -> `affected_person(Person_ID)` ON DELETE CASCADE

### rescue_org

rescue_org(<u>Org_ID</u>, Org_Name, Org_Type, Contact)

- UNIQUE (Org_Name)

### rescue_org_relief_item

rescue_org_relief_item(*<u>Org_ID</u>*, *<u>Relief_ID</u>*)

- FK `Org_ID` -> `rescue_org(Org_ID)` ON DELETE CASCADE
- FK `Relief_ID` -> `relief_item(Relief_ID)` ON DELETE CASCADE

### shelter

shelter(<u>Shelter_ID</u>, Shelter_Name, Location, Capacity, *Area_ID*, Latitude, Longitude, Services, Accessibility_Notes, Capacity_Verified_At, *Capacity_Verified_By*)

- FK `Area_ID` -> `area(Area_ID)` ON DELETE RESTRICT
- FK `Capacity_Verified_By` -> `users(User_ID)` ON DELETE SET NULL
- UNIQUE (Shelter_Name)
- CHECK (Capacity > 0)
- CHECK (Latitude between -(90) and 90)
- CHECK (Longitude between -(180) and 180)

### shelter_assignment

shelter_assignment(<u>Assignment_ID</u>, *Disaster_ID*, *Person_ID*, *Shelter_ID*, Check_In, Check_Out)

- FK `Disaster_ID` -> `disaster(Disaster_ID)` ON DELETE CASCADE
- FK `Person_ID` -> `affected_person(Person_ID)` ON DELETE CASCADE
- FK `Shelter_ID` -> `shelter(Shelter_ID)` ON DELETE RESTRICT
- UNIQUE (if((`Check_Out` is null),`Person_ID`,NULL))
- CHECK ((Check_Out is null) or (Check_Out >= Check_In))

### shelter_emergency_item

shelter_emergency_item(*<u>Shelter_ID</u>*, *<u>Item_ID</u>*)

- FK `Item_ID` -> `emergency_item(Item_ID)` ON DELETE CASCADE
- FK `Shelter_ID` -> `shelter(Shelter_ID)` ON DELETE CASCADE

### users

users(<u>User_ID</u>, Username, Password_Hash, Role, Failed_Logins, Locked_Until, Created_At)

- UNIQUE (Username)

