<?php

/*
Plugin Name: Dashboard for Developers
Description: Provides access to the personal dashboard for each developer
Author: Artem Avvakumov
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Version: 0.2.9
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once 'includes.php';


define( 'DDB_VERSION', '0.2.9' );
define( 'DDB_TEXT_DOMAIN', 'developer-dashboard' );

$plugin_root = __FILE__;

register_activation_hook( $plugin_root, array('Ddb_Plugin', 'install' ) );
//register_deactivation_hook( $plugin_root, array('Ddb_Plugin', 'uninstall' ) );

/**** Initialise Plugin ****/

$ddb_plugin = new Ddb_Plugin( $plugin_root );

/**** Initialize report generators ****/

if ( filter_input( INPUT_GET, Ddb_Core::BUTTON_SUMBIT ) == Ddb_Core::ACTION_GENERATE_PAYOUT ) {
  
  // Admin requested to generate CSV report file
  // to send him the file, we need to prepare the file contents and send headers before doing anything else
  add_action('init', array( 'Ddb_Plugin', 'generate_payout_report_for_admin' ) );
}

if ( filter_input( INPUT_POST, Ddb_Core::BUTTON_SUMBIT ) == Ddb_Core::ACTION_GENERATE_REPORT_XLSX ) {
  
  // Admin requested to generate XLS report file

	$save_to_file = filter_input( INPUT_POST, 'save_to_file' );
	
	if ( ! $save_to_file ) { // "save to file" checkbox is not checked, therefore we need to send the file to the browser instead of saving onto the disk
	
		// report will be sent directly to the browser (allowing to download it immediately)
		// to send the file contents to th browser,
		// we need to prepare the file contents and send headers before doing anything else
		
		require_once( 'vendor/xlsxwriter.class.php' );
		add_action('init', array( 'Ddb_Plugin', 'generate_xlsx_sales_report_for_admin' ) );
		
	} else { 
		// need to generate report and save it to disk.
		// in this case, report generation will be handled as usual, by Ddb_Plugin::do_action()
	}
}

if ( filter_input( INPUT_POST, Ddb_Core::BUTTON_SUMBIT ) === Ddb_Frontend::ACTION_DOWNLOAD_ORDERS_REPORT ) {
  
  // Frontend user requested to generate XLS file
  // to send him the file, we need to prepare the file contents and send headers before doing anything else
  require_once( 'vendor/xlsxwriter.class.php' );
  add_action( 'init', array( 'Ddb_Report_Generator', 'validate_and_generate_xlsx_report_for_developer' ) );
}

/**
 * Copy-pasted from:
 * 
 * @snippet       WooCommerce Prevent Duplicate Order
 * @author        Rodolfo Melogli, Business Bloomer
 * @compatible    WooCommerce 9
 * @community     https://businessbloomer.com/club/
 */
 
add_action( 'woocommerce_checkout_process', 'apd_prevent_duplicate_orders' );
 
function apd_prevent_duplicate_orders() {
    $args = [
        'limit' => 1,
        'customer' => $_POST['billing_email'],
        'date_created' => '>' . ( time() - 2.5 * MINUTE_IN_SECONDS ),
        'status' => wc_get_is_paid_statuses(),
        'total' => WC()->cart->get_total( 'edit' ),
        'return' => 'ids',
    ];
    $orders = wc_get_orders( $args );
    if ( $orders ) {
      wc_add_notice( __( 'It looks like you already placed this order recently. Please wait a minute before trying again.' ), 'error' );
    }
}
