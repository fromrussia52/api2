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
use Hoa\File\Temporary\Temporary;
use Hoa\Protocol\Node;
use Hoa\Protocol\Protocol;
use Kitab\Compiler\Compiler;
use Kitab\Compiler\Target\Html\Html;
use Kitab\Compiler\Target\Html\Configuration;
use Kitab\Finder;
use RuntimeException;

/**
 * This `kitab` sub command compiles the documentation into HTML.
 */
class Compile extends Console\Dispatcher\Kit
{
    /**
     * Options description.
     */
    protected $options = [
        ['configuration-file', Console\GetOption::REQUIRED_ARGUMENT, 'c'],
        ['default-namespace',  Console\GetOption::REQUIRED_ARGUMENT, 'd'],
        ['with-composer',      Console\GetOption::OPTIONAL_ARGUMENT, 'a'],
        ['with-logo-url',      Console\GetOption::REQUIRED_ARGUMENT, 'l'],
        ['with-project-name',  Console\GetOption::REQUIRED_ARGUMENT, 'p'],
        ['output-directory',   Console\GetOption::REQUIRED_ARGUMENT, 'o'],
        ['open',               Console\GetOption::NO_ARGUMENT,       'r'],
        ['help',               Console\GetOption::NO_ARGUMENT,       'h'],
        ['help',               Console\GetOption::NO_ARGUMENT,       '?']
    ];



    /**
     * The entry method.
     */
    public function run(): int
    {
        $configuration   = new Configuration();
        $outputDirectory = Temporary::getTemporaryDirectory() . DS . 'Kitab.html.output';
        $directoryToScan = null;
        $open            = false;

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

                    if (!($_configuration instanceof Configuration)) {
                        throw new RuntimeException(
                            'The configuration file ' . $v . ' exists, but it returns ' .
                            'a value that is _not_ an object of kind ' . Configuration::class . '.'
                        );
                    }

                    $configuration = $_configuration;

                    break;

                case 'd':
                    $configuration->defaultNamespace = $v;

                    break;

                case 'a':
                    if (false === is_string($v)) {
                        $configuration->composerFile = './composer.json';
                    } else {
                        $configuration->composerFile = $v;
                    }

                    break;

                case 'l':
                    $configuration->logoURL = $v;

                    break;

                case 'p':
                    $configuration->projectName = $v;

                    break;

                case 'o':
                    $outputDirectory = $v;

                    break;

                case 'r':
                    $open = $v;

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

        if (null !== $configuration->composerFile) {
            if (false === file_exists($configuration->composerFile)) {
                throw new RuntimeException('Composer file `' . $configuration->composerFile . '` is not found.');
            }

            $composerFileContent = json_decode(file_get_contents($configuration->composerFile), true);

            if (isset($composerFileContent['autoload']) &&
                isset($composerFileContent['autoload']['psr-4'])) {
                $protocolInput     = Protocol::getInstance()['Kitab']['Input'];
                $composerDirectory = dirname($configuration->composerFile);

                foreach ($composerFileContent['autoload']['psr-4'] as $psrNamespaces => $psrDirectory) {
                    $latestNode = $protocolInput;

                    foreach (explode('\\', trim($psrNamespaces, '\\')) as $psrNamespace) {
                        if (!isset($latestNode[$psrNamespace])) {
                            $latestNode[$psrNamespace] = new Node($psrNamespace);
                        }

                        $latestNode = $latestNode[$psrNamespace];
                    }

                    $latestNode->setReach("\r" . $composerDirectory . DS . $psrDirectory . DS);
                }
            }
        }

        Protocol::getInstance()['Kitab']['Output']->setReach("\r" . $outputDirectory . DS);

        $this->parser->listInputs($directoryToScan);

        if (empty($directoryToScan)) {
            throw new RuntimeException(
                'Directory to scan must not be empty.' . "\n" .
                'Retry with ' . '`' . implode(' ', $_SERVER['argv']) . ' src` ' .
                'to compile the documentation inside the `src` directory.'
            );
        }

        if (false === is_dir($directoryToScan)) {
            throw new RuntimeException(
                'Directory to scan `' . $directoryToScan . '` does not exist.'
            );
        }

        $finder = new Finder();
        $finder->in($directoryToScan);

        $target = new Html(null, $configuration);

        $compiler = new Compiler();
        $compiler->compile($finder, $target);

        $index = $outputDirectory;

        if (true === file_exists($index . DS . 'index.html')) {
            $index .= DS . 'index.html';
        }

        if (true === $open) {
            if (isset($_SERVER['BROWSER'])) {
                echo
                    'Opening…', "\n",
                    Console\Processus::execute($_SERVER['BROWSER'] . ' ' . escapeshellarg($index), false);

                return 0;
            }

            $utilities = [
                'xdg-open',
                'gnome-open',
                'kde-open',
                'open'
            ];

            foreach ($utilities as $utility) {
                if (null !== $utilityPath = Console\Processus::locate($utility)) {
                    echo
                        'Opening…', "\n",
                        Console\Processus::execute($utilityPath . ' ' . escapeshellarg($index), false);

                    return 0;
                }
            }

            echo 'Did not succeed to open the documentation automatically (', $index, ').', "\n";
        } else {
            echo $index, "\n";
        }

        return 0;
    }

    /**
     * Print help.
     */
    public function usage()
    {
        echo
            'Usage   : compile <options> directory-to-scan', "\n",
            'Options :', "\n",
            $this->makeUsageOptionsList([
                'c'    => 'Path to a PHP file returning a `' . Configuration::class . '` ' .
                          'instance to be the default configuration. All the other options ' .
                          'in this command-line will overwrite the items in the configuration. ' .
                          'If used, it must be the first option in the command-line.',
                'd'    => 'Default namespace that must be displayed.',
                'a'    => 'Use a specific Composer file to get PSR-4 mappings ' .
                          '(default: `./composer.json` if enabled).',
                'l'    => 'URL of the logo.',
                'p'    => 'Project name.',
                'o'    => 'Directory that will receive the generated documentation.',
                'r'    => 'Open the documentation in a browser after its computation.',
                'help' => 'This help.'
            ]);
    }
}
