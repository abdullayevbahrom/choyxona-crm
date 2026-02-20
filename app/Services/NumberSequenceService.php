<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    public function next(string $sequenceKey, int $year): int
    {
        return DB::transaction(function () use ($sequenceKey, $year) {
            $row = DB::table('number_sequences')
                ->where('sequence_key', $sequenceKey)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                try {
                    DB::table('number_sequences')->insert([
                        'sequence_key' => $sequenceKey,
                        'year' => $year,
                        'last_value' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException) {
                    // Concurrent insert: continue and re-read locked row.
                }

                $row = DB::table('number_sequences')
                    ->where('sequence_key', $sequenceKey)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();
            }

            $next = ((int) ($row?->last_value ?? 0)) + 1;

            DB::table('number_sequences')
                ->where('sequence_key', $sequenceKey)
                ->where('year', $year)
                ->update([
                    'last_value' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        });
    }
}
