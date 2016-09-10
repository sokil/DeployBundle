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

        $rootNode
            ->children()
                ->arrayNode('config')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('taskName')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('tasks')
                    ->useAttributeAsKey('taskName')
                    ->prototype('variable')
                ->end();

        return $treeBuilder;
    }
}
