#!/bin/bash

BACKUP_USER="backup_user"
BACKUP_PASS="password"
DB_NAME="password"
ARCHIVE_DIR="/opt/archive"
DB_ARCHIVE_FILE_NAME=`date +ls-change-log-%F.sql`
ACCESS_ARCHIVE_FILEN_NAME=`date +ls-access-dat-%F.tar.gz`

mysql -u $BACKUP_USER --password=$BACKUP_PASS -e 'CREATE TABLE `tmp_cms_history_changes` ( `user` varchar(20) COLLATE utf8_unicode_ci NOT NULL, `change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `cmsid` bigint(20) NOT NULL, `site` smallint(6) NOT NULL, `lang` smallint(6) NOT NULL, `pathl` varchar(60) COLLATE utf8_unicode_ci NOT NULL, `field` varchar(30) COLLATE utf8_unicode_ci NOT NULL, `old` mediumtext COLLATE utf8_unicode_ci NOT NULL, `new` mediumtext COLLATE utf8_unicode_ci NOT NULL, `ip` varchar(15) CHARACTER SET ascii COLLATE ascii_bin NOT NULL ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;' $DB_NAME
mysql -u $BACKUP_USER --password=$BACKUP_PASS -e 'insert into tmp_cms_history_changes select * from cms_history_changes where cms_history_changes.change < date_sub(date(now()),interval 1 week);' $DB_NAME

mysqldump -u $BACKUP_USER --password=$BACKUP_PASS --no-create-db --quick --skip-lock-tables $DB_NAME tmp_cms_history_changes > /opt/archive/$DB_ARCHIVE_FILE_NAME
xz $ARCHIVE_DIR/$DB_ARCHIVE_FILE_NAME

mysql -u $BACKUP_USER --password=$BACKUP_PASS -e 'delete from cms_history_changes where cms_history_changes.change < date_sub(date(now()),interval 1 week);' $DB_NAME
mysql -u $BACKUP_USER --password=$BACKUP_PASS -e 'OPTIMIZE TABLE `cms_history_changes`;' $DB_NAME
mysql -u $BACKUP_USER --password=$BACKUP_PASS -e 'drop table tmp_cms_history_changes;' $DB_NAME

tar czvf $ARCHIVE_DIR/$ACCESS_ARCHIVE_FILEN_NAME /opt/lsCRM/__cms/data/*.dat
