<?php

/*====================================================================================================
 *
 * Load HTML, CSS and JS fields
 *
 *====================================================================================================*/


function custom_css_wrap($atts, $content = null) {
    $result = '<style type="text/css">';
    $result .= do_shortcode($content);
    $result .= '</style>';
    return $result;
}

add_shortcode('css', 'custom_css_wrap');

function custom_js_wrap( $atts, $content = null ) {
    $result = '<script type="text/javascript">';
    $result .= do_shortcode( $content );
    $result .= '</script>';
    return $result;
}

add_shortcode('js', 'custom_js_wrap');


function ccs_safe_eval($code) {
	$strip_tags='<p><br />';
	ob_start();
	eval('?>' . $code);
	$code = ob_get_contents();
	ob_end_clean();
	return $code;
}

function custom_load_script_file($atts) {

	extract( shortcode_atts( array(
		'css' => null, 'js' => null, 'dir' => null,
		'file' => null,'format' => null, 'shortcode' => null,
		'gfonts' => null, 'cache' => 'false',
		'php' => 'true', 'debug' => 'false',
		), $atts ) );

	switch($dir) {
		case 'web' : $dir = "http://"; break;
        case 'site' : $dir = home_url() . '/'; break; /* Site address */
		case 'wordpress' : $dir = get_site_url() . '/'; break; /* WordPress directory */
		case 'content' : $dir = get_site_url() . '/wp-content/'; break;
		case 'layout' : $dir = get_site_url() . '/wp-content/layout/'; break;
		case 'child' : $dir = get_stylesheet_directory_uri() . '/'; break;
		default:

			if(($dir=='theme')||($dir=='template')) {
				$dir = get_template_directory_uri() . '/';
			} else {
				$dir = get_template_directory_uri() . '/';
				if($css != '') {
					$dir .= 'css/';
				}
				if($js != '') {
					$dir .= 'js/';
				}
			}
	}

	if($css != '') {
		echo '<link rel="stylesheet" type="text/css" href="';
		echo $dir . $css;

		if($cache=='false') {

			for ($i=0; $i<8; $i++) { 
				$tail .= rand(0,9) ; 
			} 

			echo '?' . $tail;
		}
		echo '" />';
	}
	if($gfonts != '') {
		echo '<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=';
		echo $gfonts . '" />';
	}
	if($js != '') {
		echo '<script type="text/javascript" src="' . $dir . $js . '"></script>';
	}
	if($file != '') {

		$output = @file_get_contents($dir . $file);

		if($output!='') {
			if(($format == 'on')||($format == 'true')) { // Format?
				$output = wpautop( $output );
			}

			/* Put safe_eval here for executing PHP inside template files */


			if($php=='true') {

				$output = ccs_safe_eval( $output );

			}

			if(($shortcode != 'false')||($shortcode != 'off')) { // Shortcode?
				$output = do_shortcode( $output );
			}
			return $output;
		}
	}
	return null;
}

add_shortcode('load', 'custom_load_script_file');


/** Load CSS field into header **/

add_action('wp_head', 'load_custom_css');
function load_custom_css() {
	global $wp_query;

	$custom_css = get_post_meta( $wp_query->post->ID, "css", $single=true );

/*	if($custom_css == '') { */
		$root_dir_soft = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
		$default_layout_dir = $root_dir_soft . 'wp-content/layout/';
		$default_css = $default_layout_dir . 'style.css';

		if(file_exists($default_css))
			$custom_css .= '[load css="style.css" dir="layout"]';
/*	} */

	$custom_css = do_shortcode( $custom_css );
	if( $custom_css != '' ) {
		echo $custom_css;
	}
}

/** Load JS field into footer **/

add_action('wp_footer', 'load_custom_js');
function load_custom_js() {
	global $wp_query;

	$custom_js = get_post_meta( $wp_query->post->ID, "js", $single=true );

/*	if($custom_js == '') { */

		$root_dir_soft = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
		$default_layout_dir = $root_dir_soft . 'wp-content/layout/';
		$default_js = $default_layout_dir . 'scripts.js';

		if(file_exists($default_js))
			$custom_js .= '[load js="scripts.js" dir="layout"]';
/*	} */

	$custom_js = do_shortcode( $custom_js );
	if( $custom_js != '' ) {
		echo $custom_js;
	}
}

/** Load HTML field instead of content **/

add_action('the_content', 'load_custom_html');
function load_custom_html($content) {
	global $wp_query;
	global $ccs_global_variable;

	if(( $ccs_global_variable['is_loop'] == "false" ) &&
		!is_admin() ) {

		$html_field = get_post_meta( $wp_query->post->ID, "html", $single=true );

		/* Set default layout filename */

		$root_dir_soft = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
		$default_layout_dir = $root_dir_soft . 'wp-content/layout/';
		$default_header = 'header.html';

		$current_post_type = $wp_query->post->post_type;
		$current_post_slug = $wp_query->post->post_name;

		$default_post_type_template = $current_post_type . '.html';
		$default_current_post_type_template = $current_post_type . '-' . $current_post_slug . '.html';

		$default_current_page_template = 'page-' . $current_post_slug . '.html';

		$default_page_template = 'page.html';

		$default_footer = 'footer.html';

		$output = '';

		// Load default header

		if( file_exists( $default_layout_dir . $default_header ) ) {
			$output .= '[load file="'. $default_header . '" dir="layout"]';
		}

		// Load default page template

		if ( $html_field == '' ) {
			if( file_exists( $default_layout_dir . $default_current_post_type_template ) ) {
				$output .= '[load file="'. $default_current_post_type_template . '" dir="layout"]';
			}
			elseif( file_exists( $default_layout_dir . $default_post_type_template ) ) {
				$output .= '[load file="'. $default_post_type_template . '" dir="layout"]';
			}
			elseif( ($current_post_type == 'page') &&
				( file_exists( $default_layout_dir . $default_current_page_template ) ) ) {
					$output .= '[load file="' . $default_current_page_template . '" dir="layout"]';
			}
			elseif( file_exists( $default_layout_dir . $default_page_template ) ) {
				$output .= '[load file="' . $default_page_template . '" dir="layout"]';
			}
		} else {
			$output .= $html_field;
		}

		// Load default footer

		if( file_exists( $default_layout_dir . $default_footer ) ) {
			$output .= '[load file="' . $default_footer . '" dir="layout"]';
		}

		$custom_html = do_shortcode( $output );
		if( $custom_html != '' ) {
			return $custom_html;
		} else {
			return $content;
		}
	}
	return $content;
}

