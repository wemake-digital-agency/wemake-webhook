<?php

// Contact form 7 - mail sent

function wmhk_wpcf7_mail_sent($cf7){

    if(empty($_POST)){
        return;
    }

    // Get webhook URL

    if(method_exists($cf7, 'id')){
        $form_id = intVal(sanitize_key($cf7->id()));
    }elseif(isset($_POST['_wpcf7'])){
        $form_id = intVal(sanitize_key($_POST['_wpcf7']));
    }

    if(empty(get_option('wmhk_advanced_mode'))){
        $webhook_url = sanitize_url(get_option('wmhk_default_webhook_url'));
    }elseif(!empty($form_id)){
        $webhook_url = sanitize_url(get_option('wmhk_webhook_url_fr_' . $form_id));
    }

    if(empty(trim($webhook_url))){
        return;
    }

    // Send data

    $submission = WPCF7_Submission::get_instance();

    $post_data = $submission->get_posted_data();

    if(!empty($post_data['keyword'])){
        $post_data['keyword'] = urldecode($post_data['keyword']);
    }

    $post_data['http_referer'] = $_SERVER['HTTP_REFERER'];

    $post_data = apply_filters('wmhk_post_data', $post_data);

    $response = wp_remote_post($webhook_url, array(
        'method'      => 'POST',
        'body'        => wp_json_encode($post_data),
        'headers'     => array(
            'Content-Type'  => 'application/json; charset=utf-8',
        ),
    ));

    $debug = apply_filters('wmhk_debug', false);

    if(!empty($debug)){
        if(is_wp_error($response)){
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        }else{
            echo 'Response:<pre>';
            print_r($response);
            echo '</pre>';
        }
    }

    $debug_post = apply_filters('wmhk_debug_post', false);

    if(!empty($debug_post)){
        echo 'POST DATA:<pre>';
        print_r($post_data);
        echo '</pre>';
    }

}

add_action('wpcf7_mail_sent', 'wmhk_wpcf7_mail_sent');

?>