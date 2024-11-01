<?php
/*
Plugin Name: Woocommerce Book Price
Plugin URI: http://openswatch.com
Description: Book Price for product on Woocommerce
Author: anhvnit@gmail.com
Author URI: http://openswatch.com/
Version: 1.0
WC requires at least: 2.6
Text Domain: woo-book-price
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
define('OPENPOS_BOOK_DIR',plugin_dir_path(__FILE__));
define('OPENPOS_BOOK_URL',plugins_url('woo-book-price'));

require(OPENPOS_BOOK_DIR.'vendor/autoload.php');

require_once( OPENPOS_BOOK_DIR.'lib/db.php' );
require_once( OPENPOS_BOOK_DIR.'includes/Book.php' );
register_activation_hook( __FILE__, array( 'OP_Book_Price', 'install' ) );

if(!function_exists('is_plugin_active'))
{
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

global $_has_openpos;
$_has_openpos = false;
if(is_plugin_active( 'woocommerce-openpos/woocommerce-openpos.php' ))
{
    $_has_openpos = true;
}

$_op_book = new OP_Book();
$_op_book->init();
?>