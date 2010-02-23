<?php
/*
 * Created by Martin Wernståhl on 2010-02-23.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Static route matcher builder, for routes which does not utilize regex matches.
 */
class Inject_Request_HTTP_URI_RouteBuilderStatic extends Inject_Request_HTTP_URI_RouteBuilderAbstract
{
	public function __construct($pattern, $options)
	{
		parent::__construct($pattern, $options);
	}
	
	public function getMatchCode()
	{
		// Static routes are only to match
		return 'if($uri === \''.addcslashes($this->pattern, '\'').'\')
		{
			return '.var_export($this->options, true).';
		}';
	}
	
	public function getReverseMatchCode()
	{
		// should we have any specific parameters?
		$param_check = empty($this->parameters) ? 'empty($params)' : var_export($this->parameters, true).' == $params';
		
		if( ! $this->hasAction())
		{
			// No action check
			return 'if('.$param_check.')
				{
					return \''.addcslashes($this->pattern, "'").'\';
				}';
		}
		else
		{
			// Action check
			return 'if($options[\'_action\'] === \''.$this->getAction().'\' && '.$param_check.')
				{
					return \''.addcslashes($this->pattern, "'").'\';
				}';
		}
	}
}


/* End of file RouteBuilderStatic.php */
/* Location: ./lib/Inject/Request/HTTP/URI */