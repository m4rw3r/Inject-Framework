<?php
/*
 * Created by Martin Wernståhl on 2011-03-06.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Console;

/**
 * 
 */
interface GeneratorInterface
{
	public function getShortHelp();
	
	public function getHelpText();
	
	public function getConsoleCommand();
	
	public function generate($env);
}


/* End of file GeneratorInterface.php */
/* Location: src/php/Inject/Console */