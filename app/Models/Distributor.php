<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Distributor extends Model
{
    /**
     * All distributors with derived financial columns:
     *   credit_total = مجموع مبيعات الآجل (الآجل)
     *   paid_total   = مجموع التحصيلات (التحصيل)
     *   balance      = الآجل − التحصيل  (الرصيد المستحق)
     *
     * المبيعات النقدية لا تدخل في هذه الحسابات.
     */
    public function all(): array
    {
        return $this->fetchAll(
            "SELECT d.*,
                    COALESCE((SELECT SUM(s.total) FROM sales s WHERE s.distributor_id = d.id AND s.payment_type = 'credit'), 0) AS credit_total,
                    COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.distributor_id = d.id), 0) AS paid_total
               FROM distributors d
              ORDER BY d.name"
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM distributors WHERE id = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('distributors', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->updateRow('distributors', $id, $data);
    }

    public function delete(int $id): int
    {
        return $this->deleteRow('distributors', $id);
    }

    public function count(): int
    {
        return $this->fetchInt('SELECT COUNT(*) FROM distributors');
    }

    /** Total outstanding debt across all distributors (الآجل − التحصيل). */
    public function totalDebt(): int
    {
        return $this->fetchInt(
            "SELECT COALESCE(SUM(
                (SELECT COALESCE(SUM(s.total), 0) FROM sales s WHERE s.distributor_id = d.id AND s.payment_type = 'credit')
                -
                (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.distributor_id = d.id)
            ), 0)
               FROM distributors d
              HAVING SUM(1) > 0"
        );
    }

    /** الرصيد المستحق = الآجل − التحصيل */
    public function balance(int $id): int
    {
        $credit = $this->fetchInt(
            "SELECT COALESCE(SUM(total), 0) FROM sales WHERE distributor_id = ? AND payment_type = 'credit'",
            [$id]
        );
        $paid = $this->fetchInt(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE distributor_id = ?',
            [$id]
        );
        return $credit - $paid;
    }
}
