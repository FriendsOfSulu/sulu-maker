<?php

namespace FriendsOfSulu\MakerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluMakerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('maker')) {
            throw new \LogicException('The Symfony MakerBundle is not installed or not enabled in the bundles.php file.');
        }
        parent::build($container);
    }
}
