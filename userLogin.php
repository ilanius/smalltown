<!doctype html>
<html>
<head>
  <link href="jc/style.css" rel="style">
  <script src="jc/script.css"></script>
  <meta charset="utf-8">
  <title>smalltown</title>
</head>

<body>
    <h1>User Login <?= $user ?> </h1>
  <div class="userGrid">

    <div class="userPane"> 
      <span class=""> <img src="img/users/<?=$R[userImage]?>"> </span>
      <span class=""> </span>
      <span class=""> </span>
    </div>

    <div class="contPane"> 
      <div class="lognPane">
        <form action="" method="post">
          <label for="">Email    </label> <input type="email" name="uEmail"> 
          <label for="">Password </label> <input type="password" name="uPassword">
          <button>Login</button>
          <button>Sign Up!</button>
        </form>
      </div>
    </div>

    <div class="footPane"> 
      <div class="cookieLoose">
          Cookies on the loose!! This site uses cookies! <br>
          - If you don't like cookies don't use this site!
      </div>
    </div>

  </div>
</body>
</html>
