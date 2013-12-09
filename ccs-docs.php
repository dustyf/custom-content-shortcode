<?php

/*====================================================================================================
 *
 * Create help page under Settings -> Content Shortcodes
 *
 *====================================================================================================*/


// create custom user settings menu
add_action('admin_menu', 'ccs_content_settings_create_menu');

function ccs_content_settings_create_menu() {
	add_options_page('Custom Content Shortcode - Documentation', 'Custom Content', 'manage_options', 'ccs_content_shortcode_help', 'ccs_content_settings_page');
}


add_action( 'admin_init', 'ccs_content_settings_register_settings' );
function ccs_content_settings_register_settings() {
	register_setting( 'ccs_content_settings_field', 'ccs_content_settings', 'ccs_content_settings_field_validate' );
	add_settings_section('ccs_content_settings_section', '', 'ccs_content_settings_section_page', 'ccs_content_settings_section_page_name');
	add_settings_field('ccs_content_settings_field_string', 'Custom content settings field', 'ccs_content_settings_field_input', 'ccs_content_settings_section_page_name', 'ccs_content_settings_section');
}

function ccs_content_settings_section_page() {
/*	echo '<p>Main description</p>';  */
}



function ccs_content_settings_field_input() {
/*

	$settings = get_option( 'ccs_content_settings');

		$registration_enabled = isset( $settings['registration'] ) ?
			esc_attr( $settings['registration'] ) : 'on'; // If no setting, then default
	?>

	<tr>
		<td width="200px">
			<input type="checkbox" name="ccs_content_settings[registration]"
				<?php checked( $settings['registration'], 'on' ); ?>
			/>

			<?php echo '&nbsp;&nbsp;Nová registrace'; ?>
		</td>
	</tr>

<?php 

	<tr>
		<td width="200px">
			<input type="checkbox" name="ccs_content_settings[option2]"
				<?php checked( $settings['option2'], 'on' ); ?>
			/>

			<?php echo '&nbsp;&nbsp;Něco dalšího'; ?>
		</td>
	</tr>

		<td width="200px">
			<input type="text" size="1"
				id="ampl_settings_field_max_limit"
				name="ampl_settings[max_limit][<?php echo $key; ?>]"
				value="<?php echo $max_number; ?>" />
		</td>
		<td width="200px">
			<input type="radio" value="date" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'date', $post_orderby ); ?>/>
			<?php echo 'date&nbsp;&nbsp;'; ?>
			<input type="radio" value="title" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'title', $post_orderby ); ?>/>
			<?php echo 'title&nbsp;&nbsp;'; ?>
			<input type="radio" value="menu_order" name="ampl_settings[orderby][<?php echo $key; ?>]" <?php checked( 'menu_order', $post_orderby ); ?>/>
			<?php echo 'menu&nbsp;&nbsp;'; ?>
		</td>
 ?>

	<?php
*/
}



function ccs_content_settings_field_validate($input) {
	// Validate somehow
	return $input;
}


function ccs_docs_admin_css() {
   echo '<style type="text/css">
   			.doc-style {
   				max-width: 760px; /*margin: 0 auto;*/
   				padding-top:10px;
   				padding-left:10px;
   			}
   			.doc-style, .doc-style p {
   				font-size: 16px;
   			}
   			.doc-style code {
   				font-size: 16px;
   				padding: 5px 8px;
				line-height: 24px;
				display: block;
   			}
   			.doc-style h4 {
   				font-weight:normal;
   				font-style:italic;
   			}
   			ul {
   				list-style:disc; padding-left:40px;
   			}
         </style>';
}
add_action('admin_head', 'ccs_docs_admin_css');


function ccs_content_settings_page() {

	/* -- For later, in case of option form is needed
	?>
		<div class="wrap">
		<h2>Form title</h2>
		<form method="post" action="options.php">
		    <?php settings_fields( 'ccs_content_settings_field' ); ?>
		    <?php do_settings_sections( 'ccs_content_settings_section_page_name' ); ?>
		    <?php submit_button(); ?>
		</form>
		</div>
	<?php
	*/

	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'overview'; 

	?>
		<div class="wrap">
		<h2>Custom Content Shortcode</h2>
		<h3>Documentation Page</h3>

		<h2 class="nav-tab-wrapper">  
			<a href="?page=ccs_content_shortcode_help&tab=overview"
				class="nav-tab <?php echo $active_tab == 'overview' ? 'nav-tab-active' : ''; ?>">
					Overview</a>
			<a href="?page=ccs_content_shortcode_help&tab=content"
				class="nav-tab <?php echo $active_tab == 'content' ? 'nav-tab-active' : ''; ?>">
					Content</a>
			<a href="?page=ccs_content_shortcode_help&tab=loop"
				class="nav-tab <?php echo $active_tab == 'loop' ? 'nav-tab-active' : ''; ?>">
					Loop</a>
			<a href="?page=ccs_content_shortcode_help&tab=examples"
				class="nav-tab <?php echo $active_tab == 'examples' ? 'nav-tab-active' : ''; ?>">
					Examples</a>
			<a href="?page=ccs_content_shortcode_help&tab=gallery"
				class="nav-tab <?php echo $active_tab == 'gallery' ? 'nav-tab-active' : ''; ?>">
					Gallery</a>
			<a href="?page=ccs_content_shortcode_help&tab=bootstrap"
				class="nav-tab <?php echo $active_tab == 'bootstrap' ? 'nav-tab-active' : ''; ?>">
					Bootstrap</a>
		</h2>  

	<?php

		echo '<div class="doc-style">' .
			wpautop( @file_get_contents( dirname(__FILE__).'/docs/' . $active_tab . '.html') )
			. '</div>';

/*		include (dirname(__FILE__).'/docs/' . $active_tab . '.html') );	// Load doc part
*/
/*		switch ( $active_tab ) {
		 	case 'overview':
		 		break;
		 	case 'content':
				?>Content here
				<?php
		 		break;
		 }
*/
	?>
		</div>
	<?php

}

