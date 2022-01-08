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
function postDelete( pId ) {  
    var sendTxt = "func=postDelete&pId="+pId;
    // https://developer.mozilla.org/en-US/docs/Web/API/Element/remove
    httpPost( sendTxt, function() { gid('pId'+pId).remove() } );    
}
function postCreate( p, children ) {
    p['fromTo'] = p['uId'];
    if ( p['ruId'] && p['ruId'] != p['uId'] ) {
      p['fromTo'] += '=>' + p['ruId'];
    }        
    let str = `<div id="pId${p['pId']}" class="post">` + 
    `<span class="fromTo">${p['fromTo']} </span> ` +
    `<div class="pTxt"> ${p['pTxt']}     </div>`   + 
    `<span id="emot_${p['pId']}" onclick="alert(${p['pId']})"> -like/dislike- </span>` +     
    `<span id="emot_${p['pId']}" onclick="postDelete(${p['pId']}, ${p['ppId']})"> -delete - </span>` +  
    `<span onclick="postSubmit0(${p['pId']})"> -comment- </span>`;
    str += children; // ~buildTree( p['child'] );
    str += `<span class="postComment" id="comment${p['pId']}"> </span>` + 
    '</div>';
    return str;
}
function postSubmit0( pId ) {
    comment = gid( 'comment' + pId);
    comment.innerHTML = `<input type="text" id="commentInput${pId}" name="commentInput${pId}"` + 
    ' placeholder="opinion" onkeyUp="postSubmit(event, this);">';
    gid( 'commentInput'+pId ).focus();
}
function postSubmitAddNewNode( txt ) {
    var p = JSON.parse( txt );
    if ( p['ppId'] == undefined ) { p['ppId'] = ''; }
    gid( 'commentInput'+p['ppId']).remove();
    var newNode = postCreate( p, '' );
    var parentNode = gid( 'pId' + p['ppId'] );
    parentNode.innerHTML += newNode;
}
function postSubmit( e, o ) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    e.preventDefault();                                // cancel event bubble here
    var ppId = o.name.substring( 'commentInput'.length );    
    var sendTxt = "func=postSubmit&profileId="+profileId+"&ppId="+ppId+"&pTxt="+ o.value;
    httpPost( sendTxt, postSubmitAddNewNode );
}

/* ************************************************ */
function reqLoginMail( contId, uEmailId ) {
    var uEmail = gid( uEmailId ).value;
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { gid( contId ).innerHTML = '  Mail requested ' + txt; } );
}
