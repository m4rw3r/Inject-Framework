<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\Generator\Destination;

use \Inject\Web\Router\Route;
use \Inject\Web\Router\Generator\Tokenizer;

/**
 * 
 */
class Controller extends AbstractDestination
{
	/**
	 * A list of default values for controller requests.
	 * 
	 * @var array(string => string)
	 */
	protected $defaults = array(
			'action' => 'index',
			'format' => 'html'
		);
	
	protected $options;
	
	protected function doValidation(Tokenizer $tokenizer)
	{
		$this->options = array_merge($this->defaults, $this->route->getOptions());
		
		if(preg_match('/^((?:\\\\)?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*)#([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$/', $this->route->getTo(), $matches))
		{
			// controller#action
			$this->controller        = $this->translateShortControllerName($matches[1]);
			$this->options['action'] = $matches[2];
		}
		elseif(preg_match('/^((?:\\\\)?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*)#$/', $this->route->getTo(), $matches))
		{
			// controller#
			$this->controller = $this->translateShortControllerName($matches[1]);
		}
		else
		{
			throw new \Exception(sprintf('The route %s does not have a compatible To value, expected controller#action or controller#.', $this->route->getRawPattern()));
		}
	}
	
	public function getCompiled()
	{
		$this->compile();
		
		return array(new Route\ControllerRoute($this->pattern, $this->options, $this->capture_intersect, $this->route->getAcceptedRequestMethods(), $this->controller));
	}
	
	public function getCacheCode($var_name, $controller_var)
	{
		$this->compile();
		
		return $var_name.' = new Route\ControllerRoute('.var_export($this->pattern, true).', '.var_export($this->options, true).', '.var_export($this->capture_intersect, true).', '.var_export($this->route->getAcceptedRequestMethods(), true).', '.var_export($this->controller, true).');';
	}
}


/* End of file Callback.php */
/* Location: lib/Inject/Web/Router/Generator/Destination */