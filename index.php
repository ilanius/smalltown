<?php
/* *********************************** */
/* Smalltown a.k.a facebook light      */
/* no ads                              */
/* *********************************** */
function checkLogin(&$R, &$DB ) {
    if ( isset( $_COOKIE['session'] ) ) {
        $session = $DB->selectOne("* from session where sHash='$_COOKIE[session]'");
        if ( $session ) {
            $R['sTime']= 'now()';
            $R['uId'] = $session['uId']; 
            $R['user'] = $DB->selectOne("* from user where uId='$R[uId]'");        
            if ( !isset( $R['user']['uImageId']) || strlen($R['user']['uImageId'] ) < 3 ) { /* if user has not yet uploaded image we set default here */
                $R['user']['uImageId'] = $R['userImage'];
            } 
            $DB->update('session', $R, "sHash='$_COOKIE[session]'");
            if ( !isset($R['func']) || strlen($R['func'])==0 || $R['func']=='userLogin' ) {            
                $R['func'] = 'userEvent';
            }
            return 1;
        }
    }        
    return 0;
}
/* ************************************************** */
/* We set session cookie here 
/* ************************************************** */
function createSession( &$R, &$DB ) {
    $R['sHash'] = sha1( time().$R['uEmail'] );
    $R['sTime'] = 'now()';
    $DB->replace('session', $R );
    setCookie( 'session', $R['sHash'] );
}
function debug( $mess ) {
    error_log( $mess."\n" , 3, './debugLog.txt');
}

/* ****************************************************** */
/* This method will allow us to add customized template files
/* that will supersede the core template files
/* i.e. entry.htm supersedes entry0.htm
/* search.htm > search0.htm and so on
/* Templates can only access system variables via $R
/* ****************************************************** */
function requir0( $fileName, &$R ) { 
    file_exists( $fileName.'.htm') ? require $fileName.'.htm' : require $fileName.'0.htm';
}

/* ******************************************************************************** */
/* buildTree is used by userEventFeed and userProfileFeed                           */
/* to build a tree of posts. TODO: just send JSON of SQL results to client          */
/* and let client spend cpu building tree.
/* ******************************************************************************** */
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


/* ******************************************************************* */
/* The following code is used to manipulate the relation column
/* in table friend. The database column relation uses 8 bytes per row. 
/* Set values: 'block' (=1 ), 'follow' (=2), 'friend' (=4), 'request' (=8), 
/* occupies one bit each. Bits are set and unset using | (or) or & /* (and) operations.
/* For example setting friend and unsetting request in one operation 
/* can be done with the following expression: (relation|4)&7  where 
/* | sets a bit and & unsets the bits that are not overlapping the bits
/* of 7, i.e. 111 in binary
/* ******************************************************************* */
function friendRelation( &$R, &$DB ) {
    global $C;
    $action = [
        'requestAccept' => "relation=(relation|4)&7",
        'block'         => "relation=1",            'unblock'   => "relation=relation&14",
        'follow'        => "relation=relation|2",   'unfollow'  => "relation=relation&13",
        'request'       => "relation=relation|8",   // this is curious as you may be blocking the one you are requesting
        'unrequest'     => "relation=relation&7",   'unfriend'  => "relation=relation&0",
    ];    
    if ( isset( $action[$R['subFunc'] ] ) ) {
        $check = $DB->select("uId1 from friend where uId1='$R[uId1]' and uId2='$R[uId2]'");
        if ( ! $check ) {
            $check = $DB->query("replace friend values ('$R[uId1]', '$R[uId2]', '0')");  
        }
        $stmnt = "update friend set ".$action[$R['subFunc']]. " where uId1='$R[uId1]' and uId2='$R[uId2]'";    
        $DB->query( $stmnt); 
    }
    $reaction = [ // if A blocks or unfriends B, B's relation data vis a vis A must be updated
        'requestAccept' => "relation=(relation|4)&7",
        'requestDeny'   => "relation=relation&7",
        'block'         => 'relation=relation&1',    
        'unfriend'      => 'relation=relation&3',
    ];
    if ( isset( $reaction[$R['subFunc']]) ) {    
        $check = $DB->select("uId1 from friend where uId1='$R[uId2]' and uId2='$R[uId1]'");
        if ( ! $check ) {
            $check = $DB->query("replace friend values ('$R[uId2]', '$R[uId1]', '0')");  
        }
        $stmnt = "update friend set ".$reaction[$R['subFunc']]. " where uId1='$R[uId2]' and uId2='$R[uId1]'";
        $DB->query( $stmnt); 
    }
    $DB->query( "commit");
}
function expressRelation( &$R, &$p ) {
    global $C;
    $optss = [  /* block, friend, request are mutually exclusive options. Follow will always be an option */
        'block'   => [ 'block', 'follow', 'request' ], /* You can follow someone you have blocked, i.e. if the person has not blocked you */
        'friend'  => [ 'block', 'follow', 'friend'  ],
        'request' => [ 'block', 'follow', 'request' ] ];
    $opts = $optss['block'];
    foreach ( $optss as $key => $val ) {
        if ( isset( $p[$key] ) && $p[$key] ) {
            $opts = $val;  break;
        }
    }
    $str = '';
    foreach ( $opts as $o ) {
        $o = isset( $p[$o] ) && $p[$o] ? $C->opposites[ $o ] : $o;
        $op = isset( $C->prettyPrint[$o] ) ? $C->prettyPrint[$o] : $o;
        $str = $str . <<<html
          <button class="relButton" onclick="changeRelation('row_$p[uId]','$o','$R[uId]','$p[uId]')"> $op </button>
html;
    }
    return $str;
}
function changeRelation( &$R, &$DB ) {
    friendRelation( $R, $DB );
    $p = $DB->selectOne("* from user where uId='$R[uId2]'");
    $r = $DB->selectOne( "* from friend where uId1='$R[uId1]' and uId2='$R[uId2]'");     
    foreach ( ['block','friend','follow','request'] as $type ) {
        $p[$type] = strpos( ' '.$r['relation'], $type );
    }
    echo expressRelation( $R, $p );
}
/* ********************************************************************** */
/* This function allows you to post in your own feed or in a friends feed */
/* ********************************************************************** */
function postSubmit( &$R, &$DB ) {
    // Add post to user feed
    if ( $R['ppId'] == '' ) {
        $post = [
            'uId' => $R['user']['uId'],    'ruId' => $R['profileId'], // $R['user']['uId'],
            'rpId' => 'null',              'pTxt' => $R['pTxt'],
            'comment' => '',
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
        $post['emotion'] = '';
        if ( $post['ppId'] != '' ) {
            unset( $post['ruId'] );
        }
        $DB->insert( 'post', $post ); /* untaint input */
    }
    // auto_increment is convenient but we need to know pId
    // https://www.w3schools.com/sql/func_mysql_last_insert_id.asp
    $rslt = $DB->selectOne("last_insert_id()");
    $post['pId'] = $rslt['last_insert_id()'];
    echo json_encode( $post );
}

/* ****************************************************************************** */
/* The algorithm below is not easy to understand so it has been heavily commented */
/* ****************************************************************************** */
function postDelete(&$R, &$DB) {
    /* We want to delete element with post id pId.
    /* We select pId and ppId where post id > pId  >= we select parent id and root parent id
    /* children of pId have post ids > pId and we make certain we own pId by selecting for uId as wel */
    $stmnt = "pId, ppId from post where pId>='$R[pId]' and rpId in (select rpId from post where pId='$R[pId]' and uId='$R[uId]' )";
    $posts = $DB->select( $stmnt );   // sizeof ( $posts ) may be empty if uId is wrong 

    $prev = $posts[0]['ppId'];
    $dels = [];                       // ids of posts we want to delete 
    $pars[ $posts[0]['pId'] ] = 'X';  // $posts[0]['ppId']; // parent of post pId is X
    $dels[] = $posts[0]['pId'];       // root post with post id = pId
    foreach ( $posts as &$p ) {
        if ( $pars[ $p['ppId'] ] ) {  // If parent exists we delete this post
            $pars[ $p['pId'] ] = $p['ppId']; // We add this post to chain of posts to delete
            $dels[] = $p['pId'];      // We add post id to our list of deletions
        }
    }
    $dels[] = '-1';                   // If list is empty code will crash so we add dummy value here
    $colls = implode(',', $dels ); 
    $DB->delete( 'post', "pId in ($colls)");
    echo "OK";
}
function postEmotion( &$R, &$DB ) {
    $post    = $DB->selectOne( "* from post where pId='$R[pId]'");
    [$emotion, $uId, $emot, $pId ] = [ &$post['emotion'], $R['uId'], $R['emot'], $R['pId'] ];
    /* ******************************************* */
    /* toggle emotion                              */
    /* format for emotion /n1:\d+,p1:\d+,p2:\d+,/  */
    /* n1 == sad, p1 == like, p2 == smiley         */
    /* ******************************************* */
    $emotion = $emotion ? $emotion : 'n1:p1:p2:';
    $set     = !preg_match( "/$emot:(\d+,)*($uId),/", $emotion );
    $emotion = preg_replace( "/([:,])$uId,/", '$1', $emotion );
    if ( $set ) {
        $emotion = preg_replace( "/$emot:/", "$emot:$uId,", $emotion );
    }
    $post['emotion'] = $emotion;
    debug( 'emotion:'. $emotion );
    $DB->update( 'post', $post /*[ 'emotion' => '$emotion' ]*/ , "pId='$pId'" );
    echo json_encode( $post );
}
function userLostPass0( &$R, &$DB ) {
    if ( $R['func'] != 'userLostPass0' ) return 0;
    $body = '';
    if ( isset( $R['uEmail'] ) ) {
        $U = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
        if ( $U ) {
            $U['uPassword0'] = urlencode( $U['uPassword'] );
            $to_email = "leonard.ilanius@gmail.com";
            $subject = "Password reset";
            $body = '';
            $body = $body . <<<html
         "Hi,\n This is test email send by PHP Script. 
        <a href="?func=userLostPass1&uEmail=$U[uEmail]&uPassword0=$U[uPassword0]"> Access Token </a>
html;
            $headers = "From: admin@smalltown.com";
        } else {
            $body = '';
        }
        /* ***************************************************** */
        /* we need a working e-mail server for this line to work */
        /* mail($R['uEmail'], $subject, $body, $headers);        */
        /* ***************************************************** */
        echo $body; 
        return 1;
    } 
    return 0;
}

/* ********************************************************************** */
/* Feed of a logged in user                                               */
/* Contains a selecttion (reverse time order) of posts by friends (and your own!?)   */
/* function userEvent delivers page. Function userEventFeed delivers data */
/* for page in JSON format                      
/* ********************************************************************** */
function userEventFeed( &$R, &$DB ) {
    $stmnt        = "uId2 from friend where uId1='$R[uId]' and relation & 2";   // 2 == follow 4 == friend
    $friends      = $DB->select( $stmnt );
    array_push( $friends, [ 'uId2' => '-1'], ['uId2' => $R['uId'] ] );
    $fU           = $DB->implodeSelection( $friends, 'uId2' ); //  .",-1,$R[uId]";
    $feedPosition = $R['feedPosition'];
    $stmnt        = "rpId from post where ruId in ($fU) order by pTime desc limit $feedPosition,100";
    $posts        = $DB->select( $stmnt );
    if ( sizeof( $posts ) > 0 ) {
        $rpId  = $DB->implodeSelection( $posts, 'rpId');
        $stmnt = "* from post where rpId in ($rpId) order by pTime"; 
        $posts = $DB->select( $stmnt ); 
        // we need to augment posts with post.uId uImageId and post.ruId uImageId
        // Tricky
        // u1
        //   u2         <============= not yet complete
        //     u3
        $coll = [];
        $DB->collection( $posts, 'uId',  $coll );
        $DB->collection( $posts, 'ruId', $coll );
        $DB->collection( $posts, 'ppId', $coll );
        $ids = implode( ',', array_keys($coll) );
        debug('ids:'. $ids );
        $uReg  = $DB->getReg("uId, uImageId,uFirstName,uLastName from user where uId in ($ids)", 'uId');
        debug( print_r( $uReg, 1) );
        foreach ( $posts as &$p ) {
            $p['uImageId'] = $uReg[ $p['uId'] ]['uImageId'];
            $p['uFirstName'] = $uReg[ $p['uId'] ]['uFirstName'];
            $p['uLastName'] = $uReg[ $p['uId'] ]['uLastName'];
            
            if ( $p['ppId'] && isset( $uReg[ $p['ppId']] ) ) {
                $u = $uReg[ $p['ppId'] ];
                $p['puImageId']   = isset($u['uImageId'])   ? $u['uImageId']   : 'default';    
                $p['puFirstName'] = isset($u['uFirstName']) ? $u['uFirstName'] : '-';    
                $p['puLastName']  = isset($u['uLastName'])  ? $u['uLastName']  : '-';    
            }
        }
        debug( print_r( $posts, 1 ) );
        $tree  = buildTree( $posts );
        // if $feedPosition > 0 then we query postChanges table
        echo json_encode( $tree );
    } else {
        echo "[]";
    }
}
function userEvent( &$R, &$DB ) {
    $R['profile']   = &$R['user'];
    $R['profileId'] = $R['uId'];
    $R['feedType']  = 'userEventFeed';
    requir0( 'feed', $R );
}
/* ********************************************************************* */
/* Function userProfileFeed delivers your own or a friends posts         */
/* sorted in reverse chronological order                                 */
/* Function userProfile delivers page. Function userProfileFeed delivers */
/* JSON data to page                                                     */
/* ********************************************************************* */
function userProfileFeed( &$R, &$DB ) {
    $feedPosition = $R['feedPosition'];
    // We may need to reconsider hard coding the number 100 here
    $stmnt = "rpId from post where ruId='$R[profileId]' order by pTime desc limit $feedPosition,100";
    $posts = $DB->select( $stmnt );
    if ( sizeof( $posts ) == 0 ) {
        echo "[]";
        return;
    }
    $rpId  = $DB->implodeSelection( $posts, 'rpId');    
    $stmnt = "* from post where rpId in ($rpId) order by pTime"; 
    $posts = $DB->select( $stmnt );
    $tree = buildTree( $posts );
    echo json_encode( $tree );
}
function userProfile( &$R, &$DB) {
    $R['profileId'] = isset( $R['profileId']) ? $R['profileId'] : $R['uId'];
    $p = 0; $stmnt = '';
    if ( $R['profileId'] != $R['uId'] ) { 
        $stmnt = "* from friend inner join user on user.uId=friend.uId2 where friend.uId1='$R[uId]' and friend.uId2='$R[profileId]'";       
        $p = $DB->selectOne( $stmnt );
    } 
    debug('userProfile stmnt:'. $stmnt );
    if ( $p == 0 ) { // friend may lack values for profileId
        $stmnt = "* from user where uId='$R[profileId]'";
        $p = $DB->selectOne( $stmnt );
    }
    if ( isset( $p['relation'] ) ) { // compare userSearch
        foreach ( ['block','friend','follow','request'] as $type ) {
            $p[$type] = strpos( ' '.$p['relation'], $type );
        }
    }
    debug('userProfile:'. print_r( $p, 1 ) );
    if ( !isset($p['uImageId']) && strlen( $p['uImageId'] ) < 3 ) {
        $p['uImageId'] = 'profileDefaultImage.png';
    }
    $R['profile'] = &$p;
    $R['feedType'] = 'userProfileFeed';
    requir0( 'feed', $R );
}
/* ********************************************************************** */

function userAccount(&$R, &$DB ) {
    if ( isset( $R['subFunc'] ) && $R['subFunc'] == 'update' ) {
        $fTmp    = $_FILES['profileImage']['tmp_name'];
        $fName   = $_FILES['profileImage']['name'];
        if ( strlen($fTmp) > 0 && is_uploaded_file($fTmp) && preg_match( "/(jpeg|jpg|png|gif)$/",$fName)) { // Can only handle jpg,gif,png
            $iType = strtolower(pathinfo($fName, PATHINFO_EXTENSION)) ;
            // In order to prevent harvesting of images by foreign machines we hash the filename, but we keep the filetype
            $R['uImageId'] = substr( md5( $R['uId'].$iType.$fTmp ), 5).'.'.$iType; // sufficiently complicated
            // https://stackoverflow.com/questions/14649645/resize-image-in-php
            // https://www.php.net/manual/en/function.imagecreatefromjpeg.php
            $iType = $iType == 'jpg' ? 'jpeg' : $iType; 
            $image = ('imagecreatefrom'.$iType)($fTmp); 
            // https://www.php.net/manual/en/function.getimagesize.php
            // https://www.php.net/manual/en/function.imagescale.php        
            $imgResized = imagescale($image , 128, 128); // brutal resizing to 128x128 format
            if ( preg_match("/(png)$/", $iType )) {
                imagealphablending($imgResized, false);
                imagesavealpha($imgResized, true);
            }
            ('image'.$iType)( $imgResized, 'img/'.$R['uImageId'] );   // perhaps all images should be saved as jpg   
        }        
        if ( isset( $R['uPassword'] ) && strlen( $R['uPassword'] ) > 0 ) {
            $R['uPassword'] = password_hash( $R['uPassword'] , PASSWORD_DEFAULT);
        } else {
            unset($R['uPassword'] );
        }
        $DB->update("user", $R, "uId=$R[uId]");    
        $R['user'] = $DB->selectOne("* from user where uId=$R[uId]");
    }

    // List of friend requests
    $friendRequest      = $DB->select("uId1 from friend where uId2='$R[uId]' and relation&8");
    array_push( $friendRequest, ['uId1' => '-1'] ); // in case request is empty
    $fId                = $DB->implodeSelection( $friendRequest, 'uId1' );
    $stmnt              = "* from user where uId in ($fId)";
    $R['friendRequest'] = $DB->select( $stmnt );

    // List of friends
    $stmnt              = "* from friend inner join user on user.uId=friend.uId2 where friend.uId1=$R[uId] and friend.relation&4";
    $friend             = $DB->select( $stmnt); 
    array_push( $friend, ['uId2' => '-1'] ); // in case request is empty
    $fId                = $DB->implodeSelection( $friend, 'uId2' );
    $stmnt              = "* from user where uId in ($fId)";
    $R['friend']        = $DB->select( $stmnt );

    // TODO: Friend suggestion
    $R['friendSuggestion'] = [];
    if ( isset( $R['user']['uYear'] ) ) {
        // not yet implemented
    }
    requir0( 'account', $R );
}
function userEntry(&$R, &$DB ) {  // login signup page
    requir0( 'entry', $R );
}
function userSearch(&$R, &$DB) {
    global $C;
    // TODO: dont show users that have blocked you
    // Facebook allows you to search your own account but this complicates logic somewhat
    $R['stmnt'] = "* from user where uId!='$R[uId]' && (uLastName like '$R[search]%' or uFirstName like '$R[search]%')";
    $R['posts'] = $DB->select( $R['stmnt'] ); // copies
    $relHash   = [];
    $relations = $DB->select( "* from friend where uId1='$R[uId]'");
    foreach ( $relations as $r ) {  
        $relHash[ $r['uId2'] ] = $r['relation'];
    }
    foreach ( $R['posts'] as &$p ) { // we need to skip posts who have blocked user
        foreach ( ['block','friend','follow','request'] as $type ) {
            if ( isset( $relHash[ $p['uId'] ] ) ) {
                $p[$type] = strpos( ' '.$relHash[$p['uId']], $type );
            } else {
                $p[$type] = 0;
            }
        }
    }
    requir0( 'search', $R ); 
}
function userLogin(&$R, &$DB) {
    $R['error'] = 'BadLogin'; 
    if ( ! ( isset( $R['uEmail'] ) && isset( $R['uPassword0'] ) ) ) {
        $R['error'] = '';
        return 0;
    }
    $user = $DB->selectOne("* from user where uEmail='$R[uEmail]'");        
    if ( $R['func'] == 'userLostPass1' ) {
        if ( !( $user && $R['uPassword0'] == $user['uPassword'] ) ) {
            return 0;
        }
        $R['func'] = "userAccount"; // eventFeed uId
        $R['updateNow'] = 1;
    } else if ( $R['func'] == 'userLogin' ) { 
        $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
        if ( !( $user && password_verify( $R['uPassword0'], $user['uPassword'] ) ) ) {
            return 0;
        }
        $R['func'] = "userEvent"; // eventFeed uId
    } else {
        return 0;
    }
    $R['uId'] = $user['uId'];
    $R['user'] = $user;
    if ( !isset( $R['user']['uImageId']) || strlen($R['user']['uImageId'] ) < 3 ) { /* if user has not yet uploaded image we set default here */
        $R['user']['uImageId'] = $R['userImage'];
    }
    createSession( $R, $DB );
    return 1;
}
function userLogout( &$R, &$DB ) {
    $DB->delete("session", "uId='$R[uId]'");
    setCookie('session', '');
    requir0( 'entry', $R );
}
function userSignup( &$R, &$DB ) {
    if ( $R['func'] != 'userSignup' ) { return 0; }  
    $user           = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
    if ( $user ) { 
        $R['error'] = 'UserExists'; 
        return 0; 
    } 
    $R['uName']     = isset( $R['uName'] ) ? $R['uName'] : 'nada';
    $R['uPassword'] = password_hash( $R['uPassword0'] , PASSWORD_DEFAULT);
    $R['stmnt']     = $DB->insert( 'user', $R );
    $user           = $DB->selectOne("* from user where uEmail='$R[uEmail]'");
    $R['uId']       = $user['uId'];
    $R['user']      = &$user;
    $R['profile']   = &$R['user'];
    $R['user']['uImageId'] = $R['userImage'];
    $R['func']      = 'userEvent';
    createSession( $R, $DB );
    return 1;
}

/* ************************************************************* */
/* init                                                          */
/* ************************************************************* */
require 'lib/Config.php';
require 'lib/Database.php';

$C  = new Config();
$DB = new Database( $C );

$R = array( // $R is easier to write than $_REQUEST
    'badLogin'  => '',     'userImage' => 'profileDefaultImage.png',
    'func'      => '',     'session'   => '',  );
foreach ( $_REQUEST as $k=>$v ) { // $R less to write than $_REQUEST
    $R[$k] = str_replace( array('\\\\','\"'), array('','&quot'), $_REQUEST[$k] ); // guard against sql injection
}
/* ********************** */
/* entry                  */
/* ********************** */
debug('A route:'.$R['func']);

/* if any of these succeeds they return 1 */
checkLogin( $R, $DB ) || userLogin( $R, $DB ) || userSignup($R,$DB) || userLostPass0( $R, $DB) || 
/* userEntry returns 0  then we exit */
userEntry($R, $DB)  || exit();

/* **************************** */
/* Routing, i.e. determine which function/model (view) to call  */
/* change to switch: https://www.php.net/manual/en/control-structures.switch.php  */
/* **************************** */
debug('B route:'.$R['func']);
$allowed = [  /* only functions followed by 1 can be called if you are logged in */
    ''               => 0,     
    'postSubmit'     => 1,      'postDelete'        => 1,
    'postEmotion'    => 1,
    'changeRelation' => 1,      'friendRelation'    => 1,      
    'userEventFeed'  => 1,      'userProfileFeed'   => 1,
    'userLostPass0'  => 0,      'userEvent'         => 1,   
    'userAccount'    => 1,      'userLogout'        => 1,     
    'userProfile'    => 1,      'userSearch'        => 1,   
 ];
   
if ( $allowed[ $R['func'] ] > 0 ) {
    $R['func']($R, $DB );
    return; 
}
debug('C unauthorized :'.$R['func']);

?>
