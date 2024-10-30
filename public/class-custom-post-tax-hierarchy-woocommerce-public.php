<?php

class Custom_Post_Tax_Hierarchy_Woocommerce_Public {

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
	//private $arr_customPostTermSlug = array();
	private $arr_cpt_for_rewrite = array();
	private $shop_base_page = '';
	private $registered_cpts = array();
	private $selected_cpts_for_rewrite = array();
	private $str_option_name = 'cpth_settings_md5';
	private $admin_options = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $registered_post_types) {

		$this->admin_options = get_option('cpth_settings');
		
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->registered_cpts = $registered_post_types;
		$this->shop_base_page = get_option('woocommerce_product_category_slug') ? get_option('woocommerce_product_category_slug') : _x('product-category', 'slug', 'woocommerce');
		$this->cpth_filters_hooks();
		$this->cpth_get_list_of_cpts();
		$this->cpth_check_flush_rewrites();

		$this->selected_cpts_for_rewrite = get_option('cpth_settings');
	}

	private function cpth_filters_hooks() {
		add_filter('generate_rewrite_rules', array(&$this, 'cpth_rewriteRulesForCustomPostTypeAndTax'));
		add_filter('post_type_link', array(&$this, 'cpth_url_link'), 1, 2);
		
		if (!empty($this->admin_options['woocommerce']) && $this->admin_options['woocommerce']) {
			add_filter('generate_rewrite_rules', array(&$this, 'cpth_rewriteRulesForWoocommerce'));
			add_filter('post_type_link', array(&$this, 'cpth_wooCustomPostLink'), 1, 2);
		}

		add_action('save_post', array(&$this, 'cpth_post_save_edit'), 1, 2);
		add_action('edit_post', array(&$this, 'cpth_post_save_edit'), 1, 2);
		add_action('edit_term', array(&$this, 'cpth_edit_term'), 1, 0);

		add_action('wp_footer', array(&$this, 'cpth_add_footer_comment'));
	}

	function cpth_edit_term() {
		$this->cpth_flush_rewrites();
	}

	public function cpth_add_footer_comment() {
		echo "<!-- SEO boosted by : https://wordpress.org/plugins/custom-post-taxonomy-hierarchy-seo/ --> \n";
	}

	public function cpth_getTheTermListModified($id, $taxonomy) {
		$terms = get_the_terms($id, $taxonomy);
		if (is_wp_error($terms))
			return $terms;
		if (empty($terms))
			return false;
		$links = array();
		foreach ($terms as $term) {
			$link = str_replace('product-category', $this->shop_base_page, get_term_link($term, $taxonomy));
			if (is_wp_error($link)) {
				return $link;
			}

			$links[] = str_replace(home_url(), '', $link);
		}

		$current_page_in_array = array();
		foreach ($links as $key => $val) {

			if (stristr($val, $_SERVER['REQUEST_URI'])) {
				$current_page_in_array[] = $val;
			}
		}
		if (!empty($current_page_in_array)) {
			return $current_page_in_array;
		} else {
			return $links;
		}
	}

	/**
	 * Get the slugs for Woocommerce items
	 *
	 * @param post_link	$post_link	The current link
	 * 
	 * @param post	$post	The post object.
	 * 
	 * @return Array of possible links
	 */
	public function cpth_wooCustomPostLink($post_link, $post = array()) {
		if (empty($post)) {
			$post = get_post(get_the_ID());
		}
		if (get_post_type() == 'product') {
			$obj_shop_page = get_post(woocommerce_get_page_id('shop'));
			//$links = get_the_term_list_modified($post->ID, $link_setup[get_post_type($post->ID)]);
			$links = $this->cpth_getTheTermListModified($post->ID, 'product_cat');
			
			if (!empty($links)) {
				//by default wordpress adds "product-category" to it's shop base page slug, we don't want that, replace with the slug of the specified shop page
				$post_link = home_url() . end($links) . $post->post_name . '/';
			} else {
				$post_link = '/' . $obj_shop_page->post_name . '/' . $post->post_name . '/';
			}
		}
		
		return $post_link;
	}

	public function cpth_getTaxonomyHierarchy($term, $custom_tax) {
		//TODO:: this global $arr_termSlug is a mess, fix it dammit
		global $arr_termSlug;
		array_push($arr_termSlug, $term->slug);
		if ($term->parent > 0) {
			$this->cpth_getTaxonomyHierarchy(get_term($term->parent), $custom_tax);
		}
	}

	/**
	 * Generate the rewrite rules for Woocommerce
	 * 
	 * @return Nothing
	 */
	function cpth_rewriteRulesForWoocommerce($wp_rewrite) {
		global $arr_termSlug;
		$custom_post_rules = array();
		$product_categories = get_terms('product_cat');
		foreach ($product_categories as $category) {
			$arr_termSlug = array();
			$this->cpth_getTaxonomyHierarchy($category, 'product');
			$slug = implode('/', array_reverse($arr_termSlug));
			$custom_post_rules['^' . $this->shop_base_page . '/' . $slug . '/?$'] = 'index.php?product_cat=' . $category->slug;
		}
		//------------This code is simply for products that do no have a category, by default wordpress assigns "uncategorized" category and puts it in the slug
		$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1
		);
		$arr_woo_products = new WP_Query($args);
		foreach ($arr_woo_products->posts as $product) {

			$links = $this->cpth_getTheTermListModified($product->ID, 'product_cat');

			if (empty($links)) { //no category
				$custom_post_rules['^' . $obj_shop_page->post_name . '/' . $product->post_name . '/?$'] = 'index.php?product=' . $product->post_name;
			} else {
				foreach ($links as $key => $val) {
					$custom_post_rules['^' . ltrim($val, '/') . $product->post_name . '/?$'] = 'index.php?product=' . $product->post_name;
				}
			}
		}
		//-------------------------------------------------------------------------------------------------------------------------------------------------------

		return $wp_rewrite->rules = $custom_post_rules + $wp_rewrite->rules;
	}

	public function cpth_post_save_edit($post_ID, $post_obj) {
		$this->cpth_flush_rewrites();
	}

	private function cpth_flush_rewrites() {
		update_option($this->str_option_name, array()); //just blank the flush rewrite rule setting so it flushes on next page load
		flush_rewrite_rules();
	}

	private function cpth_check_flush_rewrites() {
		$arr_option_cptmd5 = get_option($this->str_option_name);
		$md5_cpts = md5(json_encode($this->arr_cpt_for_rewrite));
		if (empty($arr_option_cptmd5)) {
			update_option($this->str_option_name, $md5_cpts);
			$this->cpth_flush_rewrites(); //first time this is created, flush rules
		} else {
			if ($arr_option_cptmd5 != $md5_cpts) { //CPT's have changed flush rewrite rules
				update_option($this->str_option_name, $md5_cpts);
				$this->cpth_flush_rewrites();
			}
		}
	}

	private function cpth_get_list_of_cpts() {
		$arr_all_registered_cpts = get_post_types(array(), 'objects');

		$arr_post_taxs = array();
		$options = get_option('cpth_settings');
		if (!empty($options['selected_cpt'])) {
			foreach ($options['selected_cpt'] as $key => $cpt) {
				$arr_post_taxs[$cpt] = $arr_all_registered_cpts[$cpt];
				$arr_post_taxs[$cpt]->taxonomies = array_values(get_object_taxonomies($cpt, 'objects'));
			}
		}
		$this->arr_cpt_for_rewrite = $arr_post_taxs;
	}

	protected function cpth_getTermHierarchy($arr_term, $custom_tax) {
		foreach ($arr_term as $term) {
			array_push($this->arr_customPostTermSlug, $term->slug);
			if ($term->parent > 0) {
				$this->cpth_getTermHierarchy(get_term($term->parent), $custom_tax);
			}
		}
	}

	public function cpth_getSlugsForPostTax($id) {
		$links = array();
		$taxonomy = get_post_taxonomies($id);
		$terms = get_the_terms($id, $taxonomy[0]);

		if (is_wp_error($terms))
			return $terms;
		if (empty($terms))
			return false;

		$this->arr_customPostTermSlug = array(); //reset the hierarchy
		$this->cpth_getTermHierarchy($terms, $taxonomy[0]);

		$terms = array_filter($this->arr_customPostTermSlug);
		foreach ($terms as $term) {
			$links[] = $term;
		}
		return implode('/', array_reverse($links));
	}

	public function cpth_getTermFromCurrentURL($post) {
		$arr_terms = wp_get_object_terms($post->ID, get_post_taxonomies($post), array('fields' => 'all'));
		foreach ($arr_terms as $term) {
			$check = $term->slug;
			if ($term->parent > 0) {
				$parent = get_term($term->parent);
				$check = $parent->slug;
			}
			if (stristr($this->current_url, $check)) {
				return $term;
			}
		}
	}

	public function cpth_rewriteRulesForCustomPostTypeAndTax($wp_rewrite) {
		
		if (empty($this->arr_cpt_for_rewrite)) {
			return;
		}
		
		$tax_rules = array();
		$custom_post_rules = array();

		foreach ($this->arr_cpt_for_rewrite as $post_type) {
			
			$args = array(
					'post_type' => $post_type->name,
					'posts_per_page' => -1
			);

			$custom_post_type_posts = new WP_Query($args);

			foreach ($custom_post_type_posts->posts as $post_key => $post_val) {

				$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->name;

				if (!empty($this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->rewrite['slug'])) {
					$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->rewrite['slug'];
				}

				$arr_slugs = $this->cpth_getSlugsForPostTax($post_val->ID);

				if (!empty($arr_slugs)) {
					foreach ((array) $arr_slugs as $slug_key => $slug_val) {
						$single_post_slug = array();
						$single_post_slug[] = $cpt_base_slug; //replace the old base taxonomy with the new one.
						$single_post_slug[] = $slug_val; //add the post name at the end of the array
						$single_post_slug[] = $post_val->post_name; //add the post name at the end of the array
						$single_post_slug = implode('/', $single_post_slug) . '-' . $post_val->ID;
						$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type->name . '=' . $post_val->post_name;
					}
				} else { //only one slug available, create the rule
					$single_post_slug = $cpt_base_slug . '/' . $post_val->post_name . '-' . $post_val->ID;
					$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type->name . '=' . $post_val->post_name;
				}
			}

			$arr_categories = get_categories(array('type' => $post_type->name, 'taxonomy' => $post_type->taxonomies[0]->name, 'orderby' => 'term_id', 'order' => 'ASC', 'hide_empty' => 0, 'hierarchical' => true));

			$cat_ids = array();
			foreach ($arr_categories as $category) {
				$cat_ids[$category->term_id] = $category;
			}

			foreach ($cat_ids as $cat) {
				if ($cat->category_parent > 0) {
					$cat_ids[$cat->category_parent]->child = $cat;
				}
			}

			foreach ($cat_ids as $cat) {
				if (!empty($cat->child)) {
					$slug = $cat->slug;
					$tax_rules['^' . $cpt_base_slug . '/' . $slug . '/?$'] = 'index.php?' . $cat->taxonomy . '=' . $cat->slug;
					$child = $cat->child;
					do {
						$slug.='/'.$child->slug;
						$tax_rules['^' . $cpt_base_slug . '/' . $slug . '/?$'] = 'index.php?' . $cat->taxonomy . '=' . $cat->slug;
						$child = !empty($child->child) ? $child->child : '';
					} while (!empty($child));
					$tax_rules['^' . $cpt_base_slug . '/' . $slug . '/?$'] = 'index.php?' . $cat->taxonomy . '=' . $cat->slug;
				} else {
					$tax_rules['^' . $cpt_base_slug . '/' . $cat->slug . '/?$'] = 'index.php?' . $cat->taxonomy . '=' . $cat->slug;
				}
			}
		}

		$final_rules = array_merge($custom_post_rules, $tax_rules);
		$wp_rewrite->rules = $final_rules + $wp_rewrite->rules;
	}

	public function cpth_url_link($post_link, $post = NULL) {

		if (empty($this->arr_cpt_for_rewrite[get_post_type($post->ID)])) {
			return $post_link;
		}

		$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post->ID)]->name;

		if (!empty($this->arr_cpt_for_rewrite[get_post_type($post->ID)]->rewrite['slug'])) {
			$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post->ID)]->rewrite['slug'];
		}

		$terms = wp_get_object_terms($post->ID, get_post_taxonomies($post), array('fields' => 'all'));

		if (!empty($terms)) { //add the terms into the url structure
			$this->arr_customPostTermSlug = array(); //re-initialize the array
			$this->cpth_getTermHierarchy($terms, get_post_type($post->ID));

			$slug = implode('/', array_reverse(array_filter($this->arr_customPostTermSlug)));

			$post_link = home_url() . '/' . $cpt_base_slug . '/' . $slug . '/' . $post->post_name . '-' . $post->ID . '/';
		} else { //append post-id to any custom posts not inside a taxonomy
			if (is_a($post, 'WP_Term')) { //this is a term link
				$post_link = '/' . $post->slug . '/';
			} else {
				$post_link = home_url() . '/' . $cpt_base_slug . '/' . $post->post_name . '-' . $post->ID . '/';
			}
		}

		return $post_link;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/custom-post-tax-hierarchy-woocommerce-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/custom-post-tax-hierarchy-woocommerce-public.js', array('jquery'), $this->version, false);
	}

}
