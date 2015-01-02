<?php
/*
Plugin Name: Penguin Integration
Plugin URI: http://getpenguin.com/
Description: Penguin Integration allows you to easily add Penguin buttons to your site so you can start accepting Apple Pay today. To begin, login on the <a href="./options-general.php?page=penguin-integration" target="_self" >settings</a> page.
Version: 1.0
Author: Penguin
Author URI: http://getpenguin.com
License: GPLv2 or later
*/

/*  Copyright 2014  Michael Ozeryansky  (email : mozer624@gmail.com)

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

if (!class_exists("PenguinIntegration")) {
class PenguinIntegration
{
    var $optionsName  = "penguinIntegrationOptions";
    var $settingPage = "penguin-integration";
    var $options;
    
    
    /*
     *  Setup
     */
     

    function __contructor()
    {
        
    }
    
    function init()
    { 
        // add activation hook
        register_activation_hook(__FILE__, array($this, 'onActivate'));
        register_deactivation_hook(__FILE__, array($this, 'onDeactivate'));
        
        // add shortcode
        add_shortcode('penguin', array($this, 'penguinShortcode'));
        
        // enable shortcode replacement in text widgets
        add_filter('widget_text', 'shortcode_unautop');
        add_filter('widget_text', 'do_shortcode', 11);
        
        // admin setup
        if(is_admin()){
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    function onActivate()
    {
        // set the options to the default
        $defaultOpts = array(
            'email' => ''
        );
    
        update_option($this->optionsName, $defaultOpts);
    }
    
    function onDeactivate()
    {
        // clear options
        update_option($this->optionsName, '');
    }
    
    /*
     *  Shortcode
     */

    function penguinShortcode($atts = '')
    {   
        $defaultAttr = array(
            'productid' => ''
        );
        
        $atts = shortcode_atts($defaultAttr, $atts);
        $atts = array_map('esc_html', $atts);

        $insert = $this->createIFrame($atts);

        return $insert;
    }

    function createIFrame($atts)
    {
        $productId = $atts['productid'];
    
        ob_start();
?>
        <iframe src="http://getpenguin.com/iframe?productId=<?php echo $productId; ?>&wp" width="190px" height="100px"></iframe>
<?php
        $html = trim(ob_get_contents());
        ob_end_clean();
        
        return $html;
    }
    
    /*
     *  Settings
     */

    public function add_admin_menu()
    {
        // This page will be under "Settings"
        add_options_page(
            'Penguin Integration Admin', 
            'Penguin', 
            'manage_options', 
            $this->settingPage, 
            array($this, 'display_admin_page')
        );
    }

    /**
     * Print out the admin page
     */
    public function display_admin_page()
    {
        // get options
        $this->options = get_option($this->optionsName);
        
        // if download form submitted
        if(isset($_POST['penguin_download'])){
            // post variables
            $email = isset($_POST['penguin_email'])?esc_sql($_POST['penguin_email']):'';
            $password = isset($_POST['penguin_password'])?esc_sql($_POST['penguin_password']):'';
            
            // attempt to login
            $auth = $this->loginBusiness($email, $password);
            if(is_wp_error($auth)){
                // error
                $error = 'Download Error: '.$auth->get_error_message();
                
            } else {
                // attempt to get products
                $products = $this->getBusinessProducts($auth);
                if(is_wp_error($products)){
                    // error
                    $error = 'Download Error: '.$products->get_error_message();
                } else {
                    // success
                    // save successful email
                    $this->options['email'] = $email;
                    
                    // save products, only needed info
                    $products_ = array();
                    foreach($products as $product){
                        $product_ = array(
                            'id' => $product['_id'],
                            'name' => $product['name']
                        );
                        $products_[] = $product_;
                    }
                    $this->options['products'] = $products_;
                    
                    // save date of last dowload
                    $this->options['lastdownload'] = date_i18n("F j, Y, g:i a");
                    
                    // update
                    update_option($this->optionsName, $this->options);
                }
            }
        }
        
        // get products for display
        if(isset($this->options['products']) && is_array($this->options['products'])){
            $products = $this->options['products'];
        }
        
        // get stored email is saved
        if(isset($this->options['email'])){
            $email = $this->options['email'];
        }
        
        // get stored email is saved
        if(isset($this->options['lastdownload'])){
            $lastDownload = $this->options['lastdownload'];
        }
        
        require 'settingsPage.php';
    }
    
    /**
     *  Penguin API
     */
    
    // login verifies user if the redirect is '/', false login will be '/login'
    // returns cookies
    private function loginBusiness($email, $password)
    {
        $loginURL = 'http://getpenguin.com/login?wp';
    
        $response = wp_remote_post($loginURL, array(
        	    'body' => array('email' => $email, 'password' => $password),
        	    'redirection' => 0
            )
        );
        
        if(is_wp_error($response)){
            return $response;
        
        } else if(isset($response['headers']['location'])){
            if($response['headers']['location'] == '/') {
                // logged in
                return $response['cookies'];
                
            } else {
                // failure
                return new WP_Error('http_request_failed', __('Invalid username or password'));
            }
        }
        
        // login error
        return new WP_Error('http_request_failed', __('Could not login to Penguin'));
    }
    
    // expects cookie for a logged in business
    private function getBusinessProducts($auth)
    {
        $productsURL = 'http://getpenguin.com/api/product/all?wp';
    
        $response = wp_remote_get($productsURL, array(
        	    'cookies' => $auth,
        	    'redirection' => 0
            )
        );
        
        if(is_wp_error($response)){
            return $response;
        
        } else if(isset($response['body'])){
            // decode the json response
            $products = json_decode($response['body'], true);
            
            if($products === NULL){
                // invalid json response
                return new WP_Error('http_request_failed', __('Could not download products, invalid data.'));
            }
            
            return $products;
        }
        
        // login error
        return new WP_Error('http_request_failed', __('Could not download products'));
    }
}

/*
 *  Setup
 */

$penguin = new PenguinIntegration();
$penguin->init();

}

?>
