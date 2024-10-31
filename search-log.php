<?php
/*
Plugin Name: Search Log
Plugin URI: http://jonefox.com/blog
Description: Keep track of every search visitors do on your site so that you can make sure they're finding what they're looking for.  This plugin quickly shows your most recent searches as well as your top searches over time.
Version: 1.01
Author: jonefox
Author URI: http://jonefox.com/blog
*/

function sl_search_grabber() {
        global $wpdb;
        $wpdb->query( "CREATE TABLE IF NOT EXISTS `searches` (
                `id` int(11) NOT NULL auto_increment,
                `search` varchar(250) character set utf8 default NULL,
                `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                PRIMARY KEY  (`id`),
                KEY `searches` (`search`)
              ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;" );
        
        // Grab the search term from the URL if it exists
        if ( $index = strpos( $_SERVER['REQUEST_URI'], '/search/' ) ) {
                $search_term = urldecode( substr( $_SERVER['REQUEST_URI'], $index + strlen( '/search/' ) ) );
        } elseif ( strlen( $_GET['s'] ) > 0 ) {
                $search_term = urldecode( $_GET['s'] );
        } else {
                $search_term = false;
        }
        
        if ( !isset( $_GET['searchlog'] ) && $search_term )
                $wpdb->insert( 'searches', array( 'search' => $search_term, 'time' => date( 'Y-m-d H:i:s' ) ) );
}

add_action( 'init', 'sl_search_grabber' );

add_action( 'admin_menu', 'sl_add_menu_items' );

function sl_add_menu_items() {
        if ( function_exists( 'add_object_page' ) ) { // WP 2.7+
                add_object_page(
                        'Search Logs',
                        'Search Logs',
                        'publish_posts',
                        'searchlogs',
                        'sl_search_logs_page',
                        WP_CONTENT_URL . '/plugins/search-log/search-log.png'
                );
        } else { // < WP 2.7
                add_menu_page(
                        'Search Logs',
                        'Search Logs',
                        'publish_posts',
                        'searchlogs',
                        'sl_search_logs_page'
                );
        }
}

function sl_search_logs_page() {
        global $wpdb;
        ?>
        <div class="wrap">
                <h2>Search Logs</h2>
        </div>
        <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                        <div class="postbox-container" style="width:49%;">
                                <div id="side-sortables" class="meta-box-sortables ui-sortable">
                                        <div class="postbox " id="dashboard_incoming_links">
                                                <div title="Click to toggle" class="handlediv"><br></div>
                                                <h3 class="hndle"><span>Recent Searches</span></h3>
                                                <div class="inside" style="">
                                                        <ul>
                                                                <?php
                                                                        $searches = $wpdb->get_results( "SELECT * FROM `searches` ORDER BY `id` DESC LIMIT 10" );
                                                                        $counter = 0;
                                                                        foreach( $searches as $search ) {
                                                                                if ( $counter % 2 == 1 )
                                                                                        $bg = 'background-color: #F0F0F0;';
                                                                                else
                                                                                        $bg = '';
                                                                                        
                                                                                if ( strlen( $search->search ) > 0 )
                                                                                        echo '<li style="padding-left: 10px; line-height: 200%; ' . $bg . '"><a href="' . get_option( 'siteurl' ) . '?searchlog=false&s=' . stripslashes( $search->search ) . '">"' . stripslashes( $search->search ) . '"</a> <span class="sl_time" style="display:none; float: right; padding-right: 10px;">' . ( strtotime( $search->time ) - date( 'Z' ) ) . '</span></li>';
                                                                                
                                                                                $counter++;
                                                                        }
                                                                        if ( count( $searches ) == 0 )
                                                                                echo '<li style="padding-left: 10px; line-height: 200%; "><i>No searches to display yet</i></li>';
                                                                ?>
                                                        </ul>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        <div class="postbox-container" style="width:49%;">
                                <div id="side-sortables" class="meta-box-sortables ui-sortable">
                                        <div class="postbox " id="dashboard_incoming_links">
                                                <div title="Click to toggle" class="handlediv"><br></div>
                                                <h3 class="hndle"><span>Top Searches</span></h3>
                                                <div class="inside" style="">
                                                        <ul>
                                                                <?php
                                                                        $searches = $wpdb->get_results( "SELECT `search`, count(id) as count FROM `searches` GROUP BY `search` ORDER BY count DESC, id DESC LIMIT 10" );
                                                                        $counter = 0;
                                                                        foreach( $searches as $search ) {
                                                                                if ( $counter % 2 == 1 )
                                                                                        $bg = 'background-color: #F0F0F0;';
                                                                                else
                                                                                        $bg = '';
                                                                                        
                                                                                if ( strlen( $search->search ) > 0 )
                                                                                        echo '<li style="padding-left: 10px; line-height: 200%; ' . $bg . '"><a href="' . get_option( 'siteurl' ) . '?searchlog=false&s=' . stripslashes( $search->search ) . '">"' . stripslashes( $search->search ) . '"</a> <span style=" float: right; padding-right: 10px;">' . number_format( $search->count ) . '</span></li>';
                                                                                        
                                                                                $counter++;
                                                                        }
                                                                        if ( count( $searches ) == 0 )
                                                                                echo '<li style="padding-left: 10px; line-height: 200%; "><i>No searches to display yet</i></li>';
                                                                ?>
                                                        </ul>
                                                </div>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>
        <script type='text/javascript'>
                if ( typeof(jQuery) == 'undefined' )
                        alert( 'No jQuery?' );
                else {
                        jQuery('span.sl_time').each( function( index, el ) { var dobj = new Date(); var d = new Date( parseInt( el.innerHTML ) * 1000 - dobj.getTimezoneOffset() ); jQuery(el).html( d.getFullYear() + '/' + pad( (d.getMonth() + 1) , 2 ) + '/' + pad( d.getDate(), 2 ) + ' ' + d.getHours() + ':' + pad( d.getMinutes(), 2 ) + ':' + pad( d.getSeconds(), 2 ) ); el.style.display='inline'; } );
                }
                
                function pad(number, length) {
                        var str = '' + number;
                        while (str.length < length) {
                                str = '0' + str;
                        }
                
                        return str;
                }
        </script>
        <?php
}

function sl_save_option( $name, $value ) {
        global $wpmu_version;
        
        if ( false === get_option( $name ) && empty( $wpmu_version ) ) // Avoid WPMU options cache bug
                add_option( $name, $value, '', 'no' );
        else
                update_option( $name, $value );
}

function sl_register_site() {
        global $current_user;
        
        $site = array( 'url' => get_option( 'siteurl' ), 'title' => get_option( 'blogname' ), 'user_email' => $current_user->user_email );
        
        $response = sl_send_data( 'add-site', $site );
        if ( strpos( $response, '|' ) ) {
                // Success
                $vals = explode( '|', $response );
                $site_id = $vals[0];
                $site_key = $vals[1];
                if ( isset( $site_id ) && is_numeric( $site_id ) && strlen( $site_key ) > 0 ) {
                        sl_save_option( 'sl_site_id', $site_id );
                        sl_save_option( 'sl_site_key', $site_key );
                        return true;
                }
        }
        
        return $response;
}

function sl_rest_handler() {
        if ( !get_option( 'sl_ignore_message') && get_option( 'sl_notice' ) ) {
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'thickbox', null, array('jquery') );
                wp_enqueue_style( 'thickbox.css', '/' . WPINC . '/js/thickbox/thickbox.css', null, '1.0' );
        }
        
        // Basic ping
        if ( isset( $_GET['sl_ping'] ) || isset( $_POST['sl_ping'] ) )
                return sl_ping_handler();
}

add_action( 'init', 'sl_rest_handler' );

function sl_ping_handler() {
        if ( !isset( $_GET['sl_ping'] ) && !isset( $_POST['sl_ping'] ) )
                return false;
        
        $ping = ( $_GET['sl_ping'] ) ? $_GET['sl_ping'] : $_POST['sl_ping'];
        if ( strlen( $ping ) <= 0 )
                exit;
        
        if ( $ping != get_option( 'sl_site_key' ) )
                exit;
        
        sl_getnotice();
        echo sha1( $ping );
        exit;
}

function sl_notice() {
        if ( !get_option( 'sl_ignore_message') && get_option( 'sl_notice' ) ) {
                ?>
                <div class="updated fade-ff0000">
                        <p><strong><?php echo get_option( 'sl_notice' );?></strong></p>
                </div>
                <?php
        }
        
        if ( get_option( 'sl_has_shown_notice') )
                return;
        
        sl_save_option( 'sl_has_shown_notice', true );
        return;
}

add_action( 'admin_notices', 'sl_notice' );

function sl_activate() {
        sl_register_site();
}

register_activation_hook( __FILE__, 'sl_activate' );

if ( !function_exists( 'wp_remote_get' ) && !function_exists( 'get_snoopy' ) ) {
        function get_snoopy() {
                include_once( ABSPATH . '/wp-includes/class-snoopy.php' );
                return new Snoopy;
        }
}

function sl_http_query( $url, $fields ) {
        $results = '';
        if ( function_exists( 'wp_remote_get' ) ) {
                // The preferred WP HTTP library is available
                $url .= '?' . http_build_query( $fields );
                $response = wp_remote_get( $url );
                if ( !is_wp_error( $response ) )
                        $results = wp_remote_retrieve_body( $response );
        } else {
                // Fall back to Snoopy
                $snoopy = get_snoopy();
                $url .= '?' . http_build_query( $fields );
                if ( $snoopy->fetch( $url ) )
                        $results = $snoopy->results;
        }
        return $results;
}

function sl_send_data( $action, $data_fields ) {
        $data = array( 'action' => $action, 'data' => base64_encode( json_encode( $data_fields ) ) );
        
        return sl_http_query( 'http://jonefox.com/search-logs/rest.php', $data );
}

function sl_getnotice() {
        $response = sl_send_data( 'get-notice', array( 'site_id' => get_option( 'sl_site_id' ), 'site_key' => get_option( 'sl_site_key' ) ) );
        if ( $response && strlen( $response ) > 0 ) {
                sl_save_option( 'sl_notice', $response );
                sl_save_option( 'sl_ignore_message', false );
        }
}

if ( isset( $_GET['sl_ignore'] ) ) {
        sl_save_option( 'sl_ignore_message', true );
}
?>