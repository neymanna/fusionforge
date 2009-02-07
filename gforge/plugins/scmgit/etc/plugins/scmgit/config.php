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

$sys_default_git_remote_user='www-data';
$sys_default_git_ssh_key='/home/www-data/.ssh/id_rsa';
$sys_default_git_type_command='/usr/local/bin/git_repo_type.sh';

$enabled_by_default = 1;

$default_git_server  = 'git.localgarage';
$default_git_root    = 'projects' ;
$default_git_use_dav = true ;
$default_git_use_ssl = true ;

//only this user is able to set Git as an SCM system upon project registration
//set it blank if you want to allow it to everybody 
$test_user = '';

?>
