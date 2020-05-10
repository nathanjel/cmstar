#!/bin/sh

if [ -f /tmp/.cmstar.update.in.progress ]; then
    exit 0
fi

touch /tmp/.cmstar.update.in.progress

SERVERNAME=`cat /etc/apache2/sites-enabled/* | awk '/^ServerName.*cms.*example/{ printf "%s",$2; exit; }'`

curl -X GET "https://$SERVERNAME/?action=cron_action" >/tmp/.cmstar.tmp.file
if [ -n "$1" ]; then echo ACTION cron_action; cat /tmp/.cmstar.tmp.file; fi

unlink /tmp/.cmstar.tmp.file
unlink /tmp/.cmstar.update.in.progress
