<?php
if (!defined('BLARG')) trigger_error();

$userMenu = [];

if(isset($loguserid) && $loguserid == true) {
	if (HasPermission('user.editprofile')) {
		$userMenu[actionLink('editprofile')] = __('Edit profile');
		if (HasPermission('user.editavatars'))
			$userMenu[actionLink('editavatars')] = __('Mood avatars');
	}
	
	$userMenu[actionLink('private')] = __('Private messages');
	$userMenu[actionLink('favorites')] = __('Favorites');

	if (HasPermission('admin.viewadminpanel'))
		$userMenu[pageLink('admin')] = __('Dashboard');
}

$bucket = 'userMenu'; include(__DIR__.'/../lib/pluginloader.php');

$layout_userpanel = $userMenu;
?>
