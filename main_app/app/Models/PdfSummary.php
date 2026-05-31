<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfSummary extends Model
{
    /** @use HasFactory<\Database\Factories\PdfSummary> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'summary',
        'filesize',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
