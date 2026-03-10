<?PHP
/**
 * Plugin Name: Erfindergeist Room status
 * Description: Room status Erfindergeist Jülich e.V.
 * Author: Lars 'vreezy' Eschweiler
 * Author URI: https://www.vreezy.de
 * Version: 3.3.0
 * Text Domain: erfindergeist
 * Domain Path: /languages
 * Tested up to: 6.9.1
 *
 *
 * @package Erfindergeist-Room-Status
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require_once 'vars.php';
require_once 'apis.php';
require_once 'styles.php';
require_once 'content.php';

function egj_room_status_settings_page() {

  //must check that the user has the required capability
	if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
    
  $token1_post = "";
  $token2_post = "";
  $token3_post = "";
  $status = "";
  $rate_limit_in_post = "";
  $rate_limit_out_post = "";

  // Check if the user has submitted the form
  if ( !empty($_POST) && isset($_POST['egj_door_status_field']) && wp_verify_nonce($_POST['egj_door_status_field'], 'egj_door_status_action') ) {
    $token1_post = egj_escape($_POST[ $_SESSION['egj_room_status_token_input_name_1'] ]);
    $token2_post = egj_escape($_POST[ $_SESSION['egj_room_status_token_input_name_2'] ]);
    $token3_post = egj_escape($_POST[ $_SESSION['egj_room_status_token_input_name_3'] ]);
    $rate_limit_in_post = egj_escape($_POST[ $_SESSION['egj_room_status_option_rate_limit_in'] ]);
    $rate_limit_out_post = egj_escape($_POST[ $_SESSION['egj_room_status_option_rate_limit_out'] ]);

    if (isset($rate_limit_in_post)) {
      update_option( $_SESSION['egj_room_status_option_rate_limit_in'], $rate_limit_in_post );
      ?>
        <div class="updated"><p><strong><?php _e('Rate Limit In saved.', 'menu-test' ); ?></strong></p></div>
      <?php
    }

    if (  isset($rate_limit_out_post)) {
      update_option( $_SESSION['egj_room_status_option_rate_limit_out'], $rate_limit_out_post );
      ?>
        <div class="updated"><p><strong><?php _e('Rate Limit Out saved.', 'menu-test' ); ?></strong></p></div>
      <?php
    }

    if (  isset($token1_post) &&  isset($token2_post) && isset($token3_post)) {
      update_option( $_SESSION['egj_room_status_token_option_name_1'], $token1_post );
      update_option( $_SESSION['egj_room_status_token_option_name_2'], $token2_post );
      update_option( $_SESSION['egj_room_status_token_option_name_3'], $token3_post );

      // Put a "settings saved" message on the screen
      ?>
        <div class="updated"><p><strong><?php _e('Tokens saved.', 'menu-test' ); ?></strong></p></div>
      <?php
    }

    $status = json_decode(stripslashes_deep($_POST[ $_SESSION['egj_room_status_option_name_1'] ]), true);
    if(!json_last_error()) {
      update_option( $_SESSION['egj_room_status_option_name_1'], $status );
      ?>
        <div class="updated"><p><strong><?php _e('Status saved.', 'menu-test' ); ?></strong></p></div>
      <?php
    }

    if(json_last_error()) {
      ?>
        <div class="error"><p><strong><?php _e('Status JSON Error: ' . json_last_error_msg(), 'menu-test' ); ?></strong></p>
          <pre>
              <?php echo esc_html($_POST[ $_SESSION['egj_room_status_option_name_1'] ]); ?>
          </pre>
        </div>
      <?php
    }
  }
  else {
    // If the form hasn't been submitted, get the option value
    $token1_post = get_option( $_SESSION['egj_room_status_token_option_name_1'] );
    $token2_post = get_option( $_SESSION['egj_room_status_token_option_name_2'] );
    $token3_post = get_option( $_SESSION['egj_room_status_token_option_name_3'] );
    $rate_limit_in_post =  get_option( $_SESSION['egj_room_status_option_rate_limit_in'] );
    $rate_limit_out_post =  get_option( $_SESSION['egj_room_status_option_rate_limit_out'] );
  }

  $status = json_encode(get_option( $_SESSION['egj_room_status_option_name_1'] ),  JSON_PRETTY_PRINT);

  // Form
  ?>
    <div>
      <h3>Erfindergeist Room Status Settings</h3>
      <form name="form1" method="post" action="">
        <?php wp_nonce_field('egj_door_status_action','egj_door_status_field'); ?>

        <label for="token1">Token 1</label><br>
        <input id="token1" type="text" name="<?php echo $_SESSION['egj_room_status_token_input_name_1']; ?>" value="<?php echo isset($token1_post) ? esc_attr($token1_post) : ''; ?>"><br>
        <label for="token2">Token 2</label><br>
        <input id="token2" type="text" name="<?php echo $_SESSION['egj_room_status_token_input_name_2']; ?>" value="<?php echo isset($token2_post) ? esc_attr($token2_post) : ''; ?>"><br>
        <label for="token3">Token 3</label><br>
        <input id="token3" type="text" name="<?php echo $_SESSION['egj_room_status_token_input_name_3']; ?>" value="<?php echo isset($token3_post) ? esc_attr($token3_post) : ''; ?>"><br>
        <label for="status">Status</label><br>
        <textarea id="status" name="<?php echo $_SESSION['egj_room_status_option_name_1']; ?>" rows="10" cols="50" style="resize: both"><?php echo isset($status) ? esc_textarea($status) : ''; ?></textarea><br>
        
        <label for="rate_limit_in">Rate Limit In</label><br>
        <input id="rate_limit_in" type="number" name="<?php echo $_SESSION['egj_room_status_option_rate_limit_in']; ?>" value="<?php echo isset($rate_limit_in_post) ? esc_attr($rate_limit_in_post) : ''; ?>"><br>

        <label for="rate_limit_out">Rate Limit Out</label><br>
        <input id="rate_limit_out" type="number" name="<?php echo $_SESSION['egj_room_status_option_rate_limit_out']; ?>" value="<?php echo isset($rate_limit_out_post) ? esc_attr($rate_limit_out_post) : ''; ?>"><br>
        <br>
        
        <p class="submit">
          <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
        </p>
      </form>
    </div>
  <?php
}

function egj_room_status_plugin_options() {
  ?>
    <div>
      <h3>Erfindergeist</h3>
      <p>Please use Submenus for Options</p>
    </div>
  <?php
}

function egj_room_status_plugin_menu() {
  if ( empty ( $GLOBALS['admin_page_hooks']['erfindergeist'] ) ) {
    add_menu_page(
      'Erfindergeist',
      'Erfindergeist',
      'manage_options',
      'erfindergeist',
      'egj_room_status_plugin_options'
    );
  }

  add_submenu_page(
    'erfindergeist',
    'Room Status',
    'Room Status Settings',
    'manage_options',
    'egj-room-status-options-submenu-handle',
    'egj_room_status_settings_page'
  );
}

add_action( 'admin_menu', 'egj_room_status_plugin_menu' );