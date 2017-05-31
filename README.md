# Sunny Web Box - Report current power levels.

This php script will scan whatever folder you give it - for the files Sunny web box uploads when using the available FTP data trasnfer.

It will alert to a slack channel you choose when the power reaches the threshholds.

```
Developed and maintainted by Joel Male <joel@joelmale.com>
```

### Installation Instructions

1. Set up an FTP server for Sunny webbox to transfer to.
2. Adjust the $FILE_PATH variable to the directory where Sunny Webbox uploads the zip files to. (An example of a zip file is included)
3. Update $SITE_NAME and Warning/Critical levels to your Site name, and your thresholds.
4. Update your slack endpoint in power_level_check.php for notification purposes
5. Finally - update the Database details in database.php.

### Testing

You can test the script by running 

``` php power_level_check.php ```

### Cron

You can ensure this runs automatically every minute by simply adding the following to your crontab:

```
* * * * * php power_level_check.php
```