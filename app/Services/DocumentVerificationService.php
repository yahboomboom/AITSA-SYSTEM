<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DocumentVerificationService
{
    public function verifyNameOnDocument(UploadedFile $file, string $fullName): bool
    {
        $text = $this->extractText($file);

        if ($text === null || trim($text) === '') {
            return true;
        }

        return $this->nameMatchesText($fullName, $text);
    }

    protected function extractText(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType();
        $path = $file->getRealPath();

        try {
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                return $this->ocrImage($path);
            }

            if ($mime === 'application/pdf') {
                $text = $this->extractPdfText($path);
                if ($text !== null && mb_strlen(trim($text)) >= 20) {
                    return $text;
                }
                return $this->ocrScannedPdf($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Document OCR/verification failed: ' . $e->getMessage());
            return null;
        }

        return null;
    }

    protected function ocrImage(string $path): ?string
    {
        if (!class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class)) {
            return null;
        }
        return (new \thiagoalessio\TesseractOCR\TesseractOCR($path))->run();
    }

    protected function extractPdfText(string $path): ?string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return null;
        }
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($path);
        return $pdf->getText();
    }

    protected function ocrScannedPdf(string $path): ?string
    {
        if (!class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class) || !class_exists(\Imagick::class)) {
            return null;
        }

        $imagick = new \Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($path . '[0]');
        $imagick->setImageFormat('png');

        $tmpImage = sys_get_temp_dir() . '/' . uniqid('doc_ocr_') . '.png';
        $imagick->writeImage($tmpImage);
        $imagick->clear();

        try {
            return (new \thiagoalessio\TesseractOCR\TesseractOCR($tmpImage))->run();
        } finally {
            @unlink($tmpImage);
        }
    }

    public function nameMatchesText(string $fullName, string $extractedText): bool
    {
        $tokens = $this->nameTokens($fullName);
        if (empty($tokens)) {
            return true;
        }

        $docText = ' ' . $this->normalize($extractedText) . ' ';

        $found = 0;
        foreach ($tokens as $token) {
            if (str_contains($docText, ' ' . $token . ' ')) {
                $found++;
            }
        }

        $required = count($tokens) === 1 ? 1 : max(2, (int) ceil(count($tokens) * 0.6));
        return $found >= $required;
    }

    protected function normalize(string $text): string
    {
        $text = mb_strtoupper($text);
        $text = preg_replace('/[^A-Z\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    protected function nameTokens(string $fullName): array
    {
        $ignore = ['JR', 'SR', 'II', 'III', 'IV'];
        $tokens = explode(' ', $this->normalize($fullName));
        return array_values(array_filter(
            $tokens,
            fn ($t) => mb_strlen($t) >= 2 && !in_array($t, $ignore, true)
        ));
    }
}