<?php

/*
Plugin Name: SoMakeIt
Plugin URI: http://github.com/so-make-it/wordpress
Description: Authenticates against SoMakeIt's members area.
Author: Benjie Gillam
Author URI: http://www.benjiegillam.com/
Licence: GPLv3
Version: 0.0.1
*/

// There can be only one
remove_all_filters('authenticate');

add_filter('authenticate', 'somakeit_auth_signon', 1, 3);
function somakeit_auth_signon($null, $username, $password) {
  if (strlen($username) == 0) {
    return NULL;
  }
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "https://members.somakeit.org.uk/me");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, array("email" => $username, "password" => $password));
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $json = curl_exec($ch);
  $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $result = json_decode($json, true);
  if ($statuscode == 200 && !empty($result['username']) && !empty($result['id'])) {
    $username = sanitize_user($result['username']);
    $id = username_exists($username);
    if (empty($id)) {
      $random_password = wp_generate_password($length=24, $include_standard_special_chars=false);
      $id = wp_create_user($username, $random_password, $result['email']);
    }
    $user = new WP_User($id);
    $user->add_role('contributor');
    return $user;
  } else {
    return NULL;
  }
}
