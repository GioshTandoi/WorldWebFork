<?php
if (!defined('BLARG')) trigger_error();

// notification formatting callbacks for each notification type
$NotifFormat = [
	'pm' => 'FormatNotif_PM',
	'profilecomment' => 'FormatNotif_ProfileComment',
];

// plugins should use an init hook to extend $NotifFormat

/*risoluzione 9337:  in FormatNotif_PM--> $loguserid=0 con controllo if (isset($loguserid))
Inoltre $args è stata eliminata dai parametri perchè non utilizzata
From Giosh96  */
function FormatNotif_PM($id, $loguserid=0) {

	$staffpm = '';
	if (HasPermission('admin.viewstaffpms')) $staffpm = ' OR p.userto=-1';

if (isset($loguserid)){
    $pm = Fetch(Query("	SELECT 
							p.id,
							pt.title pmtitle, 
							u.(_userfields) 
						FROM 
							{pmsgs} p 
							LEFT JOIN {pmsgs_text} pt ON pt.pid=p.id 
							LEFT JOIN {users} u ON u.id=p.userfrom 
						WHERE 
							p.id={0} AND (p.userto={1}{$staffpm})",
        $id, $loguserid));
    $userdata = getDataPrefix($pm, 'u_');

    return __('New private message from ').UserLink($userdata)."\n".
        actionLinkTag(htmlspecialchars($pm['pmtitle']), 'showprivate', $pm['id']);
}else
    return false;

}

/*risoluzione 9337:  in FormatNotif_ProfileComment--> $loguserid=0, $loguser=[], con controllo if(isset($loguserid) && isset($loguser))
Inoltre $id, $args sono state eliminate dai parametri perchè non utilizzate
From Giosh96  */

function FormatNotif_ProfileComment($loguserid=0, $loguser=[]) {
	if(isset($loguserid) && isset($loguser))
	    return __('New comments in ').actionLinkTag(__('your profile'),
              'profile', $loguserid, '', $loguser['name']);
}


function notifsort($a, $b) {
	if ($a['date'] == $b['date']) return 0;
	return ($a['date'] > $b['date']) ? -1 : 1;
}

/*risoluzione 9337:  in GetNotifications--> $loguserid=0, $NotifFormat=[]
con controllo (!isset($loguserid) || !isset($NotifFormat))
From Giosh96  */

function GetNotifications($loguserid=0, $NotifFormat=[]) {

    $notifs = [];

	if (!isset($loguserid) || !isset($NotifFormat))
	    return $notifs;
	else{
        // TODO do it better!
        $staffnotif = '';
        if (HasPermission('admin.viewstaffpms')) $staffnotif = ' OR user=-1';

        $ndata = Query("SELECT type,id,date,args FROM {notifications} WHERE user={0}{$staffnotif} ORDER BY date DESC", $loguserid);
        while ($n = Fetch($ndata)) {
            $ncb = $NotifFormat[$n['type']];
            if (function_exists($ncb))
                $ndesc = $ncb($n['id'], $n['args']?unserialize($n['args']):null);
            else
                $ndesc = htmlspecialchars($n['type'].':'.$n['id']);

            $ts = '<span class="nobr">'; $te = '</span>';
            $stringConcat=$ndesc;
            if(strpos($ndesc,"\n")!==FALSE)
                $stringConcat=str_replace("\n", $te.'<br/>'.$ts, $ndesc);
            $ndesc = $ts.$stringConcat.$te;

            $notifs[] = [
                'date' => $n['date'],
                'formattedDate' => relativedate($n['date']),
                'text' => $ndesc
            ];
        }

        return $notifs;
    }


}

// type: notification type (pm, profilecomment, etc)
// id: identifier for this notification, should be unique (for example, for PMs the PM ID is used as an ID)
// args: notification-specific args, used by the format callbacks
function SendNotification($type, $id, $user, $args=null) {
	$argstr = $args ? serialize($args) : '';
	$now = time();

	Query('
		INSERT INTO {notifications} (type,id,user,date,args) VALUES ({0},{1},{2},{3},{4})
		ON DUPLICATE KEY UPDATE date={3}, args={4}',
		$type, $id, $user, $now, $argstr);

	//$bucket = 'sendNotification';
	include(__DIR__.'/pluginloader.php');
}

function DismissNotification($type, $id, $user) {
	Query('DELETE FROM {notifications} WHERE type={0} AND id={1} AND user={2}', $type, $id, $user);

	//$bucket = 'dismissNotification';
	include(__DIR__.'/pluginloader.php');
}

