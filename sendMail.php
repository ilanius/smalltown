<?php

/* 
    C:\Program Files\PHP7\php.ini
*/
   $to_email = "leonard.ilanius@gmail.com";
   $subject = "Simple Email Test via PHP";
   $body = "Hi,\n This is test email send by PHP Script";
   $headers = "From: leonard.ilanius@gmail.com";
 
   if ( mail($to_email, $subject, $body, $headers)) {
      echo("Email successfully sent to $to_email...");
   } else {
      echo("Email sending failed...");
   }
?>
