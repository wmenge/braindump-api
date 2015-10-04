Scheduling jobs
===============

Scheduling jobs can be done using cron.

Example configuration
-------

Open the crontab file in your favourite editor
````
crontab -e
````

Add this line to check mails every 10 minutes:

````
*/10 * * * * /usr/bin/php  /var/www/braindump/jobs/process_mail.php
````

See http://stackoverflow.com/questions/1830208/php-cron-job-every-10-minutes for more information