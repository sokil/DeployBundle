<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class TaskDiscoveryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // find task manager
        $taskManagerDefinition = $container->findDefinition(
            'deploy.task_manager'
        );

        // get deploy configuration
        $tasksConfiguration = $container->getParameter('deploy.tasksConfiguration');

        // find tasks
        $taskDefinitionList = $container->findTaggedServiceIds('deploy.task');
        foreach ($taskDefinitionList as $abstractTaskServiceId => $taskServiceTags) {
            foreach ($taskServiceTags as $taskServiceTagParameters) {

                $taskAlias = $taskServiceTagParameters['alias'];

                // check if task configured
                if (!array_key_exists($taskAlias, $tasksConfiguration)) {
                    continue;
                }

                // create task definition
                $taskServiceId = 'deploy.task.' . $taskAlias;
                $container
                    ->setDefinition($taskServiceId, new DefinitionDecorator($abstractTaskServiceId))
                    ->addArgument($taskAlias)
                    ->addArgument($tasksConfiguration[$taskAlias]);

                // add task to task manager
                $taskManagerDefinition->addMethodCall(
                    'addTask',
                    [
                        new Reference($taskServiceId),
                    ]
                );
            }
        }
    }
}