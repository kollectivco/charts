import re

new_artists_raw = """
Afroto - عفرتو
Abyusif - أبيوسف
Abdel Baset Hamouda - عبد الباسط حمودة
Abdelhalim Hafez - عبد الحليم حافظ
Ahmed Amer - أحمد عامر
Ahmed Bahaa - أحمد بهاء
Ahmed Moza - أحمد موزة
Ahmed Saad - أحمد سعد
Ahmed Sheba - أحمد شيبة
Ameen Khattab - أمين خطاب
Amal Maher - آمال ماهر
Amer Mounib - عامر منيب
Amr Diab - عمرو دياب
Amr Gaber - عمرو جابر
Amr Mostafa - عمرو مصطفى
Angham - أنغام
Assala Nasri - أصالة نصري
Bahaa Sultan - بهاء سلطان
Billie Eilish - بيلي إيليش
Burna Boy - برنا بوي
Cairokee - كايروكي
Coolpix - كولبيكس
Dj Fam - دي جي فام
Dominic Fike - دومينيك فيك
Drake - دريك
El Waili - الوايلي
Elissa - إليسا
Ehab Tawfik - إيهاب توفيق
Essam Sasa - عصام صاصا
Fadel Shaker - فضل شاكر
Fairuz - فيروز
Felo - فيلو
G. Oka - أوكا
George Wassouf - جورج وسوف
Hakim - حكيم
Hamada Helal - حمادة هلال
Hamo Bika - حمو بيكا
Hamo ElTikha - حمو التيخا
Hamza Namira - حمزة نمرة
Hany Shaker - هاني شاكر
Hassan El Asmar - حسن الأسمر
Hassan Shakosh - حسن شاكوش
Houda Bondok - هدى بندق
iMediaMusicRecords - آي ميديا ميوزيك ريكوردز
Ismail Nosrat - إسماعيل نصرت
Karim Cristiano - كريم كريستيانو
Karizma Production - كاريزما برودكشن
Lege-Cy - ليجي-سي
Maha Ftouni - مها فتوني
Mahmoud El Esseily - محمود العسيلي
Mahmoud El Lithy - محمود الليثي
Magdy El Zahar - مجدي الزهار
Marwan Moussa - مروان موسى
Marwan Pablo - مروان بابلو
Michael Jackson - مايكل جاكسون
Mohamed Fouad - محمد فؤاد
Mohamed Hamaki - محمد حماقي
Mohamed Mounir - محمد منير
Mohamed Ramadan - محمد رمضان
Mohamed Reda - محمد رضا
Mohamed Soltan - محمد سلطان
Molotof - مولوتوف
Mond - موند
Muhab - محب
Muslim - مسلم
Nabil - نبيل
Omar Id - عمر إد
Omar Kamal - عمر كمال
Omar Meme - عمر ميم
Osha El Soghayar - أوشا الصغير
Pousi - بوسي
Rahma Mohsen - رحمة محسن
Ramy Gamal - رامي جمال
Ramy Sabry - رامي صبري
Reda El Bahrawy - رضا البحراوي
Retal Ahmed - ريتال أحمد
RKmazika - أر كيه مزيكا
Saad Lamjarred - سعد لمجرد
Samara Now - سمارة ناو
Saint Levant - سانت ليفانت
Sherine - شيرين
Shakira - شاكيرا
Tamer Ashour - تامر عاشور
Tamer Hosny - تامر حسني
Tareq Alshiekh - طارق الشيخ
Teefo - تيفو
Tommy Gun - تومي غن
Tommyy - تومي
TUL8TE - تولايت
Versus Music - فيرسس ميوزيك
Wael Jassar - وائل جسار
Wegz - ويجز
ZIAD ZAZA - زياد ظاظا
3enba - عنبة
Zain Group - زين جروب
"""

file_path = '/Users/appleworld/Desktop/APP DEV/charts new/inc/Core/Translation.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Extract existing map
start_marker = "// Artist Names Arabic Translations"
end_marker = "];"
start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx)

existing_block = content[start_idx:end_idx]

# Parse existing map
existing_map = {}
for line in existing_block.split('\n'):
    if '=>' in line:
        parts = line.split('=>')
        k = parts[0].strip().strip("'")
        v = parts[1].strip().strip(",").strip().strip("'")
        existing_map[k] = v

# Parse new map
for line in new_artists_raw.split('\n'):
    if '-' in line:
        parts = line.split('-', 1)
        k = parts[0].strip()
        v = parts[1].strip()
        if k and v:
            existing_map[k] = v

# Rebuild block
new_block = start_marker + "\n"
for k, v in existing_map.items():
    new_block += f"        '{k}' => '{v}',\n"

# Replace in content
new_content = content[:start_idx] + new_block + "    " + content[end_idx:]

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Updated Translation.php")
