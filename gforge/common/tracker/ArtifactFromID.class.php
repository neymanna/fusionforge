<?php
/**
 * FusionForge trackers
 *
 * Copyright 2002, GForge, LLC
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

class ArtifactFromID extends Error {

//artifact_vw

	var $Group;
	var $ArtifactType;
	var $Artifact;

	function ArtifactFromID($id, $data = false) {
		if ($data) {
			$art_arr =& $data;
		} else {
			$res=db_query("SELECT * FROM artifact_vw WHERE artifact_id='$id'");
			if (!$res || db_numrows($res) < 1) {
				$this->setError("Invalid Artifact ID");
				return false;
			} else {
				$art_arr =& db_fetch_array($res);
			}
		} 

		$at = artifactType_get_object($art_arr['group_artifact_id']);
		if (!$at || !is_object($at)) {
			$this->setError("Could Not Create ArtifactType");
			return false;
		} elseif ($at->isError()) {
			$this->setError($at->getErrorMessage());
			return false;
		}
		$this->ArtifactType =& $at;

		$a = artifact_get_object($id,$art_arr);
		if (!$a || !is_object($a)) {
			$this->setError("Could Not Create Artifact");
			return false;
		} elseif ($a->isError()) {
			$this->setError($a->getErrorMessage());
			return false;
		}
		$this->Artifact =& $a;
		return true;
	}

}

?>
