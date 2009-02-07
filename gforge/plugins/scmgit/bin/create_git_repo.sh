#!/bin/bash
#
# create_git_repo.sh 
#
# Author:     ferenc@maemo.org
# Copyright:  Ferenc Szekely (c) 2008-2009
# License:    GPL v2 (see http://www.gnu.org/licenses/gpl-2.0.html)
#
# Description:
#
# The script is a dirty hack. It creates a new git repository and performs
# the necessary steps to make the repo cloneable. This means the repo will
# be first cloned on the git repository server and a 'welcome' file will be 
# commited to the root of the repository. This is the dirty part.
#
# The script can be used standalone, although it is primarily used
# by the cron job (create_git.php). See cronjobs directory.
#
# The scripts takes 2 arguments: project_name [private]
#
#   project_name is mandatory
#   private      is optional
#
# Works with git version 1.5.6.5
# Tested on Debian lenny (unstable).
#
# Assuming web server setup is appropriate (DAV enabled, authentication 
# configured etc)
#
# If the script is run with sudo then make sure you have these sudoers settings. 
# For details check the INSTALL document.
#

ME=$0
PROJECT=$1
PRIVATE=$2

#
# Adjust these variables according to your setup
#
# A command prefix, that can be e.g. 'sudo -u www-data ' 
CMD_PREFIX=''
CMD_PREFIX_ROOT='' # could be 'sudo -u root '

# The location of the git repositories on the server
GIT_REPO="/var/lib/git/projects"

# The web prefix to access the ready git repos. Suggested to use SSL!
WEB_PREFIX="http://git.localgarage/projects"

usage() {
  echo
  echo "Usage:"
  echo "$0 project_name [private]"
  echo
  echo " project_name    mandatory; name of the new repository, written all lowercase"
  echo " private         optional;  tells to create a private repository. An extra apache2 config entry will be created."
  echo
  echo "By default all new repositories are set to public"
}

if [ -z $PROJECT ]; then 
  usage
  exit 1
fi

if [ ! -d $GIT_REPO ]; then
  # create a repository placeholder
  $CMD_PREFIX mkdir $GIT_REPO
  if [ $? -ne 0 ]; then 
    echo "$GIT_REPO could not be created. Exiting."
    exit 3
  fi
fi

if [ ! -d $GIT_REPO/$PROJECT ]; then
  # create a bare push repo
  $CMD_PREFIX mkdir $GIT_REPO/$PROJECT
  if [ $? -ne 0 ]; then 
    echo "$GIT_REPO/$PROJECT could not be created. Exiting."
    exit 3
  fi
else 
  echo "$GIT_REPO/$PROJECT already exists. Exiting."
  exit 2
fi

cd $GIT_REPO/$PROJECT
$CMD_PREFIX git --bare init
echo "git --bare update-server-info" > /tmp/post-update
$CMD_PREFIX cp /tmp/post-update hooks/post-update
$CMD_PREFIX chmod +x hooks/post-update
$CMD_PREFIX chown www-data:www-data hooks/post-update
rm -rf /tmp/post-update

# very important
cd $GIT_REPO/$PROJECT
$CMD_PREFIX chown -R www-data:www-data .

# create a master branch
cd /tmp
$CMD_PREFIX git clone -l $GIT_REPO/$PROJECT
cd $PROJECT
$CMD_PREFIX touch welcome
$CMD_PREFIX git add welcome 
$CMD_PREFIX git commit -m "welcome"
$CMD_PREFIX git push origin master

cd /tmp
$CMD_PREFIX_ROOT rm -rf $PROJECT

# very important again
cd $GIT_REPO/$PROJECT
$CMD_PREFIX chown -R www-data:www-data .

# add the config for the private project and restart apache2
GIT_REPO_TYPE=`which git_repo_type.sh`
if [ ! -z $GIT_REPO_TYPE ]; then
  if [ x$PRIVATE == "xprivate" ]; then
    $GIT_REPO_TYPE $PROJECT private
  else 
    $GIT_REPO_TYPE $PROJECT
  fi
else 
  echo "Web server configuration script is not found."
  echo "Can not set the web server according to the repository's type."  
fi

echo 
echo "Done"
echo
echo "You can now advertise or clone the repository on the client side:"
echo "git clone $WEB_PREFIX/$PROJECT"
echo 
echo "If you have an SSL enabled repository, then the client side might" 
echo "need to set this environment variable:"
echo " GIT_SSL_NO_VERIFY=1"
echo
