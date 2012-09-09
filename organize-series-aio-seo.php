<?php
/*
Plugin Name: Organize Series AIO SEO Integration
Description: This addon integrates Organize Series with the <a href="http://wordpress.org/extend/plugins/all-in-one-seo-pack/">All In One SEO pack</a> by <a href="http://twitter.com/michaeltorbert/">Michael Torbert</a>.  
Version: 1.3
Author: Darren Ethier
Author URI: http://organizeseries.com
*/

/* LICENSE */
//"Organize Series Plugin" and all addons for it created by this author are copyright (c) 2007-2012 Darren Ethier. This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
//
//It goes without saying that this is a plugin for WordPress and I have no interest in developing it for other platforms so please, don't ask ;).

//Automatic Upgrades stuff
if ( file_exists(WP_PLUGIN_DIR . '/organize-series/inc/pue-client.php') ) {
	//let's get the client api key for updates
	$series_settings = get_option('org_series_options');
	$api_key = $series_settings['orgseries_api'];
	$host_server_url = 'http://organizeseries.com';
	$plugin_slug = 'organize-series-aio-seo';
	$options = array(
		'apikey' => $api_key,
		'lang_domain' => 'organize-series'
	);
	
	require( WP_PLUGIN_DIR . '/organize-series/inc/pue-client.php' );
	$check_for_updates = new PluginUpdateEngineChecker($host_server_url, $plugin_slug, $options);
}

/* 
* ALL_INITS_ACTIONS 
*/

// ALWAYS CHECK TO MAKE SURE ORGANIZE SERIES IS RUNNING FIRST //
add_action('plugins_loaded', 'orgseries_check_organize_series_aio_seo');

//load localization
add_action('init', 'orgseries_aio_seo_register_textdomain');

//other inits
add_action('admin_init', 'orgseries_aio_seo_settings_setup');
add_filter('wp_title', 'add_aio_seo_series_title');
add_action('wp_head', 'add_aio_seo_series_meta');
add_filter('aioseop_keywords', 'add_aio_seo_series_keywords');
add_action('get_header', 'maybe_remove_aio_wp_head');


/*
* ALL FUNCTIONS
*/
function orgseries_aio_seo_register_textdomain() {
	$dir = basename(dirname(__FILE__)).'/lang';
	load_plugin_textdomain('organize-series-aio-seo', false, $dir);
}

function orgseries_check_organize_series_aio_seo() {
	if ( !class_exists('orgSeries') ) {
		add_action('admin_notices', 'orgseries_organize_series_aio_seo_warning');
	}
	
	if ( !class_exists('All_in_One_SEO_Pack')) {
		add_action('admin_notices', 'orgseries_aio_seo_warning');
	}
	
	if ( !class_exists('orgSeries') || !class_exists('All_in_One_SEO_Pack') ) {
		add_action('admin_notices', 'orgseries_organize_series_aio_seo_deactivate');
	}
}

function orgseries_organize_series_aio_seo_deactivate() {
	deactivate_plugins('organize-series-aio-seo/organize-series-aio-seo.php', true);
}

function orgseries_organize_series_aio_seo_warning() {
	$msg = '<div id="wpp-message" class="error fade"><p>'.__('The <strong>All in One SEO</strong> addon for Organize Series requires the Organize Series plugin to be installed and activated in order to work.  The addon won\'t activate until this condition is met.', 'organize-series-aio-seo').'</p></div>';
	echo $msg;
}

function orgseries_aio_seo_warning() {
	$msg = '<div id="wpp-message" class="error fade"><p>'.__('The <strong>All in One SEO</strong> addon for Organize Series requires the <a href="http://wordpress.org/extend/plugins/all-in-one-seo-pack/">All In One SEO pack</a> plugin to be installed and activated in order to work.  The addon won\'t activate until this condition is met.', 'organize-series-aio-seo').'</p></div>';
	echo $msg;
}

//hooking into organize series options
function orgseries_aio_seo_settings_setup() {
	add_settings_field('orgseries_aio_seo_settings','Integration with AIO SEO settings','orgseries_aio_seo_output', 'orgseries_options_page','series_automation_settings');
	register_setting('orgseries_options', 'org_series_options');
	add_filter('orgseries_options', 'orgseries_aio_seo_options_validate', 10, 2);
}

function orgseries_aio_seo_options_validate($newinput, $input) {
	$newinput['post_series_format'] = trim(stripslashes( $input['post_series_format']));
	$newinput['use_series_for_meta_keywords'] = ( $input['use_series_for_meta_keywords'] == 1 ? 1 : 0 );
	$newinput['use_no_index_series_archives'] = ( $input['use_no_index_series_archives'] == 1 ? 1 : 0 );
	return $newinput;
}

function orgseries_aio_seo_output() {
	$org_opt = $orgseries->settings;
	$org_name = 'org_series_options';
	//setup defaults for initial activation
	$org_opt['post_series_format'] = !isset($org_opt['post_series_format'] ) ? '' : $org_opt['post_series_format'];
	$org_opt['use_no_index_series_archives'] = !isset($org_opt['use_no_index_series_archives'] ) ? 0 : $org_opt['use_no_index_series_archives'];
	$org_opt['use_series_for_meta_keywords'] = !isset($org_opt['use_series_for_meta_keywords'] ) ? 0 : $org_opt['use_series_for_meta_keywords'];
	?>
		<p><strong><?php _e('Post Series Format: ', 'organize-series-aio-seo' ); ?></strong><input name="<?php echo $org_name; ?>[post_series_format]" id="post_series_format" type="text" value="<?php echo htmlspecialchars($org_opt['post_series_format']); ?>" style="width:300px;" /> <br />
		<small><em><?php _e("The following macros are supported: <ul><li>%blog_title% - Your blog title (sometimes included by default by your theme)</li><li>%blog_description% - Your blog description</li><li>%series_seo_title% - The original title of the series</li><li>%series_seo_description% - The description of the series</li></ul>", 'organize-series-aio-seo'); ?></em></small></p>
		
		<p><input name="<?php echo $org_name; ?>[use_no_index_series_archives]" id="use_no_index_series_archives" type="checkbox" value="1" <?php checked('1', $org_opt['use_no_index_series_archives']); ?> /> <?php _e('Use noindex for Series Archive Pages', 'organize-series-aio-seo'); ?><br />
		<small><em><?php _e('Check this for excluding series archive pages from being crawled. Useful for avoiding duplicate content.', 'organize-series-aio-seo'); ?></em></small></p>
		
		<p><input name="<?php echo $org_name; ?>[use_series_for_meta_keywords]" id="use_series_for_meta_keywords" type="checkbox" value="1" <?php checked('1', $org_opt['use_series_for_meta_keywords']); ?> /> <?php _e('Use Series for META keywords', 'organize-series-aio-seo'); ?><br />
		<small><em><?php _e('Check this if you want your series for a given post used as the META keywords for this post (in addition to any keywords and tags you specify on the post edit page).', 'organize-series-aio-seo'); ?></em></small></p>
	<?php
}

//functions for hooking into aio-seo
function add_aio_seo_series_title( $title ) {
	global $orgseries, $aiosp;
	if ( is_series() && !is_feed() ) {
		$settings = $orgseries->settings;
		$title_format = $settings['post_series_format'];
		$title = str_replace('%series_seo_title%', single_series_title('', false), $title_format);
		$title = str_replace('%blog_title%', get_bloginfo('name'), $title);
		$title = str_replace('%blog_description%', get_bloginfo('description'), $title);
		$title = str_replace('%series_seo_description%', series_description(), $title);
		$title = $aiosp->paged_title($title);
	}
	return $title;			
}

function add_aio_seo_series_meta() {
	global $orgseries, $aioseop_options, $aiosp, $wp_query;
	$haspost = count($wp_query->posts) > 0;
	$settings = $orgseries->settings;
	$no_index = ($settings['use_no_index_series_archives']);
	if ( is_series() && !is_feed() ) {
		$meta = '';
		$description = series_description();
		$description = trim(strip_tags($description));
		$meta .= "<meta name=\"description\" content=\"".$description."\" />\n";
		if ( $no_index ) 
			$meta .="<meta name=\"robots\" content=\"noindex, follow\" />\n";
		if ( $aioseop_options['aiosp_can'] == 'on') {
			$link = '';
			if ( $haspost ) {
				$link = get_series_link();
				$link = $aiosp->yoast_get_paged($link);
			}
			if ( !empty($link) ) {
				$link = apply_filters('aioseop_canonical_url', $link);
				$meta .= "<link rel=\"canonical\" href=\"".$link."\" />\n";
			}
				
		}
		echo $meta;
	}
}

function add_aio_seo_series_keywords($keywords) {
	global $orgseries;
	// NOTE: this is already set up for if a post ends up belonging to multiple series (which may be an addon in the future).
	if ( in_series() && !is_feed() ) {
		$keywords_arr = explode(',',$keywords);
		$settings = $orgseries->settings;
		if ( $settings['use_series_for_meta_keywords'] ) {
			$series = get_the_series();
			if ( !empty($series) ) {
				foreach ($series as $ser) {
					$keywords_arr[] = $ser->name;
				}
			}
		}
	
		if ( count($keywords_arr) <= 1 ) {
			$keywords = implode('',$keywords_arr);
		} else {
			$keywords = implode(',',$keywords_arr);
		}
	}
	
	return $keywords;
}

function maybe_remove_aio_wp_head() {
	global $aiosp, $orgseries;
	if ( is_series() ) {
		remove_filter('wp_title', array($orgseries, 'add_series_wp_title'));
		remove_action('wp_head', array($aiosp, 'wp_head'));
		remove_action('template_redirect', array($aiosp, 'template_redirect'));
	}
}