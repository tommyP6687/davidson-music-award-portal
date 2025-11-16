<?php
session_start();
$pageTitle  = 'Sign In';
$pageHeading= 'Sign in to the Music Awards Portal';
$pageSub    = 'Choose how you want to sign in.';
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
    <nav class="site-nav">
      <?php if (!empty($_SESSION['role'])): ?>
        <?php if ($_SESSION['role']==='admin'): ?>
          <a href="admin_dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="student_dashboard.php">My Award</a>
        <?php endif; ?>
        <a href="logout.php">Log out</a>
      <?php else: ?>
        <a href="login.php">Sign in</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="page-hero"><div class="inner">
  <h1 class="h1 m0"><?=htmlspecialchars($pageHeading)?></h1>
  <div class="small"><?=htmlspecialchars($pageSub)?></div>
</div></div>

<div class="container">
  <div class="card">
    <div class="actions">
      <a class="btn" href="student_login.php">Student Sign-in</a>
      <a class="btn ghost" href="admin_login.php">Administrator Sign-in</a>
    </div>
  </div>
</div>
</body>
</html>