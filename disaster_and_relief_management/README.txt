Disaster Mapping & Relief Management System  (PHP + MySQL, WampServer)
=====================================================================
সম্পূর্ণ বিবরণ ইংরেজিতে: README.md   |   Full details in English: README.md

কীভাবে চালাবেন / How to run
1. WampServer চালু করুন (আইকন সবুজ হতে হবে)।
2. এই পুরো  disaster_and_relief_management  ফোল্ডারটা  C:\wamp64\www\  এর ভেতরে পেস্ট করুন।
3. MySQL-এর user/password config.php ফাইলে দিন (ডিফল্ট: root, পাসওয়ার্ড ফাঁকা)। শুধু user/pass বদলান, name বদলাবেন না।
4. ব্রাউজারে খুলুন:  http://localhost/disaster_and_relief_management/
5. প্রথমবার খুললে database, ২২টা table, ৮টা trigger ও নমুনা ডেটা নিজে নিজে তৈরি হয় (phpMyAdmin-এ import লাগবে না)।
   তারপর "First run" পেজে নিজের admin user name ও password (কমপক্ষে ৮ অক্ষর) তৈরি করুন।
   Apache ছাড়া চালাতে:  start_server.bat  ডাবল-ক্লিক করুন  ->  http://127.0.0.1:8099/
   এক ক্লিকে localhost চালাতে (MySQL + Apache):  উপরের ফোল্ডারের start_localhost.bat  ->  http://localhost/disaster_and_relief_management/
   Apache চালু না হলে (apache_error.log-এ "VCRUNTIME140.dll ... not compatible"): C:\wamp64\bin\php\php8.2.13\vcruntime140.dll ফাইলটা
   C:\wamp64\bin\apache\apache2.4.58\bin\ ফোল্ডারে কপি করে Apache আবার চালু করুন।

লগইন ও role
   admin  : সবকিছু + "Users" পেজ + disaster মোছার অনুমতি
   editor : record add / edit / delete করতে পারে (কিন্তু disaster মুছতে পারে না)
   viewer : শুধু দেখতে, সার্চ ও CSV export করতে পারে
   ৫ বার ভুল password দিলে account ৫ মিনিট lock হয়। ৪ ঘণ্টা কিছু না করলে নিজে থেকে sign out হয়।
   নিজের password বদলাতে উপরের ডানদিকে নামে ক্লিক করুন।

Live Report (প্রতি ৫ সেকেন্ডে নিজে থেকে আপডেট)
   1. Warning signal   : সিগন্যাল ১-৩ = সবুজ "Normal", ৪-৭ = কমলা "Danger", ৮-১০ = লাল "Severe Danger"
                         "Check my area" দিয়ে যেকোনো এলাকার সিগন্যাল দেখা যায়
   2. Disaster path    : মানচিত্রে ঝড়ের চলার পথ (সলিড লাইন), পূর্বাভাস (ড্যাশ লাইন), বর্তমান অবস্থান ও প্রভাবের বৃত্ত;
                         "এখন যে এলাকা পার হচ্ছে" / "পথে পড়বে" / "পার হয়ে গেছে" তালিকা (ইন্টারনেট লাগে, শুধু মানচিত্রের জন্য)
   3. Shelter space    : কত জায়গা বাকি - জায়গা থাকলে সবুজ + সংখ্যা, ০ হলে লাল "No space available";
                         এলাকা ধরে ফিল্টার, কাছের shelter আগে ("Nearest to" বা "Use my location")
   4. Relief           : ত্রাণ পেয়েছেন কতজন - স্বাভাবিক হলে সবুজ + সংখ্যা; ঘাটতি হলে লাল + "পেয়েছেন" ও "পাননি" দুটোই।
                         ঘাটতি = ক্ষতিগ্রস্তদের ৮০%-এর কম পেলে, অথবা Disaster Area টেবিলে Relief Shortage = Yes দিলে
   আগের কার্ড ও টেবিলগুলো আগের মতোই নিচে আছে।

অন্যান্য পেজ
   Dashboard      : মোট সংখ্যা, "Live status" কার্ড, "Needs attention" তালিকা
   Live Weather   : Khulna-র current weather, forecast ও interactive map (Ventusky দ্বারা চালিত; ইন্টারনেট লাগে)
   Data Health    : ১৭টি পরীক্ষা - ডেটা নিজের সাথে মিলছে কিনা (shelter-এ ধারণক্ষমতার বেশি, ভুল disaster-এর shelter ইত্যাদি)
   Disaster Report: একটি দুর্যোগের পূর্ণ রিপোর্ট (সিগন্যাল, পথ, ক্ষতি, shelter, ত্রাণ, এলাকাভিত্তিক ত্রাণ-কভারেজ) - প্রিন্ট করা যায়
   Map            : ঝুঁকিপূর্ণ এলাকা ও shelter মানচিত্রে
   Search         : উপরের বক্সে সব table একসাথে খোঁজা যায়
   Audit Log      : কে কখন কী add/edit/delete করেছে এবং আগে-পরে কী ছিল (শুধু দেখা যায়)
   table পেজ      : কলামের নামে ক্লিক করে sort, Export CSV, delete-এর আগে কোন কোন record-ও মুছে যাবে তার তালিকা

ডেটা নিয়ম (Rules)
   shelter-এর capacity বা relief stock পার হলে, ভুল disaster-এর shelter/পরিবার/সংস্থা দিলে সেভ হয় না।
   এই নিয়মগুলো database-এর trigger দিয়েও রক্ষা করা হয় (phpMyAdmin থেকে বদলালেও)।

পুরনো ডেটাবেস থেকে আপগ্রেড
   আগের ২০-table ভার্সনের database থাকলে যেকোনো পেজ খুললেই নিজে নিজে আপগ্রেড হয় - আপনার ডেটা ও user মোছা হয় না।
   (এই একবারের জন্য config.php-তে root ব্যবহার করুন; সীমিত user-এর table বদলানোর অধিকার থাকে না।)
   অন্য কোনো অসম্পূর্ণ database থাকলে কিছুই বদলানো হয় না; দরকারি ডেটা backup নিয়ে phpMyAdmin থেকে Drop করে পেজ রিফ্রেশ দিন।

নিরাপত্তা / Security
   - config.php, *.sql, *.txt, *.md, inc/, tests/ ব্রাউজার থেকে বন্ধ (.htaccess / router.php)।
   - root ব্যবহার না করে create_db_user.sql চালিয়ে সীমিত-অধিকারের user বানান, তারপর config.php-তে সেটা দিন।
   - MySQL এর আসল error ব্রাউজারে দেখানো হয় না; PHP error log-এ যায়।
   - CSRF token, prepared statements, Content-Security-Policy, bcrypt password, account lock, audit log।
   - ইন্টারনেটে খুলে দিলে HTTPS ব্যবহার করুন।

টেস্ট (ঐচ্ছিক)
   প্রজেক্ট ফোল্ডারে:  php tests/smoke.php      (MySQL চালু থাকতে হবে; ডেটা বদলায় না; ৮১টি পরীক্ষা)

ডেটাবেস নতুন করে বানাতে চাইলে
   phpMyAdmin থেকে disaster_and_relief_management database টা Drop করে পেজ রিফ্রেশ দিন (admin account আবার বানাতে হবে)।

Live Report গতি বদলাতে: assets/report.js-এ REFRESH_MS (মিলিসেকেন্ড) বদলান।

English summary
   Copy the folder into www, set the MySQL login in config.php, open index.php, create the admin account on the first-run screen.
   The Live Report shows warning signals (1-3 green, 4-7 orange, 8-10 red), the live disaster path on a map, shelter space
   (green with places left / red "No space available"), and relief status (green with received count / red with received and not received).
   An older 20-table database is upgraded in place (data and users are kept). Run "php tests/smoke.php" for the automated checks.
