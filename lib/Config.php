<?php
class Config {

    public    $debug      = false;

    // ---- database ----
    public    $host       = 'localhost'; // 192.168.0.103'; // virtual or real host?
    public    $user       = 'smalltown';
    public    $password   = 'smalltown';
    public    $database   = 'smalltown';

    public    $mailfrom   = 'XX@XXX.XXX';
    public    $superadmin = 'XX@XXX.XXX';
    public    $webroot    = '/';

    // --- miscellaneous  --------------------- */
    public    $maxrslt     = 15;
    public    $postwidth   = 435;
    public    $postheight  = 435; // only applies to flash
    public    $thumbwidth  = 180;
    public    $thumbheight = 180;
    public    $decimals    = 0;
    public    $defaultImage = "profileDefaultImage.png";

    public    $feedClearInterval = 60; // s

    // --- newsletter --not used --- legacy code ------------------- */
    public    $newstest   = "XXX@XXXX.XXX";
    public    $salt       = "XXXX";

    public    $opposites = [
        'block'    => 'unblock',    'unblock'   => 'block',  
        'follow'   => 'unfollow',   'unfollow'  => 'follow', 
        'friend'   => 'unfriend',   'unfriend'  => 'friend', 
        'request'  => 'unrequest',  'unrequest' => 'request'
    ];
    public    $prettyPrint = [
        'unblock'   => 'remove block',   'unfriend'  => 'remove friend',
        'request'   => 'add friend',     'unrequest' => 'remove request',
        'unblock'   => 'remove block'
    ];
}
?>
