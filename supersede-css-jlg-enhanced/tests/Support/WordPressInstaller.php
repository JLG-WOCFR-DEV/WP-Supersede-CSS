<?php

declare(strict_types=1);

namespace SSC\Tests\Support;

use RuntimeException;
use ZipArchive;

final class WordPressInstaller
{
    private const WORDPRESS_DIR = '/wordpress';
    private const WORDPRESS_ARCHIVE_ENV = 'WP_TESTS_WORDPRESS_ZIP';

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
        $localArchive = self::resolveLocalArchivePath();
        if ($localArchive !== null) {
            self::extractArchive($localArchive, $destination);

            return;
        }

        $downloadUrl = sprintf('https://wordpress.org/wordpress-%s-no-content.zip', $version);
        $archive = self::fetchArchive($downloadUrl);

        $tempFile = self::createTempFile();

        try {
            if (file_put_contents($tempFile, $archive) === false) {
                throw new RuntimeException('Failed to write the downloaded WordPress archive to disk.');
            }

            self::extractArchive($tempFile, $destination);
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private static function fetchArchive(string $downloadUrl): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
            ],
        ]);

        $handler = static function (int $severity, string $message, string $file = '', int $line = 0) use ($downloadUrl): bool {
            unset($severity, $file, $line);

            throw new RuntimeException(sprintf('Unable to download WordPress from %s: %s', $downloadUrl, $message));
        };

        set_error_handler($handler);

        try {
            $archive = file_get_contents($downloadUrl, false, $context);
        } finally {
            restore_error_handler();
        }

        if ($archive === false) {
            throw new RuntimeException(sprintf('Unable to download WordPress from %s.', $downloadUrl));
        }

        return $archive;
    }

    private static function extractArchive(string $archivePath, string $destination): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The ZipArchive extension is required to extract the WordPress archive.');
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Failed to open the WordPress archive.');
        }

        $extractPath = self::createTempDir();

        try {
            if (!$zip->extractTo($extractPath)) {
                throw new RuntimeException('Failed to extract the WordPress archive.');
            }
        } finally {
            $zip->close();
        }

        try {
            $extractedWordPressPath = $extractPath . '/wordpress';
            if (!is_dir($extractedWordPressPath)) {
                throw new RuntimeException('The WordPress archive did not contain the expected directory.');
            }

            if (is_dir($destination)) {
                self::deletePath($destination);
            }

            self::movePath($extractedWordPressPath, $destination);
        } finally {
            self::deletePath($extractPath);
        }
    }

    private static function resolveLocalArchivePath(): ?string
    {
        $configuredPath = getenv(self::WORDPRESS_ARCHIVE_ENV);
        if ($configuredPath === false) {
            return null;
        }

        $configuredPath = trim($configuredPath);
        if ($configuredPath === '') {
            return null;
        }

        if (!self::isAbsolutePath($configuredPath)) {
            $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configuredPath), DIRECTORY_SEPARATOR);
            $path = self::projectRoot() . DIRECTORY_SEPARATOR . $relative;
        } else {
            $path = $configuredPath;
        }

        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved)) {
            throw new RuntimeException(sprintf(
                'Local WordPress archive defined in %s does not exist: %s',
                self::WORDPRESS_ARCHIVE_ENV,
                $path
            ));
        }

        if (!is_readable($resolved)) {
            throw new RuntimeException(sprintf(
                'Local WordPress archive defined in %s is not readable: %s',
                self::WORDPRESS_ARCHIVE_ENV,
                $resolved
            ));
        }

        return $resolved;
    }

    private static function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return true;
        }

        return str_starts_with($path, 'phar://');
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
