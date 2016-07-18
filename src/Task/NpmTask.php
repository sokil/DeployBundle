<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\TaskManager\ResourceAwareInterface;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class NpmTask extends AbstractTask implements ResourceAwareInterface
{
    public function getDescription()
    {
        return 'Updating npm dependencies';
    }

    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    public function run(
        callable $input,
        callable $output,
        $environment,
        $verbosity
    ) {
        $bundleList = $this->getOption('bundles');
        if (empty($bundleList) || !is_array($bundleList)) {
            throw new InvalidTaskConfigurationException('Bundles not configured for bower');
        }

        foreach ($bundleList as $bundleName) {
            // get bundle path
            $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);

            // get package file path
            $npmPath = $bundlePath . 'package.json';
            if (!file_exists($npmPath)) {
                return true;
            }

            // run task
            $output->writeln('<' . $this->h2Style . '>Install npm dependencies from ' . $npmPath . '</>');

            $productionFlag = $environment === 'prod' ? ' --production' : null;

            $isSuccessfull = $this->runShellCommand(
                'cd ' . $bundlePath . '; npm install' . $productionFlag,
                function() use ($output) {
                    $output->writeln('Npm dependencies updated successfully');
                },
                function() use ($output) {
                    $output->writeln('<error>Error while updating Npm dependencies</error>');
                },
                $output
            );

            if (!$isSuccessfull) {
                throw new TaskExecuteException('Error updating npm dependencies for bundle ' . $bundleName);
            }
        }
    }
}