<?php

if(!defined('WMHK_ABSPATH')) exit;

// Scripts and styles

add_action('admin_enqueue_scripts', function(){
    if(isset($_GET['page']) && $_GET['page']=='wm-webhook'){
        wp_enqueue_style('wmhk-admin-style', WMHK_URI . '/assets/css/admin-settings.css', array(), WMHK_PLUGIN_VERSION, 'all');
        wp_enqueue_script('wmhk-admin-js', WMHK_URI . '/assets/js/admin-settings.js', array(), WMHK_PLUGIN_VERSION, true);
        wp_enqueue_script('jquery-form');
    }
});

// Plugin page

function wmhk_add_settings_page() {
    add_options_page(
        __('Wemake Webhook', WMHK_PLUGIN_SLUG),
        __('Wemake Webhook', WMHK_PLUGIN_SLUG),
        'manage_options',
        'wm-webhook',
        'wmhk_plugin_settings_page'
    );
}

add_action('admin_menu', 'wmhk_add_settings_page');

function wmhk_plugin_settings_page(){

    $contact_forms = wmhk_get_contact_forms();

    $settings = array(
        'default_webhook_url' => get_option('wmhk_default_webhook_url'),
        'advanced_mode' => get_option('wmhk_advanced_mode'),
    );

    if(!empty($contact_forms)){
        foreach($contact_forms as $form){
            $key = 'webhook_url_fr_' . $form['id'];
            $settings[$key] = get_option('wmhk_' . $key);
        }
    }

    $form_action = wmhk_get_ajax_action_url('wmhk_change_settings');
    $form_class = array('wmhk-form');

    if(!empty($settings['advanced_mode'])){
        $form_class[] = '-advanced';
    }

    ?>

    <div class="wmhk-page">
        <h1 class="wp-heading-inline">
            <?php _e('Wemake Webhook Settings', WMHK_PLUGIN_SLUG); ?>
        </h1>
        <form method="POST" action="<?php esc_attr_e($form_action); ?>" class="<?php esc_attr_e(implode(' ', $form_class)); ?>" enctype="multipart/form-data">

            <div class="wmhk-result"></div>

            <div class="wmhk-basic-fields">
                <div class="wmhk-field">
                    <label for="wmhk_webhook_url" class="wmhk-label">
                        <?php _e('Default Webhook URL:', WMHK_PLUGIN_SLUG); ?>
                    </label><br>
                    <input type="url" name="default_webhook_url" value="<?php esc_attr_e($settings['default_webhook_url']); ?>" id="wmhk_webhook_url">
                </div>
            </div>

            <?php if(!empty($contact_forms)){ ?>
                <div class="wmhk-advanced-fields">
                    <?php foreach($contact_forms as $form){
                        $key = 'webhook_url_fr_' . $form['id'];
                        ?>
                        <div class="wmhk-field">
                            <label for="wmhk_<?php esc_attr_e($key); ?>" class="wmhk-label">
                                <span class="wmhk-cfr-title">
                                    <?php esc_html_e($form['title']); ?>
                                </span> -
                                <?php _e('Webhook URL:', WMHK_PLUGIN_SLUG); ?>
                            </label><br>
                            <input type="url" name="<?php esc_attr_e($key); ?>" value="<?php esc_attr_e($settings[$key]); ?>" id="wmhk_<?php esc_attr_e($key); ?>">
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="wmhk-field wmhk-advanced-mode">
                <div class="wmhk-label">
                    <?php _e('Advanced mode', WMHK_PLUGIN_SLUG); ?>
                </div>
                <p><?php _e('You can send all data from you contact forms to specific Webhook or to turn advanced mode and to select a different Webhook for each form.', WMHK_PLUGIN_SLUG); ?></p>
                <?php
                $checked = !empty($settings['advanced_mode']) ? ' checked' : '';
                ?>
                <div class="wmhk-field-el">
                    <div class="wmhk-toggle"
                         data-text-on="<?php _e('Individual Forms', WMHK_PLUGIN_SLUG); ?>"
                         data-text-off="<?php _e('All Contact Forms', WMHK_PLUGIN_SLUG); ?>">
                        <input type="checkbox" name="advanced_mode" id="wmhk_advanced_mode" value="1"<?php echo $checked; ?>>
                        <label class="wmhk-toggle-label" for="wmhk_advanced_mode"></label>
                    </div>
                </div>
            </div>

            <button type="submit" class="wmhk-submit">
                <?php _e('Save settings', WMHK_PLUGIN_SLUG); ?>
            </button>

        </form>
    </div>

    <?php
}

// Admin footer

add_action('admin_footer', function(){
    ?>
    <script>
        <?php if(isset($_GET['page']) && $_GET['page']=='wm-webhook'){ ?>
        var wmhk_language = {
            "unsaved_changes": "<?php esc_attr_e('You have unsaved changes', WMHK_PLUGIN_SLUG); ?>",
            "request_error": "<?php esc_attr_e('Request error!', WMHK_PLUGIN_SLUG); ?>",
            "success": "<?php esc_attr_e('Settings successfully changed', WMHK_PLUGIN_SLUG); ?>",
        };
        <?php } ?>
    </script>
    <?php
});

// "Settings" link

add_filter('plugin_action_links_wemake-webhook/wemake-webhook.php', function($links){
    $url = get_admin_url() . 'options-general.php?page=wm-webhook';
    $settings_link = array('settings' => '<a href="' . $url . '">' . __('Settings', WMHK_PLUGIN_SLUG) . '</a>');
    $links = array_merge($settings_link, $links);
    return $links;
});


require_once(WMHK_ABSPATH . '/inc/admin_update_plugin_github.php');
