=== Plugin Name ===
Contributors: bukssaayman
Donate link: https://bukssaayman.co.za/
Tags: seo, custom post, taxonomy, term, custom taxonomy term hierarchy, url, slug
Requires at least: 3.0.1
Tested up to: 4.8
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to squeeze some more SEO juice out of your website by adding your custom post type's taxonomies in your URL structure.

== Description ==

If your site has Custom Post Types and Custom Taxonomies, this plugin will allow you to select which Custom Post Types to include the Custom Taxonomies in the URL structure for. 

We are now offering Woocomerce support as well. Your products URL structure can include the list of nested categories.

For example:

If you have a custom post type called "Projects" and a taxonomy for "Project Types".  

This plugin will give you:
`http://domain.com/projects/project-type/project-slug/`

Instead of the default:
`http://domain.com/projects/project-slug/`

If your Woocommerce product "Woo Ninja" was categorised under: cloting >> hoodies

This plugin will give you:
`http://domain.com/shop/clothing/hoodies/woo-ninja/`

Instead of the default:
`http://domain.com/shop/woo-ninja/`

== Installation ==

Either install the plugin the traditional way through your Admin dashboard's Plugins screen, or you can :

1. Upload the plugin files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. In WP-admin, look for `Custom Post Tax Hierarchy` in your left-hand sidebar menu. There you must select which Custom Post Types for this to apply to.

Go to your custom post type posts, either in your Admin dashboard, or the front-end of your website, and look at the URL, it now includes all your custom taxonomy terms.

== Frequently Asked Questions ==
= What if I don't have any custom post types or taxonomies yet? =
If you want a GUI interface through which to create your custom post types and taxonomies, I would recommend you use this plugin : <https://wordpress.org/plugins/pods/>. 
Alternatively, if you are familiar with PHP, you could just follow the follow the Codex examples : <https://codex.wordpress.org/Post_Types>


= What if I enable this plugin and all my posts are broken? =
All this plugin does is create rewrite rules that maps your URL back to an actual post. If you find that after you've enabled this plugin, things go horribly wrong, simply disable the plugin and create a support request here.

= Will this work for Woocommerce? =
Yes it definitely will! We have added Woocommerce support to our FREE plugin.

= Why do my URL's now suddenly have a numerical suffix e.g /name-of-post-1234/ =
That's a really good question. The answer is a bit technical. Becuase you could technically have a post and a category with exactly the same URL, we've had to add a way of differentiating between the two. For example you could have `domain.com/amazing-pictures/` which could be a category, but `domain.com/amazing-pictures/` could also be a single post. In WordPress they're treated the same, but we need to know which is which. Therefore, your post will be suffixed by the unique post identifier. This is the industry standard way of doing it and is completely SEO safe.

== Screenshots ==

1. In your wp-admin dashboard, look for these settings. There you choose the custom post types you want to have the slug include the taxonomies for.

2. Example of what you can expect your URL structure to be.

== Changelog ==

= 1.0.3 =
* I forgot to flush permalinks after editing a taxonomy term.

= 1.0.2 =
* Fixing bug reported by @rbertrand. The plain taxonomy archive pages didn't work. I added the rewrite rules for them.

= 1.0.1 =
* First bug fixes to make admin custom post type selection take effect.

= 1.0 =
* Initial release, have fun :-)
