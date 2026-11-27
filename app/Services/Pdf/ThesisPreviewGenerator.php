<?php

namespace App\Services\Pdf;

use setasign\Fpdi\Fpdi;

class ThesisPreviewGenerator
{
    public function generate(string $sourcePath, int $requestedPageCount): string
    {
        $source = new Fpdi;
        $pageCount = $source->setSourceFile($sourcePath);
        abort_if($pageCount < 1, 422, 'Le document ne contient aucune page.');

        $source->SetMargins(0, 0, 0);
        $source->SetAutoPageBreak(false);

        for ($pageNumber = 1; $pageNumber <= min($requestedPageCount, $pageCount); $pageNumber++) {
            $page = $source->importPage($pageNumber);
            $size = $source->getTemplateSize($page);
            $source->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $source->useTemplate($page);
        }

        return $source->Output('S');
    }
}
