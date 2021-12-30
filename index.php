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
function commentComplement( &$posts, &$DB ) {
    $cPostIds = [-1];
    foreach ( $posts as $post ) {
        $postHash[ $post['pId'] ] = $post;
        array_push( $cPostIds, $post['pId'] );
    }
    // comments
    $cpi = implode( ',', $cPostIds );
    // $stmnt = "* from post where uId in ($fU) and ppId in ($cpi) order by pTime desc limit 0,100"; debug( 'C userEventFeed.stmnt:'.$stmnt );
    $stmnt = "* from post where ppId in ($cpi) order by pTime desc limit 0,100"; debug( 'C userEventFeed.stmnt:'.$stmnt );
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
function postDelete(&$R, &$DB) {
    // check if user owns post
    // delete post
    // delete comments to post
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
    return $tree; // finally our recursive structure
}
function userEventFeed( &$R, &$DB ) {
    $stmnt   = "uId2 from friend where uId1='$R[uId]' and relType in ('friend', 'follow')"; 
    $friends = $DB->select( $stmnt );
    $fU      = $DB->implodeSelection( $friends, 'uId2' ) .",-1,$R[uId]";
    debug( '-->fU:'.$fU );
    /* main posts for feed  */

    $stmnt = "rpId from post where ruId in ($fU) order by pTime desc limit 0,100";
    $R['stmnt1'] = $stmnt;
    $posts = $DB->select( $stmnt );
    $rpId  = $DB->implodeSelection( $posts, 'rpId');
    debug( 'userEventFeed 2:'.$stmnt );

    $stmnt = "* from post where rpId in ($rpId) order by pTime"; 
    $R['stmnt2'] = $stmnt;
    debug( 'userEventFeed 3:'.$stmnt );
    $posts = $DB->select( $stmnt );
   
    if ( sizeof ( $posts ) > 0 ) {
        $R['posts'] = buildTree( $posts );
        debug( 'posts:'. print_r( $R['posts'], true ) );
    } else {
        $R['posts'] = [ 0 => [ 'pTxt' => 'Nothing to show yet!'] ];    
    } 
    require 'userFeed.htm'; // works also for userProfileFeed
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
            'ruId' => $R['user']['uId'],
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
/*
function userPost( &$R, &$DB ) {
    debug( 'userPost pTxt:'. $R['pTxt'] );
    // add post to user feed
    debug( $R['uId'].' <---------' );
    $DB->insert( 'post', $R); // untaint input 
    if ( $R['func0'] == 'userProfileFeed' ) {
        userProfileFeed( $R, $DB );
    } else userEventFeed( $R, $DB); // or userProfileFeed
}
*/
function userProfileFeed( &$R, &$DB) {
    $user    = $DB->select("* from user where uId='$R[uId]'")[0];
    $friends = $DB->select("* from user where uId in (select uId2 from friend where relType='friend' && uId1='$user[uId]')");
    $posts   = $DB->select("* from post where uId = '$R[uId]' order by pTime desc limit 0, 100");
    commentComplement( $posts, $DB );
    require 'userProfile.php';
}
function userSearch(&$R, &$DB) {}
function userSignup( &$R, &$DB ) {
    if ( $R['func'] != 'userSignup' ) { return 0; }   
    $R['uName'] = isset( $R['uName'] ) ? $R['uName'] : 'nada';
    $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
    $R['stmnt'] = $DB->insert( 'user', $R );
    $user = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
    $R['uId'] = $user['uId'];
    $R['user'] = $user;
    $R['func'] = 'userEventFeed';
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
debug('entry func:' . $R['func']);
checkLogin( $R, $DB ) || userLogin( $R, $DB ) || userSignup($R,$DB) || userEntry($R,$DB) || exit();

/* **************************** */
/* routing */
/* change to switch: https://www.php.net/manual/en/control-structures.switch.php  */
/* **************************** */
debug('route:'.$R['func']);
$R['func0'] = $R['func']; /* for use later */
if ( $R['func'] == 'userLogout') {
    userLogout($R, $DB );
} else if ( $R['func'] == 'userPost' ) {
    userPost( $R, $DB );
} else if ( $R['func'] == 'userPost0' ) {
    userPost0( $R, $DB );
}  else if ( $R['func'] == 'userComment' ) {
    userComment( $R, $DB );
} else if ( $R['func'] == 'userProfileFeed' ) {
    userProfileFeed( $R, $DB );
} else {  // default userEVentFeed
    userEventFeed( $R, $DB );
}

?>
