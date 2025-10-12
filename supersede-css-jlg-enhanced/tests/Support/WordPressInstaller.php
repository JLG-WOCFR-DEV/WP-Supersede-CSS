<?php

declare(strict_types=1);

namespace SSC\Tests\Support;

use RuntimeException;
use ZipArchive;

final class WordPressInstaller
{
    private const WORDPRESS_DIR = '/wordpress';

    public static function ensure(): void
    {
        $wordpressPath = self::wordpressPath();

        if (file_exists($wordpressPath . '/wp-settings.php')) {
            return;
        }

        $version = self::resolveWordPressVersion();
        self::downloadAndExtract($version, $wordpressPath);
    }

    private static function wordpressPath(): string
    {
        return self::projectRoot() . self::WORDPRESS_DIR;
    }

    private static function resolveWordPressVersion(): string
    {
        $lockPath = self::projectRoot() . '/composer.lock';
        if (!is_file($lockPath)) {
            throw new RuntimeException('composer.lock file is required to resolve the WordPress version.');
        }

        $lockContents = file_get_contents($lockPath);
        if ($lockContents === false) {
            throw new RuntimeException('Unable to read composer.lock.');
        }

        $data = json_decode($lockContents, true);
        if (!is_array($data)) {
            throw new RuntimeException('Unable to parse composer.lock.');
        }

        $packages = $data['packages-dev'] ?? [];
        foreach ($packages as $package) {
            if (($package['name'] ?? null) === 'wp-phpunit/wp-phpunit') {
                $version = $package['version'] ?? '';
                if ($version === '') {
                    break;
                }

                return ltrim($version, 'v');
            }
        }

        throw new RuntimeException('Unable to determine the WordPress version from composer.lock.');
    }

    private static function downloadAndExtract(string $version, string $destination): void
    {
        $downloadUrl = sprintf('https://wordpress.org/wordpress-%s-no-content.zip', $version);
        $tempFile = self::createTempFile();

        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
            ],
        ]);

        $archive = file_get_contents($downloadUrl, false, $context);
        if ($archive === false) {
            throw new RuntimeException(sprintf('Unable to download WordPress from %s.', $downloadUrl));
        }

        if (file_put_contents($tempFile, $archive) === false) {
            throw new RuntimeException('Failed to write the downloaded WordPress archive to disk.');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The ZipArchive extension is required to extract the WordPress archive.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== true) {
            throw new RuntimeException('Failed to open the WordPress archive.');
        }

        $extractPath = self::createTempDir();
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('Failed to extract the WordPress archive.');
        }
        $zip->close();
        @unlink($tempFile);

        $extractedWordPressPath = $extractPath . '/wordpress';
        if (!is_dir($extractedWordPressPath)) {
            self::deletePath($extractPath);
            throw new RuntimeException('The WordPress archive did not contain the expected directory.');
        }

        if (is_dir($destination)) {
            self::deletePath($destination);
        }

        self::movePath($extractedWordPressPath, $destination);
        self::deletePath($extractPath);
    }

    private static function createTempFile(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'wp-');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create a temporary file for the WordPress archive.');
        }

        return $tempFile;
    }

    private static function createTempDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/wp-' . bin2hex(random_bytes(8));

        if (!mkdir($tempDir) && !is_dir($tempDir)) {
            throw new RuntimeException(sprintf('Unable to create temporary directory: %s', $tempDir));
        }

        return $tempDir;
    }

    private static function deletePath(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            self::deletePath($path . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($path);
    }

    private static function movePath(string $source, string $destination): void
    {
        $parent = dirname($destination);
        if (!is_dir($parent) && !mkdir($parent, recursive: true) && !is_dir($parent)) {
            throw new RuntimeException(sprintf('Unable to create directory for WordPress installation: %s', $parent));
        }

        if (!rename($source, $destination)) {
            self::deletePath($source);
            throw new RuntimeException('Failed to move the extracted WordPress directory into place.');
        }
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
