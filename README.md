#
# Smalltown
#
# This system mimics a bare minimum subset of Facebook features
# Its output could be improved with CSS
# It needs debugging and testing
#
# Tables are documented on google.doc
# https://docs.google.com/document/d/1zonUvxt3HwJSKie6GtoiK2B-rS2yFVpIjFHQzIU9MS0/edit?usp=sharing
# 
# The system comprises Abyss Webserver, MySQL (MariaDB), PHP on top of windows
# 
# It is designed according to the Model View Controll paradigm (MVC). 
# The database is the Model. Controll is the logic in index.php and view consists of file
# with '.htm' or '0.htm' endings where the latter denotes core template files. 
# If for example a file named entry.htm is added to directory index.php will use instead 
# of entry0.htm
#
#
# If we need an email server hMailserver did not install, whereas Pegasus
# mail might work. Webhotels provide email-servers.
#
# Sendmail.php is for testing email function and settings in 
# C:\Program Files\PHP7\php.ini
#
#
# Initializing system
# In order to initialize system you can create tables using the following command in a shell
# mysql -uroot -p[root]
# create database smalltown;
# grant all privileges to smalltown.* to user 'smalltown'@'%' identified by password 'smalltown';
# [exit mysql shell]
# in ordinary shell in directory /htdocs/smalltown/ write: 
# "[path]\mysql.exe" -usmalltown -psmalltown -hlocalhost smalltown < smalltown.sql 
# On your system likely:
# "C:\Program Files\MariaDB 10.6\bin\mysql.exe" -usmalltown -psmalltown -hlocalhost smalltown < smalltown.sql
#
#
# Improvements
# I think a good starting point where one could improve Graphical User Interface (GUI)
# is the userEntry.htm file. There are 3 entries for login, signing up and lost password 
# one page. They could be rearranged using css and javascript without rewriting server
# script. Eventually it could become a single page application (SPA) where as much
# computation as possible is located to the client side.
#
# Further improvements in GUI and otherwise will become obvious after some
# use of the system. 
#
