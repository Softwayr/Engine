<?php

/*
	SOFTWAYR ENGINE :: ROUTE CLASS
*/

namespace Softwayr\Engine;

class Route {

	private static $ROUTES = [];

	/**
	 *	Creates and registers routes based on information provided.
	 *
	 *	@param string $method The request method the route will accept. (GET|POST)
	 *	@param string $path The path without leading slash. ("some/page")
	 *	@param callable $action A callable action to perform with route is accessed.
	 *	@param array $options Optional array containing additional information to go with the route.
	 */
	public static function create( string $method, string $path, callable $action, array $options = [] ) {
		// Check for valid method and default to GET.
		if( strtoupper( $method ) == "GET" || strtoupper( $method ) == "POST" )
			$options['method'] = strtoupper( $method );
		else
			$options['method'] = "GET";

		// Check path contains valid characters, otherwise don't register the route.
		// Valid characters are all letters and numbers, dashes, underscores, forward-slashes, and curly brackets.
		if( preg_match( "/^[\w\d\-\.\/\{\}]+$/", $path ) ) {
			// Path is valid, set it as a route option.
			$options['path'] = $path;

			// Find all placeholders prefixed and suffixed with { and }.
			preg_match('#\{(.*?)\}#', $path, $curly_brackets_match);
			// Did we find any placeholders?
			if( count( $curly_brackets_match ) > 0 ) {
				// Split the path of the route into segments based on forward-slashes.
				$options['path_parts'] = explode("/", $path);
				// For all the different path segments containing placeholders, add to array as part of route.
				for( $cbm = 1; $cbm < count( $curly_brackets_match ); $cbm++ ) {
					$options['path_params'][] = $curly_brackets_match[$cbm];
				}
			}

			// Register the callable action.
			$options['action'] = $action;

			// Register the route by adding it to the static routes array.
			Route::$ROUTES[] = $options;
		}
	}

	/**
	 *	Alias of create() for GET routes.
	 *
	 *	@param string $path The path without leading slash. ("some/page")
	 *	@param callable $action A callable action to perform with route is accessed.
	 *	@param array $options Optional array containing additional information to go with the route.
	 */
	public static function get( string $path, callable $action, array $options = [] ) { Route::create("GET", $path, $action, $options); }

	/**
	 *	Alias of create() for POST routes.
	 *
	 *	@param string $path The path without leading slash. ("some/page")
	 *	@param callable $action A callable action to perform with route is accessed.
	 *	@param array $options Optional array containing additional information to go with the route.
	 */
	public static function post( string $path, callable $action, array $options = [] ) { Route::create("POST", $path, $action, $options); }

	/**
	 *	Find registered route that matches the given options or find all registered routes.
	 *	Todo: Find multiple matched routes.
	 *
	 *	@param array $options An optional array containing options to match routes against.
	 *	@return array An array containing all matched routes or all routes.
	 */
	public static function find( array $options = [] ) {
		// Do we have any options to match against?
		if( count( $options ) > 0 ) {
			// For each route, match the provided options.
			foreach( Route::$ROUTES as $route ) {
				// Keep track of matches found.
				$matches = 0;
				// For each option, check for existance within route, ensuring same value is matched.
				foreach( $options as $option_key => $option_value ) {
					// If match is found, track it.
					if( array_key_exists( $option_key, $route ) && $route[ $option_key ] == $option_value ) {
						$matches++;
					}
				}

				// Have we matched the same number of options requested?
				if( count( $options ) == $matches )
					// Return the matched route.
					return $route;
			}
		}
		// No options provided or matched, return all registered routes.
		return Route::$ROUTES;
	}

	/**
	 *	Dispatch a request to a route.
	 */
	public static function dispatch() {
		// Find the requested host.
		$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : "";
		// Find the request method.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == ("GET" || "POST") ? $_SERVER['REQUEST_METHOD'] : "GET";
		// Find the path provided by mod_rewrite in .htaccess file.
		$path = isset( $_GET['path'] ) ? $_GET['path'] : "";
		// Prepare array for path parameters later.
		$path_params = [];

		// Find all registered routes.
		$routes = Route::find();

		// Prepare array for requested route later.
		$req_route = [];

		// Loop through all registered routes.
		foreach( $routes as $route ) {
			// Check if route requires parameters
			if( is_array( $route ) && array_key_exists( "path_parts", $route ) && array_key_exists( "path_params", $route ) ) {
				// Find the path parameters for this route.
				$reg_path_params = $route['path_params'];
				// Find the path parts for this route.
				$reg_path_parts = $route['path_parts'];
				// Split the requested path into segments based on forward-slashes.
				$req_path_parts = explode("/", $path);
				// Prepare array for requested path parameters later.
				$req_path_params = [];
				// Keep track of path parameters matched.
				$req_path_param = 0;

				// Do we have the same number of registered path parts compared to requested path parts?
				if( count( $reg_path_parts ) == count( $req_path_parts ) ) {
					// For each registered path part
					for( $rpp = 0; $rpp < count( $reg_path_parts ); $rpp++ ) {
						// Find placeholders
						preg_match( '/(\{[a-zA-Z]*\})/', $reg_path_parts[$rpp], $matches );
						// Are there any placeholders?
						if( count( $matches ) > 0 ) {
							// For each placeholder
							for( $m = 1; $m < count( $matches ); $m++ ) {
								// Replace the placeholder with parameter provided in the requested path.
								$req_path_params[] = $req_path_parts[ $rpp ];
								$req_path_parts[$rpp] = preg_replace('/(\{[a-zA-Z]*\})/', $req_path_parts[$rpp], $reg_path_parts[$rpp]);
							}
						}
					}

					// Prepare to store updated requested path.
					$req_path = "";
					// for all the requested path parts
					for( $req_path_part = 0; $req_path_part < count( $req_path_parts ); $req_path_part++ ) {
						// Merge into updated path.
						$req_path .= $reg_path_parts[$req_path_part];

						// If not the last path part, insert a forward-slash.
						if( $req_path_part < count( $req_path_parts ) -1 ) {
							$req_path .= "/";
						}
					}

					// Is the updated requested path the same as the current route path?
					if( $req_path == $route['path'] ) {
						// Let's assume this is the route requested, save it for later.
						$req_route = $route;
					}
				}
			// Route does not require parameters, perform a simple path match.
			} else if( $path && is_array( $route ) && $route['path'] == $path ) {
				// Let's assume this is the route requested, save it for later.
				$req_route = $route;
			}

			// Have we found the requested route?
			if( is_array( $req_route ) && array_key_exists('action', $req_route) ) {
				// Call the route's callable action.
				call_user_func_array( $req_route['action'], $req_path_params );
				// All done, now exit.
				exit;
			}
		}

		// Seriously? We still have not found a route? Only one thing we can do.
		// Output header 404 not found status.
		header("HTTP/1.0 404 Not Found");
		// In fact, let's try to find a route one more time.
		// Check for "not_found" route.
		$not_found_route = Route::find(['path' => 'not_found']);
		// Did we find it?
		if( array_key_exists( 'action', $not_found_route ) ) {
			// Finally! Let's call the route's callable action.
			call_user_func( $not_found_route['action'] );
		} else {
			// Still not found it? Oh well, we tried.
			echo "Not Found (404)";
		}
	}

}
