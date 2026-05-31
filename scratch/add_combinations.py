import re

new_artists_raw = """
ليجي-سي، غاليا / Lege-Cy, Ghaliaa
ليجي-سي، إسماعيل نصرت / Lege-Cy, Ismail Nosrat
زياد ظاظا، إسماعيل نصرت، ليجي-سي / ZIAD ZAZA, Ismail Nosrat, Lege-Cy
محمود العسيلي، صبرين النجيلي / Mahmoud El Esseily, Sabren Elnegily
محمود العسيلي، بهاء سلطان، بنك مصر / Mahmoud El Esseily, Bahaa Sultan, Banque Misr
مصطفى الجن وهادي الصغيّر - فريق الإبداع / Mustafa El-Gen and Hadi El-Soghayar - Team El-Ebdaa
تومي، رالي / Tommyy, Rally
جونغكوك، لاتو / Jung Kook, Latto
ليجي-سي، حميد الشاعري، إسماعيل نصرت، ريد بول سيكا / Lege-Cy, Hamid Al Shaeri, Ismail Nosrat, Red Bull Sika
مصطفى الجن وهادي الصغيّر - فريق الإبداع، كريم كريستيانو / Mostafa El-Gen and Hadi El-Soghayar - Team El-Ebdaa, Karim Cristiano
ناصر، كاي / Nasser, Kay
زياد ظاظا، خمستاشر / ZIAD ZAZA, 5mstashr
مروان موسى، ليجي-سي / Marwan Moussa, Lege-Cy
أحمد بهاء، ليجي-سي / Ahmed Bahaa, Lege-Cy
إنكريس / Increase
كريم أسامة / Karim Osama
نور، الضبع / nour, Eldab3
أحمد شيبة، عصام صاصا / Ahmed Sheba, Essam Sasa
عمر ميم، عصام صاصا / Omar Meme, Essam Sasa
يوسف الأشري / Youssif Elashry
مولوتوف، مروان بابلو / Molotof, Marwan Pablo
إسلام كابونجا، فيجو الدخلاوي / Eslam Kabonga, Figo El Dakhlawy
ريفو شو / Revo Show
شهاب، دي جي توتي / Shehab, DJ Totti
ناصر، بشمحمد / Nasser, Bashmohannad
ويجز، محمد منير / Wegz, Mohamed Mounir
حمو التيخا، مودي أمين / Hamo ElTikha, Mody Amin
إسلام كابونجا، إيفا الإيرانية، فيجو الدخلاوي / Eslam Kabonga, Eva El Irani, Figo El Dakhlawy
مروان بابلو، هادي معمر / Marwan Pablo, HADY MOAMER
مروان بابلو، زاندر جوست، هادي معمر / Marwan Pablo, Xander Ghost, HADY MOAMER
سانت ليفانت، فارس سكر / Saint Levant, Fares Sokar
كريم كريستيانو، مصطفى الجن، هادي الصغيّر / Karim Cristiano, Mostafa El Gen, Hady Elsoghier
جاستن بيبر، نيكي ميناج / Justin Bieber, Nicki Minaj
ياسمينا العبد، علي الدين عمر، WRST ستوديو / Yasmina El-Abd, Aley Eldin Omar, WRST Studio
مروان الزعيم / Marwan Al-Zaim
أصالة، رامي صبري / Assala, Ramy Sabry
تامر عاشور، تاج مصر / Tamer Ashour, TAJ MISR
عمرو دياب، أورانج، جنا دياب / Amr Diab, Orange, Jana Diab
تامر حسني، الشامي / Tamer Hosny, AL SHAMI
ويجز، ناصر / Wegz, Nasser
"""

file_path = '/Users/appleworld/Desktop/APP DEV/charts new/inc/Core/Translation.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

start_marker = "// Artist Names Arabic Translations"
end_marker = "];"
start_idx = content.find(start_marker)
end_idx = content.find(end_marker, start_idx)

existing_block = content[start_idx:end_idx]

existing_map = {}
for line in existing_block.split('\n'):
    if '=>' in line:
        parts = line.split('=>')
        k = parts[0].strip().strip("'")
        v = parts[1].strip().strip(",").strip().strip("'")
        existing_map[k] = v

for line in new_artists_raw.split('\n'):
    if '/' in line:
        parts = line.split('/')
        ar = parts[0].strip()
        en = parts[1].strip()
        if en and ar:
            existing_map[en] = ar

new_block = start_marker + "\n"
for k, v in existing_map.items():
    new_block += f"        '{k}' => '{v}',\n"

new_content = content[:start_idx] + new_block + "    " + content[end_idx:]

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Updated Translation.php")
