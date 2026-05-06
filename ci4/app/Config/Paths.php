<?php

namespace Config;

/**
 * Paths Configuration
 *
 * Lokasi folder utama relatif ke front controller (public/index.php).
 * Hanya ubah jika kamu memindahkan folder system / app / writable / tests.
 */
class Paths
{
    /**
     * @var string Path ke folder system (CodeIgniter framework di vendor).
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * @var string Path ke folder app/.
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * @var string Path ke folder writable (cache, logs, session, dll).
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * @var string Path ke folder tests.
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * @var string Path ke folder view (default app/Views/).
     */
    public string $viewDirectory = __DIR__ . '/../Views';
}
