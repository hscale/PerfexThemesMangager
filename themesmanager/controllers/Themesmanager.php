<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Themesmanager extends AdminController
{
    public function __construct()
    {
        parent::__construct(); 
        
        //Themesmanager are only accessible by administrators
        
        if (!is_admin()) {
            redirect(admin_url());
        }             
        
        $this->load->library('App_themesmanager');
        $this->load->library('App_themesmanager_migration');
        $this->load->library('App_themesmanager_installer');
        
        $this->load->model('themesmanager_model');     
        
        //$CI = &get_instance();
        $data['themes'] = $this->app_themesmanager->get();


        hooks()->do_action('themes_loaded');
        // Load the directory helper so the directory_map function can be used
        include_once(FCPATH .'/system/helpers/directory_helper.php');

        foreach ($this->app_themesmanager->get_valid_themes() as $theme) {
            $excludeUrisPath = $theme['path'] . 'config' . DIRECTORY_SEPARATOR . 'csrf_exclude_uris.php';

            if (file_exists($excludeUrisPath)) {
                $uris = include_once($excludeUrisPath);

                if (is_array($uris)) {
                    hooks()->add_filter('csrf_exclude_uris', function ($current) use ($uris) {
                        return array_merge($current, $uris);
                    });
                }
            }
        } 
        
    }

    public function index()
    {   
        /* List all themes */ 
        
        $CI = &get_instance();
        $data['themes'] = $CI->app_themesmanager->get();
        $alert_autoload = $this->define_my_autoload_core();
        
        if (!has_permission('themesmanager', '', 'view')) {
            access_denied('themesmanager');
        }
        
        $data['alert_autoload'] = $alert_autoload;
        $data['title']   = _l('themesmanager');
        //$this->load->view('index', $data);
        $this->load->view('themesmanager/list', $data);
    }
 
    
    function define_my_autoload_core(){

        /**
         * Make autoload
        */
        $core_my_autoload_path = APPPATH.'config/my_autoload.php';
        
        $themesmanager_my_autoload_path = FCPATH.'modules/themesmanager/my_autoload.php';
        $alert_autoload = false;
        if (!file_exists($core_my_autoload_path))
        {
            copy($themesmanager_my_autoload_path, $core_my_autoload_path);
            $alert_autoload = false;
            
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
                    $alert_autoload = true;
                  }                
            }
        }
        return $alert_autoload;

    }

    public function activate($name)
    {
        $this->app_themesmanager->activate($name);
        $this->to_themesmanager();
    }

    public function deactivate($name)
    {
        $this->app_themesmanager->deactivate($name);
        $this->to_themesmanager();
    }

    public function uninstall($name)
    {
        $this->app_themesmanager->uninstall($name);
        $this->to_themesmanager();
    }

    public function upload()
    {
        $this->load->library('app_themesmanager_installer');
        $data = $this->app_themesmanager_installer->from_upload();

        if ($data['error']) {
            set_alert('danger', $data['error']);
        } else {
            set_alert('success', 'Theme uploaded successfully');
        }

        $this->to_themesmanager();
    }

    public function upgrade_database($name)
    {
        $result = $this->app_themesmanager->upgrade_database($name);

        // Possible error
        if (is_string($result)) {
            set_alert('danger', $result);
        } else {
            set_alert('success', 'Database Upgraded Successfully');
        }

        $this->to_themesmanager();
    }

    public function update_version($name)
    {
        if($this->app_themesmanager->new_version_available($name)) {
            $this->app_themesmanager->update_to_new_version($name);
        }

        $this->to_themesmanager();
    }

    private function to_themesmanager()
    {
        redirect(admin_url('themesmanager'));
    }
}


