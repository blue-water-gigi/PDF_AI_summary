<?php

namespace App\Services\Summarization;

use App\Exceptions\Summarizer\PdfExtractorException;
use Exception;
use Smalot\PdfParser\Parser;

readonly class PdfTextExtractorService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private Parser $parser)
    {
    }

    /**
     *
     * @throws PdfExtractorException
     * @throws Exception
     */

    public function extractText(string $path, ?int $limit = null): string
    {
        $text = $this->parser->parseFile($path)->getText($limit);

        return $text === ''
            ? throw new PdfExtractorException('Unable to extract text from PDF file. File is most probably empty', [
                'plain_text' => $text,
            ])
            : $text;
    }
}
