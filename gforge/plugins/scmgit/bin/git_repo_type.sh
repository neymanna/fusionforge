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

# Adjust these settings according to your GForge setup
GFORGE_HOST="localgarage"
GFORGE_DB="gforge"
GFORGE_USER="gforge"
GFORGE_PASSWORD="gforge"

usage() {
  echo
  echo "Usage:"
  echo "$0 project_name [private]"
  echo
  echo " project_name    mandatory; name of the repository to which the change is made"
  echo " private         optional;  if added an extra apache2 config entry will be created."
  echo "                            if not added the project's repository will be set to public"
  echo "                            meaning that people will be able to clone it."
  echo "                            pushing still requires membership to that project."
  echo  
}

if [ -z $PROJECT ]; then 
  usage
  exit 1
fi

CONFNAME="/etc/apache2/conf.d/$PROJECT.conf"
GITLIST="/var/www/projects/list.txt"
if [ ! -f $GITLIST ]; then
  touch $GITLIST
fi

# add the config for the private project and restart apache2
if [ x$PRIVATE == "xprivate" ]; then
  #for gitweb
  sed -i -i "s/$PROJECT//g" $GITLIST
  sed '/^$/d' $GITLIST > $GITLIST.bak
  mv $GITLIST.bak $GITLIST
  
  cat << EOF > $CONFNAME
<LocationMatch "/projects/$PROJECT">
  DAV on
    
  AuthType Basic
  AuthName "Git Repository - $PROJECT Private Project"
            
  AuthUserFile /dev/null
  AuthBasicAuthoritative Off
  Auth_PG_host            $GFORGE_HOST
  Auth_PG_database        $GFORGE_DB
  Auth_PG_port            5432
  Auth_PG_user            $GFORGE_USER
  Auth_PG_pwd             $GFORGE_PASSWORD
  Auth_PG_pwd_table       " users, user_group, groups"
  Auth_PG_uid_field       " users.user_name"
  Auth_PG_pwd_field       " users.user_pw"
  Auth_PG_pwd_whereclause " and users.user_id=user_group.user_id and user_group.group_id=groups.group_id group by users.user_pw"
  Auth_PG_encrypted       on
  Auth_PG_hash_type       md5
  Auth_PG_cache_passwords off
  Auth_PG_authoritative   on
  Require valid-user
  Order allow,deny
  Allow from all
</LocationMatch>
EOF
else 
  if [ -f $CONFNAME ]; then
    rm -rf $CONFNAME
  fi
  # for gitweb
  grep $PROJECT $GITLIST >/dev/null
  if [ $? == 1 ]; then 
    echo $PROJECT >> $GITLIST
  fi
fi

chown www-data:www-data $GITLIST
sudo -u root /usr/sbin/apache2ctl graceful
