<?php

define('BLARG', 1);
$ajaxPage = true;
define('MAIN_PAGE', 'home');
include __DIR__ . '/lib/common.php';
header('Cache-Control: no-cache');
header('Content-type: text/plain');

getBirthdaysText(false);

$action = $_GET['a'];
//Check if things are defined before using them, damnit!


if (isset($_GET['id']))
	$id = (int)$_GET['id'];
else
	$id = 0;

$hideTricks = ' <a href=\"javascript:void(0)\" onclick=\"hideTricks('.$id.')\">'.__('Back').'</a>';

switch ($action){

	case 'q':  //-------------------------------------------------------------------------------------------------------------------------------------Quote

        $qQuote = '	select
					p.id, p.deleted, pt.text,
					u.name poster
				from {posts} p
					left join {posts_text} pt on pt.pid = p.id and pt.revision = p.currentrevision
					left join {threads} t on t.id=p.thread
					left join {users} u on u.id=p.user
				where p.id={0} AND t.forum IN ({1c})';

        $rQuote = Query($qQuote, $id, ForumsWithPermission('forum.viewforum'));

        if(!NumRows($rQuote))
           trigger_error(__('Unknown post ID.'));

        $quote = Fetch($rQuote);

        if (isset($quote['deleted']))
            $quote['text'] = __('Post is deleted');

        $reply = "[quote=\"".$quote['poster']."\" id=\"".$quote['id']."\"]".$quote['text'].'[/quote]';
        $reply = str_replace('/me ', '[b]* '.htmlspecialchars($quote['poster']).'[/b]', $reply);
        trigger_error($reply);

	    break;

	case 'rp':   // ----------------------------------------------------------------------------------------------------------------------------retrieve post

        $rPost = Query('
			SELECT
				p.id, p.date, p.num, p.deleted, p.deletedby, p.reason, p.options, p.mood, p.ip,
				pt.text, pt.revision, pt.user AS revuser, pt.date AS revdate,
				u.(_userfields), u.(rankset,title,picture,posts,postheader,signature,signsep,lastposttime,lastactivity,regdate,globalblock,fulllayout),
				ru.(_userfields),
				du.(_userfields),
				t.forum fid
			FROM
				{posts} p
				LEFT JOIN {posts_text} pt ON pt.pid = p.id AND pt.revision = p.currentrevision
				LEFT JOIN {users} u ON u.id = p.user
				LEFT JOIN {users} ru ON ru.id=pt.user
				LEFT JOIN {users} du ON du.id=p.deletedby
				LEFT JOIN {threads} t ON t.id=p.thread
			WHERE p.id={0} AND t.forum IN ({1c})', $id, ForumsWithPermission('forum.viewforum'));


        if (!NumRows($rPost))
            trigger_error(__('Unknown post ID.'));
        $post = Fetch($rPost);

        if (!HasPermission('mod.deleteposts', $post['fid']) || $poster['id'] == $loguserid)
            trigger_error(__('No.'));

        trigger_error(MakePost($post, isset($_GET['o']) ? POST_DELETED_SNOOP : POST_NORMAL, ['tid'=>$post['thread'], 'fid'=>$post['fid']]));
		break;

	case 'ou': //--------------------------------------------------------------------------------------------------------------------------------------Online Users
        trigger_error(OnlineUsers((int)$_GET['f'], false));
        break;

	case 'tf': //---------------------------------------------------------------------------------------------------------------------------------------Theme File
        $theme = $_GET['t'];

        $themeFile = "themes/$theme/style.css";
        if(!file_exists($themeFile))
            $themeFile = "themes/$theme/style.php";

        function checkForImage(&$image, $external, $file) {
            if(isset($image)) return;

            if(isset($external)) {
                if(file_exists(DATA_DIR.$file))
                    $image = DATA_URL.$file;
            } else {
                if(file_exists($file))
                    $image = resourceLink($file);
            }
        }

        $layout_logopic = 'img/logo.png';

        trigger_error(resourceLink($themeFile).'|'.$layout_logopic);

        break;

	case 'srl':  //------------------------------------------------------------------------------------------------------------------------------------Show Revision List

        $qPost = 'select currentrevision, thread from {posts} where id={0}';
        $rPost = Query($qPost, $id);
        if(NumRows($rPost))
            $post = Fetch($rPost);
        else
            trigger_error(format(__('Unknown post ID #{0}.'), $id).' '.$hideTricks);

        $qThread = 'select forum from {threads} where id={0}';
        $rThread = Query($qThread, $post['thread']);
        $thread = Fetch($rThread);

        if (!HasPermission('forum.viewforum', $thread['forum']))
            trigger_error('You may not view this forum.');
        if (!HasPermission('mod.editposts', $thread['forum']))
            trigger_error('No.');


        $qRevs = 'SELECT
				revision, date AS revdate,
				ru.(_userfields)
			FROM
				{posts_text}
				LEFT JOIN {users} ru ON ru.id = user
			WHERE pid={0}
			ORDER BY revision ASC';
        $revs = Query($qRevs, $id);


        $reply = __('Show revision:').'<br />';
        while($revision = Fetch($revs)) {
            $reply .= ' <a href=\"javascript:void(0)\" onclick=\"showRevision('.$id.','.$revision['revision'].')\">'.format(__('rev. {0}'), $revision['revision']).'</a>';

            if (isset($revision['ru_id'])) {
                $ru_link = UserLink(getDataPrefix($revision, 'ru_'));
                $revdetail = ' '.format(__('by {0} on {1}'), $ru_link, formatdate($revision['revdate']));
            } else
                $revdetail = '';
            $reply .= $revdetail;
            $reply .= '<br />';
        }

        $hideTricks = ' <a href=\"javascript:void(0)\" onclick=\"showRevision('.$id.','.$post['currentrevision'].'); hideTricks('.$id.')\">'.__('Back').'</a>';
        $reply .= $hideTricks;
        trigger_error($reply);
        break;

	case 'sr':  //-------------------------------------------------------------------------------------------------------------------------------------Show Revision

        global $loguser, $blocklayouts;
        $rPost = Query('
			SELECT
				p.*,
				pt.text, pt.revision, pt.user AS revuser, pt.date AS revdate,
				u.(_userfields), u.(rankset,title,picture,posts,postheader,signature,signsep,lastposttime,lastactivity,regdate,globalblock),
				ru.(_userfields),
				du.(_userfields),
				t.forum fid
			FROM
				{posts} p
				LEFT JOIN {posts_text} pt ON pt.pid = p.id AND pt.revision = {1}
				LEFT JOIN {threads} t ON t.id=p.thread
				LEFT JOIN {users} u ON u.id = p.user
				LEFT JOIN {users} ru ON ru.id=pt.user
				LEFT JOIN {users} du ON du.id=p.deletedby
			WHERE p.id={0} AND t.forum IN ({2c})', $id, (int)$_GET['rev'], ForumsWithPermission('forum.viewforum'));

        if(NumRows($rPost))
            $post = Fetch($rPost);
        else
            trigger_error(format(__('Unknown post ID #{0} or revision missing.'), $id));

        if (!HasPermission('mod.editposts', $post['fid']))
            trigger_error('No.');

        $poster = getDataPrefix($post, 'u_');

        LoadBlockLayouts();
        $pltype = Settings::get('postLayoutType');
        $isBlocked = $poster['globalblock'] || $loguser['blocklayouts'] || isset($blocklayouts[$poster['id']]);

        $post['haslayout'] = false;
        $post['fulllayout'] = false;

        if(!isset($isBlocked)) {
            $poster['postheader'] = $pltype ? trim($poster['postheader']) : '';
            $poster['signature'] = trim($poster['signature']);

            $post['haslayout'] = $poster['postheader']?1:0;
            $post['fulllayout'] = $poster['fulllayout'] && $post['haslayout'] && ($pltype==2);

            if (!$post['haslayout'] && $poster['signature'])
                $poster['signature'] = '<div class="signature">'.$poster['signature'].'</div>';
        } else {
            $poster['postheader'] = '';
            $poster['signature'] = '';
        }

        trigger_error(makePostText($post, $poster));
        break;

	case 'em':  	//------------------------------------------------------------------------------------------------------------------------------------Email
        $privacy = HasPermission('admin.editusers') ? '' : ' and showemail=1';
        $blah = FetchResult("select email from {users} where id={0}{$privacy}", $id);
        trigger_error(htmlspecialchars($blah));
        break;

	case 'vc': //------------------------------------------------------------------------------------------------------------------------------------------View Counter
        $blah = FetchResult('select views from {misc}');
        trigger_error(number_format($blah));
        break;

	case 'no': //------------------------------------------------------------------------------------------------------------------------------------------ notification list
        $notif = getNotifications();
        trigger_error(json_encode($notif));
        break;
}

trigger_error(__('Unknown action.'));
?>
