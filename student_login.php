<?php
session_start();

$pageTitle   = 'Student Login';
$pageHeading = 'Student Sign-in';
$pageSub     = 'Use your Davidson email to continue.';

// Handle form submit
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !preg_match('/^[^@\s]+@davidson\.edu$/i', $email)) {
        $error = 'Please sign in with a valid @davidson.edu email.';
    } else {
        // Minimal session for student
        $_SESSION['email'] = $email;
        $_SESSION['role']  = 'student';
        header('Location: student_dashboard.php');
        exit;
    }
}
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
      <a href="login.php">Home</a>
    </nav>
  </div>
</header>

<div class="page-hero"><div class="inner">
  <h1 class="h1 m0"><?=htmlspecialchars($pageHeading)?></h1>
  <div class="small"><?=htmlspecialchars($pageSub)?></div>
</div></div>

<div class="container">
  <div class="card">
    <form method="POST">
      <label>Davidson Email</label>
      <input type="email" name="email" placeholder="you@davidson.edu" required>
      <div class="mt12">
        <button class="btn" type="submit">Continue</button>
        <a class="btn muted" href="login.php" style="margin-left:8px">Cancel</a>
      </div>
      <?php if ($error): ?>
        <div class="small mt12" style="color:#9a3412;"><strong>⚠</strong> <?=htmlspecialchars($error)?></div>
      <?php endif; ?>
    </form>
  </div>
</div>
</body>
</html>