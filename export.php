<?php

$DEBUG = 0;

if(PHP_SAPI != 'cli') die('ERROR: You must run this script under shell.');

chdir(dirname(__FILE__));

declare(ticks = 1);
require 'vendor/autoload.php';

use \Symfony\Component\Yaml\Yaml;
use \Symfony\Component\Yaml\Dumper;

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

if(!file_exists($postsPath)){ mkdir($postsPath); }

#$postsPath .= '/backup_'.date('Y-m-d_H-i-s');
#if(!file_exists($postsPath)){ mkdir($postsPath); }

$dumper = new Dumper();

$client = new Tumblr\API\Client($paramters['tumblr']['consumer_key'], $paramters['tumblr']['consumer_secret'], $paramters['tumblr']['token'], $paramters['tumblr']['token_secret']);

foreach($client->getUserInfo()->user->blogs as $blog){
	$blogName = $blog->name;
	print "blog: ".$blogName."\n";
	
	$blogDirPath = $postsPath.'/'.$blogName;
	$blogPostsDirPath = $blogDirPath.'/posts';
	if(!file_exists($blogDirPath)){ mkdir($blogDirPath); }
	if(!file_exists($blogPostsDirPath)){ mkdir($blogPostsDirPath); }
	
	$blogInfo = $client->getBlogInfo($blogName);
	
	file_put_contents($blogDirPath.'/info.yml', $dumper->dump((array)$blogInfo->blog));
	
	for($postN = 0; $postN <= $blogInfo->blog->posts; $postN += 20){
		print "\t posts: ".$postN." to ".($postN + 20)."\n";
		
		foreach($client->getBlogPosts($blogName, array('limit' => 20, 'offset' => $postN))->posts as $post){
			print "\t\t post: ".$post->id.", ".$post->type." \n";
			
			$postDate = new DateTime($post->date);
			
			file_put_contents($blogPostsDirPath.'/post_'.$post->id.'_'.$postDate->format('Y-m-d_H-i-s').'_'.$post->type.'.yml', $dumper->dump((array)$post));
			
			#break;
			if($exit) break;
		}
		
		#break;
		if($exit) break;
	}
	
	#break;
}



print "\n";
