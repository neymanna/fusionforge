<?php
/**
  *
  * SourceForge Generic Tracker facility
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  * @version   $Id$
  *
  */


require_once $gfcommon.'tracker/ArtifactType.class.php';
require_once $gfcommon.'tracker/ArtifactExtraField.class.php';
require_once $gfcommon.'tracker/ArtifactExtraFieldElement.class.php';

class ArtifactTypeHtml extends ArtifactType {

	/**
	 *  ArtifactType() - constructor
	 *
	 *  @param $Group object
	 *  @param $artifact_type_id - the id # assigned to this artifact type in the db
	 */
	function ArtifactTypeHtml(&$Group,$artifact_type_id=false, $arr=false) {
		return $this->ArtifactType($Group,$artifact_type_id,$arr);
	}

	function header($params) {
		global $HTML, $sys_use_tracker;
		if (!$sys_use_tracker) {
			exit_disabled();
		}
		$group_id= $this->Group->getID();

		//required by new site_project_header
		$params['group']=$group_id;
		$params['toptab']='tracker';
		$params['tabtext']=$this->getName();

		site_project_header($params);

		$labels = array();
		$links  = array();

		$labels[] = $this->getName().': '._('Browse');
		$links[]  = '/tracker/?func=browse&amp;group_id='.$group_id.'&amp;atid='. $this->getID();
		$labels[] = _('Download .csv');
		$links[]  = '/tracker/?func=downloadcsv&amp;group_id='.$group_id.'&amp;atid='. $this->getID();
		if ($this->allowsAnon() || session_loggedin()) {
			$labels[] = _('Submit New');
			$links[]  = '/tracker/?func=add&amp;group_id='.$group_id.'&amp;atid='. $this->getID();
		}

		if (session_loggedin()) {
			$labels[] = _('Reporting');
			$links[]  = '/tracker/reporting/?group_id='.$group_id.'&amp;atid='. $this->getID();
			if ($this->isMonitoring()) {
				$labels[] = _('Stop Monitor');
			} else {
				$labels[] = _('Monitor');
			}
			$links[]  = '/tracker/?group_id='.$group_id.'&amp;atid='. $this->getID().'&amp;func=monitor';

			if ($this->userIsAdmin()) {
				$labels[] = _('Admin');
				$links[]  = '/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID();
			}
		}

		echo $HTML->subMenu($labels,$links);
	}

	function footer($params) {
		site_project_footer($params);
	}

	function adminHeader($params) {
		global $HTML;
		echo $this->header($params);
		$group_id= $this->Group->getID();

		$links_arr[]='/tracker/admin/?group_id='.$group_id;
		$title_arr[]=_('New Tracker');

		$links_arr[]='/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID().'&update_type=1';
		$title_arr[]=_('Update Settings');

		$links_arr[]='/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID().'&amp;add_extrafield=1';
		$title_arr[]=_('Manage Custom Fields');

		$links_arr[]='/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID().'&amp;clone_tracker=1';
		$title_arr[]=_('Clone Tracker');

		$links_arr[]='/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID().'&amp;add_canned=1';
		$title_arr[]=_('Add Canned Responses');

		$links_arr[]='/tracker/admin/?group_id='.$group_id.'&amp;atid='.$this->getID().'&amp;delete=1';
		$title_arr[]=_('Delete');

		echo $HTML->printSubMenu($title_arr,$links_arr);
	}

	function adminFooter($params) {
		echo $this->footer($params);
	}

	function renderExtraFields($selected=array(),$show_100=false,$text_100='none',$show_any=false,$text_any='Any',$filter='',$status_show_100=false,$mode='') {
		$efarr = $this->getExtraFields($filter);
		//each two columns, we'll reset this and start a new row
//TODO - add code for "display only" such as mod-limited and detail.php pages
		$template = $this->getRenderHTML($filter);
		if ($mode=='QUERY') {
			$keys=array_keys($efarr);
			for ($k=0; $k<count($keys); $k++) {
				$i=$keys[$k];
				$efarr[$i]['field_type'] = ARTIFACT_EXTRAFIELDTYPE_MULTISELECT;
			}
		}
		$keys=array_keys($efarr);
		for ($k=0; $k<count($keys); $k++) {
			$i=$keys[$k];

			if (!isset($selected[$efarr[$i]['extra_field_id']])) 
				$selected[$efarr[$i]['extra_field_id']] = '';

			if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_SELECT) {
				$str = $this->renderSelect($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$show_100,$text_100,$show_any,$text_any);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX) {

				$str = $this->renderCheckbox($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$show_100,$text_100);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_RADIO) {

				$str = $this->renderRadio($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$show_100,$text_100,$show_any,$text_any);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_TEXT) {

				$str = $this->renderTextField($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$efarr[$i]['attribute1'],$efarr[$i]['attribute2']);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_TEXTAREA) {

				$str = $this->renderTextArea($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$efarr[$i]['attribute1'],$efarr[$i]['attribute2']);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_MULTISELECT) {

				$str = $this->renderMultiSelectBox ($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$show_100,$text_100);

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_STATUS) {

				$str = $this->renderSelect($efarr[$i]['extra_field_id'],$selected[$efarr[$i]['extra_field_id']],$status_show_100,$text_100,$show_any,$text_any);

			}
			$template = str_replace('<!--'.$efarr[$i]['field_name'].'-->',$str,$template);
		}
		if($template != NULL){
			echo $template;
		}
	}

	/**
	 *	getRenderHTML
	 *
	 *	@return	string	HTML template.
	 */
	function getRenderHTML($filter='') {
		return (($this->data_array['custom_renderer']) ? $this->data_array['custom_renderer'] : $this->generateRenderHTML($filter) );
	}

	/**
	 *	generateRenderHTML
	 *
	 *	@return	string	HTML template.
	 */
	function generateRenderHTML($filter='') {
		$efarr =& $this->getExtraFields($filter);
		//each two columns, we'll reset this and start a new row

		$return = '
			<!-- Start Extra Fields Rendering -->
			<!-- COLUMN NAMES MUST BE PRESERVED EXACTLY, INCLUDING CASE! -->
			<tr>';
		$col_count=0;

		$keys=array_keys($efarr);
		$count=count($keys);
		for ($k=0; $k<$count; $k++) {
			$i=$keys[$k];

			if ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_SELECT) {

				$return .= '
					<td width="50%" valign="top"><strong>'.$efarr[$i]['field_name'].':<br /></strong><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_CHECKBOX) {

				$return .= '
					<td width="50%" valign="top"><strong>'.$efarr[$i]['field_name'].':<br /></strong><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_RADIO) {

				$return .= '
					<td width="50%" valign="top"><strong>'.$efarr[$i]['field_name'].':<br /></strong><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_TEXT) {

				//text fields might be really wide, so need a row to themselves.
				if (($col_count == 1) && ($efarr[$i]['attribute1'] > 30)) {
					$colspan=2;
					$return .= '
					<td>&nbsp;</td>
			</tr>
			<tr>';
				} else {
					$colspan=1;
				}
				$return .= '
					<td width="'.(50*$colspan).'%" colspan="'.$colspan.'" valign="top"><strong>'.$efarr[$i]['field_name'].'</strong>:<br /><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_TEXTAREA) {

				//text areas might be really wide, so need a row to themselves.
				if (($col_count == 1) && ($efarr[$i]['attribute2'] > 30)) {
					$colspan=2;
					$return .= '
					<td>&nbsp;</td>
			</tr>
			<tr>';
				} else {
					$colspan=1;
				}
				$return .= '
					<td width="'.(50*$colspan).'%" colspan="'.$colspan.'" valign="top"><strong>'.$efarr[$i]['field_name'].'</strong>:<br /><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_MULTISELECT) {

				$return .= '
					<td width="50%" valign="top"><strong>'.$efarr[$i]['field_name'].'</strong>:<br /><!--'.$efarr[$i]['field_name'].'--></td>';

			} elseif ($efarr[$i]['field_type'] == ARTIFACT_EXTRAFIELDTYPE_STATUS) {

				$return .= '
					<td width="50%" valign="top"><strong>'.$efarr[$i]['field_name'].'</strong>:<br /><!--'.$efarr[$i]['field_name'].'--></td>';

			}
			$col_count++;
			//we've done two columns - if there are more to do, start a new row
			if (($col_count == 2) && ($k != $count-1)) {
				$col_count = 0;
				$return .= '
			</tr>
			<tr>';
			}
		}
		if ($col_count == 1) {
			$return .= '
					<td>&nbsp;</td>';
		}
		$return .= '
			</tr>
			<!-- End Extra Fields Rendering -->';
		return $return;
	}

	/**
	 *	renderSelect - this function builds pop up
	 *	box with choices.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		string	The item that should be checked
	 *	@param		string	Whether to show the '100 row'
	 *	@param		string	What to call the '100 row'
	 *	@return		box and choices	
	 */	
	function renderSelect ($extra_field_id,$checked='xzxz',$show_100=false,$text_100='none',$show_any=false,$text_any='Any') {
		if ($text_100 == 'none'){
			$text_100=_('None');
		}
		$arr =& $this->getExtraFieldElements($extra_field_id);
		for ($i=0; $i<count($arr); $i++) {
			$keys[$i]=$arr[$i]['element_id'];
			$vals[$i]=$arr[$i]['element_name'];
		}
		return html_build_select_box_from_arrays ($keys,$vals,'extra_fields['.$extra_field_id.']',$checked,$show_100,$text_100,$show_any,$text_any);
	}

	/**
	 *	renderRadio - this function builds radio buttons.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		string	The item that should be checked
	 *	@param		string	Whether to show the '100 row'
	 *	@param		string	What to call the '100 row'
	 *	@return		radio buttons
	 */	
	function renderRadio ($extra_field_id,$checked='xzxz',$show_100=false,$text_100='none',$show_any=false,$text_any='Any') {
		$arr =& $this->getExtraFieldElements($extra_field_id);
		for ($i=0; $i<count($arr); $i++) {
			$keys[$i]=$arr[$i]['element_id'];
			$vals[$i]=$arr[$i]['element_name'];
		}
		return html_build_radio_buttons_from_arrays ($keys,$vals,'extra_fields['.$extra_field_id.']',$checked,$show_100,$text_100,$show_any,$text_any);
	}

	/**
	 *	renderCheckbox - this function builds checkboxes.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		array	The items that should be checked
	 *	@param		string	Whether to show the '100 row'
	 *	@param		string	What to call the '100 row'
	 *	@return		radio buttons
	 */	
	function renderCheckbox ($extra_field_id,$checked=array(),$show_100=false,$text_100='none') {
		if ($text_100 == 'none'){
			$text_100=_('None');
		}
		if (!$checked || !is_array($checked)) {
			$checked=array();
		}
		$arr =& $this->getExtraFieldElements($extra_field_id);
		if ($show_100) {
			$return .= '
				<input type="checkbox" name="extra_fields['.$extra_field_id.'][]" value="100" '.
			((in_array(100,$checked)) ? 'CHECKED' : '').'/>'.$text_100.'<br />';
		}
		for ($i=0; $i<count($arr); $i++) {
			$return .= '
				<input type="checkbox" name="extra_fields['.$extra_field_id.'][]" value="'.$arr[$i]['element_id'].'" '.
			((in_array($arr[$i]['element_id'],$checked)) ? 'CHECKED' : '').'/>&nbsp;'.$arr[$i]['element_name'].'<br />';
		}
		return $return;
	}

	/**
	 *	renderMultiSelectBox - this function builds checkboxes.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		array	The items that should be checked
	 *	@param		string	Whether to show the '100 row'
	 *	@param		string	What to call the '100 row'
	 *	@return		radio multiselectbox
	 */	
	function renderMultiSelectBox ($extra_field_id,$checked=array(),$show_100=false,$text_100='none') {
		$arr =& $this->getExtraFieldElements($extra_field_id);
		if (!$checked) {
			$checked=array();
		}
		if (!is_array($checked)) {
			$checked = explode(',',$checked);
		}	
		$keys=array();
		$vals=array();
		$arr =& $this->getExtraFieldElements($extra_field_id);
		for ($i=0; $i<count($arr); $i++) {
			$keys[]=$arr[$i]['element_id'];
			$vals[]=$arr[$i]['element_name'];
		}
		return html_build_multiple_select_box_from_arrays($keys,$vals,"extra_fields[$extra_field_id][]",$checked,15,$show_100,$text_100);
	}

	/**
	 *	renderTextField - this function builds a text field.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		string	The data for this field.
	 *	@return		text area and data.
	 */	
	function renderTextField ($extra_field_id,$contents,$size,$maxlength) {
		return '
			<input type="text" name="extra_fields['.$extra_field_id.']" value="'.$contents.'" size="'.$size.'" maxlength="'.$maxlength.'"/>';
	}

	/**
	 *	renderTextArea - this function builds a text area.
	 *	
	 *	@param		int 	The ID of this field.
	 *	@param 		string	The data for this field.
	 *	@return		text area and data.
	 */	
	function renderTextArea ($extra_field_id,$contents,$rows,$cols) {
		return '
			<textarea name="extra_fields['.$extra_field_id.']" rows="'.$rows.'" cols="'.$cols.'">'.$contents.'</textarea>';
	}

	function technicianBox ($name='assigned_to[]',$checked='xzxz',$show_100=true,$text_100='none',$extra_id='-1',$extra_name='',$multiple=false) {
		if ($text_100=='none'){
			$text_100=_('Nobody');
		}
		$result = $this->getTechnicians();
		//	this was a bad hack to allow you to mass-update to unassigned, which is ID=100, which 
		//	conflicted with the "No Change" ID of 100.
		$ids =& util_result_column_to_array($result,0);
		$names =& util_result_column_to_array($result,1);
		if ($extra_id != '-1') {
			$ids[]=$extra_id;
			$names[]=$extra_name;
		}
			
		if ($multiple) {
			if (!is_array($checked)) {
				$checked = explode(',',$checked);
			}
			return html_build_multiple_select_box_from_arrays ($ids,$names,$name,$checked,15,$show_100,$text_100);
		} else {
			return html_build_select_box_from_arrays ($ids,$names,$name,$checked,$show_100,$text_100);
		}
	}

	function cannedResponseBox ($name='canned_response',$checked='xzxz') {
		return html_build_select_box ($this->getCannedResponses(),$name,$checked);
	}

	/**
	 *	statusBox - show the statuses - automatically shows the "custom statuses" if they exist
	 *
	 *	
	 */
	function statusBox ($name='status_id',$checked='xzxz',$show_100=false,$text_100='none') {
		if ($text_100=='none'){
			$text_100=_('None');
		}
		return html_build_select_box($this->getStatuses(),$name,$checked,$show_100,$text_100);
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
