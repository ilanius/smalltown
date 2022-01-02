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
            $R['user'] = $DB->selectOne("* from user where uId='$R[uId]'");        
            $DB->update('session', $R, "sHash='$_COOKIE[session]'");
            return 1;
        }
    } 
    return 0;
}
function commentAdd(&$R, &$DB) {
    $DB->insert( 'post', $R );
    userEventFeed();
}
function commentDelete(&$R, &$DB) {
    // check if user owns comment
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
function postDelete(&$R, &$DB) {
    // check if user owns post
    // delete post
    // delete comments to post
}

function userAccount(&$R, &$DB ) {
    // friend requests
    require 'userAccount.htm';
}

function userEntry(&$R, &$DB ) {
    require 'userEntry.htm';
}
function buildTree( &$posts ) {
    $postHash = [];
    $tree     = [];
    foreach ( $posts as &$p ) {
        $postHash[ $p['pId'] ] = &$p; // reference
        $p['child'] = [];
        if ( $p['ppId'] > 0 ) {
            $parc = &$postHash[ $p['ppId'] ]['child']; // reference
            $parc[] = &$p;  // pushing reference - php copies alot :-(
        } else {
            $tree[] = &$p; // pushing roots
        }
    }
    return $tree; // a recursive tree structure
}
function userFeedEvent( &$R, &$DB ) {
    $stmnt   = "uId2 from friend where uId1='$R[uId]' and relation & 6";   // 6 == friend and follow
    $friends = $DB->select( $stmnt );
    array_push( $friends, [ 'uId2' => '-1'], ['uId2' => $R['uId'] ] );
    $fU      = $DB->implodeSelection( $friends, 'uId2' ); //  .",-1,$R[uId]";
 
    /* main posts for feed  */
    $stmnt = "rpId from post where ruId in ($fU) order by pTime desc limit 0,100";
    $R['stmnt1'] = $stmnt;
    $posts = $DB->select( $stmnt );
    $rpId  = $DB->implodeSelection( $posts, 'rpId');
    
    $stmnt = "* from post where rpId in ($rpId) order by pTime"; 
    $R['stmnt2'] = $stmnt;
    $posts = $DB->select( $stmnt );
    $R['posts'] = $posts; 
    $R['profileId'] = $R['uId'];
    require 'userFeed.htm'; // works also for userProfileFeed
}
function userFeedProfile( &$R, &$DB) {
    $R['profileId'] = isset( $R['profileId']) ? $R['profileId'] : $R['uId'];
    $stmnt = "rpId from post where ruId='$R[profileId]' order by pTime desc limit 0,100";
    $R['stmnt1'] = $stmnt;
    $posts = $DB->select( $stmnt );
    $posts[] = [ 'rpId' => '-1'];
    $rpId  = $DB->implodeSelection( $posts, 'rpId');
    
    $stmnt = "* from post where rpId in ($rpId) order by pTime"; 
    $R['stmnt2'] = $stmnt;
    $posts = $DB->select( $stmnt );
    $R['posts'] = $posts; // buildTree( $posts );
    require 'userFeed.htm';
}
function userLogout( &$R, &$DB ) {
    $DB->delete("session", "uId='$R[uId]'");
    setCookie('session', '');
    require 'userEntry.htm';
}
/* present a page with a button */
function userLostPass0( &$R, &$DB ) {}
/* present request received mail recipient */
function userLostPass1( &$R, &$DB ) {}
/* ************************* */
/* friend suggestions        */
/* ************************* */

function userBlock( &$R, &$DB ) { 
    $R['relation'] = 'block';
    $DB->replace('friend', $R );
    echo "OK";
}
function userUnBlock( &$R, &$DB ) {
    $DB->update('friend', [ 'relation' => 'relation & 14' ], "uId1='$R[uId1]' and uId2='$R[uId2]'");
    echo "OK";
}
function friendRequest( &$R, &$DB ) {
    // cases
    // a) id2 blocks id1
    // c) id1 block1 id2 ==> remove block
//             1         2        4          8
    // SET('block', 'follow', 'friend', 'request')
    // insert into friend values ( 4, 3, 7, 'block,follow,friend,request' );
    $f12    = "$R[uId1],$R[uId2]";
    $check1 = $DB->selectOne("* from friend where (uId1 in ($f12) and uId2 in ($f12) and relation & 5)");  // friend =4 block =1
    if ( $check1 ) return; // already friends or uid2 has blocked uid1 or uid1 has blocked uid2(!)
    $check2 = $DB->selectOne("* from friend where (uId1='$R[uId1]' and uId2='$R[uId2]'");
    if ( $check2 ) { 
        $DB->update('friend', [ 'relation' => 'relation | 8' ], "uId1='$R[uId1]' and uId2='$R[uId2]'");
    } else {
        $R['relation'] ='request';
        $DB->insert( 'friend', $R) ;
    }
    echo "OK";
}
/* ************************* */
/* friend request deny       */
/* ************************* */
function friendRequestDeny( &$R, &$DB ) {
    $DB->update('friend', [ 'relation' => 'relation & 7' ], "uId1='$R[uId1]' and uId2='$R[uId2]'");
    echo "OK";
}
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
    $R['user'] = $user;
    $R['func'] = 'userEventFeed'; // eventFeed uId
    createSession( $R, $DB );
    return 1;
}
/* ************************************** */
/* post in your own feed or someone elses */
/* ************************************** */
function userPost0( &$R, &$DB ) {
    debug( 'userPost0 pTxt:'. $R['pTxt'] );
    // add post to user feed
    if ( $R['ppId'] == '' ) {
        $post = [
            'uId' => $R['user']['uId'],
            'ruId' => $R['profileId'], // $R['user']['uId'],
            'rpId' => 'null',
            'pTxt' => $R['pTxt'],
        ]; 
        $DB->insert( 'post', $post ); /* untaint input */
        $DB->query( "update post set rpId=pId where rpId is NULL" );
    } else { 
        $post = $DB->selectOne( "* from post where pId=$R[ppId]");
        $post['ppId'] = $post['pId'];
        // $post['rpId'] = $post['pId'];
        $post['pTxt'] = $R['pTxt'];
        $post['uId'] = $R['user']['uId'];
        unset( $post['pId'] );
        unset( $post['pTime']);
        if ( $post['ppId'] != '' ) {
            unset( $post['ruId'] );
        }    
        $DB->insert( 'post', $post ); /* untaint input */
    }
    debug( print_r( $post, true ) );
    debug( 'userPost0. post:'. print_r( $post, true ) );
    echo "OK";
    return;
}
function userSearch(&$R, &$DB) {
    // dont show users that have blocked uId
    $R['stmnt'] = "* from user where uLastName like '$R[search]%' or uFirstName like '$R[search]%'";
    $posts = $DB->select( $R['stmnt'] );
    // concat res1 and res2
    require 'userSearch.htm';
}
function userSignup( &$R, &$DB ) {
    if ( $R['func'] != 'userSignup' ) { return 0; }   
    $R['uName'] = isset( $R['uName'] ) ? $R['uName'] : 'nada';
    $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
    $R['stmnt'] = $DB->insert( 'user', $R );
    $user = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
    $R['uId'] = $user['uId'];
    $R['user'] = $user;
    $R['func'] = 'userFeedEvent';
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
    'badLogin'  => 0,     'userImage' => 'img/profile0.png',
    'func'      => '',    'session'   => '',  );
foreach ( $_REQUEST as $k=>$v ) { // $R less to write than $_REQUEST
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}

/* ********************** */
/* entry */
/* ********************** */
debug('entry func:' . $R['func']);
checkLogin( $R, $DB ) || userLogin( $R, $DB ) || userSignup($R,$DB) || userEntry($R,$DB) || exit();

/* **************************** */
/* routing */
/* change to switch: https://www.php.net/manual/en/control-structures.switch.php  */
/* **************************** */
debug('route:'.$R['func']);
$R['func0'] = $R['func']; /* for use later */


if ( $R['func'] == 'userAccount') {
    userAccount($R, $DB );
} else if ( $R['func'] == 'userLogout') {
    userLogout($R, $DB );
} else if ( $R['func'] == 'userPost' ) {
    userPost( $R, $DB );
} else if ( $R['func'] == 'userPost0' ) {
    userPost0( $R, $DB );
}  else if ( $R['func'] == 'userComment' ) {
    userComment( $R, $DB );
} else if ( $R['func'] == 'userFeedProfile' ) {
    userFeedProfile( $R, $DB );
} else if ( $R['func'] == 'userSearch' ) {
    userSearch( $R, $DB );
} else {  // default userEVentFeed
    userFeedEvent( $R, $DB );
}

?>
