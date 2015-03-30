<?php
/*
*  ACF - SoundCloud Playlists
*  @since 1.0
*/

class acf_scp extends acf_field {
	
	/*
	*  __construct
	*
	*/
	
	function __construct(){

		define( 'SCP_VERSION' , '1.0' );

		// vars
		$this->name = 'scplaylist';
		$this->label = __('Playlists (SoundCloud)');
		$this->category = __("Content",'acf-scp'); 
		$this->defaults = array(
				'button_label' => __('Browse Playlist','acf-scp'),
				'return_format' => 'array',
			);
		
		// do not delete!
    	parent::__construct();

	}
	
	
	/*
	*  create_options()
	*  @v5 render_field_settings()
	*/
	
	function create_options( $field ) {
		
		// key is needed in the field names to correctly save the data
		$key = $field['name'];
		
		
		// Create Field Options HTML
		?>

<!-- Button Label -->
<tr class="field_option field_option_<?php echo $this->name; ?>">
	
	<td class="label">
		<label><?php _e("Button Label",'acf-scp'); ?></label>
	</td>
	<td>
		<?php
		
		do_action('acf/create_field', array(
			'type'		=>	'text',
			'value'		=>	$field['button_label'],
			'name'		=>	'fields['.$key.'][button_label]'
		));
		
		?>
	</td>
</tr>

<!-- Return Value -->
<tr class="field_option field_option_<?php echo $this->name; ?>">
		
	<td class="label">
		<label><?php _e("Return Value",'acf-scp'); ?></label>
		<p class="description"><?php echo __('Specify the returned value format on template','acf-scp'); ?></p>
	</td>
	<td>
		<?php
		
		do_action('acf/create_field', array(
			'type'		=>	'radio',
			'value'		=>	$field['return_format'],
			'name'		=>	'fields['.$key.'][return_format]',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				'array'			=> __("Playlist Array",'acf-scp'),
				'list'			=> __("List of Tracks",'acf-scp'),
			)
		));
		
		?>
	</td>
</tr>
		<?php
		
	}
	

	/*
	*  load_field()
	*
	*/
	
	function create_field( $field ) {

		// strip out empty values in the array
		$field = array_filter( $field );

		$defaults = array(
			'name' => '',
			'value' => array(),
		);

		$field = wp_parse_args( $field, $defaults );

		extract( $field );

		$input_values = scp_extract_values( $value );
		$check_value = array_shift($value);
		
		$label  = ( !empty($check_value) ) ? scp_get_playlist_label( $input_values ) : $button_label; 


		if( is_scp_ready() ) :

			?>
			<div class="acf-hidden">
				<input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $input_values['id']; ?>" data-name="id">
				<input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $input_values['title']; ?>"  data-name="title">
    			<input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $input_values['count']; ?>"  data-name="count">
			</div>
			<div class="browse">
				<a href="#TB_inline?width=783&height=550&inlineId=scp-browse" class="thickbox button js_load-button" data-scp-name="<?php echo $name; ?>" >
					<span class="playlist-label"><?php echo $label; ?></span>
				</a>
			</div>	
			<?php

		else : 

			echo __( sprintf('Please configure the API key <a href="%s">here</a>', scp_settings_url( array() )),'acf-scp' ) ;

		endif;

	}


	/*
	*  input_admin_enqueue_scripts()
	*
	*/
	
	function input_admin_enqueue_scripts() {
		
		$dir = plugin_dir_url( __FILE__ );

		wp_enqueue_script('thickbox');

		wp_register_script( 'scp-selectize-plug-js', "{$dir}assets/js/selectize.js", array('jquery'), '0.12.0' , false);
		wp_enqueue_script('scp-selectize-plug-js');	

		wp_register_script( 'scp-scsdk-js', "//connect.soundcloud.com/sdk.js", array('jquery') );
		wp_enqueue_script('scp-scsdk-js');	

		wp_register_script( 'scp-js', "{$dir}assets/js/acf_scp.js", array('jquery','scp-scsdk-js'), SCP_VERSION , false);
		wp_enqueue_script('scp-js');	

		wp_enqueue_style('thickbox');

		wp_register_style( 'scp-selectize-plug-style', "{$dir}assets/css/selectize.css" );
		wp_enqueue_style('scp-selectize-plug-style');	

		wp_register_style( 'scp-style', "{$dir}assets/css/acf_scp.css", array(), SCP_VERSION , false );
		wp_enqueue_style('scp-style');		
		
	}


	/*
	*  input_admin_head()
	*
	*/

	function input_admin_head() {
		
		// add class .scp-active to body
		add_filter( 'admin_body_class', array( $this, 'update_body_class' ) );

		// browse playlist modal view
		add_action('admin_footer', array( $this, 'input_admin_footer' ) );
	}


	/*
	*  format_value_for_api()
	*
	*/
	
	function format_value_for_api( $value, $post_id, $field ) {

		$value = scp_extract_values($value); 
		
		// bail early if no value
		if( empty($value['id']) ) {

			return;
			
		}

		$query = ( isset($_REQUEST['set']) && isset($_REQUEST['track'])  ) ? $_SERVER['QUERY_STRING']: '';


		if( !empty($query) ) :
			
			// ready for displaying track

			parse_str($query);


			// bail early if not same set/playlist id
			if( $set !== $value['id'] ) {

				return;
				
			}


			$return = scp_get_track( $query, $track );

		else :
			
			// get value in order

			$set = intval($value['id']);

			$return = scp_get_tracks( $field['return_format'], $set );

		endif; 
		
		return $return;
		
	}


	/*
	*	update_body_class()
	*	utility to add class name to admin body html
	*
	*	@since 1.0
	*/

	function update_body_class( $classes ) {
		
		// add .scp-active
		$classes .= 'scp-active';
		
		return $classes;

	}


	/*
	*	input_admin_footer()
	*	utility to add scrip in the admin footer
	*
	*	@since 1.0
	*/

	function input_admin_footer() {

		$dir 	 = plugin_dir_url( __FILE__ );

		$options = scp_get_app();
		
		$acf_scp = array(
				'fieldType'		 =>	$this->name,
				'client_app_key' => $options['clientkey'],
				'html_callback'  => $dir . 'inc/callback.html',
			);

		// render browse playlist in the footer for global modal view
		$this->render_scp_browse();

		?>
		<script type="text/javascript">
		
		( function($) {
		
			acf.scp = <?php echo json_encode($acf_scp); ?>;
        
        })(jQuery);

		</script>
		<?php

	}

	/*
	*	render_scp_browse()
	*	modal view of the playlist
	*
	*	@since 1.0
	*/

	function render_scp_browse() {
		
		$tokey = scp_get_app('tokey');

	?>
	<div id="scp-browse" style="display:none;">
	          
		<div class="browse-container-<?php echo $this->name; ?>" data-scp-ready="<?php echo ( !empty($tokey) ) ? 1: 0; ?>">

			<section class="browse-header">
			  <h2><?php _e('SoundCloud', 'acf-scp'); ?></h2>
			  <span><?php _e('Browse your playlist by connecting with your SoundCloud', 'acf-scp'); ?></span>
			</section>

			<section class="browse-content">
				<?php

				if( empty($tokey) ) :

					//view SeoundCloud Auth
					render_auth_html();

				endif;

			  	?>
			</section>

			<section class="browse-footer">
				<?php $disconnect_class = ( !empty($tokey) ) ? 'go-disconnect' : 'go-disconnect hidden-by-conditional-logic'; ?>
				<a href="<?php echo scp_settings_url( array( 'show_disconnect' => 1) ); ?>" class="<?php echo $disconnect_class; ?>">Disconnect SoundCloud</a>
				<a href="#" class="js_set-playlist sc-set-playlist button button-primary button-large" disabled="disabled">Set Playlist</a>
			</section>

		</div>

	</div>
	<?php	
	
	}

	
}


// create field
new acf_scp();

?>