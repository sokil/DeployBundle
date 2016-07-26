<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Output\OutputInterface;

class NpmTask extends AbstractTask implements
    ResourceAwareTaskInterface,
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @param ProcessRunner $runner
     * @return BowerTask
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
        return $this;
    }

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    public function getDescription()
    {
        return 'Updating npm dependencies';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $bundleList = $this->getOption('bundles');
        if (empty($bundleList) || !is_array($bundleList)) {
            throw new TaskConfigurationValidateException('Bundles not configured for bower');
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

            $isSuccessfull = $this->processRunner->run(
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