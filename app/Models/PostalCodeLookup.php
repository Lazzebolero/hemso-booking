<?php

namespace App\Models;

use Database\Factories\PostalCodeLookupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostalCodeLookup extends Model
{
    /** @use HasFactory<PostalCodeLookupFactory> */
    use HasFactory;

    protected $primaryKey = 'postal_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'postal_code',
        'locality',
        'municipality_code',
        'municipality_name',
        'county_code',
        'county_name',
    ];

    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) !== 5) {
            return null;
        }

        return $digits;
    }
}
