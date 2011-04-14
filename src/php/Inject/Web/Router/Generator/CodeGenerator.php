<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

/**
 * 
 */
class CodeGenerator
{
	/**
	 * A list of parameter variables to be set as the parameter list of the
	 * generated closure, the first one is the only one used for matching.
	 * 
	 * @var array(string)
	 */
	protected $params = array();
	
	/**
	 * A list of variables to be put in the Closure's use() statement.
	 * 
	 * @var array(string)
	 */
	protected $use_variables = array();
	
	/**
	 * The path parameter set code, needs equal sign on end (can be empty though).
	 * 
	 * @var string
	 */
	protected $path_params_var = '';
	
	/**
	 * The code to run if the routing fails.
	 * 
	 * @var string
	 */
	protected $fail_code = 'return false;';
	
	/**
	 * List of associated DestinationHandlerInterface classes.
	 * 
	 * @var array(string)
	 */
	protected $dest_handlers = array();
	
	/**
	 * A list of compiled definitions.
	 * 
	 * @var array
	 */
	protected $definitions = array();
	
	// ------------------------------------------------------------------------

	/**
	 * Registers a specific destination handler class which will generate code
	 * for routing based on the Mapping objects created by the user.
	 * 
	 * @param  string  Class implementing Inject\Web\Router\Generator\DestinationHandlerInterface
	 * @return void
	 */
	public function registerDestinationHandlers($class)
	{
		foreach((Array)$class as $klass)
		{
			$ref = new \ReflectionClass($klass);
			
			if($ref->isSubclassOf('Inject\Web\Router\Generator\DestinationHandler'))
			{
				// Only allow a single instance per class
				in_array($klass, $this->dest_handlers) OR $this->dest_handlers[] = $klass;
			}
			else
			{
				// TODO: Exception
				throw new \Exception(sprintf('The class %s is not a valid route destination handler, it must implement \Inject\Web\Route\Generator\DestinationHandler', $klass));
			}
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setClosureParameters($params)
	{
		$this->params = (Array) $params;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setUseVariables($vars)
	{
		$this->use_variables = (Array) $vars;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setPathParamsVar($var)
	{
		$this->path_params_var = $var;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function setFailCode($code)
	{
		$this->fail_code = $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Takes a list of Mapping objects and compiles them and stores internally.
	 * 
	 * @param  array(\Inject\Web\Router\Generator\Mapping)
	 * @return void
	 */
	public function compileDefinitions(array $definitions, array $validation_params = array())
	{
		foreach($definitions as $def)
		{
			$handler = null;
			
			foreach($def->getToArray() as $to_val)
			{
				foreach($this->dest_handlers as $handler_class)
				{
					if($tmp = $handler_class::parseTo($to_val, $def, $handler))
					{
						$handler = $tmp;
					}
				}
			}
			
			if( ! $handler)
			{
				// TODO: Exception
				throw new \Exception(sprintf('The route %s does not have a compatible to() value.', $def->getPathPattern()));
			}
			
			// Compile the contents of the DestinationHandlers
			$handler->prepare();
			$handler->validate($validation_params);
			$handler->compile();
			
			$this->definitions[] = $handler;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Generates router code for the compiled mappings.
	 * 
	 * @return 
	 */
	public function generateRouterCode()
	{
		$tree = $this->constructRouteTree($this->definitions);
		
		return 'function('.implode(', ', $this->params).') use('.implode(', ', $this->use_variables).')
{
	$matches = array();

'.self::indentCode($tree->createCode()).'

	'.$this->fail_code.'
}';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Constructs a tree of the routes so they group by their conditions like
	 * PATH_INFO, REQUEST_URI etc.
	 * 
	 * @param  array
	 * @return array
	 */
	public function constructRouteTree(array $definitions)
	{
		// Routing tree
		$tree = new ConditionSegment();
		
		foreach($definitions as $def)
		{
			$current = $tree;
			
			$conditions = $def->getConditions(reset($this->params), '$match', $this->use_variables);
			
			foreach($conditions as $cond)
			{
				if( ! isset($current[$cond]))
				{
					$current[$cond] = new ConditionSegment($cond);
				}
				
				$current = $current[$cond];
			}
			
			if($current->hasDestination())
			{
				// TODO: Proper error
				throw new \Exception('Conflicting conditions');
			}
			
			$current->setDestination($this->createRunCode($def));
		}
		
		return $tree;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates code which will run a route, will put the matched route parameters
	 * (if any) into $env['web.route_params'].
	 * 
	 * @param  \Inject\Web\Router\Generator\DestinationHandler
	 * @return string
	 */
	public function createRunCode(DestinationHandler $handler)
	{
		$code = $this->path_params_var.'$merged = ';
		
		if( ! count($handler->getCaptureIntersect()))
		{
			$code .= var_export($handler->getOptions(), true).';';
		}
		else
		{
			$code .= 'array_intersect_key(array_merge('.var_export($handler->getOptions(), true).', array_reduce($matches, \'array_merge\', array())), '.var_export($handler->getCaptureIntersect(), true).');';
		}
		
		$code .= "\n".$handler->getCallCode($this->params, $this->use_variables, '$merged');
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a PHP code array containing closures which will each create the
	 * URI for their respective keys in the array.
	 * 
	 * TODO: Move to separate class
	 * 
	 * @param  array(\Inject\Web\Router\Generator\DestinationHandler)
	 * @return string
	 */
	public function createReverseRouter()
	{
		$code = "array(\n\t";
		
		$uris = array();
		
		foreach($this->definitions as $def)
		{
			if($def->getName())
			{
				$uris[] = var_export($def->getName(), true).' => function($options)
	{
'.self::indentCode('return '.$this->createUriAssembler($def->getTokens()).';', 2).'
	}';
			}
		}
		
		$code .= implode(",\n\t", $uris)."\n);";
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Generates a one-liner generating the URI for the supplied tokens,
	 * will return an array with the required keys if at least one is missing.
	 * 
	 * TODO: Move to separate class
	 * 
	 * @param  array   Tokens from the Tokenizer
	 * @return string
	 */
	public function createUriAssembler(array $tokens)
	{
		// TODO: Split into smaller parts?
		$code   = '';
		$end    = count($tokens);
		$concat = false;
		
		for($i = 0; $i < $end; $i++)
		{
			list($type, $data) = $tokens[$i];
			
			switch($type)
			{
				case Tokenizer::LITERAL:
					$code  .= ($concat ? '.' : '').var_export($data, true);
					$concat = true;
					break;
					
				case Tokenizer::CAPTURE:
					$code  .= ($concat ? '.' : '').'$options['.var_export($data, true).']';
					$concat = true;
					break;
					
				case Tokenizer::OPTBEGIN:
					$conds = array();
					
					// Check for nested captures
					$ind = 1;
					for($j = $i + 1; $j < $end && $ind > 0; $j++)
					{
						if($tokens[$j][0] == Tokenizer::CAPTURE && $ind == 1)
						{
							$conds[] = 'empty($options['.var_export($tokens[$j][1], true).'])';
						}
						elseif($tokens[$j][0] == Tokenizer::OPTBEGIN)
						{
							$ind++;
						}
						elseif($tokens[$j][0] == Tokenizer::OPTEND)
						{
							$ind--;
						}
					}
					
					// Do we have any nested conditions?
					if( ! empty($conds))
					{
						$code  .= ($concat ? '.' : '').'('.implode(' OR ', $conds).' ? \'\' : ';
						$concat = false;
					}
					else
					{
						// Nope, scroll past the conditional pattern
						$indent = 1;
						
						for($j = 0;$j < $end && $indent > 0; $j++)
						{
							if($tokens[$j][0] == Tokenizer::OPTEND)
							{
								$indent--;
							}
							elseif($tokens[$j][0] == Tokenizer::OPTBEGIN)
							{
								$indent++;
							}
						}
						
						$i = $j;
					}
					
					break;
					
				case Tokenizer::OPTEND:
					$code  .= ')';
					$concat = true;
			}
		}
		
		// Find required matches:
		$required = array();
		$captures = array();
		$indent   = 0;
		
		foreach($tokens as $tok)
		{
			list($type, $data) = $tok;
			
			switch($type)
			{
				case Tokenizer::CAPTURE:
					if($indent == 0)
					{
						$captures[] = $data;
						$required[] = 'empty($options['.var_export($data, true).'])';
					}
					break;
				
				// Optional section start
				case Tokenizer::OPTBEGIN:
					$indent++;
					break;
				
				// Optional section end
				case Tokenizer::OPTEND:
					$indent--;
					break;
			}
		}
		
		if( ! empty($required))
		{
			$code = '('.implode(' OR ', $required).' ? '.var_export($captures, true).' : '.$code.')';
		}
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Indents code with tabs, does not work on code which contains Heredoc,
	 * Nowdoc or multiline strings as they will get tabs in them.
	 * 
	 * @param  string
	 * @return string
	 */
	public static function indentCode($code, $indent = 1)
	{
		$lines = explode("\n", $code);
		
		$lines = array_map(function($elem) use($indent)
		{
			return str_repeat("\t", $indent).$elem;
		}, $lines);
		
		return implode("\n", $lines);
	}
}


/* End of file CodeGenerator.php */
/* Location: src/php/Inject/Web/Router/Generator */