<?php
/* ******************************* */
/* smalltown - facebook light      */
/* no ads                          */
/* ******************************* */

function postAdd() {  /* post and comments handled here */
    $DB->insert( 'post', $ord );
    userFeed();
}
function postDelete() {
    // check if user owns post
    // delete post
    // delete comments to post
}
function commentAdd() {
    $DB->insert( 'post', $ord );
    userFeed();
}
function commentDelete() {
    // check if user owns comment
}
/* - 
CREATE TABLE `user` ( `uId` int(11) NOT NULL AUTO_INCREMENT, `uName` varchar(32) NOT NULL, 
`uEmail` varchar(128) NOT NULL, `uLastName` varchar(32) DEFAULT NULL, `uFirstName` varchar(32) DEFAULT NULL, 
`uPassword` char(128) DEFAULT NULL,  `uYear` int(11) DEFAULT NULL, `uCourse` char(32) DEFAULT NULL, 
`uImageId` int(11) DEFAULT NULL,  PRIMARY KEY (`uId`), KEY `uEmail` (`uEmail`) ); 
*/
function userGet($R, $DB) {
    /* complex algorithm */
    $user = $DB->select("* from user where uId='$R[uId]'");
    // implode to string
    $fU = '(' . implode( ',', $friendUser ) . ')';
    $R['uPost'] = $DB->select("* from posts where uId = '$R[uId]'");

    require 'userProfile.php';
}
function userFeed( $R, $DB ) {
    $friendUser = $DB->select("uId2 from friend where uId1='$R[uId]' and relType in ('friend', 'follow')");
    // implode to string
    $fU = '(' . implode( ',', $friendUser ) . ')';
    $post = $DB->select("* from post where uId in $fU and uId2 is null order by pTime desc limit0,100");

    foreach ( $post as $itm ) {
        $phsh[ $itm['pId'] ] = $itm;
    }

    $comm = $DB->select("* from post where uId in $fu and uId2 is not null and order by pTime desc limit0,100");
    foreach ( $comm as $itm ) {
        if ( $phsh[ $itm['pId2'] ] ) {
            $pst = $phsh[$itm['pId2'] ];
            array_push(  $pst['comm'], $itm );
        }
    }
    $ord['uFeed'] = $posts;
    require 'userFeed.php';
}
function userLoggedIn($R, $DB ) {
    if ( isset( $_COOKIE['session'] ) ) {
        $res = $DB->select("* from session where session='$_COOKIE[session]'");
        if ( $res != 0 ) {
            $DB->update("session where session='$_COOKIE[session]' set sTime='now()'");
        }
        return 1;
    } else if ( isset( $_POST['uEmail'] ) && isset( $_POST['uPassword'] ) ) {
        $pass = password_hash( $_POST['uPassword'], PASSWORD_DEFAULT );
        
        $res = $DB->select("* from user where uEmail='$_POST[uEmail]'");
        if ( $password_verify( $_POST['uPassword'], $res['uPassword'] ) ) {
            return 1;
        }
    }
    return 0;
}
function userLogin($R) {
    // serve login/signup-form
    require 'userLogin.htm';
    return 0;
}
function userSearch() {}
function userSignup() {}

/* init */
require 'lib/Config.php';
require 'lib/Database.php';
$C = new Config();
$DB = new Database( $C );

global $R;
$R = array();
foreach ( $_REQUEST as $k=>$v ) {
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}
$R['userImage'] = 'default.png';
$R['func'] = isset( $R['func'] ) ? $R['func'] : '';
/* **************************** */
/* routing */
/* **************************** */
userLoggedIn( $R, $DB ) || userLogin( $R, $DB ) || die();

// change to switch: https://www.php.net/manual/en/control-structures.switch.php
if ( $R['func'] == 'userPost' ) {
    echo "\nRoute userPost\n";
    userPost( $R, $DB );
} else if ( $R['func'] == 'userProfile' ) {
    userProfile( $R, $DB );
} else if ( $R['func'] == 'userComment' ) {
    userComment( $R, $DB );
} else {  // default userFeed
    echo "\nRoute userFeed\n";
    userFeed( $R, $DB );
}

?>

