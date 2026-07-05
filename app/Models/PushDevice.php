<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $device_key
 * @property string $expo_push_token
 * @property string $platform
 * @property string|null $device_name
 * @property string|null $app_version
 * @property Carbon $last_registered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'device_key',
    'expo_push_token',
    'platform',
    'device_name',
    'app_version',
    'last_registered_at',
])]
class PushDevice extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_registered_at' => 'datetime',
        ];
    }
}
