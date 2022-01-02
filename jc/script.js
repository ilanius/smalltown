function gid( id ) {
    return document.getElementById( id );
}
function userLogin() {
    var uEmail = gid('uEmail').value;
    var uPass  = gid('uPassword').value;
    var uform  = gid('loginForm');
    form.onsubmit();
}
function insertInput( pId ) {
    e = gid( 'comm_' + pId);
    // javascript template here?
    e.innerHTML = '<input type="text" id="omm_'+
      pId+'" name="omm_'+pId+
      '" placeholder="opinion" onkeyUp="postIt(event, this);">';
    gid( 'omm_'+pId ).focus();
}
function setIt( o ) {
    e = gid( 'c' + o.name );
    e.innerHTML = o.value; // page reload here?
}  
function postIt( e, o ) {
    if ( e.keyCode != 13 || o.value == '' ) return;
    e.preventDefault(); // cancel event bubble here
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        setIt( o );
      }
    };
    var val = o.name.substring(4);    
    xhttp.open("POST", "index.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("func=userPost0&profileId="+profileId+"&ppId="+val+"&pTxt="+ o.value );
}
function block() { /* reload page */ }
function relation( contId, rAction, uId1, uId2 ) {
    // alert( contId + ' ' + rAction + ' '+  uId1 + ' ' + uId2 );
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
      if (this.readyState == 4 && this.status == 200) {
        gid( contId ).innerHTML = xhttp.responseText; 
      }
    };
    xhttp.open("POST", "index.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("func=friendRelation&subFunc="+rAction+"&uId1="+uId1+"&uId2="+uId2+"&contId="+contId );
}
