<?php
/**
 * GForge Doc Mgr Facility
 *
 * Copyright 2002 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */


/*
	Document Manager

	by Quentin Cregan, SourceForge 06/2000

	Complete OO rewrite by Tim Perdue 1/2003
*/
/*

   Ftp upload option is commented-out

*/

require_once('../../env.inc.php');
require_once $gfwww.'include/pre.php';
require_once $gfwww.'docman/include/doc_utils.php';
require_once $gfwww.'docman/include/DocumentGroupHTML.class.php';
require_once $gfcommon.'docman/DocumentFactory.class.php';
require_once $gfcommon.'docman/DocumentGroup.class.php';
require_once $gfcommon.'docman/DocumentGroupFactory.class.php';
require_once $gfcommon.'include/TextSanitizer.class.php'; // to make the HTML input by the user safe to store

$group_id = getIntFromRequest('group_id');
if (!$group_id) {
	exit_no_group();
}

$g =& group_get_object($group_id);
if (!$g || !is_object($g) || $g->isError()) {
	exit_no_group();
}

$perm =& $g->getPermission( session_get_user() );
if (!$perm || $perm->isError() || !$perm->isDocEditor()) {
	exit_permission_denied();
}

$editdoc = getStringFromRequest('editdoc');
$docid = getIntFromRequest('docid');

$upload_dir = $sys_ftp_upload_dir . "/" . $g->getUnixName();

//
//
//	Submit the changes to the database
//
//

if (getStringFromRequest('submit')) {
	if ($editdoc) {
		$doc_group = getIntFromRequest('doc_group');
		$title = getStringFromRequest('title');
		$description = getStringFromRequest('description');
		$language_id = getIntFromRequest('language_id');
		$data = getStringFromRequest('data');
		$file_url = getStringFromRequest('file_url');
		//$ftp_filename = getStringFromRequest('ftp_filename');
		$uploaded_data = getUploadedFile('uploaded_data');
		$stateid = getIntFromRequest('stateid');
		$filetype = getStringFromRequest('filetype');
		$editor = getStringFromRequest('editor');

		$d= new Document($g,$docid);
		if ($d->isError()) {
			exit_error(_('Error'),$d->getErrorMessage());
		}
		
		$sanitizer = new TextSanitizer();
		$data = $sanitizer->SanitizeHtml($data);
		if (($editor) && ($d->getFileData()!=$data) && (!$uploaded_data['name'])) {
			$filename = $d->getFileName();
			if (!$filetype) {
				$filetype = $d->getFileType();
			}
		} elseif ($uploaded_data['name']) {
			if (!is_uploaded_file($uploaded_data['tmp_name'])) {
				exit_error(_('Error'),sprintf(_('Invalid file attack attempt %1$s'), $uploaded_data['name']));
			}
			$data = addslashes(fread(fopen($uploaded_data['tmp_name'], 'r'), $uploaded_data['size']));
			$filename=$uploaded_data['name'];
			$filetype=$uploaded_data['type'];
		} elseif ($file_url) {
			$data = '';
			$filename=$file_url;
			$filetype='URL';
		/*
		} elseif ($sys_use_ftpuploads && $ftp_filename!=100) { //100==None
			$filename=$upload_dir.'/'.$ftp_filename;
			$data = addslashes(fread(fopen($filename, 'r'), filesize($filename)));
			$filetype=$uploaded_data_type;
		*/
		} else {
			$filename=addslashes($d->getFileName());
			$filetype=addslashes($d->getFileType());
		}
		if (!$d->update($filename,$filetype,$data,$doc_group,$title,$language_id,$description,$stateid)) {
			exit_error('Error',$d->getErrorMessage());
		}
		$feedback = _('Updated successfully');

	} elseif (getStringFromRequest('editgroup')) {
		$doc_group = getIntFromRequest('doc_group');
		$groupname = getStringFromRequest('groupname');
		$parent_doc_group = getIntFromRequest('parent_doc_group');
		
		$dg = new DocumentGroup($g,$doc_group);
		if ($dg->isError()) {
			exit_error('Error',$dg->getErrorMessage());
		}
		if (!$dg->update($groupname,$parent_doc_group)) {			
			exit_error('Error',$dg->getErrorMessage());
		}
		$feedback = _('Updated successfully');


	} elseif (getStringFromRequest('addgroup')) {
		$groupname = getStringFromRequest('groupname');
		$parent_doc_group = getIntFromRequest('parent_doc_group');

		$dg = new DocumentGroup($g);
		if ($dg->isError()) {
			exit_error('Error',$dg->getErrorMessage());
		}
		if (!$dg->create($groupname, $parent_doc_group)) {
			exit_error('Error',$dg->getErrorMessage());
		}
		$feedback = _('Created successfully');
	
	} elseif (getStringFromRequest('deletedoc') && $docid && getStringFromRequest('sure') && getStringFromRequest('really_sure')) {
		$d= new Document($g,$docid);
		if ($d->isError()) {
			exit_error('Error',$d->getErrorMessage());
		}
		
		if (!$d->delete()) {
			exit_error('Error',$d->getErrorMessage());
		}
		
		$feedback = _('Deleted');
		header('Location: index.php?group_id='.$d->Group->getID().'&feedback='.urlencode($feedback));
		die();	// End parsing file and redirect
	}

}

//
//
//	Edit a specific document
//
//
if ($editdoc && $docid) {

	$d= new Document($g,$docid);
	if ($d->isError()) {
		exit_error('Error',$d->getErrorMessage());
	}

	$dgf = new DocumentGroupFactory($g);
	if ($dgf->isError()) {
		exit_error('Error',$dgf->getErrorMessage());
	}
	
	$dgh = new DocumentGroupHTML($g);
	if ($dgh->isError()) {
		exit_error('Error',$dgh->getErrorMessage());
	}

	
	docman_header(_('Document Manager Administration'),_('Edit Docs'),'');

	?>
		<br />
		<?php echo _('<strong>Document Title</strong>:  Refers to the relatively brief title of the document (e.g. How to use the download server)<br /><strong>Description:</strong> A brief description to be placed just under the title.') ?>
	<form name="editdata" action="index.php?editdoc=1&amp;group_id=<?php echo $group_id; ?>" method="post" enctype="multipart/form-data">

	<table border="0">

	<tr>
		<td>
		<strong><?php echo _('Document Title') ?>: </strong><?php echo utils_requiredField(); ?> <?php printf(_('(at least %1$s characters)'), 5) ?><br />
		<input type="text" name="title" size="40" maxlength="255" value="<?php echo $d->getName(); ?>" />
		<br /></td>
	</tr>

	<tr>
		<td>
		<strong><?php echo _('Description') ?></strong><?php echo utils_requiredField(); ?> <?php printf(_('(at least %1$s characters)'), 10) ?><br />
		<input type="text" name="description" size="20" maxlength="255" value="<?php echo $d->getDescription(); ?>" />
		<br /></td>
	</tr>

	<tr>
		<td>
		<strong><?php echo _('File')?></strong><?php echo utils_requiredField(); ?><br />
		<?php if ($d->isURL()) {
			echo '<a href="'.$d->getFileName().'">[View File URL]</a>';
		} else { ?>
		<a target="_blank" href="../view.php/<?php echo $group_id.'/'.$d->getID().'/'.$d->getFileName() ?>"><?php echo $d->getName(); ?></a>
		<?php } ?>
		</td>
	</tr>

	<?php

	if ((!$d->isURL()) && ($d->isText())) {
		echo '<tr>
				<td>
				';
		//echo '<input type="hidden" name="editor" value="editor">';
		echo _('Edit the contents to your desire or leave them as they are to remain unmodified.');
		/*
		$GLOBALS['editor_was_set_up']=false;
		$params = array () ;
		$params['name'] = 'data';
		$params['width'] = "800";
		$params['height'] = "500";
		$params['group'] = $group_id;
		$params['body'] = $d->getFileData();
		if ($d->isHtml()){
			// we are displaying with textarea if the document is not html (fckeditor pre-parses the files as html and validates it/changes it)
			plugin_hook("text_editor",$params);
		}
		if (!$GLOBALS['editor_was_set_up']) {
		*/
			//if we don't have any plugin for text editor, display a simple textarea edit box
			echo '<textarea name="data" rows="15" cols="100" wrap="soft">'. $d->getFileData()  .'</textarea><br />';
			echo '<input type="hidden" name="filetype" value="text/plain">';
		/*
		} else {
			echo '<input type="hidden" name="filetype" value="text/html">'; // the fckeditor creates html docs. this is for filetype
		}
		unset($GLOBALS['editor_was_set_up']);
		*/
		echo '</td>
			</tr>';
	}
	
	?>

	<tr>
		<td>
		<strong><?php echo _('Language') ?></strong><br />
		<?php

			echo html_get_language_popup('language_id',$d->getLanguageID());

		?></td>
	</tr>

	<tr>
		<td>
		<strong><?php echo _('Group that document belongs in') ?></strong><br />
		<?php

			//echo display_groups_option($group_id,$d->getDocGroupID());
			$dgh->showSelectNestedGroups($dgf->getNested(), 'doc_group', false, $d->getDocGroupID());

		?></td>
	</tr>

	<tr>
		<td>
		<br /><strong><?php echo _('State') ?>:</strong><br />
		<?php

			doc_get_state_box($d->getStateID());

		?></td>
	</tr>

	<?php

/*
	//	if this is a text/html doc, display an edit box
	if (strstr($d->getFileType(),'ext')) {

		echo	'
	<tr>
		<td>
		<strong>'._('Document Contents').'</strong><br />
		<textarea cols="80" rows="20" name="data">'. htmlspecialchars( $d->getFileData() ).'</textarea>
		</td>
	</tr>';
	}
*/
	?>
	<tr>
		<td>
		<?php if ($d->isURL()) { ?>
		<strong><?php echo _('Specify an outside URL where the file will be referenced') ?> :</strong><?php echo utils_requiredField(); ?><br />
        <input type="text" name="file_url" size="50" value="<?php echo $d->getFileName() ?>" />
		<?php } else { ?>
		<strong><?php echo _('OPTIONAL: Upload new file') ?></strong><br />
		<input type="file" name="uploaded_data" size="30" /><br/><br />
			<?php //if ($sys_use_ftpuploads) { ?>
			<!--<strong><?php //printf(_('OR choose one form FTP %1$s'), $sys_ftp_upload_host) ?></strong>--><br />
			<?php
			//$ftp_files_arr=array_merge($arr,ls($upload_dir,true));
			//echo html_build_select_box_from_arrays($ftp_files_arr,$ftp_files_arr,'ftp_filename','');
			//echo '<br /><br />';			
			//}
		}
		?>
		</td>
	</tr>
	</table>

	<input type="hidden" name="docid" value="<?php echo $d->getID(); ?>" />
	<input type="submit" value="<?php echo _('Submit Edit') ?>" name="submit" /><br /><br />
	<a href="index.php?deletedoc=1&amp;docid=<?php echo $d->getID() ?>&amp;group_id=<?php echo $d->Group->getID() ?>"><?php echo _('Permanently delete this document') ?></a>

	</form>
	<?php

	docman_footer(array());

//
//
//	Add a document group / view existing groups list
//
//
} elseif (getStringFromRequest('addgroup')) {

	docman_header(_('Document Manager Administration'),_('Add Document Groups'),'');

	echo "<h1>"._('Add Document Groups')."</h1>";
	
	$dgf = new DocumentGroupFactory($g);
	if ($dgf->isError()) {
		exit_error('Error',$dgf->getErrorMessage());
	}
	
	$dgh = new DocumentGroupHTML($g);
	if ($dgh->isError()) {
		exit_error('Error',$dgh->getErrorMessage());
	}
	
	$nested_groups =& $dgf->getNested();
	
	if (count($nested_groups) > 0) {
		$title_arr=array();
		$title_arr[]=_('ID');
		$title_arr[]=_('Group Name');

		echo $GLOBALS['HTML']->listTableTop ($title_arr);
		
		$row = 0;
		$dgh->showTableNestedGroups($nested_groups, $row);
		
		echo $GLOBALS['HTML']->listTableBottom();
		
	} else {
		echo "\n<h1>"._('No Document Groups defined')."</h1>";
	}
	?>
	<p><strong><?php echo _('Add a group') ?>:</strong></p>
	<form name="addgroup" action="index.php?addgroup=1&amp;group_id=<?php echo $group_id; ?>" method="post">
	<table>
		<tr>
			<th><?php echo _('New Group Name') ?>:</th>
			<td><input type="text" name="groupname" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<th><?php echo _('Belongs to') ?>:</th>
			<td>
				<?php echo $dgh->showSelectNestedGroups($nested_groups, 'parent_doc_group') ?>
			</td>

			<td><input type="submit" value="<?php echo _('Add') ?>" name="submit" /></td>
		</tr>
	</table>
	<p>
		 <?php echo _('Group name will be used as a title, so it should be formatted correspondingly.') ?>
	</p>
	</form>
	<?php

	docman_footer(array());

//
//
//	Edit a specific doc group
//
//
} elseif (getStringFromRequest('editgroup') && getIntFromRequest('doc_group')) {
	$doc_group = getIntFromRequest('doc_group');
	
	$dg = new DocumentGroup($g,$doc_group);
	if ($dg->isError()) {
		exit_error('Error',$dg->getErrorMessage());
	}
	
	$dgf = new DocumentGroupFactory($g);
	if ($dgf->isError()) {
		exit_error('Error',$dgf->getErrorMessage());
	}
	
	$dgh = new DocumentGroupHTML($g);
	if ($dgh->isError()) {
		exit_error('Error',$dgh->getErrorMessage());
	}

	docman_header(_('Document Manager Administration'),_('Edit Groups'),'');
	?>
	<p><strong><?php echo _('Edit a group') ?></strong></p>
	<form name="editgroup" action="index.php?editgroup=1&amp;group_id=<?php echo $group_id; ?>" method="post">
	<input type="hidden" name="doc_group" value="<?php echo $doc_group; ?>" />
	<table>
		<tr>
			<th><?php echo _('Group Name') ?>:</th>
			<td><input type="text" name="groupname" value="<?php echo $dg->getName(); ?>" /></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<th><?php echo _('Belongs to') ?>:</th>
			<td>
			<?php
				$dgh->showSelectNestedGroups($dgf->getNested(), "parent_doc_group", true, $dg->getParentId(), array($dg->getID()));
			?>
			</td>
			<td><input type="submit" value="<?php echo _('Edit') ?>" name="submit" /></td>
		</tr>
	</table>
	<p>
		 <?php echo _('Group name will be used as a title, so it should be formatted correspondingly.') ?>

	</p>
	</form>
	<?php
	docman_footer(array());
} else if (getStringFromRequest('deletedoc') && $docid) {
	$d= new Document($g,$docid);
	if ($d->isError()) {
		exit_error('Error',$d->getErrorMessage());
	}
	
	docman_header(_('Document Manager Administration'),_('Edit Groups'),'');
?>
		<p>
		<form action="<?php echo $PHP_SELF.'?deletedoc=1&amp;docid='.$d->getID().'&amp;group_id='.$d->Group->getID() ?>" method="post">
		<input type="hidden" name="submit" value="1" /><br />
		<?php echo _('You are about to permanently delete this document.'); ?>
		<p>
		<input type="checkbox" name="sure" value="1"><?php echo _('I\'m Sure.') ?><br />
		<input type="checkbox" name="really_sure" value="1"><?php echo _('I\'m Really Sure.') ?><br />
		<p>
		<input type="submit" name="post_changes" value="<?php echo _('Delete') ?>" /></p>
		</form></p>
<?php
	docman_footer(array());

//
//
//	Display the main admin page
//
//
} else {

	$df = new DocumentFactory($g);
	if ($df->isError()) {
		exit_error(_('Error'),$df->getErrorMessage());
	}
	
	$dgf = new DocumentGroupFactory($g);
	if ($dgf->isError()) {
		exit_error(_('Error'),$dgf->getErrorMessage());
	}
	

	$df->setStateID('ALL');
//	$df->setSort('stateid');
	$d_arr =& $df->getDocuments();
	
	docman_header(sprintf(_('Project %s'), $g->getPublicName()),_('Document Manager: Administration'),'admin');

	?> 
	<h3><?php echo _('Document Manager: Administration') ?></h3>
	<p>
	<a href="index.php?group_id=<?php echo $group_id; ?>&amp;addgroup=1"><?php echo _('Add/Edit Document Groups') ?></a>
	</p>
	<?php
	
	$selected_stateid = getIntFromRequest('selected_stateid');
	if (!$d_arr || count($d_arr) < 1) {
		print "<p><strong>"._('This project has no visible documents').".</strong></p>";
	} else {
		// get a list of used document states		
		$states = $df->getUsedStates();
		$nested_groups =& $dgf->getNested();
		echo "<ul>";
		foreach ($states as $state) {
			echo "<li><strong>".$state["name"]."</strong>";
			docman_display_documents($nested_groups, $df, true, $state['stateid'], true);
			echo "</li>";
		}
		echo "</ul>";
	}

	docman_footer(array());

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
