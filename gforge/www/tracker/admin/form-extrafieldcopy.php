<?php

//
//  FORM TO COPY Choices configured by admin for extra_field BOXES 
//
		$id = getIntFromRequest('id');
		$fb= new ArtifactExtraField($ath,$id);
		$title = sprintf(_('Copy choices from custom field %1$s'), $fb->getName());
		$ath->adminHeader(array ('title'=>$title));
		echo "<h3>".$title."</h3>";
		
		$efearr =& $ath->getExtraFieldElements($id);
		for ($i=0; $i<count($efearr); $i++) {
			$field_id_arr[] = $efearr[$i]['element_id'];
			$field_arr[] = $efearr[$i]['element_name'];
		}
		echo '<table>';
		echo '<tr>';
		echo '<td></td><td><center><strong>';
		echo _('Copy From');
		echo '<br />';
		echo $fb->getName();
		echo '</center></strong></td><td></td><td><strong><center>';
		
		echo _('Into trackers and custom fields');
		echo '</center></strong></tr><tr><td><strong><center>';
		echo '</center></strong></td>';
		echo '<td valign=top>';
		?>
		
		<form action="<?php echo getStringFromServer('PHP_SELF') .'?group_id='.$group_id.'&atid='.$ath->getID(); ?>" method="post" >
		<input type="hidden" name="copy_opt" value="copy" >
		<input type="hidden" name="id" value="<?php echo $id; ?>">
		<?php
		echo html_build_multiple_select_box_from_arrays($field_id_arr,$field_arr,'copyid[]',array(),10,false);
		echo '</td><td><strong><center>';
		//get a list of all extra fields in trackers and groups that you have perms to admin
		$sql="SELECT DISTINCT g.unix_group_name, agl.name AS tracker_name, aefl.field_name, aefl.extra_field_id
			FROM groups g, 
			artifact_group_list agl, 
			artifact_extra_field_list aefl,
			user_group ug,
                        role_setting rs
			WHERE
                        (
                           (rs.section_name = 'projectadmin' AND rs.value = 'A')
                           OR (rs.section_name = 'trackeradmin' AND rs.value = '2')
                           OR (rs.section_name = 'tracker' AND rs.value::integer >= 2 AND rs.ref_id = agl.group_artifact_id)
                        )
			AND ug.user_id='".user_getid()."'
			AND ug.group_id=g.group_id
			AND g.group_id=agl.group_id 
			AND rs.role_id=ug.role_id
			AND aefl.group_artifact_id=agl.group_artifact_id
			AND aefl.field_type IN (1,2,3,5)";
		$res=db_query($sql);

//		echo db_error().$sql;

		while($arr =db_fetch_array($res)) {
				$name_arr[]=$arr['unix_group_name']. '::'. $arr['tracker_name'] . '::'. $arr['field_name'];
				$id_arr[]=$arr['extra_field_id'];
		}
		echo '<td valign=top>';

		echo html_build_select_box_from_arrays($id_arr,$name_arr,'selectid',$selectid,false);
		echo '</td></tr>';
		echo '<tr><td>';
		?>
		<br />
	 	<input type="submit" name="post_changes" value="<?php echo _('Submit') ?>" />
		</td></tr></table></form>
		
		<?php
		$ath->footer(array());

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
