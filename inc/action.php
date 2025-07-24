<?php

function wmhk_change_settings_action(){

    // Security

    if(!wmhk_editor_perm() || check_ajax_referer($_REQUEST['action'])!==1 || wp_verify_nonce($_REQUEST['_wpnonce'], $_REQUEST['action'])!==1) {
        return;
    }

    // Header

    header('Content-Type: application/json');

    // Result array

    $result = array('success' => 0, 'error' => 0);

    // Sanitize data

    $settings = array('advanced_mode' => '');

    if(isset($_POST['default_webhook_url'])){
        $settings['default_webhook_url'] = sanitize_url($_POST['default_webhook_url']);
    }

    if(isset($_POST['advanced_mode'])){
        $settings['advanced_mode'] = intVal(sanitize_text_field($_POST['advanced_mode']));
    }

    if(!empty($contact_forms = wmhk_get_contact_forms())){
        foreach($contact_forms as $form){
            $key = 'webhook_url_fr_' . $form['id'];
            if(isset($_POST[$key])){
                $settings[$key] = sanitize_url($_POST[$key]);
            }
        }
    }

    // Save settings

    if(!empty($settings)){
        foreach($settings as $key=>$option){
            update_option('wmhk_' . $key, $option);
        }
    }

    // Return result

    $result['success'] = 1;

    wmhk_ajax_return($result);

}
add_action('wp_ajax_wmhk_change_settings', 'wmhk_change_settings_action');

?>