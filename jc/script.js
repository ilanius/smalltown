"strict";

var feedType       = '';
var lastFeedTime   = 0;   /* this is mysql server time  */

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
function setEmos( str, emo, user ) {
    let emos = emo.split(',');
    let str2 = '';
    for ( let i in emos ) {
        let uId = emos[i];
        if ( uId == '' ) break;
        str2 += 
`<a href="?func=userProfile&profileId=${uId}"> 
  ${user[uId]['uFirstName']} ${user[uId]['uLastName']} 
</a> <br>`;
    }
    if ( str2.length > 0 ) return str + '<br>' + str2;
    return '';
} 
function setPlate( txt, pId, emo, plat ) {
    let user = JSON.parse( txt );
    let str = '';
    str  += setEmos( 'dislike', emo[1], user );
    str  += setEmos( 'like',    emo[2], user );
    str  += setEmos( 'smile',   emo[3], user );
    plat.innerHTML = str;
}
function collectPlate( pId, emotion ) {
    let plat = gid( 'emotionPlate' + pId );

    /* This condition works as a brake. collectPlace onmouseover fires 50 times a second */
    if ( plat.innerHTML != '!' ) return; 
    plat.innerHTML = '';

    let emo = emotion.split( /[np]\d:/ ); // get rid of n1 p1 p2
    let users = emo[1] + emo[2] + emo[3];
    users = encodeURIComponent( users.substring( 0, users.length - 1) );
    var sendTxt = "func=collectPlate&users="+users;
    httpPost( sendTxt, function( txt ) { setPlate( txt, pId, emo, plat );   } );
}
function emotionCreate( p ) {
    var emotion = p['emotion'] || '';
    var p1 = emotion.indexOf('p1:');
    var p2 = emotion.indexOf('p2:');
    var a=0,b=0,c=0, i=0;
    while ( ( i=emotion.indexOf(',', i) ) > 0 ) {
        if ( i > 0  && i < p1 ) a++;
        if ( i > p1 && i < p2 ) b++;
        if ( i > p2 ) c++;
        i++;
    }
    var out = `<span id="collectPlate${p['pId']}" onmouseover="collectPlate(${p['pId']}, '${p['emotion']}')" class="dropPlate">
    <div id="emotionPlate${p['pId']}" class="emotionPlate">!</div>`;
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
    `"  onclick="emotionSubmit(event,${p['pId']},'p1')"> like </button>`;    
    return out;
}
function addPostSubmitField( event, pId ) {
    comment = gid( 'comment' + pId);
    // https://www.smashingmagazine.com/2018/01/drag-drop-file-uploader-vanilla-js/
    // https://developer.mozilla.org/en-US/docs/Web/API/HTML_Drag_and_Drop_API/Drag_operations#specifying_drop_targets
    comment.innerHTML = `<input type="text" ondragenter="return false" ondragover="return false" ondrop="alert('drop not yet implemented'); return false" id="commentInput${pId}" name="commentInput${pId}"` + 
    ' placeholder="opinion" onkeyUp="postSubmit(event, this);">';
    gid( 'commentInput'+pId ).focus();
}
/* *************************************** */
/* See buildTree in feed0.htm
/* A new level of recursiveness
/* *************************************** */
function postInnerHtml( p, children ) {
    if ( !p['uImageId'] ) { p['uImageId'] ='profileDefaultImage.png' };
    let str = 
    `<span class="fromTo">
      <a href="?func=userProfile&profileId=${p['uId']}" title="${p['uFirstName']+' '+p['uLastName']}">
        <img class="pImg" src="img/${p['uImageId']}"> ${p['uFirstName']+' '+p['uLastName']} </a> `;
    if ( p['ruId'] && p['ruId'] != p['uId'] && p['pId'] == p['rpId'] ) {
        str += `=> ${p['ruFirstName']+' '+p['ruLastName']} `;
    }
                   
    str += '</span><br> ' +
    `<div id="pTxt${p['pId']}" class="pTxt"> ${p['pTxt']}     </div>`   + 
    `<div id="emotion${p['pId']}" class="emotion">` +
    emotionCreate( p ) +
    `</div>` + 
    `<span id="postEmotion${p['pId']}">` +
    likeButtonCreate( p ) + 
    `</span>`;
    if ( uId == p['uId'] || uId == parseInt(p['ruId']) ) { // if you own the post or the feed you are allowed to delete
        str += `<button class="postButton" id="postDelete${p['pId']}" onclick="postDelete(${p['pId']})"> delete </button>`;
    }
    str += `<button class="postButton" onclick="addPostSubmitField(event, ${p['pId']})"> comment </button>`;
    str += children; // ~buildTree( p['child'] );
    str += `<span class="postComment" id="comment${p['pId']}"> </span>`;
    return str;
}
function postCreate( p, child ) {
    // p['fromTo'] = p['uId'];    if ( p['ruId'] && p['ruId'] != p['uId'] ) { p['fromTo'] += '=>' + p['ruId']; } 
    let str = `<div data-ruid="${p['ruId']}" data-uid="${p['uId']}" id="pId${p['pId']}" class="post">` + 
    postInnerHtml( p, child ) + 
    '</div>';
    return str;
}
function emotionSubmit( event, pId, emot ) {
    clearTimeout( feedUpdateTime ); // this function may be called inside call interval
    var sendTxt = `func=postEmotion&pId=${pId}&emot=${emot}&lastFeedTime=${lastFeedTime}`;
    resetFeedUpdate();
    httpPost( sendTxt, feedUpdateSet ); 
}
function postSubmit(event, o ) {
    if ( event.keyCode != 13 || o.value == '' ) return;
    event.preventDefault();                                   // cancel event bubble here
    var pId = o.name.substring( 'commentInput'.length );  // pId will be parent of this post
    var sendTxt = `func=postSubmit&profileId=${profileId}&ppId=${pId}&pTxt=${o.value}&lastFeedTime=${lastFeedTime}`;  
    //gid( 'commentInput'+pId).value= '';
    o.value = '';
    if ( pId.length > 0 ) {
        gid( 'commentInput'+pId).remove(); // text input not post is removed 
    } 
    resetFeedUpdate();
    httpPost( sendTxt, feedUpdateSet ); // postSubmitAddNewNode );
}
function postDelete( pId ) {  
    /* https://developer.mozilla.org/en-US/docs/Learn/HTML/Howto/Use_data_attributes */
    let post = document.querySelector('#pId'+pId );
    let data = post.dataset;
    if ( !( +data.uid == +uId || +data.ruid == +uId )  ) { 
        /* Only owner of post or feed may delete. Check at server */
        return;
    }
    var sendTxt = `func=postDelete&pId=${pId}&lastFeedTime=${lastFeedTime}`;
    // https://developer.mozilla.org/en-US/docs/Web/API/Element/remove
    clearTimeout( feedUpdateTime ); // this function may be called inside call interval
    resetFeedUpdate();
    httpPost( sendTxt, feedUpdateSet );    
}
/* ************************************************** */
function feedUpdateAdd( p ) {
    if ( gid( 'pId' + p['pId'] ) ) return;  // post exists, no need to add again
    if ( p['ppId'] == undefined ) { p['ppId'] = ''; }
    // if ( p['rpId'] == p['pId'] && p['uId'] != profileId && feedType != 'userEventFeed' ) {    return; /* Don't add root post to wrong feed */  }
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
function feedUpdateDel( p ) {
    let post = document.querySelector('#pId'+p['pId'] );
    if ( post ) post.remove();
}
function feedUpdateMod( p ) {
    var emoTag = emotionCreate( p );
    var o0 = gid( 'emotion'+p['pId'] );
    if ( !o0 ) return;
    o0.innerHTML = emoTag;
    gid( 'postEmotion'+p['pId'] ).innerHTML = likeButtonCreate( p );
    if ( p['pTxt'] ) {
        gid( 'pTxt' + p['pId'] ).innerHTML = p['pTxt'];  
    }
}
function feedUpdateSet( txt ) {
    // console.log( txt );
    var data = JSON.parse( txt );
    var post = data['post'];
    lastFeedTime = data['lastFeedTime'];
    for ( var i in post ) {
        var p = post[i];
        if ( p['action'] == 'del' ) {
            feedUpdateDel( p );   
        } else if ( p['action'] == 'mod') { 
            feedUpdateMod( p );
        } else if ( p['action'] == 'add' ) {
            feedUpdateAdd( p );  
        }
    }
}
/* ************************************************ */

function reqLoginMail( event, contId, uEmailId ) {
    // event.preventDefault();  // cancel event bubble here
    var uEmail = gid( uEmailId ).value;
    if ( uEmail.length < 8 ) return; 
    var sendTxt = "func=userLostPass0&uEmail="+uEmail;
    httpPost( sendTxt, function( txt ) { 
        gid( contId ).innerHTML = '  Mail requested ' + txt; 
    } );
}

function tabView( event, view ) { 
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
/* ********************************************************** */
/*
/* https://css-tricks.com/cycle-through-classes-html-element/ 
/* 
/* Adding drag and drop of image to input field
/* https://www.smashingmagazine.com/2018/01/drag-drop-file-uploader-vanilla-js/^M
/*
/* ********************************************************** */
