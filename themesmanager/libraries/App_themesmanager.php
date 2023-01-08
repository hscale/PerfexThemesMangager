<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_themesmanager
{
    /**
     * @since  2.3.4
     *
     * There is function add_theme_support($theme_name, $feature) so themes can hook support
     * Check the function add_theme_support for more info
     *
     * @var array
     */
    private static $supports = [];

    private $ci;

    /**
     * The themes info that is stored in database
     * @var array
     */
    private $db_themes = [];

    /**
     * All valid themes
     * @var array
     */
    private $themes = [];

    /**
     * All activated themes
     * @var array
     */
    private $active_themes = [];

    /**
     * Theme new version data
     * @var array
     */
    private $new_version_data = [];

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('directory');

        /**
         * The themes feature is added in version 2.3.0 if the current database version is smaller don't try to load the themes
         * This code exists because after update, the database is not yet updated and the table themes does not exists and will throw errors.
         */
        if ($this->ci->app->get_current_db_version() < 230) {
            return;
        }

        $this->initialize();
    }

    /**
     * Activate Theme
     * @param  string $name Theme Name [system_name]
     * @return boolean
     */
    public function activate($name)
    {
        /*         
        * Deactivate activated theme
        */
        foreach ($this->get_valid_themes() as $theme_to_deactivate) {
            $this->deactivate($theme_to_deactivate['name']);
        } 

        $theme = $this->get($name);

        if (!$theme) {
            return false;
        }

        /**
         * Check if theme is already added to database
         */

        if (!$this->theme_exists_in_database($name)) {
            $this->ci->db->where('theme_name', $name);
            $this->ci->db->insert(db_prefix() . 'themes', ['theme_name' => $name, 'installed_version' => $theme['headers']['version']]);
        }
        
        /**
         * Require the init theme file
         */

        include_once($theme['init_file']);
        
        /**
         * Require the theme functions file
         */
        $path_parts = pathinfo($theme['init_file']);        
        if (file_exists($path_parts['dirname'].'/functions.php')) {            
            include_once($path_parts['dirname'].'/functions.php');
        }

        /**
        * Maybe used from another themes?
        */
        hooks()->do_action('pre_activate_theme', $theme);

        /**
         * Theme developers can add hooks for their own activate actions that needs to be taken
         */
        hooks()->do_action("activate_{$name}_theme");

        /**
         * Activate the theme in database
         */
        $this->ci->db->where('theme_name', $name);
        $this->ci->db->update(db_prefix() . 'themes', ['active' => 1]);
        
        $this->ci->db->where('name', 'theme_active');
        $this->ci->db->update(db_prefix() . 'options', ['value' => $name]);        
        

        /**
         * After theme is activated action
         */
        hooks()->do_action('theme_activated', $theme);

        return true;
    }

    /**
     * Deactivate Theme
     * @param  string $name Theme Name [system_name]
     * @return boolean
     */
    public function deactivate($name)
    {
        $theme = $this->get($name);

        if (!$theme) {
            return false;
        }

        /**
         * Maybe used from another themes?
         */
        hooks()->do_action('pre_deactivate_theme', $theme);

        /**
         * Theme developers can add hooks for their own activate actions that needs to be taken
         */
        hooks()->do_action("deactivate_{$name}_theme");

        /**
         * Deactivate the theme in database
         */
        $this->ci->db->where('theme_name', $name);
        $this->ci->db->update(db_prefix() . 'themes', ['active' => 0]);
                
        $this->ci->db->where('name', 'theme_active');
        $this->ci->db->update(db_prefix() . 'options', ['value' => NULL]);


        /**
         * After theme is activated action
         */
        hooks()->do_action('theme_deactivated', $theme);

        return true;
    }

    /**
     * Uninstall Theme
     * @param  string $name Theme Name [system_name]
     * @return boolean
     */
    public function uninstall($name)
    {
        $theme = $this->get($name);

        if (!$theme) {
            return false;
        }

        /**
         * Theme needs to be deactivated first in order to be uninstalled
         */
        if ($theme['activated'] == 1 || in_array($name, uninstallable_themes())) {
            return false;
        }

        /**
         * Maybe used from another themes?
         */
        hooks()->do_action('pre_uninstall_theme', $theme);

        /**
         * Remove the theme from database
         */
        $this->ci->db->where('theme_name', $name);
        $this->ci->db->delete(db_prefix() . 'themes');

        /**
         * Theme developers can add hooks for their own uninstall actions that needs to be taken
         */
        $uninstallPath = $theme['path'] . 'uninstall.php';
        if (file_exists($uninstallPath)) {
            include_once($uninstallPath);
        } else {
            hooks()->do_action("uninstall_{$name}_theme");
        }

        /**
         * Delete theme files
         */
        if (is_dir($theme['path'])) {
            delete_files($theme['path'], true);
            rmdir($theme['path']);
        }

        /**
         * After theme is uninstalled action
         */
        hooks()->do_action('theme_uninstalled', $theme);

        return true;
    }

    /**
     * Get all activated themes
     * @return array
     */
    public function get_activated()
    {
        return $this->active_themes;
    }

    /**
     * Check whether a theme is active
     * @param  string  $name theme name
     * @return boolean
     */
    public function is_active($name)
    {
        return array_key_exists($name, $this->get_activated());
    }

    /**
     * Check whether a theme is inactive
     * @param  string  $name theme name
     * @return boolean
     */
    public function is_inactive($name)
    {
        return ! $this->is_active($name);
    }

    /**
     * Check whether a theme is installed for a first time
     * @param  string  $name theme name
     * @return boolean
     */
    public function is_installed($name)
    {
        if (!isset($this->themes[$name])) {
            return false;
        }

        return $this->themes[$name]['installed_version'] !== false;
    }

    /**
     * Check if the theme minimum requirement version is met
     * @param  [type]  $name [description]
     * @return boolean       [description]
     */
    public function is_minimum_version_requirement_met($name)
    {
        $theme = $this->get($name);

        if (!isset($theme['headers']['requires_at_least'])) {
            return true;
        }

        $this->ci->config->load('migration');
        $appVersion               = wordwrap($this->ci->config->item('migration_version'), 1, '.', true);
        $themeRequiresAppVersion = $theme['headers']['requires_at_least'];

        if (version_compare($appVersion, $themeRequiresAppVersion, '>=')) {
            return true;
        }

        return false;
    }

    /**
     * Upgrade theme to latest database version
     * @param  string $name theme name
     * @return mixed
     */
    public function upgrade_database($name)
    {
        $migration = new App_themesmanager_migration($name);

        if ($migration->to_latest() === false) {
            return $migration->error_string();
        }

        return true;
    }

    /**
     * Check whether database upgrade is required to theme
     * When theme Version header is different then the one stored in database
     * @param  string  $name theme name
     * @return boolean
     */
    public function is_database_upgrade_required($name)
    {
        $theme = $this->get($name);

        $themeInstalledVersion = $theme['installed_version'];

        if ($themeInstalledVersion == false) {
            // Not yet activated for the first time
            return false;
        }

        $themeFilesVersion = $theme['headers']['version'];

        /**
        * Check if downgrade is required
        * By default, version_compare() returns -1 if the first version is lower than the second,
        * 0 if they are equal, and 1 if the second is lower.
        */
        if (version_compare($themeInstalledVersion, $themeFilesVersion) === 1) {
            return true;
        }

        if (version_compare($themeFilesVersion, $themeInstalledVersion) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Themes can create release_handler.php file inside the theme root directory and apply their own logic to check whether there is new version available.
     * release_handler.php file should return false if there is no version available or array with e.q. the following params:
     * $data['version'] = VERSION_NUMBER;
     * (Optional) $data['changelog'] = 'https://official-website.com/plugin/changelog';
     * (Optional) $data['update_handler'] = '';
     * @param  string  $name theme system name
     * @return mixed
     */
    public function new_version_available($name)
    {
        $retVal = $this->get_new_version_data($name);

        if ($retVal !== false && !is_array($retVal)) {
            return false;
        }

        return $retVal;
    }

    public function get_new_version_data($name)
    {
        if (isset($this->new_version_data[$name])) {
            return $this->new_version_data[$name];
        }

        $file = theme_dir_path($name, 'release_handler.php');

        if (!file_exists($file)) {
            return false;
        }

        $retVal                        = include_once($file);
        $this->new_version_data[$name] = $retVal;

        if ($this->is_update_handler_available($name)) {
            hooks()->add_action('theme_' . $name . '_update_handler', $retVal['update_handler']);
        }

        return $retVal;
    }

    public function update_to_new_version($name)
    {
        $data = $this->get_new_version_data($name);
        hooks()->do_action('theme_' . $name . '_update_handler', $data['update_handler']);
    }

    public function is_update_handler_available($name)
    {
        $retVal = $this->get_new_version_data($name);
        if (isset($retVal['update_handler']) && $retVal['update_handler']) {
            return true;
        }

        return false;
    }

    /**
     * Return the number of themes that requires database upgrade
     * @return integer
     */
    public function number_of_themes_that_require_database_upgrade()
    {
        $CI       = &get_instance();
        $cacheKey = 'no-of-themes-require-database-upgrade';
        $total    = $CI->app_object_cache->get($cacheKey);
        if ($total === false) {
            $total = 0;
            foreach ($this->themes as $theme) {
                if ($this->is_database_upgrade_required($theme['system_name'])) {
                    $total++;
                }
            }
            $CI->app_object_cache->add($cacheKey, $total);
        }

        return $total;
    }

    /**
     * Get all themes or specific theme if theme system name is passed
     * This method returns all themes including active and inactive
     * @param  mixed $theme
     * @return mixed
     */
    public function get($theme = null)
    {
        if (!$theme) {
            $themes = $this->themes;

            /* Sort themes by name */

            usort($themes, function ($a, $b) {
                return strcmp(strtolower($a['headers']['theme_name']), strtolower($b['headers']['theme_name']));
            });

            return $themes;
        }

        if (isset($this->themes[$theme])) {
            return $this->themes[$theme];
        }

        return null;
    }

    /**
     * Get theme from database
     * @param  string $name theme system name
     * @return mixed
     */
    public function get_database_theme($name)
    {
        if (isset($this->db_themes[$name])) {
            return $this->db_themes[$name];
        }

        $this->ci->db->where('theme_name', $name);

        return $this->ci->db->get(db_prefix() . 'themes')->row();
    }

    /**
     * Initialize all themes
     * @return null
     */
    public function initialize()
    {
        // For caching
        $this->query_db_themes();

        foreach (static::get_valid_themes() as $theme) {
            $name = $theme['name'];
            // If the theme hasn't already been added and isn't a file
            if (!isset($this->themes[$name])) {
                /**
                 * System name
                 */
                $this->themes[$name]['system_name'] = $name;

                /**
                 * Theme headers
                 */
                $this->themes[$name]['headers'] = $this->get_headers($theme['init_file']);
                /**
                 * Init file path
                 * The file name must be the same like the theme folder name
                 */
                $this->themes[$name]['init_file'] = $theme['init_file'];
                /**
                 * Theme path
                 */
                $this->themes[$name]['path'] = $theme['path'];

                // Check if theme is activated
                $themeDB = $this->get_database_theme($name);

                if ($themeDB && $themeDB->active == 1) {
                    $this->themes[$name]['activated'] = 1;
                    // Add to active themes handler
                    $this->active_themes[$name] = $this->themes[$name];
                } else {
                    $this->themes[$name]['activated'] = 0;
                }
                /**
                 * Installed version
                 */
                $this->themes[$name]['installed_version'] = $themeDB ? $themeDB->installed_version : false;
            }
        }
    }

    /**
     * @since 2.3.4
     * @see add_theme_support function.
     *
     * @param string $theme_name  theme name
     * @param string $feature     support feature
     *
     */
    public function add_supports_feature($theme_name, $feature)
    {
        if (!isset(self::$supports[$theme_name])) {
            self::$supports[$theme_name] = [];
        }

        if (in_array($feature, self::$supports[$theme_name])) {
            return;
        }

        self::$supports[$theme_name][] = $feature;
    }

    /**
     * @since 2.3.4
     * @see add_theme_support function.
     *
     * @param string $theme_name  theme name
     * @param string $feature     support feature
     * @return  boolean
     */
    public function supports_feature($theme_name, $feature)
    {
        return isset(self::$supports[$theme_name]) && in_array($feature, self::$supports[$theme_name]);
    }

    /**
     * Get theme headers info
     * @param  string $theme_source the theme init file location
     * @return array
     */
    public function get_headers($theme_source)
    {
        $theme_data = read_file($theme_source); // Read the theme init file.

        preg_match('|Theme Name:(.*)$|mi', $theme_data, $name);
        preg_match('|Theme URI:(.*)$|mi', $theme_data, $uri);
        preg_match('|Version:(.*)|i', $theme_data, $version);
        preg_match('|Description:(.*)$|mi', $theme_data, $description);
        preg_match('|Author:(.*)$|mi', $theme_data, $author_name);
        preg_match('|Author URI:(.*)$|mi', $theme_data, $author_uri);
        preg_match('|Requires at least:(.*)$|mi', $theme_data, $requires_at_least);

        $arr = [];

        if (isset($name[1])) {
            $arr['theme_name'] = trim($name[1]);
        }

        if (isset($uri[1])) {
            $arr['uri'] = trim($uri[1]);
        }

        if (isset($version[1])) {
            $arr['version'] = trim($version[1]);
        } else {
            $arr['version'] = 0;
        }

        if (isset($description[1])) {
            $arr['description'] = trim($description[1]);
        }

        if (isset($author_name[1])) {
            $arr['author'] = trim($author_name[1]);
        }

        if (isset($author_uri[1])) {
            $arr['author_uri'] = trim($author_uri[1]);
        }

        if (isset($requires_at_least[1])) {
            $arr['requires_at_least'] = trim($requires_at_least[1]);
        }
        return $arr;
    }

    /**
     * Check whether theme is inserted into database table
     * @param  string $name theme system name
     * @return boolean
     */
    private function theme_exists_in_database($name)
    {
        return (bool) $this->get_database_theme($name);
    }

    /**
     * Get valid themes
     * @return array
     */
    public static function get_valid_themes()
    {
        /**
        * Themes path
        *
        * APP_THEMESMANAGER_PATH constant is defined in application/config/constants.php
        *
        * @var array
        */
        $themes = directory_map(APP_THEMESMANAGER_PATH, 1);
        //var_dump($themes);

        $valid_themes = [];

        if ($themes) {
            foreach ($themes as $name) {
                $name = strtolower(trim($name));

                /**
                 * Filename may be returned like chat/ or chat\ from the directory_map function
                 */
                foreach (['\\', '/'] as $trim) {
                    $name = rtrim($name, $trim);
                }

                // If the theme hasn't already been added and isn't a file
                if (!stripos($name, '.')) {
                    $theme_path = APP_THEMESMANAGER_PATH . $name . '/';
                    $init_file   = $theme_path . $name . '.php';

                    // Make sure a valid theme file by the same name as the folder exists
                    if (file_exists($init_file)) {
                        $valid_themes[] = [
                            'init_file' => $init_file,
                            'name'      => $name,
                            'path'      => $theme_path,
                        ];
                    }
                }
            }
        }

        return $valid_themes;
    }

    private function query_db_themes()
    {
        $db_themes = $this->ci->db->get(db_prefix() . 'themes')->result();

        foreach ($db_themes as $db_theme) {
            $this->db_themes[$db_theme->theme_name] = $db_theme;
        }
    }
}
