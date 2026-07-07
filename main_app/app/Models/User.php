<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\DTO\SubscriptionStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Override;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plan_id',
        'pdf_count',
        'pdf_count_resets_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'pdf_count_resets_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (!$user->plan_id) {
                $basicPlan = once(fn() => Plan::where('slug', 'basic')->first());
                if ($basicPlan) {
                    $user->plan_id = $basicPlan->id;
                    $user->pdf_count = 0;
                    $user->pdf_count_resets_at = now()->addMonth();
                }
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pdfSummaries(): HasMany
    {
        return $this->hasMany(PdfSummary::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function canSummarizePdf(): bool
    {
        if (!$this->plan_id) {
            return false;
        }

        if (!$this->pdf_count_resets_at || $this->pdf_count_resets_at->isPast()) {
            $this->update([
                'pdf_count' => 0,
                'pdf_count_resets_at' => now()->addMonth(),
            ]);

            $this->refresh();
        }

        // unlimited usage = negative value on pdf_limit
        if ($this->plan->pdf_limit < 0) {
            return true;
        }

        return $this->pdf_count < $this->plan->pdf_limit;
    }

    public function isLimitReached(): bool
    {
        return $this->pdf_count === $this->plan->pdf_limit;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function increasePdfCount(int|float $amount = 1): void
    {
        $this->increment('pdf_count', $amount);
    }

    /**
     * Check if user have active subscription
     */
    public function hasActiveSub(): bool
    {
        return $this->subscription?->status === SubscriptionStatus::ACTIVE
            && $this->subscription?->current_period_end?->isFuture() === true;
    }
}


