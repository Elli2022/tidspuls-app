<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\PersonnummerNormalizer;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'role',
        'name',
        'email',
        'password',
        'personnummer',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
    ];

    public function setPersonnummerAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['personnummer'] = null;

            return;
        }

        $canonical = PersonnummerNormalizer::canonical($value);
        $this->attributes['personnummer'] = $canonical ?? trim($value);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function locationLogs(): HasMany
    {
        return $this->hasMany(LocationLog::class);
    }

    /**
     * Resolve a user by personnummer for API login. Matches canonical dashed form, compact 10 digits,
     * optional century prefix (12-digit legacy rows), and falls back to comparing last 10 digits when
     * the stored string contains odd separators or spacing.
     */
    public static function findForAuthenticationByPersonnummer(string $normalizedPersonnummer): ?self
    {
        $trimmed = trim($normalizedPersonnummer);
        $digits = preg_replace('/\D+/', '', $trimmed);

        if ($digits === '' || strlen($digits) < 10) {
            return null;
        }

        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        $dashed = substr($digits, 0, 6).'-'.substr($digits, 6, 4);

        $variants = array_unique(array_filter([
            $trimmed,
            $dashed,
            $digits,
            '19'.$digits,
            '20'.$digits,
        ]));

        $matches = static::query()->whereIn('personnummer', $variants)->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            return null;
        }

        $found = null;

        foreach (static::query()->cursor() as $candidate) {
            $hay = preg_replace('/\D+/', '', $candidate->personnummer ?? '');
            if ($hay === '' || strlen($hay) < 10) {
                continue;
            }

            $hay10 = strlen($hay) > 10 ? substr($hay, -10) : $hay;

            if ($hay10 !== $digits) {
                continue;
            }

            if ($found !== null) {
                return null;
            }

            $found = $candidate;
        }

        return $found;
    }
}
