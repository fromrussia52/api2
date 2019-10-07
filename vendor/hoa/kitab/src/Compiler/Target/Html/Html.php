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

namespace Kitab\Compiler\Target\Html;

use Hoa\Console\Processus;
use Hoa\File\Directory;
use Hoa\File\Write;
use Hoa\Protocol\Protocol;
use Hoa\Stream\IStream\Touchable;
use Kitab\Compiler\IntermediateRepresentation;
use Kitab\Compiler\Target\Target;
use Kitab\Exception;
use StdClass;

class Html implements Target
{
    protected $_router        = null;
    protected $_configuration = null;

    public function __construct(Router $router = null, Configuration $configuration = null)
    {
        if (null === $router) {
            $this->_router = new Router();
        }

        if (null === $configuration) {
            $configuration = new Configuration();
        }

        $this->_configuration = $configuration;

        if (null === $this->_configuration->viewSourceLinkFormatter) {
            $this->_configuration->viewSourceLinkFormatter = function (IntermediateRepresentation\Entity $entity): string {
                return
                    '.' .
                    $this->_router->unroute(
                        'entity',
                        [
                            'namespaceName' => mb_strtolower(str_replace('\\', '/', $entity->getNamespaceName())),
                            'shortName'     => $entity->getShortName() . '.source'
                        ]
                    );
            };
        }

        return;
    }

    public function compile(IntermediateRepresentation\File $file)
    {
        foreach ($file as $representation) {
            if ($representation instanceof IntermediateRepresentation\Class_) {
                $this->compileClass($representation);
            } elseif ($representation instanceof IntermediateRepresentation\Interface_) {
                $this->compileInterface($representation);
            } elseif ($representation instanceof IntermediateRepresentation\Trait_) {
                $this->compileTrait($representation);
            } elseif ($representation instanceof IntermediateRepresentation\Function_) {
                $this->compileFunction($representation);
            } else {
                throw new Exception\TargetUnknownIntermediateRepresentation(
                    'Intermediate representation `%s` has not been handled.',
                    0,
                    get_class($representation)
                );
            }
        }
    }

    protected function compileClass(Intermediaterepresentation\Class_ $class)
    {
        $data        = new StdClass();
        $data->class = $class;

        return $this->compileEntity(
            $class,
            __DIR__ . DS . 'Template' . DS . 'Partial' . DS . 'Class.html',
            $data
        );
    }

    protected function compileInterface(Intermediaterepresentation\Interface_ $interface)
    {
        $data            = new StdClass();
        $data->interface = $interface;

        return $this->compileEntity(
            $interface,
            __DIR__ . DS . 'Template' . DS . 'Partial' . DS . 'Interface.html',
            $data
        );
    }

    protected function compileTrait(Intermediaterepresentation\Trait_ $trait)
    {
        $data        = new StdClass();
        $data->trait = $trait;

        return $this->compileEntity(
            $trait,
            __DIR__ . DS . 'Template' . DS . 'Partial' . DS . 'Trait.html',
            $data
        );
    }

    protected function compileFunction(Intermediaterepresentation\Function_ $function)
    {
        $data           = new StdClass();
        $data->function = $function;

        return $this->compileEntity(
            $function,
            __DIR__ . DS . 'Template' . DS . 'Partial' . DS . 'Function.html',
            $data
        );
    }

    protected function compileEntity(Intermediaterepresentation\Entity $entity, string $templateFile, StdClass $data)
    {
        $namespaceName = mb_strtolower(str_replace('\\', '/', $entity->getNamespaceName()));
        $shortName     = $entity->getShortName();

        $url = $this->_router->unroute(
            'entity',
            [
                'namespaceName' => $namespaceName,
                'shortName'     => $shortName
            ]
        );
        $output = 'hoa://Kitab/Output/' . $url;

        $urlSource = $this->_router->unroute(
            'entity',
            [
                'namespaceName' => $namespaceName,
                'shortName'     => $shortName . '.source'
            ]
        );
        $outputSource = 'hoa://Kitab/Output/' . $urlSource;

        $data->url             = new StdClass();
        $data->url->viewSource = ($this->_configuration->viewSourceLinkFormatter)($entity);

        Directory::create(dirname($output));

        $view = new Templater(
            $templateFile,
            new Write($output, Write::MODE_TRUNCATE_WRITE),
            $this->_router,
            $data
        );
        $view->render();

        $dataSource = new StdClass();

        $dataSource->configuration = $this->_configuration;

        $dataSource->layout        = new StdClass();
        $dataSource->layout->base  = './' . str_repeat('../', substr_count($namespaceName, '/') + 1);
        $dataSource->layout->title = sprintf(
            'Source of %s from %s',
            $entity->name,
            $this->_configuration->projectName
        );

        $dataSource->layout->import       = new StdClass();
        $dataSource->layout->import->file = __DIR__ . DS . 'Template' . DS . 'Source.html';

        $dataSource->layout->import->data                = new StdClass();
        $dataSource->layout->import->data->configuration = $this->_configuration;
        $dataSource->layout->import->data->fileContent   = file_get_contents($entity->file->name);

        $viewSource = new Templater(
            __DIR__ . DS . 'Template' . DS . 'Layout.html',
            new Write($outputSource, Write::MODE_TRUNCATE_WRITE),
            $this->_router,
            $dataSource
        );
        $viewSource->render();

        Search::insert([
            'id'             => null,
            'name'           => $entity->name,
            'normalizedName' => str_replace('\\', ' ', $entity->name),
            'description'    => (string) $entity->documentation,
            'url'            => '.' . $url
        ]);

        return;
    }

    public function assemble(array $symbols)
    {
        $this->assembleNamespaces($symbols);
        $this->assembleEntities($symbols);
        $this->assembleResources();

        return;
    }

    protected function assembleNamespaces(array $symbols, string $accumulator = '')
    {
        $siblingNamespaces = [];

        foreach ($symbols as $symbolPrefix => $subSymbols) {
            if ('@' !== $symbolPrefix[0]) {
                $siblingNamespace       = new StdClass();
                $siblingNamespace->name = $symbolPrefix;
                $siblingNamespace->url  =
                    '.' .
                    $this->_router->unroute(
                        'namespace',
                        [
                            'namespaceName' => mb_strtolower($accumulator . $symbolPrefix)
                        ]
                    );

                $siblingNamespaces[] = $siblingNamespace;
            }
        }

        foreach ($symbols as $symbolPrefix => $subSymbols) {
            if ('@' !== $symbolPrefix[0]) {
                $nextAccumulator = $accumulator . $symbolPrefix . '\\';
                $namespaceName   = mb_strtolower(str_replace('\\', '/', $nextAccumulator));

                $output =
                    'hoa://Kitab/Output/' .
                    $this->_router->unroute(
                        'namespace',
                        [
                            'namespaceName' => $namespaceName
                        ]
                    );

                $data = new StdClass();

                $data->configuration = $this->_configuration;

                $data->namespace       = new StdClass();
                $data->namespace->name = rtrim($nextAccumulator, '\\');

                $data->namespace->description = null;
                $data->namespace->namespaces  = [];
                $data->namespace->classes     = [];
                $data->namespace->interfaces  = [];
                $data->namespace->traits      = [];
                $data->namespace->functions   = [];

                $namespaceDirectory       = 'hoa://Kitab/Input/' . str_replace('\\', '/', $nextAccumulator);
                $namespaceDescriptionFile = $namespaceDirectory . DS . 'README.md';

                if (true === file_exists($namespaceDescriptionFile)) {
                    $data->namespace->description = file_get_contents($namespaceDescriptionFile);
                }

                foreach ($subSymbols as $subSymbolPrefix => $subSymbol) {
                    if ('@' !== $subSymbolPrefix[0]) {
                        $_namespace       = new StdClass();
                        $_namespace->name = $subSymbolPrefix;
                        $_namespace->url  =
                            '.' .
                            $this->_router->unroute(
                                'namespace',
                                [
                                    'namespaceName' => $namespaceName . mb_strtolower($subSymbolPrefix)
                                ]
                            );

                        $data->namespace->namespaces[] = $_namespace;

                        continue;
                    }

                    list($subSymbolType, $subSymbolName) = explode(':', $subSymbolPrefix);

                    switch ($subSymbolType) {
                        case '@class':
                            $_class              = new StdClass();
                            $_class->name        = $subSymbolName;
                            $_class->description = $subSymbol->description;
                            $_class->url         =
                                '.' .
                                $this->_router->unroute(
                                    'entity',
                                    [
                                        'namespaceName' => rtrim($namespaceName, '/'),
                                        'shortName'     => $subSymbolName
                                    ]
                                );

                            $data->namespace->classes[] = $_class;

                            break;

                        case '@interface':
                            $_interface              = new StdClass();
                            $_interface->name        = $subSymbolName;
                            $_interface->description = $subSymbol->description;
                            $_interface->url         =
                                '.' .
                                $this->_router->unroute(
                                    'entity',
                                    [
                                        'namespaceName' => rtrim($namespaceName, '/'),
                                        'shortName'     => $subSymbolName
                                    ]
                                );

                            $data->namespace->interfaces[] = $_interface;

                            break;

                        case '@trait':
                            $_trait              = new StdClass();
                            $_trait->name        = $subSymbolName;
                            $_trait->description = $subSymbol->description;
                            $_trait->url         =
                                '.' .
                                $this->_router->unroute(
                                    'entity',
                                    [
                                        'namespaceName' => rtrim($namespaceName, '/'),
                                        'shortName'     => $subSymbolName
                                    ]
                                );

                            $data->namespace->traits[] = $_trait;

                            break;

                        case '@function':
                            $_function              = new StdClass();
                            $_function->name        = $subSymbolName;
                            $_function->description = $subSymbol->description;
                            $_function->url         =
                                '.' .
                                $this->_router->unroute(
                                    'entity',
                                    [
                                        'namespaceName' => rtrim($namespaceName, '/'),
                                        'shortName'     => $subSymbolName
                                    ]
                                );

                            $data->namespace->functions[] = $_function;

                            break;
                    }
                }

                $data->layout         = new StdClass();
                $data->layout->base   = './' . str_repeat('../', substr_count($nextAccumulator, '\\'));
                $data->layout->title  = sprintf(
                    'Index of %s from %s',
                    trim($nextAccumulator, '\\'),
                    $this->_configuration->projectName
                );

                $data->layout->import       = new StdClass();
                $data->layout->import->file = __DIR__ . DS . 'Template' . DS . 'Namespace.html';

                $data->layout->import->data                = $data;
                $data->layout->import->data->configuration = $this->_configuration;

                $data->navigation             = new StdClass();
                $data->navigation->heading    = $accumulator;
                $data->navigation->namespaces = $siblingNamespaces;

                $view = new Templater(
                    __DIR__ . DS . 'Template' . DS . 'Layout.html',
                    new Write($output, Write::MODE_TRUNCATE_WRITE),
                    $this->_router,
                    $data
                );
                $view->render();

                $this->assembleNamespaces(
                    $subSymbols,
                    $nextAccumulator
                );
            }
        }
    }

    protected function assembleEntities(array $symbols, string $accumulator = '')
    {
        $siblingClasses    = [];
        $siblingInterfaces = [];
        $siblingTraits     = [];
        $siblingFunctions  = [];

        foreach ($symbols as $symbolPrefix => $symbol) {
            if ('@' === $symbolPrefix[0]) {
                list($symbolType, $symbolName) = explode(':', $symbolPrefix);

                $siblingEntity       = new StdClass();
                $siblingEntity->name = $symbolName;

                switch ($symbolType) {
                    case '@class':
                        $siblingEntity->url  =
                            '.' .
                            $this->_router->unroute(
                                'entity',
                                [
                                    'namespaceName' => mb_strtolower(str_replace('\\', '/', rtrim($accumulator, '\\'))),
                                    'shortName'     => $symbolName
                                ]
                            );

                        $siblingClasses[] = $siblingEntity;

                        break;

                    case '@interface':
                        $siblingEntity->url  =
                            '.' .
                            $this->_router->unroute(
                                'entity',
                                [
                                    'namespaceName' => mb_strtolower(str_replace('\\', '/', rtrim($accumulator, '\\'))),
                                    'shortName'     => $symbolName
                                ]
                            );

                        $siblingInterfaces[] = $siblingEntity;

                        break;

                    case '@trait':
                        $siblingEntity->url  =
                            '.' .
                            $this->_router->unroute(
                                'entity',
                                [
                                    'namespaceName' => mb_strtolower(str_replace('\\', '/', rtrim($accumulator, '\\'))),
                                    'shortName'     => $symbolName
                                ]
                            );

                        $siblingTraits[] = $siblingEntity;

                        break;

                    case '@function':
                        $siblingEntity->url  =
                            '.' .
                            $this->_router->unroute(
                                'entity',
                                [
                                    'namespaceName' => mb_strtolower(str_replace('\\', '/', rtrim($accumulator, '\\'))),
                                    'shortName'     => $symbolName
                                ]
                            );

                        $siblingFunctions[] = $siblingEntity;

                        break;
                }
            }
        }

        foreach ($symbols as $symbolPrefix => $subSymbols) {
            if ('@' !== $symbolPrefix[0]) {
                $this->assembleEntities(
                    $subSymbols,
                    $accumulator . $symbolPrefix . '\\'
                );
            } else {
                $symbolFullName = $subSymbols->name;

                list($symbolType, $symbolName) = explode(':', $symbolPrefix);

                switch ($symbolType) {
                    case '@class':
                    case '@interface':
                    case '@trait':
                    case '@function':
                        $output =
                            'hoa://Kitab/Output/' .
                            $this->_router->unroute(
                                'entity',
                                [
                                    'namespaceName' => mb_strtolower(str_replace('\\', '/', $accumulator)),
                                    'shortName'     => $symbolName
                                ]
                            );

                        break;

                    default:
                        continue 2;
                }

                $data = new StdClass();

                $data->configuration = $this->_configuration;

                $data->layout        = new StdClass();
                $data->layout->base  = './' . str_repeat('../', substr_count($accumulator, '\\'));
                $data->layout->title = sprintf(
                    '%s from %s',
                    $symbolFullName,
                    $this->_configuration->projectName
                );

                $data->layout->import       = new StdClass();
                $data->layout->import->file = __DIR__ . DS . 'Template' . DS . 'Entity.html';

                $data->layout->import->data = new StdClass();

                $data->layout->import->data->configuration = $this->_configuration;

                $data->layout->import->data->navigation             = new StdClass();
                $data->layout->import->data->navigation->heading    = $accumulator;
                $data->layout->import->data->navigation->classes    = $siblingClasses;
                $data->layout->import->data->navigation->interfaces = $siblingInterfaces;
                $data->layout->import->data->navigation->traits     = $siblingTraits;
                $data->layout->import->data->navigation->functions  = $siblingFunctions;

                $data->layout->import->data->layout = new StdClass();

                $data->layout->import->data->layout->import       = new StdClass();
                $data->layout->import->data->layout->import->file = __DIR__ . DS . 'Template' . DS . 'Echo.html';

                $data->layout->import->data->layout->import->data       = new StdClass();
                $data->layout->import->data->layout->import->data->echo = file_get_contents($output);

                $view = new Templater(
                    __DIR__ . DS . 'Template' . DS . 'Layout.html',
                    new Write($output, Write::MODE_TRUNCATE_WRITE),
                    $this->_router,
                    $data
                );
                $view->render();
            }
        }
    }

    protected function assembleResources()
    {
        $defaultNamespace = $this->_configuration->defaultNamespace;

        if (null !== $defaultNamespace) {
            $data      = new StdClass();
            $data->url = '.' . $this->_router->unroute(
                'namespace',
                [
                    'namespaceName' => mb_strtolower(str_replace('\\', '/', $defaultNamespace)),
                ]
            );

            $view = new Templater(
                __DIR__ . DS . 'Template' . DS . 'Redirect.html',
                new Write('hoa://Kitab/Output/index.html', Write::MODE_TRUNCATE_WRITE),
                $this->_router,
                $data
            );
            $view->render();
        }

        $from = __DIR__ . DS . 'Template' . DS . 'Public' . DS . 'css';
        $to   = 'hoa://Kitab/Output/css';

        (new Directory($from))->copy($to, Touchable::OVERWRITE);

        $from = __DIR__ . DS . 'Template' . DS . 'Public' . DS . 'javascript';
        $to   = 'hoa://Kitab/Output/javascript';

        (new Directory($from))->copy($to, Touchable::OVERWRITE);

        $from = __DIR__ . DS . 'Template' . DS . 'Public' . DS . 'font';
        $to   = 'hoa://Kitab/Output/font';

        (new Directory($from))->copy($to, Touchable::OVERWRITE);

        $from = dirname(__DIR__, 4) . DS . 'resource';
        $to   = 'hoa://Kitab/Output/resource';

        (new Directory($from))->copy($to, Touchable::OVERWRITE);

        Search::pack();

        $searchDatabase      = json_decode(file_get_contents(Search::DATABASE_FILE));
        $searchMetadataItems = [];

        foreach ($searchDatabase as $searchDatabaseItem) {
            $searchMetadataItems[] = [
                'id'          => $searchDatabaseItem->id,
                'name'        => $searchDatabaseItem->name,
                'description' => $searchDatabaseItem->description,
                'url'         => $searchDatabaseItem->url
            ];
        }

        $searchMetadata = new Write('hoa://Kitab/Output/javascript/search-metadata.js', Write::MODE_TRUNCATE_WRITE);
        $searchMetadata->writeAll('window.searchMetadata = ');
        $searchMetadata->writeAll(json_encode($searchMetadataItems));
        $searchMetadata->writeAll(';');

        $protocol = Protocol::getInstance();
        $output   = 'hoa://Kitab/Output/javascript/search-index.js';
        touch($output);

        if (defined('KITAB_PHAR_NAME')) {
            $temporaryJavascriptDirectory = 'hoa://Kitab/Temporary/Target/Html/Javascript';
            (new Directory(__DIR__ . DS . 'Javascript'))->copy($temporaryJavascriptDirectory, Touchable::OVERWRITE);

            $searchBuildIndex = $protocol->resolve($temporaryJavascriptDirectory . '/search-build-index.js');
        } else {
            $searchBuildIndex = __DIR__ . DS . 'Javascript' . DS . 'search-build-index.js';
        }

        Processus::execute(
            sprintf(
                '%s %s %s %s',
                Processus::locate('node'),
                escapeshellarg($searchBuildIndex),
                escapeshellarg($protocol->resolve(Search::DATABASE_FILE)),
                escapeshellarg($protocol->resolve($output))
            )
        );

        return;
    }
}
