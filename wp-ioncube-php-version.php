<?php
/*
Plugin Name: نمایش نسخه ionCube
Description: نمایش نسخه ionCube Loader در سلامت سایت و صفحه افزونه‌ها با روش‌های جایگزین
Version: 1.3
Author: مهرشاد درزی
*/

defined('ABSPATH') or die('دسترسی مستقیم ممنوع است!');

class Advanced_Ioncube_Info_Plugin {
    
    public function __construct() {
        add_action('admin_notices', array($this, 'show_admin_notice'));
        add_filter('debug_information', array($this, 'add_ioncube_info_to_site_health'));
    }
    
    public function show_admin_notice() {
        $screen = get_current_screen();
        if ($screen->id !== 'plugins' || !current_user_can('manage_options')) {
            return;
        }
        
        $ioncube_info = $this->get_ioncube_info();
        $php_version = phpversion();
        
        $message = sprintf(
            '<strong>اطلاعات سرور:</strong> PHP نسخه %s | ionCube %s',
            esc_html($php_version),
            $ioncube_info['status'] === 'active' 
                ? sprintf('نسخه %s (فعال)', esc_html($ioncube_info['version']))
                : 'غیرفعال'
        );
        
        echo '<div class="notice notice-info is-dismissible"><p>' . $message . '</p></div>';
    }
    
    public function add_ioncube_info_to_site_health($info) {
        $ioncube_info = $this->get_ioncube_info();
        
        $info['ioncube'] = array(
            'label'  => 'ionCube Loader',
            'fields' => array(
                'ioncube_version' => array(
                    'label' => 'نسخه ionCube',
                    'value' => $ioncube_info['status'] === 'active' ? $ioncube_info['version'] : 'غیرفعال',
                    'debug' => $ioncube_info['status'] === 'active' ? $ioncube_info['version'] : 'not_loaded',
                ),
                'ioncube_status' => array(
                    'label' => 'وضعیت ionCube',
                    'value' => $ioncube_info['status'] === 'active' ? 'فعال' : 'غیرفعال',
                ),
            ),
        );
        
        return $info;
    }
    
    private function get_ioncube_info() {
        // روش اول: بررسی از طریق تابع ioncube_loader_version()
        if (function_exists('ioncube_loader_version')) {
            return [
                'status' => 'active',
                'version' => ioncube_loader_version()
            ];
        }
        
        // روش دوم: بررسی از طریق extension_loaded و phpinfo
        if (extension_loaded('ionCube Loader')) {
            ob_start();
            phpinfo(INFO_MODULES);
            $phpinfo = ob_get_clean();
            
            if (preg_match('/ionCube PHP Loader\s+v?([0-9.]+)/', $phpinfo, $matches)) {
                return [
                    'status' => 'active',
                    'version' => $matches[1]
                ];
            }
            
            return [
                'status' => 'active',
                'version' => 'نسخه نامشخص (فعال)'
            ];
        }
        
        // روش سوم: بررسی از طریق php -v (آخرین راهکار)
        $php_cli = shell_exec('php -v 2>&1');
        if (preg_match('/ionCube PHP Loader\s+v?([0-9.]+)/i', $php_cli, $matches)) {
            return [
                'status' => 'active',
                'version' => $matches[1]
            ];
        }
        
        return [
            'status' => 'inactive',
            'version' => 'غیرفعال'
        ];
    }
}

new Advanced_Ioncube_Info_Plugin();