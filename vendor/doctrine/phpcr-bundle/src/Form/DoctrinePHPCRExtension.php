<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\PHPCRBundle\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractExtension;

class DoctrinePHPCRExtension extends AbstractExtension
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    protected function loadTypes()
    {
        return [
            new Type\DocumentType($this->registry),
        ];
    }

    protected function loadTypeGuesser()
    {
        return new PhpcrOdmTypeGuesser($this->registry);
    }
}
