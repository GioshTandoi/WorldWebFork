<?php
if (!defined('BLARG')) trigger_error();

$bbcodeCallbacks = [
	'[b' => 'bbcodeBold',
	'[i' => 'bbcodeItalics',
    '[u'=> 'bbcodeUnderline',
	'[s' => 'bbcodeStrikethrough',
	'[center' => 'bbcodeCenter',

	'[url' => 'bbcodeURL',
	'[urlnf' => 'bbcodeURLnf',
	'[img' => 'bbcodeImage',
	'[imgs' => 'bbcodeImageScale',

	'[user' => 'bbcodeUser',
	'[thread' => 'bbcodeThread',
	'[forum' => 'bbcodeForum',

	'[quote' => 'bbcodeQuote',
	'[reply' => 'bbcodeReply',

	'[spoiler' => 'bbcodeSpoiler',
	'[code' => 'bbcodeCode',

	'[table' => 'bbcodeTable',
	'[tr' => 'bbcodeTableRow',
	'[trh' => 'bbcodeTableRowHeader',
	'[td' =>'bbcodeTableCell',

	'[youtube' => 'bbcodeYoutube',
	'[vidmeo' => 'bbcodeVidmeo',
	'[gist' => 'bbcodeGist',

	'[instameme' => 'bbcodeMeme',
	
	//Color BBCode Starts here
	'[color'  => 'bbcodecolordefault',
	'[colour' => 'bbcodecolourdefault',
	'[purple' => 'bbcodecolorpurple',
	'[yellow' => 'bbcodecoloryellow',
	'[orange' => 'bbcodecolororange',
	'[violet' => 'bbcodecolorviolet',
	'[indigo' => 'bbcodecolorindigo',
	'[red'	=> 'bbcodecolorred',
	'[blue'   => 'bbcodecolorblue',
	'[bleu'   => 'bbcodecolorbleu',
	'[pink'  => 'bbcodecolorpink',
	'[green'  =>'bbcodecolorgreen',
	'[white'  => 'bbcodecolorwhite',
	'[black'  => 'bbcodecolorblack',
	'[rouge'  => 'bbcodecolorrouge',
	'[grey'   => 'bbcodecolorgrey',
	'[gray'  => 'bbcodecolorgray',
];

//Allow plugins to register their own callbacks (new bbcode tags)
$bucket = 'bbcode'; include(__DIR__.'/pluginloader.php');

function bbcodeBold($contents)
{
	return "<strong>$contents</strong>";
}
function bbcodeItalics($contents)
{
	return "<em>$contents</em>";
}
function bbcodeUnderline($contents)
{
	return "<u>$contents</u>";
}
function bbcodeStrikethrough($contents)
{
	return "<del>$contents</del>";
}
function bbcodeCenter($contents)
{
	return "<center>$contents</center>";
}

function bbcodeURL($contents, $arg)
{
	$dest = htmlentities($contents);
	$title = $contents;

	if(isset($arg))
		$dest = htmlentities($arg);

	return '<a href="'.$dest.'">'.$title.'</a>';
}
function bbcodeURLnf($contents, $arg)
{
	$dest = htmlentities($contents);
	$title = $contents;

	if(isset($arg))
		$dest = htmlentities($arg);

	return '<a href="'.$dest.'" rel="nofollow">'.$title.'</a>';
}

function bbcodeURLAuto($match) {
	$text = $match[0];
	$text = html_entity_decode($text);
	return '<a href="'.htmlspecialchars($text).'">'.htmlspecialchars($text).'</a>';
}

function bbcodeImage($contents, $arg)
{
	$dest = $contents;
	$title ='';
    if(isset($arg))	{
		$title = $contents;
		$dest = $arg;
	}

	// I can't into camo yet
	if (strpos($dest, 'https://images.weserv.nl/?url=') !== 0 && strpos($dest, getServerURLNoSlash($ishttps)) !== 0) {
		if (strpos($dest, 'http://images.weserv.nl/?url=') === 0)
			$dest = 'https://' . substr($dest, strlen('http://'));
		else if (strpos($dest, 'http://') !== 0 && strpos($dest, 'https://') !== 0)
			$dest = 'https://images.weserv.nl/?url=' . $dest;
		else if (strpos($dest, 'http://') === 0)
			$dest = 'https://images.weserv.nl/?url=' . substr($dest, strlen('http://'));
		else if (strpos($dest, 'https://') === 0)
			$dest = 'https://images.weserv.nl/?url=ssl:' . substr($dest, strlen('https://'));
	}

	return '<img class="imgtag" src="'.htmlspecialchars($dest).'" alt="'.htmlspecialchars($title).'"/>';
}


function bbcodeImageScale($contents, $arg)
{
	$dest = $contents;
	$orig = $dest;
	$title = '';
    if(isset($arg))	{
		$title = $contents;
		$dest = $arg;
		$orig = $dest;
	}

	// I can't into camo yet
	if (strpos($dest, 'https://images.weserv.nl/?url=') !== 0 && strpos($dest, getServerURLNoSlash($ishttps)) !== 0) {
		if (strpos($dest, 'http://images.weserv.nl/?url=') === 0)
			$dest = 'https://' . substr($dest, strlen('http://'));
		else if (strpos($dest, 'http://') !== 0 && strpos($dest, 'https://') !== 0)
			$dest = 'https://images.weserv.nl/?url=' . $dest;
		else if (strpos($dest, 'http://') === 0)
			$dest = 'https://images.weserv.nl/?url=' . substr($dest, strlen('http://'));
		else if (strpos($dest, 'https://') === 0)
			$dest = 'https://images.weserv.nl/?url=ssl:' . substr($dest, strlen('https://'));
	}

	return '<a href="'.htmlspecialchars($orig).'" target="_blank"><img class="imgtag" style="max-width:300px; max-height:300px;" src="'.htmlspecialchars($dest).'" alt="'.htmlspecialchars($title).'"/></a>';
}


function bbcodeUser($arg)
{
	return UserLinkById((int)$arg);
}
/*per risolvere la 9337 nella funzione  bbcodeThread ho inserito la variabile $threadLinkCache come paramentro
opzionale con opportuno valore di default, ho invece eliminato la variabile $loguser che non veniva utilizzata
da nessuna parte nella funzione. Ho inoltre calcellato i parametri $parenttag e $contents anche essi inutilizzati
from Giosh96 */
function bbcodeThread($arg, $threadLinkCache=[]) {

	$id = (int)$arg;
	if(!isset($threadLinkCache[$id])) {
		$rThread = Query('select t.id, t.title, t.forum from {threads} t where t.id={0} AND t.forum IN ({1c})', $id, ForumsWithPermission('forum.viewforum'));
		if(NumRows($rThread))
		{
			$thread = Fetch($rThread);
			$threadLinkCache[$id] = makeThreadLink($thread);
		}
		else
			$threadLinkCache[$id] = '&lt;invalid thread ID&gt;';
	}
	return $threadLinkCache[$id];
}
/*per risolvere la 9337 nella funzione  bbcodeForum ho inserito la variabile $forumLinkCache come paramentro
opzionale con opportuno valore di default, ho invece eliminato la variabile $loguser che non veniva utilizzata
da nessuna parte nella funzione. Ho inoltre calcellato i parametri $parenttag e $contents anche essi inutilizzati
from Giosh96 */
function bbcodeForum($arg, $forumLinkCache=[])
{
	global ;
	$id = (int)$arg;
	if(!isset($forumLinkCache[$id]))
	{
		$rForum = Query('select id, title from {forums} where id={0} AND id IN ({1c})', $id, ForumsWithPermission('forum.viewforum'));
		if(NumRows($rForum))
		{
			$forum = Fetch($rForum);
			$forumLinkCache[$id] = actionLinkTag($forum['title'], 'forum', $forum['id'], '', HasPermission('forum.viewforum',$forum['id'],true)?$forum['title']:'');
		}
		else
			$forumLinkCache[$id] = '&lt;invalid forum ID&gt;';
	}
	return $forumLinkCache[$id];
}

function bbcodeQuote($contents, $arg)
{
	return bbcodeQuoteGeneric($contents, $arg, __('Posted by'));
}

function bbcodeReply($contents, $arg)
{
	return bbcodeQuoteGeneric($contents, $arg, __('Sent by'));
}

function bbcodeQuoteGeneric($contents, $arg, $text)
{
    if(!isset($arg))
        return "<div class='quote'><div class='quotecontent'>$contents</div></div>";

	// Possible formats:
	// [quote=blah]
	// [quote="blah blah" id="123"]

	if(preg_match('/"(.*)" id="(.*)"/', $arg, $match))
	{
		$who = htmlspecialchars($match[1]);
		$id = (int) $match[2];
		return "<div class='quote'><div class='quoteheader'><a href=\"".htmlentities(actionLink('post', $id))."\">$text $who</a></div><div class='quotecontent'>$contents</div></div>";
	}
	else
	{
		if ($arg[0] == '"') $arg = substr($arg,1,-1);
		$who = htmlspecialchars($arg);
		return "<div class='quote'><div class='quoteheader'>$text $who</div><div class='quotecontent'>$contents</div></div>";
	}
}

function bbcodeSpoiler($contents, $arg)
{
    if(isset($arg))
        return "<div class=\"spoiler\"><button class=\"spoilerbutton named\">".htmlspecialchars($arg)."</button><div class=\"spoiled hidden\">$contents</div></div>";
	else
		return "<div class=\"spoiler\"><button class=\"spoilerbutton\">Show spoiler</button><div class=\"spoiled hidden\">$contents</div></div>";
}

function bbcodeCode($contents)
{
	return '<pre><code>'.htmlentities($contents).'</code></pre>';
}

function bbcodeTable($contents)
{
	return "<table class=\"outline margin\">$contents</table>";
}

$bbcodeCellClass = 0;

function bbcodeTableCell($contents, $parenttag) {
	if($parenttag == '[trh')
		return "<th>$contents</th>";
	else
		return "<td>$contents</td>";
}
/*per risolvere la 9337 nella funzione  bbcodeTableRow e  bbcodeTableRowHeader ho inserito la variabile
$bbcodeCellClass come paramentro opzionale con opportuno valore di default, e inserito la variabile in un array che ritorna sia la stringa
che il valore della variabile che ha modificato.
Ho inoltre calcellato i parametri $arg, $parenttag perchè inutilizzati
from Giosh96 */
function bbcodeTableRow($contents, $bbcodeCellClass=0) {

	$bbcodeCellClass++;
	$bbcodeCellClass %= 2;

	return array("<tr class=\"cell$bbcodeCellClass\">$contents</tr>",$bbcodeCellClass );
}

function bbcodeTableRowHeader($contents, $bbcodeCellClass=0) {

	$bbcodeCellClass++;
	$bbcodeCellClass %= 2;

	return array("<tr class=\"header0\">$contents</tr>",$bbcodeCellClass) ;
}

function getYoutubeIdFromUrl($url)
{
	$pattern =
		'%^# Match any youtube URL
		(?:https?://)?  # Optional scheme. Either http or https
		(?:www\.)?	  # Optional www subdomain
		(?:			 # Group host alternatives
		  youtu\.be/	# Either youtu.be,
		| youtube\.com  # or youtube.com
		  (?:		   # Group path alternatives
			/embed/	 # Either /embed/
		  | /v/		 # or /v/
		  | /watch\?v=  # or /watch\?v=
		  )			 # End path alternatives.
		)			   # End host alternatives.
		([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
		$%x'
		;
	$result = preg_match($pattern, $url, $matches);
	if (false !== $result) {
		return $matches[1];
	}
	return false;
}

function bbcodeYoutube($contents)
{
	$contents = trim($contents);
	$id = getYoutubeIdFromUrl($contents);
	if(isset($id))
		$contents = $id;

	if(!preg_match("/^[\-0-9_a-zA-Z]+$/", $contents))
		return '[Invalid youtube video ID]';

	return '[youtube]'.$contents.'[/youtube]';
}

function getVimeoIdFromUrl($url)
{
	$pattern =
		'%^# Match any youtube URL
		(?:https?://)?	# Optional scheme. Either http or https
		(?:www\.)?		# Optional www subdomain
		vimeo.com/		# The host,
		  (?:			# Group path alternatives
			/video/		# either /v/
		  | /			# or the root
		  )				# End path alternatives.
		([\w-]{8,10})	# Allow 8-10 for 9 char vimeo id.
		$%x'
		;
	$result = preg_match($pattern, $url, $matches);
	if (false !== $result) {
		return $matches[1];
	}
	return false;
}

function bbcodeVimeo($contents) {
	$contents = trim($contents);
	$id = getVimeoIdFromUrl($contents);
	if(isset($id))
		$contents = $id;

	if(!preg_match("/^[\-0-9_a-zA-Z]+$/", $contents))
		return '[Invalid vimeo video ID]';

	return '[vimeo]'.$contents.'[/vimeo]';
}
//elimnata args perchè non più utile
function bbcodeGist($contents) {
	if (!function_exists('curl_init')) {
		return "<a href=\"https://gist.github.com/$contents\">View $contents on GitHub</a>";
	}
	else if (!preg_match('/([0-9_a-zA-Z]+)\/([0-9a-f]+)/', $contents)) {
		return '<pre><code>Invalid Gist</code></pre>';
	}
	else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://gist.githubusercontent.com/$contents/raw");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$code = curl_exec($ch);
		curl_close($ch);
		return "<pre><code>$code</code></pre>";
	}
}

function bbcodeMeme($contents, $arg) {
	//Detecting what meme to use
	if ($arg == '1' or $contents == '1')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme1.jpg" alt="Instameme1"/>';
	else if ($arg == '2' or $contents == '2')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme2.jpg" alt="Instameme2"/>';
	else if ($arg == '3' or $contents == '3')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme3.jpg" alt="Instameme3"/>';
	else if ($arg == '4' or $contents == '4')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme4.png" alt="Instameme4"/>';
	else if ($arg == '5' or $contents == '5')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme5.jpg" alt="Instameme5"/>';
	else if ($arg == '6' or $contents == '6')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme6.png" alt="Instameme6"/>';
	else if ($arg == '7' or $contents == '7')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme7.jpg" alt="Instameme7"/>';
	else if ($arg == '8' or $contents == '8')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/instameme8.jpg" alt="Instameme8"/>';
	//I guess intergrate the ban tag too, it doesn't really need its own tag
	else if ($contents == 'ban' or $arg == 'ban')
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/banhammer.jpg" alt="You got banned"/>';
	//If a number was found that is not in the collection
	else
		return '<img class="imgtag" style="max-width:300px; max-height:300px;" src="../../img/instameme/insta404.jpg" alt="Instameme404"/>';
}

//Code for Color BBCode Starts Here.

function bbcodeColor($contents, $arg)
{
	return "<div style=\"color: ".htmlspecialchars($arg).";\">$contents</div>";
}
function bbcodeColour($contents, $arg)
{
	return "<div style=\"color: ".htmlspecialchars($arg).";\">$contents</div>";
}
function bbcodeColorred($contents)
{
	return "<div style=\"color: #FF0000;\">$contents</div>";
}
function bbcodeColoryellow($contents)
{
	return "<div style=\"color: #FFFF00;\">$contents</div>";
}
function bbcodeColorgreen($contents)
{
	return "<div style=\"color: #008000;\">$contents</div>";
}

function bbcodeColorblue($contents, $arg){

	switch($arg){
        case 'dark':
            return "<div style=\"color: #00008B;\">$contents</div>";
            break;

        case 'light':
            return "<div style=\"color: #ADD8E6;\">$contents</div>";
            break;

        case 'alice':
            return "<div style=\"color: #F0F8FF;\">$contents</div>";
            break;

        default:
            return "<div style=\"color: #0000FF;\">$contents</div>";
            break;

    }
}


function bbcodeColorwhite($contents)
{
	return "<div style=\"color: #FFFFFF;\">$contents</div>";
}
function bbcodeColorpurple($contents)
{
	return "<div style=\"color: #800080;\">$contents</div>";
}
function bbcodeColorrouge($contents)
{
	return "<div style=\"color: #FF0000;\">$contents</div>";
}
function bbcodeColorOrange($contents)
{
	return "<div style=\"color: #FFA500;\">$contents</div>";
}
function bbcodeColorIndigo($contents)
{
	return "<div style=\"color: #4B0082;\">$contents</div>";
}
function bbcodeColorPink($contents)
{
	return "<div style=\"color: #FFC0CB;\">$contents</div>";
}
function bbcodeColorGrey($contents)
{
	return "<div style=\"color: #808080;\">$contents</div>";
}
function bbcodeColorGray($contents)
{
	return "<div style=\"color: #808080;\">$contents</div>";
}
function bbcodeColorBlack($contents)
{
	return "<div style=\"color: #000000;\">$contents</div>";
}
