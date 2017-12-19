<?php
if (!defined('BLARG')) trigger_error();

define('POST_ATTACHMENT_CAP', 10*1024*1024);
define('TIME_COSTANT', 604800 );
function UploadFile($file, $parenttype, $parentid, $cap, $description='', $temporary=false,$loguser=null, $loguserid=null) {
	$targetdir = DATA_DIR.'uploads';

	$filedata = $_FILES[$file];
	$filename = $filedata['name'];

	if($filedata['size'] == 0)
		return true;
	else if($filedata['size'] > $cap)
		return false;
	else {

	    if(!isset($loguserid) || !isset($loguser))
	        return false;

		CleanupUploads();

		$randomid = Shake();
		$pname = $randomid.'_'.Shake();

		$temp = $filedata['tmp_name'];

		Query('
			INSERT INTO {uploadedfiles} (id, physicalname, filename, description, user, date, parenttype, parentid, downloads, deldate) 
			VALUES ({0}, {1}, {2}, {3}, {4}, {5}, {6}, {7}, 0, {8})',
			$randomid, $pname, $filename, $description, $loguserid, time(), $parenttype, $parentid, $temporary?time():0);

		$fullpath = $targetdir.'/'.$pname;
		copy($temp, $fullpath);
		Report('[b]'.$loguser['name'].'[/] uploaded file \"[b]'.$filename.'[/]\"', false);

		return $randomid;
	}
}

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

function DeleteUpload($userid, $loguser, $filename) {

    $whitelist = [
        '/',
        '/Upload'
    ];
    define('UPLOADPATH', '/Upload/');

    $realBase = realpath(UPLOADPATH);

    $userpath = UPLOADPATH . $_GET['path'];
    $path = basename(realpath($userpath));
    $hashedpath = basename(realpath($userpath.'hash'));

    $host1 = parse_url(userpath, PHP_URL_HOST);
    $host2 = parse_url(hashedpath, PHP_URL_HOST);

    if (!empty($host1) && in_array($host1, $whitelist) &&  !empty($host2) && in_array($host2, $whitelist)) {

    if ($path === false || strpos($path, $realBase) !== 0) {

    } else {
        if (!file_exists($path . 'hash')) return;
        $hash = get_data();
        $hashedfile = hash_hmac_file('sha256', basename(realpath($path)), $userid . SALT);

            if ($hashedfile !== false && $hash !== false && $hash === $hashedfile) {
                error_reporting(0);
                unlink(basename(realpath($path)));
                error_reporting(0);
                unlink(basename(realpath($hashedpath)));
            }
        }
    }

    Report('[b]'.$loguser['name'].'[/] deleted file \"[b]'.$filename.'[/]\"', false);
}

function CleanupUploads() {
	$targetdir = DATA_DIR.'uploads';

	$timebeforedel = time()-TIME_COSTANT; // one week
	$nrighe = NumRows(Query('SELECT physicalname, user, filename FROM {uploadedfiles} WHERE deldate!=0 AND deldate<{0}', $timebeforedel));
	if ($nrighe) {
		while ($entry = Fetch(Query('SELECT physicalname, user, filename FROM {uploadedfiles} WHERE deldate!=0 AND deldate<{0}', $timebeforedel))) {
			Report("[b]{$entry['filename']}[/] deleted by auto-cleanup", false);
			DeleteUpload($targetdir.'/'.$entry['physicalname'], $entry['user']);
		}

		Query('DELETE FROM {uploadedfiles} WHERE deldate!=0 AND deldate<{0}', $timebeforedel);
	}
}


function HandlePostAttachments($postid, $final, $entry, $http=null) {
	$targetdir = DATA_DIR.'uploads';

	if (!Settings::get('postAttach')) return [];

	$attachs = [];

	if ($http&& !empty($http->post('files'))) {
		foreach ($http->post('files') as $fileid=>$blarg) {
			if ($http->post('deletefile') && $http->post('deletefile')[$fileid]) {
				Query('SELECT physicalname, user FROM {uploadedfiles} WHERE id={0}', $fileid);
				DeleteUpload($targetdir.'/'.$entry['physicalname'], $entry['user']);
				Query('DELETE FROM {uploadedfiles} WHERE id={0}' , $fileid);
			} else {
				if (isset($final)) Query('UPDATE {uploadedfiles} SET parentid={0}, deldate=0 WHERE id={1}', $postid, $fileid);
				$attachs[$fileid] = FetchResult('SELECT filename FROM {uploadedfiles} WHERE id={0}', $fileid);
			}
		}
	}

	foreach ($_FILES as $file=>$data) {
		if (!in_array($data['name'], $attachs)) {
            $res = UploadFile($file, 'post_attachment', $postid, POST_ATTACHMENT_CAP, '', !$final);
            if ($res === false) return $res;
            if ($res === true) continue;
            $attachs[$res] = $data['name'];
        }
	}

	return $attachs;
}

function PostAttachForm($files) {
	if (!Settings::get('postAttach')) return;

	$fdata = [];
	asort($files);
	foreach ($files as $_fileid=>$filename) {
		$fileid = htmlspecialchars($_fileid);
		$fdata[] =
			htmlspecialchars($filename).' 
			<label><input type="checkbox" name="deletefile['.$fileid.']" value="1"> Delete</label>
			<input type="hidden" name="files['.$fileid.']" value="blarg">';
	}

	$fields = [
		'newFile' => '<input type="file" name="newfile">',

		'btnSave' => '<input type="submit" name="saveuploads" value="'.__('Save').'">',
	];

	RenderTemplate('form_attachfiles', ['files' => $fdata, 'fields' => $fields, 'fileCap' => BytesToSize(POST_ATTACHMENT_CAP)]);
}
