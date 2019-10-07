<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Kitab\Bin;

use Hoa\Console;
use Hoa\Console\Processus;
use Hoa\Event;
use Hoa\File;
use Hoa\File\Temporary\Temporary;
use Hoa\Protocol\Protocol;
use Kitab\Compiler\Compiler;
use Kitab\Compiler\Target\DocTest;
use Kitab\Finder;
use RuntimeException;

/**
 * This `kitab` sub command compiles and runs the doctests.
 */
class Test extends Console\Dispatcher\Kit
{
    /**
     * Options description.
     */
    protected $options = [
        ['configuration-file',       Console\GetOption::REQUIRED_ARGUMENT, 'c'],
        ['autoloader',               Console\GetOption::REQUIRED_ARGUMENT, 'l'],
        ['output-directory',         Console\GetOption::REQUIRED_ARGUMENT, 'o'],
        ['concurrent-processes',     Console\GetOption::REQUIRED_ARGUMENT, 'p'],
        ['bypass-cache',             Console\GetOption::NO_ARGUMENT,       'C'],
        ['atoum-configuration-file', Console\GetOption::REQUIRED_ARGUMENT, 'a'],
        ['verbose',                  Console\GetOption::NO_ARGUMENT,       'v'],
        ['help',                     Console\GetOption::NO_ARGUMENT,       'h'],
        ['help',                     Console\GetOption::NO_ARGUMENT,       '?']
    ];



    /**
     * The entry method.
     */
    public function run(): int
    {
        $configuration   = new DocTest\Configuration();
        $outputDirectory = null;
        $directoryToScan = null;
        $verbose         = false;

        while (false !== $c = $this->getOption($v)) {
            switch ($c) {
                case 'c':
                    if (false === file_exists($v)) {
                        throw new RuntimeException(
                            'Tried to load the configuration file ' . $v . ', but the file does not exist.'
                        );
                    }

                    $_configuration = (function () use ($v) {
                        return require $v;
                    })();

                    if (!($_configuration instanceof DocTest\Configuration)) {
                        throw new RuntimeException(
                            'The configuration file ' . $v . ' exists, but it returns ' .
                            'a value that is _not_ an object of kind ' . DocTest\Configuration::class . '.'
                        );
                    }

                    $configuration = $_configuration;

                    break;

                case 'o':
                    $outputDirectory = $v;

                    break;

                case 'l':
                    if (false === file_exists($v)) {
                        throw new RuntimeException('Autoloader file `' . $v . '` does not exist.');
                    }

                    $configuration->autoloaderFile = $v;

                    break;

                case 'p':
                    $configuration->concurrentProcesses = max(1, intval($v));

                    break;

                case 'C':
                    $configuration->bypassCache = $v;

                    break;

                case 'a':
                    if (false === file_exists($v)) {
                        throw new RuntimeException('Extra atoum configuration file `' . $v . '` does not exist.');
                    }

                    $configuration->atoumConfigurationFile = $v;

                    break;

                case 'v':
                    $verbose = $v;

                    break;

                case 'h':
                case '?':
                    $this->usage();

                    return 0;

                case '__ambiguous':
                    $this->resolveOptionAmbiguity($v);

                    break;
            }
        }


        if (empty($configuration->autoloaderFile) && true === file_exists('vendor' . DS . 'autoload.php')) {
            $autoloaderFile = realpath('vendor' . DS . 'autoload.php');

            // Use the existing `vendor/autoload.php` file if it is not the
            // Kitab's one embedded in the PHAR to avoid double inclusion.
            if (!(defined('KITAB_PHAR_NAME') &&
                  file_get_contents($autoloaderFile) === file_get_contents(dirname(__DIR__, 2) . DS . 'vendor' . DS . 'autoload.php'))) {
                $configuration->autoloaderFile = $autoloaderFile;
            }
        }

        $this->parser->listInputs($directoryToScan);

        if (empty($directoryToScan)) {
            throw new RuntimeException(
                'Directory to scan must not be empty.' . "\n" .
                'Retry with `' . implode(' ', $_SERVER['argv']) . ' src` ' .
                'to test the documentation inside the `src` directory.'
            );
        }

        if (false === is_dir($directoryToScan)) {
            throw new RuntimeException(
                'Directory to scan `' . $directoryToScan . '` does not exist.'
            );
        }

        if (null === $outputDirectory) {
            $outputDirectory = Temporary::getTemporaryDirectory() . DS . 'Kitab.test.output' . DS . hash('sha256', realpath($directoryToScan)). DS;
        }

        Protocol::getInstance()['Kitab']['Output']->setReach("\r" . $outputDirectory . DS);

        if (true === $verbose) {
            echo
                'Directory to scan: ', $directoryToScan, "\n",
                'Output directory : ', $outputDirectory, "\n";
        }

        $finder = new Finder();
        $finder
            ->in($directoryToScan)
            ->notIn('/^vendor$/');

        if (false === is_dir($outputDirectory)) {
            File\Directory::create($outputDirectory);
        } elseif (false === $configuration->bypassCache) {
            $since = time() - filemtime($outputDirectory);
            $finder->modified('since ' . $since . ' seconds');
        }

        $target = new DocTest\DocTest();

        foreach ($configuration->codeBlockHandlerNames as $codeBlockHandlerName) {
            $target->addCodeBlockHandler(new $codeBlockHandlerName);
        }

        $compiler = new Compiler();
        $compiler->compile($finder, $target);
        $command = $_SERVER['argv'][0] . ' atoum';

        if (defined('KITAB_PHAR_NAME')) {
            $temporaryAutoloaderPath = $outputDirectory . '.kitab.phar.autoloader.php';
            touch($temporaryAutoloaderPath);

            $temporaryAutoloader = new File\Write($temporaryAutoloaderPath, File::MODE_TRUNCATE_WRITE);
            $temporaryAutoloader->writeAll(
                '<?php' . "\n\n" .
                'Phar::loadPhar(\'' . KITAB_PHAR_PATH . '\', \'' . KITAB_PHAR_NAME . '\');' . "\n\n" .
                'require_once \'phar://'. KITAB_PHAR_NAME .'/vendor/autoload.php\';' . "\n" .
                (!empty($configuration->autoloaderFile) ? 'require_once \'' . str_replace("'", "\\'", realpath($configuration->autoloaderFile)) . '\';' : '')
            );

            $configuration->autoloaderFile = $temporaryAutoloader->getStreamName();
        } else {
            $composerAutoloader = realpath(dirname(__DIR__, 4) . DS . 'autoload.php');

            if (false === $composerAutoloader) {
                $composerAutoloader = realpath(dirname(__DIR__, 2) . DS . 'vendor' . DS . 'autoload.php');
            }

            $temporaryAutoloaderPath = $outputDirectory . '.kitab.autoloader.php';
            touch($temporaryAutoloaderPath);

            $temporaryAutoloader = new File\Write($temporaryAutoloaderPath, File\File::MODE_TRUNCATE_WRITE);
            $temporaryAutoloader->writeAll(
                '<?php' . "\n\n" .
                'require_once \'' . str_replace("'", "\\'", $composerAutoloader) . '\';' . "\n" .
                (!empty($configuration->autoloaderFile) ? 'require_once \'' . str_replace("'", "\\'", realpath($configuration->autoloaderFile)) . '\';' : '')
            );

            $configuration->autoloaderFile = $temporaryAutoloader->getStreamName();
        }

        if (true === $verbose) {
            $command .= ' ++verbose';
        }

        $command .=
            ' --autoloader-file ' .
                escapeshellarg($configuration->autoloaderFile) .
            ' --force-terminal' .
            ' --no-code-coverage' .
            ' --max-children-number ' .
                $configuration->concurrentProcesses .
            ' --directories ' .
                escapeshellarg($outputDirectory);

        if (!empty($configuration->atoumConfigurationFile)) {
            $command .=
                ' --configurations ' .
                    escapeshellarg($configuration->atoumConfigurationFile);
        }

        $processus = new Processus($command, null, null, getcwd(), $_SERVER);
        $processus->on(
            'input',
            function (Event\Bucket $bucket) {
                return false;
            }
        );
        $processus->on(
            'output',
            function (Event\Bucket $bucket) {
                echo $bucket->getData()['line'], "\n";

                return;
            }
        );
        $processus->on(
            'stop',
            function (Event\Bucket $bucket) {
                // Wait on sub-processes to stop.
                sleep(1);
                exit($bucket->getSource()->getExitCode());
            }
        );
        $processus->run();

        return 0;
    }

    /**
     * Print help.
     */
    public function usage()
    {
        echo
            'Usage   : test <options> directory-to-scan', "\n",
            'Options :', "\n",
            $this->makeUsageOptionsList([
                'c'    => 'Path to a PHP file returning a `' . DocTest\Configuration::class . '` ' .
                          'instance to be the default configuration. All the other options ' .
                          'in this command-line will overwrite the items in the configuration. ' .
                          'If used, it must be the first option in the command-line.',
                'l'    => 'Path to the autoloader file.',
                'o'    => 'Directory that will receive the generated documentation test suites.',
                'p'    => 'Maximum concurrent processes that can run.',
                'C'    => 'Bypass the cache; compile test suites like it is for the first time.',
                'a'    => 'atoum is used to execute the generated tests. This option adds an ' .
                          'extra atoum configuration file after the one embedded inside Kitab.',
                'v'    => 'Be verbose (add some debug information).',
                'help' => 'This help.'
            ]);
    }
}
