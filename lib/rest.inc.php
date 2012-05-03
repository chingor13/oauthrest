<?php
require_once('jsonwrapper/jsonwrapper.php');
require_once('xmlwrapper/xmlwrapper.php');
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
    if(count($format_parts) != 2) {
      return;
    }
    $this->format = $format_parts[1];
    $path = $format_parts[0];

    // parse the params based on type
    switch($this->method) {
      case 'get':
        $this->params = $_GET;
        break;
      case 'post':
        $this->params = $_POST;
        break;
      case 'put':
        parse_str(file_get_contents('php://input'), $put_vars);
        $this->params = array_merge($_POST, $put_vars);
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
  public $results, $format;

  public function __construct($results, $format) {
    $this->results = $results;
    $this->format = $format;
  }

  public function to_xml() {
    // Build our XML Wrapper
    $xmlwrapper = new XMLWrapper('1.0', 'utf-8');
    // We want our output nice and tidy
    $xmlwrapper->formatOutput = true;
    $xmlwrapper->tag = "result";

    // Initialize our root element tag
    $root = $xmlwrapper->createElement("results");
    $root = $xmlwrapper->appendChild($root);

    $xmlwrapper->fromMixed($this->results, $root);

    return $xmlwrapper->saveXML();
  }

  public function to_json() {
    return json_encode($this->results);
  }

  public function to_s() {
    if($this->format == "xml") {
      return $this->to_xml();
    } else {
      return $this->to_json();
    }
  }

  public function content_type() {
    if($this->format == "xml") {
      return "application/xml";
    } else {
      return "application/json";
    }
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
    return $this->filter($this->db->get_results("SELECT * FROM " . $this->table() . " WHERE " . $this->where()));
  }

  public function show() {
    $sql = $this->db->prepare("SELECT * FROM " . $this->table() . " WHERE ID=%d" . " LIMIT 1", $this->params['id']);
    $result = $this->filter($this->db->get_results($sql));
    return $result[0];
  }

  public function create() {
    $resp = $this->db->insert($this->table(), $this->object_params());
    $result_id = $this->db->insert_id;
    $result = $this->filter($this->db->get_results("SELECT * FROM " . $this->table() . " WHERE ID=" . $result_id . " LIMIT 1"));
    return $result[0];
  }

  public function update() {
    $sql = $this->db->prepare("SELECT * FROM " . $this->table() . " WHERE ID=%d" . " LIMIT 1", $this->params['id']);
    $result = $this->filter($this->db->get_results($sql));
    if($result) {
      $resp = $this->db->insert($this->table(), $this->object_params(), array("id" => $this->params['id']));
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
      return $this->filter_result($result);
    }
  }

  protected function filter_result($result) {
    return get_object_vars($result);
  }

  protected function where() {
    return "1=1";
  }

  protected function object_params() {
    $object_params = $this->params[$this->object_name()];
    if(is_null($object_params)) {
      $object_params = array();
    }
    return $object_params;
  }

  protected function object_name() {
    return "default";
  }

}

class PostsController extends OAuthRestController {
  protected function table() {
    return $this->db->posts;
  }

  protected function where() {
    return "ID > 0 AND post_type LIKE 'post'";
  }

  protected function filter_result($result) {
    $result = get_object_vars($result);
    unset($result['post_password']);
    unset($result['menu_order']);
    $result['permalink'] = get_permalink($result['ID']);
    return $result;
  }

  protected function object_name() {
    return "post";
  }
}

class CommentsController extends OAuthRestController {
  protected function table() {
    return $this->db->comments;
  }

  protected function where() {
    return "ID > 0";
  }

  protected function object_name() {
    return "comment";
  }
}

?>
