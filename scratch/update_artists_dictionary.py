import re

new_artists_raw = """
Sherine → شيرين
Angham → أنغام
Ramy Sabry → رامي صبري
Tamer Ashour → تامر عاشور
Mohamed Hamaki → محمد حماقي
Amr Diab → عمرو دياب
Wael Jassar → وائل جسار
Elissa → إليسا
Assala → أصالة
Fadel Chaker → فضل شاكر
George Wassouf → جورج وسوف
Hakim → حكيم
Hamada Helal → حمادة هلال
Ahmed Saad → أحمد سعد
Ahmed Sheba → أحمد شيبة
Mahmoud El Esseily → محمود العسيلي
Bahaa Sultan → بهاء سلطان
Reda El Bahrawy → رضا البحراوي
Pousi → بوسي
Houda Bondok → حودة بندق
Essam Sasa → عصام صاصا
Muslim - مُسلِم → مسلم
Hamo Bika → حمو بيكا
Hamo ElTikha → حمو التيخا
Rahma Mohsen → رحمة محسن
Mohamed Ramadan → محمد رمضان
Wegz → ويجز
Marwan Pablo → مروان بابلو
Marwan Moussa → مروان موسى
Cairokee → كايروكي
Tamer Hosny → تامر حسني
Amr Mostafa → عمرو مصطفى
Amr Gaber → عمرو جابر
Lege-Cy → ليجي-سي
TUL8TE → توليت
Afroto → عفروتو
Abyusif → أبيوسف
Saint Levant → سانت ليفانت
ZIAD ZAZA → زياد ظاظا
Coolpix → كولبيكس
Ismail Nosrat → إسماعيل نصرت
Ghaliaa → غالية
Tommyy → تومي
Rally → رالي
Omar Meme → عمر ميم
Omar Keif → عمر كيف
Figo El Dakhlawy → فيجو الدخلاوي
Eslam Kabonga → إسلام كابونجا
Nour El Tot → نور التوت
7l2olo → حلقوله 
Modi Amin → مودي أمين
Ahmed Moza → أحمد موزة
Marwan Moussa → مروان موسى
Marwan Pablo → مروان بابلو
Michael Jackson → مايكل جاكسون
Billie Eilish → بيلي آيليش
Dominic Fike → دومينيك فايك
Jung Kook → جونغكوك
Latto → لاتو
Karim Cristiano → كريم كريستيانو
Mostafa El Gen → مصطفى الجن
Hady Elsoghier → هادي الصغير
Moataz Mady → معتز مادي
Ahmed Amer → أحمد عامر
Mond → موند
Nabil → نبيل
Shehab → شهاب
Amer Mounib → عامر منيب
El Sheikh Hashem Al Saqaf → الشيخ هاشم السقاف
Youssif Elashry → يوسف العشري
Dr Alfons → د. ألفونس
Omar Id → عمر إد
AL SHAMI → الشامي
nour, Eldab3 → نور ،  الدبّع
Fares Sokar → فارس سكر
Versus Music → فيرسس ميوزيك
Karizma Production → كاريزما برودكشن
iMediaMusicRecords → آي ميديا ميوزيك ريكوردز
ML2 Music → إم إل 2 ميوزيك
Banque Misr → بنك مصر
Red Bull Sika → ريد بُل سيكا
Orange → أورنج
Vodafone Music → ڤودافون ميوزيك 
"""

# Parse the new translations
updates = {}
for line in new_artists_raw.split('\n'):
    if '→' in line:
        parts = line.split('→')
        en = parts[0].strip()
        ar = parts[1].strip()
        if en and ar:
            updates[en] = ar

php_file = 'inc/Core/Translation.php'
with open(php_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace or add logic
# We'll use regex to find the 'Artist Names Arabic Translations' block and update it.
# Actually, since it's just an array, let's just append or replace.
# The array is returned at the bottom. Wait, it's defined inside get_default_strings().

lines = content.split('\n')
new_lines = []
in_artists_section = False
added_keys = set()

for line in lines:
    if '// Artist Names Arabic Translations' in line:
        in_artists_section = True
        new_lines.append(line)
        continue
    
    if in_artists_section:
        if line.strip() == '];': # End of array
            # Add all updates not already processed
            for en, ar in updates.items():
                if en not in added_keys:
                    # Escape quotes if necessary
                    en_esc = en.replace("'", "\\'")
                    ar_esc = ar.replace("'", "\\'")
                    new_lines.append(f"        '{en_esc}' => '{ar_esc}',")
            in_artists_section = False
            new_lines.append(line)
        else:
            # Check if this line defines a translation
            match = re.search(r"^\s*'([^']+)'\s*=>\s*'([^']+)',", line)
            if match:
                en = match.group(1)
                if en in updates:
                    ar_esc = updates[en].replace("'", "\\'")
                    new_lines.append(f"        '{en}' => '{ar_esc}',")
                    added_keys.add(en)
                else:
                    new_lines.append(line)
            else:
                new_lines.append(line)
    else:
        new_lines.append(line)

with open(php_file, 'w', encoding='utf-8') as f:
    f.write('\n'.join(new_lines))

print("Translation strings updated!")
