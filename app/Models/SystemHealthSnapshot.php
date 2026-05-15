<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthSnapshot extends Model
{
    protected $fillable = [
        'overall_status',
        'checks',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checks' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->overall_status) {
            'ok' => 'OK',
            'warning' => 'Varning',
            'error' => 'Fel',
            default => 'Okänd',
        };
    }

    /**
     * @return array{ok: int, warning: int, error: int}
     */
    public function checkCounts(): array
    {
        $counts = ['ok' => 0, 'warning' => 0, 'error' => 0];

        foreach ($this->checks as $check) {
            $status = $check['status'] ?? 'ok';

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function checkSummary(): string
    {
        $counts = $this->checkCounts();

        return sprintf(
            '%d OK, %d varningar, %d fel',
            $counts['ok'],
            $counts['warning'],
            $counts['error']
        );
    }
}
