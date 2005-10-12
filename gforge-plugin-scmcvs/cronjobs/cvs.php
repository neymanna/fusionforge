#! /usr/bin/php4 -f
<?php
/**
 * GForge Cron Job
 *
 * The rest Copyright 2002-2005 (c) GForge Team
 * http://gforge.org/
 *
 * @version   $Id$
 *
 * This file is part of GForge.
 *
 * GForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require ('squal_pre.php');
require ('common/include/cron_utils.php');

require_once('common/include/SCM.class') ;

setup_plugin_manager () ;

$use_cvs_acl = false;
$maincvsroot = "/cvsroot";

/**
* Retrieve a file into a temporary directory from a CVS server
*
* @param String $repos Repository Name
* @param String $file File Name
*
* return String the FileName in the working repository
*/
function getCvsFile($repos,$file) {
        $actual_dir = getcwd();
        $tempdirname = tempnam("/tmp","cvstracker");
        if (!$tempdirname) 
                return false;
        if (!unlink($tempdirname))
                return false;

        // Create the temporary directory and returns its name.
        if (!mkdir($tempdirname))
                return false;

        if (!chdir($tempdirname))
                return false;
        system("cvs -d ".$repos." co ".$file);

        chdir($actual_dir);
        return $tempdirname."/".$file;
}

/**
* putCvsFile commit a file to the repository
*
* @param String $repos Repository
* @param String $file to commit
* @param String $message to commit
*/
function putCvsFile($repos,$file,$message="Automatic updated by cvstracker") {
	$actual_dir = getcwd();
	chdir(dirname($file));	
	system("cvs -d ".$repos." ci -m \"".$message."\" ".basename($file));
	// unlink (basename($file));
	chdir($actual_dir);
}

//the directory exists
if(is_dir($maincvsroot)) {
	addProjectRepositories();
} else {
	if(is_file($maincvsroot)) {
		$err .= "$maincvsroot exists but is a file\n";
		exit;
	} else {
		if (mkdir($maincvsroot)) {
			//need to update group permissions using chmod
			addProjectRepositories();
		} else {
			$err .= "unable to make $maincvsroot directory\n";
			exit;
		}	
	}
}

function writeFile($filePath, $content) {
	$file = fopen($filePath, 'a');
	flock($file, LOCK_EX);
	ftruncate($file, 0);
	rewind($file);
	if(!empty($content)) {
		fwrite($file, $content);
	}
	flock($file, LOCK_UN);
	fclose($file);
}

/**
*addsyncmail
*Copyright GForge 2004
*addsyncmail write to /CVSROOT/loginfo unix_name-commits@lists.gforge.company.com
*
*@autor Luis A. Hurtado A. luis@gforgegroup.com
*@param $unix_group_name Name Group
*@return void
*@date 2004-10-25
*/
function addsyncmail($unix_group_name) {

	global $sys_lists_host;
	global $maincvsroot;
	$loginfo_file=getCvsFile($maincvsroot."/".$unix_group_name,'CVSROOT/loginfo');
	$pathsyncmail = "ALL ".
		dirname(__FILE__)."/syncmail -u %p %{sVv} ".
		$unix_group_name."-commits@".$sys_lists_host."\n";
	$content = file_get_contents ($loginfo_file);
	if ( strstr($content, "syncmail") == FALSE) {
		echo $unix_group_name.":Syncmail not found in loginfo.Adding\n";
		$content .= "\n#BEGIN Added by cvs.php script\n".
			$pathsyncmail. "\n#END Added by cvs.php script\n";
		if(is_file($loginfo_file)){
			echo $unix_group_name.":About to write the lines\n";
			writeFile($loginfo_file, $content);
		}
		putCvsFile($maincvsroot."/".$unix_group_name,$loginfo_file);
	} else {
		echo "Syncmail Found!\n";
	}

}

function addProjectRepositories() {
	global $maincvsroot;
	global $use_cvs_acl;

	$res = db_query("select groups.group_id,groups.unix_group_name,groups.enable_anonscm,groups.enable_pserver".
		" FROM groups, plugins, group_plugin".
		" WHERE groups.status != 'P' ".
		" AND groups.group_id=group_plugin.group_id ".
		" AND group_plugin.plugin_id=plugins.plugin_id ".
		" AND plugins.plugin_name='scmcvs'");
	
	for($i = 0; $i < db_numrows($res); $i++) {
		/*
			Simply call cvscreate.sh
		*/
		
		$project = &group_get_object(db_result($res,$i,'group_id')); // get the group object for the current group
		
		if ( (!$project) || (!is_object($project))  )  {
			echo "Error Getting Group." . " Id : " . db_result($res,$i,'group_id') . " , Name : " . db_result($res,$i,'unix_group_name');
			break; // continue to the next project
		}
		
		$repositoryPath = $maincvsroot."/".$project->getUnixName();
		if (is_dir($repositoryPath)) {
			$writersContent = '';
			$readersContent = '';
			$passwdContent = '';
			if($project->enableAnonSCM()) {
				$repositoryMode = 02775;
				if ($project->enablePserver()) {
					$readersContent = 'anonymous';
					$passwdContent = 'anonymous:8Z8wlZezt48mY';
				}
			} else {
				$repositoryMode = 02770;
			}
			chmod($repositoryPath, $repositoryMode);
			writeFile($repositoryPath.'/CVSROOT/writers', $writersContent);
			writeFile($repositoryPath.'/CVSROOT/readers', $readersContent);
			writeFile($repositoryPath.'/CVSROOT/passwd', $passwdContent);
			addsyncmail($project->getUnixName());
			$hookParams['group_id']=$project->getID();
			$hookParams['file_name']=$repositoryPath;
			plugin_hook("update_cvs_repository",$hookParams);
		} elseif (is_file($repositoryPath)) {
			$err .= $repositoryPath.' already exists as a file';
		} else {
			system(dirname(__FILE__).'/cvscreate.sh '.
				$project->getUnixName().
				' '.($project->getID()+50000).
				' '.$project->enableAnonSCM().
				' '.$project->enablePserver());
			addsyncmail($project->getUnixName());
			$hookParams['group_id']=$project->getID();
			$hookParams['file_name']=$repositoryPath;
			plugin_hook("update_cvs_repository",$hookParams);
			if ($use_cvs_acl == true) {
				system ("cp ".dirname($_SERVER['_']).
					"/aclconfig.default ".$repositoryPath.'/CVSROOT/aclconfig');
				$res_admins = db_query("SELECT users.user_name FROM users,user_group ".
					"WHERE users.user_id=user_group.user_id AND ".
					"user_group.group_id='".$project->getID()."'");
				$useradmin_group = db_result($res_admins,0,'user_name');
				system("cvs -d ".$repositoryPath." racl ".$useradmin_group.":p -r ALL -d ALL");
			}
		}
	}
}

// return's true if it's ok to write the file
function checkLoginfo($file_name) {
	if (!file_exists($file_name)) {
		// files does't exist, it's ok to write it
		return true;
	} else { // check if file is empty or commented out
		$file = @fopen($file_name, 'r');
		if (!$file) { // couldn't open file
			return false;
		}

		while (!feof($file)) {
			$content = trim(fgets($file, 4096));
			if (strlen($content) > 1) {
				if ($content{0} != '#') { // it's not a comment
					fclose($file);
					return false;
				}
			}
		}
		fclose($file);
		return true;
	}
}

cron_entry(13,$err);

?>
