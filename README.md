# sharenet-php
Welcome to the ShareNet. Sharenet is a free project based on the p2p concept. Our aim is to create a network with hundreds of nodes relaying messages to all the peers stored in the server list. The relay is self learning, if a message has an unknown origin, the server may add it depending on your relay settings.

In order to make the relay easy to set up, we decided to write it in PHP, the script was first published in 2013. You just have to upload it at the root of your website and the relay will be working. However, when you set up a relay, it doesn't know any other peer. You will have to add a user in data/config/user.lst and send a message to another relay to make it add your server.

To send a message you can use the sharenetLib.php file which includes many functions to manage your relay. 

Each message is stored into the data/ folder: hash.shm (ShareNet Message), you can configure your relay not to store messages.

Around the relay, and thanks to the sharenetLib functions, you can create whatever interface you want : an user interface to send and receive messages, a relay control panel... It is pretty easy to manage the relay, send a message and check its origin.

For example, if a message is sent by Bob on mydomain.com:80 to myrelay.com:80 which sends it to myfinalrelay.com:8080, the last relay will receive:

Hash: ...
Author: Bob@mydomain.com:80
From: myrelay.com:80
Date: ...
Message: ...

The relay will ask the origin server if its user did sent this message and then, add the 2 servers into its list.

Contact me after setting up a relay, I will add it to the default server list :)

