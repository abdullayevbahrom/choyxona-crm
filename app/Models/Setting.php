<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_address',
        'company_phone',
        'receipt_footer',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            ['company_name' => 'Choyxona CRM']
        );
    }
}
