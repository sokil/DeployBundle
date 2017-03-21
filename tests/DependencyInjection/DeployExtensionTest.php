<?php

namespace Sokil\DeployBundle\DependencyInjection;

use Sokil\DeployBundle\AbstractTestCase;
use Sokil\DeployBundle\Task\GitTask;
use Sokil\DeployBundle\TaskManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DeployExtensionTest extends AbstractTestCase
{
    public function testGetTaskManager()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');
        $this->assertInstanceOf('\Sokil\DeployBundle\TaskManager', $taskManager);
    }

    public function testGetSimpleTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // simple task
        $gitTask = $taskManager->getTask('git');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GitTask', $gitTask);

        $reflectionClass = new \ReflectionClass($gitTask);

        $defaultRemoteProperty = $reflectionClass->getProperty('defaultRemote');
        $defaultRemoteProperty->setAccessible(true);
        $this->assertSame(
            GitTask::DEFAULT_REMOTE_NAME,
            $defaultRemoteProperty->getValue($gitTask)
        );

        $defaultBranchProperty = $reflectionClass->getProperty('defaultBranch');
        $defaultBranchProperty->setAccessible(true);
        $this->assertSame(
            GitTask::DEFAULT_BRANCH_NAME,
            $defaultBranchProperty->getValue($gitTask)
        );

        $reposProperty = $reflectionClass->getProperty('repos');
        $reposProperty->setAccessible(true);
        $this->assertSame(
            [
                'core' => [
                    'path' => '/tmp',
                    'branch' => 'master',
                    'remote' => 'origin',
                    'tag' => false
                ],
            ],
            $reposProperty->getValue($gitTask)
        );
    }

    public function testGetResourceAwareTask()
    {
        $taskManager = $this->getContainer()->get('deploy.task_manager');

        // resource aware task
        $gitTask = $taskManager->getTask('grunt');
        $this->assertInstanceOf('\Sokil\DeployBundle\Task\GruntTask', $gitTask);

        $reflectionClass = new \ReflectionClass($gitTask);

        $bundlesProperty = $reflectionClass->getProperty('bundles');
        $bundlesProperty->setAccessible(true);
        $this->assertSame(
            [
                'bundle1' => 'gruntTask1 gruntTask2',
                'bundle2' => true,
            ],
            $bundlesProperty->getValue($gitTask)
        );

        $parallelProperty = $reflectionClass->getProperty('parallel');
        $parallelProperty->setAccessible(true);
        $this->assertSame(
            false,
            $parallelProperty->getValue($gitTask)
        );
    }
}