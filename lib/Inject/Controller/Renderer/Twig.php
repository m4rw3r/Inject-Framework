<?php
/*
 * Created by Martin Wernståhl on 2010-02-27.
 * Copyright (c) 2010 Martin Wernståhl.
 * All rights reserved.
 */

/**
 * Twig based view renderer.
 */
class Inject_Controller_Renderer_Twig implements Inject_Controller_RenderInterface
{
	public $twig_loader;
	
	public $twig;
	
	function __construct(Inject_Request $req)
	{
		$paths = array();
		foreach(Inject::getApplicationPaths() as $p)
		{
			$paths[] = $p.'Views';
		}
		
		$this->twig_loader = new Twig_Loader_Filesystem($paths);
		
		$cachedir = Inject::getIsProduction() ? Inject::getCacheFolder().'Twig' : false;
		
		$this->twig = new Twig_Environment($this->twig_loader, array(
		  'cache' => $cachedir
		));
		
		$this->twig->addExtension(new Twig_Extension_Escaper(true));
		// $this->twig->addExtension(new Inject_Twig_Extension($req));
	}
	
	// ------------------------------------------------------------------------
	
	public function render($view_name, $data = array(), $file_ext = 'php')
	{
		$t = $this->twig->loadTemplate($view_name.'.'.$file_ext);
		
		return $t->render($data);
	}
}


/* End of file Twig.php */
/* Location: ./lib/Inject/Controller/Renderer */