<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Middleware;

use \Inject\Core\Application\Engine;
use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * Middleware catching exceptions and printing error messages and stack traces
 * if in development, otherwise prints a 500 error page.
 */
class ExceptionCatcher implements MiddlewareInterface
{
	/**
	 * The associated application.
	 * 
	 * @var \Inject\Core\Application\Engine
	 */
	protected $app;
	
	/**
	 * Next callback in chain.
	 * 
	 * @var callback
	 */
	protected $next;
	
	// ------------------------------------------------------------------------

	/**
	 * @param  \Inject\Core\Application\Engine
	 */
	public function __construct(Engine $app)
	{
		$this->app = $app;
	}
	
	// ------------------------------------------------------------------------
	
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	public function __invoke($env)
	{
		try
		{
			$next = $this->next;
			
			return $next($env);
		}
		catch(\Exception $exception)
		{
			// TODO: Make it possible to change environment
			$status = 'dev';
			
			// TODO: Code
			// $format = $env->getParameter('format', 'html');
			$format = 'html';
			
			switch($status)
			{
				case 'dev':
					$type    = get_class($exception);
					$message = $exception->getMessage();
					$file    = $exception->getFile();
					$line    = $exception->getLine();
					$trace   = $exception->getTrace();
					array_unshift($trace, array(
							'file'     => $file,
							'line'     => $line,
							'function' => 'throw'
						));
				
				// TODO: Production environment
				
			}
			
			ob_start();
			
			include __DIR__.'/ExceptionCatcher/exception_'.$status.'.'.$format.'.php';
			
			return array(500, array(), ob_get_clean());
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Extracts the code around the specified line in the specified file, 
	 * wraps it in a table and highlights it.
	 * 
	 * @param  string
	 * @param  int
	 * @return string
	 */
	protected function extractCode($file, $line)
	{
		$data = explode("\n", file_get_contents($file));
		
		$code = array_slice($data, max($line - 4, 0), 7, true);
		
		$self = $this;
		
		$code = array_map(function($k, $l) use($line, $self)
		{
			$k++;
			return '<tr class="line'.($k == $line ? ' current' : '').'"><td class="line_no">'.($k + 1).'</td><td>'.$self->highlight($l, true).'</td></tr>';
		}, array_keys($code), array_values($code));
		
		return '<table cellpadding="0" cellspacing="0" border="0">'.implode("\n", $code).'</table>';
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Simple PHP Code highlighter using the Tokenizer extension to tokenize
	 * the code.
	 * 
	 * @param  string  PHP code, without leading <?php
	 * @return string  HTML string with the code
	 */
	public function highlight($string)
	{
		$tokens = token_get_all('<?php '.$string);
		
		$result = array();
		
		foreach($tokens as $tok)
		{
			if( ! is_array($tok))
			{
				$result[] = $tok;
				continue;
			}
			switch($tok[0])
			{
				// Keywords
				case T_ABSTRACT:
				case T_ARRAY:
				case T_AS:
				case T_BREAK:
				case T_CASE:
				case T_CATCH:
				case T_CLASS:
				case T_CLONE:
				case T_CONST:
				case T_CONTINUE:
				case T_DECLARE:
				case T_DEFAULT:
				case T_DO:
				case T_ELSE:
				case T_ELSEIF:
				case T_ENDDECLARE:
				case T_ENDFOR:
				case T_ENDFOREACH:
				case T_ENDIF:
				case T_ENDSWITCH:
				case T_ENDWHILE:
				case T_END_HEREDOC:
				case T_EXIT:
				case T_EXTENDS:
				case T_FINAL:
				case T_FOR:
				case T_FOREACH:
				case T_FUNCTION:
				case T_GLOBAL:
				case T_GOTO:
				case T_IF:
				case T_IMPLEMENTS:
				case T_INTERFACE:
				case T_NAMESPACE:
				case T_NEW:
				case T_PRIVATE:
				case T_PUBLIC:
				case T_PROTECTED:
				case T_STATIC:
				case T_SWITCH:
				case T_THROW:
				case T_TRY:
				case T_USE:
				case T_VAR:
				case T_WHILE:
					$result[] = '<span style="color: #766ffa">'.$tok[1].'</span>';
					break;
				
				// Function
				case T_ECHO:
				case T_EMPTY:
				case T_EVAL:
				case T_HALT_COMPILER:
				case T_INCLUDE:
				case T_INCLUDE_ONCE:
				case T_ISSET:
				case T_LIST:
				case T_PRINT:
				case T_REQUIRE:
				case T_REQUIRE_ONCE:
				case T_RETURN:
				case T_UNSET:
				case T_STRING:
					$result[] = '<span style="color: #ac31ae">'.$tok[1].'</span>';
					break;
				
				// Operators
				case T_AND_EQUAL:
				case T_ARRAY_CAST:
				case T_BOOLEAN_AND:
				case T_BOOLEAN_OR:
				case T_BOOL_CAST:
				case T_CONCAT_EQUAL:
				case T_DEC:
				case T_DIV_EQUAL:
				case T_DOUBLE_ARROW:
				case T_DOUBLE_CAST:
				case T_DOUBLE_COLON:
				case T_INC:
				case T_INSTANCEOF:
				case T_INT_CAST:
				case T_IS_EQUAL:
				case T_IS_GREATER_OR_EQUAL:
				case T_IS_IDENTICAL:
				case T_IS_NOT_EQUAL:
				case T_IS_NOT_IDENTICAL:
				case T_IS_SMALLER_OR_EQUAL:
				case T_LOGICAL_AND:
				case T_LOGICAL_OR:
				case T_LOGICAL_XOR:
				case T_MINUS_EQUAL:
				case T_MOD_EQUAL:
				case T_MUL_EQUAL:
				case T_OBJECT_CAST:
				case T_OBJECT_OPERATOR:
				case T_OR_EQUAL:
				case T_PAAMAYIM_NEKUDOTAYIM:
				case T_PLUS_EQUAL:
				case T_SL:
				case T_SL_EQUAL:
				case T_SR:
				case T_SR_EQUAL:
				case T_START_HEREDOC:
				case T_STRING_CAST:
				case T_UNSET_CAST:
				case T_XOR_EQUAL:
					$result[] = '<span style="color: #b5b4cf">'.$tok[1].'</span>';
					break;
				
				// Constants
				case T_CLASS_C:
				case T_DIR:
				case T_FILE:
				case T_FUNC_C:
				case T_METHOD_C:
					$result[] = '<span style="color: #6b63ab">'.$tok[1].'</span>';
					break;
				
				// String
				case T_CONSTANT_ENCAPSED_STRING:
				case T_ENCAPSED_AND_WHITESPACE:
				case T_NUM_STRING:
				case T_STRING_VARNAME:
					$result[] = '<span style="color: #9868ab">'.$tok[1].'</span>';
					break;
				
				// Comments
				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_LINE:
				case T_NS_C:
					$result[] = '<span style="color: #c486db">'.$tok[1].'</span>';
					break;
				 
				// Whitespace
				case T_WHITESPACE:
				case T_NS_SEPARATOR:
					$result[] = strtr(htmlentities($tok[1]), array(' ' => '&nbsp;', "\t" => '&nbsp;&nbsp;&nbsp;&nbsp;'));
					break;
				
				// Variable
				case T_VARIABLE:
					$result[] = '<span style="color: #6ca373">'.$tok[1].'</span>';
					break;
					
				// Number
				case T_DNUMBER:
				case T_LNUMBER:
					$result[] = '<span style="color: #ac31ae">'.$tok[1].'</span>';
					break;
				
				// Skip
				case T_CLOSE_TAG:
				case T_INLINE_HTML:
				case T_OPEN_TAG:
				case T_OPEN_TAG_WITH_ECHO:
				
			}
		}
		
		return implode('', $result);
	}
}


/* End of file ExceptionCatcher.php */
/* Location: src/php/Inject/Web/Middleware */