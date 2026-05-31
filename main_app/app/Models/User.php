<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'stripe_customer_id',
        'stripe_sub_id',
        'stripe_sub_ends_at',
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
            'stripe_sub_ends_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (! $user->plan_id) {
                $basicPlan = once(fn () => Plan::where('slug', 'basic')->first());
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

    public function canSummarizePdf(): bool
    {
        if (! $this->plan_id) {
            return false;
        }

        if ($this->pdf_count_resets_at->isPast()) {
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

        return $this->pdf_count <= $this->plan->pdf_limit;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasActiveSub(): bool
    {
        if (! $this->stripe_sub_id) {
            return false;
        }

        return ! ($this->stripe_sub_ends_at && $this->stripe_sub_ends_at->isPast());
    }

    public function canChangePlan(): bool
    {
        return $this->hasActiveSub();
    }
}
