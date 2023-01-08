<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Theme Name: Default theme
Description: Default theme just copied views folder and files from current Perfex CRM instance
Version: 1.0.0
Author: Perfexcloud
Author URI: https://perfexcloud.com
Requires at least: 2.3.*
*/


/**
* Register activation theme hook
*/
register_activation_hook('default_theme', 'default_theme_activation_hook');

function default_theme_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}
