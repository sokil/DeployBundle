<?php

namespace Sokil\DeployBundle;

use Sokil\DeployBundle\DependencyInjection\TaskDiscoveryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DeployBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TaskDiscoveryCompilerPass());
    }
}
