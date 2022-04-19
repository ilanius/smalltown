#
# Smalltown
#
This system mimics a bare minimum subset of Facebook features
Its output could be improved with CSS
It needs debugging and testing

Tables are documented on google.doc
https://docs.google.com/document/d/1zonUvxt3HwJSKie6GtoiK2B-rS2yFVpIjFHQzIU9MS0/edit?usp=sharing
 
The system consists of a Web Server, Database MySQL (MariaDB), email server (Postfix) and PHP/Javascript
 
It is designed according to the Model View Controll paradigm (MVC). 

Files that contain '.0.' can be overridden. By setting the design parameter in the Config.php file
If for example designer Leo wants to override entry.0.htm he may write a file entry.leo.htm 

Sendmail.php is for testing email function and settings in 
C:\Program Files\PHP7\php.ini

# Initializing system
In order to initialize system you can create tables using the following command in a shell
mysql -uroot -p[root]
create database smalltown;
grant all privileges to smalltown.* to user 'smalltown'@'%' identified by password 'smalltown';
[exit mysql shell]

In ordinary shell in directory /htdocs/smalltown/ write: 
"[path]\mysql.exe" -usmalltown -psmalltown -hlocalhost smalltown < smalltown.sql 
On your system likely:
"C:\Program Files\MariaDB 10.6\bin\mysql.exe" -usmalltown -psmalltown -hlocalhost smalltown < smalltown.sql

image function require installation
apt install php7.4-gd

# Improvements
I think a good starting point where one could improve Graphical User Interface (GUI)
is the userEntry.htm file. There are 3 entries for login, signing up and lost password 
one page. They could be rearranged using css and javascript without rewriting server
script. Eventually it could become a single page application (SPA) where as much
computation as possible is located to the client side.

