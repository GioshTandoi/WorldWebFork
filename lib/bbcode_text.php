<?php
if (!defined('BLARG')) trigger_error();


/*risoluzione 9337: le variabili globali $smilies, $smiliesReplaceOrig, $smiliesReplaceNew venivano risettate copletamente ma
 il loro valore non veniva utilizzato, quindi le ho aggiunte tutte in un array che ritorna ogni suo valore
From Giosh96  */

// Misc things that get replaced in text.
function loadSmilies() {
		$rSmilies = Query('select * from {smilies} order by length(code) desc');
	$smilies = [];

	while($smiley = Fetch($rSmilies))
		$smilies[] = $smiley;

	$smiliesReplaceOrig = $smiliesReplaceNew = [];

	$counterSmilies=count($smilies);

	for ($i = 0; $i < $counterSmilies ; $i++) {
		$smiliesReplaceOrig[] = '/(?<!\w)'.preg_quote($smilies[$i]['code'], '/').'(?!\w)/';
		$smiliesReplaceNew[] = "<img class=\"smiley\" alt=\"\" src=\"".resourceLink('img/smilies/'.$smilies[$i]['image'])."\" />";
	}

	return array($smilies, $smiliesReplaceOrig, $smiliesReplaceNew);
}
/*risoluzione 9337: variabile globale $smiliesOrdered veniva risettata copletamente e il suo valore
non veniva utilizzato, quindi l'ho posta come valore di ritorno
From Giosh96  */
function loadSmiliesOrdered(){
		$rSmilies = Query('select * from {smilies}');
	// ho eliminato la variabile dichiarata $smilies = array(); perchè inutilizzata. From Giosh96
	while($smiley = Fetch($rSmilies))
		$smiliesOrdered[] = $smiley;

	return $smiliesOrdered;
}

// lol
function funhax($s)
{
	return 'DU'.str_repeat('R', strlen($s[0])-2);
}

function rainbowify($s)
{
	$r = mt_rand(0,359);
	$len = strlen($s);
	$out = '';
	for ($i = 0; $i < $len; $i++)
	{
		if ($s[$i] == ' ')
		{
			$out .= ' ';
			continue;
		}
		
		$out .= '<span style="color:hsl('.$r.',100%,80.4%);">'.$s[$i].'</span>';
		$r += 31;
		$r %= 360;
	}
	return $out;
}

/*risoluzione 9337:  in postDoReplaceText-->$postNoSmilies=false, $postPoster='',$smiliesReplaceOrig=[], $smiliesReplaceNew =[].
Inoltre $parentTag è stata eliminata dai parametri perchè non utilizzata
From Giosh96  */
//Main post text replacing.
function postDoReplaceText($s, $parentMask, $postNoSmilies=false, $postPoster='',$smiliesReplaceOrig=[], $smiliesReplaceNew =[]) {

	if(isset($postPoster))
		$s = preg_replace("'/me '",'<b>* '.htmlspecialchars($postPoster).'</b> ', $s);

	// silly filters
	//$s = preg_replace_callback('@\._+\.@', 'funhax', $s);
	//$s = str_replace(':3', ':3 '.rainbowify('ALL THE INSULTS I JUST SAID NOW BECOME LITTLE COLOURFUL FLOWERS'), $s);

	//Smilies
	if(!isset($postNoSmilies)) {
		if(!isset($smiliesReplaceOrig))
			LoadSmilies();
		if(isset($smiliesReplaceNew))
		    $s = preg_replace($smiliesReplaceOrig, $smiliesReplaceNew, $s);
	}
	
	//Automatic links
	// does it really have to be that complex?! we're not phpBB
	//$s = preg_replace_callback('((?:(?:view-source:)?(?:[Hh]t|[Ff])tps?://(?:(?:[^:&@/]*:[^:@/]*)@)?|\bwww\.)[a-zA-Z0-9\-]+(?:\.[a-zA-Z0-9\-]+)*(?::[0-9]+)?(?:/(?:->(?=\S)|&amp;|[\w\-/%?=+#~:\'@*^$!]|[.,;\'|](?=\S)|(?:(\()|(\[)|\{)(?:->(?=\S)|[\w\-/%&?=+;#~:\'@*^$!.,;]|(?:(\()|(\[)|\{)(?:->(?=\S)|l[\w\-/%&?=+;#~:\'@*^$!.,;])*(?(3)\)|(?(4)\]|\})))*(?(1)\)|(?(2)\]|\})))*)?)', 'bbcodeURLAuto', $s);
	if (!($parentMask & TAG_NOAUTOLINK))
	{
		$s = preg_replace_callback("@(?:(?:http|ftp)s?://|\bwww\.)[\w\-/%&?=+#~\'\@*^$\.,;!:]+[\w\-/%&?=+#~\'\@*^$]@i", 'bbcodeURLAuto', $s);
	}

	//Plugin bucket for allowing plugins to add replacements.
	//$bucket = 'postMangler';
	include(__DIR__.'/pluginloader.php');

	return $s;
}