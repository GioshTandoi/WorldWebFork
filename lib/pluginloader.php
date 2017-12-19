<?php
if (!defined('BLARG')) trigger_error();

//Plugin loader -- By Nikolaj


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