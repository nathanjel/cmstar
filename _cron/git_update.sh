#/bin/sh

cd /opt/cmstar
git pull

cd /tmp
sudo crontab -l >curr.crtb
if ! cmp curr.crtb /opt/cmstar/_cron/crontab >/dev/null 2>&1
then
	cat /opt/cmstar/_cron/crontab | sudo crontab -
fi
sudo rm curr.crtb
