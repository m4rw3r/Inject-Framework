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
	protected $engine;
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function generateCode(array $definitions)
	{
		$tree = $this->constructRouteTree($definitions);
		
		$code = '$controllers = '.var_export($this->engine->getAvailableControllers(), true).';
$router = function($env) use($engine, $controllers)
{
	$matches = array();
	
'.$this->indentCode($this->constructIfTree($tree)).'
	
	return array(404, array(\'X-Cascade\' => \'pass\'), \'\');
};';
		$code .= "\n\n\$reverse = array(\n\t";
		
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
		
		$code .= "\n\nreturn array(\$router, \$reverse);";
		
		return $code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function constructRouteTree(array $definitions)
	{
		// Routing tree
		$tree = array();
		
		foreach($definitions as $def)
		{
			$current =& $tree;
			
			$def->prepare();
			$def->validate($this->engine);
			$def->compile();
			
			$conditions = $def->getConditions('$env', '$match', '$controllers');
			
			//sort($conditions);
			
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
	 * 
	 * 
	 * @return 
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
	 * 
	 * 
	 * @return 
	 */
	public function createRunCode(DestinationHandlerInterface $dh)
	{
		$code = '$env[\'web.route_params\'] = ';
		
		if( ! count($dh->getCaptureIntersect()))
		{
			$code .= 'array();';
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
	 * 
	 * 
	 * @return 
	 */
	public function createUriAssembler(array $tokens)
	{
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
	 * 
	 * 
	 * @return 
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