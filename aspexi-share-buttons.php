<?php
/*
Plugin Name: Aspexi Share Buttons
Plugin URI:  http://aspexi.com/downloads/aspexi-share-buttons/?src=free_plugin
Description: Simple social share (Facebook and Twitter) buttons for mobile, stick to the bottom of the page.
Author: Aspexi
Version: 1.0.3
Author URI: http://aspexi.com/
License: GPLv2 or later

    Copyright 2017 Aspexi
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Text Domain: aspexisharebuttons
Domain Path: /languages
*/

defined('ABSPATH') or exit();

__( 'Simple social share (Facebook and Twitter) buttons for mobile, stick to the bottom of the page.', 'aspexisharebuttons' );

if ( !class_exists( 'AspexiShareButtons' ) ) {

    define('ASPEXISHAREBUTTONS_VERSION', '1.0.3');
    define('ASPEXISHAREBUTTONS_URL', plugin_dir_url( __FILE__ ) );
    define('ASPEXISHAREBUTTONS_BASENAME', basename( __FILE__ ));
    define('ASPEXISHAREBUTTONS_ADMIN_URL', 'themes.php?page=' . basename( __FILE__ ) );

    class AspexiShareButtons {

    	public $cf          = array(); // config array
        private $messages   = array(); // admin messages
        private $errors     = array(); // admin errors

        public function __construct() {

            /* Configuration */
            $this->settings();

            add_action( 'admin_menu',           array( &$this, 'admin_menu'));
            add_action( 'init',                 array( &$this, 'init' ), 10 );
            add_action( 'wp_footer',            array( &$this, 'get_html' ), 21 );            
            add_action( 'admin_enqueue_scripts', array( &$this, 'admin_init_scripts') );
            add_action( 'wp_enqueue_scripts',   array( &$this, 'init_scripts') );
            add_filter( 'plugin_action_links',  array( &$this, 'settings_link' ), 10, 2);

            register_uninstall_hook( __FILE__, array( 'AspexiShareButtons', 'uninstall' ) );
        }

        /* WP init action */
        public function init() {

            /* Internationalization */
            load_plugin_textdomain( 'aspexisharebuttons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        }

        public function settings() {

            /* Defaults */
            $cf_default = array(
                'aspexisharebuttons_version' => ASPEXISHAREBUTTONS_VERSION,
                'facebook' => '1',
                'twitter' => '1'
            );

            if ( !get_option( 'aspexisharebuttons_options' ) )
                add_option( 'aspexisharebuttons_options', $cf_default, '', 'yes' );

            $this->cf = get_option( 'aspexisharebuttons_options' );

            /* Upgrade */
            if( $this->cf['aspexisharebuttons_version'] != ASPEXISHAREBUTTONS_VERSION ) {
                switch( $this->cf['aspexisharebuttons_version'] ) {
                    default:
                        $this->cf = array_merge( $cf_default, (array)$this->cf );
                        $this->cf['aspexisharebuttons_version'] = ASPEXISHAREBUTTONS_VERSION;
                        update_option( 'aspexisharebuttons_options',  $this->cf, '', 'yes' );
                }
            }
        }

        public function settings_link( $action_links, $plugin_file ){
            if( $plugin_file == plugin_basename(__FILE__) ) {

                $pro_link = $this->get_pro_link();
                array_unshift( $action_links, $pro_link );

                $settings_link = '<a href="themes.php?page=' . basename( __FILE__ )  .  '">' . __("Settings") . '</a>';
                array_unshift( $action_links, $settings_link );

            }
            return $action_links;
        }

        private function add_message( $message ) {
            $message = trim( $message );

            if( strlen( $message ) )
                $this->messages[] = $message;
        }

        private function add_error( $error ) {
            $error = trim( $error );

            if( strlen( $error ) )
                $this->errors[] = $error;
        }

        public function has_errors() {
            return count( $this->errors );
        }

        public function display_admin_notices( $echo = false ) {
            $ret = '';

            foreach( (array)$this->errors as $error ) {
                $ret .= '<div class="error fade"><p><strong>'.$error.'</strong></p></div>';
            }

            foreach( (array)$this->messages as $message ) {
                $ret .= '<div class="updated fade"><p><strong>'.$message.'</strong></p></div>';
            }

            if( $echo )
                echo $ret;
            else
                return $ret;
        }

        public function admin_menu() {
            add_submenu_page( 'themes.php', __( 'Aspexi Share Buttons', 'aspexisharebuttons' ), __( 'Aspexi Share Buttons', 'aspexisharebuttons' ), 'manage_options', basename(__FILE__), array( &$this, 'admin_page') );
        }

        public function admin_page() {

        	if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            if ( isset( $_REQUEST['asb_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'asb_nonce_name' ) ) {

            	// Error checks here - no need in current version

            	// No errors
            	if( !$this->has_errors() ) {
                    $aspexisharebuttons_request_options = array();

                    $aspexisharebuttons_request_options['facebook']     = ( isset( $_REQUEST['asb_facebook'] ) && 'on' == $_REQUEST['asb_facebook'] ) ? 1 : 0;
                    $aspexisharebuttons_request_options['twitter']     = ( isset( $_REQUEST['asb_twitter'] ) && 'on' == $_REQUEST['asb_twitter'] ) ? 1 : 0;
                    $this->cf = array_merge( (array)$this->cf, $aspexisharebuttons_request_options );

                    update_option( 'aspexisharebuttons_options',  $this->cf, '', 'yes' );
                    $this->add_message( __( 'Settings saved.', 'aspexisharebuttons' ) );
                }
            }

            ?>
            <div class="wrap">
                <div id="icon-link" class="icon32"></div><h2><?php _e( 'Aspexi Share Buttons Settings', 'aspexisharebuttons' ); ?></h2>
                <?php $this->display_admin_notices( true ); ?>
                <div id="poststuff" class="metabox-holder">
                    <div id="post-body">
                        <div id="post-body-content">
                            <form method="post" action="<?php echo ASPEXISHAREBUTTONS_ADMIN_URL; ?>">

                                <input type="hidden" name="asb_form_submit" value="submit" />

                                <div class="postbox">
                                    <h3><span><?php _e('Settings', 'aspexisharebuttons'); ?></span></h3>
                                    <div class="inside">
                                        <table class="form-table">
                                            <tbody>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Facebook Share Button', 'aspexisharebuttons'); ?></th>
                                                <td><input type="checkbox" value="on" name="asb_facebook" <?php if( 1 == $this->cf['facebook'] ) echo ' checked'; ?> /></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Facebook share button icon', 'aspexisharebuttons'); ?></th>
                                                <td><input disabled readonly checked type="checkbox" name="asb_facebook_icon" /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Facebook share button copy', 'aspexisharebuttons'); ?></th>
                                                <td><input disabled readonly value="Share on Facebook" type="text" name="asb_facebook_copy" /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Facebook share button background', 'aspexisharebuttons'); ?></th>
                                                <td>
                                                    <select name="asb_facebook_background_type">
                                                        <option disabled readonly value="gradient"><?php echo __('Gradient', 'aspexisharebuttons'); ?></option>
                                                        <option disabled readonly value="solid" selected><?php echo __('Solid', 'aspexisharebuttons'); ?></option>
                                                    </select><?php echo $this->get_pro_link(); ?>
                                                    <br><br>
                                                    <div id="asb_facebook_background_type_solid">
                                                        <input disabled readonly class="asb-color-picker" type="text" name="asb_facebook_background_solid" value="#3352a0">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th colspan="2"><hr></th>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Twitter Share Button', 'aspexisharebuttons'); ?></th>
                                                <td><input type="checkbox" value="on" name="asb_twitter"  <?php if( 1 == $this->cf['twitter'] ) echo ' checked'; ?>/></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Twitter share button icon', 'aspexisharebuttons'); ?></th>
                                                <td><input disabled readonly checked type="checkbox" name="asb_twitter_icon"/><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Twitter share button copy', 'aspexisharebuttons'); ?></th>
                                                <td><input disabled readonly value="Share on Twitter" type="text" name="asb_twitter_copy" /><?php echo $this->get_pro_link(); ?></td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('twitter share button background', 'aspexisharebuttons'); ?></th>
                                                <td>
                                                    <select name="asb_twitter_background_type">
                                                        <option disabled readonly value="gradient"><?php echo __('Gradient', 'aspexisharebuttons'); ?></option>
                                                        <option disabled readonly value="solid" selected><?php echo __('Solid', 'aspexisharebuttons'); ?></option>
                                                    </select><?php echo $this->get_pro_link(); ?>
                                                    <br><br>
                                                    <div id="asb_twitter_background_type_solid">
                                                        <input disabled readonly class="asb-color-picker" type="text" name="asb_twitter_background_solid" value="#1797e8">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th colspan="2"><hr></th>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row">
                                                    <?php _e('Show share counter', 'aspexisharebuttons'); ?>
                                                </th>
                                                <td>
                                                    <input type="checkbox" name="asb_share_count" disabled readonly />
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Show on desktop and mobile (default mobile only)', 'aspexisharebuttons'); ?></th>
                                                <td>
                                                    <input disabled readonly type="checkbox" name="asb_all_desktop"><?php echo $this->get_pro_link(); ?>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Buttons height', 'aspexisharebuttons'); ?></th>
                                                <td>
                                                    <input disabled readonly type="text" name="asb_all_height" size="5" value="42">
                                                    <select name="asb_all_height_type">
                                                        <option disabled readonly value="px" selected>px</option>
                                                        <option disabled readonly value="em">em</option>
                                                    </select><?php echo $this->get_pro_link(); ?>
                                                </td>
                                            </tr>
                                            <tr valign="top">
                                                <th scope="row"><?php _e('Show on', 'aspexisharebuttons'); ?></th>
                                                    <td>
                                                        <label for="show_on_homepage">
                                                            <input id="show_on_homepage" type="checkbox" name="asb_show_on_homepage" value="on" checked readonly disabled>Homepage <?php echo $this->get_pro_link(); ?>
                                                        </label>
                                                        <?php
                                                        $types = get_post_types();
                                                        unset($types['attachment']);
                                                        unset($types['revision']);
                                                        unset($types['nav_menu_item']);
                                                        unset($types['custom_css']);
                                                        unset($types['customize_changeset']);
                                                        if( count( $types ) > 0 ) :
                                                        foreach ($types as $post_type_name) : ?>
                                                            <?php
                                                            $post_type = get_post_type_object($post_type_name);
                                                            ?>
                                                            <div>
                                                                <label for="show_on_post_type_<?php echo $post_type_name; ?>">
                                                                    <input id="show_on_post_type_<?php echo $post_type_name; ?>" type="checkbox" name="asb_show_on_post_type[]" value="<?php echo $post_type_name; ?>"  checked readonly disabled><?php echo $post_type->label; ?> <?php echo $this->get_pro_link(); ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach;
                                                        endif; ?>
                                                        <br>
                                                        <div>
                                                            <label for="">Include posts (comma separated)</label><br>
                                                            <input type="text" name="asb_show_on_include" value="" disabled readonly> <?php echo $this->get_pro_link(); ?>
                                                        </div>
                                                        <div>
                                                            <label for="">Exclude posts (comma separated)</label><br>
                                                            <input type="text" name="asb_show_on_exclude" value="" disabled readonly> <?php echo $this->get_pro_link(); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            echo apply_filters('aspexisharebuttons_admin_settings', '');
                                            ?>
                                            </tbody>
                                        </table>

                                    </div>
                                </div>

                                <?php wp_nonce_field( plugin_basename( __FILE__ ), 'asb_nonce_name' ); ?>

                                <p><input class="button-primary" type="submit" name="send" value="<?php _e('Save all settings', 'aspexisharebuttons'); ?>" id="submitbutton" /></p>

                            </form>
                            <div class="postbox">
                                <h3><span><?php _e('Made by', 'aspexisharebuttons'); ?></span></h3>
                                <div class="inside">
                                    <div style="width: 170px; margin: 0 auto;">
                                        <a href="<?php echo $this->get_pro_url(); ?>" target="_blank"><img src="<?php echo ASPEXISHAREBUTTONS_URL.'images/aspexi300.png'; ?>" alt="" border="0" width="150" /></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public function get_pro_url() {
            return 'https://aspexi.com/downloads/aspexi-share-buttons/?src=free_plugin';
        }

        public function get_pro_link() {
            $ret = '';

            $ret .= '&nbsp;&nbsp;&nbsp;<a href="'.$this->get_pro_url().'" target="_blank">'.__( 'Get PRO version', 'aspexisharebuttons' ).'</a>';

            return $ret;
        }

        public function get_html() {

        	$facebook 	= 1 == apply_filters( 'aspexisharebuttons_facebook', $this->cf['facebook'] ) ? true : false;
        	$twitter	= 1 == apply_filters( 'aspexisharebuttons_twitter', $this->cf['twitter'] ) ? true : false;

        	if( !$facebook && !$twitter )
        		return;

        	$facebook_html = $twitter_html = '';

        	// Get current page URL
        	if( is_home() || is_front_page() ) {
        		$permalink 	= get_home_url();
        		$title 		= esc_attr(strip_tags(get_bloginfo('name')));
        	} else {
	        	$post 		= get_the_ID();
	    		$permalink 	= get_permalink($post->ID);
	    		$title 		= esc_attr(strip_tags(get_the_title($post->ID)));
    		}

    		if( !$permalink )
    			return;

        	// Generate share URLs
        	if( $facebook ) {

        		$facebook_link = 'https://www.facebook.com/sharer/sharer.php?u='.urlencode( $permalink );

        		$facebook_class = array(
	        		'aspexisharebuttons_col',
	        		'aspexisharebuttons_col_facebook'
	        	);

	        	$facebook_html = '<div class="'.implode(' ', $facebook_class).'"><a href="'.$facebook_link.'" target="_blank" rel="nofollow">'.apply_filters( 'aspexisharebuttons_facebook_icon', '<span class="dashicons dashicons-facebook"></span>' ).apply_filters( 'aspexisharebuttons_facebook_copy', __('Share on Facebook', 'aspexisharebuttons' ) ).'</a></div>';
        	}

        	if( $twitter ) {

        		$twitter_link = 'https://twitter.com/intent/tweet?text='.$this->urlencode_helper( mb_strimwidth( $title, 0, 140 ) ).'&url='.urlencode( $permalink );

        		$twitter_class = array(
	        		'aspexisharebuttons_col',
	        		'aspexisharebuttons_col_twitter'
	        	);

	        	$twitter_html = '<div class="'.implode(' ', $twitter_class).'"><a href="'.$twitter_link.'" target="_blank" rel="nofollow">'.apply_filters( 'aspexisharebuttons_twitter_icon', '<span class="dashicons dashicons-twitter"></span>' ).apply_filters( 'aspexisharebuttons_twitter_copy', __('Share on Twitter', 'aspexisharebuttons' ) ).'</a></div>';
        	}

        	if( $facebook && $twitter ) {
        		$facebook_class[] 	= 'aspexisharebuttons_col_50';
        		$twitter_class[] 	= 'aspexisharebuttons_col_50';
        	}

        	$css = '<style type="text/css">
        	#aspexisharebuttons-container { display: none; }
        	@media (max-width: '.apply_filters( 'aspexisharebuttons_maxwidth', '767' ).'px) { 
        		#aspexisharebuttons-container { 
        			display: block;
        			background-color: #fff;
        			bottom: 0;
        			box-shadow: 0 0 10px 1px rgba(0, 0, 0, 0.50);
        			padding: 0;
        			position: fixed;
        			width: 100%;
        			z-index: 997;
        		} 
        		#aspexisharebuttons-tbl {
        			width: 100%;
        			display: table;
        			border: 0 none;
        			margin: 0;
        			padding: 0;
        			vertical-align: baseline;
        		}
        		.aspexisharebuttons_col {
	        		display: table-cell;
	        		max-height: 3.0em;
	        		overflow: hidden;
	        		text-align: center;
	        		vertical-align: middle;
	        	}
	        	.aspexisharebuttons_col_50 {
	        		width: 50%;
	        	}
	        	.aspexisharebuttons_col a {
	        		color: #fff;
	        		display: block;
	        		font-size: 12px;
	        		font-weight: normal;
	        		line-height: 2.5em;
	        		padding:0.5em 1em;
	        	}
	        	.aspexisharebuttons_col a, .aspexisharebuttons_col a:hover, .aspexisharebuttons_col a:active, .aspexisharebuttons_col a:focus, .aspexisharebuttons_col a:visited, .aspexisharebuttons_col a:link {
	        		text-decoration: none;
	        	}
	        	.aspexisharebuttons_col a .dashicons {
	        		vertical-align: text-bottom;
	        		margin-right: 10px;
	        	}
	        	.aspexisharebuttons_col_facebook {
	        		background-color: #3b579d;
	        		background-image: linear-gradient(to bottom, #3352a0 0%, #4461ab 100%);
	        	}
	        	.aspexisharebuttons_col_twitter {
	        		background-color: #1da1f2;
	        		background-image: linear-gradient(to bottom, #1797e8 0%, #22a6f9 100%);
	        	}
        	}
        	</style>';

        	$html = '<div id="aspexisharebuttons-container">
        				<div id="aspexisharebuttons-tbl">';

        	$html .= $facebook_html.$twitter_html;

        	$html .= '</div></div>';

        	$output = apply_filters( 'aspexisharebuttons_css', $css );
        	$output .= apply_filters( 'aspexisharebuttons_html', $html );

        	echo $output;
        }

        public function admin_init_scripts() {
            if (is_admin() && isset($_GET['page']) && $_GET['page'] == ASPEXISHAREBUTTONS_BASENAME) {
                wp_enqueue_style( 'wp-color-picker' );

                wp_enqueue_script( 'asb_admin', ASPEXISHAREBUTTONS_URL . 'js/asb_admin.js', array( 'wp-color-picker' ), false, true );
            }
        }

        public function init_scripts() {
        	
            wp_enqueue_style( 'dashicons' );

            return;
        }

        public static function uninstall() {

            delete_option( 'aspexisharebuttons_options' );
        }

        public function admin_scripts() {
            // premium only
            return;
        }

        function urlencode_helper($string) {

		    $a = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
		    $b = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");

		    return str_replace($a, $b, urlencode($string));
		}

    }

    /* Let's start the show */
    global $aspexisharebuttons;

    $aspexisharebuttons = new AspexiShareButtons();

}