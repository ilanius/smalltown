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
/* function friendRelation( contId, rAction, uId1, uId2 ) {
  var sendTxt = "func=friendRelation&subFunc="+rAction+"&uId1="+uId1+"&uId2="+uId2+"&contId="+contId;
  httpPost( sendTxt, reloadPage );
} */
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
function userPost( e, o ) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    e.preventDefault(); // cancel event bubble here
    var val = o.name.substring(4);    
    var sendTxt = "func=userPost&profileId="+profileId+"&ppId="+val+"&pTxt="+ o.value;
    httpPost( sendTxt, reloadPage );
}
function userPost0( pId ) {
    e = gid( 'comm_' + pId);
    // javascript template here?
    e.innerHTML = '<input type="text" id="omm_'+
      pId+'" name="omm_'+pId+'" placeholder="opinion" onkeyUp="userPost(event, this);">';
    gid( 'omm_'+pId ).focus();
}
function postDelete( pId ) {  
    var sendTxt = "func=postDelete&pId="+pId;
    httpPost( sendTxt, reloadPage );    
}
function reqLoginMail( contId, uEmailId ) {
    var uEmail = gid( uEmailId ).value;
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { gid( contId ).innerHTML = '  Mail requested ' + txt; } );
}
