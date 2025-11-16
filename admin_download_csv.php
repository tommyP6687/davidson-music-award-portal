<?php
// Streams a CSV with: Submission Date, Letter Status, Last Name (A), First Name (B), Column C
require_once "middleware.php"; require_role('admin');
require_once "config.php"; require_once "utils.php";

// Load sheet record
$sheet = $pdo->query("SELECT * FROM admin_sheet ORDER BY uploaded_at DESC LIMIT 1")->fetch();
if (!$sheet) { http_response_code(404); die("No spreadsheet uploaded."); }

// Read CSV using header scan
$absCsv = BASE_PATH . ltrim(parse_url($sheet['csv_path'], PHP_URL_PATH) ?: $sheet['csv_path'], '/');
$csv = read_csv_scan_name_header($absCsv);
if (!$csv || empty($csv['header'])) { http_response_code(500); die("Could not read the current CSV."); }

$header = $csv['header'];
$rows   = $csv['rows'];

// Identify columns strictly by position
$lastHeader   = $header[0] ?? 'Last Name';   // Column A
$firstHeader  = $header[1] ?? 'First Name';  // Column B
$thirdHeader  = $header[2] ?? 'Column C';    // Column C (whatever it is)

// Optional email column (for matching only)
$emailHeader = null;
foreach ($header as $h) {
  if (preg_match('/^email$/i', $h)) { $emailHeader = $h; break; }
}

// Build submission indices
$subByEmail = [];
$subByName  = [];
$subs = $pdo->query("SELECT first_name,last_name,email,status,submitted_at FROM submissions")->fetchAll();
foreach ($subs as $s) {
  if (!empty($s['email'])) $subByEmail[strtolower(trim($s['email']))] = $s;
  $k = strtolower(trim($s['last_name'])) . '|' . strtolower(trim($s['first_name']));
  $subByName[$k] = $s;
}

// Output headers and CSV content
$filename = "awards_with_status_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

// Excel UTF-8 BOM
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// New header: derived columns + A, B, and C
fputcsv($out, ['Submission Date', 'Letter Status', $lastHeader, $firstHeader, $thirdHeader]);

// Rows
foreach ($rows as $row) {
  $s = null;

  // Prefer email match if possible
  if ($emailHeader) {
    $rowEmail = strtolower(trim($row[$emailHeader] ?? ''));
    if ($rowEmail && isset($subByEmail[$rowEmail])) $s = $subByEmail[$rowEmail];
  }

  // Fallback to Column A/B (Last/First)
  if (!$s) {
    $key = strtolower(trim($row[$lastHeader] ?? '')) . '|' . strtolower(trim($row[$firstHeader] ?? ''));
    if (($row[$lastHeader] ?? '') !== '' && ($row[$firstHeader] ?? '') !== '') {
      $s = $subByName[$key] ?? null;
    }
  }

  $status = $s ? $s['status'] : 'none';
  $date   = $s ? format_ddmmyyyy($s['submitted_at']) : '';

  fputcsv($out, [
    $date,
    $status,
    $row[$lastHeader]   ?? '',
    $row[$firstHeader]  ?? '',
    $row[$thirdHeader]  ?? ''
  ]);
}

fclose($out);
exit;