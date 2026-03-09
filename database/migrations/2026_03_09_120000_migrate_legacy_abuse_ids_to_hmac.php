<?php

use App\ValueObjects\AbuseId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateTable('post_metas');
        $this->migrateTable('bans');
    }

    public function down(): void
    {
        // One-way migration: legacy abuse IDs were raw user IDs and cannot be restored from HMAC.
    }

    private function migrateTable(string $table): void
    {
        $keyColumn = $table === 'post_metas' ? 'post_id' : 'id';

        DB::table($table)
            ->select($keyColumn, 'abuse_id')
            ->orderBy($keyColumn)
            ->chunk(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $legacy = (string) ($row->abuse_id ?? '');

                    if (! preg_match('/^u:(\d+)$/', $legacy, $m)) {
                        continue;
                    }

                    $newAbuseId = AbuseId::fromUserId((int) $m[1])->value();
                    $keyColumn = $table === 'post_metas' ? 'post_id' : 'id';
                    $keyValue = $row->{$keyColumn};

                    DB::table($table)
                        ->where($keyColumn, $keyValue)
                        ->update(['abuse_id' => $newAbuseId]);
                }
            });
    }
};
