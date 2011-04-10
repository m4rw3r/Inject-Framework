<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Middleware;

use \Inject\Core\Middleware\MiddlewareInterface;

/**
 * Catches X-Cascade == pass headers and replaces with a 404 response.
 * 
 * Example usage with a renderer:
 * <code>
 * protected function initMiddleware()
 *     //...
 *     $engine = $this;
 *     //...
 *     new \Inject\Web\Middleware\NotFoundCatcher(function($env) use($engine)
 *     {
 *         $r = new \Inject\Renderer\BasicPhp();
 * 
 *         return array(
 *                 404,
 *                 array('Content-Type' => 'text/html; charset=UTF-8'),
 *                 $r->render($engine->paths['views'].'My404.html.php')
 *             );
 *     }),
 *     //...
 * </code>
 * 
 * Example usage with a "404 controller":
 * <code>
 * protected function initMiddleware()
 *     //...
 *     $engine = $this;
 *     //...
 *     new \Inject\Web\Middleware\NotFoundCatcher(function($env) use($engine)
 *     {
 *         return \My\Controller404::stack($engine, 'action404')->run($env);
 *     }),
 *     //...
 * </code>
 */
class NotFoundCatcher implements MiddlewareInterface
{
	/**
	 * The next middleware/endpoint.
	 * 
	 * @var \Inject\Core\Middleware\MiddlewareInterface|Closure|ObjectImplementing__invoke
	 */
	protected $next;
	
	/**
	 * The callback which is called when a 404 occurs.
	 * 
	 * @var callback
	 */
	protected $render_callback = '\\Inject\\Web\\Middleware\\NotFoundCatcher::default404';
	
	// ------------------------------------------------------------------------

	/**
	 * @param  Closure|callback
	 */
	public function __construct($render_callback = '\\Inject\\Web\\Middleware\\NotFoundCatcher::default404')
	{
		$this->render_callback = $render_callback;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Tells this middleware which middleware or endpoint it should call if it
	 * wants the call-chain to proceed.
	 * 
	 * @param  \Inject\Core\Middleware\MiddlewareInterface|Closure|ObjectImplementing__invoke
	 */
	public function setNext($callback)
	{
		$this->next = $callback;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Forwards the request to the next middleware/endpoint, then checks return value
	 * for the X-Cascade: pass header and if found calls the $render_callback.
	 * 
	 * @param  array
	 * @return array(int, array(string => string), string)
	 */
	public function __invoke($env)
	{
		$callback = $this->next;
		$ret = $callback($env);
		
		if( ! isset($ret[1]['X-Cascade']) OR $ret[1]['X-Cascade'] != 'pass')
		{
			return $ret;
		}
		
		return call_user_func($this->render_callback, $env);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Default 404 render callback.
	 * 
	 * @param  array
	 * @return array(int, array(string => string), string)
	 */
	public static function default404($env)
	{
		// TODO: Make it more awesome
		return array(404, array(), sprintf('404: File Not Found: "%s"', $env['PATH_INFO']));
	}
}


/* End of file NotFoundCatcher.php */
/* Location: src/php/Inject/Web/Middleware */