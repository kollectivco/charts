<?php
/**
 * Quick Translation UI
 */

use Charts\Core\Translation;

$registered_strings = Translation::get_all_registered();
$saved_translations = get_option('kcharts_translations', []);
if ( ! is_array( $saved_translations ) ) {
    $saved_translations = [];
}
?>

<div class="wrap kc-translation-wrap premium-bento">
    <form method="post" action="" id="kc-translation-form">
        <?php wp_nonce_field( 'kcharts_save_translations' ); ?>
        
        <div class="kc-settings-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
            <div class="kc-branding">
                <h1 class="kc-title" style="margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -0.04em; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-translation" style="font-size: 32px; width: 32px; height: 32px;"></span>
                    Quick Translation
                </h1>
                <p class="kc-subtitle" style="margin: 8px 0 0; color: #64748b; font-size: 15px;">Allows you to quickly translate front-end strings to your language.</p>
            </div>
            <div class="kc-header-actions" style="display: flex; gap: 10px;">
                <button type="submit" name="charts_action" value="save_translations" class="kb-btn kb-btn-primary" style="background: #0f172a; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-update" style="font-size: 16px; width: 16px; height: 16px; margin-top: -2px;"></span>
                    Save Translations
                </button>
            </div>
        </div>

        <div class="kb-warning-notice" style="background: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 16px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 30px; display: flex; gap: 10px;">
            <span class="dashicons dashicons-info" style="color: #d97706; margin-top: 2px;"></span>
            <div>
                <strong>PLEASE NOTE:</strong> The default text is Egyptian Arabic. If you leave a field blank, it will automatically use the default Arabic translation.
            </div>
        </div>

        <div class="kb-table-card" style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden;">
            <table class="kb-translation-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr>
                        <th style="padding: 20px 24px; border-bottom: 2px solid #e2e8f0; font-size: 14px; font-weight: 800; color: #1e293b; background: #f8fafc; width: 40%;">
                            <span class="dashicons dashicons-admin-site-alt3" style="font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom; margin-right: 6px; color: #64748b;"></span>
                            Source String - English
                        </th>
                        <th style="padding: 20px 24px; border-bottom: 2px solid #e2e8f0; font-size: 14px; font-weight: 800; color: #1e293b; background: #f8fafc;">
                            <span class="dashicons dashicons-translation" style="font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom; margin-right: 6px; color: #64748b;"></span>
                            Translation
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registered_strings as $english_key => $default_arabic) : 
                        $current_val = isset($saved_translations[$english_key]) ? $saved_translations[$english_key] : '';
                    ?>
                    <tr>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #475569; font-weight: 500;">
                            <?php echo esc_html($english_key); ?>
                        </td>
                        <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                            <input type="text" name="kc_trans[<?php echo esc_attr($english_key); ?>]" value="<?php echo esc_attr($current_val); ?>" placeholder="<?php echo esc_attr($default_arabic); ?>" style="width: 100%; border: none; background: transparent; font-size: 14px; color: #059669; font-weight: 700; outline: none; padding: 8px 0;" class="kc-trans-input">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<style>
.kc-translation-wrap { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: -apple-system, system-ui, sans-serif; }
.kc-trans-input:focus { border-bottom: 2px solid #059669 !important; border-radius: 0; background: #f8fafc !important; padding: 8px !important; }
.kc-trans-input::placeholder { color: #cbd5e1; font-weight: 500; }
.kb-btn-primary:hover { background: #1e293b !important; transform: translateY(-1px); }
.kb-translation-table tr:last-child td { border-bottom: none !important; }
.kb-translation-table tbody tr:hover td { background: #f8fafc; }
</style>
