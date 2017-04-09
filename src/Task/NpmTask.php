<?php

/**
 * This file is part of the DeployBundle package.
 *
 * (c) Dmytro Sokil <dmytro.sokil@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
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
     * @var array
     */
    private $bundleConfigurationList;

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

    public function setResourceLocator(ResourceLocator $locator)
    {
        $this->resourceLocator = $locator;
    }

    public function getDescription()
    {
        return 'Updating npm dependencies';
    }

    /**
     * Prepare task options: check values and set default values
     *
     * @param array $options configuration
     *
     * @throws TaskConfigurationValidateException
     */
    protected function configure(array $options)
    {
        // bundles list
        if (empty($options['bundles']) || !is_array($options['bundles'])) {
            throw new TaskConfigurationValidateException('Bundles not configured for npm');
        }

        $this->bundleConfigurationList = $options['bundles'];
    }

    /**
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     * @return bool
     * @throws TaskConfigurationValidateException
     * @throws TaskExecuteException
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        // get path list to package.js
        $packagePathList = [];
        foreach ($this->bundleConfigurationList as $bundleName => $bundleConfiguration) {
            // skip disabled bundles
            if ($bundleConfiguration === false) {
                continue;
            }

            // get bundles with gruntfile inside
            $bundleDir = $this->resourceLocator->locateResource('@' . $bundleName);

            // get dir with bundle file
            $packageFileDir = $bundleDir;
            if (is_array($bundleConfiguration) && !empty($bundleConfiguration['package'])) {
                $packageFileDir .= rtrim($bundleConfiguration['package'], '/') . '/';
            }

            // check existence of gruntfile
            $packagePathList[$bundleName] = $packageFileDir . 'package.json';
            if (!file_exists($packagePathList[$bundleName])) {
                throw new TaskConfigurationValidateException(sprintf(
                    'Bundle "%s" configured for running npm but package.js not found at "%s"',
                    $bundleName,
                    $bundleDir
                ));
            }
        }

        // run npm
        foreach ($packagePathList as $bundleName => $packagePath) {
            $output->writeln('<' . self::STYLE_H2 . '>Install npm dependencies from ' . $packagePath . '</>');

            $packageDir = dirname($packagePath);
            $productionFlag = $environment === 'prod' ? ' --production' : null;
            $isSuccessful = $this->processRunner->run(
                'cd ' . $packageDir . '; npm install' . $productionFlag,
                $environment,
                $verbosity,
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error updating npm dependencies for bundle ' . $bundleName);
            }

            $output->writeln('Npm dependencies updated successfully for bundle ' . $bundleName);
        }
    }
}
