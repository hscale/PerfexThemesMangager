<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

$this->load->database();

$theme_name = null;
$sql = 'select * from '.db_prefix().'options where name = "theme_active"';
$obj = $this->db->query($sql)->row();
if($obj){    
        $theme_name = $obj->value;
    }
if (!empty($theme_name)) {    
    if (!dir_is_empty(FCPATH . 'themes/'.$theme_name.'/views/')) {    
        $this->_ci_view_paths = array(
            FCPATH . 'themes/'.$theme_name.'/views/' => TRUE
        );
    }
}

/*
 * Check if a directory is empty (a directory with just '.svn' or '.git' is empty)
 *
 * @param string $dirname
 * @return bool
 */
function dir_is_empty($dirname)
{
  //if (!is_dir($dirname)) return false;
  foreach (scandir($dirname) as $file)
  {
    if (!in_array($file, array('.','..','.svn','.git'))) return false;
  }
  return true;
}
