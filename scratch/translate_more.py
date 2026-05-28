import os
import glob
import re

template_dir = '/Users/appleworld/Desktop/APP DEV/charts new/public/templates'

replacements = {
    r"'#1 This Week'": r"'رقم 1 الأسبوع ده'",
    r"'>1 wks on chart<'": r"'>1 أسابيع في السباق<'",
    r"'>(\d+) wks on chart<'": r"'>\g<1> أسابيع في السباق<'",
    r"'Peak #(\d+)'": r"'أعلى مركز #\g<1>'",
    r"'>Wks On Chart<'": r"'>أسابيع في السباق<'",
    r"'>Last Wk<'": r"'>الأسبوع اللي فات<'",
    r"'>Cover Title<'": r"'>الغلاف والاسم<'",
    r"'>Move<'": r"'>الحركة<'",
    r"'>Current Rank<'": r"'>المركز الحالي<'",
    r"'>Peak Position<'": r"'>أعلى مركز<'",
    r"'>Previous Week<'": r"'>الأسبوع اللي فات<'",
    r"'>Weeks on Chart<'": r"'>أسابيع في السباق<'",
    r"'More Details'": r"'تفاصيل أكتر'",
    r"'>Chart Not Found<'": r"'>السباق مش موجود<'",
    r"'>The requested chart definition does not exist.<'": r"'>السباق اللي بتدور عليه مش موجود حالياً.<'",
    r"Settings::get\('label_breakdown', 'More Details'\)": r"Settings::get('labels.chart_cta_text', 'تفاصيل أكتر')"
}

for root, _, files in os.walk(template_dir):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            for old, new in replacements.items():
                content = re.sub(old, new, content)
            
            # case insensitive exact replacements for some:
            content = re.sub(r'(?i)>WKS ON CHART<', '>أسابيع في السباق<', content)
            content = re.sub(r'(?i)>LAST WK<', '>الأسبوع اللي فات<', content)
            content = re.sub(r'(?i)>COVER TITLE<', '>الغلاف والاسم<', content)
            content = re.sub(r'(?i)>MOVE<', '>الحركة<', content)
            content = re.sub(r'(?i)>CURRENT RANK<', '>المركز الحالي<', content)
            content = re.sub(r'(?i)>PEAK POSITION<', '>أعلى مركز<', content)
            content = re.sub(r'(?i)>PREVIOUS WEEK<', '>الأسبوع اللي فات<', content)
            content = re.sub(r'(?i)>WEEKS ON CHART<', '>أسابيع في السباق<', content)

            if content != original_content:
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Translated: {file}")

print("Done.")
