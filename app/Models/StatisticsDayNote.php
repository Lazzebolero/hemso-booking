<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\StatisticsDayNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class StatisticsDayNote extends Model
{
    /** @use HasFactory<StatisticsDayNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'note_date',
        'body',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'note_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Collection<int, string>|array<int, string>  $dates  Y-m-d keys
     * @return array<string, string> Y-m-d => body
     */
    public static function mapForDates(Collection|array $dates): array
    {
        $keys = collect($dates)
            ->filter(fn ($d) => is_string($d) && $d !== '')
            ->unique()
            ->values();

        if ($keys->isEmpty()) {
            return [];
        }

        return static::query()
            ->where(function ($query) use ($keys): void {
                foreach ($keys as $key) {
                    $query->orWhereDate('note_date', $key);
                }
            })
            ->get(['note_date', 'body'])
            ->mapWithKeys(fn (self $note) => [
                $note->note_date->toDateString() => (string) $note->body,
            ])
            ->all();
    }

    /**
     * @return array<string, string> Y-m-d => body
     */
    public static function mapForRange(CarbonInterface $from, CarbonInterface $to): array
    {
        return static::query()
            ->whereDate('note_date', '>=', $from->toDateString())
            ->whereDate('note_date', '<=', $to->toDateString())
            ->get(['note_date', 'body'])
            ->mapWithKeys(fn (self $note) => [
                $note->note_date->toDateString() => (string) $note->body,
            ])
            ->all();
    }
}
