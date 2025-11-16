<?php
require_once "middleware.php"; require_role('admin');
require_once "config.php"; require_once "utils.php";

// Upload/replace CSV (only CSV)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_sheet'])) {
  if (!isset($_FILES['sheet']) || $_FILES['sheet']['error']!==UPLOAD_ERR_OK) {
    $err="Upload failed.";
  } else {
    $tmp=$_FILES['sheet']['tmp_name']; $name=$_FILES['sheet']['name'];
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION))!=='csv') {
      $err="Please upload a CSV file (export from Excel/Google Sheets).";
    } else {
      $safe=time()."_".sanitize_filename($name);
      if (!move_uploaded_file($tmp, SHEET_UPLOAD_DIR.$safe)) { $err="Could not save file."; }
      else {
        $pdo->query("DELETE FROM admin_sheet");
        $stmt=$pdo->prepare("INSERT INTO admin_sheet (csv_path, original_name) VALUES (?,?)");
        $stmt->execute([SHEET_WEB_PATH.$safe, $name]);
        $ok="Spreadsheet uploaded and set as current.";
      }
    }
  }
}

// Download all signed letters (zip)
if (isset($_GET['download_all']) && $_GET['download_all']==='1') {
  $zipPath=create_signed_zip();
  if ($zipPath && file_exists($zipPath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipPath).'"');
    header('Content-Length: '.filesize($zipPath));
    readfile($zipPath); @unlink($zipPath); exit;
  } else { $err="Could not create zip."; }
}

// Load sheet + submissions
$sheet = $pdo->query("SELECT * FROM admin_sheet ORDER BY uploaded_at DESC LIMIT 1")->fetch();
$sheetData = null;
if ($sheet) {
  $abs = BASE_PATH . ltrim(parse_url($sheet['csv_path'], PHP_URL_PATH) ?: $sheet['csv_path'], '/');
  if (file_exists($abs)) $sheetData = read_csv_scan_name_header($abs); // scan header row
}

// Build submissions index by name and email for quick lookups
$subByEmail = [];
$subByName  = [];
$subs = $pdo->query("SELECT * FROM submissions")->fetchAll();
foreach ($subs as $s) {
  if (!empty($s['email'])) $subByEmail[strtolower(trim($s['email']))] = $s;
  $k = strtolower(trim($s['last_name'])) . '|' . strtolower(trim($s['first_name']));
  $subByName[$k] = $s;
}

$pageTitle='Admin Dashboard'; $pageHeading='Admin Dashboard'; $pageSub='Upload the current CSV, preview status, and download signed letters.';
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
    <nav class="site-nav"><a href="admin_dashboard.php">Dashboard</a><a href="logout.php">Log out</a></nav>
  </div>
</header>
<div class="page-hero"><div class="inner">
  <h1 class="h1 m0"><?=htmlspecialchars($pageHeading)?></h1>
  <div class="small"><?=htmlspecialchars($pageSub)?></div>
</div></div>

<div class="container">
  <div class="card">
    <form method="POST" enctype="multipart/form-data" class="actions">
      <label class="m0">Upload current spreadsheet (CSV)</label>
      <input type="file" name="sheet" accept=".csv" required>
      <button class="btn" type="submit" name="upload_sheet" value="1">Upload/Replace</button>
      <?php if ($sheet): ?>
        <a class="btn ghost" href="admin_download_csv.php">Download current CSV</a>
        <a class="btn ghost" href="?download_all=1">Download ALL signed letters (zip)</a>
      <?php endif; ?>
    </form>
    <?php if (!empty($ok)): ?><div class="small mt12" style="color:#126a2a;"><strong>✔</strong> <?=htmlspecialchars($ok)?></div><?php endif; ?>
    <?php if (!empty($err)): ?><div class="small mt12" style="color:#9a3412;"><strong>⚠</strong> <?=htmlspecialchars($err)?></div><?php endif; ?>
  </div>

  <?php if (!$sheet): ?>
    <p class="small mt12"><em>No spreadsheet uploaded yet.</em></p>
  <?php else: ?>
    <div class="card mt16">
      <div class="small">Current file: <strong><?=htmlspecialchars($sheet['original_name'])?></strong> · Uploaded <?=htmlspecialchars($sheet['uploaded_at'])?></div>
      <div class="small">We display <strong>Last Name</strong> (A), <strong>First Name</strong> (B), and the immediate next column (C). Header row is auto-detected.</div>

      <?php if (!$sheetData || empty($sheetData['rows'])): ?>
        <p class="small mt12"><em>Could not read CSV or it has no rows.</em></p>
      <?php else: ?>
        <?php
          $hdr = $sheetData['header'];
          $lastHeader   = $hdr[0] ?? null;  // Column A (Last Name)
          $firstHeader  = $hdr[1] ?? null;  // Column B (First Name)
          $thirdHeader  = $hdr[2] ?? null;  // Column C (whatever it is)

          // Optional email header (for matching only)
          $emailHeader = null;
          foreach ($hdr as $h) { if (preg_match('/^email$/i', $h)) { $emailHeader = $h; break; } }
        ?>
        <table class="table mt12">
          <thead>
            <tr>
              <th>Submission Date</th>
              <th>Letter Status</th>
              <th><?=htmlspecialchars($lastHeader ?: 'Last Name')?></th>
              <th><?=htmlspecialchars($firstHeader ?: 'First Name')?></th>
              <th><?=htmlspecialchars($thirdHeader ?: 'Column C')?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sheetData['rows'] as $row):
            // Prefer email match if CSV includes an Email column
            $s = null;
            if ($emailHeader) {
              $rowEmail = strtolower(trim($row[$emailHeader] ?? ''));
              if ($rowEmail && isset($subByEmail[$rowEmail])) $s = $subByEmail[$rowEmail];
            }
            // Fallback: Column A/B (Last/First) — trimmed + lowercased
            if (!$s && $lastHeader && $firstHeader) {
              $key = strtolower(trim($row[$lastHeader] ?? '')) . '|' . strtolower(trim($row[$firstHeader] ?? ''));
              $s = $subByName[$key] ?? null;
            }

            $date   = $s ? format_ddmmyyyy($s['submitted_at']) : '';
            $status = $s ? $s['status'] : 'none';
            $cls    = $status==='accepted' ? 'accepted' : ($status==='declined' ? 'declined' : 'none');
          ?>
            <tr>
              <td><?=htmlspecialchars($date)?></td>
              <td><span class="pill <?=$cls?>"><?=htmlspecialchars($status)?></span></td>
              <td><?=htmlspecialchars($row[$lastHeader]   ?? '')?></td>
              <td><?=htmlspecialchars($row[$firstHeader]  ?? '')?></td>
              <td><?=htmlspecialchars($row[$thirdHeader]  ?? '')?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card mt16">
      <h3 class="m0" style="font-family:'Merriweather', Georgia, serif;">Signed Award Letters</h3>
      <ul class="small mt8" style="list-style:disc; padding-left:20px;">
        <?php
          $any=false;
          if (is_dir(SIGNED_UPLOAD_DIR)) {
            $files = array_values(array_filter(scandir(SIGNED_UPLOAD_DIR), fn($f)=>$f!=='.'&&$f!=='..'&&is_file(SIGNED_UPLOAD_DIR.$f)));
            sort($files, SORT_NATURAL|SORT_FLAG_CASE);
            foreach ($files as $f) { $any=true; echo '<li><a href="'.htmlspecialchars(SIGNED_WEB_PATH.$f).'" class="small" download>'.htmlspecialchars($f).'</a></li>'; }
          }
          if (!$any) echo '<li class="small"><em>No signed letters uploaded yet.</em></li>';
        ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
</body>
</html>