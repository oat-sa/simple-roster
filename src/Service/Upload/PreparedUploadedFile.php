<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class PreparedUploadedFile
{
    public function __construct(
        private UploadedFile $file,
        private bool $isTemporary = false
    ) {
    }

    public function file(): UploadedFile
    {
        return $this->file;
    }

    public function cleanup(): void
    {
        if ($this->isTemporary) {
            @unlink($this->file->getPathname());
        }
    }
}
