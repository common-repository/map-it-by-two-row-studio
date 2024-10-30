<?php
/**
 * Plugin Name:     Map it! by Two Row Studio
 * Plugin URI:      https://tworowstudio.com/mapit
 * Description:     A plugin to allow you to create customized Google maps on your WordPress site
 * Author:          Two Row Studio
 * Author URI:      https://tworowstudio.com
 * Text Domain:     trs_mapit
 * Domain Path:     /languages
 * Version:         1.0.7
 *
 * @package         Trs_mapit
 */



defined('ABSPATH') or die('Your father was a hamster and your father smelt of elderberries!');

define('TRMI_NAME', 'trs_mapit');

define ('TRMI_PLUGINDIR',trailingslashit( plugin_dir_path(__FILE__) ));
define ('TRMI_PLUGINURL',trailingslashit( plugin_dir_url(__FILE__) ));

/* Version Control and compatibility defintions */
define('TRMI_VERSION','1.0');
define('TRMI_DB_VERSION','0.1');


register_activation_hook( __FILE__, 'trmi_install' );
register_deactivation_hook(__FILE__, 'trmi_deactivate' );
register_uninstall_hook( __FILE__, 'trmi_uninstall' );


require_once TRMI_PLUGINDIR.'functions/init.php';
