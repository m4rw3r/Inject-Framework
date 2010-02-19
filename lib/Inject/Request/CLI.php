<?php
/*
 * Created by Martin Wernståhl on 2010-02-14.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * 
 */
class Inject_Request_CLI extends Inject_Request
{
	protected $script_name;
	
	protected $method = 'GET';
	
	protected $class_name;
	
	protected $action_name;
	
	protected $parameters = array();
	
	function __construct()
	{
		parent::__construct();
		
		if(strtolower(PHP_SAPI) !== 'cli')
		{
			throw new Exception('Cannot create a CLI request object when PHP is not running in CLI.');
		}
		
		$this->parse_argv();
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses the ARGV parameters from the CLI SAPI.
	 * 
	 * @return void
	 */
	protected function parse_argv()
	{
		$this->script_name = $_SERVER['PHP_SELF'];
		
		$argv = $_SERVER['argv'];
		
		// Remove script name if present
		if(isset($argv[0]) && $argv[0] == $_SERVER['PHP_SELF'])
		{
			array_shift($argv);
		}
		
		$key = null;
		$run_path = array();
		
		// Parse parameter list
		while( ! empty($argv))
		{
			$v = array_shift($argv);
			
			// Is it a parameter?
			if(strpos($v, '-') === 0)
			{
				// Do we have a started parameter already?
				if( ! is_null($key))
				{
					// Yes, finish it
					$this->parameters[$key] = true;
				}
				
				// Is it 2 or 1 dash at the start? remove them
				if(strpos($v, '--') === 0)
				{
					$key = substr($v, 2);
				}
				else
				{
					$key = substr($v, 1);
				}
			}
			// Do we have a parameter value
			elseif( ! is_null($key))
			{
				// Yes
				$this->parameters[$key] = $v;
				
				// Done processing that parameter
				$key = null;
			}
			else
			{
				// Not tied to a parameter, add it to the "address"
				$run_path[] = $v;
			}
		}
		
		// Do we have an orphaned parameter?
		if( ! is_null($key))
		{
			// Yes, add it
			$this->parameters[$key] = true;
		}
		
		// Do we have a path, or the help flags?
		if(empty($run_path) OR count(array_intersect(array('h', 'help', '?'), array_keys($this->parameters))))
		{
			$this->showHelp();
		}
		
		// Controller
		if(isset($run_path[0]))
		{
			$class = $run_path[0];
			
			$this->class_name = 'Cli_'.Utf8::ucfirst(preg_replace('/(_\w)/eu', "Utf8::strtoupper('$1')", Utf8::strtolower($class)));;
		}
		
		// Action
		if(isset($run_path[1]))
		{
			$this->action_name = 'action'.$run_path[1];
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Shows the invocation help for the CLI request.
	 * 
	 * @return void
	 */
	public function showHelp()
	{
		echo '
Inject Framework CLI Interface

Usage:
    php '.$this->script_name.' [controller] ([action_name]) ([-parameter] ([parameter_value]))

Example:
    php '.$this->script_name.' welcome say_hello -name "Martin W"

    Will call the Cli_Welcome controller\'s method actionSay_hello
    with the parameters array(\'name\' => \'Martin W\').

To print this help again, use the --help, -help, -h or -? options.

';
		
		exit;
	}
	
	// ------------------------------------------------------------------------
	
	public function getProtocol()
	{
		return 'cli';
	}
	
	// ------------------------------------------------------------------------
	
	public function getMethod()
	{
		return $this->method;
	}
	
	// ------------------------------------------------------------------------
	
	public function getControllerClass()
	{
		return $this->class_name;
	}
	
	// ------------------------------------------------------------------------
	
	public function getActionMethod()
	{
		return $this->action_name;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameter($name, $default = null)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
	}
	
	// ------------------------------------------------------------------------
	
	public function getParameters()
	{
		return $this->parameters;
	}
	
	// ------------------------------------------------------------------------
	
	public function createCall($controller, $action = null, $parameters = array())
	{
		if(is_array($controller))
		{
			throw new Exception('CODE NOT WRITTEN!');
		}
		
		if(strpos(strtolower($controller), 'cli_') === 0)
		{
			$controller = substr($controller, 4);
		}
		
		$params = $controller;
		
		if( ! empty($action))
		{
			$params .= ' '.$action;
		}
		
		foreach($parameters as $k => $v)
		{
			$params .= ' -'.$k;
			empty($v) OR $params .= ' '.$this->escapeForTerminal($v);
		}
		
		return $this->script_name.' '.$params;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Escapes the value for usage in a terminal.
	 * 
	 * @param  string
	 * @return string
	 */
	protected function escapeForTerminal($value)
	{
		$chars_to_escape = array(' ', '"', '!');
		
		if(count(array_intersect($chars_to_escape, str_split($value))))
		{
			return '"'.addcslashes($value, '"').'"';
		}
		else
		{
			return $value;
		}
	}
}


/* End of file CLI.php */
/* Location: ./lib/Inject/Request */