#! /usr/bin/php5 -d include_path=/etc/gforge:/usr/local/gforge:/usr/local/gforge/www/include 
<?php
/**
 * create_git.php 
 *
 * Author: Ferenc Székely <ferenc@maemo.org>
 *
 * Based on create_svn.php written by
 * Francisco Gimeno <kikov@fco-gimeno.com>
 *
 * This php script assumes that the Git server is available
 * from the GForge server thru ssh. naturally these two can be
 * located on the same physicial or virtual server. Never the less
 * this script will always use ssh to connect.
 * Establishing an environment for automated ssh authentication
 * requires care and precaution. 
 *
 * The script should be run as root or as ssh_user (see below) on 
 * the GForge server!
 *
 */
require ('squal_pre.php');
require_once('common/include/cron_utils.php');

// user that connects to Git server
// assumed that ssh key exchange is done prior
$ssh_user = 'www-data';
$ssh_key = '/home/www-data/.ssh/id_rsa';
$ssh_timeout = '-o ConnectTimeout=1';
$create_repo_script = '/usr/local/bin/create_git_repo.sh';

//$err = "Git server: " . $sys_default_git_server . "\n";
//$err .= "SSH user  : " . $ssh_user . "\n";

// check if the Git server is available and ssh_user can connect to it
system("ssh $ssh_timeout -i $ssh_key $ssh_user@$sys_default_git_server ls $create_repo_script > /dev/null 2>&1", $failure);

if ($failure) {
  //non zero $failure means connection problem
  die ("The ssh connection to $sys_default_git_server failed. Check the ssh authentication setup.\n");
}

// get all projects that need a git repo
$res = db_query("SELECT is_public, enable_anonscm, unix_group_name, groups.group_id 
	FROM groups, plugins, group_plugin 
	WHERE groups.status != 'P' 
	AND groups.group_id=group_plugin.group_id
	AND group_plugin.plugin_id=plugins.plugin_id
	AND plugins.plugin_name='ggit'");

if (!$res) {
	$err .=  "Error! Database Query Failed: ".db_error();
	echo $err;
	cron_entry(21,$err);
	exit;
}

$ssl_cert_warning = "If you experience problems with 'git push' please set the GIT_SSL_NO_VERIFY environment variable to 1\n";

// the loop where we go thru all projects that require git repo
// and create the repo unless it has been created earlier
while ( $row =& db_fetch_array($res) ) {	
  $type = "public";
  $create_cmd = "ssh $ssh_timeout -i $ssh_key $ssh_user@$sys_default_git_server ";
  $create_cmd .= $create_repo_script . " " . $row['unix_group_name'];

  $git_clone_cmd = "git clone ";
  $git_clone_cmd .= (($sys_default_git_use_dav == 'true') ? 'http' : 'git+ssh');
  $git_clone_cmd .= (($sys_default_git_use_ssl == 'true') ? 's' : '') . "://";
  $git_clone_cmd .= $sys_default_git_server . "/";
  $git_clone_cmd .= (($sys_default_git_root) ? $sys_default_git_root ."/" : '');
  $git_clone_cmd .= $row['unix_group_name'];
  
  if ( ! $row['is_public'] || ! $row['enable_anonscm'] ) {
    $type = "private";
    $create_cmd .= " private";
  }

  $create_cmd .= " >/dev/null 2>&1";
  $err .= "Creating " . $type . " repository for " . $row['unix_group_name'] . " project";
  $err .= " at " .  $sys_default_git_server . ".\n"; 

  passthru($create_cmd, $ret);

  switch ($ret) {
    case 0: 
      $err .= "Repository successfuly created.\n";    
      break;
    case 1:
      $err .= "Repository creator script did not get proper arguments\n";
      break;
    case 2:
      $err .= "Repository for " . $row['unix_group_name'] . " already exists.\n";
      break;
    case 3:
      $err .= "Repository could not be created for " . $row['unix_group_name'] . "\n";
      break;
    default:
      break;
  }
  $err .= "You may try to access it with the following commmand: \n";
  $err .= $git_clone_cmd . "\n";
  $err .= (($sys_default_git_use_ssl == 'true') ? $ssl_cert_warning : '');
}

echo $err;

//cron_entry(21, $err);

exit;

?>


/**

function check_svn_mail($project, $repos) {
	$contents = @file_get_contents($repos."/hooks/post-commit");
	if ( strstr($contents, "svncommitemail") == FALSE ) {
		add_svn_mail_to_repository($project,$repos);
	}
}

function add_svn_mail_to_repository($unix_group_name,$repos) {
	global $sys_lists_host,$file_owner,$sys_plugins_path;
	
	if (file_exists($repos.'/hooks/post-commit')) {
		$FOut = fopen($repos.'/hooks/post-commit', "a+");
	} else {
		$FOut = fopen($repos.'/hooks/post-commit', "w");
		$Line = '#!/bin/sh'."\n"; // add this line to first line or else the script fails
	}
	
	if($FOut) {
		$Line .= '
#begin added by svncommitemail
'.$sys_plugins_path.'/svncommitemail/bin/commit-email.pl '.$repos.' "$2" '.$unix_group_name.'-commits@'.$sys_lists_host.'
#end added by svncommitemail';
		fwrite($FOut,$Line);
		`chmod +x $repos'/hooks/post-commit'`;
		`chmod 700 $repos'/hooks/post-commit'`;
		`chown $file_owner $repos'/hooks/post-commit'`;
		fclose($FOut);
	}
}

function check_svn_www($project, $repos) {
	$contents = @file_get_contents($repos."/hooks/post-commit");
        if ( strstr($contents, "svnwww") == FALSE ) {
        	add_svn_www_to_repository($project,$repos);
	}                                        
}

function add_svn_www_to_repository($unix_group_name,$repos) {
	global $sys_lists_host,$file_owner,$sys_plugins_path;
	
	if (file_exists($repos.'/hooks/post-commit')) {
		$FOut = fopen($repos.'/hooks/post-commit', "a+");
	} else {
		$FOut = fopen($repos.'/hooks/post-commit', "w");
		$Line = '#!/bin/sh'."\n"; // add this line to first line or else the script fails
	}
	
	if($FOut) {
		$Line .= "\n\n".'#svnwww'."\n";
                $Line .= 'svn ls file://'.$repos.'/www > /dev/null 2>&1'."\n";
                $Line .= "if [ \$? -eq \"0\" ]; then\n";
		$Line .= '    rm -rf /var/www/homedirs/groups/'.$unix_group_name.'/.previous'."\n";
		$Line .= '    mv /var/www/homedirs/groups/'.$unix_group_name.' /var/www/homedirs/groups/'.$unix_group_name.'.previous'."\n";
		$Line .= '    svn export --force file://'.$repos.'/www /var/www/homedirs/groups/'.$unix_group_name."\n";
		$Line .= '    chown -R www-data:www-data /var/www/homedirs/groups/'.$unix_group_name."\n";
		$Line .= '    mv /var/www/homedirs/groups/'.$unix_group_name.'.previous /var/www/homedirs/groups/'.$unix_group_name.'/.previous'."\n";
                $Line .= 'fi'."\n";
		$Line .= '#end_of_svnwww'."\n";
		fwrite($FOut,$Line);
		`chmod +x $repos'/hooks/post-commit'`;
		`chmod 700 $repos'/hooks/post-commit'`;
		`chown $file_owner $repos'/hooks/post-commit'`;
		fclose($FOut);
	}
}

*/