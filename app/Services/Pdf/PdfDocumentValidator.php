<?php

namespace App\Services\Pdf;

use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\FpdiException;

class PdfDocumentValidator
{
    public function validate(string $path, string $field): int
    {
        try {
            $pageCount = (new Fpdi)->setSourceFile($path);
        } catch (FpdiException) {
            throw ValidationException::withMessages([
                $field => 'Le PDF est illisible, incomplet ou chiffré.',
            ]);
        }

        if ($pageCount < 1) {
            throw ValidationException::withMessages([
                $field => 'Le PDF ne contient aucune page lisible.',
            ]);
        }

        return $pageCount;
    }
}
