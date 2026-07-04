<?php

namespace App\Repositories;

use App\Models\PdfSummary;
use App\Models\User;

class PdfSummaryRepository
{
    public function updateOrCreate(
        User      $user,
        string    $filename,
        string    $summary,
        string    $summaryType,
        int|float $size): PdfSummary
    {
        return PdfSummary::query()->updateOrCreate([
            'user_id' => $user->id,
            'filename' => $filename,
            'summary_type' => $summaryType,
        ], [
            'summary' => $summary,
            'file_size' => $size,
        ]);
    }

    /**
     * @param string|int $userId
     * @param int $paginate integer for pagination
     * @return array<string,mixed>
     */
    public function findAllByUserId(string|int $userId, int $paginate = 10): array
    {
        return PdfSummary::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($paginate)
            ->toArray();
    }
}
