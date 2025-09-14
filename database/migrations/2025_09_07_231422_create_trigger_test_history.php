<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */

    public function up(): void
    {

        DB::unprepared("
            CREATE TRIGGER tests_after_update
            AFTER UPDATE ON tests
            FOR EACH ROW
            BEGIN
                DECLARE action VARCHAR(11);
                DECLARE diff INT;

                IF NEW.testcount > OLD.testcount THEN
                    SET action = 'add';
                    SET diff = NEW.testcount - OLD.testcount;
                ELSEIF NEW.testcount < OLD.testcount THEN
                    SET action = 'delete';
                    SET diff = OLD.testcount - NEW.testcount;
                ELSE
                    SET action = NULL;
                    SET diff = 0;
                END IF;

                IF action IS NOT NULL AND diff > 0 THEN
                    INSERT INTO trigger_history (
                        partner_id,
                        customer_id,
                        type,
                        testtype,
                        status,
                        num,
                        testname,
                        created_at,
                        updated_at
                    )
                    SELECT
                        NEW.partner_id,
                        NEW.customer_id,
                        'customer',
                        tp.code,
                        action,
                        diff,
                        NEW.testname,
                        NOW(),
                        NOW()
                    FROM testparts tp
                    WHERE tp.test_id = NEW.id;
                END IF;
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER tests_after_insert
                AFTER INSERT ON tests
                FOR EACH ROW
                BEGIN
                    INSERT INTO trigger_history (
                        partner_id,
                        customer_id,
                        type,
                        testtype,
                        status,
                        num,
                        testname,
                        created_at,
                        updated_at
                    )
                    SELECT
                        NEW.partner_id,
                        NEW.customer_id,
                        'customer',
                        tp.code,
                        'add',
                        NEW.testcount,   -- 初期登録の testcount をそのまま num に入れる
                        NEW.testname,
                        NOW(),
                        NOW()
                    FROM testparts tp
                    WHERE tp.test_id = NEW.id;
                END;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tests_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS tests_after_update');
    }

};
