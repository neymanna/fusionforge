<?php   
/**
 * FusionForge groups
 *
 * Copyright 1999-2001, VA Linux Systems, Inc.
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published
 * by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 * 
 * FusionForge is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
 * USA
 */

require_once $gfcommon.'tracker/ArtifactTypes.class.php';
require_once $gfcommon.'tracker/ArtifactTypeFactory.class.php';
require_once $gfcommon.'forum/Forum.class.php';
require_once $gfcommon.'forum/ForumFactory.class.php';
require_once $gfcommon.'pm/ProjectGroup.class.php';
require_once $gfcommon.'pm/ProjectGroupFactory.class.php';
require_once $gfcommon.'include/Role.class.php';
require_once $gfcommon.'frs/FRSPackage.class.php';
require_once $gfcommon.'docman/DocumentGroup.class.php';
require_once $gfcommon.'mail/MailingList.class.php';
require_once $gfcommon.'mail/MailingListFactory.class.php';
require_once $gfcommon.'survey/SurveyFactory.class.php';
require_once $gfcommon.'survey/SurveyQuestionFactory.class.php';
require_once $gfcommon.'include/gettext.php';

//the license_id of "Other/proprietary" license
define('GROUP_LICENSE_OTHER',126);

$LICENSE_NAMES=array();

/**
 * group_get_licences() - get the licenses list
 *
 * @return array list of licenses
 */
function & group_get_licenses() {
	global $LICENSE_NAMES;
	if(empty($LICENSE_NAMES)) {
		$result = db_query('select * from licenses');
		while($data = db_fetch_array($result)) {
			$LICENSE_NAMES[$data['license_id']] = $data['license_name'];
		}
	}
	return $LICENSE_NAMES;
}

$GROUP_OBJ=array();

/**
 *  group_get_object() - Get the group object.
 *
 *  group_get_object() is useful so you can pool group objects/save database queries
 *  You should always use this instead of instantiating the object directly.
 *
 *  You can now optionally pass in a db result handle. If you do, it re-uses that query
 *  to instantiate the objects.
 *
 *  IMPORTANT! That db result must contain all fields
 *  from groups table or you will have problems
 *
 *  @param		int		Required
 *  @param		int		Result set handle ("SELECT * FROM groups WHERE group_id=xx")
 *  @return a group object or false on failure
 */
function &group_get_object($group_id,$res=false) {
	//create a common set of group objects
	//saves a little wear on the database

	//automatically checks group_type and 
	//returns appropriate object
	
	global $GROUP_OBJ;
	if (!isset($GROUP_OBJ["_".$group_id."_"])) {
		if ($res) {
			//the db result handle was passed in
		} else {
			$res=db_query("SELECT * FROM groups WHERE group_id='$group_id'");
		}
		if (!$res || db_numrows($res) < 1) {
			$GROUP_OBJ["_".$group_id."_"]=false;
		} else {
			/*
				check group type and set up object
			*/
			if (db_result($res,0,'type_id')==1) {
				//project
				$GROUP_OBJ["_".$group_id."_"]= new Group($group_id,$res);
			} else {
				//invalid
				$GROUP_OBJ["_".$group_id."_"]=false;
			}
		}
	}
	return $GROUP_OBJ["_".$group_id."_"];
}

function &group_get_objects($id_arr) {
	global $GROUP_OBJ;
	
	// Note: if we don't do this, the result may be corrupted
	$fetch = array();
	$return = array();
	
	for ($i=0; $i<count($id_arr); $i++) {
		//
		//	See if this ID already has been fetched in the cache
		//
		if (!$id_arr[$i]) {
			continue;
		}
		if (!isset($GROUP_OBJ["_".$id_arr[$i]."_"])) {
			$fetch[]=$id_arr[$i];
		} else {
			$return[] =& $GROUP_OBJ["_".$id_arr[$i]."_"];
		}
	}
	if (count($fetch) > 0) {
		$res=db_query("SELECT * FROM groups WHERE group_id IN ('".implode($fetch,'\',\'') ."')");
		while ($arr =& db_fetch_array($res)) {
			$GROUP_OBJ["_".$arr['group_id']."_"] = new Group($arr['group_id'],$arr);
			$return[] =& $GROUP_OBJ["_".$arr['group_id']."_"];
		}
	}
	return $return;
}

function &group_get_object_by_name($groupname) {
	$res=db_query("SELECT * FROM groups WHERE unix_group_name='$groupname'");
	return group_get_object(db_result($res,0,'group_id'),$res);
}

function &group_get_objects_by_name($groupname_arr) {
	$sql="SELECT group_id FROM groups WHERE unix_group_name IN ('".implode($groupname_arr,'\',\'')."')";
	$res=db_query($sql);
	$arr =& util_result_column_to_array($res,0);
	return group_get_objects($arr);
}

class Group extends Error {
	/**
	 * Associative array of data from db.
	 * 
	 * @var array $data_array.
	 */
	var $data_array;

	/**
	 * array of User objects.
	 * 
	 * @var array $membersArr.
	 */
	var $membersArr;

	/**
	 * Permissions data row from db.
	 * 
	 * @var array $perm_data_array.
	 */
	var $perm_data_array;

	/**
	 * Whether the use is an admin/super user of this project.
	 *
	 * @var bool $is_admin.
	 */
	var $is_admin;

	/**
	 * Artifact types result handle.
	 * 
	 * @var int $types_res.
	 */
	var $types_res;

	/**
	 * Associative array of data for plugins.
	 * 
	 * @var array $plugins_array.
	 */
	var $plugins_array;

	/**
	 *	Group - Group object constructor - use group_get_object() to instantiate.
	 *
	 *	@param	int		Required - group_id of the group you want to instantiate.
	 *	@param	int		Database result from select query OR associative array of all columns.
	 */
	function Group($id=false, $res=false) {
		$this->Error();
		if (!$id) {
			//setting up an empty object
			//probably going to call create()
			return true;
		}
		if (!$res) {
			if (!$this->fetchData($id)) {
				return false;
			}
		} else {
			//
			//	Assoc array was passed in
			//
			if (is_array($res)) {
				$this->data_array =& $res;
			} else {
				if (db_numrows($res) < 1) {
					//function in class we extended
					$this->setError(_('Group Not Found'));
					$this->data_array=array();
					return false;
				} else {
					//set up an associative array for use by other functions
					db_reset_result($res);
					$this->data_array = db_fetch_array($res);
				}
			}
		}
		
		$systemGroups = array(GROUP_IS_NEWS, GROUP_IS_STATS, GROUP_IS_PEER_RATINGS);
		if(!$this->isPublic() && !in_array($id, $systemGroups)) {
			$perm =& $this->getPermission(session_get_user());

			if (!$perm || !is_object($perm) || !$perm->isMember()) {
				$this->setError(_('Permission denied'), ERROR__PERMISSION_DENIED_ERROR);
				return false;
			}
		}
		return true;
	}

	/**
	 *	fetchData - May need to refresh database fields if an update occurred.
	 *
	 *	@param	int	The group_id.
	 */
	function fetchData($group_id) {
		$res = db_query("SELECT * FROM groups WHERE group_id='$group_id'");
		if (!$res || db_numrows($res) < 1) {
			$this->setError(sprintf(_('fetchData():: %s'),db_error()));
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		return true;
	}

	/**
	 *	create - Create new group.
	 *
	 *	This method should be called on empty Group object.
	 *  
	 *  @param	object	The User object.
	 *  @param	string	The full name of the user.
	 *  @param	string	The Unix name of the user.
	 *  @param	string	The new group description.
	 *  @param	int	The ID of the license to use.
	 *  @param	string	The 'other' license to use if any.
	 *  @param	string	The purpose of the group.
	 */
	function create(&$user, $full_name, $unix_name, $description, $license, $license_other, $purpose, $unix_box='shell1', $scm_box='cvs1', $is_public=1) {
		// $user is ignored - anyone can create pending group

		if ($this->getID()!=0) {
			$this->setError(_('Group::create: Group object already exists'));
			return false;
		} else if (strlen($full_name)<3) {
			$this->setError(_('Invalid full name'));
			return false;
		} else if (!account_groupnamevalid($unix_name)) {
			$this->setError(_('Invalid Unix name'));
			return false;
		} else if (db_numrows(db_query("SELECT group_id FROM groups WHERE unix_group_name='$unix_name'")) > 0) {
			$this->setError(_('Unix name already taken'));
			return false;
		} else if (strlen($purpose)<10) {
			$this->setError(_('Please describe your Registration Purpose in a more comprehensive manner'));
			return false;
		} else if (strlen($purpose)>1500) {
			$this->setError(_('The Registration Purpose text is too long. Please make it smaller than 1500 bytes.'));
			return false;
		} else if (strlen($description)<10) {
			$this->setError(_('Describe in a more comprehensive manner your project.'));
			return false;
		} else if (strlen($description)>255) {
			$this->setError(_('Your project description is too long. Please make it smaller than 256 bytes.'));
			return false;
		} else if (!$license) {
			$this->setError(_('You have not chosen a license'));
			return false;
		} else if ($license!=GROUP_LICENSE_OTHER && $license_other) {
			$this->setError(_('Conflicting licenses choice'));
			return false;
		} else if ($license==GROUP_LICENSE_OTHER && strlen($license_other)<50) {
			$this->setError(_('Please give more comprehensive licensing description'));
			return false;
		} else {

			srand((double)microtime()*1000000);
			$random_num = rand(0,1000000);
	
			db_begin();
	
			$res = db_query("
				INSERT INTO groups (
					group_name,
					is_public,
					unix_group_name,
					short_description,
					http_domain,
					homepage,
					status,
					unix_box,
					scm_box,
					license,
					register_purpose,
					register_time,
					license_other,
                                        enable_anonscm,
					rand_hash
				)
				VALUES (
					'".htmlspecialchars($full_name)."',
					'$is_public',
					'$unix_name',
					'".htmlspecialchars($description)."',
					'$unix_name.".$GLOBALS['sys_default_domain']."',
					'$unix_name.".$GLOBALS['sys_default_domain']."',
					'P',
					'$unix_box',
					'$scm_box',
					'$license',
					'".htmlspecialchars($purpose)."',
					".time().",
					'".htmlspecialchars($license_other)."',
					'$is_public',
					'".md5($random_num)."'
				)
			");
	
			if (!$res || db_affected_rows($res) < 1) {
				$this->setError(sprintf(_('ERROR: Could not create group: %s'),db_error()));
				db_rollback();
				return false;
			}
	
			$id = db_insertid($res, 'groups', 'group_id');
			if (!$id) {
				$this->setError(sprintf(_('ERROR: Could not get group id: %s'),db_error()));
				db_rollback();
				return false;
			}
	
			//
			// Now, make the user an admin
			//
			$sql="INSERT INTO user_group ( user_id, group_id, admin_flags,
				cvs_flags, artifact_flags, forum_flags, role_id)
				VALUES ( ".$user->getID().", '$id', 'A', 1, 2, 2, 1)";
	
			$res=db_query($sql);
			if (!$res || db_affected_rows($res) < 1) {
				$this->setError(sprintf(_('ERROR: Could not add admin to newly created group: %s'),db_error()));
				db_rollback();
				return false;
			}
	
			if (!$this->fetchData($id)) {
				db_rollback();
				return false;
			}

			$hook_params = array ();
			$hook_params['group'] = $this;
			$hook_params['group_id'] = $this->getID();
			$hook_params['group_name'] = $full_name;
			$hook_params['unix_group_name'] = $unix_name;
			plugin_hook ("group_create", $hook_params);
			
			db_commit();
			$this->sendNewProjectNotificationEmail();
			return true;
		}
	}


	/**
	 *	updateAdmin - Update core properties of group object.
	 *
	 *	This function require site admin privilege.
	 *
	 *	@param	object	User requesting operation (for access control).
	 *	@param	bool	Whether group is publicly accessible (0/1).
	 *	@param	string	Project's license (string ident).
	 *	@param	int		Group type (1-project, 2-foundry).
	 *	@param	string	Machine on which group's home directory located.
	 *	@param	string	Domain which serves group's WWW.
	 *	@return status.
	 *	@access public.
	 */
	function updateAdmin(&$user, $is_public, $license, $type_id, $unix_box, $http_domain) {
		$perm =& $this->getPermission($user);

		if (!$perm || !is_object($perm)) {
			$this->setError(_('Could not get permission.'));
			return false;
		}

		if (!$perm->isSuperUser()) {
			$this->setError(_('Permission denied.'));
			return false;
		}

		db_begin();

		$res = db_query("
			UPDATE groups
			SET is_public='$is_public',
				license='$license',type_id='$type_id',
				unix_box='$unix_box',http_domain='$http_domain'
			WHERE group_id='".$this->getID()."'
		");

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(_('ERROR: DB: Could not change group properties: %s'),db_error());
			db_rollback();
			return false;
		}

		// Log the audit trail
		if ($is_public != $this->isPublic()) {
			$this->addHistory('is_public', $this->isPublic());
		}
		if ($license != $this->data_array['license']) {
			$this->addHistory('license', $this->data_array['license']);
		}
		if ($type_id != $this->data_array['type_id']) {
			$this->addHistory('type_id', $this->data_array['type_id']);
		}
		if ($unix_box != $this->data_array['unix_box']) {
			$this->addHistory('unix_box', $this->data_array['unix_box']);
		}
		if ($http_domain != $this->data_array['http_domain']) {
			$this->addHistory('http_domain', $this->data_array['http_domain']);
		}

		if (!$this->fetchData($this->getID())) {
			db_rollback();
			return false;
		}
		db_commit();
		return true;
	}

	/**
	 *	update - Update number of common properties.
	 *
	 *	Unlike updateAdmin(), this function accessible to project admin.
	 *
	 *	@param	object	User requesting operation (for access control).
	 *	@param	bool	Whether group is publicly accessible (0/1).
	 *	@param	string	Project's license (string ident).
	 *	@param	int		Group type (1-project, 2-foundry).
	 *	@param	string	Machine on which group's home directory located.
	 *	@param	string	Domain which serves group's WWW.
	 *	@return int	status.
	 *	@access public.
	 */
	function update(&$user, $group_name,$homepage,$short_description,$use_mail,$use_survey,$use_forum,
		$use_pm,$use_pm_depend_box,$use_scm,$use_news,$use_docman,
		$new_doc_address,$send_all_docs,$logo_image_id,
		$enable_pserver,$enable_anonscm,
			$use_ftp,$use_tracker,$use_frs,$use_stats,$is_public) {

		$perm =& $this->getPermission($user);

		if (!$perm || !is_object($perm)) {
			$this->setError(_('Could not get permission.'));
			return false;
		}

		if (!$perm->isAdmin()) {
			$this->setError(_('Permission denied.'));
			return false;
		}

		// Validate some values
		if (!$group_name) {
			$this->setError(_('Invalid Group Name'));
			return false;
		}

		if ($new_doc_address) {
			$invalid_mails = validate_emails($new_doc_address);
			if (count($invalid_mails) > 0) {
				$this->setError(sprintf (ngettext('New Doc Address Appeared Invalid: %s', 'New Doc Addresses Appeared Invalid: %s', count($invalid_mails)),implode(',',$invalid_mails)));
				return false;
			}
		}

		// in the database, these all default to '1',
		// so we have to explicity set 0
		if (!$use_mail) {
			$use_mail=0;
		}
		if (!$use_survey) {
			$use_survey=0;
		}
		if (!$use_forum) {
			$use_forum=0;
		}
		if (!$use_pm) {
			$use_pm=0;
		}
		if (!$use_pm_depend_box) {
			$use_pm_depend_box=0;
		}
		if (!$use_scm) {
			$use_scm=0;
		}
		if (!$use_news) {
			$use_news=0;
		}
		if (!$use_docman) {
			$use_docman=0;
		}
		if (!$use_ftp) {
			$use_ftp=0;
		}
		if (!$use_tracker) {
			$use_tracker=0;
		}
		if (!$use_frs) {
			$use_frs=0;
		}
		if (!$use_stats) {
			$use_stats=0;
		}
		if (!$send_all_docs) {
			$send_all_docs=0;
		}

		if (!$homepage) {
			$homepage=$GLOBALS['sys_default_domain'].'/projects/'.$this->getUnixName().'/';
		}

		if (strlen($short_description)>255) {
			$this->setError(_('Error updating project information: Maximum length for Project Description is 255 chars.'));
			return false;
		}

		db_begin();

		//XXX not yet actived logo_image_id='$logo_image_id', 
		$sql = "
			UPDATE groups
			SET 
				group_name='".htmlspecialchars($group_name)."',
				homepage='$homepage',
				short_description='".htmlspecialchars($short_description)."',
				use_mail='$use_mail',
				use_survey='$use_survey',
				use_forum='$use_forum',
				use_pm='$use_pm',
				use_pm_depend_box='$use_pm_depend_box',
				use_scm='$use_scm',
				use_news='$use_news',
				use_docman='$use_docman',
                                is_public='$is_public',
				new_doc_address='$new_doc_address',
				send_all_docs='$send_all_docs',
		";
		if ($enable_pserver != '') {
		$sql .= "
				enable_pserver='$enable_pserver',
		";
		}
		if ($enable_anonscm != '') {
		$sql .= "
				enable_anonscm='$enable_anonscm',
		";
		}
		$sql .= "
				use_ftp='$use_ftp',
				use_tracker='$use_tracker',
				use_frs='$use_frs',
				use_stats='$use_stats'
			WHERE group_id='".$this->getID()."'
		";
		$res = db_query($sql);

		if (!$res) {
			$this->setError(sprintf(_('Error updating project information: %s'), db_error()));
			db_rollback();
			return false;
		}

		$hook_params = array ();
		$hook_params['group'] = $this;
		$hook_params['group_id'] = $this->getID();
		$hook_params['group_homepage'] = $homepage;
		$hook_params['group_name'] = htmlspecialchars($group_name);
		$hook_params['group_description'] = htmlspecialchars($short_description);
		plugin_hook ("group_update", $hook_params);

		// Log the audit trail
		$this->addHistory('Changed Public Info', '');

		if (!$this->fetchData($this->getID())) {
			db_rollback();
			return false;
		}
		db_commit();
		return true;
	}

	/**
	 *	getID - Simply return the group_id for this object.
	 *
	 *	@return int group_id.
	 */
	function getID() {
		return $this->data_array['group_id'];
	}

	/**
	 *	getType() - Foundry, project, etc.
	 *
	 *	@return	int	The type flag from the database.
	 */
	function getType() {
		return $this->data_array['type_id'];
	}


	/**
	 *	getStatus - the status code.
	 *
	 *	Statuses	char	include I,H,A,D.
	 */
	function getStatus() {
		return $this->data_array['status'];
	}

	/**
	 *	setStatus - set the status code.
	 *
	 *	Statuses include I,H,A,D.
	 *
	 *	@param	object	User requesting operation (for access control).
	 *	@param	string	Status value.
	 *	@return	boolean	success.
	 *	@access public.
	 */
	function setStatus(&$user, $status) {
		global $SYS;

		$perm =& $this->getPermission($user);
		if (!$perm || !is_object($perm)) {
			$this->setPermissionDeniedError();
			return false;
		} elseif (!$perm->isSuperUser()) {
			$this->setPermissionDeniedError();
			return false;
		}

		//	Projects in 'A' status can only go to 'H' or 'D'
		//	Projects in 'D' status can only go to 'A'
		//	Projects in 'P' status can only go to 'A' OR 'D'
		//	Projects in 'I' status can only go to 'P'
		//	Projects in 'H' status can only go to 'A' OR 'D'
		$allowed_status_changes = array(
			'AH'=>1,'AD'=>1,'DA'=>1,'PA'=>1,'PD'=>1,
			'IP'=>1,'HA'=>1,'HD'=>1
		);			  

		// Check that status transition is valid
		if ($this->getStatus() != $status
			&& !$allowed_status_changes[$this->getStatus().$status]) {
			$this->setError(_('Invalid Status Change'));
			return false;
		}

		db_begin();

		$res = db_query("UPDATE groups
			SET status='$status'
			WHERE group_id='". $this->getID()."'");

		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('ERROR: DB: Could not change group status: %s'),db_error()));
			db_rollback();
			return false;
		}

		if ($status=='A') {
			// Activate system group, if not yet
			if (!$SYS->sysCheckGroup($this->getID())) {
				if (!$SYS->sysCreateGroup($this->getID())) {
					$this->setError($SYS->getErrorMessage());
					db_rollback();
					return false;
				}
			}
			if (!$this->activateUsers()) {
				db_rollback();
				return false;
			}

		/* Otherwise, the group is not active, and make sure that
		   System group is not active either */
		} else if ($SYS->sysCheckGroup($this->getID())) {
			if (!$SYS->sysRemoveGroup($this->getID())) {
				$this->setError($SYS->getErrorMessage());
				db_rollback();
				return false;
			}
		}

		$hook_params = array ();
		$hook_params['group'] = $this;
		$hook_params['group_id'] = $this->getID();
		$hook_params['status'] = $status;
		plugin_hook ("group_setstatus", $hook_params);
		
		db_commit();

		// Log the audit trail
		if ($status != $this->getStatus()) {
			$this->addHistory('Status', $this->getStatus());
		}

		$this->data_array['status'] = $status;
		return true;
	}

	/**
	 *	isProject - Simple boolean test to see if it's a project or not.
	 *
	 *	@return	boolean is_project.
	 */
	function isProject() {
		if ($this->getType()==1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *	isPublic - Simply returns the is_public flag from the database.
	 *
	 *	@return	boolean	is_public.
	 */
	function isPublic() {
		return $this->data_array['is_public'];
	}

	/**
	 *	isActive - Database field status of 'A' returns true.
	 *
	 *	@return	boolean	is_active.
	 */
	function isActive() {
		if ($this->getStatus()=='A') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *  getUnixName - the unix_name
	 *
	 *  @return	string	unix_name.
	 */
	function getUnixName() {
		return strtolower($this->data_array['unix_group_name']);
	}

	/**
	 *  getPublicName - the full-length public name.
	 *
	 *  @return	string	The group_name.
	 */
	function getPublicName() {
		return $this->data_array['group_name'];
	}

	/**
	 *  getRegisterPurpose - the text description of the purpose of this project.
	 *
	 *  @return	string	The description.
	 */
	function getRegisterPurpose() {
		return $this->data_array['register_purpose'];
	}

	/**
	 *  getDescription	- the text description of this project.
	 *
	 *  @return	string	The description.
	 */
	function getDescription() {
		return $this->data_array['short_description'];
	}

	/**
	 *  getStartDate - the unix time this project was registered.
	 *
	 *  @return int (unix time) of registration.
	 */
	function getStartDate() {
		return $this->data_array['register_time'];
	}

	/**
	 *  getLogoImageID - the id of the logo in the database for this project.
	 *
	 *  @return	int	The ID of logo image in db_images table (or 100 if none).
	 */
	function getLogoImageID() {
		return $this->data_array['logo_image_id'];
	}

	/**
	 *  getUnixBox - the hostname of the unix box where this project is located.
	 *
	 *  @return	string	The name of the unix machine for the group.
	 */
	function getUnixBox() {
		return $this->data_array['unix_box'];
	}

	/**
	 *  getSCMBox - the hostname of the scm box where this project is located.
	 *
	 *  @return	string	The name of the unix machine for the group.
	 */
	function getSCMBox() {
		return $this->data_array['scm_box'];
	}
	/**
	 * setSCMBox - the hostname of the scm box where this project is located.
	 *
	 * @param	string The name of the new SCM_BOX
	 */
	function setSCMBox($scm_box) {
		if ($scm_box) {
			db_begin();
			$sql = "UPDATE groups SET scm_box = '$scm_box' WHERE group_id = ".$this->getID();
			$res = db_query($sql);
			if ($res) {
				$this->addHistory('scm_box', $this->data_array['scm_box']);
				$this->data_array['scm_box']=$scm_box;
				db_commit();
				return true;
			} else {
				db_rollback();
				$this->setError(_("Couldn't insert SCM_BOX to database"));
				return false;
			}
		} else {
			$this->setError(_("SCM Box can't be empty"));
			return false;
		}
	}

	/**
	 *  getDomain - the hostname.domain where their web page is located.
	 *
	 *  @return	string	The name of the group [web] domain.
	 */
	function getDomain() {
		return $this->data_array['http_domain'];
	}

	/**
	 *  getLicense	- the license they chose.
	 *
	 *  @return	int	ident of group license.
	 */
	function getLicense() {
		return $this->data_array['license'];
	}
	
	/**
	 * getLicenseName - the name of the license
	 *
	 * @return string license name
	 */
	function getLicenseName() {
		$licenses =& group_get_licenses();
		if(isset($licenses[$this->data_array['license']])) {
			return $licenses[$this->data_array['license']];
		} else {
			return '';
		}
	}

	/**
	 *  getLicenseOther - optional string describing license.
	 *
	 *  @return	string	The custom license.
	 */
	function getLicenseOther() {
		if ($this->getLicense() == GROUP_LICENSE_OTHER) {
			return $this->data_array['license_other'];
		} else {
			return '';
		}
	}

	/**
	 *  getRegistrationPurpose - the text description of the purpose of this project.
	 *
	 *  @return	string	The application for project hosting.
	 */
	function getRegistrationPurpose() {
		return $this->data_array['register_purpose'];
	}


	/**
	 * getAdmins() - Get array of Admin user objects.
	 *
	 *	@return	array	Array of User objects.
	 */
	function &getAdmins() {
		// this function gets all group admins in order to send Jabber and mail messages
		$q = "SELECT user_id FROM user_group WHERE admin_flags = 'A' AND group_id = ".$this->getID();
		$res = db_query($q);
		$user_ids=util_result_column_to_array($res);
		return user_get_objects($user_ids);
	}
		
	/*

		Common Group preferences for tools

	*/

	/**
	 *	enableAnonSCM - whether or not this group has opted to enable Anonymous SCM.
	 *
	 *	@return boolean enable_scm.
	 */
	function enableAnonSCM() {
		if ($this->isPublic() && $this->usesSCM()) {
			return $this->data_array['enable_anonscm'];
		} else {
			return false;
		}
	}

	function SetUsesAnonSCM ($booleanparam) {
		db_begin () ;
		$booleanparam = $booleanparam ? 1 : 0 ;
		$sql = "UPDATE groups SET enable_anonscm = $booleanparam WHERE group_id = ".$this->getID() ;
		$res = db_query($sql);
		if ($res) {
			$this->data_array['enable_anonscm']=$booleanparam;
			db_commit () ;
		} else {
			db_rollback ();
			return false;
		}
	}

	/**
	 *	enablePserver - whether or not this group has opted to enable Pserver.
	 *
	 *	@return boolean	enable_pserver.
	 */
	function enablePserver() {
		if ($this->usesSCM()) {
			return $this->data_array['enable_pserver'];
		} else {
			return false;
		}
	}

	function SetUsesPserver ($booleanparam) {
		db_begin () ;
		$booleanparam = $booleanparam ? 1 : 0 ;
		$sql = "UPDATE groups SET enable_pserver = $booleanparam WHERE group_id = ".$this->getID() ;
		$res = db_query($sql);
		if ($res) {
			$this->data_array['enable_pserver']=$booleanparam;
			db_commit () ;
		} else {
			db_rollback();
			return false;
		}
	}

	/**
	 *	usesSCM - whether or not this group has opted to use SCM.
	 *
	 *	@return	boolean	uses_scm.
	 */
	function usesSCM() {
		global $sys_use_scm;
		if ($sys_use_scm) {
			return $this->data_array['use_scm'];
		} else {
			return false;
		}
	}

	/**
	 *	usesMail - whether or not this group has opted to use mailing lists.
	 *
	 *	@return	boolean uses_mail.
	 */
	function usesMail() {
		global $sys_use_mail;
		if ($sys_use_mail) {
			return $this->data_array['use_mail'];
		} else {
			return false;
		}
	}

	/**
	 * 	usesNews - whether or not this group has opted to use news.
	 *
	 *	@return	boolean	uses_news.
	 */
	function usesNews() {
		global $sys_use_news;
		if ($sys_use_news) {
			return $this->data_array['use_news'];
		} else {
			return false;
		}
	}

	/**
	 *	usesForum - whether or not this group has opted to use discussion forums.
	 *
	 *  @return	boolean	uses_forum.
	 */
	function usesForum() {
		global $sys_use_forum;
		if ($sys_use_forum) {
			return $this->data_array['use_forum'];
		} else {
			return false;
		}
	}	   

	/**
	 *  usesStats - whether or not this group has opted to use stats.
	 *
	 *  @return	boolean	uses_stats.
	 */
	function usesStats() {
		return $this->data_array['use_stats'];
	}

	/**
	 *  usesFRS - whether or not this group has opted to use file release system.
	 *
	 *  @return	boolean	uses_frs.
	 */
	function usesFRS() {
		global $sys_use_frs;
		if ($sys_use_frs) {
			return $this->data_array['use_frs'];
		} else {
			return false;
		}
	}

	/**
	 *  usesTracker - whether or not this group has opted to use tracker.
	 *
	 *  @return	boolean	uses_tracker.
	 */
	function usesTracker() {
		global $sys_use_tracker;
		if ($sys_use_tracker) {
			return $this->data_array['use_tracker'];
		} else {
			return false;
		}
	}

	/**
	 *  usesDocman - whether or not this group has opted to use docman.
	 *
	 *  @return	boolean	uses_docman.
	 */
	function usesDocman() {
		global $sys_use_docman;
		if ($sys_use_docman) {
			return $this->data_array['use_docman'];
		} else {
			return false;
		}
	}

	/**
	 *  usesFTP - whether or not this group has opted to use FTP.
	 *
	 *  @return	boolean	uses_ftp.
	 */
	function usesFTP() {
		global $sys_use_ftp;
		if ($sys_use_ftp) {
			return $this->data_array['use_ftp'];
		} else {
			return false;
		}
	}

	/**
	 *  usesSurvey - whether or not this group has opted to use surveys.
	 *
	 *  @return	boolean	uses_survey.
	 */
	function usesSurvey() {
		global $sys_use_survey;
		if ($sys_use_survey) {
			return $this->data_array['use_survey'];
		} else {
			return false;
		}
	}	   

	/**
	 *  usesPM - whether or not this group has opted to Project Manager.
	 *
	 *  @return	boolean	uses_projman.
	 */
	function usesPM() {
		global $sys_use_pm;
		if ($sys_use_pm) {
			return $this->data_array['use_pm'];
		} else {
			return false;
		}
	}

	/**
	 *  getPlugins -  get a list of all available group plugins
	 *
	 *  @return	array	array containing plugin_id => plugin_name
	 */
	function getPlugins() {
		if (!isset($this->plugins_data)) {
			$this->plugins_data = array () ;
			$sql="SELECT group_plugin.plugin_id, plugins.plugin_name
							  FROM group_plugin, plugins
				  WHERE group_plugin.group_id=".$this->getID()."
								AND group_plugin.plugin_id = plugins.plugin_id" ;
			$res=db_query($sql);
			$rows = db_numrows($res);

			for ($i=0; $i<$rows; $i++) {
				$plugin_id = db_result($res,$i,'plugin_id');
				$this->plugins_data[$plugin_id] = db_result($res,$i,'plugin_name');
			}
		}
		return $this->plugins_data ;
	}

	/**
	 *  usesPlugin - returns true if the group uses a particular plugin 
	 *
	 *  @param	string	name of the plugin
	 *  @return	boolean	whether plugin is being used or not
	 */
	function usesPlugin($pluginname) {
		$plugins_data = $this->getPlugins() ;
		foreach ($plugins_data as $p_id => $p_name) {
			if ($p_name == $pluginname) {
				return true ;
			}
		}
		return false ;
	}

	/**
	 *  setPluginUse - enables/disables plugins for the group
	 *
	 *  @param	string	name of the plugin
	 *  @param	boolean	the new state
	 *  @return	string	database result 
	 */
	function setPluginUse($pluginname, $val=true) {
		if ($val == $this->usesPlugin($pluginname)) {
			// State is already good, returning
			return true ;
		}
		$sql="SELECT plugin_id
			  FROM plugins
			  WHERE plugin_name = '" . $pluginname . "'" ;
		$res=db_query($sql);
		$rows = db_numrows($res);
		if ($rows == 0) {
			// Error: no plugin by that name
			return false ;
		}
		$plugin_id = db_result($res,0,'plugin_id');
		// Invalidate cache
		unset ($this->plugins_data) ;
		if ($val) {
			$sql="INSERT INTO group_plugin (group_id, plugin_id)
							  VALUES (". $this->getID() . ", ". $plugin_id .")" ;
			$res=db_query($sql);
			return $res ;
		} else {
			$sql="DELETE FROM group_plugin
				WHERE group_id = ". $this->getID() . "
				AND plugin_id = ". $plugin_id ;
			$res=db_query($sql);
			return $res ;
		}
	}

	/**
	 *  getDocEmailAddress - get email address(es) to send doc notifications to.
	 *
	 *  @return	string	email address.
	 */
	function getDocEmailAddress() {
		return $this->data_array['new_doc_address'];
	}

	/**
	 *  DocEmailAll - whether or not this group has opted to use receive notices on all doc updates.
	 *
	 *  @return	boolean	email_on_all_doc_updates.
	 */
	function docEmailAll() {
		return $this->data_array['send_all_docs'];
	}


	/**
	 *	getHomePage - The URL for this project's home page.
	 *
	 *	@return	string	homepage URL.
	 */
	function getHomePage() {
		return $this->data_array['homepage'];
	}

	/**
	 *	getPermission - Return a Permission for this Group and the specified User.
	 *
	 *	@param	object	The user you wish to get permission for (usually the logged in user).
	 *	@return	object	The Permission.
	 */
	function &getPermission(&$_user) {
		return permission_get_object($this, $_user);
	}


	/**
	 *	userIsAdmin - Return if for this Group the User is admin.
	 *
	 *	@return boolean	is_admin.
	 */
	function userIsAdmin() {
		$perm =& $this->getPermission( session_get_user() );
		if (!$perm || !is_object($perm)) {
			return false;
		} elseif ($perm->isError()) {
			return false;
		}
		return $perm->isAdmin();
	}

	function delete($sure,$really_sure,$really_really_sure) {
		if (!$sure || !$really_sure || !$really_really_sure) {
			$this->setMissingParamsError();
			return false;
		}
		if ($this->getID() == $GLOBALS['sys_news_group'] ||
			$this->getID() == 1 ||
			$this->getID() == $GLOBALS['sys_stats_group'] ||
			$this->getID() == $GLOBALS['sys_peer_rating_group']) {
			$this->setError(_('Cannot Delete System Group'));
			return false;
		}
		$perm =& $this->getPermission( session_get_user() );
		if (!$perm || !is_object($perm)) {
			$this->setPermissionDeniedError();
			return false;
		} elseif ($perm->isError()) {
			$this->setPermissionDeniedError();
			return false;
		} elseif (!$perm->isSuperUser()) {
			$this->setPermissionDeniedError();
			return false;
		}

		db_begin();
		//
		//	Remove all the members
		//
		$members =& $this->getMembers();
		for ($i=0; $i<count($members); $i++) {
			$this->removeUser($members[$i]->getID());
		}
		//
		//	Delete Trackers
		//
		$atf = new ArtifactTypeFactory($this);
		$at_arr =& $atf->getArtifactTypes();
		for ($i=0; $i<count($at_arr); $i++) {
			if (!is_object($at_arr[$i])) {
				printf (_("Not Object: ArtifactType: %d"),$i);
				continue;
			}
			$at_arr[$i]->delete(1,1);
		}
		//
		//	Delete Forums
		//
		$ff = new ForumFactory($this);
		$f_arr =& $ff->getForums();
		for ($i=0; $i<count($f_arr); $i++) {
			if (!is_object($f_arr[$i])) {
				printf (_("Not Object: Forum: %d"),$i);
				continue;
			}
			$f_arr[$i]->delete(1,1);
//echo 'ForumFactory'.db_error();
		}
		//
		//	Delete Subprojects
		//
		$pgf = new ProjectGroupFactory($this);
		$pg_arr =& $pgf->getProjectGroups();
		for ($i=0; $i<count($pg_arr); $i++) {
			if (!is_object($pg_arr[$i])) {
				printf (_("Not Object: ProjectGroup: %d"),$i);
				continue;
			}
			$pg_arr[$i]->delete(1,1);
//echo 'ProjectGroupFactory'.db_error();
		}
		//
		//	Delete FRS Packages
		//
		//$frspf = new FRSPackageFactory($this);
		$res=db_query("SELECT * FROM frs_package WHERE group_id='".$this->getID()."'");
//echo 'frs_package'.db_error();
		//$frsp_arr =& $frspf->getPackages();
		while ($arr = db_fetch_array($res)) {
			//if (!is_object($pg_arr[$i])) {
			//	echo "Not Object: ProjectGroup: ".$i;
			//	continue;
			//}
			$frsp=new FRSPackage($this,$arr['package_id'],$arr);
			$frsp->delete(1,1);
		}
		//
		//	Delete news
		//
		$news_group=&group_get_object($GLOBALS['sys_news_group']);
		$res=db_query("SELECT forum_id FROM news_bytes WHERE group_id='".$this->getID()."'");
		for ($i=0; $i<db_numrows($res); $i++) {
			$Forum = new Forum($news_group,db_result($res,$i,'forum_id'));
			if (!$Forum->delete(1,1)) {
				printf (_("Could Not Delete News Forum: %d"),$Forum->getID());
			}
		}
		$res=db_query("DELETE FROM news_bytes WHERE group_id='".$this->getID()."'");

		//
		//	Delete docs
		//
		$res=db_query("DELETE FROM doc_data WHERE group_id='".$this->getID()."'");
//echo 'doc_data'.db_error();
		$res=db_query("DELETE FROM doc_groups WHERE group_id='".$this->getID()."'");
//echo 'doc_groups'.db_error();
		//
		//	Delete group history
		//
		$res=db_query("DELETE FROM group_history WHERE group_id='".$this->getID()."'");
//echo 'group_history'.db_error();
		//
		//	Delete group plugins
		//
		$res=db_query("DELETE FROM group_plugin WHERE group_id='".$this->getID()."'");
//echo 'group_plugin'.db_error();
		//
		//	Delete group cvs stats
		//
		$res=db_query("DELETE FROM stats_cvs_group WHERE group_id='".$this->getID()."'");
//echo 'stats_cvs_group'.db_error();
		//
		//	Delete Surveys
		//
		$sf = new SurveyFactory($this);
		$s_arr =& $sf->getSurveys();
		for ($i=0; $i<count($s_arr); $i++) {
			if (!is_object($s_arr[$i])) {
				printf (_("Not Object: Survey: %d"),$i);
				continue;
			}
			$s_arr[$i]->delete();
//echo 'SurveyFactory'.db_error();
		}
		//
		//	Delete SurveyQuestions
		//
		$sqf = new SurveyQuestionFactory($this);
		$sq_arr =& $sqf->getSurveyQuestions();
		for ($i=0; $i<count($sq_arr); $i++) {
			if (!is_object($sq_arr[$i])) {
				printf (_("Not Object: SurveyQuestion: %d"),$i);
				continue;
			}
			$sq_arr[$i]->delete();
//echo 'SurveyQuestionFactory'.db_error();
		}
		//
		//	Delete Mailing List Factory
		//
		$mlf = new MailingListFactory($this);
		$ml_arr =& $mlf->getMailingLists();
		for ($i=0; $i<count($ml_arr); $i++) {
			if (!is_object($ml_arr[$i])) {
				printf (_("Not Object: MailingList: %d"),$i);
				continue;
			}
			if (!$ml_arr[$i]->delete(1,1)) {
				$this->setError(_('Could not properly delete the mailing list'));
			}
//echo 'MailingListFactory'.db_error();
		}
		//
		//	Delete trove
		//
		$res=db_query("DELETE FROM trove_group_link WHERE group_id='".$this->getID()."'");
		$res=db_query("DELETE FROM trove_agg WHERE group_id='".$this->getID()."'");
		//
		//	Delete counters
		//
		$res=db_query("DELETE FROM project_sums_agg WHERE group_id='".$this->getID()."'");
//echo 'project_sums_agg'.db_error();
		$res=db_query("INSERT INTO deleted_groups (
		unix_group_name,delete_date,isdeleted) VALUES 
		('".$this->getUnixName()."','".time()."','0')");
//echo 'InsertIntoDeleteQueue'.db_error();
		$res=db_query("DELETE FROM groups WHERE group_id='".$this->getID()."'");
//echo 'DeleteGroup'.db_error();
		db_commit();
		if (!$res) {
			return false;
		}
               
		$hook_params = array ();
		$hook_params['group'] = $this;
		$hook_params['group_id'] = $this->getID();
		plugin_hook ("group_delete", $hook_params);
		
		if (isset($GLOBALS['sys_upload_dir']) && $this->getUnixName()) {
			exec('/bin/rm -rf '.$GLOBALS['sys_upload_dir'].'/'.$this->getUnixName().'/');
		}
		if (isset($GLOBALS['sys_ftp_upload_dir']) && $this->getUnixName()) {
			exec('/bin/rm -rf '.$GLOBALS['sys_ftp_upload_dir'].'/'.$this->getUnixName().'/');
		}
		//
		//	Delete reporting
		//
		$res=db_query("DELETE FROM rep_group_act_weekly WHERE group_id='".$this->getID()."'");
//echo 'rep_group_act_weekly'.db_error();
		$res=db_query("DELETE FROM rep_group_act_monthly WHERE group_id='".$this->getID()."'");
//echo 'rep_group_act_monthly'.db_error();
		$res=db_query("DELETE FROM rep_group_act_daily WHERE group_id='".$this->getID()."'");
//echo 'rep_group_act_daily'.db_error();
		unset($this->data_array);
		return true;

	}

	/*


		Basic functions to add/remove users to/from a group
		and update their permissions


	*/

	/**
	 *	addUser - controls adding a user to a group.
	 *  
	 *  @param	string	Unix name of the user to add OR integer user_id.
	 *	@param	int	The role_id this user should have.
	 *	@return	boolean	success.
	 *	@access public.
	 */
	function addUser($user_unix_name,$role_id) {
		global $SYS;
		/*
			Admins can add users to groups
		*/

		$perm =& $this->getPermission( session_get_user() );
		if (!$perm || !is_object($perm) || !$perm->isAdmin()) {
			$this->setPermissionDeniedError();
			return false;
		}
		db_begin();
		
		/*
			get user id for this user's unix_name
		*/
		if (eregi('[^0-9]',$user_unix_name)) {
			$res_newuser = db_query("SELECT * FROM users WHERE user_name='". strtolower($user_unix_name) ."'");
		} else {
			$res_newuser = db_query("SELECT * FROM users WHERE user_id='". intval($user_unix_name) ."'");
		}
		if (db_numrows($res_newuser) > 0) {
			//
			//	make sure user is active
			//
			if (db_result($res_newuser,0,'status') != 'A') {
				$this->setError(_('User is not active. Only active users can be added.'));
				db_rollback();
				return false;
			}

			//
			//	user was found - set new user_id var
			//
			$user_id = db_result($res_newuser,0,'user_id');

			//
			//	if not already a member, add them
			//
			$res_member = db_query("SELECT user_id 
				FROM user_group 
				WHERE user_id='$user_id' AND group_id='". $this->getID() ."'");

			if (db_numrows($res_member) < 1) {
				//
				//	Create this user's row in the user_group table
				//
				$res=db_query("INSERT INTO user_group 
					(user_id,group_id,admin_flags,forum_flags,project_flags,
					doc_flags,cvs_flags,member_role,release_flags,artifact_flags)
					VALUES ('$user_id','". $this->getID() ."','','0','0','0','1','100','0','0')");

				//verify the insert worked
				if (!$res || db_affected_rows($res) < 1) {
					$this->setError(sprintf(_('ERROR: Could Not Add User To Group: %s'),db_error()));
					db_rollback();
					return false;
				}
				//
				//	check and create if group doesn't exists
				//
//echo "<h2>Group::addUser SYS->sysCheckCreateGroup(".$this->getID().")</h2>";
				if (!$SYS->sysCheckCreateGroup($this->getID())){
					$this->setError($SYS->getErrorMessage());
					db_rollback();
					return false;
				}
				//
				//	check and create if user doesn't exists
				//
//echo "<h2>Group::addUser SYS->sysCheckCreateUser($user_id)</h2>";
				if (!$SYS->sysCheckCreateUser($user_id)) {
					$this->setError($SYS->getErrorMessage());
					db_rollback();
					return false;
				}
				//
				//	Role setup
				//
				$role = new Role($this,$role_id);
				if (!$role || !is_object($role)) {
					$this->setError(_('Error Getting Role Object'));
					db_rollback();
					return false;
				} elseif ($role->isError()) {
					$this->setError('addUser::roleget::'.$role->getErrorMessage());
					db_rollback();
					return false;
				}
//echo "<h2>Group::addUser role->setUser($user_id)</h2>";
				if (!$role->setUser($user_id)) {
					$this->setError('addUser::role::setUser'.$role->getErrorMessage());
					db_rollback();
					return false;
				}
			} else {
				//
				//  user was already a member
				//  make sure they are set up 
				//
				$user=&user_get_object($user_id,$res_newuser);
				$user->fetchData($user->getID());
				$role = new Role($this,$role_id);
				if (!$role || !is_object($role)) {
					$this->setError(_('Error Getting Role Object'));
					db_rollback();
					return false;
				} elseif ($role->isError()) {
					$this->setError('addUser::roleget::'.$role->getErrorMessage());
					db_rollback();
					return false;
				}
//echo "<h2>Already Member Group::addUser role->setUser($user_id)</h2>";
				if (!$role->setUser($user_id)) {
					$this->setError('addUser::role::setUser'.$role->getErrorMessage());
					db_rollback();
					return false;
				}
				//
				//	set up their system info
				//
//echo "<h2>Already Member Group::addUser SYS->sysCheckCreateUser($user_id)</h2>";
				if (!$SYS->sysCheckCreateUser($user_id)) {
					$this->setError($SYS->getErrorMessage());
					db_rollback();
					return false;
				}
				db_commit();
				return true;
			}
		} else {
			//
			//	user doesn't exist
			//
			$this->setError(_('ERROR: User does not exist'));
			db_rollback();
			return false;
		}

		$hook_params['group'] = $this;
		$hook_params['group_id'] = $this->getID();
		$hook_params['user'] = &user_get_object($user_id);
		$hook_params['user_id'] = $user_id;
		plugin_hook ("group_adduser", $hook_params);
		
		//
		//	audit trail
		//
		$this->addHistory('Added User',$user_unix_name);
		db_commit();
		return true;
	}

	/**
	 *  removeUser - controls removing a user from a group.
	 * 
	 *  Users can remove themselves.
	 *
	 *  @param	int		The ID of the user to remove.
	 *	@return	boolean	success.
	 */ 
	function removeUser($user_id) {
		global $SYS,$sys_database_type;

		if ($user_id==user_getid()) {
			//users can remove themselves
			//everyone else must be a project admin
		} else {
			$perm =& $this->getPermission( session_get_user() );

			if (!$perm || !is_object($perm) || !$perm->isAdmin()) {
				$this->setPermissionDeniedError();
				return false;
			}
		}
	
		db_begin();
		$res=db_query("DELETE FROM user_group 
			WHERE group_id='".$this->getID()."' 
			AND user_id='$user_id'");
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError(sprintf(_('ERROR: User not removed: %s'),db_error()));
			db_rollback();
			return false;
		} else {
			//
			//	reassign open artifacts to id=100
			//
			$res=db_query("UPDATE artifact SET assigned_to='100' 
				WHERE group_artifact_id 
				IN (SELECT group_artifact_id 
				FROM artifact_group_list 
				WHERE group_id='".$this->getID()."') 
				AND status_id='1' AND assigned_to='$user_id'");
			if (!$res) {
				$this->setError(sprintf(_('ERROR: DB: artifact: %s'),db_error()));
				db_rollback();
				return false;
			}

			//
			//	reassign open tasks to id=100
			//	first have to purge any assignments that would cause 
			//	conflict with existing assignment to 100
			//
			if ($sys_database_type == 'mysql') {
				$res=db_mquery("
					SELECT pt.project_task_id 
					FROM project_task pt, project_group_list pgl, project_assigned_to pat 
					WHERE pt.group_project_id = pgl.group_project_id 
					AND pat.project_task_id=pt.project_task_id
					AND pt.status_id='1' AND pgl.group_id='".$this->getID()."'
					AND pat.assigned_to_id='$user_id' INTO @task_list;
					DELETE FROM project_assigned_to WHERE project_task_id IN ( @task_list ) AND assigned_to_id='100'");
				if ($res) {
					$res = db_next_result();
				}
			} else {
				$res=db_query("DELETE FROM project_assigned_to
					WHERE project_task_id IN (SELECT pt.project_task_id 
					FROM project_task pt, project_group_list pgl, project_assigned_to pat 
					WHERE pt.group_project_id = pgl.group_project_id 
					AND pat.project_task_id=pt.project_task_id
					AND pt.status_id='1' AND pgl.group_id='".$this->getID()."'
					AND pat.assigned_to_id='$user_id') 
					AND assigned_to_id='100'");
			}
			if (!$res) {
				$this->setError(sprintf(_('ERROR: DB: project_assigned_to %d: %s'),1,db_error()));
				db_rollback();
				return false;
			}
			$res=db_query("UPDATE project_assigned_to SET assigned_to_id='100' 
				WHERE project_task_id IN (SELECT pt.project_task_id 
				FROM project_task pt, project_group_list pgl 
				WHERE pt.group_project_id = pgl.group_project_id 
				AND pt.status_id='1' AND pgl.group_id='".$this->getID()."') 
				AND assigned_to_id='$user_id'");
			if (!$res) {
				$this->setError(sprintf(_('ERROR: DB: project_assigned_to %d: %s'),2,db_error()));
				db_rollback();
				return false;
			}

			//
			//	Remove user from system
			//
//echo "<h2>Group::addUser SYS->sysGroupRemoveUser(".$this->getID().",$user_id)</h2>";
			if (!$SYS->sysGroupRemoveUser($this->getID(),$user_id)) {
				$this->setError($SYS->getErrorMessage());
				db_rollback();
				return false;
			}
			//audit trail
			$this->addHistory('Removed User',$user_id);
		}
		db_commit();
		return true;
	}

	/**	 
	 *  updateUser - controls updating a user's role in this group.
	 *
	 *  @param	int		The ID of the user.
	 *	@param	int		The role_id to set this user to.
	 *	@return	boolean	success.
	 */	 
	function updateUser($user_id,$role_id) {
		global $SYS;

		$perm =& $this->getPermission( session_get_user() );
		if (!$perm || !is_object($perm) || !$perm->isAdmin()) {
			$this->setPermissionDeniedError();
			return false;
		}

		$role = new Role($this,$role_id);
		if (!$role || !is_object($role)) {
			$this->setError(_('Could Not Get Role'));
			return false;
		} elseif ($role->isError()) {
			$this->setError(sprintf(_('Role: %s'),$role->getErrorMessage()));
			return false;
		}
//echo "<h3>Group::updateUser role->setUser($user_id)</h3>";
		if (!$role->setUser($user_id)) {
			$this->setError(sprintf(_('Role: %s'),$role->getErrorMessage()));
			return false;
		}
		
		$hook_params['group'] = $this;
		$hook_params['group_id'] = $this->getID();
		$hook_params['user'] = &user_get_object($user_id);
		$hook_params['user_id'] = $user_id;
		plugin_hook ("group_removeuser", $hook_params);
		
		$this->addHistory('Updated User',$user_id);
		return true;
	}

	/**
	 *	addHistory - Makes an audit trail entry for this project.
	 *
	 *  @param	string	The name of the field.
	 *  @param	string	The Old Value for this $field_name.
	 *	@return database result handle.
	 *	@access public.
	 */
	function addHistory($field_name, $old_value) {
		$sql="
			INSERT INTO group_history(group_id,field_name,old_value,mod_by,adddate) 
			VALUES ('". $this->getID() ."','$field_name','$old_value','". user_getid() ."','".time()."')
		";
		return db_query($sql);
	}		  

	/**
	 *	activateUsers - Make sure that group members have unix accounts.
	 *
	 *	Setup unix accounts for group members. Can be called even
	 *	if members are already active. 
	 *
	 *	@access private.
	 */
	function activateUsers() {

		/*
			Activate member(s) of the project
		*/

		$member_res = db_query("SELECT user_id, role_id
			FROM user_group
			WHERE group_id='".$this->getID()."'");

		$rows = db_numrows($member_res);

		if ($rows > 0) {

			for ($i=0; $i<$rows; $i++) {

				$member =& user_get_object(db_result($member_res,$i,'user_id'));
				$roleId = db_result($member_res,$i,'role_id');

				if (!$member || !is_object($member)) {
					$this->setError(_('Error getting member object'));
					return false;
				} else if ($member->isError()) {
					$this->setError(sprintf(_('Error getting member object: %s'),$member->getErrorMessage()));
					return false;
				}

				if (!$this->addUser($member->getUnixName(),$roleId)) {
					return false;
				}
			}

		 }

		 return true;
	}

	/**
	 *	getMembers - returns array of User objects for this project
	 *
	 *	@return array of User objects for this group.
	 */
	function &getMembers() {
		if (!isset($this->membersArr)) {
			$res=db_query("SELECT users.* FROM users
				INNER JOIN user_group ON users.user_id=user_group.user_id
				WHERE user_group.group_id='".$this->getID()."'");
			while ($arr =& db_fetch_array($res)) {
				$this->membersArr[] =& new GFUser($arr['user_id'],$arr);
			}
		}
		return $this->membersArr;
	}

	/**
	 *	approve - Approve pending project.
	 *
	 *	@param	object	The User object who is doing the updating.
	 *	@access public
	 */
	function approve(&$user) {

		if ($this->getStatus()=='A') {
			$this->setError(_("Group already active"));
			return false;
		}

		db_begin();

		// Step 1: Activate group and create LDAP entries
		if (!$this->setStatus($user, 'A')) {
			db_rollback();
			return false;
		}

		//
		//
		//	Tracker Integration
		//
		//
		$ats = new ArtifactTypes($this);
		if (!$ats || !is_object($ats)) {
			$this->setError(_('Error creating ArtifactTypes object'));
			db_rollback();
			return false;
		} else if ($ats->isError()) {
			$this->setError(sprintf (_('ATS%d: %s'), 1, $ats->getErrorMessage()));
			db_rollback();
			return false;
		}
		if (!$ats->createTrackers()) {
			$this->setError(sprintf (_('ATS%d: %s'), 2, $ats->getErrorMessage()));
			db_rollback();
			return false;
		}

		//
		//
		//	Forum Integration
		//
		//
		$f = new Forum($this);
		if (!$f->create('Open-Discussion','General Discussion',1,'',1,0)) {
			$this->setError(sprintf (_('F%d: %s'), 1, $f->getErrorMessage()));
			db_rollback();
			return false;
		}
		$f = new Forum($this);
		if (!$f->create('Help','Get Public Help',1,'',1,0)) {
			$this->setError(sprintf (_('F%d: %s'), 2, $f->getErrorMessage()));
			db_rollback();
			return false;
		}
		$f = new Forum($this);
		if (!$f->create('Developers','Project Developer Discussion',0,'',1,0)) {
			$this->setError(sprintf (_('F%d: %s'), 3, $f->getErrorMessage()));
			db_rollback();
			return false;
		}

		//
		//
		//	Doc Mgr Integration
		//
		//
		$dg = new DocumentGroup($this);
		if (!$dg->create('Uncategorized Submissions')) {
			$this->setError(sprintf(_('DG: %s'),$dg->getErrorMessage()));
			db_rollback();
			return false;
		}

		//
		//
		//	FRS integration
		//
		//
		$frs = new FRSPackage($this);
		if (!$frs->create($this->getUnixName())) {
			$this->setError(sprintf(_('FRSP: %s'),$frs->getErrorMessage()));
			db_rollback();
			return false;
		}

		//
		//
		//	PM Integration
		//
		//
		$pg = new ProjectGroup($this);
		if (!$pg->create('To Do','Things We Have To Do',1)) {
			$this->setError(sprintf(_('PG%d: %s'),1,$pg->getErrorMessage()));
			db_rollback();
			return false;
		}
		$pg = new ProjectGroup($this);
		if (!$pg->create('Next Release','Items For Our Next Release',1)) {
			$this->setError(sprintf(_('PG%d: %s'),2,$pg->getErrorMessage()));
			db_rollback();
			return false;
		}

		//
		//
		//	Set Default Roles
		//
		//
		$role = new Role($this);
		$todo = array_keys($role->defaults);
		for ($c=0; $c<count($todo); $c++) {
			$role = new Role($this);
			if (!$role->createDefault($todo[$c])) {
				$this->setError(sprintf(_('R%d: %s'),$c,$role->getErrorMessage()));
				db_rollback();
				return false;
			}
		}

		$admin_group = db_query("SELECT user_id FROM user_group 
		WHERE group_id=".$this->getID()." AND admin_flags='A'");
		if (db_numrows($admin_group) > 0) {
			$idadmin_group = db_result($admin_group,0,'user_id');
		} else {
			$idadmin_group = 1;
		}

		//
		//
		//	Create MailingList
		//
		//
		if ($GLOBALS['sys_use_mail']) {
			$mlist = new MailingList($this);
			if (!$mlist->create('commits','Commits',1,$idadmin_group)) {
				$this->setError(sprintf(_('ML: %s'),$mlist->getErrorMessage()));
				db_rollback();
				return false;
			}
		}
		
		db_commit();

		$this->sendApprovalEmail();
		$this->addHistory('Approved', 'x');
		
		//plugin webcal
		//change assistant for webcal
			
		$params[0] = $idadmin_group ;
		$params[1] = $this->getID();
		plugin_hook('change_cal_permission_default',$params);	

		return true;
	}



	/**
	 *	sendApprovalEmail - Send new project email.
	 *
	 *	@return	boolean	success.
	 *	@access public.
	 */
	function sendApprovalEmail() {
		$res_admins = db_query("
			SELECT users.user_name,users.email,users.language,users.user_id
			FROM users,user_group
			WHERE users.user_id=user_group.user_id
			AND user_group.group_id='".$this->getID()."'
			AND user_group.admin_flags='A'
		");

		if (db_numrows($res_admins) < 1) {
			$this->setError(_("Group does not have any administrators."));
			return false;
		}

		// send one email per admin
		while ($row_admins = db_fetch_array($res_admins)) {
			$admin =& user_get_object($row_admins['user_id']);
			setup_gettext_for_user ($admin) ;

			$message=stripcslashes(sprintf(_('Your project registration for %4$s has been approved.

Project Full Name:  %1$s
Project Unix Name:  %2$s

Your DNS will take up to a day to become active on our site.
Your web site is accessible through your shell account. Please read
site documentation (see link below) about intended usage, available
services, and directory layout of the account.

If you visit your
own project page in %4$s while logged in, you will find
additional menu functions to your left labeled \'Project Admin\'.

We highly suggest that you now visit %4$s and create a public
description for your project. This can be done by visiting your project
page while logged in, and selecting \'Project Admin\' from the menus
on the left (or by visiting %3$s
after login).

Your project will also not appear in the Trove Software Map (primary
list of projects hosted on %4$s which offers great flexibility in
browsing and search) until you categorize it in the project administration
screens. So that people can find your project, you should do this now.
Visit your project while logged in, and select \'Project Admin\' from the
menus on the left.

Enjoy the system, and please tell others about %4$s. Let us know
if there is anything we can do to help you.

-- the %4$s crew'), 
						       $this->getPublicName(), 
						       $this->getUnixName(), 
						       util_make_url ('/project/admin/?group_id='.$this->getID()),
						       $GLOBALS['sys_name']));
	
			util_send_message($row_admins['email'], sprintf(_('%1$s Project Approved'), $GLOBALS['sys_name']), $message);

			setup_gettext_from_browser () ;
		}

		return true;
	}


	/**
	 *	sendRejectionEmail - Send project rejection email.
	 *
	 *	This function sends out a rejection message to a user who
	 *	registered a project.
	 *
	 *	@param	int	The id of the response to use.
	 *	@param	string	The rejection message.
	 *	@return completion status.
	 *	@access public.
	 */
	function sendRejectionEmail($response_id, $message="zxcv") {
		$res_admins = db_query("
			SELECT u.email, u.language, u.user_id
			FROM users u, user_group ug
			WHERE ug.group_id='".$this->getID()."'
			AND u.user_id=ug.user_id;
		");

		if (db_numrows($res_admins) < 1) {
			$this->setError(_("Group does not have any administrators."));
			return false;
		}
		
		while ($row_admins = db_fetch_array($res_admins)) {
			$admin =& user_get_object($row_admins['user_id']);
			setup_gettext_for_user ($admin) ;

			$response=stripcslashes(sprintf(_('Your project registration for %3$s has been denied.

Project Full Name:  %1$s
Project Unix Name:  %2$s

Reasons for negative decision:

'), $this->getPublicName(), $this->getUnixName(), $GLOBALS['sys_name']));

			// Check to see if they want to send a custom rejection response
			if ($response_id == 0) {
				$response .= stripcslashes($message);
			} else {
				$response .= db_result(db_query("
				SELECT response_text
				FROM canned_responses
				WHERE response_id='$response_id'
			"), 0, "response_text");
			}

			util_send_message($row_admins['email'], sprintf(_('%1$s Project Denied'), $GLOBALS['sys_name']), $response);
			setup_gettext_from_browser () ;
		}

		return true;
	}

	/**
	 *	sendNewProjectNotificationEmail - Send new project notification email.
	 *
	 *	This function sends out a notification email to the
	 *	SourceForge admin user when a new project is
	 *	submitted.
	 *
	 *	@return	boolean	success.
	 *	@access public.
	 */
	function sendNewProjectNotificationEmail() {
		// Get the user who wants to register the project
		$res = db_query("SELECT u.user_id
				 FROM users u, user_group ug
				 WHERE ug.group_id='".$this->getID()."' AND u.user_id=ug.user_id;");

		if (db_numrows($res) < 1) {
			$this->setError(_("Could not find user who has submitted the project."));
			return false;
		}
		
		$submitter =& user_get_object(db_result($res,0,'user_id'));


		$res = db_query("SELECT users.email, users.language, users.user_id
	 			FROM users,user_group
				WHERE group_id=1 
				AND user_group.admin_flags='A'
				AND users.user_id=user_group.user_id;");
		
		if (db_numrows($res) < 1) {
			$this->setError(_("There is no administrator to send the mail."));
			return false;
		}

		for ($i=0; $i<db_numrows($res) ; $i++) {
			$admin_email = db_result($res,$i,'email') ;
			$admin =& user_get_object(db_result($res,$i,'user_id'));
			setup_gettext_for_user ($admin) ;
			
			$message=stripcslashes(sprintf(_('New %1$s Project Submitted

Project Full Name:  %2$s
Submitted Description: %3$s
License: %4$s
Submitter: %6$s (%7$s)

Please visit the following URL to approve or reject this project:
%5$s'),
						       $GLOBALS['sys_name'],
						       $this->getPublicName(),
						       util_unconvert_htmlspecialchars($this->getRegistrationPurpose()),
						       $this->getLicenseName(), 
						       util_make_url ('/admin/approve-pending.php'),
						       $submitter->getRealName(), 
						       $submitter->getUnixName()));
			util_send_message($admin_email, sprintf(_('New %1$s Project Submitted'), $GLOBALS['sys_name']), $message);
			setup_gettext_from_browser () ;
		}
		

		$email = $submitter->getEmail() ;
		setup_gettext_for_user ($submitter) ;
				
		$message=stripcslashes(sprintf(_('New %1$s Project Submitted

Project Full Name:  %2$s
Submitted Description: %3$s
License: %4$s

The %1$s admin team will now examine your project submission.  You will be notified of their decision.'), $GLOBALS['sys_name'], $this->getPublicName(), util_unconvert_htmlspecialchars($this->getRegistrationPurpose()), $this->getLicenseName(), $GLOBALS['sys_default_domain']));
				
		util_send_message($email, sprintf(_('New %1$s Project Submitted'), $GLOBALS['sys_name']), $message);
		setup_gettext_from_browser () ;
		
		return true;
	}
}

/**
 * group_getname() - get the group name
 *
 * @param	   int	 The group ID
 * @deprecated
 *
 */
function group_getname ($group_id = 0) {
	$grp = &group_get_object($group_id);
	if ($grp) {
		return $grp->getPublicName();
	} else {
		return 'Invalid';
	}
}

/**
 * group_getunixname() - get the unixname for a group
 *
 * @param	   int	 The group ID
 * @deprecated
 *
 */
function group_getunixname ($group_id) {
	$grp = &group_get_object($group_id);
	if ($grp) {
		return $grp->getUnixName();
	} else {
		return 'Invalid';
	}
}

/**
 * group_get_result() - Get the group object result ID.
 *
 * @param	   int	 The group ID
 * @deprecated
 *
 */
function &group_get_result($group_id=0) {
	$grp = &group_get_object($group_id);
	if ($grp) {
		return $grp->getData();
	} else {
		return 0;
	}
}

/**
 *	getUnixStatus - Status of activation of unix account.
 *
 *	@return	char	(N)one, (A)ctive, (S)uspended or (D)eleted
 */
function getUnixStatus() {
	return $this->data_array['unix_status'];
}

/**
 *	setUnixStatus - Sets status of activation of unix account.
 *
 *	@param	string	The unix status.
 *	N	no_unix_account
 *	A	active
 *	S	suspended
 *	D	deleted
 *
 *	@return	boolean success.
 */
function setUnixStatus($status) {
	global $SYS;
	db_begin();
	$res=db_query("
		UPDATE groups 
		SET unix_status='$status' 
		WHERE group_id='". $this->getID()."'
	");

	if (!$res) {
		$this->setError(sprintf(_('ERROR - Could Not Update Group Unix Status: %s'),db_error()));
		db_rollback();
		return false;
	} else {
		if ($status == 'A') {
			if (!$SYS->sysCheckCreateGroup($this->getID())) {
				$this->setError($SYS->getErrorMessage());
				db_rollback();
				return false;
			}
		} else {
			if ($SYS->sysCheckGroup($this->getID())) {
				if (!$SYS->sysRemoveGroup($this->getID())) {
					$this->setError($SYS->getErrorMessage());
					db_rollback();
					return false;
				}
			}
		}
		
		$this->data_array['unix_status']=$status;
		db_commit();
		return true;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
