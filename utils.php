<?php
// Safe guards
if (!defined('BASE_PATH'))   define('BASE_PATH', __DIR__ . '/');
if (!defined('PUBLIC_BASE')) define('PUBLIC_BASE', '');

// Upload dirs
if (!defined('SHEET_UPLOAD_DIR')) define('SHEET_UPLOAD_DIR', BASE_PATH . 'uploads/sheets/');
if (!defined('SHEET_WEB_PATH'))   define('SHEET_WEB_PATH',   PUBLIC_BASE . 'uploads/sheets/');
if (!defined('SIGNED_UPLOAD_DIR')) define('SIGNED_UPLOAD_DIR', BASE_PATH . 'uploads/signed/');
if (!defined('SIGNED_WEB_PATH'))   define('SIGNED_WEB_PATH',   PUBLIC_BASE . 'uploads/signed/');

// Ensure folders
foreach ([SHEET_UPLOAD_DIR, SIGNED_UPLOAD_DIR] as $dir) {
  if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
}

// Helpers
function is_pdf($tmp) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $type  = finfo_file($finfo, $tmp);
  finfo_close($finfo);
  return in_array($type, ['application/pdf','application/x-pdf']);
}

function read_csv_to_array($path) {
  $rows = [];
  if (!file_exists($path)) return $rows;
  if (($h = fopen($path, 'r')) === false) return $rows;
  $header = fgetcsv($h);
  if (!$header) { fclose($h); return $rows; }
  while (($data = fgetcsv($h)) !== false) {
    $row = [];
    foreach ($header as $i => $col) $row[$col] = $data[$i] ?? '';
    $rows[] = $row;
  }
  fclose($h);
  return ['header'=>$header,'rows'=>$rows];
}

// NEW: read a CSV and locate the header row where Col A = "Last Name" and Col B = "First Name"
// Returns ['header' => array_of_headers, 'rows' => array_of_assoc_rows]
function read_csv_scan_name_header($path) {
    $result = ['header' => [], 'rows' => []];
    if (!file_exists($path)) return $result;

    if (($h = fopen($path, 'r')) === false) return $result;

    $header = null;
    $dataRows = [];

    // Scan until we find the header row: A="Last Name", B="First Name" (case-insensitive, trimmed)
    while (($row = fgetcsv($h)) !== false) {
        // Skip completely empty lines
        $allEmpty = true;
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') { $allEmpty = false; break; }
        }
        if ($allEmpty) continue;

        $a = isset($row[0]) ? trim($row[0]) : '';
        $b = isset($row[1]) ? trim($row[1]) : '';
        if (preg_match('/^last\s*name$/i', $a) && preg_match('/^first\s*name$/i', $b)) {
            $header = $row;
            break;
        }
    }

    // If we didn’t find the target header, fall back to the first non-empty row as header
    if ($header === null) {
        rewind($h);
        while (($row = fgetcsv($h)) !== false) {
            $allEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') { $allEmpty = false; break; }
            }
            if (!$allEmpty) { $header = $row; break; }
        }
        if ($header === null) { fclose($h); return $result; }
    }

    // Build rows from the remainder of the file
    while (($row = fgetcsv($h)) !== false) {
        // Skip empty lines
        $allEmpty = true;
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') { $allEmpty = false; break; }
        }
        if ($allEmpty) continue;

        $assoc = [];
        foreach ($header as $i => $colName) {
            $assoc[$colName] = $row[$i] ?? '';
        }
        $dataRows[] = $assoc;
    }

    fclose($h);
    $result['header'] = $header;
    $result['rows']   = $dataRows;
    return $result;
}

function create_signed_zip() {
  $zipName = BASE_PATH . 'uploads/signed_all_' . date('Ymd_His') . '.zip';
  $zip = new ZipArchive();
  if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SIGNED_UPLOAD_DIR));
  foreach ($it as $file) {
    if ($file->isDir()) continue;
    $filePath = $file->getRealPath();
    $rel = substr($filePath, strlen(SIGNED_UPLOAD_DIR));
    $zip->addFile($filePath, $rel);
  }
  $zip->close();
  return $zipName;
}

function sanitize_filename($name) {
  $name = preg_replace('/[^A-Za-z0-9_\.-]+/', '_', $name);
  return trim($name, '_');
}

function format_ddmmyyyy($mysqlDatetime) {
  if (!$mysqlDatetime) return '';
  $ts = strtotime($mysqlDatetime);
  return $ts ? date('d/m/Y', $ts) : '';
}