<?php
/*
Plugin Name: OAuth REST API
Plugin URI: http://github.com/chingor13/oauth-rest
Description: REST API that uses the OAuth provided by the oauth-provider plugin
Author: Jeff Ching
Version: 0.1
Author URI: http://chingr.com

Copyright 2012 Jeff Ching (email : jeff@chingr.com)

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
*/

class OAuthRest {

  function install() {
    // Nothing to do...
  }

  function remove() {
    // Nothing to do...
  }

  function insert_rewrite_rules($rules) {
    $new_rules = array(
      '(api)/(.*)$' => 'index.php?oauth=api&request=$matches[2]'
    );
    return $new_rules + $rules;
  }

  function flush_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }

  function add_query_var($vars) {
    array_push($vars, 'request');
    return $vars;
  }

  public static function dispatch() {
    require_once 'lib/rest.inc.php';
    $router = new OAuthRestRouter();
    if($router->is_valid()) {
      $controller_class = ucfirst($router->controller)."Controller";
      if(class_exists($controller_class)) {
        $controller = new $controller_class($router);
        $action = $router->action;
        $results = $controller->$action();
        $formatter = new OAuthRestFormatter($results);
        $formatter->send();
      } else {
        die(OAuthRestFormatter::sendResponse(404));
      }
    } else {
      die(OAuthRestFormatter::sendResponse(404));
    }
  }

}

register_activation_hook(__FILE__, array('OAuthRest', 'install'));
register_deactivation_hook(__FILE__, array('OAuthRest', 'remove'));

add_filter('rewrite_rules_array', array('OAuthRest', 'insert_rewrite_rules'));
add_filter('query_vars', array('OAuthRest', 'add_query_var'));
add_filter('init', array('OAuthRest', 'flush_rewrite_rules'));

//add_action('wp', array('OAuthRest', 'dispatch'));

// OAuth integration
add_oauth_method('api', array('OAuthRest', 'dispatch'))
?>
