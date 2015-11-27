# php-instagram-downloader
Downloads images off Instagram

### Introduction
This thing is not 100% polished. I haven't tested it much. It was just a quick thing I did to download all my Instagram photos with the upload date intact.

Will be looking into speeding up the download times by downloading the files synchronously.

### Usage

```sh
$ php instagram_dl.php <username>
```

### Cookies
Cookies can be exported from your local browser and included in a file called cookies.txt in the same folder as the script.

##### Example cookies.txt
``
instagram.com	FALSE	/	FALSE	1447966497.46746	sessionid	IGSC29284ebc9306394b234aeb80328798a667fc77abd2f34ea8ef9d64a9e8%3ApOywREAC42kSpG8IQNmlZz4qRxCsGDf7n%3A%7B%22_token_ver%22%3A1%2C%22_auth_user_id%22%3A45790314%2C%22_token%22%3A%2245790314%3ASgNlcPPqBkWeYFhN3WVXxzQJ7A3GarmD%3A5289dd03492ec09956373ee803d18106f77df35bcb9179b8bfc80230%22%2C%22_auth_user_backend%22%3A%22accounts.backends.CaseInsensitiveModelBackend%22%2C%22last_refreshed%22%3A1444176362.567775%2C%22_platform%22%3A4%7D
``

### Requirements

This script requires the following installed:
* PHP
* cURL
