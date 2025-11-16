<?php
require_once "middleware.php"; require_role('student');
require_once "config.php";
require_once "utils.php";

/* --- always load fresh --- */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$pageTitle   = 'Student Award';
$pageHeading = 'Student Award Letter';
$pageSub     = 'Submit your response below.';

/* Load latest submission for this student by email */
$studentEmail = $_SESSION['email'] ?? null;
$submission = null;
if ($studentEmail) {
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, status, signed_pdf_path, submitted_at
        FROM submissions
        WHERE email = ?
        ORDER BY submitted_at DESC
        LIMIT 1
    ");
    $stmt->execute([$studentEmail]);
    $submission = $stmt->fetch();
}

$statusLabel  = $submission['status'] ?? 'none';
$signedPath   = $submission['signed_pdf_path'] ?? null;
$submittedAt  = $submission['submitted_at'] ?? null;
$submittedDDM = $submittedAt ? format_ddmmyyyy($submittedAt) : '';

/* cache-bust the PDF preview */
$versionedPdf = null;
if ($signedPath && $statusLabel === 'accepted') {
    $pathOnly = parse_url($signedPath, PHP_URL_PATH) ?: $signedPath;
    $abs = BASE_PATH . ltrim($pathOnly, '/');
    $ver = file_exists($abs) ? filemtime($abs) : time();
    $versionedPdf = $signedPath . (str_contains($signedPath, '?') ? '&' : '?') . 'v=' . $ver;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($pageTitle)?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/theme.css">
  <style>
    .two-col { display:grid; grid-template-columns: minmax(260px, 475px) 1fr; gap:18px; align-items:start; }
    .pill{ padding:3px 10px; border-radius:999px; font-size:12px; display:inline-block; border:1px solid #e5e7eb }
    .accepted{ background:#eef8f1; border-color:#9cd3ae; color:#126a2a }
    .declined{ background:#fff1ee; border-color:#f3b4a8; color:#9a3412 }
    .none{ background:#f3f4f6; color:#6b7280 }
    .pdfbox { border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#fff }
    .pdfbox iframe { width:100%; height:75vh; border:0 }
    .ack { display:flex; align-items:flex-start; gap:10px; margin-top:8px; }
    .ack input { margin-top:4px; }
  </style>
</head>
<body>
<header class="site-header">
  <div class="bar">
    <a class="brand" href="login.php"><span class="mark">DC</span><span class="name">Music Awards Portal</span></a>
    <nav class="site-nav"><a href="student_dashboard.php">My Award</a><a href="logout.php">Log out</a></nav>
  </div>
</header>

<div class="page-hero"><div class="inner">
  <h1 class="h1 m0"><?=htmlspecialchars($pageHeading)?></h1>
  <div class="small"><?=htmlspecialchars($pageSub)?></div>
</div></div>

<div class="container">
  <div class="two-col">
    <!-- Left: form (reset each load) -->
    <div class="card">
      <form id="awardForm" action="student_submit.php" method="POST" enctype="multipart/form-data" novalidate>
        <?php // CSRF hidden input if you’re using one: ?>
        <?php if (!empty($_SESSION['csrf'])): ?>
          <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf'])?>">
        <?php endif; ?>

        <label>First Name</label>
        <input type="text" name="first_name" required>

        <label>Last Name</label>
        <input type="text" name="last_name" required>

        <label>Response</label>
        <select name="decision" id="decision" required>
          <option value="accepted">Accept</option>
          <option value="declined">Decline</option>
        </select>

        <div id="signed_wrap">
          <label>Upload signed award letter (PDF only)</label>
          <input type="file" name="signed_pdf" id="signed_pdf" accept="application/pdf">

          <!-- Required acknowledgement #1: penalties -->
          <label class="ack">
            <input type="checkbox" id="accept_ack" name="accept_ack" value="1">
            <span class="small">I have read the letter carefully and accept the penalties of withdrawing from lessons after the second week of class.</span>
          </label>

          <!-- NEW: Required acknowledgement #2: registration -->
          <label class="ack">
            <input type="checkbox" id="registered_ack" name="registered_ack" value="1">
            <span class="small"><b>Register now</b> for your required ensemble and lessons. Depending on your award lesson length, you will register for one of the following on the <a href = "https://www.davidson.edu/academic-departments/music" target = "_blank">Music Department website</a>: MUS 50 (30-minute non-credit), MUS 55 (1-hr  non-credit), or MUS 155 (1-hr credit).</span>
          </label>
        </div>

        <div class="mt16">
          <button class="btn" type="submit">Submit</button>
        </div>
      </form>
    </div>

    <!-- Right: preview of the student's own signed letter (cache-busted) -->
    <div class="card">
      <div class="small">Signed letter status</div>
      <?php $cls = $statusLabel==='accepted' ? 'accepted' : ($statusLabel==='declined' ? 'declined' : 'none'); ?>
      <div class="mt8">
        <span class="pill <?= $cls ?>"><?=htmlspecialchars($statusLabel)?></span>
        <?php if ($submittedDDM): ?>
          <span class="small" style="margin-left:8px">Submitted: <?=htmlspecialchars($submittedDDM)?></span>
        <?php endif; ?>
      </div>

      <div class="mt16">
        <strong>Your Signed Award Letter</strong>
        <?php if ($versionedPdf && $statusLabel === 'accepted'): ?>
          <div class="pdfbox mt8"><iframe src="<?=htmlspecialchars($versionedPdf)?>"></iframe></div>
          <div class="mt8"><a class="btn ghost" href="<?=htmlspecialchars($versionedPdf)?>" download>Download PDF</a></div>
        <?php else: ?>
          <p class="small mt8"><em>No signed award letter on file yet. Choose “Accept”, tick both confirmations, upload your signed PDF, then submit.</em></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  // Clear the form on every load/refresh
  const form = document.getElementById('awardForm');
  form.reset();

  const sel  = document.getElementById('decision');
  const wrap = document.getElementById('signed_wrap');
  const file = document.getElementById('signed_pdf');
  const ack1 = document.getElementById('accept_ack');
  const ack2 = document.getElementById('registered_ack');

  function toggleAcceptUI() {
    const isAccept = sel.value === 'accepted';
    wrap.style.display = isAccept ? 'block' : 'none';
    file.required = isAccept;
    ack1.required = isAccept;
    ack2.required = isAccept;
    if (!isAccept) {
      file.value = '';
      ack1.checked = false;
      ack2.checked = false;
    }
  }
  sel.addEventListener('change', toggleAcceptUI);
  toggleAcceptUI();
</script>
</body>
</html>