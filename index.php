<?php
/* ******************************* */
/* smalltown - facebook light      */
/* no ads                          */
/* ******************************* */
function debg( $mess ) {
    error_log( $mess."\n" , 3, './phplog.txt');
}
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
    $stmnt = "uId2 from friend where uId1='$R[uId]' and relType in ('friend', 'follow')"; debg( 'A userFeed. stmnt:'.$stmnt );
    $friends = $DB->select( $stmnt );
    array_push( $friends, '-1' );
    $fU = implode( ',', $friends );

    // main posts for feed
    $stmnt = "* from post where uId in ($fU) and ppId is null order by pTime desc limit 0,100"; debg( 'B userFeed.stmnt: '.$stmnt );
    $posts = $DB->select( $stmnt );
 
    debg( 'sizeof posts:'. sizeof($posts) ) ;
 
    if ( sizeof ( $posts ) > 0 ) {
        $cPostIds = [-1];
        foreach ( $posts as $post ) {
            $postHash[ $post['pId'] ] = $post;
            array_push( $cPostIds, $post['pId'] );
        }

        // comments
        $cpi = implode( ',', $cPostIds );
        $stmnt = "* from post where uId in ($fU) and ppId in ($cpi) order by pTime desc limit 0,100"; debg( 'C userFeed.stmnt:'.$stmnt );
        $comments = $DB->select( $stmnt );

        foreach ( $comments as $comment ) {
            if ( $postHash[ $comment['ppId'] ] ) {
                $post = $postHash[$comment['ppId'] ];
                array_push(  $post['comment'], $comment );
            }
        }
    } else {
        $posts = [ 0 => [ 'pTxt' => 'No posts!'] ];    
    }
    $R['posts'] = $posts;
    require 'userFeed.htm';
}
function createSession( &$R, $DB ) {
    $R['sHash'] = sha1( time().$R['uEmail'] );
    $R['sTime'] = 'now()';
    $DB->replace('session', $R );
    setCookie( 'session', $R['sHash'] );
    debg( $R['sHash'] );
}
function userLoggedIn(&$R, $DB ) {
    if ( isset( $_COOKIE['session'] ) ) {
        $res = $DB->select("* from session where sHash='$_COOKIE[session]'");
        if ( $res != 0 ) {
            $R['sTime']= 'now()';
            $R['uId'] = $res[0]['uId'];
            debg('userLoggedIn update $R[uId]:'.$R['uId'] );
            $DB->update('session', $R, "sHash='$_COOKIE[session]'");
        }
        return 1;
    } else {
        if ( $R['func'] == 'userLogin' ) { /* logging in */
            $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
            if ( isset( $R['uEmail'] ) && isset( $R['uPassword0'] ) ) {
                $res = $DB->select("* from user where uEmail='$R[uEmail]'");
                if ( !( $res && password_verify( $R['uPassword0'], $res['uPassword'] ) ) ) {
                    return 0;
                }
            }
        } else if ( $R['func'] == 'userSignup' ) {
            $R['stmnt'] = $DB->insert( 'user', $R );
            $res = $DB->select("* from user where uEmail='$R[uEmail]'");
            $R['uId'] = $res[0]['uId'];
        } else {
            return 0;
        }
        // set cookie
        createSession( $R, $DB );
        return 1;
    }
    return 0;
}
function userLogin($R) {
    // serve login/signup-form
    require 'userLogin.htm';
    return 0;
}
function userSearch() {}
function userSignup( $R, $DB ) {
    // hash password
    // if ( $R['uPassword1'] != $R['uPassword2] ) {}
}

/* init */
require 'lib/Config.php';
require 'lib/Database.php';
$C = new Config();
$DB = new Database( $C );

global $R;
$R = array();
foreach ( $_REQUEST as $k=>$v ) { // moving to shorter $R
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}
$R['userImage'] = 'default.png';
$R['func'] = isset( $R['func'] ) ? $R['func'] : '';

/* **************************** */
/* routing */
/* **************************** */

debg( ">>>>1>>>>>>$R[func]<<<" );

userLoggedIn( $R, $DB ) || userLogin( $R, $DB ) || die();
debg( ">>>>2>>>>>>$R[func]<<<" );

// change to switch: https://www.php.net/manual/en/control-structures.switch.php
if ( $R['func'] == 'userPost' ) {
    echo "\nRoute userPost\n";
    userPost( $R, $DB );
} else if ( $R['func'] == 'userProfile' ) {
    userProfile( $R, $DB );
} else if ( $R['func'] == 'userComment' ) {
    userComment( $R, $DB );
} else {  // default userFeed
    userFeed( $R, $DB );
}

?>

