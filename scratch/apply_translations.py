import os
import re

template_dir = '/Users/appleworld/Desktop/APP DEV/charts new/'

replacements = {
    r"أسابيع في الشارتس": r"<?php echo \Charts\Core\Translation::get('wks on chart'); ?>",
    r"أعلى مركز #": r"<?php echo \Charts\Core\Translation::get('Peak #'); ?>",
    r"جديد": r"<?php echo \Charts\Core\Translation::get('NEW'); ?>",
    r"الفنان": r"<?php echo \Charts\Core\Translation::get('Artist'); ?>",
    r"متصدر الأسبوع": r"<?php echo \Charts\Core\Translation::get('Chart Leader'); ?>",
    r"رقم ١ الأسبوع ده": r"<?php echo \Charts\Core\Translation::get('#1 This Week'); ?>",
    r"المركز السابق": r"<?php echo \Charts\Core\Translation::get('Previous Rank'); ?>",
    r"المركز الحالي": r"<?php echo \Charts\Core\Translation::get('Current Rank'); ?>",
    r"بحث": r"<?php echo \Charts\Core\Translation::get('Search'); ?>",
    r"كل السباقات": r"<?php echo \Charts\Core\Translation::get('All Charts'); ?>",
    r"أفضل الفنانين": r"<?php echo \Charts\Core\Translation::get('Top Artists'); ?>",
    r"فنان تريند": r"<?php echo \Charts\Core\Translation::get('Trending Artist'); ?>",
    r"عرض السباق كاملاً": r"<?php echo \Charts\Core\Translation::get('View Full Chart'); ?>",
    r"المركز": r"<?php echo \Charts\Core\Translation::get('Rank'); ?>",
    r"الحركة": r"<?php echo \Charts\Core\Translation::get('Movement'); ?>",
    r"تفاصيل": r"<?php echo \Charts\Core\Translation::get('Details'); ?>",
    r"استمع": r"<?php echo \Charts\Core\Translation::get('Listen'); ?>",
    r"شاهد": r"<?php echo \Charts\Core\Translation::get('Watch'); ?>",
    r"الأسبوع اللي فات": r"<?php echo \Charts\Core\Translation::get('Previous Rank'); ?>", # Fix to use same key
}

# The problem with direct replacement: sometimes it's already inside a PHP block, or it's just raw HTML text.
# Let's use a targeted approach per file to avoid breaking HTML.

files_to_check = []
for root, _, files in os.walk(template_dir):
    if 'vendor' in root or '.git' in root or 'node_modules' in root or 'scratch' in root or 'admin' in root:
        continue
    if 'public/templates' in root or 'inc/Integrations/Elementor/Widgets' in root or 'inc/Core' in root:
        for file in files:
            if file.endswith('.php'):
                files_to_check.append(os.path.join(root, file))

for path in files_to_check:
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # We will simply replace the Arabic strings with <?php echo \Charts\Core\Translation::get('...'); ?>
    # But ONLY if they are NOT inside an existing <?php block or HTML attribute like value="بحث" (wait, value="بحث" needs value="<?php ... ?>")
    
    # Safe simple replacement for all occurrences
    for ar_str, php_code in replacements.items():
        # But wait, in Settings.php, there are PHP arrays: 'chart_cta_text' => 'عرض السباق كاملاً'
        # If we replace those, it becomes 'chart_cta_text' => '<?php echo ...' which breaks PHP!
        # So we only apply replacements if the file is a template (public/templates) or a widget.
        if 'public/templates' in path or 'Widgets' in path:
            # Check if it's already inside a PHP string (this is naive, but works for most)
            # Actually, the templates are mostly HTML mixed with PHP.
            # Example: <span>أسابيع في الشارتس</span> -> <span><?php echo \Charts\Core\Translation::get('wks on chart'); ?></span>
            content = content.replace(ar_str, php_code)
            
            # Fix if we accidentally put <?php inside another <?php
            content = re.sub(r"<\?php\s*echo\s*<\?php\s*echo\s*(.*?);\s*\?>\s*;\s*\?>", r"<?php echo \1; ?>", content)

    if content != original_content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated: {path}")

print("Done.")
