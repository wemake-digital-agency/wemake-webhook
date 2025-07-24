<?php

if(!defined('WMHK_ABSPATH')) exit;

function wmhk_reg_esc($c){
    $patterns = array('/\//', '/\^/', '/\./', '/\$/', '/\|/',
        '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/',
        '/\?/', '/\{/', '/\}/', '/\,/');
    $replace = array('\/', '\^', '\.', '\$', '\|', '\(', '\)',
        '\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,');
    return preg_replace($patterns,$replace, $c);
}

function wmhk_editor_perm(){
    if(current_user_can('administrator') || current_user_can('editor')){
        return true;
    }
    return false;
}

function wmhk_ajax_return_esc($arr){
    if(!empty($arr)){
        foreach($arr as $k=>$item){
            if(is_array($item)){
                $arr[$k] = wmhk_ajax_return_esc($item);
            }else{
                $arr[$k] = esc_html($item);
            }
        }
    }
    return $arr;
}

function wmhk_ajax_return($data){
    echo json_encode(wmhk_ajax_return_esc($data));
    exit;
}

function wmhk_get_ajax_action_url($action, $parameters = array()){

    $action_url = admin_url('/admin-ajax.php?action='.$action.'&_wpnonce='.wp_create_nonce($action));

    if(count($parameters)){
        foreach($parameters as $par_k=>$par){
            $action_url .= '&'.$par_k.'='.$par;
        }
    }

    return esc_url($action_url);

}

function wmhk_get_contact_forms(){

    global $wpdb, $table_prefix;

    return $wpdb->get_results('
        SELECT 
            `ID` as `id`,
            `post_title` as `title`
        FROM `'.$table_prefix.'posts`
        WHERE
            `post_type` = "wpcf7_contact_form" AND
            `post_status` = "publish"
        ORDER BY `post_title` ASC
    ', ARRAY_A);

}

function wmhk_get_plugin_row_path(){
    return WMHK_PLUGIN_SLUG.'/'.WMHK_PLUGIN_SLUG.'.php';
}

?>
