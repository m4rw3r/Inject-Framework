<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Web\Router;

use \Inject\Core\Engine;

use \Inject\RouterGenerator;

/**
 * 
 */
class GeneratorWrapper
{
	/**
	 * The application engine.
	 * 
	 * @var \Inject\Core\Engine
	 */
	protected $engine;
	
	/**
	 * The CodeGenerator instance used by this Generator.
	 * 
	 * @var \Inject\Web\Router\Generator\CodeGenerator
	 */
	protected $generator;
	
	/**
	 * The cached generated code, so we don't have to generate it anew for the
	 * cache file.
	 * 
	 * @var string
	 */
	protected $code;
	
	// ------------------------------------------------------------------------
	
	public function __construct(Engine $engine)
	{
		$this->engine        = $engine;
		$this->mapping_scope = new RouterGenerator\Scope(new RouterGenerator\Mapping());
		$this->generator     = new RouterGenerator\CodeGenerator();
		
		$this->generator->registerDestinationHandlers(array(
			'Inject\Web\Router\DestinationHandler\Controller',
			'Inject\Web\Router\DestinationHandler\Callback',
			'Inject\Web\Router\DestinationHandler\Engine',
			'Inject\Web\Router\DestinationHandler\Redirect'
			));
		
		$this->generator->setVariableNameContainer(new VariableNameContainer());
		
		$this->generator->setFailCode('return array(404, array(\'X-Cascade\' => \'pass\'), \'\');');
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Loads a routing config file.
	 * 
	 * @param  string  A path to the router configuration file
	 * @return void
	 */
	public function loadFile($file)
	{
		if( ! empty($this->code))
		{
			throw new \RuntimeException('Router has already been generated.');
		}
		
		$this->mapping_scope->loadFile($file);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Generates the routing code.
	 * 
	 * @return string  The routing code
	 */
	public function generateCode()
	{
		if(empty($this->code))
		{
			$this->generator->compileDefinitions($this->mapping_scope->getDefinitions(), array('engine' => $this->engine));
			
			$code = '$controllers = '.var_export($this->engine->getAvailableControllers(), true).';
$router 	= '.$this->generator->generateRouterCode().';';
			
			$code .= "\n\n\$reverse = ".$this->generator->generateReverseRouterCode();
			
			$this->code = $code."\n\nreturn array(\$router, \$reverse);";
		}
		
		return $this->code;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Returns an array containing the router closure and an array with reverse
	 * router closures.
	 * 
	 * @return array(Closure, array(string => Closure))
	 */
	public function getCompiledRoutes()
	{
		$engine = $this->engine;
		
		return eval($this->generateCode());
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Writes the router cache file.
	 * 
	 * @param  string   The file to write to
	 * @return void
	 */
	public function writeCache($path)
	{
		$file = tempnam(dirname($path), basename($path));
		
		$code = '<?php
/**
 * Route cache file generated on '.date('Y-m-d H:i:s').' by Inject Framework Router
 * ('.__CLASS__.').
 */

namespace Inject\Web\Router;

';
		
		$code .= $this->generateCode();
		
		if(@file_put_contents($file, $code))
		{
			if(@rename($file, $path))
			{
				chmod($path, 0644);
				
				return;
			}
		}
		
		// TODO: Exception
		throw new \Exception(sprintf('Cannot write to the %s directory', basename($path)));
	}
}


/* End of file Router.php */
/* Location: src/php/Inject/Web/Router */