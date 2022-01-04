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
            if ( !isset($R['func']) || $R['func']=='userLogin' ) {
                $R['func'] = 'userEvent';
            }
            return 1;
        }
    } 
    return 0;
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
function userAccount(&$R, &$DB ) {
    // friend requests
    if ( isset( $R['subFunc'] ) && $R['subFunc'] == 'update' ) {
        if ( $_FILES['profileImage']['name'] > "0" ) {
            $imgType = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION)) ;
            $R['uImageId'] = substr( md5( $R['uId'] ), 5).'.'.$imgType; // $_FILES['profileImage']['name'];
            if ( is_uploaded_file($_FILES['profileImage']['tmp_name'] ) ) {
                // resize image if needed
                // https://stackoverflow.com/questions/14649645/resize-image-in-php
                copy($_FILES['profileImage']['tmp_name'], 'img/'.$R['uImageId'] );
            }
        }
        if ( isset( $R['uPassword'] ) && strlen( $R['uPassword'] ) > 0 ) {
            $R['uPassword'] = password_hash( $R['uPassword'] , PASSWORD_DEFAULT);
        } else {
            unset($R['uPassword'] );
        }
        $DB->update("user", $R, "uId=$R[uId]");    
        $R['user'] = $DB->selectOne("* from user where uId=$R[uId]");
    }
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
function userEvent( &$R, &$DB ) {
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
    $R['stmnt2']    = $stmnt;
    $posts = $DB->select( $stmnt );
    $R['posts']     = $posts; 
    $R['profileId'] = $R['uId'];
    require 'userFeed.htm'; // works also for userProfileFeed
}
function userProfile( &$R, &$DB) {
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
function userLostPass0( &$R, &$DB ) {}
/* present request received mail recipient */
function userLostPass1( &$R, &$DB ) {}
/* ************************* */
/* friend suggestions        */
/* ************************* */
function friendRequestDeny( &$R, &$DB ) {
    $DB->update('friend', [ 'relation' => 'relation & 7' ], "uId1='$R[uId1]' and uId2='$R[uId2]'");
    echo "OK";
}
function friendRelation( &$R, &$DB ) {
    global $C;
    debug( 'friendRelation subFunc:' . $R['subFunc'] . ' contId:'. $R['contId'] );
    $stmnt = [
        'block'     => "relation=1",            'unblock'   => "relation=relation&14",
        'follow'    => "relation=relation|2",   'unfollow'  => "relation=relation&13",
        'request'   => "relation=relation|8",   // this is curious as you may be blocking the one you are requesting
        'unrequest' => "relation=relation&7",   'unfriend'  => "relation=relation&0",
    ];    
    
    $stmnt = "update friend set ".$stmnt[$R['subFunc']]. " where uId1='$R[uId1]' and uId2='$R[uId2]'";
    $DB->query( $stmnt); 

    $reciprocal = [ // if A blocks or unfriends B, B's relation data vis a vis A must be updated
        'block'     => 'relation=relation&1',    
        'unfriend'  => 'relation=relation&3',
    ];
    if ( isset( $reciprocal[$R['subFunc']]) ) {
        $stmnt = "update friend set ".$reciprocal[$R['subFunc']]. " where uId1='$R[uId2]' and uId2='$R[uId1]'";
        $DB->query( $stmnt); 
    }
    // $o = $C->opposites[ $R['subFunc']];  // page is reloaded 
    // $op = isset( $C->prettyPrint[ $o ] ) ? $C->prettyPrint[$o] : $o;
    // echo "<span onclick=\"relation('$R[contId]','$o','$R[uId1]','$R[uId2]');\" class=\"button\"> $op </span>";
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
    $R['func'] = "userEvent"; // eventFeed uId
    createSession( $R, $DB );
    return 1;
}
/* *************************************** */
/* post in your own feed or someone else's */
/* *************************************** */
function userPost( &$R, &$DB ) {
    debug( 'userPost pTxt:'. $R['pTxt'] );
    // add post to user feed
    if ( $R['ppId'] == '' ) {
        $post = [
            'uId' => $R['user']['uId'],    'ruId' => $R['profileId'], // $R['user']['uId'],
            'rpId' => 'null',              'pTxt' => $R['pTxt'],
        ]; 
        $DB->insert( "post", $post ); /* untaint input */
        $DB->query( "update post set rpId=pId where rpId is NULL" );
    } else { 
        $post = $DB->selectOne( "* from post where pId=$R[ppId]");
        $post['ppId'] = $post['pId'];
        $post['pTxt'] = $R['pTxt'];
        $post['uId']  = $R['user']['uId'];
        unset( $post['pId'] );
        unset( $post['pTime']);
        if ( $post['ppId'] != '' ) {
            unset( $post['ruId'] );
        }    
        $DB->insert( 'post', $post ); /* untaint input */
    }
    echo "OK";
}
function postDelete(&$R, &$DB) {
    $stmnt = "pId, ppId from post where pId>='$R[pId]' and rpId in (select rpId from post where pId='$R[pId]' and uId='$R[uId]' )";
    debug( $stmnt );
    $posts = $DB->select( $stmnt );
    $prev = $posts[0]['ppId'];
    $dels = [];
    $pars[ $posts[0]['pId'] ] = 'X'; // $posts[0]['ppId'];
    $dels[] = $posts[0]['pId'];
    foreach ( $posts as &$p ) {
        if ( $pars[ $p['ppId'] ] ) {
            $pars[ $p['pId'] ] = $p['ppId'];
            $dels[] = $p['pId'];
        }
    }
    $dels[] = '-1';
    debug( print_r( $dels, true ) );
    $colls = implode(',', $dels ); 
    debug( $colls );
    $DB->delete( 'post', "pId in ($colls)");
    // check if user owns post
    // delete post and child posts
    echo "OK";
}

function userSearch(&$R, &$DB) {
    global $C;
    // dont show users that have blocked you
    // facebook allows you to search your own account but this complicates logic somewhat
    $R['stmnt'] = "* from user where uId!='$R[uId]' && (uLastName like '$R[search]%' or uFirstName like '$R[search]%')";
    $posts     = $DB->select( $R['stmnt'] );
    $relHash   = [];
    $relations = $DB->select( "* from friend where uId1='$R[uId]'");
    foreach ( $relations as $r ) {  
        $relHash[ $r['uId2'] ] = $r['relation'];
    }
    foreach ( $posts as &$p ) { // we need to skip posts who have blocked user
        foreach ( ['block','friend','follow','request'] as $type ) {
            $p[$type] = strpos( ' '.$relHash[$p['uId']], $type );
        }
    }
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
    $R['func'] = 'userEvent';
    createSession( $R, $DB );
    return 1;
}
/* ********************** */
/* init                   */
/* ********************** */
require 'lib/Config.php';
require 'lib/Database.php';

$C  = new Config();
$DB = new Database( $C );

$R = array( 
    'badLogin'  => 0,     'userImage' => 'img/profile0.png',
    'func'      => '',    'session'   => '',  );
foreach ( $_REQUEST as $k=>$v ) { // $R less to write than $_REQUEST
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}
/* ********************** */
/* entry                  */
/* ********************** */
checkLogin( $R, $DB ) || userLogin( $R, $DB ) || userSignup($R,$DB) || userEntry($R,$DB) || exit();

/* **************************** */
/* Routing, i.e. determine which function/model (view) to call  */
/* change to switch: https://www.php.net/manual/en/control-structures.switch.php  */
/* **************************** */
debug('route:'.$R['func']);
$allowed = [
    'userAccount' => 1, 'userLogout' => 1, 'userPost'=>1, 
    'userProfile' => 1, 'userSearch' => 1, 'friendRelation' => 1,
    'userEvent' => 1,   'postDelete' => 1 ];

if ( $allowed[ $R['func'] ] ) {
    $R['func']($R, $DB );
} else {
    debug('unallowed func: $R[func]'); /* silence */
}

?>