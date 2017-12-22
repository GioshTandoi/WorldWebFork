<?php
if (!defined('BLARG')) trigger_error();

// TODO make this work in templates at all
// we'll consider it whenever there's enough demand.

//define("PHASE", 2);

$language = Settings::get('defaultLanguage');

include_once __DIR__.'/lang/'.$language.'.php';
if($language != 'en_US')
	include_once __DIR__.'/lang/'.$language.'_lang.php';

// Funzione creata da Gabriele Pisciotta per bypassare vulnerabilità di SSRF
function get_data()
{
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/*risoluzione 9337:
da    function __($english, $flags = 0)
{
	global $languagePack, $language;
a  function __($english, $languagePack=null, $language=null, $flags = 0)
From Giosh96  */

function __($english, $languagePack=null, $language=null, $flags = 0)
{
	if($language != 'en_US')
	{
		if(!isset($languagePack))
		{
			if(is_file(__DIR__.'/lang/'.$language.'.txt'))
			{
				importLanguagePack();
				importPluginLanguagePacks($language.'.txt');
			}
			else
				$final = $english;
		}
		if(!isset($languagePack))
			$languagePack = [];
		$eDec = html_entity_decode($english, ENT_COMPAT, 'UTF-8');
		if(array_key_exists($eDec, $languagePack))
			$final = $languagePack[$eDec];
		elseif(array_key_exists($english, $languagePack))
			$final = $languagePack[$english];
		if($final == '')
			$final = $english; //$final = "[".$english."]";
	}
	else
		$final = $english;

	if($flags & 1)
		return str_replace(' ', '&nbsp;', htmlspecialchars($final));
	else if($flags & 2)
		return html_entity_decode($final);
	return $final	;
}

function importLanguagePack()
{
	$languagePack=[];
	$f = get_data();
	$f = explode("\n", $f);

	$counterF=count($f);

	for($i = 0; $i < $counterF; $i++)
	{
		$k = trim($f[$i]);
		if($k == '' || $k[0] == '#')
			continue;
		$i++;
		$v = trim($f[$i]);
		if($v == '')
			continue;
		$languagePack[$k] = $v;
	}
	return $languagePack;
}

function importPluginLanguagePacks($file)
{   error_reporting(0);
	$pluginsDir = opendir('plugins');
	if($pluginsDir !== FALSE)
	while(($plugin = readdir($pluginsDir)) !== FALSE)
	{
		if($plugin == '.' || $plugin == '..') continue;
		if(is_dir('./plugins/'.$plugin))
		{
			$foo = './plugins/'.$plugin.'/'.$file;
			if(file_exists($foo))
				importLanguagePack();
		}
	}
}