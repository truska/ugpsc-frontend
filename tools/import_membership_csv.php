<?php
declare(strict_types=1);

require_once __DIR__ . '/../../private/dbcon.php';

if (!($pdo instanceof PDO) || empty($DB_OK)) {
  fwrite(STDERR, "Database connection failed.\n");
  exit(1);
}

$csvPath = $argv[1] ?? '';
if ($csvPath === '' || !is_file($csvPath) || !is_readable($csvPath)) {
  fwrite(STDERR, "Usage: php tools/import_membership_csv.php /full/path/to/file.csv\n");
  exit(1);
}

$importDate = $argv[2] ?? date('d-m-Y');
$batchId = 'import-' . date('Ymd-His');

$expectedHeaders = [
  'Member', 'FirstName', 'LastName', 'Address1', 'Address2', 'TownOrCity', 'CountyOrProvince',
  'Country', 'PostalCode', 'EmailAddress', 'HomePhone', 'Subscription', 'History', 'History1',
  'History2', 'History3', 'History4', 'History5', 'History6', 'History7', 'History8', 'History9',
  'History10', 'Notes', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019',
  '2020', 'No Ballots', '2021', '2022', '2023', '2024', '2025', '2026', '2027', '2028', '2029', '2030',
];

$stageSql = 'INSERT INTO mem_member_import_stage (
  import_batch, source_file, source_row_number, member, firstname, lastname, address1, address2,
  town_or_city, county_or_province, country, postal_code, email_address, home_phone, subscription,
  history, history1, history2, history3, history4, history5, history6, history7, history8, history9,
  history10, notes, year_2011, year_2012, year_2013, year_2014, year_2015, year_2016, year_2017,
  year_2018, year_2019, year_2020, no_ballots, year_2021, year_2022, year_2023, year_2024, year_2025,
  year_2026, year_2027, year_2028, year_2029, year_2030
) VALUES (
  :import_batch, :source_file, :source_row_number, :member, :firstname, :lastname, :address1, :address2,
  :town_or_city, :county_or_province, :country, :postal_code, :email_address, :home_phone, :subscription,
  :history, :history1, :history2, :history3, :history4, :history5, :history6, :history7, :history8, :history9,
  :history10, :notes, :year_2011, :year_2012, :year_2013, :year_2014, :year_2015, :year_2016, :year_2017,
  :year_2018, :year_2019, :year_2020, :no_ballots, :year_2021, :year_2022, :year_2023, :year_2024, :year_2025,
  :year_2026, :year_2027, :year_2028, :year_2029, :year_2030
)';

$memberSql = 'INSERT INTO mem_member (
  membership_number, email, firstname, surname, address1, address2, town, county, country, postcode,
  tel1, notes, login_enabled, email_is_placeholder, password_hash, gdpr_policy_accepted, gdpr_marketing_opt_in, membership_status,
  membership_expires_at, years_paid_count, created_via, payment_method, showonweb, archived, sort
) VALUES (
  :membership_number, :email, :firstname, :surname, :address1, :address2, :town, :county, :country, :postcode,
  :tel1, :notes, :login_enabled, :email_is_placeholder, :password_hash, 0, 0, :membership_status, :membership_expires_at, 0, "admin", NULL, "Yes", 0, :sort
)';

$yearSql = 'INSERT INTO mem_membership_year (
  member_id, membership_year, source, notes, showonweb, archived
) VALUES (
  :member_id, :membership_year, :source, :notes, "Yes", 0
) ON DUPLICATE KEY UPDATE
  source = IF(source = VALUES(source), source, "admin"),
  notes = CASE
    WHEN notes IS NULL OR notes = "" THEN VALUES(notes)
    WHEN VALUES(notes) IS NULL OR VALUES(notes) = "" THEN notes
    WHEN INSTR(notes, VALUES(notes)) > 0 THEN notes
    ELSE CONCAT(notes, "; ", VALUES(notes))
  END,
  modified = CURRENT_TIMESTAMP';

$fh = fopen($csvPath, 'r');
if ($fh === false) {
  fwrite(STDERR, "Unable to open CSV file.\n");
  exit(1);
}

$headers = fgetcsv($fh);
if ($headers === false) {
  fwrite(STDERR, "CSV file is empty.\n");
  exit(1);
}

$headers = array_map(static fn($v) => trim((string) $v), $headers);
if ($headers !== $expectedHeaders) {
  fwrite(STDERR, "CSV headers do not match expected format.\n");
  fwrite(STDERR, 'Found: ' . implode(',', $headers) . "\n");
  exit(1);
}

function import_trim(array $row, string $key): string {
  $value = (string) ($row[$key] ?? '');
  if ($value === '') {
    return '';
  }
  if (mb_check_encoding($value, 'UTF-8')) {
    return trim($value);
  }
  $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
  if (is_string($converted) && $converted !== '') {
    return trim($converted);
  }
  return trim(utf8_encode($value));
}

function import_bool_string(string $value): bool {
  return strtoupper(trim($value)) === 'TRUE';
}

function import_extract_year(string $value): ?int {
  if ($value === '') {
    return null;
  }
  if (preg_match('/\b(19|20)\d{2}\b/', $value, $matches) === 1) {
    return (int) $matches[0];
  }
  return null;
}

function import_placeholder_email(int $membershipNumber, string $reason): string {
  return 'member-' . $membershipNumber . '-' . $reason . '@import.invalid';
}

try {
  $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
  foreach ([
    'mem_activity_log',
    'mem_magic_link',
    'mem_password_reset',
    'mem_membership_year',
    'mem_transaction',
    'mem_member_import_stage',
    'mem_member',
  ] as $table) {
    $pdo->exec('TRUNCATE TABLE ' . $table);
  }
  $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

  $pdo->beginTransaction();

  $stageStmt = $pdo->prepare($stageSql);
  $memberStmt = $pdo->prepare($memberSql);
  $yearStmt = $pdo->prepare($yearSql);
  $updateStmt = $pdo->prepare(
    'UPDATE mem_member
     SET years_paid_count = :years_paid_count,
         membership_status = :membership_status,
         membership_expires_at = :membership_expires_at
     WHERE id = :id'
  );

  $usedEmails = [];
  $report = [
    'batch_id' => $batchId,
    'csv_path' => $csvPath,
    'rows_read' => 0,
    'members_imported' => 0,
    'year_rows_inserted' => 0,
    'blank_email_placeholders' => 0,
    'duplicate_email_placeholders' => 0,
    'invalid_email_placeholders' => 0,
    'history_year_duplicates_skipped' => 0,
    'history_year_conflicts' => [],
    'email_exceptions' => [],
  ];

  $sourceFile = basename($csvPath);
  $rowNumber = 1;

  while (($csvRow = fgetcsv($fh)) !== false) {
    $rowNumber++;
    $report['rows_read']++;
    $csvRow = array_pad($csvRow, count($headers), '');
    $row = array_combine($headers, $csvRow);

    $membershipNumber = (int) import_trim($row, 'Member');
    if ($membershipNumber <= 0) {
      throw new RuntimeException('Invalid membership number on row ' . $rowNumber);
    }

    $stageParams = [
      ':import_batch' => $batchId,
      ':source_file' => $sourceFile,
      ':source_row_number' => $rowNumber,
      ':member' => import_trim($row, 'Member'),
      ':firstname' => import_trim($row, 'FirstName'),
      ':lastname' => import_trim($row, 'LastName'),
      ':address1' => import_trim($row, 'Address1'),
      ':address2' => import_trim($row, 'Address2'),
      ':town_or_city' => import_trim($row, 'TownOrCity'),
      ':county_or_province' => import_trim($row, 'CountyOrProvince'),
      ':country' => import_trim($row, 'Country'),
      ':postal_code' => import_trim($row, 'PostalCode'),
      ':email_address' => import_trim($row, 'EmailAddress'),
      ':home_phone' => import_trim($row, 'HomePhone'),
      ':subscription' => import_trim($row, 'Subscription'),
      ':history' => import_trim($row, 'History'),
      ':history1' => import_trim($row, 'History1'),
      ':history2' => import_trim($row, 'History2'),
      ':history3' => import_trim($row, 'History3'),
      ':history4' => import_trim($row, 'History4'),
      ':history5' => import_trim($row, 'History5'),
      ':history6' => import_trim($row, 'History6'),
      ':history7' => import_trim($row, 'History7'),
      ':history8' => import_trim($row, 'History8'),
      ':history9' => import_trim($row, 'History9'),
      ':history10' => import_trim($row, 'History10'),
      ':notes' => import_trim($row, 'Notes'),
      ':year_2011' => import_trim($row, '2011'),
      ':year_2012' => import_trim($row, '2012'),
      ':year_2013' => import_trim($row, '2013'),
      ':year_2014' => import_trim($row, '2014'),
      ':year_2015' => import_trim($row, '2015'),
      ':year_2016' => import_trim($row, '2016'),
      ':year_2017' => import_trim($row, '2017'),
      ':year_2018' => import_trim($row, '2018'),
      ':year_2019' => import_trim($row, '2019'),
      ':year_2020' => import_trim($row, '2020'),
      ':no_ballots' => import_trim($row, 'No Ballots'),
      ':year_2021' => import_trim($row, '2021'),
      ':year_2022' => import_trim($row, '2022'),
      ':year_2023' => import_trim($row, '2023'),
      ':year_2024' => import_trim($row, '2024'),
      ':year_2025' => import_trim($row, '2025'),
      ':year_2026' => import_trim($row, '2026'),
      ':year_2027' => import_trim($row, '2027'),
      ':year_2028' => import_trim($row, '2028'),
      ':year_2029' => import_trim($row, '2029'),
      ':year_2030' => import_trim($row, '2030'),
    ];
    $stageStmt->execute($stageParams);

    $emailRaw = strtolower(import_trim($row, 'EmailAddress'));
    $emailReason = '';
    if ($emailRaw === '') {
      $emailReason = 'blank-email';
      $email = import_placeholder_email($membershipNumber, $emailReason);
      $report['blank_email_placeholders']++;
    } elseif (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
      $emailReason = 'invalid-email';
      $email = import_placeholder_email($membershipNumber, $emailReason);
      $report['invalid_email_placeholders']++;
    } elseif (isset($usedEmails[$emailRaw])) {
      $emailReason = 'duplicate-email';
      $email = import_placeholder_email($membershipNumber, $emailReason);
      $report['duplicate_email_placeholders']++;
    } else {
      $email = $emailRaw;
      $usedEmails[$emailRaw] = true;
    }

    if ($emailReason !== '') {
      $report['email_exceptions'][] = [
        'membership_number' => $membershipNumber,
        'original_email' => $emailRaw,
        'stored_email' => $email,
        'reason' => $emailReason,
      ];
    }

    $loginEnabled = $emailReason === '' ? 1 : 0;
    $emailIsPlaceholder = $emailReason === '' ? 0 : 1;

    $memberNote = 'Imported from old database ' . $importDate;
    $memberStmt->execute([
      ':membership_number' => $membershipNumber,
      ':email' => $email,
      ':firstname' => import_trim($row, 'FirstName'),
      ':surname' => import_trim($row, 'LastName'),
      ':address1' => import_trim($row, 'Address1'),
      ':address2' => import_trim($row, 'Address2'),
      ':town' => import_trim($row, 'TownOrCity'),
      ':county' => import_trim($row, 'CountyOrProvince'),
      ':country' => import_trim($row, 'Country'),
      ':postcode' => import_trim($row, 'PostalCode'),
      ':tel1' => import_trim($row, 'HomePhone'),
      ':notes' => $memberNote,
      ':login_enabled' => $loginEnabled,
      ':email_is_placeholder' => $emailIsPlaceholder,
      ':password_hash' => password_hash('import-' . $membershipNumber . '-' . bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
      ':membership_status' => 'lapsed',
      ':membership_expires_at' => null,
      ':sort' => $membershipNumber,
    ]);

    $memberId = (int) $pdo->lastInsertId();
    $report['members_imported']++;

    $years = [];
    for ($i = 0; $i <= 10; $i++) {
      $key = $i === 0 ? 'History' : 'History' . $i;
      $raw = import_trim($row, $key);
      $year = import_extract_year($raw);
      if ($year === null) {
        continue;
      }
      if (isset($years[$year])) {
        $report['history_year_duplicates_skipped']++;
        continue;
      }
      $years[$year] = [
        'source' => 'import_history',
        'notes' => $key . ': ' . $raw,
      ];
    }

    for ($year = 2011; $year <= 2030; $year++) {
      $raw = import_trim($row, (string) $year);
      if (!import_bool_string($raw)) {
        continue;
      }
      if (isset($years[$year])) {
        if (($years[$year]['source'] ?? '') !== 'import_year_flag') {
          $report['history_year_conflicts'][] = [
            'membership_number' => $membershipNumber,
            'year' => $year,
            'history_value' => $years[$year]['notes'] ?? '',
            'year_flag' => $raw,
          ];
          $years[$year]['notes'] = trim(($years[$year]['notes'] ?? '') . '; ' . $year . ' column TRUE', '; ');
        }
        continue;
      }
      $years[$year] = [
        'source' => 'import_year_flag',
        'notes' => $year . ' column TRUE',
      ];
    }

    ksort($years);
    foreach ($years as $membershipYear => $yearData) {
      $yearStmt->execute([
        ':member_id' => $memberId,
        ':membership_year' => $membershipYear,
        ':source' => $yearData['source'],
        ':notes' => $yearData['notes'],
      ]);
      $report['year_rows_inserted']++;
    }

    $latestYear = $years ? (int) max(array_keys($years)) : null;
    $currentYear = (int) date('Y');
    $membershipStatus = ($latestYear !== null && $latestYear >= $currentYear) ? 'active' : 'lapsed';
    $expiresAt = $latestYear !== null ? sprintf('%04d-12-31', $latestYear) : null;

    $updateStmt->execute([
      ':years_paid_count' => count($years),
      ':membership_status' => $membershipStatus,
      ':membership_expires_at' => $expiresAt,
      ':id' => $memberId,
    ]);
  }

  fclose($fh);
  $pdo->commit();

  $reportPath = __DIR__ . '/../tmp/membership-import-report-' . date('Ymd-His') . '.json';
  file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  echo json_encode([
    'ok' => true,
    'batch_id' => $report['batch_id'],
    'rows_read' => $report['rows_read'],
    'members_imported' => $report['members_imported'],
    'year_rows_inserted' => $report['year_rows_inserted'],
    'blank_email_placeholders' => $report['blank_email_placeholders'],
    'duplicate_email_placeholders' => $report['duplicate_email_placeholders'],
    'invalid_email_placeholders' => $report['invalid_email_placeholders'],
    'history_year_duplicates_skipped' => $report['history_year_duplicates_skipped'],
    'history_year_conflicts' => count($report['history_year_conflicts']),
    'report_path' => $reportPath,
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  fclose($fh);
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
