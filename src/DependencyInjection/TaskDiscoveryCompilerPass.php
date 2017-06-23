<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Sokil\DeployBundle\Exception\TaskNotFoundException;
use Sokil\DeployBundle\TaskManager;
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
        $taskManagerDefinition = $container->getDefinition('deploy.task_manager');

        // get deploy configuration
        $tasksConfiguration = $taskManagerDefinition->getArgument(0);
        $allTaskNames = array_keys($tasksConfiguration);


        // get task bundles
        $taskBundleList = $container->getParameter('deploy.taskBundles');
        if (empty($taskBundleList['default'])) {
            $taskBundleList[TaskManager::DEFAULT_TASK_BUNDLE_NAME] = $allTaskNames;
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
                $taskDefinition->addArgument($taskAlias);

                // register definition
                $container->setDefinition($taskServiceId, $taskDefinition);
            }
        }

        // add tasks to task manager with preserved order
        foreach ($taskServices as $taskAlias => $taskService) {
            if (!($taskService instanceof Reference)) {
                throw new TaskNotFoundException(sprintf(
                    'Task "%s" has configuration but no tasks with this alias found',
                    $taskAlias
                ));
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
