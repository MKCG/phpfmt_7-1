# phpfmt_7-1
Fix the phpfmt nullable types space issue with php 7.1 and above : https://github.com/nanch/phpfmt_stable/issues/36

Fix the phpfmt space after "yield from" issue with php 7.1 and above : https://github.com/nanch/phpfmt_stable/issues/37
Fix the ClassToStatic pass issue : https://github.com/nanch/phpfmt_stable/issues/31

First install https://github.com/nanch/phpfmt_stable then replace the fmt.phar

You can either use the one provided here (not recommended) or use the fix-fmt.php script to automatically fix your current fmt.phar file.

## With the provided script
The provided script takes no argument but contains the path of the file to fix so I would recommend you to replace the fourth line if necessary.

Also the PHP configuration "phar.readonly" must be disable for the fix-fmt.php script to fix the phar content.

Example :
`php -d "phar.readonly=0" fix-fmt.php`
