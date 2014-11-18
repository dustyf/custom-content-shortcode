<?php

/*========================================================================
 *
 * User shortcodes: user, is/isnt, list_shortcodes, search_form, blog
 *
 *=======================================================================*/

new CCS_BP_Member();

class CCS_BP_Member {

	public static $state;

	function __construct() {

		add_shortcode('bp_members', array($this, 'members_shortcode'));
		add_shortcode('bp_member', array($this, 'member_shortcode'));

		self::$state['is_users_loop'] = false;

		add_shortcode('blog', array($this, 'blog_shortcode'));
	}


	/*========================================================================
	 *
	 * Users loop
	 *
	 *=======================================================================*/

	function members_shortcode( $atts, $content ) {

		self::$state['is_users_loop'] = true;

		$outputs = array();

		/*========================================================================
		 *
		 * Prepare parameters
		 *
		 *=======================================================================*/

		$args = array();

		// Just pass these
		$pass_args = array('orderby','search','number','offset');

		foreach ($pass_args as $arg) {
			if (isset($atts[$arg]))
				$args[$arg] = $atts[$arg];
		}

		if (isset($atts['type']))
			$args['type'] = strtolower($atts['type']);
		if (isset($atts['include']))
			$args['include'] = CCS_Loop::explode_list($atts['include']);
		if (isset($atts['exclude']))
			$args['exclude'] = CCS_Loop::explode_list($atts['exclude']);

		if (isset($atts['search_terms']))
			$args['search_terms'] = esc_html( $atts['search_terms'] );

		if (isset($atts['field']) && isset($atts['value'])) {
			$args['meta_key'] = $atts['field'];
			$args['meta_value'] = $atts['value'];
		}

		$users = bp_core_get_users( $args );


		/*========================================================================
		 *
		 * Custom query to filter results
		 *
		 *=======================================================================*/

		// Users Loop
		foreach ( $users['users'] as $user ) {
			self::$state['current_user_object'] = $user;
			$outputs[] = do_shortcode( $content );
		}

		self::$state['is_users_loop'] = false;
		return implode('', $outputs);
	}



	/*========================================================================
	 *
	 * [user]
	 *
	 *=======================================================================*/

	public static function member_shortcode( $atts ) {

		if ( self::$state['is_users_loop'] ) {

			$current_user = self::$state['current_user_object'];

		} else {

			global $current_user;
			get_currentuserinfo();
			self::$state['current_user_object'] = $current_user;
		}

		extract(shortcode_atts(array(
			'field'            => '',
			'meta'             => '', // Alias
			'size'             => '',
			'bp_profile_field' => '',
		), $atts));

		if(empty($current_user)) return; // no current user

		// Get BuddyPress xprofile data
		if ( ! empty( $bp_profile_field ) ) {
			return xprofile_get_field_data( $bp_profile_field, $current_user->ID );
		}

		// Get field specified
		if( !empty($meta) ) $field=$meta;

		if ( empty($field) ) {
			// or just get the first parameter
			$field = isset($atts[0]) ? $atts[0] : null;
		}

		switch ( $field ) {
			case '':
			case 'fullname':
				return $current_user->display_name;
				break;
			case 'name':
				return $current_user->user_login;
				break;
			case 'id':
				return $current_user->ID;
				break;
			case 'email':
				return $current_user->user_email;
				break;
			case 'url':
				return $current_user->user_url;
				break;
			case 'avatar':
				return get_avatar( $current_user->ID, !empty($size) ? $size : 96);
				break;
			case 'fullname':
				return $current_user->display_name;
				break;
			case 'post-count':
				return strval( count_user_posts( $current_user->ID ) );
				break;
			case 'role':
				return rtrim(implode(',',array_map('ucwords', $current_user->roles)),',');
				break;
			default:
				return get_user_meta( $current_user->ID, $field, true );
				break;
		}

	}

	public static function get_user_field( $field ) {
		return self::user_shortcode( array( 'field' =>  $field ) );
	}

}