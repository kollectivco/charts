import os
import glob
import re

template_dir = '/Users/appleworld/Desktop/APP DEV/charts new/'
# we want to modify public/templates and inc/Integrations/Elementor/Widgets

replacements = {
    r"أسابيع في السباق": r"أسابيع في الشارتس",
    r"الغلاف والاسم": r"الفنان",
}

def wrap_with_helper(content):
    # Wrap specific outputs with the arabic numeral helper
    # Example 1: <?php echo $e->rank_position; ?>
    content = re.sub(r'<\?php\s+echo\s+(\$e->rank_position|intval\([^)]+\)|\$top->peak_rank|\$top->weeks_on_chart|\$top->movement_value|\$e->previous_rank|\$e->peak_rank|\$e->weeks_on_chart|\$row->weeks_on_chart|\$row->peak_rank|1)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)

    # Specific cases that might be missed:
    # <span>+<?php echo intval($top->movement_value); ?></span>
    # The regex above catches it if it matches exactly.
    
    # Let's do more targeted replacements for numbers
    content = re.sub(r'<\?php\s+echo\s+(\$e->rank_position\s+-\s+\$e->previous_rank|\$e->previous_rank\s+-\s+\$e->rank_position)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)

    # In ChartLeader.php:
    content = re.sub(r'<\?php\s+echo\s+(\$row->weeks_on_chart\s+\?:\s+1|\$row->peak_rank\s+\?:\s+1)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)
    
    # In single-chart.php:
    content = re.sub(r'<\?php\s+echo\s+intval\(\$top->peak_rank\s+\?:\s+1\)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(intval($top->peak_rank ?: 1)); ?>', content)
    content = re.sub(r'<\?php\s+echo\s+intval\(\$top->weeks_on_chart\s+\?:\s+1\)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(intval($top->weeks_on_chart ?: 1)); ?>', content)

    content = re.sub(r'<\?php\s+echo\s+(\$e->previous_rank\s+\?:\s+\'—\')\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)
    content = re.sub(r'<\?php\s+echo\s+(\$e->peak_rank\s+\?:\s+\$e->rank_position)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)
    content = re.sub(r'<\?php\s+echo\s+(\$e->weeks_on_chart\s+\?:\s+1)\s*;\s*\?>', r'<?php echo \\Charts\\Core\\Transliteration::to_arabic_numerals(\1); ?>', content)

    return content

files_to_check = []
for root, _, files in os.walk(template_dir):
    if 'vendor' in root or '.git' in root or 'node_modules' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            files_to_check.append(os.path.join(root, file))

for path in files_to_check:
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Apply direct string replacements
    for old, new in replacements.items():
        content = re.sub(old, new, content)
        
    # Apply number wrapper
    content = wrap_with_helper(content)
    
    # Hardcoded '1's in templates to Arabic numeral directly
    content = re.sub(r"'>1<'", r"'>١<'", content)
    content = re.sub(r"'>#1 This Week<'", r"'>رقم ١ الأسبوع ده<'", content)
    content = re.sub(r"'رقم 1 الأسبوع ده'", r"'رقم ١ الأسبوع ده'", content)
    content = re.sub(r"'>1 أسابيع", r"'>١ أسابيع", content)

    if content != original_content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated: {path}")

print("Done.")
