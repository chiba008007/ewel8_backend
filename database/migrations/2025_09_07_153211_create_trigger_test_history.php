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
        // userlisences AFTER INSERT trigger
        DB::unprepared("
            CREATE TRIGGER userlisences_after_insert
            AFTER INSERT ON userlisences
            FOR EACH ROW
            BEGIN
                DECLARE utype VARCHAR(255);
                DECLARE upartner_id INT;
                DECLARE ucustomer_id INT;

                SELECT type, partner_id, id
                INTO utype, upartner_id, ucustomer_id
                FROM users
                WHERE id = NEW.user_id;

                IF utype = 'partner' THEN
                    INSERT INTO trigger_history (partner_id, customer_id, type, testtype, status, num, testname, created_at, updated_at)
                    VALUES (ucustomer_id, NULL, utype, NEW.code, 'add', NEW.num, NULL, NOW(), NOW());
                ELSEIF utype = 'customer' THEN
                    INSERT INTO trigger_history (partner_id, customer_id, type, testtype, status, num, testname, created_at, updated_at)
                    VALUES (NEW.user_id, ucustomer_id, utype, NEW.code, 'add', NEW.num, NULL, NOW(), NOW());
                END IF;
            END;
        ");

        // userlisences AFTER UPDATE trigger
        DB::unprepared("
            CREATE TRIGGER userlisences_after_update
            AFTER UPDATE ON userlisences
            FOR EACH ROW
            BEGIN
                DECLARE utype VARCHAR(255);
                DECLARE upartner_id INT;
                DECLARE ucustomer_id INT;
                DECLARE action VARCHAR(11);
                DECLARE diff INT;

                IF NEW.num > OLD.num THEN
                    SET action = 'add';
                    SET diff = NEW.num - OLD.num;
                ELSEIF NEW.num < OLD.num THEN
                    SET action = 'delete';
                    SET diff = OLD.num - NEW.num;
                ELSE
                    SET action = NULL;
                END IF;

                IF action IS NOT NULL THEN
                    SELECT type, partner_id, id
                    INTO utype, upartner_id, ucustomer_id
                    FROM users
                    WHERE id = NEW.user_id;

                    IF utype = 'partner' THEN
                        INSERT INTO trigger_history (partner_id, customer_id, type, testtype, status, num, testname, created_at, updated_at)
                        VALUES (ucustomer_id, NULL, utype, NEW.code, action, diff, NULL, NOW(), NOW());
                    ELSEIF utype = 'customer' THEN
                        INSERT INTO trigger_history (partner_id, customer_id, type, testtype, status, num, testname, created_at, updated_at)
                        VALUES (NEW.user_id, ucustomer_id, utype, NEW.code, action, diff, NULL, NOW(), NOW());
                    END IF;
                END IF;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS userlisences_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS userlisences_after_update');
    }
};
