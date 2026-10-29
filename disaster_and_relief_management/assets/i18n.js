(function () {
  var translations = {
    'Disaster & Relief': 'দুর্যোগ ও ত্রাণ',
    'Management System': 'ব্যবস্থাপনা সিস্টেম',
    'Disaster Mapping & Relief Management System': 'দুর্যোগ মানচিত্র ও ত্রাণ ব্যবস্থাপনা সিস্টেম',
    'Disaster & Relief Management': 'দুর্যোগ ও ত্রাণ ব্যবস্থাপনা',
    'Skip to main content': 'মূল অংশে যান',
    'Dashboard': 'ড্যাশবোর্ড',
    'Disaster Report': 'দুর্যোগ প্রতিবেদন',
    'Map': 'মানচিত্র',
    'Live Report': 'লাইভ প্রতিবেদন',
    'Live Weather': 'লাইভ আবহাওয়া',
    'Data Health': 'তথ্য যাচাই',
    'Inventory & stock': 'মজুত ও সরবরাহ',
    'Users': 'ব্যবহারকারী',
    'Main tables': 'প্রধান টেবিল',
    'Relationship tables': 'সম্পর্কিত টেবিল',
    'Menu': 'মেনু',
    'Search everything...': 'সবকিছু খুঁজুন...',
    'Search all tables': 'সব টেবিলে খুঁজুন',
    'Change my password': 'পাসওয়ার্ড পরিবর্তন',
    'Sign out': 'সাইন আউট',
    'Switch to Bangla': 'বাংলায় দেখুন',
    'Switch to English': 'ইংরেজিতে দেখুন',
    'Delete this user?': 'এই ব্যবহারকারীকে মুছে ফেলবেন?',
    'Dashboard - Disaster & Relief Management': 'ড্যাশবোর্ড - দুর্যোগ ও ত্রাণ ব্যবস্থাপনা',
    'Sign in - Disaster & Relief Management': 'সাইন ইন - দুর্যোগ ও ত্রাণ ব্যবস্থাপনা',
    'Dashboard overview': 'ড্যাশবোর্ডের সারসংক্ষেপ',
    'Disasters on record': 'নথিভুক্ত দুর্যোগ',
    'Families affected': 'ক্ষতিগ্রস্ত পরিবার',
    'Shelter locations': 'আশ্রয়কেন্দ্র',
    'Rescue organisations': 'উদ্ধারকারী সংস্থা',
    'Early warnings issued': 'জারি করা আগাম সতর্কতা',
    'Total shelter places': 'আশ্রয়কেন্দ্রের মোট আসন',
    'People sheltered now': 'বর্তমানে আশ্রয়ে থাকা মানুষ',
    'Relief units remaining': 'অবশিষ্ট ত্রাণসামগ্রী',
    'Estimated damage loss': 'আনুমানিক ক্ষয়ক্ষতি',
    'Explore records': 'তথ্য দেখুন',
    'Khulna conditions, forecast & map': 'খুলনার আবহাওয়া, পূর্বাভাস ও মানচিত্র',
    'Check the forecast': 'পূর্বাভাস দেখুন',
    'Live status': 'সর্বশেষ পরিস্থিতি',
    'open the Live Report': 'লাইভ প্রতিবেদন খুলুন',
    'for the map and details': 'মানচিত্র ও বিস্তারিত তথ্য',
    'Manage inventory batches': 'মজুতের ব্যাচ পরিচালনা',
    'Warning signals': 'সতর্ক সংকেত',
    'No active disaster.': 'কোনো চলমান দুর্যোগ নেই।',
    'Shelter space': 'আশ্রয়কেন্দ্রের আসন',
    'No space available': 'কোনো আসন খালি নেই',
    'Space available everywhere': 'সবখানেই আসন খালি আছে',
    'Relief distribution': 'ত্রাণ বিতরণ',
    'Shortage': 'ঘাটতি',
    'Relief on track': 'ত্রাণ বিতরণ স্বাভাবিক',
    'Data health': 'তথ্যের অবস্থা',
    'Needs attention': 'মনোযোগ প্রয়োজন',
    'All clear': 'সব ঠিক আছে',
    'Open · not yet acknowledged': 'খোলা · এখনো নিশ্চিত করা হয়নি',
    'Acknowledge': 'নিশ্চিত করুন',
    'Assign to': 'দায়িত্ব দিন',
    'Shelter occupancy': 'আশ্রয়কেন্দ্রে অবস্থান',
    '(people currently checked in vs capacity)': '(বর্তমানে অবস্থানরত মানুষ বনাম ধারণক্ষমতা)',
    'No records found.': 'কোনো তথ্য পাওয়া যায়নি।',
    'No records.': 'কোনো রেকর্ড নেই।',
    'View all': 'সব দেখুন',
    'Disasters': 'দুর্যোগ',
    'People': 'মানুষ',
    'Shelters': 'আশ্রয়কেন্দ্র',
    'Relief': 'ত্রাণ',
    'Organisations': 'সংস্থা',
    'Warnings': 'সতর্কতা',
    'Status': 'অবস্থা',
    'Risk': 'ঝুঁকি',
    'Area': 'এলাকা',
    'Name': 'নাম',
    'Type': 'ধরন',
    'Date': 'তারিখ',
    'Actions': 'করণীয়',
    'Open': 'খুলুন',
    'Edit': 'সম্পাদনা',
    'Delete': 'মুছুন',
    'Add': 'যোগ করুন',
    'Save': 'সংরক্ষণ',
    'Cancel': 'বাতিল',
    'Back': 'ফিরে যান',
    'Next': 'পরবর্তী',
    'Previous': 'আগের',
    'Search': 'খুঁজুন',
    'Filter': 'ফিল্টার',
    'All': 'সব',
    'None': 'কোনোটিই নয়',
    'Yes': 'হ্যাঁ',
    'No': 'না',
    'Loading...': 'লোড হচ্ছে...',
    'Connecting...': 'সংযোগ হচ্ছে...',
    'Live': 'লাইভ',
    'Connection lost - retrying...': 'সংযোগ বিচ্ছিন্ন - আবার চেষ্টা হচ্ছে...',
    'Last checked:': 'সর্বশেষ যাচাই:',
    'Last checked: ': 'সর্বশেষ যাচাই: ',
    'Print': 'প্রিন্ট',
    'Refresh now': 'এখনই হালনাগাদ করুন',
    'Normal': 'স্বাভাবিক',
    'Danger': 'বিপদ',
    'Severe Danger': 'অতি বিপজ্জনক',
    'No warning with a signal number has been recorded.': 'সংখ্যাযুক্ত কোনো সতর্ক সংকেত নথিভুক্ত নেই।',
    'Very High risk': 'অতি উচ্চ ঝুঁকি',
    'High': 'উচ্চ',
    'Medium': 'মাঝারি',
    'Low': 'কম',
    'Choose an area': 'এলাকা নির্বাচন করুন',
    '- choose an area -': '- এলাকা নির্বাচন করুন -',
    'Pick an area to see its warning signal.': 'সতর্ক সংকেত দেখতে একটি এলাকা নির্বাচন করুন।',
    'No active disaster is affecting this area.': 'এই এলাকায় কোনো চলমান দুর্যোগের প্রভাব নেই।',
    'Live disaster path': 'দুর্যোগের চলমান গতিপথ',
    '(observed track, forecast and areas in the way)': '(পর্যবেক্ষিত গতিপথ, পূর্বাভাস ও প্রভাবিত এলাকা)',
    'Observed disaster path': 'পর্যবেক্ষিত দুর্যোগের গতিপথ',
    'Forecast path': 'পূর্বাভাসের গতিপথ',
    'Already passed': 'ইতিমধ্যে অতিক্রান্ত',
    'Being crossed now': 'বর্তমানে অতিক্রম করছে',
    'Currently crossing': 'বর্তমানে অতিক্রম করছে',
    'In forecast path': 'পূর্বাভাসিত গতিপথে',
    'Solid line = observed path, dashed line = forecast, big dot = current position with its impact circle': 'পূর্ণ রেখা = পর্যবেক্ষিত গতিপথ, ড্যাশ রেখা = পূর্বাভাস, বড় বিন্দু = প্রভাবের বৃত্তসহ বর্তমান অবস্থান',
    'All areas': 'সব এলাকা',
    '- not used -': '- ব্যবহার করা হয়নি -',
    'Nearest to:': 'নিকটতম:',
    'Area:': 'এলাকা:',
    'Only shelters with space': 'শুধু খালি আসনযুক্ত আশ্রয়কেন্দ্র',
    'Only shortages': 'শুধু ঘাটতি',
    'Use my location': 'আমার অবস্থান ব্যবহার করুন',
    'Shelters shown': 'টি আশ্রয়কেন্দ্র দেখানো হচ্ছে',
    'No shelter matches.': 'কোনো আশ্রয়কেন্দ্র মেলেনি।',
    'staying now of': 'জন এখন অবস্থান করছেন, মোট',
    'places': 'টি আসনের মধ্যে',
    'km away': 'কিমি দূরে',
    'your location': 'আপনার অবস্থান',
    'of': 'এর',
    'shelters shown': 'টি আশ্রয়কেন্দ্র দেখানো হচ্ছে',
    'first (with space before full)': 'আগে (পূর্ণ আশ্রয়কেন্দ্রের আগে খালি আসন)',
    'No area is short of relief.': 'কোনো এলাকায় ত্রাণের ঘাটতি নেই।',
    'No disaster is marked Active right now.': 'এই মুহূর্তে কোনো দুর্যোগ চলমান হিসেবে চিহ্নিত নেই।',
    'Shortage = under 80% of affected people reached, or flagged by a coordinator.': 'ঘাটতি = ৮০%-এর কম ক্ষতিগ্রস্ত মানুষের কাছে ত্রাণ পৌঁছেছে, অথবা সমন্বয়কারী ঘাটতি চিহ্নিত করেছেন।',
    'Areas by risk level': 'ঝুঁকির মাত্রা অনুযায়ী এলাকা',
    'Disaster-wise summary': 'দুর্যোগভিত্তিক সারসংক্ষেপ',
    'Relief items': 'ত্রাণসামগ্রী',
    'Coordinates are stored in the': 'স্থানাঙ্ক সংরক্ষিত আছে',
    'tables (Latitude / Longitude) - edit them there and refresh this page.': 'টেবিলে (অক্ষাংশ / দ্রাঘিমাংশ) - সেখানে সম্পাদনা করে এই পৃষ্ঠা আবার লোড করুন।',
    'Latitude': 'অক্ষাংশ',
    'Longitude': 'দ্রাঘিমাংশ',
    'In now': 'বর্তমানে আছে',
    'Capacity': 'ধারণক্ষমতা',
    'Live Weather for Khulna': 'খুলনার লাইভ আবহাওয়া',
    'Explore current conditions, forecasts and interactive weather maps for Khulna. Weather controls are provided by Ventusky; use its location search to change location.': 'খুলনার বর্তমান আবহাওয়া, পূর্বাভাস ও ইন্টারঅ্যাকটিভ আবহাওয়ার মানচিত্র দেখুন। আবহাওয়ার নিয়ন্ত্রণ Ventusky সরবরাহ করে; স্থান পরিবর্তন করতে তাদের অনুসন্ধান ব্যবহার করুন।',
    'Open Live Weather in a new tab': 'নতুন ট্যাবে লাইভ আবহাওয়া খুলুন',
    'Ventusky live weather map and forecast for Khulna': 'খুলনার Ventusky লাইভ আবহাওয়ার মানচিত্র ও পূর্বাভাস',
    'Check': 'যাচাই',
    'Result': 'ফলাফল',
    'Records': 'রেকর্ড',
    'Error': 'ত্রুটি',
    'Missing': 'অনুপস্থিত',
    'Note': 'নোট',
    'OK': 'ঠিক আছে',
    'Open ': 'খুলুন ',
    ' found a problem.': 'টি সমস্যার তথ্য পাওয়া গেছে।',
    'Sign in': 'সাইন ইন',
    'Sign in to continue': 'এগিয়ে যেতে সাইন ইন করুন',
    'First run: create the administrator account': 'প্রথমবার ব্যবহার: প্রশাসক অ্যাকাউন্ট তৈরি করুন',
    'Database problem:': 'ডেটাবেস সমস্যা:',
    'User name': 'ব্যবহারকারীর নাম',
    'Password': 'পাসওয়ার্ড',
    'Password (min 8 characters)': 'পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)',
    'Repeat password': 'পাসওয়ার্ড আবার লিখুন',
    'Create administrator': 'প্রশাসক অ্যাকাউন্ট তৈরি করুন',
    'The page expired - please try again.': 'সেশনের মেয়াদ শেষ হয়েছে - আবার চেষ্টা করুন।',
    'User name: 3-50 letters, digits, dot, dash or underscore.': 'ব্যবহারকারীর নাম: ৩-৫০টি অক্ষর, সংখ্যা, ডট, ড্যাশ বা আন্ডারস্কোর।',
    'Password must be at least 8 characters.': 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
    'The two passwords do not match.': 'দুটি পাসওয়ার্ড মিলছে না।',
    'An account already exists - please sign in.': 'একটি অ্যাকাউন্ট আগে থেকেই আছে - সাইন ইন করুন।',
    'Wrong user name or password.': 'ভুল ব্যবহারকারীর নাম বা পাসওয়ার্ড।',
    'Account': 'অ্যাকাউন্ট',
    'Signed in as': 'সাইন ইন করেছেন',
    'Current password': 'বর্তমান পাসওয়ার্ড',
    'New password (min 8 characters)': 'নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)',
    'Repeat new password': 'নতুন পাসওয়ার্ড আবার লিখুন',
    'Change password': 'পাসওয়ার্ড পরিবর্তন করুন',
    'Your current password is wrong.': 'আপনার বর্তমান পাসওয়ার্ডটি ভুল।',
    'The new password must be at least 8 characters.': 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
    'The two new passwords do not match.': 'নতুন পাসওয়ার্ড দুটি মিলছে না।',
    'Password changed.': 'পাসওয়ার্ড পরিবর্তন করা হয়েছে।',
    'Name, phone number, place, item...': 'নাম, ফোন নম্বর, স্থান, সামগ্রী...',
    'Type a name, phone number, area, shelter, organisation or item. Every table is searched.': 'নাম, ফোন নম্বর, এলাকা, আশ্রয়কেন্দ্র, সংস্থা বা সামগ্রীর নাম লিখুন। সব টেবিলে অনুসন্ধান হবে।',
    'Nothing found for': 'কোনো ফলাফল পাওয়া যায়নি:',
    'Found matches in': 'মিল পাওয়া গেছে',
    'table': 'টি টেবিলে',
    'tables': 'টি টেবিলে',
    'match': 'টি মিল',
    'matches': 'টি মিল',
    'Open in': 'এখানে খুলুন:',
    'Export CSV': 'CSV এক্সপোর্ট',
    'Add record': 'রেকর্ড যোগ করুন',
    'Edit record': 'রেকর্ড সম্পাদনা',
    'Delete record': 'রেকর্ড মুছুন',
    'Update record': 'রেকর্ড হালনাগাদ করুন',
    'Save record': 'রেকর্ড সংরক্ষণ করুন',
    'Add user': 'ব্যবহারকারী যোগ করুন',
    'Reset password': 'পাসওয়ার্ড রিসেট',
    'Role': 'ভূমিকা',
    'Username': 'ব্যবহারকারীর নাম',
    'you': 'আপনি',
    'This cannot be undone.': 'এটি আর ফিরিয়ে নেওয়া যাবে না।',
    'Record added.': 'রেকর্ড যোগ করা হয়েছে।',
    'Record updated.': 'রেকর্ড হালনাগাদ করা হয়েছে।',
    'Record deleted.': 'রেকর্ড মুছে ফেলা হয়েছে।',
    'That record was not found.': 'রেকর্ডটি পাওয়া যায়নি।',
    'That user name already exists.': 'এই ব্যবহারকারীর নাম আগে থেকেই আছে।',
    'Database error.': 'ডেটাবেসে সমস্যা হয়েছে।',
    'You': 'আপনি',
    'View': 'দেখুন',
    'Confirm': 'নিশ্চিত করুন',
    'Confirm current data': 'তথ্য হালনাগাদ নিশ্চিত করুন',
    'No family-level relief records exist for this disaster (add them in Relief Recipient).': 'এই দুর্যোগের জন্য পরিবারভিত্তিক ত্রাণের তথ্য নেই (ত্রাণগ্রহীতা টেবিলে যোগ করুন)।',
    'Still in shelters': 'এখনো আশ্রয়কেন্দ্রে',
    'Task': 'কাজ',
    'Owner': 'দায়িত্বপ্রাপ্ত',
    'Due': 'শেষ সময়',
    'Last updated': 'সর্বশেষ হালনাগাদ',
    'Summary': 'সারসংক্ষেপ',
    'Observed at': 'পর্যবেক্ষণের সময়',
    'Field updates': 'মাঠপর্যায়ের হালনাগাদ',
    'No field updates recorded.': 'মাঠপর্যায়ের কোনো হালনাগাদ নেই।',
    'Category': 'বিভাগ',
    'Situation': 'পরিস্থিতি',
    'Medical': 'চিকিৎসা',
    'Supplies': 'সরবরাহ',
    'Safety': 'নিরাপত্তা',
    'Details': 'বিস্তারিত',
    'Submit update': 'হালনাগাদ জমা দিন',
    'Field update recorded.': 'মাঠপর্যায়ের হালনাগাদ সংরক্ষণ করা হয়েছে।',
    'Only an editor or administrator can submit a field update.': 'শুধু সম্পাদক বা প্রশাসক মাঠপর্যায়ের হালনাগাদ জমা দিতে পারবেন।',
    'Batch code': 'ব্যাচ কোড',
    'Record receipt': 'পণ্য গ্রহণ নথিভুক্ত করুন',
    'No stock movements recorded.': 'মজুতের কোনো লেনদেন নথিভুক্ত নেই।',
    'Stock receipt recorded; batch inventory and item totals were updated.': 'পণ্য গ্রহণ নথিভুক্ত হয়েছে; ব্যাচের মজুত ও মোট পরিমাণ হালনাগাদ হয়েছে।',
    'Only an editor or administrator can receive stock.': 'শুধু সম্পাদক বা প্রশাসক মজুত গ্রহণ নথিভুক্ত করতে পারবেন।',
    'Estimated loss': 'আনুমানিক ক্ষয়ক্ষতি',
    'Check-in': 'প্রবেশ',
    'Check-out': 'প্রস্থান',
    'Shelter': 'আশ্রয়কেন্দ্র',
    'Contact': 'যোগাযোগ',
    'Cannot be blank': 'ফাঁকা রাখা যাবে না',
    'Select': 'নির্বাচন করুন',
    'Choose': 'বাছাই করুন',
    'Show': 'দেখান',
    'per page': 'প্রতি পৃষ্ঠায়',
    'Page': 'পৃষ্ঠা',
    'First': 'প্রথম',
    'Last': 'শেষ',
    'Go': 'যান',
    'No data to display.': 'দেখানোর মতো কোনো তথ্য নেই।',
    'No results.': 'কোনো ফলাফল নেই।',
    'Last confirmed': 'সর্বশেষ নিশ্চিতকরণ',
    'Confirmed': 'নিশ্চিত',
    'Needs review': 'পর্যালোচনা প্রয়োজন',
    'Changed': 'পরিবর্তিত',
    'Confirm capacity': 'ধারণক্ষমতা নিশ্চিত করুন',
    'Confirm current': 'বর্তমান তথ্য নিশ্চিত করুন',
    'Type to search - suggestions appear as you type': 'খুঁজতে লিখুন - লেখার সঙ্গে পরামর্শ দেখাবে',
    'Search in': 'এখানে খুঁজুন',
    'All columns': 'সব কলাম',
    'Clear': 'মুছুন',
    'record': 'টি রেকর্ড',
    'records': 'টি রেকর্ড',
    'matching': 'মিলছে',
    'sorted': 'সাজানো হয়েছে',
    'descending': 'অধঃক্রমে',
    'ascending': 'ঊর্ধ্বক্রমে',
    'by': 'অনুযায়ী',
    '(click a column title to sort)': '(সাজাতে কলামের শিরোনামে ক্লিক করুন)',
    'Yes, delete': 'হ্যাঁ, মুছে দিন',
    'You are about to delete this record from': 'আপনি এই টেবিল থেকে রেকর্ডটি মুছতে যাচ্ছেন:',
    'Other records depend on it:': 'অন্যান্য রেকর্ড এর ওপর নির্ভরশীল:',
    'will be deleted too': 'এটিও মুছে যাবে',
    'will lose the link (field set empty)': 'সংযোগ হারাবে (ঘর ফাঁকা হবে)',
    'blocks the deletion - remove or move them first': 'মুছতে বাধা দিচ্ছে - আগে সরান বা অন্যত্র স্থানান্তর করুন',
    'Next »': 'পরবর্তী »',
    'Prev': 'আগের',
    'Auto': 'স্বয়ংক্রিয়',
    '- select -': '- নির্বাচন করুন -',
    'Report': 'প্রতিবেদন',
    '1-3 Normal': '১-৩ স্বাভাবিক',
    '4-7 Danger': '৪-৭ বিপদ',
    '8-10 Severe Danger': '৮-১০ অতি বিপজ্জনক',
    'Warning signal': 'সতর্ক সংকেত',
    'Signal': 'সংকেত',
    'Signal ': 'সংকেত ',
    'No data': 'কোনো তথ্য নেই',
    'Disaster': 'দুর্যোগ',
    'Affected person': 'ক্ষতিগ্রস্ত ব্যক্তি',
    'Affected family': 'ক্ষতিগ্রস্ত পরিবার',
    'Shelter assignment': 'আশ্রয়কেন্দ্র বরাদ্দ',
    'Rescue org': 'উদ্ধারকারী সংস্থা',
    'Early warning': 'আগাম সতর্কতা',
    'Relief item': 'ত্রাণসামগ্রী',
    'Relief recipient': 'ত্রাণগ্রহীতা',
    'Disaster area': 'দুর্যোগপ্রবণ এলাকা',
    'Disaster track': 'দুর্যোগের গতিপথ',
    'Incident update': 'ঘটনার হালনাগাদ',
    'Audit log': 'নিরীক্ষা লগ',
    'Response task': 'সাড়াদান কার্যক্রম',
    'Relief batch': 'ত্রাণের ব্যাচ',
    'Damage': 'ক্ষয়ক্ষতি',
    'Area type': 'এলাকার ধরন',
    'Shelter capacity': 'আশ্রয়কেন্দ্রের ধারণক্ষমতা',
    'Current stock': 'বর্তমান মজুত',
    'Quantity': 'পরিমাণ',
    'Created at': 'তৈরির সময়',
    'Updated at': 'হালনাগাদের সময়',
    'User ID': 'ব্যবহারকারী আইডি',
    'Disaster ID': 'দুর্যোগ আইডি',
    'Area ID': 'এলাকা আইডি',
    'Person ID': 'ব্যক্তি আইডি',
    'Shelter ID': 'আশ্রয়কেন্দ্র আইডি',
    'Relief ID': 'ত্রাণ আইডি',
    'Disaster Id': 'দুর্যোগ আইডি',
    'Area Id': 'এলাকা আইডি',
    'Person Id': 'ব্যক্তি আইডি',
    'Shelter Id': 'আশ্রয়কেন্দ্র আইডি',
    'Relief Id': 'ত্রাণ আইডি',
    'Active': 'চলমান',
    'Ended': 'শেষ হয়েছে',
    'Reported': 'নথিভুক্ত',
    'Under control': 'নিয়ন্ত্রণে',
    'Closed': 'বন্ধ',
    'Pending': 'অপেক্ষমাণ',
    'In progress': 'চলমান',
    'Completed': 'সম্পন্ন',
    'Overdue': 'সময় পেরিয়েছে',
    'Passed': 'অতিক্রান্ত',
    'Forecast': 'পূর্বাভাস',
    'Actual': 'পর্যবেক্ষিত',
    'Viewer': 'দর্শক',
    'Editor': 'সম্পাদক',
    'Admin': 'প্রশাসক',
    'admin': 'প্রশাসক',
    'editor': 'সম্পাদক',
    'viewer': 'দর্শক',
    'No problems found.': 'কোনো সমস্যা পাওয়া যায়নি।',
    'See what needs fixing': 'কী ঠিক করতে হবে দেখুন',
    'View the checks': 'যাচাইগুলো দেখুন',
    'Editors and administrators can acknowledge and assign alerts. Alerts disappear automatically when the underlying issue is fixed.': 'সম্পাদক ও প্রশাসকেরা সতর্কতা নিশ্চিত করে দায়িত্ব দিতে পারেন। মূল সমস্যার সমাধান হলে সতর্কতা স্বয়ংক্রিয়ভাবে চলে যাবে।',
    'Acknowledged by': 'নিশ্চিত করেছেন',
    'at': 'সময়',
    'Assigned to': 'দায়িত্বে',
    'Update assignment': 'দায়িত্ব হালনাগাদ করুন',
    'Acknowledge & assign': 'নিশ্চিত করে দায়িত্ব দিন',
    'Disasters at a glance': 'দুর্যোগের সংক্ষিপ্ত চিত্র',
    'Loss (Tk)': 'ক্ষয়ক্ষতি (টাকা)',
    'No disasters yet.': 'এখনো কোনো দুর্যোগ নেই।',
    'Database tables': 'ডেটাবেস টেবিল',
    'Click a table to view, search, add, edit or delete its records.': 'রেকর্ড দেখতে, খুঁজতে, যোগ, সম্পাদনা বা মুছতে একটি টেবিলে ক্লিক করুন।',
    'Open the Live Report': 'লাইভ প্রতিবেদন খুলুন',
    'No problems found': 'কোনো সমস্যা পাওয়া যায়নি',
    'Receive a stock batch': 'মজুতের ব্যাচ গ্রহণ',
    'Use a unique batch code for each receipt. Existing stock was retained as a legacy batch; its original receipt date is unknown.': 'প্রতিটি গ্রহণের জন্য আলাদা ব্যাচ কোড ব্যবহার করুন। আগের মজুত পুরোনো ব্যাচ হিসেবে রাখা হয়েছে; আসল গ্রহণের তারিখ জানা নেই।',
    '- select item -': '- সামগ্রী নির্বাচন করুন -',
    'Quantity received': 'গৃহীত পরিমাণ',
    'Received date': 'গ্রহণের তারিখ',
    'Expiry date': 'মেয়াদ শেষের তারিখ',
    'Supplier / source': 'সরবরাহকারী / উৎস',
    'Storage location': 'সংরক্ষণের স্থান',
    'Notes': 'নোট',
    'Stock batches': 'মজুতের ব্যাচ',
    'Batch': 'ব্যাচ',
    'Item': 'সামগ্রী',
    'Received': 'গৃহীত',
    'Expiry': 'মেয়াদ',
    'Received qty': 'গৃহীত পরিমাণ',
    'Dispatched': 'বিতরণকৃত',
    'Available': 'উপলব্ধ',
    'Source / storage': 'উৎস / সংরক্ষণ',
    'Recorded by': 'নথিভুক্ত করেছেন',
    'No stock batches recorded.': 'মজুতের কোনো ব্যাচ নথিভুক্ত নেই।',
    'Recent stock movements': 'সাম্প্রতিক মজুত লেনদেন',
    'Movement': 'লেনদেন',
    'Supplier / destination': 'সরবরাহকারী / গন্তব্য',
    'Unknown (legacy)': 'অজানা (পুরোনো)',
    'Not recorded': 'নথিভুক্ত নয়',
    'Areas hit': 'ক্ষতিগ্রস্ত এলাকা',
    'People affected': 'ক্ষতিগ্রস্ত মানুষ',
    'Relief units given': 'বিতরণ করা ত্রাণসামগ্রী',
    'Status changed:': 'অবস্থা পরিবর্তন:',
    'not recorded': 'নথিভুক্ত নয়',
    'Closed:': 'বন্ধ হয়েছে:',
    'legacy close time not recorded': 'পুরোনো বন্ধের সময় নথিভুক্ত নয়',
    'Areas affected': 'ক্ষতিগ্রস্ত এলাকা',
    'Early warnings': 'আগাম সতর্কতা',
    'No warning was recorded.': 'কোনো সতর্কতা নথিভুক্ত নেই।',
    'Disaster path': 'দুর্যোগের গতিপথ',
    'Time': 'সময়',
    'Impact radius (km)': 'প্রভাবের ব্যাসার্ধ (কিমি)',
    'Wind (km/h)': 'বাতাসের গতি (কিমি/ঘণ্টা)',
    'Positions as recorded; the Live Report draws them on a map for Active and Under control incidents.': 'নথিভুক্ত অবস্থান; লাইভ প্রতিবেদন চলমান ও নিয়ন্ত্রণাধীন ঘটনার জন্য মানচিত্রে দেখায়।',
    'Relief coverage by home area': 'বসবাসের এলাকা অনুযায়ী ত্রাণ পৌঁছানোর হার',
    'Home area': 'বসবাসের এলাকা',
    'Received relief': 'ত্রাণ পেয়েছেন',
    'Not received': 'ত্রাণ পাননি',
    'Coverage': 'আচ্ছাদন',
    'flagged': 'চিহ্নিত',
    'Response tasks': 'সাড়াদান কার্যক্রম',
    'Unassigned': 'দায়িত্ব দেওয়া হয়নি',
    'No response tasks have been recorded for this incident.': 'এই ঘটনার জন্য কোনো সাড়াদান কার্যক্রম নথিভুক্ত নেই।',
    'Manage response tasks': 'সাড়াদান কার্যক্রম পরিচালনা',
    'Record an update from the field': 'মাঠপর্যায়ের হালনাগাদ নথিভুক্ত করুন',
    'Online submission only. Do not enter sensitive personal details; updates are visible to signed-in users.': 'শুধু অনলাইনে জমা দেওয়া যাবে। সংবেদনশীল ব্যক্তিগত তথ্য দেবেন না; সাইন-ইন করা ব্যবহারকারীরা হালনাগাদ দেখতে পারবেন।',
    'Affected area': 'ক্ষতিগ্রস্ত এলাকা',
    'Not specified': 'উল্লেখ করা হয়নি',
    'Submit field update': 'মাঠপর্যায়ের হালনাগাদ জমা দিন',
    'Area not specified': 'এলাকা উল্লেখ করা হয়নি',
    'Reported by': 'নথিভুক্ত করেছেন',
    'Circles = risk areas': 'বৃত্ত = ঝুঁকিপূর্ণ এলাকা',
    'Circles = risk areas   Dots = shelters (green < 70% full, orange < 90%, red 90%+)': 'বৃত্ত = ঝুঁকিপূর্ণ এলাকা; বিন্দু = আশ্রয়কেন্দ্র (সবুজ < ৭০% পূর্ণ, কমলা < ৯০%, লাল ৯০%+) ',
    'Roles': 'ভূমিকা',
    'everything, including this page.': 'এই পৃষ্ঠাসহ সবকিছু।',
    'add / edit / delete records.': 'রেকর্ড যোগ / সম্পাদনা / মুছুন।',
    'read-only.': 'শুধু দেখার অনুমতি।',
    'ID': 'আইডি',
    'Created': 'তৈরি হয়েছে',
    'locked (too many wrong passwords)': 'লক করা হয়েছে (অনেকবার ভুল পাসওয়ার্ড)',
    'active': 'সক্রিয়',
    'new password': 'নতুন পাসওয়ার্ড',
    'Add a user': 'ব্যবহারকারী যোগ করুন',
    'Affected Person': 'ক্ষতিগ্রস্ত ব্যক্তি',
    'Disaster Area': 'দুর্যোগপ্রবণ এলাকা',
    'Disaster Track': 'দুর্যোগের গতিপথ',
    'Disaster Shelter': 'দুর্যোগ আশ্রয়কেন্দ্র',
    'Disaster Affected Person': 'দুর্যোগে ক্ষতিগ্রস্ত ব্যক্তি',
    'Disaster Rescue Org': 'দুর্যোগ উদ্ধারকারী সংস্থা',
    'Rescue Org Relief Item': 'উদ্ধারকারী সংস্থার ত্রাণসামগ্রী',
    'Affected Person Emergency Item': 'ক্ষতিগ্রস্ত ব্যক্তির জরুরি সামগ্রী',
    'Shelter Emergency Item': 'আশ্রয়কেন্দ্রের জরুরি সামগ্রী',
    'Shelter Assignment': 'আশ্রয়কেন্দ্র বরাদ্দ',
    'Rescue Org': 'উদ্ধারকারী সংস্থা',
    'Relief Item': 'ত্রাণসামগ্রী',
    'Early Warning': 'আগাম সতর্কতা',
    'Relief Distribution': 'ত্রাণ বিতরণ',
    'Relief Recipient': 'ত্রাণগ্রহীতা',
    'Incident Task': 'ঘটনাভিত্তিক কাজ',
    'Incident Update': 'ঘটনার হালনাগাদ',
    'Relief Batch': 'ত্রাণের ব্যাচ',
    'Audit Log': 'নিরীক্ষা লগ',
    'Emergency Item': 'জরুরি সামগ্রী',
    'Area Name': 'এলাকার নাম',
    'Family Size': 'পরিবারের সদস্যসংখ্যা',
    'Phone Number': 'ফোন নম্বর',
    'Risk Level': 'ঝুঁকির মাত্রা',
    'Disaster Type': 'দুর্যোগের ধরন',
    'Start Date': 'শুরুর তারিখ',
    'End Date': 'শেষের তারিখ',
    'Alert Type': 'সতর্কতার ধরন',
    'Alert DateTime': 'সতর্কতার সময়',
    'Signal Level': 'সতর্ক সংকেতের মাত্রা',
    'Warning ID': 'সতর্কতা আইডি',
    'Task Title': 'কাজের শিরোনাম',
    'Assigned To': 'দায়িত্বপ্রাপ্ত',
    'Due At': 'শেষ সময়',
    'Created By': 'তৈরি করেছেন',
    'Update Type': 'হালনাগাদের ধরন',
    'Observed At': 'পর্যবেক্ষণের সময়',
    'Reported By': 'নথিভুক্ত করেছেন',
    'Batch Code': 'ব্যাচ কোড',
    'Quantity Received': 'গৃহীত পরিমাণ',
    'Received At': 'গ্রহণের সময়',
    'Expiry Date': 'মেয়াদ শেষের তারিখ',
    'Source': 'উৎস',
    'Storage Location': 'সংরক্ষণের স্থান',
    'Movement Type': 'লেনদেনের ধরন',
    'Movement Date': 'লেনদেনের তারিখ',
    'Estimated Loss': 'আনুমানিক ক্ষয়ক্ষতি',
    'Radius Km': 'ব্যাসার্ধ (কিমি)',
    'Wind Speed Kmh': 'বাতাসের গতি (কিমি/ঘণ্টা)',
    'Point Time': 'অবস্থানের সময়',
    'Point Type': 'অবস্থানের ধরন',
    'Check In': 'প্রবেশ',
    'Check Out': 'প্রস্থান',
    'Capacity Verified At': 'ধারণক্ষমতা যাচাইয়ের সময়',
    'Capacity Verified By': 'ধারণক্ষমতা যাচাই করেছেন',
    'No problems found. All ': 'কোনো সমস্যা পাওয়া যায়নি। সব ',
    ' checks passed': 'টি যাচাই সফল হয়েছে',
    ' (some notes below are only for your attention)': ' (নিচের কিছু নোট শুধু আপনার মনোযোগের জন্য)',
    'check found a problem.': 'টি যাচাইয়ে সমস্যা পাওয়া গেছে।',
    'checks found a problem.': 'টি যাচাইয়ে সমস্যা পাওয়া গেছে।',
    'Open the table, fix the records, then reload this page.': 'টেবিল খুলে রেকর্ড ঠিক করুন, তারপর পৃষ্ঠা আবার লোড করুন।',
    'These checks read the whole database.': 'এই যাচাইগুলো পুরো ডেটাবেস পরীক্ষা করে।',
    'records contradict each other,': 'রেকর্ডগুলো পরস্পরবিরোধী,',
    'information the live pages need is absent,': 'লাইভ পৃষ্ঠাগুলোর প্রয়োজনীয় তথ্য নেই,',
    'worth a look. Nothing here is changed automatically.': 'পর্যালোচনা করা উচিত। এখানে কিছুই স্বয়ংক্রিয়ভাবে পরিবর্তন হয় না।',
    '... and ': '... এবং ',
    'MySQL refused the user name / password in config.php.': 'config.php-এ দেওয়া MySQL ব্যবহারকারীর নাম বা পাসওয়ার্ড গ্রহণ করা হয়নি।',
    'Cannot reach MySQL - is WampServer running (green icon)?': 'MySQL-এ সংযোগ করা যাচ্ছে না - WampServer কি চালু আছে (সবুজ আইকন)?',
    'Cannot open the database. Details are in the PHP error log.': 'ডেটাবেস খোলা যাচ্ছে না। বিস্তারিত PHP error log-এ আছে।',
    'Only an administrator can open that page.': 'শুধু প্রশাসক এই পৃষ্ঠা খুলতে পারবেন।',
    'Only an editor or administrator can acknowledge or assign alerts.': 'শুধু সম্পাদক বা প্রশাসক সতর্কতা নিশ্চিত বা বরাদ্দ করতে পারবেন।',
    'That alert is no longer active; reload the dashboard.': 'সতর্কতাটি আর সক্রিয় নেই; ড্যাশবোর্ড আবার লোড করুন।',
    'Choose an active administrator or editor as the alert owner.': 'সতর্কতার দায়িত্বে একজন সক্রিয় প্রশাসক বা সম্পাদক বেছে নিন।',
    'Alert acknowledged and assigned.': 'সতর্কতা নিশ্চিত করে দায়িত্ব দেওয়া হয়েছে।',
    'That disaster was not found.': 'দুর্যোগটি পাওয়া যায়নি।',
    'My location': 'আমার অবস্থান',
    'This browser cannot tell where you are. Choose an area instead.': 'এই ব্রাউজার আপনার অবস্থান জানাতে পারে না। পরিবর্তে একটি এলাকা বেছে নিন।',
    'Your location could not be read (permission denied?). Choose an area instead.': 'আপনার অবস্থান পড়া যায়নি (অনুমতি দেওয়া হয়নি?)। পরিবর্তে একটি এলাকা বেছে নিন।',
    'Failed to load data.': 'তথ্য লোড করা যায়নি।',
    'The map library could not be loaded (internet needed). The tables below list every location.': 'মানচিত্র লাইব্রেরি লোড করা যায়নি (ইন্টারনেট প্রয়োজন)। নিচের টেবিলে সব অবস্থান দেখানো হয়েছে।',
    'The map library could not be loaded (internet needed). The lists below show the areas in the path.': 'মানচিত্র লাইব্রেরি লোড করা যায়নি (ইন্টারনেট প্রয়োজন)। নিচের তালিকায় গতিপথের এলাকাগুলো দেখানো হয়েছে।'
  };

  var language = 'en';
  var originalText = new WeakMap();
  var originalAttributes = new WeakMap();
  var initialTitle = document.title;
  try {
    language = localStorage.getItem('drms-language') === 'bn' ? 'bn' : 'en';
  } catch (error) {
    console.warn('DRMS language preference could not be read from browser storage.', error);
  }

  function translate(value) {
    var leading = value.match(/^\s*/)[0];
    var trailing = value.match(/\s*$/)[0];
    var core = value.slice(leading.length, value.length - trailing.length);
    var result = language === 'bn' ? translations[core] || core : core;
    var prefixes = [
      ['Last checked: ', 'সর্বশেষ যাচাই: '],
      ['Areas: ', 'এলাকা: '],
      ['Risk: ', 'ঝুঁকি: '],
      ['Signal ', 'সংকেত ']
    ];
    if (language === 'bn' && result === core) {
      prefixes.some(function (pair) {
        var source = pair[0];
        var target = pair[1];
        if (!core.startsWith(source)) return false;
        result = target + core.slice(source.length);
        return true;
      });
    }
    if (language === 'bn' && result === core) {
      var shelterCount = core.match(/^(\d+) of (\d+) shelters shown(?: - nearest to (.+) first \(with space before full\))?$/);
      if (shelterCount) {
        result = shelterCount[1] + ' / ' + shelterCount[2] + ' টি আশ্রয়কেন্দ্র দেখানো হচ্ছে';
        if (shelterCount[3]) result += ' - ' + shelterCount[3] + ' এর নিকটবর্তী আগে (খালি আসনযুক্তগুলো আগে)';
      }

      var stayingCount = core.match(/^([\d,.]+) staying now of ([\d,.]+) places$/);
      if (stayingCount) result = 'বর্তমানে ' + stayingCount[1] + ' জন অবস্থান করছেন / মোট ' + stayingCount[2] + ' টি আসন';

      var problemsCount = core.match(/^(\d+) problems?$/);
      if (problemsCount) result = problemsCount[1] + ' টি সমস্যা';

      var checksError = core.match(/^(\d+) checks? found a problem\.$/);
      if (checksError) result = checksError[1] + ' টি যাচাইয়ে সমস্যা পাওয়া গেছে।';

      var passedChecks = core.match(/^All (\d+) checks passed(?: \(some notes below are only for your attention\))?\.?$/);
      if (passedChecks) result = 'সব ' + passedChecks[1] + ' টি যাচাই সফল হয়েছে' + (passedChecks[0].indexOf('some notes') >= 0 ? ' (নিচের কিছু নোট শুধু আপনার মনোযোগের জন্য)' : '') + '।';
    }
    if (language === 'bn' && result === core && core.indexOf(' - ') >= 0) {
      var parts = core.split(' - ');
      var translatedParts = parts.map(function (part) {
        return translations[part] || part;
      });
      if (translatedParts.some(function (part, index) { return part !== parts[index]; })) result = translatedParts.join(' - ');
    }
    return leading + result + trailing;
  }

  function applyLanguage() {
    document.documentElement.lang = language;
    var titleSuffix = ' - Disaster & Relief Management';
    var pageTitle = initialTitle.endsWith(titleSuffix) ? initialTitle.slice(0, -titleSuffix.length) : initialTitle;
    document.title = translate(pageTitle) + (initialTitle.endsWith(titleSuffix)
      ? (language === 'bn' ? ' - দুর্যোগ ও ত্রাণ ব্যবস্থাপনা' : titleSuffix) : '');
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
      var parent = node.parentElement;
      if (parent && /^(SCRIPT|STYLE|TEXTAREA|PRE|CODE)$/i.test(parent.tagName)) continue;
      if (parent && parent.closest('[data-language-toggle]')) continue;
      if (parent && parent.closest('[data-i18n-ignore], .cell, .field-update')) continue;
      var cell = parent && parent.closest('td');
      if (cell && !cell.classList.contains('muted') && !(parent && parent.closest('.btn, .sig'))) continue;
      if (!originalText.has(node)) originalText.set(node, node.nodeValue);
      var sourceText = originalText.get(node);
      var translated = language === 'bn' ? translate(sourceText) : sourceText;
      if (translated !== node.nodeValue) node.nodeValue = translated;
    }

    document.querySelectorAll('[placeholder], [aria-label], [title], [alt], [data-confirm]').forEach(function (element) {
      ['placeholder', 'aria-label', 'title', 'alt', 'data-confirm'].forEach(function (attribute) {
        if (!element.hasAttribute(attribute)) return;
        var stored = originalAttributes.get(element) || {};
        if (!Object.prototype.hasOwnProperty.call(stored, attribute)) {
          stored[attribute] = element.getAttribute(attribute);
          originalAttributes.set(element, stored);
        }
        var source = stored[attribute];
        var translated = language === 'bn' ? translate(source) : source;
        if (translated !== element.getAttribute(attribute)) element.setAttribute(attribute, translated);
      });
    });

    document.querySelectorAll('[data-language-toggle]').forEach(function (button) {
      var buttonText = language === 'bn' ? 'English' : 'বাংলা';
      if (button.textContent !== buttonText) button.textContent = buttonText;
      var label = language === 'bn' ? 'Switch to English' : 'Switch to Bangla';
      if (button.getAttribute('aria-label') !== translations[label]) button.setAttribute('aria-label', translations[label]);
      if (button.getAttribute('title') !== translations[label]) button.setAttribute('title', translations[label]);
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-language-toggle]');
    if (!button) return;
    language = language === 'bn' ? 'en' : 'bn';
    try {
      localStorage.setItem('drms-language', language);
    } catch (error) {
      console.warn('DRMS language preference could not be saved in browser storage.', error);
    }
    applyLanguage();
  });

  window.drmsTranslate = translate;
  applyLanguage();
  if (window.MutationObserver) {
    new MutationObserver(function (records) {
      records.forEach(function (record) {
        if (record.type !== 'characterData' || !originalText.has(record.target)) return;
        var parent = record.target.parentElement;
        if (parent && parent.closest('[data-language-toggle]')) return;
        var source = originalText.get(record.target);
        var expected = language === 'bn' ? translate(source) : source;
        if (record.target.nodeValue !== expected) originalText.set(record.target, record.target.nodeValue);
      });
      applyLanguage();
    }).observe(document.body, { childList: true, subtree: true, characterData: true });
  }
})();
