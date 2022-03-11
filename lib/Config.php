<?php
class Config {

    public    $debug      = false;

    // ---- database ----
    public    $host         = 'localhost'; // 192.168.0.103'; // virtual or real host?
    public    $user         = 'smalltown';
    public    $password     = 'smalltown';
    public    $database     = 'smalltown';

    public    $domain       = 'smalltown.one';
    public    $mailfrom     = 'admin@smalltown.one';
    public    $webroot      = '/';

    // --- miscellaneous  --------------------- */
    public    $feedLimit    = 10;

    public    $postwidth    = 435;
    public    $postheight   = 435; // only applies to flash
    public    $thumbwidth   = 40;
    public    $thumbheight  = 40;
    public    $defaultImage = "profileDefaultImage.png";

    public    $design       = '0'; // <= your design here

    public    $feedClearInterval = 60; // unit is in seconds

    // --- newsletter --not used --- legacy code ------------------- */
    public    $newstest     = "XXX@XXXX.XXX";
    public    $salt         = "XXXX";

    public    $opposites = [
        'block'     => 'unblock',        'unblock'   => 'block',  
        'follow'    => 'unfollow',       'unfollow'  => 'follow', 
        'friend'    => 'unfriend',       'unfriend'  => 'friend', 
        'request'   => 'unrequest',      'unrequest' => 'request'
    ];
    public    $prettyPrint = [
        'unblock'   => 'remove block',   'unfriend'  => 'remove friend',
        'request'   => 'add friend',     'unrequest' => 'remove request',
        'unblock'   => 'remove block'
    ];
}
?>
