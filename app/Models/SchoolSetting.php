<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public const DEFAULTS = [
        'school_name' => 'Shobuj Ridge School & College',
        'short_name' => 'Shobuj Ridge',
        'eiin' => '134782',
        'phone' => '+880 2 5500 2244',
        'email' => 'hello@shobujridge.edu.bd',
        'address' => 'Road 12, Sector 10, Uttara, Dhaka 1230',
        'office_hours' => 'Sunday–Thursday · 8:00 am–4:00 pm',
        'map_embed_url' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'logo_path' => '',
    ];

    public static function values(): array
    {
        $stored = self::query()->pluck('value', 'key')->mapWithKeys(fn ($value, $key) => [$key => $value ?? (self::DEFAULTS[$key] ?? '')])->all();

        return [...self::DEFAULTS, ...$stored];
    }
}
