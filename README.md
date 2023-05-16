# requestbin
simple requestbin

## use
index.php to receive requests for example put this in a folder named /requestbin/ so the full url would be https://server.com/requestbin/

## to inspect
use ?inspect at the end of the url for example https://server.com/requestbin/?inspect

## Predefined responses

You could put a file in the main folder (could be nested) and if the file is present it would be returned as a response instead of 'ok' message

### Examples: 
the main url could be:

https://server.com/requestbin/

if you have a file named auth.json in the folder then if the url accessed is

https://server.com/requestbin/auth.json the contents of this file would be returned instead of 'ok'.

### Another example

if you have file like path/to/file.json in the folder then you can access it with 

https://server.com/requestbin/path/to/file.json

### Allow clearing the log

create a file .env in the folder and use the .env.sample as example. Change your trusted ip and only visitors from that ip
should be able to see and use the clear button which will clear the request log. It's useful if you want to keep your log file tidy.
