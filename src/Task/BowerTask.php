<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\InvalidTaskConfigurationException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Output\OutputInterface;

class BowerTask extends AbstractTask implements
    ResourceAwareTaskInterface,
    ProcessRunnerAwareTaskInterface
{
    /**
     * @var ResourceLocator
     */
    private $resourceLocator;

    /**
     * @var
     */
    private $processRunner;

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
        return $this;
    }

    /**
     * @param ProcessRunner $runner
     * @return BowerTask
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->$this->processRunner = $runner;
        return $this;
    }

    public function getDescription()
    {
        return 'Updating bower dependencies';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $bundleList = $this->getOption('bundles');
        if (empty($bundleList) || !is_array($bundleList)) {
            throw new InvalidTaskConfigurationException('Bundles not configured for bower');
        }

        foreach ($bundleList as $bundleName) {
            // get bundle path
            $bundlePath = $this->resourceLocator->locateResource('@' . $bundleName);

            // check path to bower
            $bowerPath = $bundlePath . 'bower.json';
            if (!file_exists($bowerPath)) {
                return true;
            }

            // execute
            $output->writeln('<' . $this->h2Style . '>Install bower dependencies from ' . $bowerPath . '</>');

            $productionFlag = $environment === 'prod' ? ' --production' : null;

            $isSuccessfull = $this->processRunner->run(
                'cd ' . $bundlePath . '; bower install' . $productionFlag,
                function() use ($output) {
                    $output->writeln('Bower dependencies updated successfully');
                },
                function() use ($output) {
                    $output->writeln('<error>Error while updating bower dependencies</error>');
                },
                $output
            );

            if (!$isSuccessfull) {
                throw new TaskExecuteException('Error updating bower dependencies for bundle ' . $bundleName);
            }
        }
    }
}