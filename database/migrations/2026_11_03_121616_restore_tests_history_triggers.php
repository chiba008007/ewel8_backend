<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    private const ORIGINAL_MIGRATION =
        '2025_09_07_231422_create_trigger_test_history.php';

    private const TRIGGER_NAMES = [
        'tests_after_insert',
        'tests_after_update',
    ];

    public function up(): void
    {
        $existing = DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::getDatabaseName())
            ->whereIn('TRIGGER_NAME', self::TRIGGER_NAMES)
            ->pluck('TRIGGER_NAME')
            ->all();

        // 両方存在する場合は何もしない
        if (count($existing) === count(self::TRIGGER_NAMES)) {
            return;
        }

        // 片方だけ存在する場合は停止
        if ($existing !== []) {
            throw new RuntimeException(
                'Only some tests history triggers exist: '
                . implode(', ', $existing)
            );
        }

        // 既存マイグレーションの定義を再利用
        $path = database_path(
            'migrations/' . self::ORIGINAL_MIGRATION
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                'Original trigger migration not found: ' . $path
            );
        }

        $original = require $path;

        if (!$original instanceof Migration ||
            !is_callable([$original, 'up'])) {
            throw new RuntimeException(
                'Original migration is invalid.'
            );
        }

        call_user_func([$original, 'up']);
    }

    public function down(): void
    {
        // 復旧したトリガーを誤って削除しない
    }
};
