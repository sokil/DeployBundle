<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Sokil\DeployBundle\Exception\TaskNotFoundException;
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
        $allTaskNames = array_keys($tasksConfiguration);

        // get task bundles
        $taskBundleList = $container->getParameter('deploy.taskBundles');
        if (empty($taskBundleList['default'])) {
            $taskBundleList['default'] = $allTaskNames;
        }

        // prepare tasks in pre-configured order
        $taskServices = array_fill_keys($allTaskNames, null);

        // build task references
        foreach ($container->findTaggedServiceIds('deploy.task') as $abstractTaskServiceId => $taskServiceTags) {
            foreach ($taskServiceTags as $taskServiceTagParameters) {

                $taskAlias = $taskServiceTagParameters['alias'];

                // check if task configured
                if (!array_key_exists($taskAlias, $tasksConfiguration)) {
                    continue;
                }

                // create task definition
                $taskServiceId = 'deploy.task.' . $taskAlias;
                $taskDefinition = new DefinitionDecorator($abstractTaskServiceId);
                $taskDefinition
                    ->addArgument($taskAlias)
                    ->addArgument($tasksConfiguration[$taskAlias]);

                // register definition
                $container->setDefinition($taskServiceId, $taskDefinition);

                // services to initialize
                $taskServices[$taskAlias] = new Reference($taskServiceId);
            }
        }

        // add tasks to task manager
        foreach ($taskServices as $taskAlias => $taskService) {
            if (!($taskService instanceof Reference)) {
                throw new TaskNotFoundException('Task "' . $taskAlias . '" has configuration but no tasks with this alias found');
            }

            $taskManagerDefinition->addMethodCall(
                'addTask',
                [
                    $taskService,
                ]
            );
        }

        // add task bundles to task manager
        foreach ($taskBundleList as $taskBundleName => $taskNameList) {
            $taskManagerDefinition->addMethodCall(
                'addTaskBundle',
                [
                    $taskBundleName,
                    $taskNameList,
                ]
            );
        }
    }
}