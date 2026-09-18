<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $phone_display
 * @property-read string $phone_local
 * @property-read int $transactions_count
 */
#[Fillable(['name', 'phone'])]
class Customer extends Model
{
    /**
     * @return Attribute<string, string|null>
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => PhoneNumber::normalize($value) ?? $value,
        );
    }

    public function getPhoneDisplayAttribute(): string
    {
        return PhoneNumber::formatForDisplay($this->phone);
    }

    public function getPhoneLocalAttribute(): string
    {
        return PhoneNumber::toLocalInput($this->phone);
    }
}
