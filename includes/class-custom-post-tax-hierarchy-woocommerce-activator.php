<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Custom_Post_Tax_Hierarchy_Woocommerce_Activator extends Custom_Post_Tax_Hierarchy_Woocommerce {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if (parent::check_plugin_status('custom-post-tax-hierarchy.php')) {
			exit('<div class="error"><p><strong>Custom Post Tax Hierarchy</strong> is still active. Please disable it before enabling your premium plugin.</p></div>');
		}
	}

	

}
