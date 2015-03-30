<?php 
/**
* SoundCloud Helpers
* 
*/

class Soundcloud_App
{

	var $Soundcloud;
	var $sc_p_clientapikey;
	var $sc_p_clientsecret;
	var $tokey;

	function __construct() {
		
		require_once 'Services/Soundcloud.php';

		$app = scp_get_app();
		extract($app);

		$this->Soundcloud = new Services_Soundcloud($clientkey, $secretkey);
		$this->Soundcloud->setCurlOptions(array(CURLOPT_FOLLOWLOCATION => 1));
		$this->Soundcloud->setAccessToken($tokey);

		// update class vars
		$this->tokey = $tokey; 
	}

	function get_playlist( $playlist = '' ){

		// loop playlists
		if( !empty($playlist) ) {

			// get playlist from Souncloud services
			$get = sprintf( 'playlists/%d', $playlist);
			
		} else {

			$get = 'me/playlists';

		}


		try {


			if( !function_exists('curl_init') ) {

				// User WP HTTP API
				// source to soundcloud API with access token
				$src = ( !empty($playlist) ) ? sprintf('https://api.soundcloud.com/me/playlists/%d.json?oauth_token=%s', $playlist, $this->tokey) : sprintf('https://api.soundcloud.com/me/playlists.json?oauth_token=%s', $this->tokey);

				// create a client object with access token
				$response = wp_remote_get( $src,  array( 'timeout' => 15 ) );


				// is success?
				if( ! is_wp_error( $response ) 
			        && isset( $response['response']['code'] )        
			        && 200 === $response['response']['code'] ) {	

					$remote = wp_remote_retrieve_body( $response );
					
				}

			
			} else {

				$remote = $this->Soundcloud->get($get);			

			}

		    $result = json_decode($remote);


		} catch (Services_Soundcloud_Invalid_Http_Response_Code_Exception $e) {
		    
		    exit( $e->getMessage() );

		}


		if( !empty($result) )

			return $result;

		// // get playlist by ID from Souncloud services
		// $got_playlist = json_decode($this->Soundcloud->get(sprintf( 'playlists/%d',$playlist) ));

		// // @todo -- insert sanitization maybe here?

		// if( !empty($got_playlist) )

		// 	return $got_playlist;


		// return;


		// // create a client object with access token
		// playlistssrc = sprintf('https://api.soundcloud.com/me/playlists/%d.json?oauth_token=%s', $playlist, $this->tokey);

		// $response = wp_remote_get( $playlistssrc,  array( 'timeout' => 15 ) );

		// // wp_remote_get version
		// if( ! is_wp_error( $response ) 
	 //        && isset( $response['response']['code'] )        
	 //        && 200 === $response['response']['code'] )
	 //    {	
		// 	$playlist = json_decode(wp_remote_retrieve_body( $response ));
			
		// 	// list tracks in playlist
		// 	return $playlist;
		// }

		// return;
	}


	function get_track($trackId){
		$track_api = sprintf('https://api.soundcloud.com/tracks/%d.json?client_id=%s', $trackId, $this->sc_p_clientapikey);

		$response = wp_remote_get( $track_api,  array( 'timeout' => 15 ) );
		
		// fetch a track by it's ID
		// $track = json_decode($this->SCClient->get(sprintf( 'tracks/%d',$trackId) ));
		
		// if($track) 
		// 	return $track;
		
		// wp_remote_get version
		if( ! is_wp_error( $response ) 
	        && isset( $response['response']['code'] )        
	        && 200 === $response['response']['code'] )
	    {	
			$track = json_decode(wp_remote_retrieve_body( $response ));

			// @return track info
			return $track;			
		}

		return;
	}


	function play_track ($track_args = array(), $widget_id) {

		// get a tracks oembed data
		$soundcloud_url = $this->track_url_stream($track_args);
		$track_script = $this->track_widget_script($widget_id);
		$track_iframe = $track_script . '<iframe id="'. $widget_id .'" width="100%" height="166" scrolling="no" frameborder="no" src="'. $soundcloud_url .'"></iframe>';
		
		// render the html for the player widget
		// return $embed_info->html;
		
		return $track_iframe;
	}


	private function track_url_stream ($args = array()) {
		$url = 'https://w.soundcloud.com/player/?';

		foreach ($args as $param => $value) {
			$url .= $param . '=';
			$url .= $value . '&';
		}

		return $url;
	}


	private function track_widget_script( $widget_id ) {
		$script='
		<script src="https://w.soundcloud.com/player/api.js" type="text/javascript"></script>
		<script type="text/javascript">
		  
		  (function($){

		  	$(document).ready( function(){
			    var widget       = SC.Widget('. $widget_id .');

			    widget.bind(SC.Widget.Events.READY, function() {
			      // load new widget
			      widget.bind(SC.Widget.Events.FINISH, function() {
			        widget.load(newSoundUrl, {
			          show_artwork: false
			        });
			      });
			    });

			});

		  })(jQuery);

		</script>';

		return $script;
	}


	private function get_playlist_artwork($artwork, $artwork_track) {
		
		$has_artwork = ($artwork != '') ? $artwork : $artwork_track ;
		
		return str_replace('large','crop',$has_artwork);
	}
}
?>