<?php

declare(strict_types=1);

namespace SSC\Tests\Support;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class WordPressInstallerTest extends TestCase
{
    private string $projectRoot;
    private string $wordpressPath;
    private bool $zipEnvWasSet = false;
    private ?string $previousZipEnv = null;
    /**
     * @var list<string>
     */
    private array $createdArchives = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 2);
        $this->wordpressPath = $this->projectRoot . '/wordpress';

        $existing = getenv('WP_TESTS_WORDPRESS_ZIP');
        if ($existing !== false) {
            $this->zipEnvWasSet = true;
            $this->previousZipEnv = $existing;
        }

        $this->removePath($this->wordpressPath);
    }

    protected function tearDown(): void
    {
        $this->removePath($this->wordpressPath);

        foreach ($this->createdArchives as $archive) {
            if (is_file($archive)) {
                @unlink($archive);
            }
        }

        $fixturesDir = $this->projectRoot . '/tests/tmp';
        if (is_dir($fixturesDir)) {
            @rmdir($fixturesDir);
        }

        if ($this->zipEnvWasSet) {
            putenv('WP_TESTS_WORDPRESS_ZIP=' . $this->previousZipEnv);
        } else {
            putenv('WP_TESTS_WORDPRESS_ZIP');
        }

        parent::tearDown();
    }

    public function testUsesLocalArchiveWhenProvided(): void
    {
        $archivePath = $this->createWordPressArchive();
        $relativeArchive = $this->makeRelativeToProjectRoot($archivePath);

        putenv('WP_TESTS_WORDPRESS_ZIP=' . $relativeArchive);

        WordPressInstaller::ensure();

        self::assertFileExists($this->wordpressPath . '/wp-settings.php');
    }

    public function testThrowsWhenLocalArchiveIsMissing(): void
    {
        putenv('WP_TESTS_WORDPRESS_ZIP=tests/tmp/does-not-exist.zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local WordPress archive defined in WP_TESTS_WORDPRESS_ZIP does not exist');

        WordPressInstaller::ensure();
    }

    private function createWordPressArchive(): string
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZipArchive extension is required for this test.');
        }

        $fixturesDir = $this->projectRoot . '/tests/tmp';
        if (!is_dir($fixturesDir) && !mkdir($fixturesDir, 0777, true) && !is_dir($fixturesDir)) {
            self::fail('Unable to create fixtures directory.');
        }

        $archivePath = $fixturesDir . '/wordpress-fixture-' . bin2hex(random_bytes(4)) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            self::fail('Unable to create the WordPress archive fixture.');
        }

        $zip->addEmptyDir('wordpress');
        $zip->addFromString('wordpress/wp-settings.php', "<?php\n");
        $zip->close();

        $this->createdArchives[] = $archivePath;

        return $archivePath;
    }

    private function makeRelativeToProjectRoot(string $path): string
    {
        $prefix = $this->projectRoot . DIRECTORY_SEPARATOR;
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }

        return $path;
    }

    private function removePath(string $path): void
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

            $this->removePath($path . DIRECTORY_SEPARATOR . $item);
        }

        @rmdir($path);
    }
}

