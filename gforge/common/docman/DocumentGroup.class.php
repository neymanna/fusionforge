<?php
/**
 * FusionForge document manager
 *
 * Copyright 2000, Quentin Cregan/Sourceforge
 * Copyright 2002, Tim Perdue/GForge, LLC
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

class DocumentGroup extends Error {

	/**
	 * The Group object.
	 *
	 * @var		object	$Group.
	 */
	var $Group; //object

	/**
	 * Array of data.
	 *
	 * @var		array	$data_array.
	 */
	var $data_array;

	/**
	 *  DocumentGroup - constructor.
	 *
	 *  Use this constructor if you are modifying an existing doc_group.
	 *
	 *	@param	object	Group object.
	 *  @param	array	(all fields from doc_groups) OR doc_group from database.
	 *  @return boolean	success.
	 */
	function DocumentGroup(&$Group, $data=false) {
		$this->Error();

		//was Group legit?
		if (!$Group || !is_object($Group)) {
			$this->setError('DocumentGroup: No Valid Group');
			return false;
		}
		//did Group have an error?
		if ($Group->isError()) {
			$this->setError('DocumentGroup: '.$Group->getErrorMessage());
			return false;
		}
		$this->Group =& $Group;

		if ($data) {
			if (is_array($data)) {
				$this->data_array =& $data;
//
//	should verify group_id
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
	 *	create - create a new item in the database.
	 *
	 *	@param	string	Item name.
	 *  @return id on success / false on failure.
	 */
	function create($name,$parent_doc_group=0) {
		//
		//	data validation
		//
		if (!$name) {
			$this->setError(_('DocumentGroup: name is Required'));
			return false;
		}
		
		if ($parent_doc_group) {
			// check if parent group exists
			$res=db_query("SELECT * FROM doc_groups WHERE doc_group='$parent_doc_group' AND group_id=".$this->Group->getID());
			if (!$res || db_numrows($res) < 1) {
				$this->setError(_('DocumentGroup: Invalid DocumentGroup parent ID'));
				return false;
			}
		} else {
			$parent_doc_group = 0;
		}

		$perm =& $this->Group->getPermission (session_get_user());
		if (!$perm || !$perm->isDocEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
		
		$sql="INSERT INTO doc_groups (group_id,groupname,parent_doc_group)
			VALUES ('".$this->Group->getID()."','".htmlspecialchars($name)."','".$parent_doc_group."')";
		$result=db_query($sql);
		if ($result && db_affected_rows($result) > 0) {
			$this->clearError();
		} else {
			$this->setError('DocumentGroup::create() Error Adding Group: '.db_error());
			return false;
		}

		$doc_group = db_insertid($result, 'doc_groups', 'doc_group');

		//	Now set up our internal data structures
		if (!$this->fetchData($doc_group)) {
			return false;
		}

		return true;
	}


	/**
	 *	fetchData - re-fetch the data for this DocumentGroup from the database.
	 *
	 *	@param	int		ID of the doc_group.
	 *	@return boolean.
	 */
	function fetchData($id) {
		$res=db_query("SELECT * FROM doc_groups WHERE doc_group='$id'");
		if (!$res || db_numrows($res) < 1) {
			$this->setError(_('DocumentGroup: Invalid DocumentGroup ID'));
			return false;
		}
		$this->data_array =& db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 *	getGroup - get the Group Object this DocumentGroup is associated with.
	 *
	 *	@return Object Group.
	 */
	function &getGroup() {
		return $this->Group;
	}

	/**
	 *	getID - get this DocumentGroup's ID.
	 *
	 *	@return	int	The id #.
	 */
	function getID() {
		return $this->data_array['doc_group'];
	}
	
	/**
	 *	getID - get parent DocumentGroup's id.
	 *
	 *	@return	int	The id #.
	 */
	function getParentID() {
		return $this->data_array['parent_doc_group'];
	}

	/**
	 *	getName - get the name.
	 *
	 *	@return	String	The name.
	 */
	function getName() {
		return $this->data_array['groupname'];
	}

	/**
	 *  update - update a DocumentGroup.
	 *
	 *  @param	string	Name of the category.
	 *  @return boolean.
	 */
	function update($name,$parent_doc_group) {
		$perm =& $this->Group->getPermission (session_get_user());
		if (!$perm || !$perm->isDocEditor()) {
			$this->setPermissionDeniedError();
			return false;
		}
		if (!$name) {
			$this->setMissingParamsError();
			return false;
		}
		
		if ($parent_doc_group) {
			// check if parent group exists
			$res=db_query("SELECT * FROM doc_groups WHERE doc_group='$parent_doc_group' AND group_id=".$this->Group->getID());
			if (!$res || db_numrows($res) < 1) {
				$this->setError(_('DocumentGroup: Invalid DocumentGroup parent ID'));
				return false;
			}
		} else {
			$parent_doc_group=0;
		}

		$sql="UPDATE doc_groups
			SET groupname='".htmlspecialchars($name)."',
			parent_doc_group='".$parent_doc_group."'
			WHERE doc_group='". $this->getID() ."'
			AND group_id='".$this->Group->getID()."'";
		$result=db_query($sql);
		if ($result && db_affected_rows($result) > 0) {
			return true;
		} else {
			$this->setError(db_error());
			return false;
		}
	}
		
	/**
	* hasDocuments - Recursive function that checks if this group or any of it childs has documents associated to it
	*
	* A group has associated documents if and only if there are documents associated to this
	* group or to any of its childs
	*
	* @param array	Array of nested groups information, fetched from DocumentGroupFactory class
	* @param object	The DocumentFactory object
	* @param int	(optional) State of the documents
	*/
	function hasDocuments(&$nested_groups, &$document_factory, $stateid=0) {
		global $doc_group_id;
		static $result = array();	// this function will probably be called several times so we better store results in order to speed things up
		if (!is_array(@$result[$stateid])) $result[$stateid] = array();
		if (array_key_exists($doc_group_id, $result[$stateid])) return $result[$stateid][$doc_group_id];

		$doc_group_id = $this->getID();
		
		// check if it has documents
		if ($stateid) {
			$document_factory->setStateID($stateid);
		}
		$document_factory->setDocGroupID($doc_group_id);
		$docs = $document_factory->getDocuments();
		if (is_array($docs) && count($docs) > 0) {		// this group has documents
			$result[$stateid][$doc_group_id] = true;
			return true;
		}
		
		// this group doesn't have documents... check recursively on the childs
		if (is_array($nested_groups["$doc_group_id"])) {
			$count = count($nested_groups["$doc_group_id"]);
			for ($i=0; $i < $count; $i++) {
				if ($nested_groups["$doc_group_id"][$i]->hasDocuments($nested_groups, $document_factory, $stateid)) {
					// child has documents
					$result[$stateid][$doc_group_id] = true;
					return true;
				}
			}
			// no child has documents, then this group doesn't have associated documents
			$result[$stateid][$doc_group_id] = false;
			return false;
		} else {	// this group doesn't have childs
			$result[$stateid][$doc_group_id] = false;
			return false;
		}
	}

	/**
	* hasSubgroup - Checks if this group has a specified subgroup associated to it
	*
	* @param array Array of nested groups information, fetched from DocumentGroupFactory class
	* @param int	ID of the subgroup
	*/
	function hasSubgroup(&$nested_groups, $doc_subgroup_id) {
		$doc_group_id = $this->getID();

		if (is_array(@$nested_groups["$doc_group_id"])) {
			$count = count($nested_groups["$doc_group_id"]);
			for ($i=0; $i < $count; $i++) {
				// child is a match?
				if ($nested_groups["$doc_group_id"][$i]->getID() == $doc_subgroup_id) {
					return true;
				} else {
					// recursively check if this child has this subgroup
					if ($nested_groups["$doc_group_id"][$i]->hasSubgroup($nested_groups, $doc_subgroup_id)) {
						return true;
					}
				}
			}
		}
		
		return false;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
