<?php

namespace Doctrine\Bundle\PHPCRBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\Bundle\PHPCRBundle\DependencyInjection\Compiler\InitializerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class InitializerPassTest extends AbstractCompilerPassTestCase
{
    public function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new InitializerPass());

        $inititializer = new Definition();
        $this->setDefinition(
            'doctrine_phpcr.initializer_manager',
            $inititializer
        );
    }

    public function testInitializerAddNoPriority()
    {
        $initializer = new Definition();
        $initializer->addTag('doctrine_phpcr.initializer');
        $this->setDefinition('test.initializer.1', $initializer);
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine_phpcr.initializer_manager',
            'addInitializer',
            [
                new Reference('test.initializer.1'),
                0,
            ]
        );
    }

    public function testInitializerAddWithPriority()
    {
        $initializer = new Definition();
        $initializer->addTag('doctrine_phpcr.initializer', ['priority' => 40]);
        $this->setDefinition('test.initializer.1', $initializer);
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'doctrine_phpcr.initializer_manager',
            'addInitializer',
            [
                new Reference('test.initializer.1'),
                40,
            ]
        );
    }
}
