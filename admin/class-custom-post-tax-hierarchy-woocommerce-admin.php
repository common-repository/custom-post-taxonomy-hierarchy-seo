<?php

class Custom_Post_Tax_Hierarchy_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $registered_cpts = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $registered_post_types) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->registered_cpts = $registered_post_types;

		add_action('admin_menu', array(&$this, 'cpth_add_admin_menu'));
		add_action('admin_init', array(&$this, 'cpth_settings_init'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/custom-post-tax-hierarchy-woocommerce-tax-hierarchy-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/custom-post-tax-hierarchy-woocommerce-admin.js', array('jquery'), $this->version, false);
	}

	public function cpth_add_admin_menu() {
		add_menu_page('Custom Post Tax Hierarchy', 'Custom Post Tax Hierarchy', 'manage_options', 'custom_post_tax_hierarchy', array(&$this, 'cpth_options_page'));
	}

	public function cpth_settings_init() {

		register_setting('cpth_admin_options', 'cpth_settings');

		add_settings_section(
				'cpth_cpth_admin_options_section', __('', 'wordpress'), array(&$this, 'cpth_settings_section_callback'), 'cpth_admin_options'
		);

		add_settings_field(
				'cpth_select_cpt', __('Choose which custom post types to apply SEO friendly URL structures to:', 'wordpress'), array(&$this, 'cpth_select_cpt'), 'cpth_admin_options', 'cpth_cpth_admin_options_section'
		);
	}

	/**
	 * Check if WooCommerce is active. Courtesy of : http://snippet.fm/snippets/check-if-woocommerce-plugin-is-installed-and-activated-on-server-multisite-and-single-installation/
	 *
	 * @return  bool
	 */
	public function cpth_isWoocommerceActive() {
		$active_plugins = ( is_multisite() ) ?
				array_keys(get_site_option('active_sitewide_plugins', array())) :
				apply_filters('active_plugins', get_option('active_plugins', array()));
		foreach ($active_plugins as $active_plugin) {
			$active_plugin = explode('/', $active_plugin);
			if (isset($active_plugin[1]) && 'woocommerce.php' === $active_plugin[1]) {
				return true;
			}
		}
		return false;
	}

	public function cpth_select_cpt() {


		$options['selected_cpt'] = array();
		if (!empty(get_option('cpth_settings'))) {
			$options = get_option('cpth_settings');
		}
		
		?>
		<ul>
			<?php
			if($this->cpth_isWoocommerceActive()){
				?>
			<li><input <?php echo (!empty($options['woocommerce']) && $options['woocommerce']) ? 'checked' : '' ?> type='checkbox' id="<?php echo $key; ?>" name='cpth_settings[woocommerce]' value='1'>Woocommerce Products</li>
			<?php
			}
			
			if (!empty($this->registered_cpts)) {
				foreach ((array) $this->registered_cpts as $key => $value) {
					?>
					<li><input <?php echo in_array($key, $options['selected_cpt'], true) ? 'checked' : '' ?> type='checkbox' id="<?php echo $key; ?>" name='cpth_settings[selected_cpt][]' value='<?php echo $key; ?>'><?php echo $value->label; ?></li>
					<?php
				}
			} else {
				?>
				<li>You don't seem to have any custom post types registered yet.</li>
				<?php
			}
			?>
		</ul>
		<?php
	}

	public function cpth_settings_section_callback() {

		echo __('Settings for Custom Post Type and Taxonomy hierachy', 'wordpress');
	}

	public function cpth_options_page() {
		?>
		<form action='options.php' method='post'>

			<h2>Custom Post Tax Hierarchy</h2>

			<?php
			settings_fields('cpth_admin_options');
			do_settings_sections('cpth_admin_options');
			submit_button();
			?>

		</form>
		<?php
	}

}
