<?php
/*
Plugin Name: ACF SoundCloud Playlists
Description: An Advanced Custom Fields addon that provides you a field type to browse your very own SoundCloud playlists and add them in your post, option and/or widget.
Version: 1.0
Author: Dreb Bits
Author URI: http://drebbits.com
Text Domain: acf/scp
*/

$plugin_dir = plugin_dir_path(__FILE__);

include_once( $plugin_dir  . 'class/app.php');
include_once( $plugin_dir  . 'inc/sc-option.php');
include_once( $plugin_dir  . 'inc/helpers.php');

add_action('acf/include_field_types', 'include_field_type_soundcloudPlaylists');	
add_action('acf/register_fields', 'register_fields_soundcloudPlaylists');	


// Include field type for ACF5
function include_field_type_soundcloudPlaylists( $version ) {

	include_once('acf-scp-v5.php');

}

// Include field type for ACF4
function register_fields_soundcloudPlaylists() {
	
	include_once('acf-scp-v4.php');
	
}


?>