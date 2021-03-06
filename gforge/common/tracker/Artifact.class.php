<?php
/**
 * FusionForge trackers
 *
 * Copyright 1999-2001, VA Linux Systems, Inc.
 * Copyright 2002-2004, GForge, LLC
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

require_once $gfcommon.'include/Error.class.php';
require_once $gfcommon.'tracker/ArtifactMessage.class.php';
require_once $gfcommon.'tracker/ArtifactExtraField.class.php';

// This string is used when sending the notification mail for identifying the
// user response
define('ARTIFACT_MAIL_MARKER', '#+#+#+#+#+#+#+#+#+#+#+#+#+#+#+#+#+');	

	/**
	*	Factory method which creates an Artifact from an artifact ID
	*	
	*	@param int	The artifact ID
	*	@param array	The result array, if it's passed in
	*	@return	object	Artifact object
	*/
	function &artifact_get_object($artifact_id,$data=false) {
		global $ARTIFACT_OBJ;
		if (!isset($ARTIFACT_OBJ["_".$artifact_id."_"])) {
			if ($data) {
				//the db result handle was passed in
			} else {
				$res=db_query("SELECT * FROM artifact_vw WHERE artifact_id='$artifact_id'");
				if (db_numrows($res) <1 ) {
					$ARTIFACT_OBJ["_".$artifact_id."_"]=false;
					return false;
				}
				$data =& db_fetch_array($res);
			}
			$ArtifactType =& artifactType_get_object($data["group_artifact_id"]);
			$ARTIFACT_OBJ["_".$artifact_id."_"]= new Artifact($ArtifactType,$data);
		}
		return $ARTIFACT_OBJ["_".$artifact_id."_"];
	}	

class Artifact extends Error {

	/**
	 * Resource ID.
	 *
	 * @var		int		$status_res.
	 */
	var $status_res;

	/**
	 * Artifact Type object.
	 *
	 * @var		object	$ArtifactType.
	 */
	var $ArtifactType; 

	/**
	 * Array of artifact data.
	 *
	 * @var		array	$data_array.
	 */
	var $data_array;

	/**
	 * Array of artifact data for extra fields defined by Admin.
	 *
	 * @var		array	$extra_field_data.
	 */
	var $extra_field_data;

	/**
	 * Array of ArtifactFile objects.
	 *
	 * @var		array	$files
	 */
	var $files; 

	/**
	 * Database result set of related tasks
	 *
	 * @var     result $relatedtasks
	 */
	var $relatedtasks;
    
	/**
	 *  Artifact - constructor.
	 *
	 *	@param	object	The ArtifactType object.
	 *  @param	integer	(primary key from database OR complete assoc array) 
	 *		ONLY OPTIONAL WHEN YOU PLAN TO IMMEDIATELY CALL ->create()
	 *  @return	boolean	success.
	 */
	function Artifact(&$ArtifactType, $data=false) {
		$this->Error(); 

		$this->ArtifactType =& $ArtifactType;

		//was ArtifactType legit?
		if (!$ArtifactType || !is_object($ArtifactType)) {
			$this->setError('Artifact: No Valid ArtifactType');
			return false;
		}

		//did ArtifactType have an error?
		if ($ArtifactType->isError()) {
			$this->setError('Artifact: '.$ArtifactType->getErrorMessage());
			return false;
		}

		//
		//	make sure this person has permission to view artifacts
		//
		if (!$this->ArtifactType->userCanView()) {
			$this->setError(_('Artifact: Only group members can view private artifact types'));
			return false;
		}

		//
		//	set up data structures
		//
		if ($data) {
			if (is_array($data)) {
				$this->data_array =& $data;
//
//	Should verify ArtifactType ID
//
				return true;
			} else {
				if (!$this->fetchData($data)) {
					return false;
				} else {
					return true;
				}
			}
		}
	}


	/**
	 *	create - construct a new Artifact in the database.
	 *
	 *	@param	string	The artifact summary.
	 *	@param	string	Details of the artifact.
	 *	@param	int		The ID of the user to which this artifact is to be assigned.
	 *	@param	int		The artifacts priority.
	 *	@param	array	Array of extra fields like: array(15=>'foobar',22=>'1');
	 *  @return id on success / false on failure.
	 */
	function create( $summary, $details, $assigned_to=100, $priority=3, $extra_fields=array()) {
		//
		//	make sure this person has permission to add artifacts
		//
		if (!$this->ArtifactType->isPublic()) {
			//
			//	Only admins can post/modify private artifacts
			//
			if (!$this->ArtifactType->userIsAdmin()) {
				$this->setError(_('Artifact: Only Artifact Admins Can Modify Private ArtifactTypes'));
				return false;
			}
		}

		//
		//	get the user_id
		//
		if (session_loggedin()) {
			$user=user_getid();
		} else {
			if ($this->ArtifactType->allowsAnon()) {
				$user=100;
			} else {
				$this->setError(_('Artifact: This ArtifactType Does Not Allow Anonymous Submissions. Please Login.'));
				return false;
			}
		}

		//
		//	data validation
		//
		if (!$summary) {
			$this->setError(_('Artifact: Message Summary Is Required'));
			return false;
		}
		if (!$details) {
			$this->setError(_('Artifact: Message Body Is Required'));
			return false;
		}
		if (!$assigned_to) {
			$assigned_to=100;
		}
		if (!$priority) {
			$priority=3;
		}
//		if (!$status_id) {
			$status_id=1;		// on creation, status is set to "open"
//		}
		//
		//	They may be using an extra field "status" box so we have to remap
		//	the status_id based on the extra field - this keeps the counters
		//	accurate
		//
		$status_id=$this->ArtifactType->remapStatus($status_id,$extra_fields);
		if (!$status_id) {
			$this->setError(_('Artifact: Error remapping status'));
			return false;
		}

		db_begin();

		$sql="INSERT INTO artifact 
			(group_artifact_id,status_id,priority,
			submitted_by,assigned_to,open_date,summary,details) 
			VALUES 
			('".$this->ArtifactType->getID()."','$status_id','$priority',
			'$user','$assigned_to','". time() ."','". htmlspecialchars($summary)."','". htmlspecialchars($details)."')";
		$res=db_query($sql);
		if (!$res) {
			$this->setError('Artifact: '.db_error());
			db_rollback();
			return false;
		}
		
		$artifact_id=db_insertid($res,'artifact','artifact_id');

		if (!$res || !$artifact_id) {
			$this->setError('Artifact: '.db_error());
			db_rollback();
			return false;
		} else {
			//
			//	Now set up our internal data structures
			//
			if (!$this->fetchData($artifact_id)) {
				db_rollback();
				return false;
			} else {
				// the changes to the extra fields will be logged in this array.
				// (we won't use it however)
				$extra_field_changes = array();
				if (!$this->updateExtraFields($extra_fields,$extra_field_changes)) {
					db_rollback();
					return false;
				}
			}
			//
			//	now send an email if appropriate
			//
			$this->mailFollowup(1);
			db_commit();

			return $artifact_id;
		}
	}
	
	/**
	 *	fetchData - re-fetch the data for this Artifact from the database.
	 *
	 *	@param	int		The artifact ID.
	 *	@return	boolean	success.
	 */
	function fetchData($artifact_id) {
		$res=db_query("SELECT * FROM artifact_vw 
			WHERE artifact_id='$artifact_id' AND group_artifact_id='".$this->ArtifactType->getID()."'");
		if (!$res || db_numrows($res) < 1) {
			$this->setError('Artifact: Invalid ArtifactID');
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 *	getArtifactType - get the ArtifactType Object this Artifact is associated with.
	 *
	 *	@return object	ArtifactType.
	 */
	function &getArtifactType() {
		return $this->ArtifactType;
	}
	
	/**
	 *	getID - get this ArtifactID.
	 *
	 *	@return	int	The artifact_id #.
	 */
	function getID() {
		return $this->data_array['artifact_id'];
	}

	/**
	 *	getStatusID - get open/closed/deleted flag.
	 *
	 *	@return	int	Status: (1) Open, (2) Closed, (3) Deleted.
	 */
	function getStatusID() {
		return $this->data_array['status_id'];
	}

	/**
	 *	getStatusName - get open/closed/deleted text.
	 *
	 *	@return	string	The status name.
	 */
	function getStatusName() {
		return $this->data_array['status_name'];
	}

	/**
	 *	getPriority - get priority flag.
	 *
	 *	@return int priority.
	 */
	function getPriority() {
		return $this->data_array['priority'];
	}

	/**
	 *	getSubmittedBy - get ID of submitter.
	 *
	 *	@return int user_id of submitter.
	 */
	function getSubmittedBy() {
		return $this->data_array['submitted_by'];
	}

	/**
	 *	getSubmittedEmail - get email of submitter.
	 *
	 *	@return	string	The email of submitter.
	 */
	function getSubmittedEmail() {
		return $this->data_array['submitted_email'];
	}

	/**
	 *	getSubmittedRealName - get real name of submitter.
	 *
	 *	@return	string	The real name of submitter.
	 */
	function getSubmittedRealName() {
		return $this->data_array['submitted_realname'];
	}

	/**
	 *	getSubmittedUnixName - get login name of submitter.
	 *
	 *	@return	string	The unix name of submitter.
	 */
	function getSubmittedUnixName() {
		return $this->data_array['submitted_unixname'];
	}

	/**
	 *	getAssignedTo - get ID of assignee.
	 *
	 *	@return int user_id of assignee.
	 */
	function getAssignedTo() {
		return $this->data_array['assigned_to'];
	}

	/**
	 *	getAssignedEmail - get email of assignee.
	 *
	 *	@return	string	The email of assignee.
	 */
	function getAssignedEmail() {
		return $this->data_array['assigned_email'];
	}

	/**
	 *	getAssignedRealName - get real name of assignee.
	 *
	 *	@return	string	The real name of assignee.
	 */
	function getAssignedRealName() {
		return $this->data_array['assigned_realname'];
	}

	/**
	 *	getAssignedUnixName - get login name of assignee.
	 *
	 *	@return	string	The unix name of assignee.
	 */
	function getAssignedUnixName() {
		return $this->data_array['assigned_unixname'];
	}

	/**
	 *	getOpenDate - get unix time of creation.
	 *
	 *	@return int unix time.
	 */
	function getOpenDate() {
		return $this->data_array['open_date'];
	}

	/**
	 *	getCloseDate - get unix time of closure.
	 *
	 *	@return int unix time.
	 */
	function getCloseDate() {
		return $this->data_array['close_date'];
	}

	/**
	 *	  getLastModifiedDate - the last_modified_date of this task.
	 *
	 *	  @return int	 the last_modified_date.
	 */
	function getLastModifiedDate() {
		return $this->data_array['last_modified_date'];
	}

	/**
	 *	getSummary - get text summary of artifact.
	 *
	 *	@return	string The summary (subject).
	 */
	function getSummary() {
		return $this->data_array['summary'];
	}

	/**
	 *	getDetails - get text body (message) of artifact.
	 *
	 *	@return	string	The body (message).
	 */
	function getDetails() {
		return $this->data_array['details'];
	}

	/**
	 *  delete - delete this tracker and all its related data.
	 *
	 *  @param  bool	I'm Sure.
	 *  @return bool true/false;
	 */
	function delete($sure) {
		if (!$sure) {
			$this->setMissingParamsError();
			return false;
		}
		if (!$this->ArtifactType->userIsAdmin()) {
			$this->setPermissionDeniedError();
			return false;
		}
		db_begin();
		$res = db_query("DELETE FROM artifact_extra_field_data WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting extra field data: '.db_error());
			db_rollback();
			return false;
		}
		$res = db_query("DELETE FROM artifact_file WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting file from db: '.db_error());
			db_rollback();
			return false;
		}
		$res = db_query("DELETE FROM artifact_message WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting message: '.db_error());
			db_rollback();
			return false;
		}
		$res = db_query("DELETE FROM artifact_history WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting history: '.db_error());
			db_rollback();
			return false;
		}
		$res = db_query("DELETE FROM artifact_monitor WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting monitor: '.db_error());
			db_rollback();
			return false;
		}
		$res = db_query("DELETE FROM artifact WHERE artifact_id='".$this->getID()."'");
		if (!$res) {
			$this->setError('Error deleting artifact: '.db_error());
			db_rollback();
			return false;
		}
		
		if ($this->getStatusID() == 1) {
			$res = db_query("UPDATE artifact_counts_agg SET count=count-1,open_count=open_count-1
				WHERE group_artifact_id='".$this->getID()."'");
			if (!$res) {
				$this->setError('Error updating artifact_counts_agg (1): '.db_error());
				db_rollback();
				return false;
			}
		} elseif ($this->getStatusID() == 2) {
			$res = db_query("UPDATE artifact_counts_agg SET count=count-1
				WHERE group_artifact_id='".$this->getID()."'");
			if (!$res) {
				$this->setError('Error updating artifact_counts_agg (2): '.db_error());
				db_rollback();
				return false;
			}
		}

		db_commit();
		return true;
	}

	/**
	 *  setMonitor - user can monitor this artifact.
	 *
	 *  @return false - always false - always use the getErrorMessage() for feedback
	 */
	function setMonitor() {
		if (session_loggedin()) {

			$user_id=user_getid();
			$user =& user_get_object(user_getid());
			$email=' ';

			//we don't want to include the "And email=" because
			//a logged-in user's email may have changed
			$email_sql='';

		} else {

			$this->setError(_('SetMonitor::Valid Email Address Required'));
			return false;

		}

		$res=db_query("SELECT * FROM artifact_monitor 
			WHERE artifact_id='". $this->getID() ."' 
			AND user_id='$user_id'");

		if (!$res || db_numrows($res) < 1) {
			//not yet monitoring
			$res=db_query("INSERT INTO artifact_monitor (artifact_id,user_id) 
				VALUES ('". $this->getID() ."','$user_id')");
			if (!$res) {
				$this->setError(db_error());
				return false;
			} else {
				$this->setError(_('Now Monitoring Artifact'));
				return false;
			}
		} else {
			//already monitoring - remove their monitor
			db_query("DELETE FROM artifact_monitor 
				WHERE artifact_id='". $this->getID() ."' 
				AND user_id='$user_id'");
			$this->setError(_('Artifact Monitoring Deactivated'));
			return false;
		}
	}

	function isMonitoring() {
		if (!session_loggedin()) {
			return false;
		}
		$sql="SELECT count(*) AS count FROM artifact_monitor WHERE user_id='".user_getid()."' AND artifact_id='".$this->getID()."';";
		$result = db_query($sql);
		$row_count = db_fetch_array($result);
		return $result && $row_count['count'] > 0;
	}

	/**
	 *  getMonitorIds - array of email addresses monitoring this Artifact.
	 *
	 *  @return array of email addresses monitoring this Artifact.
	 */
	function getMonitorIds() {
		$res=db_query("SELECT user_id
			FROM artifact_monitor 
			WHERE artifact_id='". $this->getID() ."'");
		return array_unique(array_merge($this->ArtifactType->getMonitorIds(),util_result_column_to_array($res)));
	}

	/**
	 *	getHistory - returns a result set of audit trail for this support request.
	 *
	 *	@return database result set.
	 */
	function getHistory() {
		$sql="SELECT * ".
		"FROM artifact_history_user_vw ".
		"WHERE artifact_id='". $this->getID() ."' ".
		"ORDER BY entrydate DESC";
		return db_query($sql);
	}

	/**
	 *	getMessages - get the list of messages attached to this artifact.
	 *
	 *	@return database result set.
	 */
	function getMessages() {
		$sql="select * ".
			"FROM artifact_message_user_vw ".
			"WHERE artifact_id='". $this->getID() ."' ORDER BY adddate DESC";
		return db_query($sql);
	}

	/**
	 *	getMessageObjects - get an array of message objects.
	 *
	 *	@return array Of ArtifactMessage objects.
	 */
	function &getMessageObjects() {
		$res=$this->getMessages();
		$return = array();
		while ($arr = db_fetch_array($res)) {
			//$return[]=new ArtifactMessage($arr['artifact_id'],$arr);
			$return[] = new ArtifactMessage($this, $arr);
		}
		return $return;
	}

	/**
	 *	getFiles - get array of ArtifactFile's.
	 *
	 *	@return array of ArtifactFile's.
	 */
	function &getFiles() {
		if (!isset($this->files)) {
			$sql="select * ".
			"FROM artifact_file_user_vw ".
			"WHERE artifact_id='". $this->getID() ."'";
			$res=db_query($sql);
			$rows=db_numrows($res);
			if ($rows > 0) {
				for ($i=0; $i < $rows; $i++) {
					$this->files[$i]=new ArtifactFile($this,db_fetch_array($res));
				}
			} else {
				$this->files=array();
			}
		}
		return $this->files;
	}

	/**
	 * getRelatedTasks - get array of related tasks
	 *
	 * @return Database result set
	 */
	function getRelatedTasks() {
		if (!$this->relatedtasks) {
			$this->relatedtasks=
			db_query("SELECT pt.group_project_id,pt.project_task_id,pt.summary,pt.start_date,pt.end_date,pgl.group_id
			FROM project_task pt, project_group_list pgl
			WHERE pt.group_project_id = pgl.group_project_id AND
			EXISTS (SELECT project_task_id FROM project_task_artifact
				WHERE project_task_id=pt.project_task_id
				AND artifact_id = ". $this->getID() . ")");
		}
		return $this->relatedtasks;
	}

	/**
	 *  addMessage - attach a text message to this Artifact.
	 *
	 *	@param	string	The message being attached.
	 *	@param	string	Email address of message creator.
	 *	@param	bool	Whether to email out a followup.
	 *	@access private.
	 *  @return	boolean	success.
	 */
	function addMessage($body,$by=false,$send_followup=false) {
		if (!$body) {
			$this->setMissingParamsError();
			return false;
		}
		if (session_loggedin()) {
			$user_id=user_getid();
			$user =& user_get_object($user_id);
			if (!$user || !is_object($user)) {
				$this->setError('ERROR - Logged In User Bug Could Not Get User Object');
				return false;
			}
			//	we'll store this email even though it will likely never be used - 
			//	since we have their correct user_id, we can join the USERS table to get email
			$by=$user->getEmail();
		} elseif (!$this->ArtifactType->allowsAnon()) {
			$this->setError(_('Artifact: This ArtifactType Does Not Allow Anonymous Submissions. Please Login.'));
			return false;
		} else {
			$user_id=100;
			if (!$by || !validate_email($by)) {
				$this->setMissingParamsError();
				return false;
			}
		}

		$sql="insert into artifact_message (artifact_id,submitted_by,from_email,adddate,body) ".
			"VALUES ('". $this->getID() ."','$user_id','$by','". time() ."','". htmlspecialchars($body). "')";
		$res = db_query($sql);
		if ($send_followup) {
			$this->mailFollowup(2,false);
		}
		return $res;
	}

	/**
	 *  addHistory - add an entry to audit trail.
	 *
	 *  @param	string	The name of the field in the database being modified.
	 *  @param	string	The former value of this field.
	 *  @access private.
	 *  @return	boolean	success.
	 */
	function addHistory($field_name,$old_value) {
		if (!session_loggedin()) {
			$user=100;
		} else {
			$user=user_getid();
		}
		$sql="insert into artifact_history(artifact_id,field_name,old_value,mod_by,entrydate) 
			VALUES ('". $this->getID() ."','$field_name','".addslashes($old_value)."','$user','". time() ."')";
		return db_query($sql);
	}

	/**
	 *	update - update the fields in this artifact.
	 *
	 *	@param	int		The artifact priority.
	 *	@param	int		The artifact status ID.
	 *	@param	int		The person to which this artifact is to be assigned.
	 *	@param	string	The artifact summary.
	 *	@param	int		The canned response.
	 *	@param	string	Attaching another comment.
	 *	@param	int		Allows you to move an artifact to another type.
	 *	@param	array	Array of extra fields like: array(15=>'foobar',22=>'1');
	 *	@return	boolean	success.
	 */
	function update($priority,$status_id,
		$assigned_to,$summary,$canned_response,$details,$new_artifact_type_id,$extra_fields=array()) {

		/*
			Field-level permission checking
		*/
		if ($this->ArtifactType->userIsAdmin()) {
			//admin can do everything
		} else {
			//everyone else cannot modify these fields
			$priority=$this->getPriority();
			$summary=addslashes($this->getSummary());
			$canned_response=100;
			$new_artifact_type_id=$this->ArtifactType->getID();
			$assigned_to=$this->getAssignedTo();

			if ($this->ArtifactType->userIsTechnician()) {
				//technician can update only certain fields
				//which were not overridden above
			} else {
				//submitter can no longer call this function
				$this->setPermissionDeniedError();
				return false;
			}

		}
		//
		//	They may be using an extra field "status" box so we have to remap
		//	the status_id based on the extra field - this keeps the counters
		//	accurate
		//
		if (count($extra_fields) > 0) {
			$status_id=$this->ArtifactType->remapStatus($status_id,$extra_fields);
		}
		if (!$this->getID() 
			|| !$assigned_to 
			|| !$status_id 
			|| !$canned_response 
			|| !$new_artifact_type_id) {
			$this->setMissingParamsError();
			return false;
		}


		// Array to record which properties were changed
		$changes = array();
		$update  = false;
		
		db_begin();

		//
		//	Get a lock on this row in the database
		//
		$lock=db_query("SELECT * FROM artifact WHERE artifact_id='".$this->getID()."' FOR UPDATE");
		$artifact_type_id = $this->ArtifactType->getID();
		//
		//	Attempt to move this Artifact to a new ArtifactType
		//	need to instantiate new ArtifactType obj and test perms
		//
		if ($new_artifact_type_id != $artifact_type_id) {
			$newArtifactType= new ArtifactType($this->ArtifactType->getGroup(), $new_artifact_type_id);
			if (!is_object($newArtifactType) || $newArtifactType->isError()) {
				$this->setError('Artifact: Could not move to new ArtifactType'. $newArtifactType->getErrorMessage());
				db_rollback();
				return false;
			}
			//	do they have perms for new ArtifactType?
			if (!$newArtifactType->userIsAdmin()) {
				$this->setPermissionDeniedError();
				db_rollback();
				return false;
			}
			
			//
			//	Now set Assigned to 100 in the new ArtifactType
			//
			$status_id=1;
			$assigned_to='100';
			//can't send a canned response when changing ArtifactType
			$canned_response=100;
			$this->ArtifactType =& $newArtifactType;
			$update = true;

			//
			//	This is a major problem - the extra fields
			//	are completely different IDs, and may not even
			//	exist in the new tracker. All extra_fields will be deleted and 
			//	then set to 100 in the new tracker.
			//
			$res=db_query("DELETE FROM artifact_extra_field_data WHERE artifact_id='".$this->getID()."'");
			$extra_fields=array();
		}
		
		

		$sqlu='';

		//
		//	handle audit trail & build SQL statement
		//
		if ($this->getStatusID() != $status_id) {
			$this->addHistory('status_id',$this->getStatusID());
			$sqlu .= " status_id='$status_id', ";
			$changes['status'] = 1;
			$update = true;

			// Reset the close_date if bug is re-opened 
			// (otherwise stat reports will be wrong).
			if ($status_id == 1) {
				$sqlu .= " close_date='0', ";
				$this->addHistory('close_date',0);
			}
		}
		if ($this->getPriority() != $priority) {
			$this->addHistory('priority',$this->getPriority());
			$sqlu .= " priority='$priority', ";
			$changes['priority'] = 1;
			$update = true;
		}

		if ($this->getAssignedTo() != $assigned_to) {
			$this->addHistory('assigned_to',$this->getAssignedTo());
			$sqlu .= " assigned_to='$assigned_to', ";
			$changes['assigned_to'] = 1;
			$update = true;
		}
		if ($summary && (addslashes($this->getSummary()) != htmlspecialchars($summary))) {
			$this->addHistory('summary', addslashes($this->getSummary()));
			$sqlu .= " summary='". htmlspecialchars($summary) ."', ";
			$changes['summary'] = 1;
			$update = true;
		}

		if ($details) {
			$this->addMessage($details,'',0);
			$changes['details'] = 1;
			$send_message=true;
		}

		//
		//	Enter the timestamp if we are changing to closed
		//
		if ($status_id != 1) {
			$now=time();
			$sqlu .= " close_date='$now', ";
			$this->addHistory('close_date',$now);
			$update = true;
		}

		/*
			Finally, update the artifact itself
		*/
		if ($update){
			$sql = "UPDATE artifact 
				SET 
				$sqlu
				group_artifact_id='$new_artifact_type_id'
				WHERE 
				artifact_id='". $this->getID() ."'
				AND group_artifact_id='$artifact_type_id'";
			$result=db_query($sql);

			if (!$result || db_affected_rows($result) < 1) {
				$this->setError('Error - update failed!'.db_error());
				db_rollback();
				return false;
			} else {
				if (!$this->fetchData($this->getID())) {
					db_rollback();
					return false;
				}
			}
		}

		//extra field handling
		$update=true;
		if (!$this->updateExtraFields($extra_fields,$changes)) {
//TODO - see if anything actually did change
			db_rollback();
			return false;
		}
		
		/*
			handle canned responses

			Instantiate ArtifactCanned and get the body of the message
		*/
		if ($canned_response != 100) {
			//don't care if this response is for this group - could be hacked
			$acr=new ArtifactCanned($this->ArtifactType,$canned_response);
			if (!$acr || !is_object($acr)) {
				$this->setError('Artifact: Could Not Create Canned Response Object');
			} elseif ($acr->isError()) {
				$this->setError('Artifact: '.$acr->getErrorMessage());
			} else {
				$body = addslashes($acr->getBody());
				if ($body) {
					if (!$this->addMessage(util_unconvert_htmlspecialchars($body),'',0)) {
						db_rollback();
						return false;
					} else {
						$send_message=true;
					}
				} else {
					$this->setError('Artifact: Unable to Use Canned Response');
					return false;
				}
			}
		}

		if ($update || $send_message){
			/*
				now send the email
			*/
			$this->mailFollowup(2, false, $changes);
			db_commit();
			return true;
		} else {
			//nothing changed, so cancel the transaction
			$this->setError(_('Nothing Changed - Update Cancelled'));
			db_rollback();
			return false;
		}
	
	}

	/**
	 * 	updateExtraFields - updates the extra data elements for this artifact
	 *	e.g. the extra fields created and defined by the admin.
	 *
	 *	@param	array	Array of extra fields like: array(15=>'foobar',22=>'1');
	 *	@param	array	Array where changes to the extra fields should be logged
	 *	@return true on success / false on failure
	 */
	function updateExtraFields($extra_fields,&$changes){
/*
	This is extremely complex code - we have take the passed array
	and see if we need to insert it into the db, and may have to 
	add history rows for the audit trail

	start by getting all the available extra fields from ArtifactType
		For each field from ArtifacType, check the passed array - 
			This prevents someone from passing bogus extra field entries - they will be ignored
			if the passed entry is blank, may have to force a default value
			if the passed array is different from the existing data in db, 
				delete old entry and insert new entries, along with possible audit trail
			else
				skip it and continue to next item

*/
		if (empty($extra_fields)) {
			return true;
		}
		//get a list of extra fields for this artifact_type
		$ef = $this->ArtifactType->getExtraFields();
		$efk=array_keys($ef);

		//now we'll update this artifact for each extra field
		for ($i=0; $i<count($efk); $i++) {
			$efid=$efk[$i];
			$type=$ef[$efid]['field_type'];

//
//	Force each field to have some value if it is a numeric field
//	text fields will just be purged and skipped
//
			if (!array_key_exists($efid, $extra_fields) || !$extra_fields[$efid]) {
				if ($type == ARTIFACT_EXTRAFIELDTYPE_STATUS) {
					$this->setError('Status Custom Field Must Be Set');
					return false;
				} elseif (($type == ARTIFACT_EXTRAFIELDTYPE_SELECT) || ($type == ARTIFACT_EXTRAFIELDTYPE_RADIO)) {
					$extra_fields[$efid]='100';
				} elseif (($type == ARTIFACT_EXTRAFIELDTYPE_MULTISELECT) || ($type == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX)) {
					$extra_fields[$efid]=array('100');
				} else {
					$resdel=db_query("DELETE FROM artifact_extra_field_data
					WHERE
					artifact_id='".$this->getID()."'
					AND extra_field_id='".$efid."'");
					continue;
				}
			}
			//
			//	get the old rows of data
			//
			$resd=db_query("SELECT * FROM artifact_extra_field_data
				WHERE
				artifact_id='".$this->getID()."'
				AND extra_field_id='".$efid."'");
			$rows=db_numrows($resd);
			if ($resd && $rows) {
//
//POTENTIAL PROBLEM - no entry was there before, but adding one now - may need history
//
				//
				//	Compare for history purposes
				//
				
				// these types have arrays associated to them, so they need
				// special handling to check for differences
				if ($type == ARTIFACT_EXTRAFIELDTYPE_MULTISELECT || $type == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX) {
					// check the differences between the old values and the new values
					$old_values = util_result_column_to_array($resd,"field_data");
					
					$added_values = array_diff($extra_fields[$efid], $old_values);
					$deleted_values = array_diff($old_values, $extra_fields[$efid]);
					
					if (!empty($added_values) || !empty($deleted_values))	{	// there are differences...
						$field_name = $ef[$efid]['field_name'];
						$changes["extra_fields"][$efid] = 1;
						
						// Do a history entry only for deleted values
						if (!empty($deleted_values)) {
							$this->addHistory($field_name, $this->ArtifactType->getElementName($deleted_values));
						}
						

						$resdel=db_query("DELETE FROM artifact_extra_field_data
						WHERE
						artifact_id='".$this->getID()."'
						AND extra_field_id='".$efid."'");
					} else {
						continue;
					}
				} elseif (addslashes(db_result($resd,0,'field_data')) == htmlspecialchars($extra_fields[$efid])) {
					//element did not change
					continue;
				} else {
					//element DID change - do a history entry
					$field_name = $ef[$efid]['field_name'];
					$changes["extra_fields"][$efid] = 1;
					$resdel=db_query("DELETE FROM artifact_extra_field_data
					WHERE
					artifact_id='".$this->getID()."'
					AND extra_field_id='".$efid."'");
					if (($type == ARTIFACT_EXTRAFIELDTYPE_SELECT) || ($type == ARTIFACT_EXTRAFIELDTYPE_RADIO) || ($type == ARTIFACT_EXTRAFIELDTYPE_STATUS)) {
//don't add history for text fields
						$this->addHistory($field_name,$this->ArtifactType->getElementName(db_result($resd,0,'field_data')));
					}
				}
			} else {

//no history for this extra field exists

			}
			//
			//	See if anything was even passed for this extra_field_id
			//
			if (!$extra_fields[$efid]) {
				//nothing in field to update - text fields may be blank
			} else {
				//determine the type of field and whether it should have multiple rows supporting it
				$type=$ef[$efid]['field_type'];
				if (($type == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX) || ($type==ARTIFACT_EXTRAFIELDTYPE_MULTISELECT)) {
					$multi_rows=true;
					$count=count($extra_fields[$efid]);
					for ($fin=0; $fin<$count; $fin++) {
						$sql="INSERT INTO artifact_extra_field_data (artifact_id,extra_field_id,field_data) 
							values ('".$this->getID()."','".$efid."',
							'".$extra_fields[$efid][$fin]."')";
						$res=db_query($sql);
						if (!$res) {
							$this->setError('Artifact::updateExtraFields:: '.$sql.db_error());
							return false;
						}
					}
				} else {
					$multi_rows=false;
					$count=1;
					$res=db_query("INSERT INTO artifact_extra_field_data (artifact_id,extra_field_id,field_data) 
						values ('".$this->getID()."','".$efid."',
						'".htmlspecialchars($extra_fields[$efid])."')");
					if (!$res) {
						$this->setError('Artifact::updateExtraFields:: '.db_error());
						return false;
					}
				}
			}
		}
		unset($this->extra_field_data);
		return true;
	}

	/**
	 *	getExtraFieldData - get an array of data for the extra fields associated with this artifact
	 *
	 *	@return	array	array of data
	 */
	function &getExtraFieldData() {
		if (!isset($this->extra_field_data)) {
			$this->extra_field_data = array();
			$res=db_query("SELECT * FROM artifact_extra_field_data 
				WHERE artifact_id='".$this->getID()."' ORDER BY extra_field_id");
			$ef = $this->ArtifactType->getExtraFields();
			while ($arr = db_fetch_array($res)) {
				$type=$ef[$arr['extra_field_id']]['field_type'];
				if (($type == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX) || ($type==ARTIFACT_EXTRAFIELDTYPE_MULTISELECT)) {
					//accumulate a sub-array of values in cases where you may have multiple rows
					if (!is_array($this->extra_field_data[$arr['extra_field_id']])) {
						$this->extra_field_data[$arr['extra_field_id']] = array();
					}
					$this->extra_field_data[$arr['extra_field_id']][]=$arr['field_data'];
				} else {
					$this->extra_field_data[$arr['extra_field_id']]=$arr['field_data'];
				}
			}
		}
		return $this->extra_field_data;
	}

	/**
	 *	marker - adds the > symbol to fields that have been modified for the email message
	 *
	 *
	 */
	function marker($prop_name,$changes,$extra_field_id=0) {
		if ($prop_name == 'extra_fields' && isset($changes[$prop_name][$extra_field_id])) {
			return '>';
		} else if ($prop_name != 'extra_fields' && isset($changes[$prop_name])) {
			return '>';
		} else {
			return '';
		}
	}				

	/**
	 *	mailFollowup - send out an email update for this artifact.
	 *
	 *	@param	int		(1) initial/creation (2) update.
	 *	@param	array	Array of additional addresses to mail to.
	 *	@param	array	Array of fields changed in this update .
	 *	@access private.
	 *	@return	boolean	success.
	 */
	function mailFollowup($type, $more_addresses=false, $changes='') {
		if (!$changes) {
			$changes=array();
		}
		
		$sess = session_get_user() ;
		if ($type == 1) { // Initial opening
			if ($sess) {
				$body = $this->ArtifactType->getName() ." item #". $this->getID() .", was opened at ". date( _('Y-m-d H:i'), $this->getOpenDate() ) . " by " . $sess->getRealName () ;
			} else {
				$body = $this->ArtifactType->getName() ." item #". $this->getID() .", was opened at ". date( _('Y-m-d H:i'), $this->getOpenDate() ) ;
			}
		} else {
			if ($sess) {
				$body = $this->ArtifactType->getName() ." item #". $this->getID() .", was changed at ". date( _('Y-m-d H:i'), $this->getOpenDate() ) . " by " . $sess->getRealName ();
			} else {
				$body = $this->ArtifactType->getName() ." item #". $this->getID() .", was changed at ". date( _('Y-m-d H:i'), $this->getOpenDate() ) ;
			}
		}
			      

		$body .= "\nYou can respond by visiting: ".
			"\n".util_make_url ('/tracker/?func=detail&atid='. $this->ArtifactType->getID() .
					    "&aid=". $this->getID() .
					    "&group_id=". $this->ArtifactType->Group->getID()) .
			"\nOr by replying to this e-mail entering your response between the following markers: ".
			"\n".ARTIFACT_MAIL_MARKER.
			"\n(enter your response here)".
			"\n".ARTIFACT_MAIL_MARKER.
			"\n\n".
			$this->marker('status',$changes).
			 "Status: ". $this->getStatusName() ."\n".
			$this->marker('priority',$changes).
			 "Priority: ". $this->getPriority() ."\n".
			"Submitted By: ". $this->getSubmittedRealName() .
			" (". $this->getSubmittedUnixName(). ")"."\n".
			$this->marker('assigned_to',$changes).
			 "Assigned to: ". $this->getAssignedRealName() .
			 " (". $this->getAssignedUnixName(). ")"."\n".
			$this->marker('summary',$changes).
			 "Summary: ". util_unconvert_htmlspecialchars( $this->getSummary() )." \n";
			 
		// Now display the extra fields
		$efd = $this->getExtraFieldDataText();
		foreach ($efd as $efid => $ef) {
			$body .= $this->marker('extra_fields', $changes, $efid);
			$body .= $ef["name"].": ".$ef["value"]."\n";
		}
			
		$subject='['. $this->ArtifactType->Group->getUnixName() . '-' . $this->ArtifactType->getName() . '][' . $this->getID() .'] '. util_unconvert_htmlspecialchars( $this->getSummary() );

		if ($type > 1) {
			// get all the email addresses that are monitoring this request or the ArtifactType
			$monitor_ids =& $this->getMonitorIds();
		} else {
			// initial creation, we just get the users monitoring the ArtifactType
			$monitor_ids =& $this->ArtifactType->getMonitorIds();
		}

		$emails = array();
		if ($more_addresses) {
			$emails[] = $more_addresses;
		}
		//we don't email the current user
		if ($this->getAssignedTo() != user_getid()) {
			$monitor_ids[] = $this->getAssignedTo();
		}
		if ($this->getSubmittedBy() != user_getid()) {
			$monitor_ids[] = $this->getSubmittedBy();
		}
		//initial submission
		if ($type==1) {
			//if an email is set for this ArtifactType
			//add that address to the BCC: list
			if ($this->ArtifactType->getEmailAddress()) {
				$emails[] = $this->ArtifactType->getEmailAddress();
			}
		} else {
			//update
			if ($this->ArtifactType->emailAll()) {
				$emails[] = $this->ArtifactType->getEmailAddress();
			}
		}

		$body .= "\n\nInitial Comment:".
			"\n".util_unconvert_htmlspecialchars( $this->getDetails() ) .
			"\n\n----------------------------------------------------------------------";

		if ($type > 1) {
			/*
				Now include the followups
			*/
			$result2=$this->getMessages();

			$rows=db_numrows($result2);
		
			if ($result2 && $rows > 0) {
				for ($i=0; $i<$rows; $i++) {
					//
					//	for messages posted by non-logged-in users, 
					//	we grab the email they gave us
					//
					//	otherwise we use the confirmed one from the users table
					//
					if (db_result($result2,$i,'user_id') == 100) {
						$emails[] = db_result($result2,$i,'from_email');
					} else {
						$monitor_ids[] = db_result($result2,$i,'user_id');
					}


					$body .= "\n\n";
					if ($i == 0) {
						$body .= $this->marker('details',$changes);
					}
					$body .= "Comment By: ". db_result($result2,$i,'realname') . " (".db_result($result2,$i,'user_name').")".
					"\nDate: ". date( _('Y-m-d H:i'),db_result($result2,$i,'adddate') ).
					"\n\nMessage:".
					"\n".util_unconvert_htmlspecialchars( db_result($result2,$i,'body') ).
					"\n\n----------------------------------------------------------------------";
				}	   
			}

		}

		$body .= "\n\nYou can respond by visiting: ".
			"\n".util_make_url ('/tracker/?func=detail&atid='. $this->ArtifactType->getID() .
					    "&aid=". $this->getID() .
					    "&group_id=". $this->ArtifactType->Group->getID());

		//only send if some recipients were found
		if (count($emails) < 1 && count($monitor_ids) < 1) {
			return true;
		}

		if (count($monitor_ids) < 1) {
			$monitor_ids=array();
		} else {
			$monitor_ids=array_unique($monitor_ids);
		}
		
		$from = $this->ArtifactType->getReturnEmailAddress();
		$extra_headers = 'Reply-to: '.$from;
		
		// load the e-mail addresses of the users
		$users =& user_get_objects($monitor_ids);
		if (count($users) > 0) {
			foreach ($users as $user) {
				if ($user->getStatus() == "A") { //we are only sending emails to active users
					$emails[] = $user->getEmail();
				}
			}
		}
		
//		print($body);

		//now remove all duplicates from the email list
		if (count($emails) > 0) {
			$BCC=implode(',',array_unique($emails));
			util_send_message('',$subject,$body,$from,$BCC,'',$extra_headers);			
		}
		
		
		//util_handle_message($monitor_ids,$subject,$body,$BCC);
		
		return true;
	}
	
	/**
	* getExtraFieldDataText - Return the extra fields' data in a human-readable form.
	*
	* @return array Array containing field ID => field name and value associated to it for
	*	this artifact
	*/
	function getExtraFieldDataText() {
		// First we get the list of extra fields and the data
		// associated to the fields
		$efs = $this->ArtifactType->getExtraFields();
		$efd = $this->getExtraFieldData();
		
		$return = array();

		foreach ($efs as $efid => $ef) {
			$name = $ef["field_name"];
			
			// Get the value according to the type
			switch ($ef["field_type"]) {
				
			// for these types, the associated value comes straight
			case ARTIFACT_EXTRAFIELDTYPE_TEXT:
			case ARTIFACT_EXTRAFIELDTYPE_TEXTAREA:
				$value = isset($efd[$efid]) ? $efd[$efid]: '';
				break;

			// the other types have and ID or an array of IDs associated to them
			default:
				$value = $this->ArtifactType->getElementName($efd[$efid]);
			}
			
			$return[$efid] = array("name" => $name, "value" => $value);
		}
		
		return $return;
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
