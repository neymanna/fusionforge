<?php
/**
 *	Plugin object
 *
 *	Provides an base class for a plugin
 *
 * This file is copyright (c) Roland Mas <lolando@debian.org>, 2002
 *
 * $Id$
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

class Plugin extends Error {
	var $name ;
	var $hooks ;

	/**
	 * Plugin() - constructor
	 *
	 */
	function Plugin () {
		$this->Error() ;
		$this->name = false ;
		$this->hooks = array () ;
	}

	/**
	 * GetHooks() - get list of hooks to subscribe to
	 *
	 * @return List of strings
	 */
	function GetHooks () {
		return $this->hooks ;
	}

	/**
	 * GetName() - get plugin name
	 *
	 * @return the plugin name
	 */
	function GetName () {
		return $this->name ;
	}

	/**
	 * CallHook() - call a particular hook
	 *
	 * @param hookname - the "handle" of the hook
	 * @param params - array of parameters to pass the hook
	 */
	function CallHook ($hookname, $params) {
		return true ; 
	}

	/**
	 * GetLanguagePath() - get the path where we can find i18n for the plugin
	 *
	 * @return string path
	 */
	function GetLanguagePath() {
		if (file_exists($GLOBALS['sys_plugins_path'].'/'.$this->name.'/common/languages/')) {
			return $GLOBALS['sys_plugins_path'].'/'.$this->name.'/common/languages/';
		} else {
			return $GLOBALS['sys_plugins_path'].'/'.$this->name.'/include/languages/';
		}
	}

	/**
	 * GetSpecificLanguagePath() - get the path where we can find installation specific language files
	 *
	 * @return string path
	 */
	function GetSpecificLanguagePath() {
		global $sys_etc_path;

		return $sys_etc_path.'/plugins/'.$this->name.'/languages/';
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>