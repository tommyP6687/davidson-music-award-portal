<?php
require_once "middleware.php"; require_role('student');
require_once "config.php"; require_once "utils.php";

$first    = trim($_POST['first_name'] ?? '');
$last     = trim($_POST['last_name'] ?? '');
$decision = $_POST['decision'] ?? '';

if ($first === '' || $last === '' || !in_array($decision, ['accepted','declined'], true)) {
  die("Invalid form submission.");
}

if ($decision === 'accepted') {
    if (empty($_POST['accept_ack'])) {
        die("You must confirm that you accept the penalties of withdrawing from lessons after the second week of class before submitting.");
    }
    if (empty($_POST['registered_ack'])) {
        die("You must confirm that you have registered for your required ensemble and lessons before submitting.");
    }
}

$signedPathWeb = null;

if ($decision === 'accepted') {
  // Require a PDF file on Accept
  if (!isset($_FILES['signed_pdf']) || $_FILES['signed_pdf']['error'] !== UPLOAD_ERR_OK) {
    die("Please upload your signed award letter PDF.");
  }

  $tmp  = $_FILES['signed_pdf']['tmp_name'];
  $orig = $_FILES['signed_pdf']['name'];

  if (!is_pdf($tmp)) {
    die("File must be a PDF.");
  }

  // Save with a sanitized, unique filename
  $safe = sanitize_filename($last . "_" . $first . "_" . time() . "_" . $orig);
  if (!move_uploaded_file($tmp, SIGNED_UPLOAD_DIR . $safe)) {
    die("Could not save file.");
  }
  $signedPathWeb = SIGNED_WEB_PATH . $safe;
}

$email = $_SESSION['email'] ?? null;

// Upsert by name (optionally add a UNIQUE index on (last_name, first_name) for strictness)
$stmt = $pdo->prepare("
  INSERT INTO submissions (first_name, last_name, email, status, signed_pdf_path)
  VALUES (?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    signed_pdf_path = VALUES(signed_pdf_path),
    submitted_at = CURRENT_TIMESTAMP
");

try {
  $stmt->execute([$first, $last, $email, $decision, $signedPathWeb]);
} catch (PDOException $e) {
  // If no unique key exists, fall back to simple insert
  $stmt2 = $pdo->prepare("
    INSERT INTO submissions (first_name, last_name, email, status, signed_pdf_path)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt2->execute([$first, $last, $email, $decision, $signedPathWeb]);
}

header("Location: student_dashboard.php?ok=1");
exit;