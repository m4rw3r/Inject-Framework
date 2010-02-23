<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
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
	protected $reverse_route_dynamic_tree = array('action' => array(), 'no_action' => array());
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct($class_name)
	{
		$this->class_name = $class_name;
		
		foreach(Inject::getApplicationPaths() as $p)
		{
			if(file_exists($p.'Config/URI_Routes.php'))
			{
				$this->files[] = $p.'Config/URI_Routes.php';
			}
		}
		
		foreach($this->files as $file)
		{
			include $file;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Adds a rule.
	 * 
	 * TODO: Manual
	 * 
	 * @return 
	 */
	public function matches($pattern, array $options)
	{
		if( ! (isset($options['_controller']) OR isset($options['_class'])) &&
			preg_match('#:_controller(?!>[\w])#u', $pattern) == false)
		{
			throw new Exception(sprintf('The pattern "%s" is missing a _controller or _class option.', $pattern));
		}
		
		if(strpos($pattern, ':') === false)
		{
			$this->static_routes[] = $r = new Inject_Request_HTTP_URI_RouteBuilder_Static($pattern, $options);
		}
		else
		{
			$this->regex_routes[] = $r = new Inject_Request_HTTP_URI_RouteBuilder_Regex($pattern, $options);
		}
		
		// Create reverse route tree
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
			$this->reverse_route_dynamic_tree['action'][$r->getAction()][] = $r;
		}
		else
		{
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
	public function writeCache($file = 'URI_Router.php')
	{
		$r = file_put_contents(Inject_Util_Cache::getFolder().$file, $this->getPHP());
		
		if( ! $r)
		{
			return false;
		}
		
		// Register written file
		//Inject_Util_Cache::registerCacheFile($file, $this->files);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Renders the PHP file.
	 * 
	 * @return string
	 */
	public function getPHP()
	{
		$code = '<?php

class '.$this->class_name.' implements Inject_Request_HTTP_URI_RouterInterface
{
	public function matches($uri)
	{
		';
		$arr = array();
		foreach($this->static_routes as $r)
		{
			$arr[] = $r->getMatchCode()."\n";
		}
		
		foreach($this->regex_routes as $r)
		{
			$arr[] = $r->getMatchCode()."\n";
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
		
		return implode("\n\n\t\t", $str);
	}
}

/* End of file Router.php */
/* Location: ./lib/Inject/Request/HTTP/URI */