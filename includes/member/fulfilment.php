<?php
require_once __DIR__ . '/bootstrap.php';

function mem_queue_fulfilment(int $memberId, int $transactionId, string $fulfilmentType): bool {
  global $pdo, $DB_OK;

  if (
    $memberId <= 0
    || $transactionId <= 0
    || !$DB_OK
    || !($pdo instanceof PDO)
    || !mem_table_exists('mem_fulfilment')
  ) {
    return false;
  }

  $fulfilmentType = $fulfilmentType === 'renewal' ? 'renewal' : 'join';
  $sql = 'INSERT INTO mem_fulfilment (
            member_id, transaction_id, fulfilment_type, status,
            membership_number, recipient_name, email,
            address1, address2, town, county, country, postcode,
            queued_at
          )
          SELECT
            m.id, :transaction_id, :fulfilment_type, "pending",
            m.membership_number,
            TRIM(CONCAT_WS(" ", NULLIF(m.firstname, ""), NULLIF(m.surname, ""))),
            m.email,
            m.address1, m.address2, m.town, m.county, m.country, m.postcode,
            COALESCE(t.paid_at, t.created, NOW())
          FROM mem_member m
          JOIN mem_transaction t ON t.id = :transaction_id AND t.member_id = m.id
          WHERE m.id = :member_id
            AND t.status = "paid"
            AND t.transaction_type IN ("join", "renewal")
          ON DUPLICATE KEY UPDATE
            modified = NOW()';

  try {
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
      ':member_id' => $memberId,
      ':transaction_id' => $transactionId,
      ':fulfilment_type' => $fulfilmentType,
    ]);
  } catch (PDOException $e) {
    return false;
  }
}
