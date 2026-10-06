<?php
declare(strict_types=1);

namespace Models;

use Model;

final class CashMovement extends Model
{
    public const IN  = 'in';
    public const OUT = 'out';

    public function all(int $limit = 200): array
    {
        return $this->fetchAll(
            'SELECT * FROM cash_movements ORDER BY created_at DESC LIMIT ' . (int)$limit
        );
    }

    public function create(array $data): int
    {
        return $this->insert('cash_movements', $data);
    }

    /**
     * All movements with a running balance computed from the actual rows
     * (oldest first), newest displayed last. Used by the cash page.
     */
    public function allWithRunningBalance(): array
    {
        $rows = $this->fetchAll('SELECT * FROM cash_movements ORDER BY id ASC');

        $balance = 0;
        foreach ($rows as &$row) {
            $balance += $row['direction'] === 'in' ? (int)$row['amount'] : -(int)$row['amount'];
            $row['running'] = $balance;
        }
        unset($row);

        return array_reverse($rows);
    }

    /**
     * Cash movements with search + filters (direction, date range, reason text),
     * running balance over the filtered set, plus filtered totals.
     */
    public function search(array $filters = []): array
    {
        $where  = [];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'reason LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $direction = (string)($filters['direction'] ?? '');
        if (in_array($direction, ['in', 'out'], true)) {
            $where[] = 'direction = :direction';
            $params['direction'] = $direction;
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $rows = $this->fetchAll(
            "SELECT * FROM cash_movements $whereSql ORDER BY created_at ASC, id ASC",
            $params
        );

        $balance = 0;
        $totalIn = 0;
        $totalOut = 0;
        foreach ($rows as $i => $row) {
            $amount = (int)$row['amount'];
            if ($row['direction'] === 'in') {
                $balance += $amount;
                $totalIn += $amount;
            } else {
                $balance -= $amount;
                $totalOut += $amount;
            }
            $rows[$i]['running'] = $balance;
        }

        // Newest first for display; running balance was computed oldest first.
        $rows = array_reverse($rows);

        return [
            'rows'      => $rows,
            'total_in'  => $totalIn,
            'total_out' => $totalOut,
            'balance'   => $balance,
            'count'     => count($rows),
        ];
    }

    /** Daily cash in/out totals for the last N days. */
    public function dailyTotals(int $days = 7): array
    {
        return $this->fetchAll(
            "SELECT DATE(created_at) AS day,
                    COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) AS cash_in,
                    COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) AS cash_out
               FROM cash_movements
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
              GROUP BY DATE(created_at)"
        );
    }

    public function balance(): int
    {
        $in  = $this->fetchInt("SELECT COALESCE(SUM(amount), 0) FROM cash_movements WHERE direction = 'in'");
        $out = $this->fetchInt("SELECT COALESCE(SUM(amount), 0) FROM cash_movements WHERE direction = 'out'");
        return $in - $out;
    }

    public function totalIn(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(amount), 0) FROM cash_movements WHERE direction = 'in'");
    }

    public function totalOut(): int
    {
        return $this->fetchInt("SELECT COALESCE(SUM(amount), 0) FROM cash_movements WHERE direction = 'out'");
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('cash_movements', $id);
    }

    /** Update the amount of all cash movements linked to a reference. */
    public function updateAmountByReference(string $referenceType, int $referenceId, int $amount): int
    {
        return $this->execute(
            "UPDATE cash_movements SET amount = ? WHERE reference_type = ? AND reference_id = ?",
            [$amount, $referenceType, $referenceId]
        );
    }

    /** Update a linked cash movement and force its accounting direction/reason. */
    public function updateByReference(string $referenceType, int $referenceId, int $amount, string $direction, string $reason): int
    {
        return $this->execute(
            "UPDATE cash_movements SET amount = ?, direction = ?, reason = ? WHERE reference_type = ? AND reference_id = ?",
            [$amount, $direction, $reason, $referenceType, $referenceId]
        );
    }

    /** Delete cash movements for all line payments belonging to one line. */
    public function deleteByLineId(int $lineId): int
    {
        return $this->execute(
            "DELETE FROM cash_movements WHERE reference_type = 'line_payment' AND reference_id IN (SELECT id FROM line_payments WHERE line_id = ?)",
            [$lineId]
        );
    }

    /** Return one cash movement linked to a reference. */
    public function fetchByReference(string $referenceType, int $referenceId): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM cash_movements WHERE reference_type = ? AND reference_id = ? ORDER BY id DESC LIMIT 1",
            [$referenceType, $referenceId]
        );
    }

    /** Delete all cash movements linked to a given reference (e.g. a sale). */
    public function deleteByReference(string $referenceType, int $referenceId): int
    {
        return $this->execute(
            "DELETE FROM cash_movements WHERE reference_type = ? AND reference_id = ?",
            [$referenceType, $referenceId]
        );
    }
}
