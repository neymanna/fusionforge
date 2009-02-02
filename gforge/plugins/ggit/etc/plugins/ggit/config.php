<?php
/** ggit - Git Plugin for Gforge
 *
 * Copyright 2009 Ferenc Székely <ferenc@maemo.org>
 *
 * This file is not part of the GForge software.
 *
 * This plugin is free software; you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * You should have received a copy of the GNU General Public License
 * along with the plugin. See the LICENSE file.
 */

$enabled_by_default = 1;

$default_git_server = $GLOBALS['sys_default_git_server'] ;
$default_git_root = $GLOBALS['sys_default_git_root'] ;
$default_git_use_dav = $GLOBALS['sys_default_git_use_dav'] ;
$default_git_use_ssl = $GLOBALS['sys_default_git_use_ssl'] ;

//only this user is able to set Git as an SCM system upon project registration
//set it blank if you want to allow it to everybody 
$test_user = '';

?>
