<!-- whole page table -->
<table width="100%" cellpadding="5" cellspacing="0" border="0">
<tr><td width="65%" valign="top">
<h2>Welcome to the GForge 3.0 Project!</h2>
<p>
GForge is an Open Source collaborative software development tool, which allows you to organize
and manage any number of software development projects. It's perfect for managing
large teams of software engineers and/or engineers scattered among multiple locations.
</p>
<p>
<strong>Track Bugs, Patches, Feature Requests, and Support Requests</strong>
</p>
<p>
<a href="/tracker/?atid=101&amp;group_id=5&amp;func=browse"><img src="/images/gforge.jpg" width="450" height="268" border="0" alt="" /></a>
</p>
<p>
<strong>New Project Management Tools:</strong>
</p>
<p>
The new Project Manager allows you to create task lists, constraints, and track the
progress of your project. The data can then be plotted in a standard Gantt chart.
</p>
<p>
<a href="http://dev.gforge.org/pm/task.php?group_id=5&amp;group_project_id=2&amp;func=ganttpage"><img src="/images/gantt.png" width="447" height="230" border="0" alt="" /></a>
</p>
<strong>Site-Wide Reporting and Time Tracking Module Add-on</strong>
<p>
With 42+ graphs covering your entire GForge installation all the way down to individual 
users and tasks, the <strong>Reporting</strong> and <strong>Time Tracking</strong> Module 
helps you manage projects, people and your entire organization.
<p>
The module is available from <a href="http://perdue.net/reporting/">Perdue, Inc.</a> starting at $999.
<p>
<a href="http://perdue.net/reporting/"><img src="/images/reporting.jpg" width="400" height="300" border="0" alt="Reporting Module"/></a>
<p />

<?php
echo $HTML->boxTop($Language->getText('group','long_news'));
echo news_show_latest($sys_news_group,5,true,false,false,5);
echo $HTML->boxBottom();
?>

</td>

<td width="35%" valign="top">

<?php
echo $HTML->boxTop('Getting GForge');
?>
<strong>Download:</strong><br />
<a href="/project/showfiles.php?group_id=1">GForge3.0pre9</a><br />
<a href="http://postgresql.org/">PostgreSQL</a><br />
<a href="http://www.php.net/">PHP 4.x</a><br />
<a href="http://www.apache.org/">Apache</a><br />
<a href="http://www.gnu.org/software/mailman/">Mailman *</a><br />
<a href="http://www.python.org/">Python *</a><br />
<a href="http://jabberd.jabberstudio.org/">Jabber Server *</a><br />
* optional
<p />
<strong>Get Help</strong><br />
<a href="http://gforge.org/pro/"><strong>Pro Help</strong></a><br />
<a href="http://gforge.org/forum/forum.php?forum_id=6"><strong>Help Board</strong></a><br />
<a href="http://gforge.org/docman/?group_id=1"><strong>Online Docs</strong></a><br />
<p />
<strong>Contribute!</strong><br />
<a href="http://gforge.org/projects/gforge/"><strong>GForge Project Page</strong></a><br />
<a href="http://gforge.org/forum/forum.php?forum_id=5"><strong>Developer Board</strong></a><br />
<a href="http://gforge.org/forum/forum.php?forum_id=29"><strong>Oracle Board</strong></a><br />
<a href="http://gforge.org/forum/forum.php?forum_id=44"><strong>SOAP API</strong></a><br />
<a href="http://gforge.org/tracker/?atid=105&amp;group_id=1&amp;func=browse"><strong>Bug Tracker</strong></a><br />
<a href="http://gforge.org/tracker/?func=browse&amp;group_id=1&amp;atid=106"><strong>Patch Submissions</strong></a><br />
<a href="http://gforge.org/tracker/?func=browse&amp;group_id=1&amp;atid=119"><strong>Feature Requests</strong></a>
<p />
<a href="http://www.debian.org/"><strong>Debian Users</strong></a> can simply add
"http://people.debian.org/~bayle/" to /etc/apt/sources.list and type
"apt-get install gforge" to
install a working GForge-3.0pre9 system, thanks to Christian Bayle and
the Debian-SF project.
<p />
<?php
echo $HTML->boxBottom();
echo show_features_boxes();
?>

</td></tr></table>
