<?php

/*
Plugin Name: Wemake Webhook
Plugin URI: https://www.wemake.co.il/
Description: Contact form 7 webhook
Version: 1.1.2
Author: Wemake Team
Author URI: https://www.wemake.co.il
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: wemake-webhook

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Constants

define("WMHK_PLUGIN_NAME", 'Wemake Webhook');
define("WMHK_PLUGIN_SLUG", 'wemake-webhook');
define("WMHK_PLUGIN_VERSION", '1.1.2');
define("WMHK_ABSPATH", dirname( __FILE__ ));
define("WMHK_URI", plugins_url().'/'.WMHK_PLUGIN_SLUG);
define('WMHK_HTTP_HOST', get_site_url());

// PHP version

if(version_compare(phpversion(), '5.6.40', '<')){
    add_action('admin_notices', function(){
        $message = 'Your server is running PHP version '.phpversion().' but '.WMHK_PLUGIN_NAME.' requires at least 5.6.40. The plugin does not work.';
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr('notice notice-error'), esc_html( $message ) );
    });
    return false;
}

// Controllers

if(isset($_GET['action']) && (function_exists('wp_doing_ajax') &&  wp_doing_ajax() || defined('DOING_AJAX'))){
    require_once(WMHK_ABSPATH . '/inc/action.php');
}

require_once(WMHK_ABSPATH . '/inc/functions.php');
require_once(WMHK_ABSPATH . '/inc/webhook.php');

add_action("wp_loaded", function(){
    if(is_admin() || is_multisite() && is_network_admin()){
        require_once(WMHK_ABSPATH . '/inc/admin-settings.php');
    }
});

?>
