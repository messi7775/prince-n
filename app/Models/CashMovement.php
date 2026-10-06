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
