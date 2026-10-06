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

    /** Total credit sales for one distributor. */
    public function creditTotal(int $id): int
    {
        return $this->fetchInt(
            "SELECT COALESCE(SUM(total), 0) FROM sales WHERE distributor_id = ? AND payment_type = 'credit'",
            [$id]
        );
    }

    /** Total collections for one distributor. */
    public function paidTotal(int $id): int
    {
        return $this->fetchInt(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE distributor_id = ?',
            [$id]
        );
    }

    /** Date of the distributor's last sale (any type) or payment. */
    public function lastActivity(int $id): ?string
    {
        $row = $this->fetchOne(
            "SELECT MAX(t) AS last_at FROM (
                (SELECT MAX(created_at) AS t FROM sales WHERE distributor_id = ?)
                UNION ALL
                (SELECT MAX(created_at) AS t FROM payments WHERE distributor_id = ?)
            ) AS x",
            [$id, $id]
        );
        return $row['last_at'] ?? null;
    }

    /**
     * كشف حساب الموزع — chronological ledger of credit sales (debit) and
     * payments (credit) with a running balance computed from actual rows.
     * $filters: date_from, date_to, type ('sale'|'payment'|'')
     */
    public function statement(int $id, array $filters = []): array
    {
        $params = [':id1' => $id, ':id2' => $id];
        $where  = [];

        $type = (string)($filters['type'] ?? '');
        if ($type === 'sale') {
            $where[] = 'kind = :kind';
            $params[':kind'] = 'sale';
        } elseif ($type === 'payment') {
            $where[] = 'kind = :kind';
            $params[':kind'] = 'payment';
        }

        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'created_at >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'created_at <= :date_to';
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        // الرصيد الافتتاحي قبل بداية الفترة (يُحسب فقط مع فلتر تاريخ وبدون فلتر نوع،
        // لأن فلتر النوع يجعل الكشف قائمة بحث لا كشفًا زمنيًا متصلاً).
        $opening = 0;
        if ($dateFrom !== '' && $type === '') {
            $row0 = $this->fetchOne(
                "SELECT COALESCE(SUM(x.net), 0) AS net FROM (
                    (SELECT COALESCE(SUM(total), 0) AS net FROM sales
                      WHERE distributor_id = :oid1 AND payment_type = 'credit' AND created_at < :odf1)
                    UNION ALL
                    (SELECT COALESCE(-SUM(amount), 0) AS net FROM payments
                      WHERE distributor_id = :oid2 AND created_at < :odf2)
                ) x",
                [
                    ':oid1' => $id, ':oid2' => $id,
                    ':odf1' => $dateFrom . ' 00:00:00', ':odf2' => $dateFrom . ' 00:00:00',
                ]
            );
            $opening = (int)($row0['net'] ?? 0);
        }

        $ledger = $this->fetchAll(
            "SELECT * FROM (
                SELECT s.id AS ref_id, 'sale' AS kind, s.created_at,
                       CONCAT('بيع آجل — ', s.bundles_count, ' شدة × ', s.bundle_price) AS description,
                       s.total AS debit, 0 AS credit
                  FROM sales s
                 WHERE s.distributor_id = :id1
                UNION ALL
                SELECT p.id, 'payment', p.created_at,
                       CONCAT('تحصيل', IF(p.note IS NOT NULL AND p.note <> '', CONCAT(' — ', p.note), '')),
                       0 AS debit, p.amount AS credit
                  FROM payments p
                 WHERE p.distributor_id = :id2
            ) AS ledger
            $whereSql
            ORDER BY created_at ASC, kind ASC, ref_id ASC",
            $params
        );

        // Running balance from the actual movements (opening + debit − credit).
        $balance = $opening;
        foreach ($ledger as &$row) {
            $balance += (int)$row['debit'] - (int)$row['credit'];
            $row['running'] = $balance;
        }
        unset($row);

        return [
            'ledger'     => $ledger,
            'opening'    => $opening,
            'sales_total'    => (int)array_sum(array_column($ledger, 'debit')),
            'payments_total' => (int)array_sum(array_column($ledger, 'credit')),
            'final_balance'  => $balance,
        ];
    }
}
