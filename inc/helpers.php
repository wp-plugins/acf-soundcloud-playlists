<?php 
/**
Filename change dependencies:
..
**/

// ajax receivers
add_action( 'wp_ajax_acf/scp/register_token',  'ajax_call_to_register_token' );
add_action( 'wp_ajax_acf/scp/deregister_token',  'ajax_call_to_deregister_token' );
add_action( 'wp_ajax_acf/scp/load_playlists',  'ajax_call_to_load_playlists' );


/**
 *  ajax_call_to_register_token
 *  
 *	Register/add SoundCoud api token key to db.
 *  @action Update
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	n/a
 **/

function ajax_call_to_register_token() {

	global $SC_app;

	// vars with default values
	// this part has been re-use in load_playlist
	$vars = array(
		'nonce'			=> '',
		'post'			=> 0,
		'name'			=> '',
		'tokey'			=> '',
	);

	// load $_POST for $vars
	$vars = wp_parse_args($_POST, $vars);

	// validate 
	// @TODO -- verify acf_nonce 
	if( ! wp_verify_nonce($_POST['nonce'], 'acf_nonce') ) {
		die();
	}

	// verify if there's even a token
	if( empty($vars['tokey']) ) {
		die();
	}

	// get app
	$keys  = scp_get_app();

	// insert token to db
	$keys['tokey'] = sanitize_text_field( $vars['tokey'] );

	$return = update_option( 'acf_scp_app', $keys );

	// SC_app init.
	scp_app_init();

	// render view of browse playlist selections. Uncomment when ready.
	render_browse_playlist_html( $vars['name'] );

	// die
	die();

}


/**
 *  ajax_call_to_deregister_token
 *  
 *	Deregister/remove SoundCoud api token key from db.
 *  @action Update
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	n/a
 **/

function ajax_call_to_deregister_token() {


}


/**
 *  ajax_call_to_load_playlist
 *  
 *	Deregister/remove SoundCoud api token key from db.
 *  @action Update
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	n/a
 **/

function ajax_call_to_load_playlists() {

	// vars with default values
	$vars = array(
		'nonce'			=> '',
		'post'			=> 0,
		'name'			=> '',
		'track'			=> '',
	);

	// load $_POST for $vars
	$vars = wp_parse_args($_POST, $vars);

	// validate 
	// @TODO -- verify acf_nonce 
	if( ! wp_verify_nonce($_POST['nonce'], 'acf_nonce') ) {
		die();
	}

	render_browse_playlist_html( $vars['name'] , $vars['track'] );

	// die
	die();
}


/**
 *  render_browse_playlist_html
 *  
 *  Browse SoundCloud playlist.
 *  @since	1.0
 *
 *  @action View
 *  @param	$playlist (array) playlist tracks
 *  @return	n/a
 **/

function render_browse_playlist_html( $name = '', $track = '' ) {

	global $SC_app;

	$playlists  = $SC_app->get_playlist();
	$options = '';
	$s_title = '';
	$s_count = '';
	$metadata = array();
	$name = $name . '[]';

	if( !empty( $playlists ) ) {

		foreach ($playlists as $playlist) {

			$s_class = '';


			if ( $playlist->id == $track )
				
				$s_class = 'selected="selected"';


			$metadata[$playlist->id] = array(
				'title' => $playlist->title,
				'count' => $playlist->track_count,
			);

			$options .= '<option value="' . $playlist->id . '" data-playlist="playlist--' . $playlist->id . '" '. $s_class .'> ' . $playlist->title . '  ( Track counts: ' . $playlist->track_count . ') </option>';
		
		}

	} else {

		$options = '<option value="">' . __('You don\'t have playlist yet', 'acf-scp'). '</option>';

	}
	
?>
<div class="playlist" data-scp-metadata="<?php echo esc_html( json_encode($metadata) ); ?>">
	<select class="results" name="">
		<option selected="selected" value="">Select a playlist...</option>
		<?php echo $options; ?>
	</select>
</div>
<?php

}


/**
 *  render_auth_html
 *  
 *  Enable button for SounCloud Auth.
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	n/a
 **/

function render_auth_html() {

?>
<div id="show" class="auth">
	<h3>Let's get started!</h3>
	<a id="connectWithSC" class="js_go-connect important-button">Connect with SoundCloud!</a>
</div>
<?php

}

/**
 *  scp_get_playlist_label
 *  
 *  Retrieve playlist label
 *  @action Get
 *  @since	1.0
 *
 *  @param	$id (string) what setting is needed.
 *  @return	$label (string) Playlist title
 **/

function scp_get_playlist_label( $value ) {

	global $SC_app;

	// $playlist = $SC_app->get_playlist( $id );

	// extract values.
	extract( $value );

	$l10n_track = ( (int)$count < 2 ) ? 'track' : 'tracks';

	$label = $title . '&nbsp;<span class="track-count">' . $count .'&nbsp;'. $l10n_track .  ' </span>';

	return $label;
}


/**
 *  scp_extract_values
 *  
 *  Retrieve playlist label
 *  @action Get
 *  @since	1.0
 *
 *  @param	$id (string) what setting is needed.
 *  @return	$label (string) Playlist title
 **/
function scp_extract_values( $value = array() ){

	$defaults = array(
		'id' => '',
		'title' => '',
		'count' => '',
	);


	if( !empty($value) )

		$value = array(
			'id' => $value[0],
			'title' => $value[1],
			'count' => $value[2],
		);


	$value = wp_parse_args( $value, $defaults );

	return $value;

}


/**
 *  scp_get_track
 *  
 *  Track
 *  @action Get
 *  @since	1.0
 *
 *  @param	$query (string) URL Request string
 *  @return	$id (bigint)  id of the playlist
 **/

function scp_get_track( $query, $track ) {
	
	global $SC_app;

	$backlink = str_replace( '?' . $query, '', $_SERVER['REQUEST_URI'] );

	$track_params = array(
		'url'				=> 'http://api.soundcloud.com/tracks/' . $track,
		'maxheight' 		=> 200,
		'auto_play' 		=> false,
		'buying' 			=> true,
		'liking' 			=> false,
		'download' 			=> false,
		'sharing' 			=> false,
		'show_artwork' 		=> false,
		'show_comments' 	=> false,
		'show_playcount' 	=> false,
		'show_user' 		=> true,
		'hide_unrelated' 	=> false,
		'visual'  			=> false,
		'start_track' 		=> 0,
		'theme_color' 		=> 'FFFFFF',
	);

	$value = $SC_app->play_track($track_params, 'sc_player');

	$value .= '<p><span><a href="'. $backlink .'">'. __('Back to playlist','acf-scp') .'</a></span></p>';

	return $value; 
}


/**
 *  scp_get_tracks
 *  
 *  Playlist tracks
 *  @since	1.0
 *
 *  @param	$output (string) what return type format
 *			$set (longint) id of the playlist
 *  @return	$value (array|html)
 **/

function scp_get_tracks( $format = 'array', $set ) {
	
	global $SC_app;

	$tokey = scp_get_app('tokey');

	// bail out if scp is not yet connected to SoundCloud
	if( empty($tokey) )

		return array();

	$playlist = $SC_app->get_playlist($set);


	// format
	if( $format == 'list' ) {

		$list = '<h4 class="sc-playlist-title">'. $playlist->title .'</h4>';
		$list .= "<ul class='sc-playlist'>";
		
		foreach ($playlist->tracks as $track) {
			
			// @todo filter
			$list .= "<li class='track-list-item'><a href='?set=$set&track=". $track->id ."'>". $track->title ."</a></li>";
		}
		
		
		$list .= "</ul>";

		$value = $list;
		
	} else {
		
		$value = array();

		$value[] = (array)$playlist;
		
	}

	return $value;
}


/**
 *  scp_get_app
 *  
 *  Retrieve SoundCloud settings
 *  @since	1.0
 *
 *  @param	$key (string) what setting is needed.
 *  @return	$options (string | array) SoundCloud settings
 **/

function scp_get_app( $key = '' ) {

	// defaults
	$options = array(
			'clientkey' => '',
			'secretkey' => '',
			'tokey' => '',
		);

	// merge values
	$options = wp_parse_args( (array)get_option( 'acf_scp_app' ), $options );


	if( !empty($key) )
		return $options[$key];

	return $options;

}


/**
 *  scp_settings_url
 *  
 *  Get settings page URL in the 
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	URL
 **/

function scp_settings_url( $args ) {

	$args = array_merge( array(
            'page' => 'soundcloud-settings',
        ), $args );

	return add_query_arg( $args, admin_url( 'options-general.php' ) );

}


/**
 *  is_scp_ready
 *  
 *  Is SoundCloud playlist ready in action in the edit panel?
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	true/false (bool)
 **/

function is_scp_ready() {
	
	$options = scp_get_app();

	extract($options);

	if( !empty($clientkey) && !empty($secretkey) )
		return true;

	return false;

}


/**
 *  scp_app_init()
 *
 *  Initialize Soundcloud_app class
 *  @since	1.0
 *
 *  @param	n/a
 *  @return	n/a
 **/

function scp_app_init() {

	global $SC_app;

	if( is_scp_ready() )

		$SC_app = new Soundcloud_App();
}

scp_app_init();