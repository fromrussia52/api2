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
use Hoa\Dispatcher;
use Hoa\Exception;
use Hoa\Router;

/**
 * Run the sub commands of the `kitab` command.
 *
 * A sub command must be a class declared in the `Kitab\Bin` namespace. Its
 * `run` public method —the entry method— will be called to run the sub
 * command.
 *
 * Any exception thrown will be outputed in `php://stderr` with a brilliant
 * colour.
 */
class Bin
{
    /**
     * Call the sub command.
     */
    public static function main()
    {
        self::setErrorHandler();

        try {
            $router     = self::getRouter();
            $dispatcher = self::getDispatcher();

            exit((int) $dispatcher->dispatch($router));
        } catch (Exception $e) {
            $message = $e->raise(true);
            $code    = 1;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $code    = 2;
        }

        self::exitWith($message, $code);
    }

    /**
     * Use the Hoa default error and exception handlers.
     */
    protected static function setErrorHandler()
    {
        Exception\Error::enableErrorHandler();
        Exception::enableUncaughtHandler();
    }

    protected static function getRouter(): Router\Cli
    {
        $router = new Router\Cli();
        $router->get(
            'g',
            '(?<command>\w+)?(?<_tail>.*?)',
            'main',
            'main',
            [
                'command' => 'welcome'
            ]
        );

        return $router;
    }

    protected static function getDispatcher(): Dispatcher\ClassMethod
    {
        $dispatcher = new Dispatcher\ClassMethod([
            'synchronous.call'
                => 'Kitab\Bin\(:%variables.command:lU:)',
            'synchronous.able'
                => 'run'
        ]);
        $dispatcher->setKitName(Console\Dispatcher\Kit::class);

        return $dispatcher;
    }

    protected static function exitWith(string $message, int $code)
    {
        ob_start();

        Console\Cursor::colorize('foreground(white) background(red)');
        echo $message, "\n";
        Console\Cursor::colorize('normal');
        $content = ob_get_contents();

        ob_end_clean();

        file_put_contents('php://stderr', $content);
        exit($code);
    }
}
