<?php
require_once('jsonwrapper/jsonwrapper.php');
class OAuthRestRouter {
  public $controller, $action, $params, $format, $method;

  public function __construct() {
    global $wp_query;

    $path = $wp_query->query_vars['request'];
    $this->method = strtolower($_SERVER['REQUEST_METHOD']);
    if($this->method == "post" && !empty($_REQUEST['_method'])) {
      $this->method = strtolower($_REQUEST['_method']);
    }

    // parse the format
    $format_parts = explode(".", $path);
    $this->format = $format_parts[1];
    $path = $format_parts[0];

    // parse the params based on type
    switch($this->method) {
      case 'get':
        $this->params = $_GET;
        break;
      case 'post':
      case 'put':
        $this->params = $_POST;
        $data = file_get_contents('php://input');
        if(!empty($data)) {
          $json_data = (array) json_decode($data);
          if(!empty($json_data)) {
            $this->params = array_merge($this->params, $json_data);
          } else {
            parse_str($data, $json_data);
            $this->params = array_merge($this->params, $json_data);
          }
        }
        break;
      default:
        $this->params = array();
    }

    // parse the controller/action from the path
    $path_parts = explode("/", $path);
    $this->controller = $path_parts[0];
    if(count($path_parts) == 1) {
      if($this->method == "post") {
        $this->action = "create";
      } else {
        $this->action = "index";
      }
    } else {
      $this->params['id'] = $path_parts[1];
      if($this->method == "put") {
        $this->action = "update";
      } else {
        $this->action = "show";
      }
    }
  }

  public function is_valid() {
    return !empty($this->controller) && !empty($this->action);
  }
}

class OAuthRestFormatter {
  public $results;

  public function __construct($results) {
    $this->results = $results;
  }

  public function to_json() {
    return json_encode($this->results);
  }

  public function to_s() {
    return $this->to_json();
  }

  public function content_type() {
    return "application/json";
  }

  public function send() {
    OAuthRestFormatter::sendResponse(200, $this->to_s(), $this->content_type());
  }

  public static function sendResponse($status, $body = '', $content_type = 'text/html') {
    $status_header = 'HTTP/1.1 ' . $status . ' ' . self::getStatusCodeMessage ( $status );
    // set the status
    header ( $status_header );
    // set the content type
    header ( 'Content-Type: ' . $content_type );
    // pages with body are easy
    if ($body != '') {
      // send the body
      echo $body;
      exit ();
    } else {
      // we need to create the body if none is passed
      // create some body messages
      $message = '';

      // this is purely optional, but makes the pages a little nicer to read
      // for your users.  Since you won't likely send a lot of different status codes,
      // this also shouldn't be too ponderous to maintain
      switch ($status) {
        case 401 :
          $message = 'You must be authorized to view this page.';
          break;
        case 404 :
          $message = 'The requested URL ' . $_SERVER ['REQUEST_URI'] . ' was not found.';
          break;
        case 500 :
          $message = 'The server encountered an error processing your request.';
          break;
        case 501 :
          $message = 'The requested method is not implemented.';
          break;
      }

      // servers don't always have a signature turned on (this is an apache directive "ServerSignature On")
      $signature = ($_SERVER ['SERVER_SIGNATURE'] == '') ? $_SERVER ['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER ['SERVER_NAME'] . ' Port ' . $_SERVER ['SERVER_PORT'] : $_SERVER ['SERVER_SIGNATURE'];

      // this should be templatized in a real-world solution
      $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
            <html>
              <head>
                <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                <title>' . $status . ' ' . self::getStatusCodeMessage ( $status ) . '</title>
              </head>
              <body>
                <h1>' . self::getStatusCodeMessage ( $status ) . '</h1>
                <p>' . $message . '</p>
                <hr />
                <address>' . $signature . '</address>
              </body>
            </html>';
      echo $body;
      exit ();
    }
  }

	public static function getStatusCodeMessage($status) {
		// these could be stored in a .ini file and loaded
		// via parse_ini_file()... however, this will suffice
		// for an example
		$codes = Array (100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => '(Unused)', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported' );
		
		return (isset ( $codes [$status] )) ? $codes [$status] : '';
	}
}

abstract class OAuthRestController {
  public $params;
  protected $db;

  public function __construct($router) {
    global $wpdb;
    $this->params = $router->params;
    $this->db = $wpdb;
  }

  public function index() {
    $sql = "SELECT * FROM " . $this->table() . " WHERE " . $this->where();
    return $this->filter($this->db->get_results($sql));
  }

  public function show() {
    $sql = $this->db->prepare("SELECT * FROM " . $this->table() . " WHERE " . $this->primary_key() . "=%d" . " LIMIT 1", $this->params['id']);
    $result = $this->filter($this->db->get_results($sql));
    return $result[0];
  }

  public function create() {
    $resp = $this->db->insert($this->table(), $this->filter_incoming_data($this->object_params(), true));
    $result_id = $this->db->insert_id;
    $result = $this->filter($this->db->get_results("SELECT * FROM " . $this->table() . " WHERE " . $this->primary_key() . "=" . $result_id . " LIMIT 1"));
    return $result[0];
  }

  public function update() {
    $sql = $this->db->prepare("SELECT * FROM " . $this->table() . " WHERE " . $this->primary_key() . "=%d" . " LIMIT 1", $this->params['id']);
    $result = $this->filter($this->db->get_results($sql));
    if($result) {
      $resp = $this->db->update($this->table(), $this->filter_incoming_data($this->object_params()), array($this->primary_key() => $this->params['id']));
    }
    $result = $this->filter($this->db->get_results($sql));
    return $result[0];
  }

  protected function table() {
    echo "must specify table";
    exit();
  }

  protected function filter($results) {
    if(is_array($results)) {
      foreach($results as $i => $result) {
        $results[$i] = $this->filter_result($result);
      }
      return $results;
    } else {
      return $this->filter_result($results);
    }
  }

  protected function filter_result($result) {
    return get_object_vars($result);
  }

  protected function filter_incoming_data($data) {
    return $data;
  }

  protected function primary_key() {
    return "ID";
  }

  protected function where() {
    return "1=1";
  }

  protected function object_params() {
    $object_params = $this->params[$this->object_name()];
    if(is_null($object_params)) {
      $object_params = array();
    }
    return (array) $object_params;
  }

  protected function object_name() {
    return "default";
  }

}

class PostsController extends OAuthRestController {
  public function index() {
    // use the built-in methods to fetch blog posts
    return $this->filter(get_posts());
  }

  public function show() {
    return $this->filter(get_post($this->params['id']));
  }

  public function create() {
    $post_id = wp_insert_post($this->filter_incoming_data($this->object_params(), true));
    return $this->filter(get_post($post_id));
  }

  public function update() {
    $post_id = wp_update_post($this->filter_incoming_data($this->object_params()));
    return $this->filter(get_post($post_id));
  }

  protected function filter_result($result) {
    $result = get_object_vars($result);
    $id = $result['ID'];
    unset($result['ID']);
    $result['id'] = $id;
    unset($result['post_password']);
    unset($result['menu_order']);
    $result['permalink'] = get_permalink($result['id']);

    # trackback count
    $sql = $this->db->prepare("SELECT COUNT(*) AS `c` FROM " . $this->db->comments . " WHERE comment_type='trackback'");
    $r = $this->db->get_results($sql);
    $result['trackback_count'] = $r[0]->c;
    return $result;
  }

  protected function filter_incoming_data($data, $inserting = false) {
    $d = array_intersect_key($data, array("post_title" => null, "post_content" => null, "post_status" => null));
    if(!$inserting && $this->params["id"]) {
      $d["id"] = $this->params["id"];
    }
    $d["post_type"] = "post";
    $d["post_author"] = get_current_user_id();
    return $d;
  }

  protected function object_name() {
    return "post";
  }
}

class CommentsController extends OAuthRestController {
  public function index() {
    return $this->filter(get_comments(array("post_id" => $this->params["post_id"])));
  }

  public function show() {
    return $this->filter(get_comment($this->params["id"]));
  }

  public function create() {
    $comment_id = wp_insert_comment($this->filter_incoming_data($this->object_params(), true));
    return $this->filter(get_comment($comment_id));
  }

  public function update() {
    wp_update_comment($this->filter_incoming_data($this->object_params()));
    return $this->filter(get_comment($this->params["id"]));
  }

  protected function filter_result($result) {
    $result = get_object_vars($result);
    $result['id'] = $result['comment_ID'];
    unset($result['comment_ID']);

    $result['post_id'] = $result['comment_post_ID'];
    unset($result['comment_post_ID']);

    return $result;
  }

  protected function filter_incoming_data($data, $inserting = false) {
    $d = array_intersect_key($data, array("comment_content" => null, "comment_parent" => null));
    $d["comment_post_ID"] = $data["post_id"];
    if($inserting) {
      // add my user data
      $user_info = get_userdata(get_current_user_id());
      $d["comment_date"] = $d["comment_date_gmt"] = date("Y-m-d H:i:s");
      $d["comment_author"] = $user_info->display_name;
      $d["comment_author_email"] = $user_info->user_email;
      $d["comment_author_url"] = $user_info->user_url;
    } elseif($this->params["id"]) {
      $d["comment_ID"] = $this->params["id"];
    }
    return $d;
  }

  protected function primary_key() {
    return "comment_ID";
  }

  protected function object_name() {
    return "comment";
  }
}

?>
