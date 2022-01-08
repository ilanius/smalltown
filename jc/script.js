function gid( id ) {
    return document.getElementById( id );
}
function reloadPage() {
    document.location.reload();
}
function httpPost( sendTxt, callBack ) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        if ( callBack ) callBack( xhttp.responseText );
      }
    };
    xhttp.open("POST", "index.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send( sendTxt );
    return xhttp;
}
function changeRelation( contId, rAction, uId1, uId2 ) {
    var sendTxt = "func=changeRelation&subFunc="+rAction+"&uId1="+uId1+"&uId2="+uId2+"&contId="+contId;
    httpPost( sendTxt, function( txt ) {  gid(contId).innerHTML = txt;   } );
}
function requestAccept( uId1, uId2 ) {
    var contId = 'nokok'+uId1+'_'+uId2;
    var sendTxt = "func=friendRelation&subFunc=requestAccept&uId1="+uId1+"&uId2="+uId2+"&contId="+contId;
    httpPost( sendTxt, function( txt ) { gid( contId ).innerHTML = '  &lt;-- friends :-) '; } );
}
function requestDeny( uId1, uId2 ) {
    var contId = 'nokok'+uId1+'_'+uId2;
    friendRelation( contId, 'requestDeny', uId1, uId2 );
    o = gid( contId ).style.display = 'none';
}

/* ************************************************ */
/* userPost0 and userPost work together. userPost0 inserts input field
/* userPost responds to [enter], submits to server and 
/* tidies up 
/* ************************************************ */
function userPost0( pId ) {
    e = gid( 'comm_' + pId);
    e.innerHTML = '<input type="text" id="omm_'+       // javascript template here?
      pId+'" name="omm_'+pId+'" placeholder="opinion" onkeyUp="userPost(event, this);">';
    gid( 'omm_'+pId ).focus();
}
function userPost( e, o ) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    e.preventDefault();                                // cancel event bubble here
    var val = o.name.substring(4);    
    var sendTxt = "func=userPost&profileId="+profileId+"&ppId="+val+"&pTxt="+ o.value;
    /* Should not reload page. Better to add to end of contPane */
    httpPost( sendTxt, reloadPage );
}
/* ************************************************ */

function postDelete( pId ) {  
    var sendTxt = "func=postDelete&pId="+pId;
    // Instead of reload page we should do element.remove() 
    // https://developer.mozilla.org/en-US/docs/Web/API/Element/remove
    httpPost( sendTxt, reloadPage );    
}
function reqLoginMail( contId, uEmailId ) {
    var uEmail = gid( uEmailId ).value;
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { gid( contId ).innerHTML = '  Mail requested ' + txt; } );
}
