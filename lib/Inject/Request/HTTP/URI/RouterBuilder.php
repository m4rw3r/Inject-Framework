<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Builds the router cache class.
 */
class Inject_Request_HTTP_URI_RouterBuilder
{
	/**
	 * The class name to use as the compiled router.
	 * 
	 * @var string
	 */
	protected $class_name;
	
	/**
	 * List of files used to create the compiled router.
	 * 
	 * @var array
	 */
	protected $files = array();
	
	/**
	 * List of regular-expression based route objects.
	 * 
	 * @var array
	 */
	protected $regex_routes = array();
	
	/**
	 * List of static route objects.
	 * 
	 * @var array
	 */
	protected $static_routes = array();
	
	/**
	 * Tree for efficient reverse routing of rules.
	 * 
	 * @array
	 */
	protected $reverse_route_tree = array();
	
	/**
	 * Tree for the rules without a controller (and optionally action).
	 * 
	 * @var array
	 */
	protected $reverse_route_dynamic_tree = array('action' => array(), 'no_action' => array(), 'dynamic' => array());
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a new router builder.
	 * 
	 * @param  string  The classname of the class to generate
	 * @param  array   The files which contains routes
	 */
	public function __construct($class_name, array $files)
	{
		$this->class_name = $class_name;
		$this->files = $files;
		
		// Load the routes
		foreach($this->files as $file)
		{
			include $file;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a routing rule.
	 * 
	 * The $pattern variable contains a URI match expression which is following
	 * the syntax specified below. If this pattern matches the URI, then the
	 * $options array will be returned to the request object which will use that
	 * data to determine which parameters to show and which controller and action
	 * to run.
	 * 
	 * The $options array have five special keys which will be used by the
	 * request object, all the others will be sent to the controller as parameters.
	 * 
	 * 
	 * The special keys are:
	 * 
	 * - "_controller":  Specifies a controller name to run
	 *                   (automatically adds "Controller_").
	 * 
	 * - "_action":      Specifies an action name to run
	 *                   (automatically adds "action").
	 * 
	 * - "_class":       Specifies a class name to run, this takes precedence
	 *                   over "_controller" and does NOT automatically
	 *                   add "Controller_", so full class names must be used.
	 * 
	 * - "_constraints": Used by the captures to change the rules for the
	 *                   content to match, see below for more information.
	 * 
	 * - "_uri":         Contains an uri to be parsed into a key => value array,
	 *                   usually not used in the $options array, it is
	 *                   instead used as a capture.
	 *                   Parsed like this: key1/value1/key2/value2 etc.
	 * 
	 * 
	 * The $pattern can contain captures, ie. pieces which will "capture" the
	 * matching part of the URI and store it in the parameter with the name of
	 * the capture.
	 * Eg. :id will capture an URI segment (not including "/")
	 * and store it in the parameter called id.
	 * 
	 * 
	 * The captures will override the contents of the $options array if they
	 * use the same name. For example the "id" option (or parameter in this case)
	 * will be overridden in "page(/:id)" if eg. "page/34" is the URI (then the
	 * id parameter will be 34, if no slash comes after "page" or if there is no
	 * data after the slash, id will be whatever it is in the $options array).
	 * 
	 * So you can for example specify an action to be called if the URI matches
	 * :_controller/foo, and then the controller name will be fetched from the URI.
	 * 
	 * But the _class parameter is not allowed to be used as a capture name for
	 * security reasons, it is not a good idea to let the user specify which class
	 * to call, as that can be anything on the include path.
	 * 
	 * 
	 * Syntax for the pattern:
	 * 
	 * <code>
	 * :foo  = matches a segment into the parameter foo
	 * (bar) = optionally match bar, tries to match if present
	 * \(    = escaped parenthesis
	 * \)    = escaped closing parenthesis
	 * \:    = escaped match statement
	 * \\:   = escaped escape character followed by a match statement
	 * </code>
	 * 
	 * 
	 * Examples:
	 * 
	 * <code>
	 * page/foo              = matches "page/foo".
	 *                       
	 * page/:id              = matches "page/" and then an URI segment which then
	 *                         is stored in the id parameter.
	 *                       
	 * page(/:id)            = matches "page" and then an optional "/" followed
	 *                         by an optional capture which is stored in the id parameter.
	 *                       
	 * (:lang/)page(/:id)    = matches an optional segment whose content will be put
	 *                         in the lang parameter, then "page" and finally an
	 *                         optional segment which will be put in the id parameter.
	 *                       
	 * \:page:name           = matches ":page" and then the following text goes into
	 *                         the name parameter.
	 * 
	 * users(/:method)(/:id) = matches "users", then optionally a segment whose
	 *                         content is put in method and then another optional
	 *                         segment which is put in id.
	 * 
	 * page(/:id(/:lang))    = matches "page", then optionally "/" followed by data
	 *                         which is put in id, then (if id is populated) an
	 *                         optional "/" + a segment which will be stored in lang.
	 * 
	 * :_controller(/:_action(/):_uri)
	 *                         is the default (and hardcoded) route used by Inject
	 *                         Framework, it will match a controller as the first
	 *                         segment, then an optional action, an optional slash
	 *                         ("/", greedily matched), and the rest is populated
	 *                         into the URI, which will parse it into a key => value
	 *                         array.
	 * </code>
	 * 
	 * 
	 * Sometimes you want to be able to adjust what a "segment" is,
	 * by default it is the regular expression for a word (\w) which will
	 * match about anything except for special characters (including : and /)
	 * (the :_uri capture uses .* instead, as it should usually match the rest
	 * of the URI).
	 * 
	 * To change that, add the key "_constraints" to the $options parameter
	 * with an array containing capture_name => regular_expression pairs for
	 * the capture matchers you want to replace.
	 * 
	 * 
	 * Example of using _constraints:
	 * 
	 * <code>
	 * $this->matches(
	 *     'rest(/:method)(/:id)',
	 *     array(
	 *         '_controller' => 'rest',
	 *         '_action' => 'handle',
	 *         '_constraints' => array('method' => '[a-z]+', 'id' => '\d+')
	 *         )
	 *     );
	 * </code>
	 * 
	 * This will force the method to be a letter (a-z, at least one char)
	 * and the id to be numeric (0-9).
	 * 
	 * 
	 * CAUTION:
	 * Keep in mind that PHP needs escaping of the "\" character in string literals!
	 * 
	 * @param  string
	 * @param  array
	 * @return void
	 */
	public function matches($pattern, array $options = array())
	{
		// Check if we're missing a class or controller,
		// the :_controller may be preceeded with an even amount of
		// backslashes (hence the str_replace())
		if( ! (isset($options['_controller']) OR isset($options['_class'])) &&
			! preg_match('#(?<!\\\\):_controller(?!>[\w])#u', str_replace('\\\\', '', $pattern)))
		{
			throw new Exception(sprintf('The pattern "%s" is missing a _controller or _class option.', $pattern));
		}
		
		// Does the route contain captures?
		if(preg_match('#(?<!\\\\)(?::|\\(|\\))#u', str_replace('\\\\', '', $pattern)))
		{
			$this->regex_routes[] = $r = new Inject_Request_HTTP_URI_RouteBuilder_Regex($pattern, $options);
		}
		else
		{
			$this->static_routes[] = $r = new Inject_Request_HTTP_URI_RouteBuilder_Static($pattern, $options);
		}
		
		// Create reverse route tree:
		if($r->hasClass() && $r->hasAction())
		{
			$this->reverse_route_tree[$r->getClass()][$r->getAction()][] = $r;
		}
		elseif($r->hasClass())
		{
			$this->reverse_route_tree[$r->getClass()][0][] = $r;
		}
		elseif($r->hasAction())
		{
			// Dynamic controller
			$this->reverse_route_dynamic_tree['action'][$r->getAction()][] = $r;
		}
		elseif($r->hasDynamicAction())
		{
			$this->reverse_route_dynamic_tree['dynamic'][] = $r;
		}
		else
		{
			// Dynamic controller and no action or dynamic action
			$this->reverse_route_dynamic_tree['no_action'][] = $r;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes the cached router to the specified file.
	 * 
	 * @param  string
	 * @return bool
	 */
	public function writeCache($file)
	{
		$r = file_put_contents(Inject_Util_Cache::getFolder().$file, '<?php

'.$this->getPHP());
		
		if( ! $r)
		{
			return false;
		}
		
		// Register written file
		Inject_Util_Cache::registerCacheFile($file, $this->files);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders the PHP file.
	 * 
	 * @return string
	 */
	public function getPHP()
	{
		$code = 'class '.$this->class_name.' implements Inject_Request_HTTP_URI_RouterInterface
{
	public function matches($uri)
	{
		';
		$arr = array();
		foreach($this->static_routes as $r)
		{
			$arr[] = $r->getMatchCode();
		}
		
		foreach($this->regex_routes as $r)
		{
			$arr[] = $r->getMatchCode();
		}
		
		$code .= implode("\n\n\t\t", $arr);
		
		$code .= '
		
		return array();
	}
	
	public function reverseRoute(array $options)
	{
		if( ! isset($options[\'_class\']))
		{
			$options[\'_class\'] = strtolower(\'controller_\'.$options[\'_controller\']);
		}
		else
		{
			$options[\'_class\'] = strtolower($options[\'_class\']);
		}
		
		$params = array_diff_key($options, array(\'_controller\' => true, \'_class\' => true, \'_action\' => true));
		
		';
		
		$code .= $this->getReverseRouteMatcher();
		
		$code .= '
		
		return false;
	}
}';
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates the reverse routing if-else-tree.
	 * 
	 * @return string
	 */
	public function getReverseRouteMatcher()
	{
		$str = array();
		
		if(empty($this->reverse_route_tree))
		{
			return '';
		}
		
		foreach($this->reverse_route_tree as $class => $data)
		{
			$no_actions = array();
			$w_actions = array();
			$s = 'if($options[\'_class\'] === \''.$class.'\')
		{
			';
			
			foreach($data as $routes)
			{
				foreach($routes as $r)
				{
					if($r->hasAction())
					{
						$w_actions[] = $r->getReverseMatchCode();
					}
					else
					{
						$no_actions[] = $r->getReverseMatchCode();
					}
				}
			}
			
			if( ! empty($w_actions))
			{
				$s .= 'if(isset($options[\'_action\']))
			{
				'.implode("\n\n\t\t\t\t", $w_actions).'
			}
			else
			{
				'.implode("\n\n\t\t\t\t", $no_actions).'
			}';
			}
			else
			{
				$s .= implode("\n\n\t\t", $no_actions);
			}
			
			$s .= '
		}';
			
			$str[] = $s;
		}
		
		foreach($this->reverse_route_dynamic_tree['action'] as $action => $routes)
		{
			$s = 'if($options[\'_action\'] === \''.$action.'\')
		{
			';
			
			foreach($routes as $r)
			{
				$s .= $r->getReverseMatchCode()."\n\t\t\t";
			}
			
			$s .= '
		}';
			
			$str[] = $s;
		}
		
		if( ! empty($this->reverse_route_dynamic_tree['no_action']))
		{
			$s = 'if(empty($options[\'_action\']))
		{
			';
			
			foreach($this->reverse_route_dynamic_tree['no_action'] as $r)
			{
				$s .= $r->getReverseMatchCode()."\n\t\t\t";
			}
			
			$s .= '
		}';
			
			$str[] = $s;
		}
		
		foreach($this->reverse_route_dynamic_tree['dynamic'] as $r)
		{
			$str[] = $r->getReverseMatchCode();
		}
		
		return implode("\n\n\t\t", $str);
	}
}

/* End of file RouterBuilder.php */
/* Location: ./lib/Inject/Request/HTTP/URI */