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
    httpPost( sendTxt, function( txt ) { gid(contId).innerHTML = txt;   } );
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
function emotionCreate( p ) {
    var emotion = p['emotion'] || '';
    var p1 = emotion.indexOf('p1:');
    var p2 = emotion.indexOf('p2:');
    var a=0,b=0,c=0;
    while ( (p=emotion.indexOf(',', p) ) > 0 ) {
        if ( p > 0  && p < p1 ) a++;
        if ( p > p1 && p < p2 ) b++;
        if ( p > p2 ) c++;
        p++;
    }
    var out = '';
    if ( b > 0 ) out += '<span class="emoticonLike">'+b+'</span>'; // <== insert your own icons here
    if ( a > 0 ) out += ' disl:'+a;
    if ( c > 0 ) out += ' smil:'+c;
    return out;
}
function likeButtonCreate( p ) {
    var re = new RegExp( 'p1:(\d+,)*(' + uId +'),' );
    var bclass = (p['emotion'] || '').match( re ) ? ' active' : '';
    var out = '<button class="postButton' + bclass + 
    `"  onclick="postEmotion(event,${p['pId']},'p1')"> like </button>`;    
    return out;
}
function postCreate( p, children ) {
    p['fromTo'] = p['uId'];
    if ( p['ruId'] && p['ruId'] != p['uId'] ) {
      p['fromTo'] += '=>' + p['ruId'];
    }        
    let str = `<div id="pId${p['pId']}" class="post">` + 
    `<span class="fromTo">${p['fromTo']} </span> ` +
    `<div class="pTxt"> ${p['pTxt']}     </div>`   + 
    `<div id="emotion${p['pId']}" class="emotion">` +
    emotionCreate( p ) +
    `</div>` + 
    `<span id="postEmotion${p['pId']}">` +
    likeButtonCreate( p ) + 
    `</span>` +
    `<button class="postButton" id="postDelete${p['pId']}" onclick="postDelete(${p['pId']}, ${p['ppId']})"> delete </button>` +  
    `<button class="postButton" onclick="postSubmit0(${p['pId']})"> comment </button>`;
    str += children; // ~buildTree( p['child'] );
    str += `<span class="postComment" id="comment${p['pId']}"> </span>` + 
    '</div>';
    return str;
}
function setEmotion( txt ) {
    var p = JSON.parse( txt );
    var emoTag = emotionCreate( p );
    gid( 'emotion'+p['pId'] ).innerHTML = emoTag;
    gid( 'postEmotion'+p['pId'] ).innerHTML = likeButtonCreate( p ) ;
}
function postEmotion( e, pId, emot ) {
    var sendTxt = "func=postEmotion&pId="+pId+"&emot="+ emot;
    httpPost( sendTxt, setEmotion );
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
function reqLoginMail( event, contId, uEmailId ) {
    // event.preventDefault();                                // cancel event bubble here
    var uEmail = gid( uEmailId ).value;
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { 
        gid( contId ).innerHTML = '  Mail requested ' + txt; 
    } );
}
function modalView( event, view ) { // we keep event just in case
    var i, tab, viewButton;
    var tab = document.getElementsByClassName("view");
    for ( i = 0; i < tab.length; i++ ) {
      tab[i].style.display = "none";  
    }
    var viewButton = document.getElementsByClassName("viewButton");
    for ( i = 0; i < viewButton.length; i++) {
      viewButton[i].className = viewButton[i].className.replace(" active", "");
    }
    event.currentTarget.className += " active";
    document.getElementById( view ).style.display = "block";  
  }