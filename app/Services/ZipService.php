<?php

namespace App\Services;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\Log;

class ZipService
{
    public function zipFolder(string $sourceFolder, string $zipFilePath): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error("ZIPファイルを作成できません: {$zipFilePath}");
            return false;
        }

        $sourceFolder = realpath($sourceFolder);
        if (!is_dir($sourceFolder)) {
            Log::error("対象フォルダが存在しません: {$sourceFolder}");
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceFolder) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
        return true;
    }
}
