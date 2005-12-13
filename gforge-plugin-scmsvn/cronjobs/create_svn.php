#! /usr/bin/php4 -f
<?php
/**
 * create_docman.php 
 *
 * Francisco Gimeno <kikov@fco-gimeno.com>
 *
 * @version   $Id
 */

require ('squal_pre.php');
require_once('common/include/cron_utils.php');

//	/path/to/svn/bin/
$svn_path='/usr/bin/';

//	Owner of files - apache
$file_owner=$sys_apache_user.':'.$sys_apache_group;

//	Where is the SVN repository?
$svn=$svndir_prefix;

//	Whether to separate directories by first letter like /m/mygroup /a/apple
$first_letter = false;

// Whether to have all projects in a single repository
$one_repository = false;

//if everything is in one repository, we need a working checkout to use
$repos_co = '/var/svn-co';

//type of repository, whether filepassthru or bdb
//$repos_type = ' --fs-type fsfs ';
$repos_type = '';


/*
	This script create the gforge dav/svn/docman repositories
*/



$err .= "Creating Groups at ". $svn."\n";

if (empty($sys_apache_user) || empty($sys_apache_group)) {
	$err .=  "Error! sys_apache_user Is Not Set Or sys_apache_group Is Not Set!";
	echo $err;
	cron_entry(21,$err);
	exit;
}

if (empty($svn) || util_is_root_dir($svn)) {
	$err .=  "Error! svndir_prefix Is Not Set Or Points To The Root Directory!";
	echo $err;
	cron_entry(21,$err);
	exit;
}

$res = db_query("SELECT is_public,enable_anonscm,unix_group_name,groups.group_id 
	FROM groups, plugins, group_plugin 
	WHERE groups.status != 'P' 
	AND groups.group_id=group_plugin.group_id
	AND group_plugin.plugin_id=plugins.plugin_id
	AND plugins.plugin_name='scmsvn'");

if (!$res) {
	$err .=  "Error! Database Query Failed: ".db_error();
	echo $err;
	cron_entry(21,$err);
	exit;
}

//
//	If using a single large repository, create the checkout if necessary
//


if ($one_repository && !is_dir($repos_co)) {
	$err .= "Error! Checkout Repository Does Not Exist!";
	echo $err;
	cron_entry(21,$err);
	exit;
} elseif (!is_dir($svn)) {
	passthru ("mkdir $svn");
}

while ( $row =& db_fetch_array($res) ) {	
	if ($one_repository) {
		if ($first_letter) {
			//
			//	Create the repository
			//
			passthru ("[ ! -d $repos_co/".$row["unix_group_name"][0]."/ ] && mkdir -p $repos_co/".$row["unix_group_name"][0]."/ && $svn_path/svn add $repos_co/".$row["unix_group_name"][0]."/");
			passthru ("[ ! -d $repos_co/".$row["unix_group_name"][0]."/".$row["unix_group_name"]."/ ] && mkdir -p $repos_co/".$row["unix_group_name"][0]."/".$row["unix_group_name"]."/ && $svn_path/svn add $repos_co/".$row["unix_group_name"][0]."/".$row["unix_group_name"]."/");
		} else {
			passthru ("[ ! -d $repos_co/".$row["unix_group_name"]." ] && mkdir -p $repos_co/".$row["unix_group_name"]."/ && $svn_path/svn add $repos_co/".$row["unix_group_name"]);
		}
		$cmd = 'chown -R '.$file_owner.' '.$repos_co;
		passthru ($cmd);
	} else {
		$project = &group_get_object($row["group_id"]); // get the group object for the current group
		if ( (!$project) || (!is_object($project))  )  {
			echo "Error Getting Group." . " Id : " . $row["group_id"] . " , Name : " . $row["unix_group_name"];
			break; // continue to the next project
		}		
		if ($first_letter) {
			//
			//	Create the repository
			//
			passthru ("[ ! -d $svn/".$row["unix_group_name"][0]."/".$row["unix_group_name"]." ] && mkdir -p $svn/".$row["unix_group_name"][0]."/ && $svn_path/svnadmin create $repos_type $svn/".$row["unix_group_name"][0]."/".$row["unix_group_name"]);
 			//svn_hooks("$svn/".$row["unix_group_name"][0]."/".$row["unix_group_name"]);
 			if ($project->usesPlugin('svncommitemail')) {
 				addSvnMail("$svn/".$row["unix_group_name"][0]."/".$row["unix_group_name"]."/hooks/post-commit");
 			}
 			if ($project->usesPlugin('svntracker')) {
 				addSvnTracker();
 			}
		} else {
			passthru ("[ ! -d $svn/".$row["unix_group_name"]." ] &&  $svn_path/svnadmin create $repos_type $svn/".$row["unix_group_name"]);
			if ($project->usesPlugin('svncommitemail')) {
				addSvnMail("$svn/".$row["unix_group_name"]."/hooks/post-commit");
			}
			if ($project->usesPlugin('svntracker')) {
				addSvnTracker();
			}
		}	
		$cmd = 'chown -R '.$file_owner.' '.$svn;
		passthru ($cmd);
	}
}

function addSvnTracker() {
	global $svn,$row;
	
	$LineFound = FALSE;
	$FIn  = @fopen($svn."/".$row["unix_group_name"]."/hooks/post-commit","r");	
	if ($FIn) {
		while (!feof($FIn))  {
			$Line = fgets ($FIn);
			if(!preg_match("/^#/", $Line) &&
				preg_match("/svntracker/",$Line)) {
				$LineFound = TRUE;
			}
		}
		fclose($FIn);
		if($LineFound==FALSE) {
			echo $row["unix_group_name"].": post-commit modified\n";
			addSvnTrackerToFile($svn."/".$row["unix_group_name"]."/hooks/post-commit");
		}
	} else {
		//create the file
		echo $row["unix_group_name"].": post-commit modified and created\n";
		addSvnTrackerToFile($svn."/".$row["unix_group_name"]."/hooks/post-commit");
	}
}

function addSvnTrackerToFile($path) {
	global $sys_plugins_path;
	
	$FOut = fopen($path, "a+");
	if($FOut) {
		$Line = 'REPOS="$1"'  . "\n";
		fwrite($FOut,$Line);
		$Line = 'REV="$2"' . "\n";
		fwrite($FOut,$Line);
		$Line = "/usr/bin/php -d include_path=".ini_get('include_path').
				" ".$sys_plugins_path. "/svntracker/bin/post.php".  ' "$REPOS" "$REV"' . "\n";
		fwrite($FOut,$Line);
		`chmod +x $path `;
		fclose($FOut);
	}
}

function addSvnMail($filepath) {
	global $svn,$row,$sys_lists_host;

	$LineFound = FALSE;
	$FIn  = fopen($filepath,"r");	
	if ($FIn) {
		while (!feof($FIn))  {
			$Line = fgets ($FIn);
			
			if((!preg_match("/^#/", $Line)) &&
				(preg_match("/commit-email.pl/",$Line)) && (preg_match("/".$sys_lists_host."/",$Line))) {
				$LineFound = TRUE;
			}
		}
		fclose($FIn);
		if($LineFound==FALSE) {
			echo $row["unix_group_name"].": post-commit modified\n";
			addSvnMailToFile($filepath,$row["unix_group_name"]);
		}
	} else {
		//create the file
		echo $row["unix_group_name"].": post-commit modified and created\n";
		addSvnMailToFile($filepath,$row["unix_group_name"]);
	}
	
}

function addSvnMailToFile($filePath,$unix_group_name) {
	global $sys_lists_host;
	
	$FOut = fopen($filePath, "a+");
	if($FOut) {
		$pathsvnmail = dirname($_SERVER['_']).'/commit-email.pl '.' "$REPOS" '.' "$REV" '.$unix_group_name.'-commits@'.$sys_lists_host . "\n";
		$Line = 'REPOS="$1"'  . "\n";
		fwrite($FOut,$Line);
		$Line = 'REV="$2"' . "\n";
		fwrite($FOut,$Line);
		fwrite($FOut, $pathsvnmail);
		`chmod +x $filePath `;
		fclose($FOut);
	}
}

/**
* svn_hooks($filePath)
* This function create the post-commit file in svn hooks
* Copyright 2004 (c) GForge
* @autor Luis Alberto Hurtado Alvarado <luis@gforgegroup.com>
* @param $filePath The path to svn repository
*/
function svn_hooks($filePath) {
	system ("cp $filePath/hooks/post-commit.tmpl $filePath/hooks/post-commit");
	system("chmod +x ".$filePath."/hooks/post-commit");
}

if ($one_repository) {
	passthru ("cd $repos_co && $svn_path/svn commit -m\"\"");
}
system("chown $file_owner -R $svn");
system("cd $svn/ && find -type d -exec chmod 700 {} \;");
system("cd $svn/ && find -type f -exec chmod 600 {} \;");

cron_entry(21,$err);
?>
