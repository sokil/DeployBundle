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
     * List of path to package.json with additional data
     * Format:
     * [
     *   [
     *      'packageFile' => './some/path/to/package.json',     REQUIRED    Path to package.json
     *      'bundleName' => 'AcmeBundle',                       OPTIONAL    Bundle name, if package.json in bundle
     *   ],
     *   ...
     * ]
     * @var array
     */
    private $packageList = [];

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
        if (!empty($options['bundles']) && !is_array($options['bundles'])) {
            $this->addPackagesFromBundleConfig($options['bundles']);
        } elseif (!empty($options['dirs']) && is_array($options['dirs'])) {
            foreach ($options['dirs'] as $dir) {
                $this->addPackage($dir);
            }
        } else {
            throw new TaskConfigurationValidateException('Task "npm" has no packages configured');
        }
    }

    /**
     * @param array $bundleConfigurationList
     *
     * @throws TaskConfigurationValidateException
     */
    private function addPackagesFromBundleConfig(array $bundleConfigurationList)
    {
        foreach ($bundleConfigurationList as $bundleName => $bundleConfiguration) {
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

            // register package
            $this->addPackage(
                $packageFileDir,
                [
                    'bundleName' => $bundleName,
                ]
            );
        }
    }

    /**
     * @param string $dir directory where package.json placed
     * @param array $metadata optional metadata
     *
     * @throws TaskConfigurationValidateException
     */
    private function addPackage($dir, array $metadata = []) {
        // check existence of package file
        $path = realpath($dir) . '/package.json';
        if (!file_exists($path)) {
            throw new TaskConfigurationValidateException(sprintf(
                'File package.json not found in "%s"',
                $dir
            ));
        }

        $this->packageList[] = [
            'path' => $path,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param array $commandOptions
     * @param $environment
     * @param $verbosity
     * @param OutputInterface $output
     *
     * @return bool
     *
     * @throws TaskConfigurationValidateException
     * @throws TaskExecuteException
     */
    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        // run npm
        foreach ($this->packageList as $package) {
            $output->writeln('<' . self::STYLE_H2 . '>Install npm dependencies from ' . $package['path'] . '</>');

            $packageDir = dirname($package['path']);
            $productionFlag = $environment === 'prod' ? ' --production' : null;
            $isSuccessful = $this->processRunner->run(
                'cd ' . $packageDir . '; npm install' . $productionFlag,
                $environment,
                $verbosity,
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException(sprintf('Error updating npm dependencies in %s', $package['path']));
            }

            $output->writeln(sprintf('Npm dependencies updated successfully in %s', $package['path']));
        }
    }
}
