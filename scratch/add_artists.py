import re

new_artists_raw = """
مصطفى الجن وهادي الصغيّر - فريق الإبداع / Mustafa El-Gen and Hadi El-Soghayar - Team El-Ebdaa
حسين الجسمي / Hussain Al Jassmi
عبد الباسط حمودة / Abdel Basset Hamouda
نانسي عجرم / Nancy Ajram
مجدي الزهار / Magdy Elzahar
أمين خطاب / Amin Khattab
صبرين النجيلي / Sabren Elnegily
محمد الباسل / Mohammed Al-Basili
أحمد عادل كروان السيد / Ahmed Adel Karwan elsaied
ليل المحمدي / Layl al-Muhammadi
فارس سكر / Fares Sokar
أكرم حسني / Akram Hosny
ريشا كوستا / Resha Costa
محمد فؤاد / Mohammad Fouad
مودي أمين / Mody Amin
مصطفى كامل / Mostafa Kamel
طارق الشيخ / Tarek El Sheikh
محمد سلطان / MOHAMED SULTAN
سكسيبيت / SXYBIT
كيمو الديب / Kimo Eldeeb
حمدين / Hamdeen
عنب / 3enab
حسن الأسمر / Hasan Al-Asmar
عبد الحليم حافظ / Abdel Halim Hafez
هيفاء وهبي / Haifa Wehbe
بي تي إس / BTS
إيفا الإيرانية / Eva the Iranian
الحسن عادل / Alhassan Adel
أحمد بتشان / Ahmed Batshan
يارا محمد / Yara Mohamed
الصواريخ / El Sawareekh
"""

file_path = '/Users/appleworld/Desktop/APP DEV/charts new/inc/Core/Translation.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replacements
content = content.replace("'أسابيع في الشارتس'", "'أسابيع في القوائم'")
content = content.replace("'كل السباقات'", "'كل القوائم'")
content = content.replace("'عرض السباق كاملاً'", "'عرض القائمة كاملة'")
content = content.replace("'لم يتم العثور على سباقات.'", "'لم يتم العثور على قوائم.'")

if "'Charts' =>" not in content:
    content = content.replace("'All Charts' => 'كل القوائم',", "'Charts' => 'قوائم',\n        'All Charts' => 'كل القوائم',")

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
