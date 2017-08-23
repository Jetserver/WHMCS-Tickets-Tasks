# WHMCS-Tickets-Tasks

# Installation

Upload the file to your WHMCS Addon modules folder, and unzip the file.

Full path after unzip: “modules/addons/ticketstasks“.
* Remove the uploaded zip file.

* If you are uploading the files as root user, don’t forget to change owner & permissions *

Login to your WHMCS as admin user, and navigate to “Setup” -> “Addon modules“, Find the “Tickets Tasks” module, and activate it.

Final step will be to setup a cron job for activating the tasks. We recommend using 10 minutes intervals.

The cron file is protected with a token, you will be able to get it throught the  module’s configuration tab:
“WHMCS Admin Top Menu” -> “Addons” -> “Tickets Tasks“.

Example usage for a cron using server’s internal php –

```
*/10 * * * * php -q /home/username/public_html/modules/addons/ticketstasks/cron.php TOKEN45634AH
```

Example usage for a cron using GET –

```
*/10 * * * * GET http://www.example-domain.loc/modules/addons/ticketstasks/cron.php?token=TOKEN45634AH
```

> *In some systems when using the server’s internal php, it will not get the token. In such cases you will have to use the GET method.

# More Information

https://docs.jetapps.com/category/whmcs-addons/whmcs-tickets-tasks
