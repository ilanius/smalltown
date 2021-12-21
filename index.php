<?php
/* ******************************* */
/* smalltown - facebook light      */
/* no ads                          */
/* ******************************* */
function checkLogin(&$R, &$DB ) {
    if ( isset( $_COOKIE['session'] ) ) {
        $session = $DB->selectOne("* from session where sHash='$_COOKIE[session]'");
        if ( $session ) {
            $R['sTime']= 'now()';
            $R['uId'] = $session['uId'];
            $DB->update('session', $R, "sHash='$_COOKIE[session]'");
            return 1;
        }
    } 
    return 0;
}
function commentAdd(&$R, &$DB) {
    $DB->insert( 'post', $R );
    userFeed();
}
function commentDelete(&$R, &$DB) {
    // check if user owns comment
}
function commentComplement( &$posts, &$DB ) {
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
}
function createSession( &$R, &$DB ) {
    $R['sHash'] = sha1( time().$R['uEmail'] );
    $R['sTime'] = 'now()';
    $DB->replace('session', $R );
    setCookie( 'session', $R['sHash'] );
}
function debug( $mess ) {
    error_log( $mess."\n" , 3, './debugLog.txt');
}
function postAdd(&$R, &$DB) {  /* post and comments handled here */
    $DB->insert( 'post', $ord );
    userFeed();
}
function postDelete(&$R, &$DB) {
    // check if user owns post
    // delete post
    // delete comments to post
}
function userEntry(&$R, &$DB ) {
    require 'userEntry.htm';
}
function userFeed( &$R, &$DB ) {
    $stmnt   = "uId2 from friend where uId1='$R[uId]' and relType in ('friend', 'follow')"; 
    $friends = $DB->select( $stmnt );
    array_push( $friends, '-1' );
    $fU = implode( ',', $friends );
    /* main posts for feed  */
    $stmnt = "* from post where uId in ($fU) and ppId is null order by pTime desc limit 0,100"; 
    $posts = $DB->select( $stmnt );
    if ( sizeof ( $posts ) > 0 ) {
        commentComplement( $posts, $DB );        
    } else {
        $posts = [ 0 => [ 'pTxt' => 'No posts!'] ];    
    }
    $R['stmnt'] = $stmnt;
    $R['posts'] = $posts;
    require 'userFeed.htm';
}
function userLogout( &$R, &$DB ) {
    $DB->delete("session", "uId='$R[uId]'");
    setCookie('session', '');
    require 'userLogout.htm';
}

/* ************************* */
/* friend suggestions        */
/* ************************* */

/* ************************* */
/* friend request            */
/* ************************* */

/* ************************* */
/* friend request deny       */
/* ************************* */

/* ************************* */
/* user block                */
/* ************************* */
function userLogin(&$R, &$DB) {
    if ( $R['func'] != 'userLogin' ) { return 0; }
    /* logging in */
    $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
    if ( isset( $R['uEmail'] ) && isset( $R['uPassword0'] ) ) {
        $user = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
        if ( !( $user && password_verify( $R['uPassword0'], $user['uPassword'] ) ) ) {
            $R['badLogin'] = 'bad login';
            return 0;
        }
        $R['uId'] = $user['uId'];
    }
    $R['func'] = 'userFeed';
    createSession( $R, $DB );
    return 1;
}
function userProfile( &$R, &$DB) {
    $user    = $DB->select("* from user where uId='$R[uId]'")[0];
    $friends = $DB->select("* from user where uId in (select uId2 from friend where relType='friend' && uId1='$user[uId]')");
    $posts   = $DB->select("* from post where uId = '$R[uId]' order by pTime desc limit 0, 100");
    commentComplement( $posts, $DB );
    require 'userProfile.php';
}
function userSearch(&$R, &$DB) {}
function userSignup( &$R, &$DB ) {
    if ( $R['func'] != 'userSignup' ) { return 0; }   
    $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
    $R['stmnt'] = $DB->insert( 'user', $R );
    $user = $DB->select("* from user where uEmail='$R[uEmail]'")[0];
    $R['uId'] = $user['uId'];
    $R['func'] = 'userFeed';
    createSession( $R, $DB );
    return 1;
}

/* ********************** */
/* init */
/* ********************** */
require 'lib/Config.php';
require 'lib/Database.php';
$C  = new Config();
$DB = new Database( $C );

global $R;
$R = array( 
    'badLogin' => 0,     'userImage' => 'img/profile0.png',
    'func'      => '',   'session'   => '',  );
foreach ( $_REQUEST as $k=>$v ) { // $R less to write than $_REQUEST
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}

/* ********************** */
/* entry */
/* ********************** */
debug('entry');
checkLogin( $R, $DB ) || userLogin( $R, $DB ) || userSignup($R,$DB) || userEntry($R,$DB) || exit();

/* **************************** */
/* routing */
/* change to switch: https://www.php.net/manual/en/control-structures.switch.php  */
/* **************************** */
debug('route:'.$R['func']);
if ( $R['func'] == 'userPost' ) {
    userPost( $R, $DB );
} else if ( $R['func'] == 'userProfile' ) {
    userProfile( $R, $DB );
} else if ( $R['func'] == 'userComment' ) {
    userComment( $R, $DB );
} else {  // default userFeed
    userFeed( $R, $DB );
}

?>

