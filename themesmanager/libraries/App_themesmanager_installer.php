<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\zip\Unzip;

class App_themesmanager_installer
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * Upload theme
     * @return array
     */
    public function from_upload()
    {
        if (isset($_FILES['theme']) && _perfex_upload_error($_FILES['theme']['error'])) {
            return [
                    'error'   => _perfex_upload_error($_FILES['file']['error']),
                    'success' => false,
            ];
        }

        if (isset($_FILES['theme']['name'])) {
            hooks()->do_action('pre_upload_theme', $_FILES['theme']);

            $response = ['success' => false, 'error' => ''];

            // Get the temp file path
            $uploadedTmpZipPath = $_FILES['theme']['tmp_name'];

            $unzip = new Unzip();

            $themeTemporaryDir = get_temp_dir() . time() . '/';

            try {
                $unzip->extract($uploadedTmpZipPath, $themeTemporaryDir);

                if ($this->check_theme($themeTemporaryDir) === false) {
                    $response['error'] = 'No valid theme is found.';
                } else {
                    $unzip->extract($uploadedTmpZipPath, APP_THEMESMANAGER_PATH);
                    hooks()->do_action('theme_installed', $_FILES['theme']);
                    $response['success'] = true;
                }

                $this->clean_up_dir($themeTemporaryDir);
            } catch (Exception $e) {
                $response['error'] = $e->getMessage();
            }

            return $response;
        }
    }

    public function check_theme($source)
    {
        // Check the folder contains at least 1 valid theme.
        $themes_found = false;

        $files = get_dir_contents($source);

        if ($files) {
            foreach ($files as $file) {
                if (endsWith($file, '.php')) {
                    $info = $this->ci->app_themesmanager->get_headers($file);
                    if (isset($info['theme_name']) && !empty($info['theme_name'])) {
                        $themes_found = true;

                        break;
                    }
                }
            }
        }

        if (!$themes_found) {
            return false;
        }

        return $source;
    }

    private function clean_up_dir($source)
    {
        delete_files($source);
        delete_dir($source);
    }
}
