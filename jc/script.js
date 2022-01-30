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
    httpPost( sendTxt, function( txt ) { gid( contId ).innerHTML = ' <span class="check"></span>'; reloadPage(); } );
}
function requestDeny( uId1, uId2 ) {
    var contId = 'nokok'+uId1+'_'+uId2;
    friendRelation( contId, 'requestDeny', uId1, uId2 );
    o = gid( contId ).style.display = 'none';
}
function postDelete0( p ) {
    let post = document.querySelector('#pId'+p['pId'] );
    if ( post ) post.remove();
}
function postDelete( pId ) {  
    /* https://developer.mozilla.org/en-US/docs/Learn/HTML/Howto/Use_data_attributes */
    let post = document.querySelector('#pId'+pId );
    let data = post.dataset;
    if ( !(data.uid == uId || data.ruid == uId )  ) { // if you are not owner of post or feed you may not delete
         return;
    }
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
    var out = '<span class="dropPlate"><div class="userPlate">'+ emotion +'</div>';
    if ( b > 0 ) out += '<span class="emotLike">' + b + '</span>'; // <== insert your own icons here
    if ( a > 0 ) out += '<span class="emotDisl">' + a + '</span>';
    if ( c > 0 ) out += '<span class="emotSmil">' + c + '</span>';
    out += '</span>';
    return out;
}
function likeButtonCreate( p ) {
    var re = new RegExp( 'p1:(\\d+,)*(' + uId +'),' );
    var bclass = (p['emotion'] || '').match(re) ? ' active' : '';
    var out = '<button class="postButton' + bclass + 
    `"  onclick="postEmotion(event,${p['pId']},'p1')"> like </button>`;    
    return out;
}
/* *************************************** */
/* parameter children has already been created recursively 
/* i.e. if child nodes exist 
/* See buildTree in feed0.htm
/* a new level of recursiveness
/* *************************************** */
function postInnerHtml( p, children ) {
    if ( !p['uImageId'] ) { p['uImageId'] ='profileDefaultImage.png' };
    let str = 
    `<span class="fromTo">
      <!-- ${p['fromTo']} -->
      <a href="?func=userProfile&profileId=${p['uId']}" title="${p['uFirstName']+' '+p['uLastName']}">
        <img class="pImg" src="img/${p['uImageId']}"></a>
    </span> ` +
    `<div class="pTxt"> ${p['pTxt']}     </div>`   + 
    `<div id="emotion${p['pId']}" class="emotion">` +
    emotionCreate( p ) +
    `</div>` + 
    `<span id="postEmotion${p['pId']}">` +
    likeButtonCreate( p ) + 
    `</span>`;
    if ( uId == p['uId'] || uId == parseInt(p['ruId']) ) { // if you own the post or the feed you are allowed to delete
        str += `<button class="postButton" id="postDelete${p['pId']}" onclick="postDelete(${p['pId']})"> delete </button>`;
    }
    str += `<button class="postButton" onclick="postSubmit0(event, ${p['pId']})"> comment </button>`;
    str += children; // ~buildTree( p['child'] );
    str += `<span class="postComment" id="comment${p['pId']}"> </span>`;
    return str;
}
function postCreate( p, child ) {
    p['fromTo'] = p['uId'];
    if ( p['ruId'] && p['ruId'] != p['uId'] ) {
      p['fromTo'] += '=>' + p['ruId'];
    }        
    let str = `<div data-ruid="${p['ruId']}" data-uid="${p['uId']}" id="pId${p['pId']}" class="post">` + 
    postInnerHtml( p, child ) + 
    '</div>';
    return str;
}
function setEmotion0( p ) {
    var emoTag = emotionCreate( p );
    var o0 = gid( 'emotion'+p['pId'] );
    if ( !o0 ) return;
    o0.innerHTML = emoTag;
    gid( 'postEmotion'+p['pId'] ).innerHTML = likeButtonCreate( p ) ;
}
function setEmotion( txt ) {
    var p = JSON.parse( txt );
    setEmotion0( p );
}
function postEmotion( e, pId, emot ) {
    var sendTxt = "func=postEmotion&pId="+pId+"&emot="+ emot;
    httpPost( sendTxt, setEmotion );
}
function postSubmitAddNewNode0( p ) {
    if ( gid( 'pId' + p['pId'] ) ) return;  // failSafe: already there no need to add again
    if ( p['ppId'] == undefined ) { p['ppId'] = ''; }
    if ( p['uImageId'] == undefined ) { p['uImageId'] = uImageId; } // uImageId is defined in feed0.htm
    var newNode = postCreate( p, '' );
    var parentNode = gid( 'pId' + p['ppId'] );
    if ( ! parentNode ) return;
    if ( p['ppId'].length==0 ) { 
        parentNode.innerHTML = newNode + parentNode.innerHTML;
    } else {
        parentNode.innerHTML += newNode;
    }
}
function postSubmitAddNewNode( txt ) {
    var p = JSON.parse( txt );
    postSubmitAddNewNode0( p );
}
function postSubmitTop(e,o) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    e.preventDefault();                                // cancel event bubble here
    var ppId = o.name.substring( 'commentInput'.length );    
    var sendTxt = "func=postSubmit&profileId="+profileId+"&ppId="+ppId+"&pTxt="+ o.value;  
    gid( 'commentInput'+ppId).value= '';
    httpPost( sendTxt, postSubmitAddNewNode );
}
function postSubmit( e, o ) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    var ppId = o.name.substring( 'commentInput'.length );    
    postSubmitTop(e,o);
    gid( 'commentInput'+ppId).remove();
}
function postSubmit0( event, pId ) {
    comment = gid( 'comment' + pId);
    comment.innerHTML = `<input type="text" id="commentInput${pId}" name="commentInput${pId}"` + 
    ' placeholder="opinion" onkeyUp="postSubmit(event, this);">';
    gid( 'commentInput'+pId ).focus();
}

/* ************************************************ */
function reqLoginMail( event, contId, uEmailId ) {
    // event.preventDefault();                                // cancel event bubble here
    var uEmail = gid( uEmailId ).value;
    if ( uEmail.length < 8 ) return;
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { 
        gid( contId ).innerHTML = '  Mail requested ' + txt; 
    } );
}

/* ****************************************** */
/* https://css-tricks.com/cycle-through-classes-html-element/ */
/* ****************************************** */
function tabView( event, view ) { // we keep event just in case
    var i, tab, viewButton;
    var tab = document.getElementsByClassName("tab");
    for ( i = 0; i < tab.length; i++ ) {
      tab[i].style.display = "none";  
    }
    var tabButton = document.getElementsByClassName("tabButton");
    for ( i = 0; i < tabButton.length; i++) {
      tabButton[i].className = tabButton[i].className.replace(" active", "");
    }
    event.currentTarget.className += " active";
    document.getElementById( view ).style.display = "block";  
}

/* ************** */
/* Adding drag and drop of image to input field
/* https://www.smashingmagazine.com/2018/01/drag-drop-file-uploader-vanilla-js/^M
/* ********************************** */
