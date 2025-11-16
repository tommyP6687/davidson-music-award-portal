<?php
session_start();
$pageTitle='Admin Login'; $pageHeading='Administrator Sign-in'; $pageSub='Use the credentials stored in the database.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($pageTitle)?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/theme.css">
</head>
<body>
<header class="site-header">
  <div class="bar">
    <a class="brand" href="login.php"><span class="mark">DC</span><span class="name">Music Awards Portal</span></a>
    <nav class="site-nav"><a href="login.php">Back</a></nav>
  </div>
</header>

<div class="page-hero"><div class="inner">
  <h1 class="h1 m0"><?=htmlspecialchars($pageHeading)?></h1>
  <div class="small"><?=htmlspecialchars($pageSub)?></div>
</div></div>

<div class="container">
  <div class="card">
    <form method="POST" action="authenticate.php">
      <input type="hidden" name="mode" value="admin">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <div class="mt12">
        <button class="btn" type="submit">Sign in</button>
        <a class="btn muted" href="login.php">Cancel</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>