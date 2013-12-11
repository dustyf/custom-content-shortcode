<?php

/*====================================================================================================
 *
 * Query loop shortcode
 *
 *====================================================================================================*/


class Loop_Shortcode {

	function __construct() {
		add_action( 'init', array( &$this, 'register' ) );
	}

	function register() {
		add_shortcode( 'loop', array( &$this, 'simple_query_shortcode' ) );
		add_shortcode( 'pass', array( &$this, 'simple_query_shortcode' ) );
	}

	function simple_query_shortcode( $atts, $template = null, $shortcode_name ) {

		global $ccs_global_variable;
		global $sort_posts;
		global $sort_key;

		$ccs_global_variable['is_loop'] = "true";
		$ccs_global_variable['current_gallery_name'] = '';
		$ccs_global_variable['current_gallery_id'] = '';
		$ccs_global_variable['is_gallery_loop'] = "false";
		$ccs_global_variable['is_attachment_loop'] = "false";
		$ccs_global_variable['is_repeater_loop'] = "false";

		if( ! is_array( $atts ) ) return;

		// non-wp_query arguments
		$args = array(
			'type' => '',
			'category' => '',
			'count' => '',
			'content_limit' => 0,
			'thumbnail_size' => 'thumbnail',
			'posts_separator' => '',
			'gallery' => '',
			'acf_gallery' => '',
			'id' => '',
			'name' => '',
			'field' => '', 'value' => '', 'compare' => '',
			'f' => '', 'v' => '', 'c' => '', 
			'field_2' => '', 'value_2' => '', 'compare_2' => '', 'relation' => '',
			'f2' => '', 'v2' => '', 'c2' => '', 'r' => '', 
			'repeater' => '',
			'x' => '',
			'taxonomy' => '', 'tax' => '', 'value' => '',
			'orderby' => '', 'keyname' => '', 'order' => '',
			'series' => '', 'key' => '',
			'post_offset' => '', 'offset' => '',
			'strip_tags' => '', 'strip' => '',
			'title' => '', 'if' => '',
			'variable' => '', 'var' => '',
			'year' => '', 'month' => '', 'day' => '',
		);

		$all_args = shortcode_atts( $args , $atts, true );
		extract( $all_args );


		/*---------------
		 * Parameters
		 *-------------*/


		$custom_value = $value;
		if($key!='') $keyname=$key;
		if($offset!='') $post_offset=$offset;
		if($strip!='') $strip_tags=$strip;
		if($strip_tags=='true')
			$strip_tags='<p><br />';
		$current_name = $name;
		if ($var!='') $variable=$var;


		/*
		 * Meta query parameters
		 */

			if($f!='')
				$field = $f;
			if($v!='')
				$value = $v;
			if($c!='')
				$compare = $c;
			if($f2!='')
				$field_2 = $f2;
			if($v!='')
				$value_2 = $v2;
			if($c!='')
				$compare_2 = $c2;
			if($r!='')
				$relation = $r;


		if(( $field != 'gallery' ) && ($shortcode_name != 'pass') && ($value!='')) {

			$query_field = $field;
			$query_value = $value;

		} else
			$custom_field = $field;


		if($x != '') { // Simple loop without query

			$output = array();
			ob_start();

			while($x > 0) {
				echo do_shortcode($template);
				$x--;
			}
			$ccs_global_variable['is_loop'] = "false";
			return ob_get_clean();
		}

		$query = array_merge( $atts, $all_args );

		// filter out non-wpquery arguments
		foreach( $args as $key => $value ) {
			unset( $query[$key] );
		}


		/*----------------
		 * Alter query
		 *---------------*/


		if( $category != '' ) {
			$query['category_name'] = $category;
		}
		if( $count != '' ) {
			$query['posts_per_page'] = $count;
		} else {

			if($post_offset!='')
				$query['posts_per_page'] = '9999'; // Show all posts (to make offset work)
			else
				$query['posts_per_page'] = '-1'; // Show all posts (normal method)

		}

		if($post_offset!='')
			$query['offset'] = $post_offset;

		if( $id != '' ) {
			$query['p'] = $id; $query['post_type'] = "any";
		} else {
			if( $current_name != '') {

				// Get ID from post slug

				$query['name']=$current_name; $query['post_type'] = "any";
				$ccs_global_variable['current_gallery_name'] = $current_name;
				$posts = get_posts( $query );
				if( $posts ) { $ccs_global_variable['current_gallery_id'] = $posts[0]->ID;
				}
			} else {

				// Current post

				$query['p'] = get_the_ID(); $query['post_type'] = "any";
			}
		}

		if(( $custom_field == 'gallery' ) && ($shortcode_name != 'pass') ){
			$gallery = 'true';
		}


		if( $type == '' ) {
			$query['post_type'] = 'any';
		} else {
			$query['post_type'] = $type;
			if( $custom_field != 'gallery' ) {
				$query['p'] = '';
			}
		}

// Query by date


		if ( ($year!='') || ($month!='') || ($day!='') ) {

			$today = getdate();
			if ($year=='today') $year=$today["year"];
			if ($month=='today') $month=$today["mon"];
			if ($day=='today') $day=$today["mday"];

			$query['date_query'] = array(

					array(
						'year' => $year,
						'month' => $month,
						'day' => $day,
					)
				);
		}

// Custom taxonomy query

		if($tax!='') $taxonomy=$tax;
		if($taxonomy!='') {

			$query['tax_query'] = array (
					array(
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => array($custom_value),
					)
				);
		}


		if($order!='')
			$query['order'] = $order;

// Orderby

		if( $orderby != '') {
				$query['orderby'] = $orderby;
				if(in_array($orderby, array('meta_value', 'meta_value_num') )) {
					$query['meta_key'] = $keyname;
				}
				if($order=='') {
					if($orderby=='meta_value_num')
						$query['order'] = 'ASC';	
					else
						$query['order'] = 'DESC';
				}				
		}

// Get posts in a series

		if($series!='') {

//			Expand range: 1-3 -> 1,2,3

		/* PHP 5.3+
			$series = preg_replace_callback('/(\d+)-(\d+)/', function($m) {
			    return implode(',', range($m[1], $m[2]));
			}, $series);
		*/

		/* Compatible with older versions of PHP */

			$callback = create_function('$m', 'return implode(\',\', range($m[1], $m[2]));');
			$series = preg_replace_callback('/(\d+)-(\d+)/', $callback, $series);

			$sort_posts = explode(',', $series);

			$sort_key = $keyname;

				$query['meta_query'] = array(
						array(
							'key' => $keyname,
							'value' => $sort_posts,
							'compare' => 'IN'
						)
					);

		}


		/*---------------------
		 * Custom field query
		 *--------------------*/


			if( ($query_field!='') && ($query_value!='') ) {

				$compare = strtoupper($compare);
				switch ($compare) {
					case '':
					case 'EQUAL': $compare = 'LIKE'; break;
					case 'NOT EQUAL': $compare = 'NOT LIKE'; break;
					default: break;
				}

				$query['meta_query'][] =
					array(
							'key' => $query_field,
							'value' => $query_value,
							'compare' => $compare
					);

				if( ($field_2!='') && ($value_2!='') ) {

					if($relation!='')
						$query['meta_query']['relation'] = $relation;
					else
						$query['meta_query']['relation'] = 'AND';

					$compare_2 = strtoupper($compare_2);
					switch ($compare_2) {
						case '':
						case 'EQUAL': $compare_2 = 'LIKE'; break;
						case 'NOT EQUAL': $compare_2 = 'NOT LIKE'; break;
						default: break;
					}

					$query['meta_query'][] =
						array(
							'key' => $field_2,
							'value' => $value_2,
							'compare' => $compare_2
					);
				}


			}

	/*--------------
	 * Main loop
	 *-------------*/

	if( ( $gallery!="true" ) && ( $type != "attachment") ) {

		if( $custom_field == "gallery" ) {
			$custom_field = "_custom_gallery";
		}

		$output = array();
		ob_start();
		$posts = new WP_Query( $query );

// Re-order by series

		if($series!='') {

			usort($posts->posts, "series_orderby_key");

		}

		$total_comment_count = 0;

		// For each post found

		if( $posts->have_posts() ) : while( $posts->have_posts() ) : $posts->the_post();

/*********
 * Repeater field
 */

			if($repeater != '') {
				$ccs_global_variable['is_repeater_loop'] = "true";
				$ccs_global_variable['current_loop_id'] = get_the_ID();

				if( function_exists('get_field') ) {

					if( get_field($repeater, $ccs_global_variable['current_loop_id']) ) { // If the field exists

						$count=1;

						while( has_sub_field($repeater) ) : // For each row

						// Pass details onto content shortcode

						$keywords = apply_filters( 'query_shortcode_keywords', array(
							'ROW' => $count,
						) );
						$ccs_global_variable['current_row'] = $count;
						$output[] = do_shortcode($this->get_block_template( $template, $keywords ));
						$count++;
						endwhile;
					}
				}

				$ccs_global_variable['is_repeater_loop'] = "false";
			} else {


/*********
 * ACF Gallery field
 */

			if($acf_gallery != '') {
				$ccs_global_variable['is_acf_gallery_loop'] = "true";
				$ccs_global_variable['current_loop_id'] = get_the_ID();

				if( function_exists('get_field') ) {

					$images = get_field($acf_gallery, get_the_ID());
					if( $images ) { // If images exist

						$count=1;

						$ccs_global_variable['current_image_ids'] = implode(',', get_field($acf_gallery, get_the_ID(), false));

						if($shortcode_name == 'pass') {

							// Pass details onto content shortcode

							$keywords = apply_filters( 'query_shortcode_keywords', array(
								'FIELD' => $ccs_global_variable['current_image_ids'],
							) );
							$output[] = do_shortcode($this->get_block_template( $template, $keywords ));
							
						} else { // For each image

							foreach( $images as $image ) :

							$ccs_global_variable['current_row'] = $count;
							$ccs_global_variable['current_image'] = '<img src="' . $image['sizes']['large'] . '">';
							$ccs_global_variable['current_image_id'] = $image['id'];
							$ccs_global_variable['current_attachment_id'] = $image['id'];
							$ccs_global_variable['current_image_url'] = $image['url'];
							$ccs_global_variable['current_image_title'] = $image['title'];
							$ccs_global_variable['current_image_caption'] = $image['caption'];
							$ccs_global_variable['current_image_description'] = $image['description'];
							$ccs_global_variable['current_image_thumb'] = '<img src="' . $image['sizes']['thumbnail'] . '">';
							$ccs_global_variable['current_image_thumb_url'] = $image['sizes']['thumbnail'];
							$ccs_global_variable['current_image_alt'] = $image['alt'];

							$output[] = do_shortcode($template);
							$count++;
							endforeach;
						} // End for each image
					}
				}

				$ccs_global_variable['is_acf_gallery_loop'] = "false";
			} else {

			// Not gallery field

			// Attachments?

			if($custom_field == "attachment") {
				$attachments =& get_children( array(
					'post_parent' => get_the_ID(),
					'post_type' => 'attachment',
				) );
				if( empty($attachments) ) {
					$custom_field_content = null; $attachment_ids = null;
				} else {
					$attachment_ids = '';
					foreach( $attachments as $attachment_id => $attachment) {
						$attachment_ids .= $attachment_id . ",";
					}
					$attachment_ids = trim($attachment_ids, ",");
					$custom_field_content = $attachment_ids;
				}
			} else {

			// Normal custom fields

				$custom_field_content = get_post_meta( get_the_ID(), $custom_field, $single=true );
				$attachment_ids = get_post_meta( get_the_ID(), '_custom_gallery', true );
			}

			$keywords = apply_filters( 'query_shortcode_keywords', array(
				'QUERY' => serialize($query), // DEBUG purpose
				'URL' => get_permalink(),
				'ID' => get_the_ID(),
				'TITLE' => get_the_title(),
				'AUTHOR' => get_the_author(),
				'AUTHOR_URL' => get_author_posts_url( get_the_author_meta( 'ID' ) ),
				'DATE' => get_the_date(),
				'THUMBNAIL' => get_the_post_thumbnail( null, $thumbnail_size ),
				'THUMBNAIL_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
				'CONTENT' => ( $content_limit ) ? wp_trim_words( get_the_content(), $content_limit ) : get_the_content(),
				'EXCERPT' => get_the_excerpt(),
				'COMMENT_COUNT' => get_comments_number(),
				'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
				'IMAGE' => get_the_post_thumbnail(),
				'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),
				'FIELD' => $custom_field_content,
				'VAR' => $variable,
				'VARIABLE' => $variable,
				'IDS' => $attachment_ids,
			) );

			$total_comment_count += get_comments_number();

			if ( ( $title == '' ) || ( strtolower($title) == strtolower(get_the_title()) ) ) {

				if($strip_tags!='')
					$output[] = do_shortcode(
						strip_tags($this->get_block_template( $template, $keywords ), $strip_tags)
					);
				else
					$output[] = do_shortcode($this->get_block_template( $template, $keywords ));
				} // End of not gallery field

			}

		} // End of not repeater

		endwhile; endif; // End loop for each post

		wp_reset_query();
		wp_reset_postdata();

		if ($if=='') {
			echo implode( $posts_separator, $output );
		} else {
			if ( ($if=='all-no-comments') && ($total_comment_count==0) ) {
				echo $output[0];
			}
		}

		$ccs_global_variable['is_loop'] = "false";
		return ob_get_clean();

	} else {

// Loop for attachments

		if( $type == 'attachment' ) {

			$output = array();
			ob_start();

			if($category == '') {
				$posts =& get_children( array (
				'post_parent' => get_the_ID(),
				'post_type' => 'attachment',
				'post_status' => 'any'
				) );

				foreach( $posts as $attachment_id => $attachment ) {
					$attachment_ids .= $attachment_id . " ";
				}

			} else { // Fetch posts by category, then attachments

				$my_query = new WP_Query( array(
			    	'cat' => get_category_by_slug($category)->term_id, 
					'post_type' => 'any',
				));
				if( $my_query->have_posts() ) {
					$posts = array('');
					while ( $my_query->have_posts() ) {
						$my_query->the_post();

						$new_children =& get_children( array (
							'post_parent' => get_the_ID(),
							'post_type' => 'attachment',
							'post_status' => 'any'
						) );

						foreach( $new_children as $attachment_id => $attachment ) {
							$attachment_ids .= $attachment_id . " ";
						}
					}
				}
			} // End fetch attachments by category

			if( empty($posts) ) {
				$output = null;
			} else {

				$attachment_ids = explode(" ", trim( $attachment_ids ) );

				if ( $attachment_ids ) { 

					$ccs_global_variable['is_attachment_loop'] = "true";

					foreach ( $attachment_ids as $attachment_id ) {
					// get original image

						$ccs_global_variable['current_attachment_id'] = $attachment_id;

						$image_link	= wp_get_attachment_image_src( $attachment_id, "full" );
						$image_link	= $image_link[0];	
										
						$ccs_global_variable['current_image'] = wp_get_attachment_image( $attachment_id, "full" );
						$ccs_global_variable['current_image_url'] = $image_link;
						$ccs_global_variable['current_image_thumb'] = wp_get_attachment_image( $attachment_id, 'thumbnail', '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
						$ccs_global_variable['current_image_thumb_url'] = wp_get_attachment_thumb_url( $attachment_id, 'thumbnail' ) ;
						$ccs_global_variable['current_image_caption'] = get_post( $attachment_id )->post_excerpt ? get_post( $attachment_id )->post_excerpt : '';
						$ccs_global_variable['current_image_title'] = get_post( $attachment_id )->post_title;
						$ccs_global_variable['current_image_description'] = get_post( $attachment_id )->post_content;
						$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

						$ccs_global_variable['current_image_ids'] = implode(" ", $attachment_ids);
						$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];

			$keywords = apply_filters( 'query_shortcode_keywords', array(
				'URL' => get_permalink( $attachment_id ),
				'ID' => $attachment_id,
				'TITLE' => get_post( $attachment_id )->post_title,
				'CONTENT' => get_post( $attachment_id )->post_content,
				'CAPTION' => get_post( $attachment_id )->post_excerpt,
				'DESCRIPTION' => get_post( $attachment_id )->post_content,
				'IMAGE' => $ccs_global_variable['current_image'],
				'IMAGE_URL' => $ccs_global_variable['current_image_url'],
				'ALT' => $ccs_global_variable['current_image_alt'],
				'THUMBNAIL' => $ccs_global_variable['current_image_thumb'],
				'THUMBNAIL_URL' => $ccs_global_variable['current_image_thumb_url'],
				'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
				'FIELD' => get_post_meta( get_the_ID(), $custom_field, $single=true ),
				'IDS' => get_post_meta( get_the_ID(), '_custom_gallery', true ),
			) );

						$output[] = do_shortcode(custom_clean_shortcodes($this->get_block_template( $template, $keywords ) ) );
					} /** End for each attachment **/
				}
				$ccs_global_variable['is_attachment_loop'] = "false";
				wp_reset_query();
				wp_reset_postdata();

				echo implode( $posts_separator, $output );
				$ccs_global_variable['is_loop'] = "false";
				return ob_get_clean();
			}
		} // End type="attachment"

		else {

			/*********************
			 *
			 * Gallery Loop
			 *
			 */

		if( function_exists('custom_gallery_get_image_ids') ) {

			$output = array();
			ob_start();

			if($ccs_global_variable['current_gallery_id'] == '') {
				$ccs_global_variable['current_gallery_id'] = get_the_ID();
			}
			$posts = new WP_Query( $query );
			$attachment_ids = custom_gallery_get_image_ids();

			if ( $attachment_ids ) { 
				$has_gallery_images = get_post_meta( $ccs_global_variable['current_gallery_id'], '_custom_gallery', true );
				if ( !$has_gallery_images ) {
					$ccs_global_variable['is_loop'] = "false";
					return;
				}
				// convert string into array
				$has_gallery_images = explode( ',', get_post_meta( $ccs_global_variable['current_gallery_id'], '_custom_gallery', true ) );

				// clean the array (remove empty values)
				$has_gallery_images = array_filter( $has_gallery_images );

				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ), 'feature' );
				$image_title = esc_attr( get_the_title( get_post_thumbnail_id( $ccs_global_variable['current_gallery_id'] ) ) );

				$ccs_global_variable['is_gallery_loop'] = "true";

				foreach ( $attachment_ids as $attachment_id ) {

					$ccs_global_variable['current_attachment_id'] = $attachment_id;

					// get original image
					$image_link	= wp_get_attachment_image_src( $attachment_id, 'full' );
					$image_link	= $image_link[0];	
										
					$ccs_global_variable['current_image']=wp_get_attachment_image( $attachment_id, 'full' );
					$ccs_global_variable['current_image_url']=$image_link;
					$ccs_global_variable['current_image_thumb']=wp_get_attachment_image( $attachment_id, apply_filters( 'thumbnail_image_size', 'thumbnail' ), '', array( 'alt' => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) ) );
					$ccs_global_variable['current_image_thumb_url']= wp_get_attachment_thumb_url( $attachment_id ) ;
					$ccs_global_variable['current_image_caption']=get_post( $attachment_id )->post_excerpt ? get_post( $attachment_id )->post_excerpt : '';
					$ccs_global_variable['current_image_title'] = get_post( $attachment_id )->post_title;
					$ccs_global_variable['current_image_description'] = get_post( $attachment_id )->post_content;
					$ccs_global_variable['current_image_alt'] = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

					$ccs_global_variable['current_image_ids'] = implode(" ", $attachment_ids);
					$ccs_global_variable['current_attachment_ids'] = $ccs_global_variable['current_image_ids'];

			$keywords = apply_filters( 'query_shortcode_keywords', array(
				'URL' => get_permalink( $attachment_id ),
				'ID' => $attachment_id,
				'TITLE' => get_post( $attachment_id )->post_title,
				'CONTENT' => get_post( $attachment_id )->post_content,
				'CAPTION' => get_post( $attachment_id )->post_excerpt,
				'DESCRIPTION' => get_post( $attachment_id )->post_content,
				'IMAGE' => $ccs_global_variable['current_image'],
				'IMAGE_URL' => $ccs_global_variable['current_image_url'],
				'ALT' => $ccs_global_variable['current_image_alt'],
				'THUMBNAIL' => $ccs_global_variable['current_image_thumb'],
				'THUMBNAIL_URL' => $ccs_global_variable['current_image_thumb_url'],
				'TAGS' => strip_tags( get_the_tag_list('',', ','') ),
				'IMAGE' => get_the_post_thumbnail(),
				'IMAGE_URL' => wp_get_attachment_url(get_post_thumbnail_id(get_the_ID())),

				'FIELD' => get_post_meta( get_the_ID(), $custom_field, $single=true ),
				'IDS' => get_post_meta( get_the_ID(), '_custom_gallery', true ),
			) );
				
					$output[] = do_shortcode(custom_clean_shortcodes($this->get_block_template( $template, $keywords ) ) );
				} /** End for each attachment **/

				$ccs_global_variable['is_gallery_loop'] = "false";
				wp_reset_query();
				wp_reset_postdata();

				echo implode( $posts_separator, $output );
				$ccs_global_variable['is_loop'] = "false";
				return ob_get_clean();
	    	} // End if attachment IDs exist
		} // End if function exists 
		$ccs_global_variable['current_gallery_id'] = '';
		$ccs_global_variable['is_loop'] = "false";
		return;
	} /* End of gallery loop */
	}

	} /* End of function simple_query_shortcode */ 

	/*
	 * Replaces {VAR} with $parameters['var'];
	 */

	function get_block_template( $string, $parameters = array() ) {
		$searches = $replacements = array();

		// replace {KEYWORDS} with variable values
		foreach( $parameters as $find => $replace ) {
			$searches[] = '{'.$find.'}';
			$replacements[] = $replace;
		}

		return str_replace( $searches, $replacements, $string );
	}

}

$loop_shortcode = new Loop_Shortcode;

/*--------------------------------------*/
/*    Clean up Shortcodes
/*--------------------------------------*/
function custom_clean_shortcodes($content){   
/*    $array = array (
        '<p>[' => '[', 
        ']</p>' => ']', 
        ']<br />' => ']'
    );
    $content = strtr($content, $array); */
    return $content;
}

