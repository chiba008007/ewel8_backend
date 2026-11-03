<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $exists = DB::selectOne("
            SELECT COUNT(*) AS cnt
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = 'testparts_after_insert_history'
        ");

        if ((int) $exists->cnt === 0) {
            DB::unprepared("
                CREATE TRIGGER testparts_after_insert_history
                AFTER INSERT ON testparts
                FOR EACH ROW
                BEGIN
                    INSERT INTO trigger_history (
                        partner_id, customer_id, type,
                        testtype, status, num, testname,
                        created_at, updated_at
                    )
                    SELECT
                        t.partner_id,
                        t.customer_id,
                        'customer',
                        NEW.code,
                        'add',
                        t.testcount,
                        t.testname,
                        NOW(),
                        NOW()
                    FROM tests t
                    WHERE t.id = NEW.test_id;
                END
            ");
        }

        DB::unprepared(
            'DROP TRIGGER IF EXISTS tests_after_insert'
        );
    }

    public function down(): void
    {
        // 履歴記録の欠落を避けるため自動削除しない
    }
};
