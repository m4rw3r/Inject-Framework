<?php
/*
 * Created by Martin Wernståhl on 2011-03-23.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router\DestinationHandler;

use \Inject\RouterGenerator\VariableNameContainerInterface;
use \Inject\RouterGenerator\DestinationHandler\Redirect as BaseRedirect;

/**
 * Modified redirect DestinationHandler which uses the framework request
 * class to create an absolute URL relative to the application root if not
 * starting with "[a-z]+://".
 */
class Redirect extends BaseRedirect
{
	public function getCallCode(VariableNameContainerInterface $vars, $matches_var)
	{
		$path = array();
		foreach($this->redirect->getTokens() as $tok)
		{
			switch($tok[0])
			{
				case Tokenizer::CAPTURE:
					// PATH_INFO is not urlencoded, so no need to encode
					$path[] = $matches_var.'['.var_export($tok[1], true).']';
					break;
				case Tokenizer::LITERAL:
					$path[] = var_export($tok[1], true);
			}
		}
		
		return '// TODO: How to inject class used for Request->getDefaultUrlOptions()?
// TODO: Remove the lines below if the generated path is a full URL
$req = new \Inject\Web\Request('.$vars->getEnvVar().');
$url = \Inject\Web\Request::urlFor(array_merge($req->getDefaultUrlOptions(), array(\'path\' => '.implode('.', $path).')));

return array('.$this->redirect->getRedirectCode().', array(\'Location\' => $url), \'\');';
	}
}


/* End of file Redirect.php */
/* Location: src/php/Inject/Web/Router/DestinationHandler */