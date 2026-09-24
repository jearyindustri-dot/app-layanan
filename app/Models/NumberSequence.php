<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'prefix',
        'period',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }

    /**
     * Generate the next formatted sequence number atomically with row locking.
     * Format: {prefix}-{period}-{padded_number} (e.g., DTSEN-202610-00001)
     */
    public static function nextFormattedNumber(string $prefix, ?string $period = null, int $padding = 5): string
    {
        $period = $period ?? now()->format('Ym');

        return DB::transaction(function () use ($prefix, $period, $padding) {
            $sequence = static::where('prefix', $prefix)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = static::create([
                    'prefix' => $prefix,
                    'period' => $period,
                    'last_number' => 0,
                ]);
            }

            $sequence->increment('last_number');

            return sprintf('%s-%s-%s', $prefix, $period, str_pad((string) $sequence->last_number, $padding, '0', STR_PAD_LEFT));
        });
    }
}
