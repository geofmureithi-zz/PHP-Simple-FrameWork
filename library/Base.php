<?php
class Core {
    static $confArray;
	static $extendArray;
	static $superExtendArray;
	protected static $instance;
	public function app_etize($app){
	$args= func_get_args();
		switch($app){
			case "db":
				include "/PDO/FluentPDO.php";
				$pdo = new PDO($args[1],$args[2],$args[3]);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
				self::extend("db","FluentPDO", $pdo);
				break;
			case "img":
				include "/SimpleImage/Image.php";
				if($args[1])
				self::extend($args[1],"SimpleImage");
				break;
		}
		
	}
    public function read($name)
    {
        return self::$confArray[$name];
    }
    public function put($name, $value)
    {
        self::$confArray[$name] = $value;
    }
	public function extend($function, $handler, $args=null){
		
		self::$extendArray[$function] = new $handler($args);
	}
	public function __call($method, $args){
		return self::$extendArray[$method];
	}
	public function __get($name) {
      if (isset( self::$extendArray[$name] )) {
         return $this->$name();
      }
      //if there is no function registered under named "$name"
      //throwing Exception is by design better, as @scragar suggested
      throw new Exception("No function registered under named {$name}");
      //return NULL;
   }
   public function stock($name,$resource){
		switch($resource){
			case "Image" :
				$this->app_etize("img",$name);
			break;
		
		}
   }
	public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            $object =__CLASS__;
            self::$instance= new $object;
        }
        return self::$instance;
    }
}

//Thanks to Alto Router.
class Router extends Core {

	protected $routes = array();
	protected $namedRoutes = array();
	protected $basePath = '';
	protected $matchTypes = array(
		'i'  => '[0-9]++',
		'a'  => '[0-9A-Za-z]++',
		'h'  => '[0-9A-Fa-f]++',
		'*'  => '.+?',
		'**' => '.++',
		''   => '[^/\.]++'
	);
	public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            $object =__CLASS__;
            self::$instance= new $object;
        }
        return self::$instance;
    }
	/**
	  * Create router in one call from config.
	  *
	  * @param array $routes
	  * @param string $basePath
	  * @param array $matchTypes
	  */
	public function __construct( $routes = array(), $basePath = '', $matchTypes = array() ) {
		$this->setBasePath($basePath);
		$this->addMatchTypes($matchTypes);

		foreach( $routes as $route ) {
			call_user_func_array(array($this,'map'),$route);
		}
	}

	/**
	 * Set the base path.
	 * Useful if you are running your application from a subdirectory.
	 */
	public function setBasePath($basePath) {
		$this->basePath = $basePath;
	}

	/**
	 * Add named match types. It uses array_merge so keys can be overwritten.
	 *
	 * @param array $matchTypes The key is the name and the value is the regex.
	 */
	public function addMatchTypes($matchTypes) {
		$this->matchTypes = array_merge($this->matchTypes, $matchTypes);
	}

	/**
	 * Map a route to a target
	 *
	 * @param string $method One of 4 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PUT|DELETE)
	 * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
	 * @param mixed $target The target where this route should point to. Can be anything.
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 *
	 */
	public function map($method, $route, $target, $name = null) {

		$this->routes[] = array($method, $route, $target, $name);

		if($name) {
			if(isset($this->namedRoutes[$name])) {
				throw new \Exception("Can not redeclare route '{$name}'");
			} else {
				$this->namedRoutes[$name] = $route;
			}

		}

		return;
	}

	/**
	 * Reversed routing
	 *
	 * Generate the URL for a named route. Replace regexes with supplied parameters
	 *
	 * @param string $routeName The name of the route.
	 * @param array @params Associative array of parameters to replace placeholders with.
	 * @return string The URL of the route with named parameters in place.
	 */
	public function linkTo($routeName, array $params = array()) {

		// Check if named route exists
		if(!isset($this->namedRoutes[$routeName])) {
			throw new \Exception("Route '{$routeName}' does not exist.");
		}

		// Replace named parameters
		$route = $this->namedRoutes[$routeName];
		
		// prepend base path to route url again
		$url = $this->basePath . $route;

		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

			foreach($matches as $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if ($pre) {
					$block = substr($block, 1);
				}

				if(isset($params[$param])) {
					$url = str_replace($block, $params[$param], $url);
				} elseif ($optional) {
					$url = str_replace($pre . $block, '', $url);
				}
			}


		}

		return $url;
	}

	/**
	 * Match a given Request Url against stored routes
	 * @param string $requestUrl
	 * @param string $requestMethod
	 * @return array|boolean Array with route information on success, false on failure (no match).
	 */
	public function match($requestUrl = null, $requestMethod = null) {

		$params = array();
		$match = false;

		// set Request Url if it isn't passed as parameter
		if($requestUrl === null) {
			$requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		}

		// strip base path from request url
		$requestUrl = substr($requestUrl, strlen($this->basePath));

		// Strip query string (?a=b) from Request Url
		if (($strpos = strpos($requestUrl, '?')) !== false) {
			$requestUrl = substr($requestUrl, 0, $strpos);
		}

		// set Request Method if it isn't passed as a parameter
		if($requestMethod === null) {
			$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		}

		// Force request_order to be GP
		// http://www.mail-archive.com/internals@lists.php.net/msg33119.html
		$_REQUEST = array_merge($_GET, $_POST);

		foreach($this->routes as $handler) {
			list($method, $_route, $target, $name) = $handler;

			$methods = explode('|', $method);
			$method_match = false;

			// Check if request method matches. If not, abandon early. (CHEAP)
			foreach($methods as $method) {
				if (strcasecmp($requestMethod, $method) === 0) {
					$method_match = true;
					break;
				}
			}

			// Method did not match, continue to next route.
			if(!$method_match) continue;

			// Check for a wildcard (matches all)
			if ($_route === '*') {
				$match = true;
			} elseif (isset($_route[0]) && $_route[0] === '@') {
				$match = preg_match('`' . substr($_route, 1) . '`', $requestUrl, $params);
			} else {
				$route = null;
				$regex = false;
				$j = 0;
				$n = isset($_route[0]) ? $_route[0] : null;
				$i = 0;

				// Find the longest non-regex substring and match it against the URI
				while (true) {
					if (!isset($_route[$i])) {
						break;
					} elseif (false === $regex) {
						$c = $n;
						$regex = $c === '[' || $c === '(' || $c === '.';
						if (false === $regex && false !== isset($_route[$i+1])) {
							$n = $_route[$i + 1];
							$regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
						}
						if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
							continue 2;
						}
						$j++;
					}
					$route .= $_route[$i++];
				}

				$regex = $this->compileRoute($route);
				$match = preg_match($regex, $requestUrl, $params);
			}

			if(($match == true || $match > 0)) {

				if($params) {
					foreach($params as $key => $value) {
						if(is_numeric($key)) unset($params[$key]);
					}
				}

				return array(
					'target' => $target,
					'params' => $params,
					'name' => $name
				);
			}
		}
		return false;
	}

	/**
	 * Compile the regex for a given route (EXPENSIVE)
	 */
	private function compileRoute($route) {
		if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

			$matchTypes = $this->matchTypes;
			foreach ($matches as $match) {
				list($block, $pre, $type, $param, $optional) = $match;

				if (isset($matchTypes[$type])) {
					$type = $matchTypes[$type];
				}
				if ($pre === '.') {
					$pre = '\.';
				}

				//Older versions of PCRE require the 'P' in (?P<named>)
				$pattern = '(?:'
						. ($pre !== '' ? $pre : null)
						. '('
						. ($param !== '' ? "?P<$param>" : null)
						. $type
						. '))'
						. ($optional !== '' ? '?' : null);

				$route = str_replace($block, $pattern, $route);
			}

		}
		return "`^$route$`";
	}
}
class Base extends Router{
	public static function getInstance()
    {
        if (!isset(self::$instance))
        {
            $object =__CLASS__;
            self::$instance= new $object;
        }
        return self::$instance;
    }
	public function play(){
		$route= $this->match();
		$this->put('PARAMS',$route['params']);
		$this->put('TARGET',$route['target']);
		$target = $route['target'];
		if(is_string($target)){
		$sub= explode('#',$target);
			$handler= new $sub[0];
			$handler->$sub[1]($this,$route['params'],@$sub[2]);
		}
		if(is_object($target)){
			call_user_func($target, $this, $route['params'] );
		}
		
	}

}


return Base::getInstance();
