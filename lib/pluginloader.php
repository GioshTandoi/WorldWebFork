<?php
if (!defined('BLARG')) die();

//Plugin loader -- By Nikolaj
global $pluginbuckets, $plugins, $plugin;

if(isset($plugin))
    $oldplugin = $plugin;

if(!isset($self))
	$self = NULL;
$oldself = $self;


if(isset($bucket)){
    if (isset($pluginbuckets[$bucket])) {
        foreach ($pluginbuckets[$bucket] as $plugin) {
            if (isset($plugins[$plugin])) {
                $self = $plugins[$plugin];
                include(__DIR__.'/../plugins/'.$self['dir'].'/'.$bucket.'.php');
                unset($self);
            }
        }
    }
}


$self = $oldself;
$plugin = $oldplugin;