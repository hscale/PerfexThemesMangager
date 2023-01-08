<?php defined('BASEPATH') or exit('No direct script access allowed');

add_option('themesmanager', 1);
add_option('theme_active', NULL, 1);
add_option('theme_locations', FCPATH.'/themesmanager', 1);

$CI = &get_instance();


if (!$CI->db->table_exists(db_prefix() . 'themes')) {
  log_message('info', 'tables themes creat');
 

  $CI->db->query('CREATE TABLE `' . db_prefix() . "themes` (
      `id` INT(11) NOT NULL,
      `theme_name` VARCHAR(255) NOT NULL,
      `theme_locations` VARCHAR(255),
      `installed_version` VARCHAR(11),
      `active` TINYINT(1) DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'themes`
  ADD PRIMARY KEY (`id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
  echo "DROP TABLE IF EXISTS `tblmodules`;
CREATE TABLE IF NOT EXISTS `tblmodules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(55) NOT NULL,
  `installed_version` varchar(11) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8;";
}

/**
 * Make themes folder and init default theme
 * */

 @mkdir(APP_THEMESMANAGER_PATH);
 recursive_copy(FCPATH .'modules/'.THEMESMANAGER_MODULE_NAME.'/default_theme',APP_THEMESMANAGER_PATH.'/default_theme',);
 recursive_copy(APPPATH .'views',APP_THEMESMANAGER_PATH.'/default_theme/views',);

 /**
  * Make autoload
  */
//$core_autoload_path = APPPATH.'config/autoload.php';
$core_my_autoload_path = APPPATH.'config/my_autoload.php';

$themesmanager_my_autoload_path = FCPATH.'modules/themesmanager/my_autoload.php';

if (!file_exists($core_my_autoload_path))
{
    copy($themesmanager_my_autoload_path, $core_my_autoload_path);    
}
else{
    
  $autoload_code = read_file($core_my_autoload_path); // Read my_autoload.php file.
  if (preg_match("|if \(file_exists\(FCPATH.'/modules/themesmanager/libraries/_autoload.php'\)\) {include_once\(FCPATH.'/modules/themesmanager/libraries/_autoload.php'\);}|mi",  $autoload_code)) {
      //echo "A match was found.";
  } else {
      //echo "A match was not found.";
      try {      
          $my_autoload = @fopen($core_my_autoload_path, "a") or die("Unable to open file!");
          $_autoload_txt = "if (file_exists(FCPATH.'/modules/themesmanager/libraries/_autoload.php')) {include_once(FCPATH.'/modules/themesmanager/libraries/_autoload.php');}";
          fwrite($my_autoload, "\n". $_autoload_txt);
          fclose($my_autoload);
        }        
        //catch exception
        catch(Exception $e) {
          log_message('error',$e->getMessage());
        }                
  }

}