<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy');

        $tasksNode = $rootNode
            ->children()
                ->arrayNode('tasks')
                ->isRequired()
                ->cannotBeEmpty()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('taskName')
                ->prototype('variable');

        return $treeBuilder;
    }
}
