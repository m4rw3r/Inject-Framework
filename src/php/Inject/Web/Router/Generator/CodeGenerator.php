<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator;

use \Inject\Core\Engine;

/**
 * 
 */
class CodeGenerator
{
	/**
	 * The engine sent to DestinationHandlers to determine controllers etc.
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $engine;
	
	// ------------------------------------------------------------------------

	/**
	 * @param  \Inject\Core\Engine
	 */
	public function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}
	// ------------------------------------------------------------------------

	/**
	 * Generates the routing code, to be put in a cache file or eval()'d to get
	 * the route closure and the reverse routing closure list.
	 * 
	 * @param  array(\Inject\Web\Router\Generator\DestinationHandler)
	 * @return string
	 */
	public function generateCode(array $definitions)
	{
		$tree = $this->constructRouteTree($definitions);
		
		// TODO: How to detect match conflicts? ie. routes which will never match because another always match instead
		// TODO: How to do with route_parameters "leaking" from one regex to the next?
		// If you have routes 1 and 2, 1 has a regex and a match vs REQUEST_METHOD,
		// 2 has only a regex, generated structure makes 1's regex match first and then
		// it has a nested if for the REQUEST_METHOD, if the regexes for 1 and 2 are similar
		// enough (so 1 and 2 matches on specific urls), then it might match 1 and put
		// its parameters in the $matches var, then fail on the REQUEST_METHOD match
		// and proceed and match regex 2, which might not match fully, but has a named capture
		// with the same name as one in regex 1, then that match in regex 1 might not be
		// overwritten with "" as it might not have come that far => faulty parameters
		$code = '$controllers = '.var_export($this->engine->getAvailableControllers(), true).';
$router = function($env) use($engine, $controllers)
{
	$matches = array();
	
'.$this->indentCode($this->constructIfTree($tree)).'
	
	return array(404, array(\'X-Cascade\' => \'pass\'), \'\');
};';
		$code .= "\n\n\$reverse = ".$this->createReverseRouter($definitions);
		
		$code .= "\n\nreturn array(\$router, \$reverse);";
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a PHP code array containing closures which will each create the
	 * URI for their respective keys in the array.
	 * 
	 * @param  array(\Inject\Web\Router\Generator\DestinationHandler)
	 * @return string
	 */
	public function createReverseRouter(array $definitions)
	{
		$code = "array(\n\t";
		
		$uris = array();
		
		foreach($definitions as $def)
		{
			if($def->getName())
			{
				$uris[] = var_export($def->getName(), true).' => function($options)
	{
'.$this->indentCode('return '.$this->createUriAssembler($def->getTokens()).';', 2).'
	}';
			}
		}
		
		$code .= implode(",\n\t", $uris)."\n);";
		
		return $code;
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
		$tree = array();
		
		foreach($definitions as $def)
		{
			$current =& $tree;
			
			$conditions = $def->getConditions('$env', '$match', '$controllers');
			
			foreach($conditions as $cond)
			{
				if( ! isset($current[$cond]))
				{
					$current[$cond] = array();
				}
				
				$current =& $current[$cond];
			}
			
			$current[] = $def;
		}
		
		return $tree;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates a tree of If-constructs which will match parts of the $env and
	 * automatically group the conditions by URI, REQUEST_METHOD, etc.
	 * 
	 * @param  array  A tree containing the routes and their conditions
	 *         format: key = condition, value = DestinationHandler or a nested
	 *                 array in this format, numeric keys are no conditions
	 * @return string
	 */
	public function constructIfTree(array $tree)
	{
		$arr = array();
		
		foreach($tree as $condition => $data)
		{
			if( ! is_array($data))
			{
				$code = $this->createRunCode($data);
			}
			elseif(is_array($data) && count($data) == 1 && isset($data[0]))
			{
				$code = $this->createRunCode($data[0]);
			}
			else
			{
				$code = $this->constructIfTree($data);
			}
			
			$code = $this->indentCode($code);
			
			if( ! is_numeric($condition))
			{
				if(preg_match('/\bpreg_match\b/u', $condition))
				{
					$code = "\t".'$matches = array_merge($matches, $match);
'.$code;
				}
				
				$code = <<<EOF
if($condition)
{
$code
}
EOF;
			}
			
			$arr[] = $code;
		}
		
		return implode("\n\n", $arr);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Creates code which will run a route, will put the matched route parameters
	 * (if any) into $env['web.route_params'].
	 * 
	 * @param  \Inject\Web\Router\Generator\DestinationHandlerInterface
	 * @return string
	 */
	public function createRunCode(DestinationHandlerInterface $dh)
	{
		$code = '$env[\'web.route_params\'] = ';
		
		if( ! count($dh->getCaptureIntersect()))
		{
			$code .= var_export($dh->getOptions(), true).';';
		}
		else
		{
			$code .= 'array_intersect_key(array_merge('.var_export($dh->getOptions(), true).', $matches), '.var_export($dh->getCaptureIntersect(), true).');';
		}
		
		$code .= "\n".$dh->getCallCode('$env', '$engine', '$matches', '$controller');
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Generates a one-liner generating the URI for the supplied tokens,
	 * will return an array with the required keys if at least one is missing.
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
	public function indentCode($code, $indent = 1)
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