<?php
namespace BrownUniversity\DOC\Controller ;
/**
 * @package    Kohana-Based Web Services
 * @author     Christopher Keith <christopher_keith@brown.edu>
 */
use BrownUniversity\DOC\View;
use Kohana\Controller;
use Kohana\Inflector;
use Kohana\Kohana;
use Kohana\ORM\ORM;
use Kohana\Request;
use Kohana\KohanaException;

defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Rest Controller Class
 *
 * Used to abstract the majority of common interface required for publishing
 * a web service.
 */
class Rest extends Controller {

	const AUTH_NONE = 'authenticate_none' ;
	const AUTH_HMAC = 'authenticate_hmac' ;
	const AUTH_HTTP_BASIC = 'authenticate_http_basic' ;


	/**
	 * type of content accepted by requesting resource
	 */
	protected $accept_type;

	/**
	 * Content-types accepted by this service.  Keys represent content-
	 * types from HTTP accept-type header and Values represent the path
	 * to the appropriate view file. Possible values are commented out
	 * here. Proper values should be set in individual controllers.
	 *
	 */
	protected $definitions = array(
//		'application/json' => 'json',
//		'text/xml' => 'version_0.1/xml',
//		'text/xml;version=0.1' => 'version_0.1/xml',
//		'vnd.edu.brown.college.advisors+xml;version=0.1' => 'version_0.1/xml'
	);

	/**
	 * Name of data model
	 */
	protected $model = NULL;

	/**
	 * List of HTTP Status Codes
	 * @todo Replace this with functionality in DOC_Util_HTTP
	 */
	private $status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);

	/**
	 *
	 * @var array HTTP Verbs supported by this instance
	 * @todo Comment these out and have them implemented only in the application's base class that extends this.
	 */
	protected $supported_methods = array(
		'HEAD',
		'DELETE',
		'GET',
		'POST',
		'PUT',
	);

	/**
	 *
	 * @var array List of IP addresses allowed to access this service
	 * @todo Comment these out and have them implemented only in the application's base class that extends this.
	 */
	protected $valid_ips = array(
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
		// ***REMOVED***
	);

	protected $authentication_method = self::AUTH_NONE ;

	/**
	 * Folder containing the views associated with content-types
	 */
	protected $view_folder = NULL;

	/**
	 * This was originally the constructor-- Kohana 3 requires a before() call instead.
	 */
	public function before() {
		parent::before();
		$this->auto_render = FALSE;
		$this->validate_ip();

		// Note that the view_folder and model handling here assumes that REST controllers
		// will always be Controller_REST_[Modelname]. This may not be true 100% of the
		// time, though exceptions would be handled by child classes.

		if ($this->view_folder === NULL) {
			$this->view_folder = strtolower(substr(get_class($this), 16));
		}

		if ($this->model === NULL) {
			$this->model = Inflector::singular($this->view_folder);
		}

		$this->process_accept_type();

		if ( ! $this->authenticate_request()) {
			$this->send_response(401);
		}

		// this was originally in the index() method

		if ( ! array_key_exists($this->accept_type, $this->definitions)) {
			$this->send_response(415);
		}

		if (array_search($this->request->method(), $this->supported_methods) === FALSE) {
			$this->send_response(405);
		}

		$method = strtolower( $this->request->method() ) ;

		$controller_method = "process_{$method}_".$this->request->action() ;
		if( !method_exists( $this, $controller_method )) {
			$controller_method = "process_{$method}" ;
		}

		$result = $this->$controller_method() ;

		$this->send_response($result['status'], $result['headers'], $result['payload']);



	}

	/**
	 * Method for authenticating a request. This calls the method defined in the
	 * authentication_method property, or fails if none is defined.
	 *
	 * @return boolean
	 */
	protected function authenticate_request() {
		if( !empty( $this->authentication_method )) {
			$auth_method = $this->authentication_method ;
		} else {
			die( 'No authentication method specified.') ;
		}
		return $this->$auth_method() ;
	}

	/**
	 * Use this when the service in question does not require authentication.
	 *
	 * @return boolean
	 */
	protected function authenticate_none() {
		return TRUE ;
	}

    /**
     * Authentication using HTTP Basic. The username and password must be defined
     * in the database.
     *
     * @return boolean
     * @todo Update ORM settings to pull from a config variable so that this can be more generic?
     * @throws \Kohana\KohanaException
     */
	protected function authenticate_http_basic() {
		$_output = FALSE ;

		$username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
		$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
		if (($username !== NULL) && ($password !== NULL)) {
			$key = ORM::factory('key')
				->where('id', $username)
				->where('secret', $password)
				->find();
			$_output = $key->loaded;
		}

		return $_output ;
	}

    /**
     * Determines whether or not a request is valid by calculating a HMAC
     * signature from the key/secret pair.  The key is sent as the username
     * portion of the HTTP Basic Authentication request and the signature is
     * sent as the password.  This system rebuilds the signature and compares
     * to that received in the request.  If they match, the request is valid.
     *
     * @return boolean
     * @todo Update ORM settings to pull from a config variable so that this can be more generic?
     * @throws \Kohana\KohanaException
     */
	protected function authenticate_hmac() {
		/**
		 * Initial HMAC implementation
		 */
		$_output = FALSE;
		$key = NULL;
		$signature = NULL;
		$script_url = $this->get_script_url();
		$script_url = substr($script_url, 1, strlen($script_url) - 1);
		$url = explode('/', $script_url);
		$uri = $_SERVER['SCRIPT_URI'];
		$resource = $url[1];

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			$key = $_SERVER['PHP_AUTH_USER'];
		}
		if (isset($_SERVER['PHP_AUTH_PW'])) {
			$signature = $_SERVER['PHP_AUTH_PW'];
		}
		if (($key != NULL) && ($signature != NULL)) {
			$restacl = ORM::factory('restacl')
				->where('key_id', $key)
				->where('resource', $resource)
				->where('method', strtoupper(Request::method()))
				->find();
			if ($restacl->loaded) {
				// Rebuild Signature
				$new_signature = hash_hmac('md5', $uri . strtoupper(Request::method()), $restacl->key->secret);
				if (strcmp($signature, $new_signature) === 0) {
					$_output = TRUE;
				}
			}
		}

		return $_output ;
	}

	/**
	 * Process requested HTTP accept types. This will give preference to the query
	 * string if it contains a mimeType and a version. If these are missing, it
	 * will use the $_SERVER[ 'HTTP_ACCEPT' ] value.
	 *
	 * @todo Create a way to more flexibly set a default accept_type.
	 */
	final protected function process_accept_type()
	{
		if( $this->request->query('mimeType') != NULL ) {
			$raw = $this->request->query('mimeType') ;
			if( $this->request->query('version') != NULL ) {
				$raw .= ';version=' . $this->request->query('version') ;
			}
		} else {
			$raw = (isset($_SERVER['HTTP_ACCEPT'])) ? $_SERVER['HTTP_ACCEPT'] : NULL;
		}

		$raw_array = explode(';', $raw);
		$types = explode(',', $raw_array[0]);
		if (count($types) == 1)
		{
			$this->accept_type = $raw;
		}
		else
		{
			if (array_search('text/html', $types) !== FALSE) {
				$this->accept_type = 'text/html';
			} else {
				$this->accept_type = $types[0];
			}
		}
	}

	/**
	 * Process requests to this URI with the DELETE verb
	 *
	 */
	protected function process_delete()
	{
		return array(
			'status'  => 501,
			'headers' => NULL,
			'payload' => NULL,
		);
	}

    /**
     * Process requests to this URI with the GET verb
     *
     * @params mixed parameters after the controller in the URI
     * @todo Either modify this to make use of the new get_payload method or strip it down to the 501 settings we have for the others.
     * @throws \Kohana\KohanaException
     */
	protected function process_get() {
		$headers = array("Content-Type: {$this->accept_type}");
		$payload = NULL;

		$params_array = $this->request->param();

		if ($params_array == NULL) {
			$status = 200;
			$payload = $this->get_payload( ORM::factory($this->model)->find_all() ) ;
		} else {
			$status = 404;
			// If ids are numeric, get by id, else get by slug
			if( $this->request->param('id') != NULL ) {
				$items = ORM::factory($this->model)
					->where('id', '=', $this->request->param('id'))
					->find_all();
			} elseif( $this->request->param('slug') != NULL ) {
				$items = ORM::factory($this->model)
					->where('slug', '=', $this->request->param('slug'))
					->find_all();
			}
			if ($items->count() > 0) {
				$status = 200;
				$payload = $this->get_payload( $items ) ;
			}
		}
		return array(
			'status'  => $status,
			'headers' => $headers,
			'payload' => $payload
		);

	}

	/**
	 * Process requests to this URI with the POST verb
	 *
	 */
	protected function process_post()
	{
		return array(
			'status'  => 501,
			'headers' => NULL,
			'payload' => NULL,
		);
	}

	/**
	 * Process requests to this URI with the PUT verb
	 *
	 */
	protected function process_put()
	{
		return array(
			'status'  => 501,
			'headers' => NULL,
			'payload' => NULL,
		);
	}

	/**
	 * Process search requests.  Overload in child controllers to add
	 * functionality
	 */
	protected function search($query_string)
	{
		return array(
			'status'  => 501,
			'headers' => NULL,
			'payload' => NULL,
		);
	}

    /**
     * Generate and return the payload based on the content type and view file.
     * Note that the view file used can be in multiple places in the hierarchy,
     * which is organized first by content type and second by controller/action.
     * This will always use the most specific view file it can find.
     *
     * @param mixed $data
     * @param array $options
     * @return string
     * @throws \Exception
     * @throws \Kohana\View\ViewException
     */
	protected function get_payload( $data, $options = array() ) {
		$view_root = 'rest/' ;
		$mime_path = $this->definitions[ $this->accept_type ] ;

		// there may be a more clever way to do this...
		$view_file = $view_root . $mime_path . '/' . $this->request->controller().'/'.$this->request->action() . '/output' ;
		if( !Kohana::find_file('views', $view_file)) {
			$view_file = $view_root . $mime_path . '/' . $this->request->controller(). '/output' ;
			if( !Kohana::find_file( 'views', $view_file )) {
				$view_file = $view_root . $mime_path . '/output' ;
				if( !Kohana::find_file( 'views', $view_file )) {
					die('unable to locate view file') ;
				}
			}
		}

		$view = View::factory( $view_file ) ;
		$view->data = $data ;
		$view->options = $options ;
		return $view->render() ;

	}

    /**
     * Send response message to requester
     *
     * @param      $status
     * @param null $headers
     * @param null $payload
     * @throws \Exception
     * @throws \Kohana\View\ViewException
     */
	final protected function send_response($status, $headers = NULL, $payload = NULL)
	{
		header('HTTP/1.1 ' . $status . ' ' . $this->status_codes[$status]);
		header('Cache-Control: no-cache, must-revalidate') ;
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT') ;
		
		if ($headers === NULL) {
			$headers = array("Content-Type: {$this->accept_type}");
		} else if ( ! is_array($headers)) {
			$headers = array($headers);
		}

		foreach ($headers as $header) {
			header($header);
		}

		if ($status != 200) {
			if ($status == 405) {
				header('Allow: ' . implode(', ', $this->supported_methods));
			}
			$output_type = 'xml';
			if (strpos($this->accept_type, 'json') !== FALSE) {
				$output_type = 'json';
			}
			if (strpos($this->accept_type, 'html') !== FALSE) {
				$output_type = 'html';
			}

			$view = View::factory( 'rest/error' ) ;

			$view->code = $status;
			$view->message = $payload;//$this->status_codes[$status];
			$view->output_type = $output_type;

			print( $view->render() ) ;

		} else if ($payload !== NULL) {
			header('Content-length: '.strlen($payload)) ;
			echo $payload;
		}
		exit;
	}

	/**
	 * Validate the source IP address of the request with the list of IPs
	 * allowed to access this instance.
	 */
	final private function validate_ip()
	{
		if( count( $this->valid_ips ) > 0 ) {
			$source_ip = $_SERVER['REMOTE_ADDR'];
			if (array_search($source_ip, $this->valid_ips) === FALSE) {
				$this->send_response(401);
			}
		}
	}

	/**
	 * Determine script URL, based on the first appropriate $_SERVER variable
	 * we can find.
	 * @return string
	 * @throws \Kohana\KohanaException
	 */
    protected function get_script_url()
    {
        $script_url = NULL;

        if (!empty($_SERVER['SCRIPT_URL']))
            $script_url = $_SERVER['SCRIPT_URL'];

        elseif (!empty($_SERVER['REDIRECT_URL']))
            $script_url = $_SERVER['REDIRECT_URL'];

        elseif (!empty($_SERVER['REQUEST_URI'])) {
            $p = parse_url($_SERVER['REQUEST_URI']);
            $script_url = $p['path'];
        } else {
            throw new KohanaException('Could not determine script URL.') ;
        }

        Kohana::$log->add(Log::DEBUG, "SCRIPT_URL = {$script_url}") ;

        return $script_url;

    }
} // End Rest Controller