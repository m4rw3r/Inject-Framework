<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

/**
 * Object returned by the router's match() method, used to represent a route
 * before it is compiled and/or cached.
 * 
 * Syntax for the patterns is the same as Ruby on Rails 3.0
 */
class Mapping
{
	/**
	 * The name of this route if it is named.
	 * 
	 * @var string
	 */
	protected $name = null;
	
	/**
	 * The input pattern.
	 * 
	 * @var string
	 */
	protected $path_pattern = '';
	
	/**
	 * Regular expression pattern fragments for the pattern.
	 * 
	 * @var array(string => string)
	 */
	protected $regex_fragments = array();
	
	/**
	 * Options to merge with matches from the pattern and return to the router.
	 *
	 * @var array(string => string)
	 */
	protected $options = array();
	
	/**
	 * The destination handler.
	 * 
	 * @var array(mixed)
	 */
	protected $to_arr = array(null);
	
	/**
	 * A list of accepted HTTP request methods, empty equals all.
	 * 
	 * @var array
	 */
	protected $via = array();
	
	/**
	 * The special regex constraints for certain captures, used for compilation.
	 * 
	 * @var array(string => string)
	 */
	protected $constraints = array();
	
	/**
	 * The named regex captures in $constraints which should carry over to the
	 * route parameters, specified by user.
	 * 
	 * @var array(string)
	 */
	protected $constraint_captures = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Resets the name of this route, to prevent multiple routes with the same name.
	 * 
	 * @return \Inject\Web\Route\Generator\Mapping
	 */
	public function __clone()
	{
		$this->name = null;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Specifies a pattern to match to the PATH_INFO key of the $env variable.
	 * 
	 * The pattern is constructed by three types of tokens:
	 *   Capture:
	 *     A colon (:) followed by one or more word characters
	 *     ([a-zA-Z0-9_]+), the characters following the colon is interpreted
	 *     as the name of the capture.
	 *     
	 *     This is a capture which by default will match anything from the
	 *     PATH_INFO until the end of the string or until a slash ("/") is
	 *     encountered.
	 *     The value matched will be stored in a parameter array with the
	 *     name of the capture which is reachable by all following middleware,
	 *     controllers and actions.
	 *     
	 *     Example:
	 *      :id, :user_id, :this_is_a_long_capture_name
	 *   
	 *   Not-limited capture:
	 *     A star (*) followed by one or more word characters ([a-zA-Z0-9_]+).
	 *     
	 *     This behaves the same as a normal capture except that it does not
	 *     stop matching at a slash ("/") but will continue until it has
	 *     captured as much as possible.
	 *     
	 *     Example:
	 *       *url, *extra_part_of_path_info
	 *   
	 *   Optional parts:
	 *     Optional parts of the pattern is a part surrounded by parenthesis
	 *     ("(", ")").
	 *     
	 *     The captures and literals within the optional part will only be
	 *     matched if it is possible.
	 *     
	 *     Example:
	 *       (id/:id)   will only match if the url contains "id/" followed by
	 *                  another url segment or nothing at all
	 *       (id/):id   :id will always be matched, but the "id/" part is
	 *                  optional
	 *   
	 *   Literal:
	 *     Literals are all the other parts of the pattern, and they will
	 *     be matched as they are.
	 *     
	 *     If you want to match a colon (":"), star ("*") or parenthesis
	 *     ("(", ")") in a literal match, prefix them with the backslash.
	 *     
	 *     Example:
	 *       some/literal_part/of/path
	 *       escaped\:colon
	 * 
	 * Example patterns: 
	 *   'user/list'      will match "user/list".
	 *   'user/:user_id'  will match "user/" followed by any non-empty path
	 *                    segment which will be stored under the "user_id"
	 *                    parameter.
	 *   'user(/:id)'     will match "user" followed by an optional "/"
	 *                    and then any non-empty path segment stored in "id"
	 *   'user\::id'      will match "user:" followed by a non-empty path
	 *                    segment.
	 * 
	 * To add constraints to the parameter captures, you can specify those
	 * as regular expression fragments in the $regex_patterns array.
	 * The key is the same as the name of the parameter capture and the
	 * value is the regular expression fragment.
	 * 
	 * Example patterns with custom constraints:
	 *   'user/:id', array('id' => '\d+')
	 *     Will match "user/" followed by any digit
	 *   'archive/:year/:month', array('year' => '\d{4}, 'month' => '\d{2}')
	 *     Will match "archive/" followed by a 4 digit number and then a
	 *     two digit number
	 * 
	 * @param  string
	 * @param  array(string => regex_fragment)  List of regular expression
	 *                fragments used for the specified captures
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function path($pattern, array $regex_patterns = array())
	{
		// Normalize the pattern
		strpos($pattern, '/') === 0 OR $pattern = '/'.$pattern;
		
		$this->path_pattern     = $this->path_pattern.$pattern;
		$this->regex_fragments = array_merge($this->regex_fragments, $regex_patterns);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the destination for this route.
	 * 
	 * Possible destinations:
	 * 
	 * - Controller and action:
	 *     'controller_name#action'
	 *     '\Controller\Class\Name#action'
	 *     
	 *     Routes to a specific controller and action, if the specified
	 *     controller is not an existing class, the controller class
	 *     is decided by the associated Engine's getAvailableControllers()
	 *     method, which is usually just the lowercase class+namespace name
	 *     of the controller which is following the "Controller\" namespace.
	 *     
	 *     Example:
	 *     'foo#lol' => \MyApp\Controller\Foo->lolAction()
	 *     '\AnotherPackage\SomeController#test' => \AnotherPackage\SomeController->testAction()
	 * 
	 * - Controller (action decided by pattern):
	 *     'controller_name#'
	 *     '\Controller\Class\Name#'
	 *     
	 *     Routes to a specific controller, if the specified
	 *     controller is not an existing class, the controller class
	 *     is decided by the associated Engine's getAvailableControllers()
	 *     method, which is usually just the lowercase class+namespace name
	 *     of the controller which is following the "Controller\" namespace.
	 * 
	 * - Callback:
	 *     'callback::string'  (can only be a string because of compiling)
	 *     
	 *     The callback string must point to either a static method or a 
	 *     function which has at most one required parameter.
	 *     This method/function will receive the $env var as its sole parameter.
	 * 
	 * - Application engine:
	 *     '\Engine\Class\Name'
	 *     
	 *     This class must extend the \Inject\Core\Engine class.
	 *     If the route matches then its MiddlewareStack will be run with a slightly modified
	 *     $env hash:
	 *     PATH_INFO   = *uri capture (or uri option)
	 *     SCRIPT_NAME = SCRIPT_NAME + (PATH_INFO - *uri capture)
	 *     BASE_URI    = BASE_URI    + (PATH_INFO - *uri capture)
	 *     
	 *     The old route will be stored in $env['web.old_route'].
	 * 
	 * - Redirect:
	 *     $this->redirect('some_destination', 301)
	 *     
	 *     Will perform a redirect with a HTTP header, see
	 *     \Inject\Web\Router\Generator\Generator->redirect()
	 *     for more information on usage.
	 * 
	 * @param  string|Redirect
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function to($to)
	{
		$this->to_arr[] = $to;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the regular expression constraints for specified $env parameters.
	 * 
	 * The key of the $options array is the key in $env you want to match the
	 * regular expression against. The value is the regular expression or
	 * an integer, double, boolean or null which is what should match.
	 * 
	 * The integer, double, boolean and null values will be converted into
	 * appropriate regular expression automatically.
	 * 
	 * The second parameter specifies a list of named captures which will be
	 * included in the route parameters in the same way as the path parameters
	 * from path() are.
	 * 
	 * Example:
	 * <code>
	 * // Requires any localhost IP
	 * $this->constraints(array('REMOTE_ADDR' => '/^127.0.0.\d{1,3}$/'));
	 * // Requires a mozilla-based web browser and stores its version in "moz_version"
	 * $this->constraints(array('HTTP_USER_AGENT' => '#^Mozilla/(?<moz_version>.+)#'));
	 * </code>
	 * 
	 * @param  array(string => mixed)  List of environment keys and their
	 *         conditions, will usually be a regular expression, but can also
	 *         be an integer, double, boolean or null value.
	 * @param  array(string)  List of named captures used in the regexes,
	 *                        if they are present on this list, they will be
	 *                        included in the route parameters
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function constraints(array $options, $named_captures = array())
	{
		$this->constraints         = array_merge($this->constraints, $options);
		$this->constraint_captures = array_merge($this->constraint_captures, (Array)$named_captures);
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the accepted request methods for this route, defaults to all.
	 * 
	 * NOTE: Can be overridden by a constraint() on REQUEST_METHOD.
	 * 
	 * @param  string|array|false  a request method or list of request methods
	 *                             this route accepts, false to allow all (default).
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function via($request_method)
	{
		if($request_method === false)
		{
			$this->via = array();
		}
		else
		{
			$this->via = array_merge($this->via, (Array)$request_method);
		}
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Specifies the default values for the captures, these will also be passed
	 * on as route parameters even if there are no captures with that name.
	 * 
	 * @param  array(string => string)
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function defaults(array $defaults)
	{
		$this->options = array_merge($this->options, $defaults);
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Specifies the name of this specific route, if used in a Scope it won't
	 * carry on to the scoped routes.
	 * 
	 * @param  string
	 * @return \Inject\Web\Router\Generator\Mapping  self
	 */
	public function name($value)
	{
		$this->name = $value;
		
		return $this;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the destination handler for this route.
	 * 
	 * @return string|Object
	 */
	public function getToArray()
	{
		return $this->to_arr;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the options array of this route.
	 * 
	 * @return string
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the constraints array for this route.
	 * 
	 * @return array(string => string)
	 */
	public function getConstraints()
	{
		return $this->constraints;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns a list of regular expression captures in the constraint list which
	 * should be preserved in the route parameters.
	 * 
	 * @return array(string)
	 */
	public function getConstraintsCaptures()
	{
		return $this->constraint_captures;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array containing allowed HTTP request methods, empty if to
	 * allow all request methods.
	 * 
	 * @return array(string)
	 */
	public function getVia()
	{
		return $this->via;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the raw path pattern for this route.
	 * 
	 * @return string
	 */
	public function getPathPattern()
	{
		return $this->path_pattern;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns regular expression fragments specified to be used for the captures
	 * in the specified pattern.
	 * 
	 * @return array(string => string)  capture_name => regex_fragment
	 */
	public function getRegexFragments()
	{
		return $this->regex_fragments;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the name of this route, if any, if this is the root route ("/")
	 * then "root" will be returned if nothing else has been defined.
	 * 
	 * @return string|null
	 */
	public function getName()
	{
		return empty($this->name) ? ($this->path_pattern == '/' ? 'root' : '') : $this->name;
	}
}


/* End of file Mapping.php */
/* Location: src/php/Inject/Web/Router/Generator */