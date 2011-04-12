<?php
/*
 * Created by Martin Wernståhl on 2011-04-01.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web;

/**
 * Object wrapping the $env var to simplify interaction and to also
 * provide tools for interacting with the request.
 */
class Request
{
	/**
	 * Parameters from the router.
	 * 
	 * @var array(string => string)
	 */
	protected $path_params = array();
	
	/**
	 * Merged parameters of GET and router, router has precedence.
	 * 
	 * @var array(string => string)
	 */
	protected $params = array();
	
	/**
	 * Cache of the parsed HTTP_ACCEPT header.
	 * 
	 * @var array(string => float)
	 */
	protected $accept_cache = array();
	
	/**
	 * @param  array Environment array
	 */
	public function __construct($env)
	{
		$this->env = $env;
		
		if(isset($env['web.route_params']))
		{
			$this->params      = array_merge($env['inject.get'], $env['web.route_params']);
			$this->path_params = $env['web.route_params'];
		}
		else
		{
			$this->params = $env['inject.get'];
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the wrapped environment ($env) variable.
	 * 
	 * @return array
	 */
	public function getEnv()
	{
		return $this->env;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the specified parameter if it exists, otherwise it returns $default,
	 * contains both parameters from route and GET (route has precedence).
	 * 
	 * @param  string
	 * @param  mixed
	 * @return string
	 */
	public function param($name, $default = null)
	{
		return isset($this->params[$name]) ? $this->params[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the specified path parameter if it exists, otherwise returns $default.
	 * 
	 * @param  string
	 * @param  mixed
	 * @return string
	 */
	public function pathParam($name, $default = null)
	{
		return isset($this->path_params[$name]) ? $this->params[$name] : $default;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns true if this request is an XmlHttpRequest (X-Requested-With: XmlHttpRequest).
	 * 
	 * @return boolean
	 */
	public function isXhr()
	{
		return empty($this->env['HTTP_X_REQUESTED_WITH']) ? strtolower($this->env['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' : false;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Negotiates a Mime type with the client's Accept header, defaults to the
	 * mime type specified first in the $formats parameter.
	 * 
	 * @param  array|string  A list containing allowed mime-types, ordered by
	 *                       preference
	 * @return string
	 */
	public function negotiateMime($formats)
	{
		$formats = (Array) $formats;
		
		foreach($this->getAccepts() as $a => $v)
		{
			if($a == '*/*')
			{
				return current($formats);
			}
			elseif(in_array($a, $formats))
			{
				return $a;
			}
		}
		
		return current($formats);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the format of the request; the request (or route) parameter 'format'
	 * if present, otherwise takes the first parameter from the Accept header (getAccepts()).
	 * 
	 * @return string
	 */
	public function getFormat()
	{
		if( ! empty($this->params['format']))
		{
			return $this->params['format'];
		}
		else
		{
			return current(array_keys($this->getAccepts()));
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Sets the format used by this request.
	 * 
	 * @param  string
	 * @return void
	 */
	public function setFormat($value)
	{
		$this->params['format'] = $value;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the list of formats this request accepts, key is the format and
	 * the value is the priority, the array is sorted with the most preferred first.
	 * 
	 * Example return format:
	 * <code>
	 * array
	 *   'application/xml' => float 1
	 *   'application/xhtml+xml' => float 1
	 *   'image/png' => float 1
	 *   'text/html' => float 0.9
	 *   'text/plain' => float 0.8
	 *   '* /*' => float 0.5  // Escaped end of comment by adding a space
	 * </code>
	 * 
	 * @return array(string => float)
	 */
	public function getAccepts()
	{
		return empty($this->accept_cache) ? $this->accept_cache = $this->parseAccept() : $this->accept_cache;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses the HTTP_ACCEPT header and sorts the formats by priority.
	 * 
	 * @return array(string => float)
	 */
	protected function parseAccept()
	{
		if(empty($this->env['HTTP_ACCEPT']))
		{
			// Default
			// TODO: Allow configuration of default accept type?
			return array('text/html' => 1);
		}
		
		$formats = array();
		$types   = explode(',', $this->env['HTTP_ACCEPT']);
		
		foreach($types as $t)
		{
			preg_match('/^\s*([^\s;]+)\s*(?:;\s*q=([\d\.]+))?/', $t, $matches);
			
			$type_name = $matches[1];
			$quality   = empty($matches[2]) ? 1.0 : $matches[2];
			
			$formats[$type_name] = (double)$quality;
		}
		
		arsort($formats);
		
		return $formats;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns the default URL construction options.
	 * 
	 * @return array(string => string)
	 */
	public function getDefaultUrlOptions()
	{
		return array(
			'protocol'    => $this->env['REQUEST_PROTOCOL'],
			'host'        => $this->env['SERVER_NAME'],
			'port'        => $this->env['SERVER_PORT'] == 80 ? '' : $this->env['SERVER_PORT'],
			'script_name' => $this->env['SCRIPT_NAME']
			);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Constructs a URL from a set of options.
	 * 
	 * Options:
	 * 'protocol':    Protocol to use, usually 'http' or 'https'
	 * 'host':        Hostname
	 * 'port':        Server port
	 * 'script_name': Front controller
	 * 'path':        Path info, if it starts with '/', script_name won't be prepended
	 * 'only_path':   If to only generate the path info and forward
	 * 'params':      GET parameters
	 * 'anchor':      Anchor
	 * 
	 * @param  array(string => string)
	 * @return string
	 */
	public static function urlFor(array $options)
	{
		if( ! (isset($options['host']) OR isset($options['only_path']) && $options['only_path']))
		{
			// TODO: Exception
			throw new \Exception('No host to link to, please set $default_url_options[\'host\'], $options[\'host\'] or $options[\'only_path\'].');
		}
		
		if(preg_match('#^[A-Za-z]+://#u', $options['path']))
		{
			return $options['path'];
		}
		
		$rewritten_url = '';
		
		if( ! (isset($options['only_path']) && $options['only_path']))
		{
			$rewritten_url .= isset($options['protocol']) ? $options['protocol'] : 'http';
			// TODO: Add authentication?
			$rewritten_url = trim($rewritten_url, '://').'://'.$options['host'];
			
			if(isset($options['port']) && ! empty($options['port']))
			{
				$rewritten_url .= ':'.$options['port'];
			}
		}
		
		if(strpos($options['path'], '/') === 0)
		{
			$rewritten_url .= $options['path'];
		}
		else
		{
			$rewritten_url .= (isset($options['script_name']) ? $options['script_name'] : '').'/'.$options['path'];
		}
		
		// GET parameters
		$rewritten_url .= empty($options['params']) ? '' : '?'.http_build_query($options['params']);
		
		if(isset($options['anchor']))
		{
			$rewritten_url .= '#'.urlencode($options['anchor']);
		}
		
		return $rewritten_url;
	}
}


/* End of file Request.php */
/* Location: src/php/Inject/Web */