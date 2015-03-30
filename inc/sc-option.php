<?php

// Menu args
function add_extra_options_pages_args() {
    
    $pages = array();

    // defaults
    $parent_slug = 'options-general.php';
    $capability = 'edit_theme_options';

    // SoundCloud
    $pages[] = array(
        'page_title' => 'SoundCloud Options',
        'menu_title' => 'SoundCloud',
        'menu_slug' => 'soundcloud-settings',
        'capability' => $capability,
        'parent_slug' => $parent_slug,
        'position' => false,
        'icon_url' => false,
    );

    return $pages;
}

// Get settings args
function get_acf_scp_page_args ($menu_slug) {

    // not found page
    if( empty($GLOBALS['extra_settings_pages'][ $menu_slug ]) ) {
        return false;   
    }
    
    // get vars
    $page = $GLOBALS['extra_settings_pages'][ $menu_slug ];
    
    // return
    return $page;
}

// SoundCloud Client ID Input
function settings_field_acf_section_intro() {
    echo '<p>' . __( sprintf('Configure your SoundCloud keys here for the %s to do its magic. If you have not registered an app in SoundCloud, please follow the guide <a href="%s" target="_blank">here</a>','<em>ACF SoundCloud Playlist</em>','http://soundcloud.com/you/apps/new'), 'acf/scp') . '</p>';
}

// Field: Status
function settings_field_acf_scp_status_html() {

    $tokey = scp_get_app('tokey');
    
    if ( empty($tokey) ) :
        
        echo '<pre>Disconnected</pre>';
    
    else :

        /* disconnect URL */
        $d_link = scp_settings_url( array(
                'sc_disconnect' => 1,
                '_wpnonce' => wp_create_nonce( 'acfscpapp-deauthorizeOK', __FILE__ ),
                ) );
        $d_show = ( isset( $_REQUEST['show_disconnect'] ) && $_REQUEST['show_disconnect'] ) ? '': 'display:none';
?>
        <pre style="margin: 6px 0;">Connected</pre>
        <small><a onclick="jQuery('#scp-confirm-disconnect').show();">Disconnect SoundCloud?</a></small>
        <div id="scp-confirm-disconnect" style="<?php echo $d_show; ?>">
            <p><?php _e('This will remove ALL the data in your site that connects with SoundCloud','acf-scp'); ?> .</p><br/>
            <a href="<?php echo $d_link; ?>" class="button">Continue</a><a onclick="this.parentNode.style.display = 'none';" style="display: inline-block; padding: 5px;">Cancel</a>
        </div>
<?php

    endif;

}

// Field: Client ID
function settings_field_acf_scp_clientkey_text() {
    $options = acf_scp_get_theme_options();
    ?>
    <input type="text" name="acf_scp_app[clientkey]" id="sc-apikey" class="regular-text" value="<?php echo sanitize_text_field( $options['clientkey'] ); ?>" />
    <label class="description" for="xtra-sc-apikey"><br/><small><?php _e( 'Also known as API Key', 'acf-soundcloud-playlists' ); ?></small></label>
    <?php
}

// Field: Secret key
function settings_field_acf_scp_secretkey_text() {
    $options = acf_scp_get_theme_options();

    ?>
    <input type="text" name="acf_scp_app[secretkey]" id="sc-secretkey" class="regular-text" value="<?php echo sanitize_text_field( $options['secretkey'] ); ?>" />
    <input type="hidden" name="acf_scp_app[tokey]" id="sc-tokey" value="<?php echo esc_attr( $options['tokey'] ); ?>" />
    <?php

}

// Field: Redirect URI
function settings_field_acf_scp_redirect_html() {
    
    $plugin_url      = plugin_dir_url( __FILE__ );
    $redirect_uri   = $plugin_url . 'callback.html'; 

    ?>
    <code><?php echo $redirect_uri; ?></code>
    <label class="description" for="xtra-sc-apikey"><br/><small><?php _e( sprintf('Copy this and add in your redirect URI for Authentication field when <a href="%s" target="_blank">registering an app</a>','http://soundcloud.com/you/apps/new'), 'acf-scp'); ?></small></label>
    <?php

}

// Get the current options from the database.
// If none are specified, use these defaults.
function acf_scp_get_theme_options() {
    $saved = (array) get_option( 'acf_scp_app' );
    $defaults = array(
        'clientkey'     => '',
        'secretkey'     => '',
        'tokey'         => '',
    );

    $defaults = apply_filters( 'extra_default_theme_options', $defaults );

    $options = wp_parse_args( $saved, $defaults );
    $options = array_intersect_key( $options, $defaults );

    return $options;
}



// Sanitize and validate updated theme options
function acf_scp_app_validate( $input ) {
    
    $output = array();

    if( isset( $input['clientkey'] ) && !empty( $input['clientkey'] ) )
        $output['clientkey'] = sanitize_text_field( $input['clientkey'] );

    if( isset( $input['secretkey'] ) && !empty( $input['secretkey'] ) )
        $output['secretkey'] = sanitize_text_field( $input['secretkey'] );

    if( isset( $input['tokey'] ) && !empty( $input['tokey'] ) )
        $output['tokey'] = sanitize_text_field( $input['tokey'] );


    $error = settings_required_scp_fields( $output, array( 'clientkey', 'secretkey' ) );

    if( $error )
        add_settings_error( "soundcloud-settings", "", "You miss to enter one or more keys", 'error' );        

    return apply_filters( 'acf_scp_app_validate', $output, $input );
}

// Render settings page
function acf_scp_render_page() {
    
    // globals cc: acf/pro/admin/options.page 
    global $plugin_page;

    $page = get_acf_scp_page_args($plugin_page);

    ?>
    <div class="wrap">
        <h2><?php echo $page['page_title']; ?></h2>
        <form method="post" action="options.php">
            <?php
                
                settings_fields( 'extras_options' );
                if ($plugin_page == 'soundcloud-settings')
                    do_settings_sections( 'soundcloud_settings' );
                
                submit_button();
                
            ?>
        </form>
    </div>
    <?php
}

// Register the theme options page and its fields
function acf_scp_app_init(){
    
    /* utility hook */
    do_action( 'acf_scp_page_init' );

    register_setting( 'extras_options', 'acf_scp_app', 'acf_scp_app_validate' );
    add_settings_section( 'soundcloud', '',  'settings_field_acf_section_intro', 'soundcloud_settings' );
    add_settings_field( 'auth_status', __( 'Status ', 'acf-soundcloud-playlists' ), 'settings_field_acf_scp_status_html', 'soundcloud_settings','soundcloud' );
    add_settings_field( 'clientkey', __( 'Client ID ', 'acf-soundcloud-playlists' ), 'settings_field_acf_scp_clientkey_text', 'soundcloud_settings','soundcloud' );
    add_settings_field( 'secretkey', __( 'Client Secret ', 'acf-soundcloud-playlists' ), 'settings_field_acf_scp_secretkey_text', 'soundcloud_settings','soundcloud' );
    add_settings_field( 'redirect_uri', __( ' Redirect URI ', 'acf-soundcloud-playlists' ), 'settings_field_acf_scp_redirect_html', 'soundcloud_settings','soundcloud' );

}

add_action( 'admin_init', 'acf_scp_app_init' );

function add_page_acf_scp_() {

    // get menu vars
    $extra_pages = add_extra_options_pages_args();

    // instantiate globals
    if( empty($GLOBALS['extra_settings_pages']) ) {
    
        $GLOBALS['extra_settings_pages'] = array();
        
    }
    
    foreach( $extra_pages as $extra_page ) {
        $slug = '';

        // insert
        $GLOBALS['extra_settings_pages'][$extra_page['menu_slug']] = $extra_page;

        if( empty($extra_page['parent_slug']) ) {
            // add page
            $slug = add_submenu_page( $extra_page['parent_slug'], $extra_page['page_title'], $extra_page['menu_title'], $extra_page['capability'], $extra_page['menu_slug'], 'acf_scp_render_page' );
        } else {
            // add subpage
            $slug = add_submenu_page( $extra_page['parent_slug'], $extra_page['page_title'], $extra_page['menu_title'], $extra_page['capability'], $extra_page['menu_slug'], 'acf_scp_render_page' );
        }
        
    }

}

add_action( 'admin_menu', 'add_page_acf_scp_' );


function acf_scp_deauthorization_check(){

    /* update notices */
    if( isset( $_REQUEST['message'] ) && !isset( $_REQUEST['settings-updated'] ) ) {
        
        if( $_REQUEST['message'] == 'acf-soundcloud-disconnected' )
            add_settings_error( "soundcloud-settings", "", "Successfully disconnected from SoundCloud", 'updated' );

        if( $_REQUEST['message'] == 'acf-soundcloud-disallowed' )
            add_settings_error( "soundcloud-settings", "", "You're not allowed to perform such action", 'error' );

    }


    if( !isset($_REQUEST['sc_disconnect']) )
        return;

    /* verify nonce */
    $acf_scp_nonce = ( isset( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : '';
    
    if( !wp_verify_nonce( $acf_scp_nonce, 'acfscpapp-deauthorizeOK' ) )
        wp_die( __( 'You do not have sufficient permissions to perform such action.' ) );

    /* can user do this action? */
    if( current_user_can( 'manage_options' ) ){
        
        global $wpdb;
        $v = 0;

        // remove option for database clearance
        delete_option( 'acf_scp_app' ); 
        
        if( class_exists('acf') ) {

            $acf = acf();
            $v = explode( '.', $acf->settings['version'] );

            if( !empty($v) );

                $v = $v[0];
        }


        if(  $v == 4 ) {
            
            // @version 4
            $args_acf = array(
                'post_type' => 'acf', 
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );

            $acf_groups = get_posts( $args_acf );


            foreach( $acf_groups as $acf_group ) {
            
                $meta = get_post_custom($acf_group->ID);

                $meta_f = array_filter_use_key( $meta );

                foreach ($meta_f as $field) {
                    
                    $field = maybe_unserialize( $field[0] );
                    $key = $field['key'];
                    $name = $field['name'];

                    // filter fields and take out type scplaylist only. 
                    maybe_scp_field_delete($field['type'], $key, $name);

                }


            }

        } elseif(  $v == 5 ) {

            // @version 5
            $args_acf = array(
                'post_type' => 'acf-field', 
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );

            $acf_fields = get_posts( $args_acf );

            foreach( $acf_fields as $acf_field ) {

                $field = maybe_unserialize( $acf_field->post_content ); 
                $key = $acf_field->post_name;
                $name = $acf_field->post_excerpt;

                maybe_scp_field_delete($field['type'], $key, $name);

            }

        }
    
    } else {

        /* error notice */
        wp_redirect( scp_settings_url( array('message'=>'acf-soundcloud-disallowed') ) );
        exit;

    }

    wp_redirect( scp_settings_url( array('message'=>'acf-soundcloud-disconnected') ) );
    exit;

}

add_action( 'acf_scp_page_init', 'acf_scp_deauthorization_check');


/*
 * scp_settings_sanitize_check
 * Sanitize field and check if field has clean input.
 *
 * @return $error bool
 */
function settings_required_scp_fields( $keys, $reqs ){

    if( empty( $reqs ) )
        return false;

    foreach( $reqs as $req ){

        $err = ( !isset( $keys[$req] ) )  ? 1: 0;

        if( $err )
            return true;

    }

    return false;

}


/*
 * maybe_scp_field_delete
 * verify if field is scp and delete if passed.
 *
 * @return $return bool
 */

function maybe_scp_field_delete( $type, $key, $name ) {
    
    global $wpdb;

    // filter fields and take out type scplaylist only. 
    if( $type == 'scplaylist' ) :

        // supportted location
        $locations = array('post', 'widget', 'option');

        // @todo Is it ideal to check its location and loop through rule match
        // just to save going through the support location (post, widgets, options)?

        foreach( $locations as $location) {
            
            switch( $location ) {

                case 'post':

                    ### UNCOMMENT IF LIVE - Post type
                    $success = $wpdb->query( 
                        $wpdb->prepare(
                            "
                            DELETE FROM $wpdb->postmeta
                            WHERE $wpdb->postmeta.meta_key = %s
                                OR $wpdb->postmeta.meta_value = %s 
                            ", $name, $key
                        )
                    );
                    break;
                
                case 'widget':
                case 'option':

                    ### UNCOMMENT IF LIVE - Widgets
                    $option_name = $wpdb->get_results( 
                        $wpdb->prepare(
                            "
                            SELECT $wpdb->options.option_name 
                            FROM $wpdb->options WHERE $wpdb->options.option_value = %s
                            ", $key
                        )
                    );

                    if( !empty($option_name) ) {
                        ### @since 3.x - ACF has transient like field name in db
                        $option_name = ltrim($option_name[0]->option_name, '_');

                        ### UNCOMMENT IF LIVE - Option
                        $success = $wpdb->query( 
                            $wpdb->prepare(
                                "
                                DELETE FROM $wpdb->options
                                WHERE $wpdb->options.option_name = %s
                                    OR $wpdb->options.option_value = %s 
                                ", $option_name, $key
                            )
                        );
                    }
                    break;

                default: 
                    $success = 0;
                break; 

            }

        }

    endif;

    return $success;
}


// WORK ONLY IN PHP 5.6.2
// function acf_scp_fields_only( $key, $val ) {

//     return preg_match("/field_/", $key);

// }

/* scp_fields_only 
 * check if custom field is an acf instance.
 *
 * @return $return bool
 */

function array_filter_use_key( $meta ) {

    foreach( array_keys($meta) as $key ) {

        if ( !preg_match("/^field_\d+/", $key) ) {

            unset($meta[$key]);

        }

    }

    return $meta;

}
