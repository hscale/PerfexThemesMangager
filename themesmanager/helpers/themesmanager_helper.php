<?php
defined('BASEPATH') or exit('No direct script access allowed');

function add_themesmanager_scripts($group){
    if($group == "admin") {
        $CI = &get_instance();
        $CI->app_scripts->add('themesmanager-js', theme_dir_url(THEMESMANAGER_MODULE_NAME, 'assets/themesmanager.js?v='.THEMESMANAGER_MODULE_VERSION));
    }
}

/**
 * Init language editor theme menu items in setup in admin_init hook
 * @return null
 */
function add_setup_menu_themesmanager_link(){
    if (has_permission('themesmanager', '', 'view')) {
        $CI = &get_instance();
        /**
         * If the logged in user is administrator, add custom menu in Setup
         */
        $CI->app_menu->add_setup_menu_item('themesmanager', [
            'href'     => admin_url('themesmanager'),
            'name'     => _l('themesmanager'),
            'position' => 300,
        ]);
    }
}

/**
 * Staff permissions for translation theme
 * @param $corePermissions array
 * @param $data array
 * @return array
 */
function themesmanager_staff_permissions($corePermissions, $data){
    $corePermissions['themesmanager'] = [
        'name'         => _l('themesmanager'),
        'capabilities' => [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit' => _l('permission_edit'),
        ],
    ];
    return $corePermissions;
}

/**
 * Add additional settings for this theme in the theme list area
 * @param  array $actions current actions
 * @return array
 */
function module_themesmanager_action_links($actions)
{
    $actions[] = '<a href="' . admin_url(THEMESMANAGER_MODULE_NAME) . '/">' . _l('settings') . '</a>';
    $actions[] = '<a href="#">' . _l('documents') . '</a>';
    return $actions;
}


/**
 * @since  2.3.4
 *
 * Custom function to add support for theme for some features, see below the @param $feature to see what's available.
 *
 * @param string $theme_name    the theme system name
 * @param string $feature        currently available features: my_prefixed_view_files
 * @return  void
 */
function add_theme_support($theme_name, $feature)
{
    get_instance()->app_themesmanager->add_supports_feature($theme_name, $feature);
}

/**
 * @since 2.3.4
 * @see  add_theme_support
 *
 * @param  string $theme_name  theme system name
 * @param  string $feature     feature name
 * @return boolean
 */
function theme_supports($theme_name, $feature)
{
    return get_instance()->app_themesmanager->supports_feature($theme_name, $feature);
}


/**
 * @since  2.3.0
 * Theme list URL for admin area
 * @return string
 */
function themes_list_url()
{
    return admin_url('themes');
}


/**
 * @since  2.3.0
 * Theme views path
 * e.q. themes/theme_name/views
 * @param  string $theme theme system name
 * @param  string $concat append string to the path
 * @return string
 */
function theme_views_path($theme, $concat = '')
{
    return theme_dir_path($theme) . 'views/' . $concat;
}

/**
 * @since  2.3.0
 * Theme libraries path
 * e.q. themes/theme_name/libraries
 * @param  string $theme theme name
 * @param  string $concat append additional string to the path
 * @return string
 */
function theme_libs_path($theme, $concat = '')
{
    return theme_dir_path($theme) . 'libraries/' . $concat;
}

/**
 * @since  2.3.0
 * Theme directory absolute path
 * @param  string $theme theme system name
 * @param  string $concat append additional string to the path
 * @return string
 */
function theme_dir_path($theme, $concat = '')
{
    return APP_THEMESMANAGER_PATH . $theme . '/' . $concat;
}

/**
 * @since  2.3.0
 * Theme URL
 * e.q. https://crm-installation.com/theme_name/
 * @param  string $theme  theme system name
 * @param  string $segment additional string to append to the URL
 * @return string
 */
function theme_dir_url($theme, $segment = '')
{
    return site_url(basename(APP_THEMESMANAGER_PATH) . '/' . $theme . '/' . ltrim($segment, '/'));
}

/**
* @since  2.3.0
 * This is private function
 * List of uninstallable themes
 * In most cases these are the default themes that comes with the installation
 * @return array
 */
function uninstallable_themes()
{
    return ['theme_style', 'menu_setup', 'backup', 'surveys', 'goals', 'exports'];
}

/* 
* This function copy $source directory and all files 
* and sub directories to $destination folder
*/

function recursive_copy($src,$dst) {
	$dir = opendir($src);
	@mkdir($dst);
	while(( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
			if ( is_dir($src . '/' . $file) ) {
				recursive_copy($src .'/'. $file, $dst .'/'. $file);
			}
			else {
				copy($src .'/'. $file,$dst .'/'. $file);
			}
		}
	}
	closedir($dir);
}

