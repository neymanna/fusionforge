<?php
/** scmgit - Git Plugin for FusionForge
 *
 * Copyright 2009 Ferenc Székely <ferenc@maemo.org>
 * Copyright 2009 Alain Peyrat <aljeux@free.fr>
 *
 * This file is part of the FusionForge software.
 *
 * This plugin is free software; you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * You should have received a copy of the GNU General Public License
 * along with the plugin. See the LICENSE file.
 */

require_once('common/include/User.class.php');

class GitPlugin extends SCM {

	function GitPlugin () {
		global $gfconfig;

		$this->SCM();

		require_once $gfconfig.'plugins/scmgit/config.php' ;

		$this->test_user = $test_user;
		 
		$this->name = 'scmgit';
		$this->text = 'Git';

		$this->hooks[] = 'scm_page';
		$this->hooks[] = 'scm_admin_update';
		$this->hooks[] = 'scm_admin_page';
		$this->hooks[] = 'scm_plugin';
		//to be revised
		//$this->hooks[] = 'scm_stats';

		$this->git_server = $default_git_server ;
		$this->git_root = $default_git_root;
		$this->use_dav = $default_git_use_dav;
		$this->use_ssl = $default_git_use_ssl;
		$this->use_ssh = false; // TODO: missing in code.
		$this->enabled_by_default = $enabled_by_default ;
		$this->gitweb_server = $default_git_server;

		$this->register();
	}

	function CallHook ($hookname, $params) {
		global $Language, $HTML ;

		switch ($hookname) {
			case 'scm_page':
				$group_id = $params['group_id'] ;
				$this->display_scm_page ($group_id) ;
				break ;
			case 'scm_admin_update':
				$this->scm_admin_update ($params) ;
				break ;
			case 'scm_admin_page':
				$this->display_scm_admin_page ($params) ;
				break ;
			case 'scm_plugin':
				$scm_plugins=& $params['scm_plugins'];
				$scm_plugins[]=$this->name;
				break;
			default:
				// Forgot something
		}
	}

	function display_scm_page ($group_id) {
		global $Language, $HTML ;

		if ($this->test_mode()) {
			exit;
		}

		$project = & group_get_object($group_id);
		if (!$project || !is_object($project)) {
			return false;
		} elseif ($project->isError()) {
			return false;
		}

		if ($project->usesPlugin ($this->name)) {
			// GIT browser links must be displayed if
			// project enables anon SCM or if logged-in
			// user is a member of the group
			$displayGitBrowser = $project->enableAnonSCM();
			if(session_loggedin()) {
				$perm =& $project->getPermission(session_get_user());
				if ($perm && is_object($perm) && !$perm->isError() && $perm->isMember()) {
					$displayGitBrowser = true;
				}
			}

			// ######################## Table for summary info
			?>
<table width="100%">
	<tr valign="top">
		<td width="65%"><?php
		print _('<p>Documentation for Git is available <a href="http://git.or.cz/gitwiki/GitDocumentation/">here</a>.</p>');

		// ######################## Anonymous Git Instructions
		if ($project->enableAnonSCM()) {
			print _('<p><b>Anonymous Git Access</b></p><p>The project\'s Git repository can be checked out through anonymous access with the following command(s).</p>');
			print '<p>';
			if ($this->use_dav == 'true') {
				print '<tt>git clone http' . (($this->use_ssl == 'true') ? 's' : '') . '://' . $this->git_server .  '/' . $this->git_root .'/'. $project->getUnixName() .'</tt><br/>';
			}
			print '</p>';
		}

		// ######################## Developer Access
		if ($this->use_ssh) {
			echo _('<p><b>Developer Git Access via SSH</b></p><p>Project developers can commit to the Git tree via this method. SSH must be installed on your client machine. Substitute <i>developername</i> with the proper values. Enter your site password when prompted.</p>');
			//print '<p><tt>git clone checkout git+ssh://<i>'._('developername').'</i>@' . $project->getSCMBox() . '/'. $this->git_root .'/'. $project->getUnixName().'</tt></p>' ;
		}
		if ($this->use_dav == 'true') {
			echo _('<p><b>Developer Git Access via DAV</b></p><p>Project developers can commit to the Git tree via this method. Substitute <i>developername</i> with the proper values. Enter your site password when prompted.</p>');
			print '<p><tt>git clone http' . (($this->use_ssl == 'true') ? 's' : '') . '://' .  $this->git_server . '/' . $this->git_root . '/' . $project->getUnixName().'</tt></p>' ;
		}

		echo (($this->use_ssl == 'true') ? "<p style='color: red'>" . _('If you experience problems with \'git clone\' or \'git push\' please set the GIT_SSL_NO_VERIFY environment variable to 1') . "</p>": '');

		echo "<p style='color: red'>\n";
		echo _('Please make sure you have filled in your garage credentials to ~/.netrc. The info that needs to be added there is the following:') . "<br/>" . "\n";
		echo _('<pre>machine git.maemo.org<br/>login __garage user name__<br/>password __garage password__</pre>') . "\n";
		echo "</p>";

		// ######################## SVN Snapshot
		if ($displayGitBrowser) {
			printf(_('Git support for garage is still under development. If you experience problems please drop an email to <strong>%1$s</strong>'), $GLOBALS['sys_admin_email']);
		}
		?></td>

		<td width="35%" valign="top"><?php
		// ######################## Git Browsing
		echo $HTML->boxTop(_('Repository History'));
		//echo $this->display_detailed_stats(array('group_id'=>$group_id)).'<p>';
		if ($displayGitBrowser) {
			echo _('<b>Browse the Git Tree</b><p>Browsing the Git tree gives you a great view into the current status of this project\'s code. You may also view the complete histories of any file in the repository.</p>');
			echo _('<strong>This feature is under development.</strong>');
			echo '<p>[<a href="'.$this->get_gitweb_url($group_id).'">'._('Browse Git Repository').'</a>]</p>' ;
		}

		echo $HTML->boxBottom();
		?></td>
	</tr>
</table>
		<?php
		}
	}

	function scm_admin_update ($params) {
		global $Language;

		if ($this->test_mode()) {
			exit;
		}

		$group =& group_get_object($params['group_id']);

		if (!$group || !is_object($group)) {
			return false;
		} elseif ($group->isError()) {
			return false;
		}
		$project = $group->data_array['unix_group_name'];
		if ($group->usesPlugin($this->name)) {
			if ($params['ggit_enable_anon_git']) {
				$group->SetUsesAnonSCM(true);
				$this->remote_command('git_repo_type', $project);
			} else {
				$group->SetUsesAnonSCM(false);
				$this->remote_command('git_repo_type', $project, 'private');
			}
		}
	}

	function display_scm_admin_page ($params) {
		global $Language ;

		if ($this->test_mode()) {
			exit;
		}

		$group =& group_get_object($params['group_id']);
		if ($group->usesPlugin($this->name)) {
			?>
<p><input type="checkbox" name="ggit_enable_anon_git" value="1"
			<?php echo $this->check($group->enableAnonSCM()); ?> /><strong><?php echo _('Enable Anonymous Access') ?></strong></p>
			<?php
		}
	}
	 
	function get_gitweb_url($group_id) {
		$project =& group_get_object($group_id);
		return 'http' . ($this->use_ssl == 'true' ? 's' : '') . '://' . $this->gitweb_server . '/projects/' . $project->getUnixName() . '/gitweb';
	}

	function getDefaultServer() {
		return $this->git_server;
	}

	function test_mode() {
		$ret = 0;
		if(session_loggedin()) {
			$u = session_get_user();
			$username = $u->data_array['user_name'];
			if ($this->test_user != '' && $this->test_user != $username) {
				$ret = 1;
			}
		} else {
			#$ret = 1;
		}
		if ($ret) $this->print_test_message();
		return $ret;
	}

	function print_test_message() {
		echo "The Git plugin at " . $GLOBALS['sys_default_domain'] . " is in testing mode, not yet enabled for mass use. We apologize for the inconvenience.";
	}

	function check($var) {
		if ($var) {
			return 'checked="checked"';
		} else {
			return '';
		}
	}

	function remote_command($command, $project, $type = '') {
		global $Language;
		if ($command == '' || $project == '') {
			echo "Project can not be changed";
			return;
		}
		$cmd = '';
		switch ($command) {
			case "git_repo_type":
				echo _('Changing the repository settings') . " ";
				$cmd = "ssh -i " . $GLOBALS['sys_default_git_ssh_key'];
				$cmd .= " " . $GLOBALS['sys_default_git_remote_user'];
				$cmd .= "@" . $GLOBALS['sys_default_git_server'];
				$cmd .= " " . $GLOBALS['sys_default_git_type_command'];
				break;
			default:
				echo "No such command";
				break;
		}

		if ($cmd != '') {
			$cmd .= " " . $project;
			if ($type == 'private') {
				$cmd .= " private";
			}
			$cmd .= " >/dev/null 2>&1";
			#echo "\n". $cmd . "\n";
			system($cmd, $ret);

			if ( ! $ret ) {
				echo _('<strong>succeeded</strong>');
			} else {
				echo _('<strong>failed</strong>');
			}
		}
	}

	/***
	 * TODO: Stats related functions need to be checked / changed / implemented ...
	 *
	 *
	 */
}

?>
