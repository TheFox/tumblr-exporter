<?php

$DEBUG = 0;

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

chdir(dirname(__FILE__));

declare(ticks = 1);
require 'vendor/autoload.php';

use \Symfony\Component\Yaml\Yaml;

$exit = 0;
if(function_exists('pcntl_signal')){
	function signalHandler($signo){ global $exit; $exit++; if($exit >= 2) exit(); }
	pcntl_signal(SIGTERM, 'signalHandler');
	pcntl_signal(SIGINT, 'signalHandler');
}

$paramtersFilePath = 'parameters.yml';
if(!file_exists($paramtersFilePath)){
	die('ERROR: File "'.$paramtersFilePath.'" not found.'."\n");
}

$paramters = Yaml::parse($paramtersFilePath);

if(
	!isset($paramters)
	|| !isset($paramters['directory'])
	|| !isset($paramters['tumblr'])
	|| !isset($paramters['tumblr']['consumer_key'])
	|| !isset($paramters['tumblr']['consumer_secret'])
	|| !isset($paramters['tumblr']['token'])
	|| !isset($paramters['tumblr']['token_secret'])
){
	print "ERROR: parameters invalid.\n";
	var_export($paramters); print "\n";
	exit(1);
}

#var_export($paramters);exit();

$postsPath = $paramters['directory'];
#$postsPath = str_replace('~', getenv('HOME'), $postsPath);
#$postsPath = realpath($postsPath);

#var_export($postsPath);exit();

if(!file_exists($postsPath)){
	mkdir($postsPath);
}


$client = new Tumblr\API\Client($paramters['tumblr']['consumer_key'], $paramters['tumblr']['consumer_secret'], $paramters['tumblr']['token'], $paramters['tumblr']['token_secret']);

foreach($client->getUserInfo()->user->blogs as $blog){
	$blogName = $blog->name;
	print "blog: ".$blogName."\n";
	$blogDirPath = $postsPath.'/'.$blogName;
	if(!file_exists($blogDirPath)){
		mkdir($blogDirPath);
		mkdir($blogDirPath.'/posts');
	}
	
	var_export($client->getBlogInfo($blogName));
	
	break;
}



print "\n";
