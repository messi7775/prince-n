<?php
declare(strict_types=1);

namespace Controllers;

use Controller;
use Request;
use Session;
use Models\AuditLog;
use Models\Admin;

final class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();

        $this->view('settings/index', [
            'pageTitle'  => 'الإعدادات',
            'active'     => 'settings',
            'adminEmail' => Session::adminEmail(),
            'success'    => '',
            'error'      => '',
        ]);
    }

    public function changePassword(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $current = (string)$request->input('current_password', '');
        $new     = (string)$request->input('new_password', '');
        $confirm = (string)$request->input('confirm_password', '');

        $error = '';
        $success = '';

        $admin = new Admin();
        $record = $admin->findById(Session::adminId() ?? 0);

        if ($record === null) {
            $error = 'تعذر العثور على الحساب.';
        } elseif (!password_verify($current, $record['password_hash'])) {
            $error = 'كلمة المرور الحالية غير صحيحة.';
        } elseif (strlen($new) < 6) {
            $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.';
        } elseif ($new !== $confirm) {
            $error = 'تأكيد كلمة المرور غير متطابق.';
        } else {
            $admin->updatePassword($record['id'], password_hash($new, PASSWORD_DEFAULT));
            $success = 'تم تغيير كلمة المرور بنجاح.';
            $this->logAudit('password_change', 'تغيير كلمة المرور');
        }

        $this->view('settings/index', [
            'pageTitle'  => 'الإعدادات',
            'active'     => 'settings',
            'adminEmail' => Session::adminEmail(),
            'success'    => $success,
            'error'      => $error,
        ]);
    }

    public function audit(Request $request): void
    {
        $this->requireAuth();

        $log = new AuditLog();
        $entries = $log->all(200);

        $this->view('settings/audit', [
            'pageTitle' => 'سجل التدقيق',
            'active'    => 'audit',
            'entries'   => $entries,
        ]);
    }

    public function backup(Request $request): void
    {
        $this->requireAuth();

        $this->view('settings/backup', [
            'pageTitle' => 'النسخ الاحتياطي',
            'active'    => 'backup',
        ]);
    }

    public function createBackup(Request $request): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $this->logAudit('backup_create', 'إنشاء نسخة احتياطية');

        // Dump the database as SQL and stream as download
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="prince_cards_backup_' . date('Y-m-d_His') . '.sql"');

        $pdo = \Database::connection();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        echo "-- Prince Cards Backup\n-- " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            echo "DROP TABLE IF EXISTS `$table`;\n";

            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            echo $create['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) continue;

            $cols = array_keys($rows[0]);
            echo "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $vals = array_map(static function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string)$v);
                }, array_values($row));
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            echo implode(",\n", $values) . ";\n\n";
        }

        exit;
    }
}
