<?php
	
	include '../controller/config.php';
	include 'db.php';
	include 'standart.php';

	class Route extends UserConfig {

		public static $allRoutes = [];

		public static function get($route, $control) {
			return self::$allRoutes[$route] = $control.'@GET';
		}

		public static function post($route, $control) {
			return self::$allRoutes[$route] = $control.'@POST';
		}

	}

	// routes has written
	include '../route/url.php';

	// user request route
	$host = $_SERVER['HTTP_HOST'];
	$nowUrl = $_SERVER['REQUEST_URI'];

	// for XAMPP localhost route
	if ($host == 'localhost') {
		$nowUrl = explode('/', $nowUrl);
		unset($nowUrl[1]);
		$nowUrl = implode('/', $nowUrl);
	}

	// route variables
	$routeVariables;

	// controller class and function name from route
	$controller;

	// match or not indicator
	$match = false;

	// matching routes (all from written routes)
	foreach (Route::$allRoutes as $route => $control) {

		// explode route for matching
		$routePieces = explode('/', $route);
		$nowUrlPieces = explode('/', $nowUrl);

		// matching routes (pieces from written routes)
		// FYI : $nowUrlPieces[$key] == $routePieces[$key] 
		// * if $nowUrlPieces offset are available
		foreach ($routePieces as $key => $pieces) {
			
			// check route variables {..} existable
			if (preg_match('/\{[a-z0-9_]*\}/', $pieces)) {

				// set variable and value
				$pieces = preg_replace('/\{/', "", $pieces);
				$pieces = preg_replace('/\}/', "", $pieces);

				// check $nowUrl offset
				if (isset($nowUrlPieces[$key])) {
					$routeVariables[$pieces] = $nowUrlPieces[$key];
				}

				// set variable and value (raw string) on route to same string
				$routePieces[$key] = '(variable)';
				if (isset($nowUrlPieces[$key])) {
					if ($nowUrlPieces[$key] == '') {
						$nowUrlPieces[$key] = '(null)';
					} else {
						$nowUrlPieces[$key] = '(variable)';
					}
				}

			}
		}

		// implode for matching now route (url) and written route
		$routeFinal = implode('/', $routePieces);
		$nowUrlFinal = implode('/', $nowUrlPieces);

		// final matching route
		if ($routeFinal == $nowUrlFinal) {
			$match = true;
			$controller = $control;
			break;
		} else {
			$match = false;
		}

	}

	// connect to controller if match
	// FYI : File name and Class name must same!
	if ($match) {

		// explode controller string
		$controller = explode('@', $controller);
		$class = $controller[0];
		$function = $controller[1];
		$routeMethod = $controller[2];

		// check route variable existable
		if (isset($routeVariables)) {

			// function parameter
			$parameter = [];
			foreach ($routeVariables as $variable => $value) {
				array_push($parameter, $value);
			}

			// check method
			$nowMethod = $_SERVER['REQUEST_METHOD'];
			if ($nowMethod == $routeMethod) {

				// run controller with parameter
				include "../controller/$class.php";
				call_user_func_array([$class,$function], $parameter);

			} else {

				// error 500
				error('500');
			}

		} else {

			// check method
			$nowMethod = $_SERVER['REQUEST_METHOD'];
			if ($nowMethod == $routeMethod) {

				// run controller without parameter
				include "../controller/$class.php";
				call_user_func([$class,$function]);

			} else {

				// error 500
				error('500');
			}
		}

	} else {
		
		// error 404
		error('404');
	}

?>