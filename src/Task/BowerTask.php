<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\InvalidTaskConfigurationException;
use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\TaskManager\ResourceAwareInterface;
use Sokil\DeployBundle\TaskManager\ResourceLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class BowerTask extends AbstractTask  implements ResourceAwareInterface
{
    public function getDescription()
    {
        return 'Updating bower dependencies';
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

            $isSuccessfull = $this->runShellCommand(
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