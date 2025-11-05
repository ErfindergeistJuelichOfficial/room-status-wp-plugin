<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require_once 'vars.php';
require_once ABSPATH . 'wp-includes/rest-api.php';

// https://mojoauth.com/escaping/php-string-escaping-in-php/
function egj_escape($string) {
  $newString = trim($string);
  $newString = addslashes($newString);
  return $newString;
}

function egj_door_status_post_api( WP_REST_Request $request){
  // rate limiting
  $ip = $_SERVER['REMOTE_ADDR'];
  $transient_key = 'erfindergeist_post_rate_limit_' . md5($ip);
  $limit = get_option( $_SESSION['egj_room_status_option_rate_limit_in'] ) || 500; // there is one hourly request, but every state change did a change as well
  $window = 3600;

  $count = get_transient($transient_key);
  if ($count === false) {
    set_transient($transient_key, 1, $window);
  } elseif ($count >= $limit) {
    $transient_mail_key = 'erfindergeist_post_mail_rate_limit_' . md5($ip);
    $bool = get_transient($transient_mail_key);
    if (!$bool) {
      set_transient($transient_mail_key, true, $window);
      $admin = get_userdata(1);
      $email = $admin ? $admin->user_email : null;
      $site_url = get_site_url();
      if($email) {
        wp_mail($email, 'Wordpress: erfindergeist-room-status: API Hammering detected', "SERVER: $site_url \n IP: $ip hat das Rate Limit überschritten. \n limit: $limit \n count: $count");
      }
    }

    return new WP_Error('rate_limited', 'Too many requests. Please try again later.', array('status' => 429));
  } else {
    set_transient($transient_key, $count + 1, $window);
  }

  // AUTH
  // https://stackoverflow.com/questions/53126137/wordpress-rest-api-custom-endpoint-with-url-parameter
  $token1_param = egj_escape($request->get_param( 'token' ));
  $token1_db = get_option( $_SESSION['egj_room_status_token_option_name_1'] );

  $token2_param = egj_escape($request->get_param( 'token2' ));
  $token2_db = get_option( $_SESSION['egj_room_status_token_option_name_2'] );

  $token3_param = egj_escape($request->get_param( 'token3' ));
  $token3_db = get_option( $_SESSION['egj_room_status_token_option_name_3'] );

  // Check if the tokens match
  if($token1_param !== $token1_db || $token2_param !== $token2_db || $token3_param !== $token3_db) {
    return new WP_Error();
  }

  // other ways to get the body
  // $body = $request->get_body();
  $body = file_get_contents('php://input');
  
  if(!json_validate( $body)) {
    return new WP_Error('invalid_json', 'The request body must be a valid JSON.', array('status' => 400));
  }

  $newData = json_decode($body, true);
    
  // check newData is a one dimensional object
  if (!is_array($newData)) {
    return new WP_Error('invalid_data', 'The data must be a one-dimensional array.', array('status' => 400));
  }

  // set a dateTime key for each entry of newData
  foreach ($newData as $key => $value) {
    if (is_array($value)) {
      $newData[$key]['dateTime'] = (new DateTime())->format(DateTime::ATOM);
    } else {
      $newData[$key] = [
        'value' => egj_escape($value),
        'dateTime' => (new DateTime())->format(DateTime::ATOM)
      ];
    }
  }

  // mix old and new data
  $oldData = get_option( $_SESSION['egj_room_status_option_name_1'] );

  if ($oldData) {
    $newData = array_merge($oldData, $newData);
  }

  update_option( $_SESSION['egj_room_status_option_name_1'], $newData );

  $response = new WP_REST_Response($newData );
  $response->set_status(200);
  return $response;
}

function egj_door_status_get_api( $data ) {
  // rate limiting
  $ip = $_SERVER['REMOTE_ADDR'];
  $transient_key = 'erfindergeist_get_rate_limit_' . md5($ip);
  $limit = get_option( $_SESSION['egj_room_status_option_rate_limit_out'] ) || 400;
  $window = 3600;

  $count = get_transient($transient_key);
  if ($count === false) {
    set_transient($transient_key, 1, $window);
  } elseif ($count >= $limit) {
    return new WP_Error('rate_limited', 'Too many requests. Please try again later.', array('status' => 429));
  } else {
    set_transient($transient_key, $count + 1, $window);
  }

  $jsonData = get_option( $_SESSION['egj_room_status_option_name_1'] );

  $response = new WP_REST_Response($jsonData);
  $response->set_status(200);

  return $response;
}

function egj_door_status_permission_callback() {
  // Authorization: Basic base64(username:application-password)
  return is_user_logged_in();
  // $user = wp_get_current_user();
  // if ($user->user_login === 'benutzername') {
  //   return true;
  // }
  // return false;
}

add_action('rest_api_init', function () {
  register_rest_route($_SESSION['egj_room_status_namespace'] .'/' . $_SESSION['egj_door_status_version'] , '/' . $_SESSION['egj_room_status_route'], array(
    'methods'  => 'GET',
    'callback' => 'egj_door_status_get_api',
    'permission_callback' => '__return_true'
  ));
});

add_action('rest_api_init', function () {
  register_rest_route($_SESSION['egj_room_status_namespace'] .'/' . $_SESSION['egj_door_status_version'], '/' . $_SESSION['egj_room_status_route'], array(
    'methods'  => 'POST',
    'callback' => 'egj_door_status_post_api',
    'permission_callback' => '__return_true' // if wanted more security egj_door_status_permission_callback
  ));
});
