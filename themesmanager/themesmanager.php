<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Perfex Themes Manager
Description: Theming and custom views manager for Perfex CRM 
Version: 1.0.0
Author: Perfexcloud
Author URI: https://perfexcloud.com
Requires at least: 2.3.*
*/

define('THEMESMANAGER_MODULE_VERSION', '1.0.0');
define('THEMESMANAGER_MODULE_NAME', 'themesmanager');
define('APP_THEMESMANAGER_PATH', FCPATH . 'themes/');

$CI = &get_instance();

/**
 * Register the theme activation hook
 */
register_activation_hook(THEMESMANAGER_MODULE_NAME, 'themesmanager_activation_hook');

/**
 * The activation function
 */
function themesmanager_activation_hook()
{
    
    require(__DIR__ . '/install.php');
}


/**
 * Register module language files
 */
register_language_files(THEMESMANAGER_MODULE_NAME, ['themesmanager']);


/**
 * Load the module helper
 */
$CI->load->helper(THEMESMANAGER_MODULE_NAME . '/themesmanager');
// Adding setup menu item for module
hooks()->add_action('admin_init', 'add_setup_menu_themesmanager_link');
// Adding permission for module
hooks()->add_action('staff_permissions', 'themesmanager_staff_permissions', 10, 2);
// Adding action links for module
hooks()->add_filter('module_themesmanager_action_links', 'module_themesmanager_action_links');

/**
 * Load the theme actived init and functions file
 */

 $CI->load->database();

 $theme_name = null;
 $sql = 'select * from '.db_prefix().'options where name = "theme_active"';
 $obj = $CI->db->query($sql)->row();
 if($obj){    
         $theme_name = $obj->value;
     }
 if (!empty($theme_name)) {    
     /**
     * Require the init theme file
     */
    require_once(APP_THEMESMANAGER_PATH.'/'.$theme_name.'/'.$theme_name.'.php');

    /**
     * Require the theme functions file
     */    
    if (file_exists(APP_THEMESMANAGER_PATH.'/'.$theme_name.'/functions.php')) {
        include_once(APP_THEMESMANAGER_PATH.'/'.$theme_name.'/functions.php');
    }
 }
