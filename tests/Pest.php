<?php

use Tests\Helpers\DevServer;
use Symfony\Component\Console\Output\ConsoleOutput;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
$dev        =   new DevServer();
$console    =   new ConsoleOutput();

\define('SERVER_TEMP_DIRECTORY', __DIR__ . DIRECTORY_SEPARATOR . 'Server' . DIRECTORY_SEPARATOR . 'temp');

// Start Dev Server
$dev->startServer(vars: ['SERVER_TEMP_DIRECTORY' => SERVER_TEMP_DIRECTORY]);
$console->writeln("<info>Development Server Started!</info>");
$console->writeln("<comment>Listening On: {$dev->getBaseUrl()}</comment>");

// Define 
\define('SERVER_BASE_URL', $dev->getBaseUrl());


// Create temp directory for testing
if (!\is_dir(SERVER_TEMP_DIRECTORY)) {
    $result = \mkdir(SERVER_TEMP_DIRECTORY, 0755, true);

    if ($result === false) {
        throw new \RuntimeException("[Developer][Exception]: Possibly An Access Permission Issue. Unable to Create/Access The Folder: [" . SERVER_TEMP_DIRECTORY . "]");
    }
} else {
    \chmod(SERVER_TEMP_DIRECTORY, 0755);
}

register_shutdown_function(function () use ($dev, $console) {
    $dev->stopServer();
    $console->writeln("<info>Development Server Closed!</info>");

    $directoryIterator  =   new \RecursiveDirectoryIterator(SERVER_TEMP_DIRECTORY, FilesystemIterator::SKIP_DOTS);
    $iterator           =   new \RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            \rmdir($item->getPathname());
        } else {
            $result = \unlink($item->getPathname());

            if (!$result) {
                $path = $item->getPathname();
                $error = error_get_last();
                $a = 1;
            }
        }
    }

    $result = \rmdir(SERVER_TEMP_DIRECTORY);

    if (!$result) {
        throw new Exception('1234!');
    }
});
