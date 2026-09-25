<?php

declare(strict_types=1);

namespace App\Service\Storage;

use InvalidArgumentException;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SecureFileNameGenerator
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    public function generate(string $originalFilename): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Unsupported attachment file type.');
        }

        $basename = pathinfo($originalFilename, PATHINFO_FILENAME) ?: 'attachment';
        $slug = (new AsciiSlugger())->slug($basename)->lower()->toString();

        return sprintf('%s-%s.%s', substr($slug, 0, 80), bin2hex(random_bytes(8)), $extension);
    }
}
