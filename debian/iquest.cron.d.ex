#
# Regular cron jobs for the iquest package
#
0 4	* * *	root	[ -x /usr/bin/iquest_maintenance ] && /usr/bin/iquest_maintenance
