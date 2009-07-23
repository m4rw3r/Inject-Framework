<?php
/*
 * Created by Martin Wernståhl on 2009-04-18.
 * Copyright (c) 2009 Martin Wernståhl.
 * All rights reserved.
 */

if(isset($_GET['p']))
{
	$page = str_replace('..', '', $_GET['p']);
	$page = str_replace('//', '/', $page);
	$page = trim($page, '\\/');
}
else
{
	$page = 'index';
}

$parts = explode('/', $page);
$base = implode('/', array_slice($parts, 0, -1));

$files = glob('./manual'.($base ? '/'.$base : '').'/*'.end($parts).'.html');

if(empty($files))
{
	die('404 Error');
}

$p = get_data(array_shift($files));

$p->nav = nav($parts);

$p->content = parse($p->content);

$p->base = '';

require './template.php';

// ------------------------------------------------------------------------

/**
 * Returns the data of the page
 * 
 * @return stdClass
 */
function get_data($file)
{
	if( ! file_exists($file))
	{
		return false;
	}
	
	$data = file_get_contents($file);
	$result = new stdClass();
	
	$end = strpos($data, "\n");
	$result->title = substr($data, 0, $end);
	$result->content = substr($data, $end);
	
	return $result;
}

// ------------------------------------------------------------------------

/**
 * Renders the navigation.
 * 
 * @return string
 */
function nav($uri, $base = '')
{
	$current = array_shift($uri);
	$str = '';
	$base = ($base ? $base.'/' : '');
	
	$main = glob('./manual/'.$base.'*.html');
	
	foreach($main as $part)
	{
		$link = preg_replace('/[\d]+/', '', str_replace(array('./manual/', '.html'), array('', ''), $part));
		
		if($base.$current == $link)
		{
			// highlight
			$str .= '<li><a href="index.php?p='.$link.'" class="current">'.get_data($part)->title.'</a>';
			
			// deeper?
			if(is_dir('./manual/'.$link))
			{
				$d = nav($uri, $link);
				
				if( ! empty($d))
				{
					$str .= '<ul>'.$d.'</ul>';
				}
			}
		}
		else
		{
			$str .= '<li><a href="index.php?p='.$link.'">'.get_data($part)->title.'</a>';
		}
		
		$str .= '</li>';
	}
	
	return $str;
}

// ------------------------------------------------------------------------

/**
 * Converts macros etc.
 * 
 * @return string
 */
function parse($str)
{
	// link to other page, with auto title
	while(strpos($str, '{link:') && preg_match('@([\w\W]*?)\{link:([^}|]+)(?:\|)?([^}]*)\}([\W\w]*)@', $str, $match))
	{
		$file = explode('/', $match[2]);
		$tmp = array_pop($file);
		$file = implode('/', $file).'/*'.$tmp;
		
		$file = glob('./manual/'.$file.'.html');
		
		if(empty($file) OR ! $file = get_data(array_pop($file)))
		{
			$replacement = '<span style="color: #f00;">ERROR LINKING TO: '.$match[2].'</span>';
		}
		else
		{
			$replacement = '<a href="index.php?p='.$match[2].'" title="'.$file->title.'">'.(( ! empty($match[3])) ? $match[3] : $file->title).'</a>';
		}
		
		$str = $match[1].$replacement.$match[4];
	}
	
	// escape the code and insert linebreaks and spaces
	$str = preg_replace('/<code>([\w\W]*?)<\/code>/ie', "'<code>'.convert_code('\\1').'</code>'", $str);
	
	$str = preg_replace('/<file>([\w\W]*?)<\/file>/i', '<tt><kbd>\\1</kbd></tt>', $str);
	$str = preg_replace('/<dir>([\w\W]*?)<\/dir>/i', '<tt><dfn>\\1</dfn></tt>', $str);
	
	while(strpos($str, '{term:') && preg_match('@([\w\W]*?)\{term:([^}|]+)(?:\|)?([^}]*)\}([\W\w]*)@', $str, $match))
	{
		$replacement = '<a href=index.php?p=index/terminology#'.$match[2].'">'.(( ! empty($match[3])) ? $match[3] : $match[2]).'</a>';
		
		$str = $match[1].$replacement.$match[4];
	}
	
	while(strpos($str, '<warning>') && preg_match('@([\w\W]*?)<warning>([\w\W]*?)</warning>([\W\w]*)@', $str, $match))
	{
		$replacement = '<p class="warning"><strong>Warning: </strong> '.$match[2].'</p>';
		
		$str = $match[1].$replacement.$match[3];
	}
	
	while(strpos($str, '<important>') && preg_match('@([\w\W]*?)<important>([\w\W]*?)</important>([\W\w]*)@', $str, $match))
	{
		$replacement = '<p class="important"><strong>Important: </strong> '.$match[2].'</p>';
		
		$str = $match[1].$replacement.$match[3];
	}
	
	
	while(strpos($str, '<tip>') && preg_match('@([\w\W]*?)<tip>([\w\W]*?)</tip>([\W\w]*)@', $str, $match))
	{
		$replacement = '<p class="tip"><strong>Tip: </strong> '.$match[2].'</p>';
		
		$str = $match[1].$replacement.$match[3];
	}
	
	// emdash
	$str = str_replace('---', '&mdash;', $str);
	
	// endash
	$str = str_replace('--', '&ndash;', $str);
	
	return str_replace('{BASEPATH}', '.', $str);
}


// ------------------------------------------------------------------------

/**
 * Converts the data between <code> tags to htmlentities.
 * 
 * @return string
 */
function convert_code($str)
{
	$str = trim($str);
	
	$str = str_replace('\\"', '"', $str);
	
	// if we have a comment, we have to use another string combination to remove it
	if(strpos($str, '/*') === 0 OR strpos($str, '//') === 0)
	{
		$str = highlight_string('<?php 12345676' . $str, true);
	
		// remove code blocks + <?php
		$str = preg_replace('/<code>[\w\W]*12345676<\/span>([\w\W]*)<\/code>/i', '\\1', $str);
	}
	else
	{
		$str = highlight_string('<?php /* 12345676 */' . $str, true);
	
		// remove code blocks + <?php
		$str = preg_replace('/^<code>[\w\W]*\/\*&nbsp;12345676&nbsp;\*\/<\/span>([\w\W]*)<\/code>$/i', '\\1', $str);
	}
	
	// change colors:
	$colors = array(
		'style="color: #0000BB"' => 'class="function"',
		'style="color: #007700"' => 'class="operator"',
		'style="color: #DD0000"' => 'class="string"',
		'style="color: #FF8000"' => 'class="comment"');
	$str = str_replace(array_keys($colors), array_values($colors), $str);
		
	return $str;
	/*$str = nl2br(htmlentities(trim(str_replace(array('\\"', '\\\\'), array('"', '\\'), $str))));
	return str_replace(array("\t", '  '), array('&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;&nbsp;'), $str);*/
}

/* End of file index.php */
/* Location: ./doc */