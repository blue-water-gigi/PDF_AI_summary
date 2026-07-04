<?php

namespace App\Services\Summarization;

use App\Contracts\AI\AiChatClientInterface;
use App\DTO\SummaryResult;
use App\Exceptions\Summarizer\PdfSummarizerException;
use App\Exceptions\Summarizer\UsageAvailabilityException;
use App\Models\User;
use App\Repositories\PdfSummaryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class PdfSummarizerService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private PdfStorageService       $storage,
        private PdfTextExtractorService $extractor,
        private PromptBuilderService    $promptBuilder,
        private AiChatClientInterface   $client,
        private PdfSummaryRepository    $repository,
    )
    {
    }


    /**
     * Summarize user input
     *
     * @return SummaryResult DTO
     * @throws PdfSummarizerException
     * @throws UsageAvailabilityException
     */
    public function summarize(User $user, UploadedFile $file, string $summaryType = 'standard'): SummaryResult
    {
        if (!$user->canSummarizePdf()) {
            throw new UsageAvailabilityException('User cant summarize pdf.');
        }

        $path = $this->storage->store($file);

        try {

            $text = trim($this->extractor->extractText($this->storage->absolutePath($path)));

            $messages = $this->promptBuilder->build($summaryType, $text);

            $summary = $this->client->sendAndReceive($messages);

            $pdfSummary = DB::transaction(function () use ($user, $file, $summary, $summaryType) {
                $pdfSummary = $this->repository->updateOrCreate(
                    user: $user,
                    filename: $file->hashName(),
                    summary: $summary,
                    summaryType: $summaryType,
                    size: $file->getSize()
                );

                $user->increasePdfCount();

                return $pdfSummary;
            });


            $this->storage->delete($this->storage->absolutePath($path));

            return new SummaryResult(
                $pdfSummary->id,
                $summary,
            );
        } catch (PdfSummarizerException $e) {
            $this->storage->delete($this->storage->absolutePath($path));

            throw $e;
        } catch (Throwable $th) {
            $this->storage->delete($this->storage->absolutePath($path));

            Log::error('Pdf summarizer error: ' . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString(),
            ]);

            throw new PdfSummarizerException(
                'Unexpected error: ' . $th->getMessage(),
                $th->getCode(),
                $th
            );
        }
    }

}
