<?php
/**
 * Registration verification page
 *
 * This page is accessed with the link sent in account confirmation
 * email.
 *
 * Copyright 1999-2001 (c) VA Linux Systems
 *
 * @version   $Id$
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

require_once('../env.inc.php');
require_once $gfwww.'include/pre.php';

$confirm_hash = getStringFromRequest('confirm_hash');

if (getStringFromRequest('submit')) {
	$loginname = getStringFromRequest('loginname');
	$passwd = getStringFromRequest('passwd');

	if (!$loginname) {
		exit_error(
			_('Missing paramater'),
			_('You must enter a user name.')
		);
	}

	$u = user_get_object_by_name($loginname);
	if (!$u || !is_object($u)) {
		exit_error('Error','Could Not Get User');
	} elseif ($u->isError()) {
		exit_error('Error',$u->getErrorMessage());
	}

	if ($u->getStatus()=='A'){
		exit_error(
			_('Invalid operation'),
			_('Account already active.')
		);
	}

	$confirm_hash = html_clean_hash_string($confirm_hash);

	if ($confirm_hash != $u->getConfirmHash()) {
		exit_error(
			_('Invalid parameter'),
			_('Cannot confirm account identity - invalid confirmation hash (or login name)')
		);
	}

	if (!session_login_valid($loginname, $passwd, 1)) {
		exit_error(
			_('Access denied'),
			_('Credentials you entered do not correspond to valid account.')
		);
	}

	if (!$u->setStatus('A')) {
		exit_error(
			_('Could not activate account'),
			_('Error while activiting account').': '.$u->getErrorMessage()
		);
	}

	session_redirect("/account/first.php");
}

$HTML->header(array('title'=>'Verify'));

echo _('<p>In order to complete your registration, login now. Your account will then be activated for normal logins.</p>');

if (isset($GLOBALS['error_msg'])) {
	print '<p><span class="error">'.$GLOBALS['error_msg'].'</span>';
}
?>

<form action="<?php echo getStringFromServer('PHP_SELF'); ?>" method="post">

<p><?php echo _('Login name:'); ?>
<br /><input type="text" name="loginname" /></p>
<p><?php echo _('Password:'); ?>
<br /><input type="password" name="passwd" /></p>
<input type="hidden" name="confirm_hash" value="<?php print htmlentities($confirm_hash); ?>" />
<p><input type="submit" name="submit" value="<?php echo _('Login'); ?>" /></p>
</form>

<?php
$HTML->footer(array());

?>
