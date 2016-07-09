<?php

namespace Sokil\DeployBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Symfony\Component\Process\Process;

class DeployCommand extends ContainerAwareCommand
{
    private $h1Style = 'fg=black;bg=cyan';
    private $h2Style = 'fg=black;bg=yellow';

    private $gitDefaultRemote = 'origin';
    private $gitDefaultBranch = 'master';

    protected function configure()
    {
        $this
            ->setName('deploy')
            ->addOption(
                'git',
                null,
                InputOption::VALUE_NONE,
                'Update source from Git repository'
            )
            ->addOption(
                'git-branch',
                null,
                InputOption::VALUE_OPTIONAL,
                'Update source from passed branch',
                $this->gitDefaultBranch
            )
            ->addOption(
                'git-remote',
                null,
                InputOption::VALUE_OPTIONAL,
                'Update source from passed remote',
                $this->gitDefaultRemote
            )
            ->addOption(
                'composer',
                null,
                InputOption::VALUE_NONE,
                'Update composer dependencies'
            )
            ->addOption(
                'bower',
                null,
                InputOption::VALUE_NONE,
                'Updating bower dependencies'
            )
            ->addOption(
                'npm',
                null,
                InputOption::VALUE_NONE,
                'Updating npm dependencies'
            )
            ->addOption(
                'grunt',
                null,
                InputOption::VALUE_NONE,
                'Executing grunt tasks'
            )
            ->addOption(
                'grunt-task',
                null,
                InputOption::VALUE_OPTIONAL,
                'List of grunt tasks'
            )
            ->addOption(
                'migrate',
                null,
                InputOption::VALUE_NONE,
                'Migrate datbase'
            )
            ->addOption(
                'asseticDump',
                null,
                InputOption::VALUE_NONE,
                'Dump assetic assets'
            )
            ->addOption(
                'assetsInstall',
                null,
                InputOption::VALUE_NONE,
                'Install bundle assets'
            )
            ->addOption(
                'clearCache',
                null,
                InputOption::VALUE_NONE,
                'ClearCache'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getOption('env');
        
        // put env variable
        putenv('SYMFONY_ENV=' . $environment);

        // deploy command features
        $requireGit = $input->getOption('git');
        $requireComposer = $input->getOption('composer');
        $requireBower = $input->getOption('bower');
        $requireNpm = $input->getOption('npm');
        $requireGrunt = $input->getOption('grunt');
        $requireMigrateDoctrine = $input->getOption('migrate');
        $requireAsseticDump = $input->getOption('asseticDump');
        $requireAssetsInstall = $input->getOption('assetsInstall');
        $requireClearCache = $input->getOption('clearCache');

        $requireAll = !(
            $requireGit
            || $requireComposer
            || $requireBower
            || $requireNpm
            || $requireGrunt
            || $requireMigrateDoctrine
            || $requireAsseticDump
            || $requireAssetsInstall
            || $requireClearCache
        );

        if ($requireAll) {
            $requireGit = true;
            $requireComposer = true;
            $requireBower = true;
            $requireNpm = true;
            $requireGrunt = true;
            $requireMigrateDoctrine = true;
            $requireAsseticDump = true;
            $requireAssetsInstall = true;
            $requireClearCache = true;
        }

        $requirePerBundleTasks =  $requireBower || $requireNpm || $requireGrunt;

        // git pull
        if ($requireGit) {
            $success = $this->updateSource(
                $input->getOption('git-remote'),
                $input->getOption('git-branch'),
                $output
            );
            if (!$success) {
                return;
            }

        }

        // composer
        if ($requireComposer) {
            $success = $this->updateComposer($environment, $output);
            if (!$success) {
                return;
            }
        }

        // migrate doctrine entities
        if ($requireMigrateDoctrine) {
            $this->migrateDoctrine($output);
        }

        // per-bundle deploy
        if ($requirePerBundleTasks) {
            // iterate bundles
            $bundles = $this->getContainer()->getParameter('site_core.deploy.bundles');
            $kernel = $this->getContainer()->get('kernel');

            // parse passed grunt tasks
            $gruntTasks = $this->parseGruntTasks($input->getOption('grunt-task'));

            foreach ($bundles as $bundleName) {
                $bundlePath = $kernel->locateResource('@' . $bundleName);
                $output->writeln('<' . $this->h1Style . '>Deploying bundle ' . $bundleName . ' from ' . $bundlePath . '</>');

                // run bower update on each bundle
                if ($requireBower) {
                    $success = $this->updateBower($bundlePath, $environment, $output);
                    if (!$success) {
                        return;
                    }
                }

                // run npm update on each bundle
                if ($requireNpm) {
                    $success = $this->updateNpm($bundlePath, $environment, $output);
                    if (!$success) {
                        return;
                    }
                }

                // run grunt on each bundle
                if ($requireGrunt) {
                    $bundleGruntTasks = !empty($gruntTasks[$bundleName]) ? $gruntTasks[$bundleName] : null;
                    $success = $this->startGrunt(
                        $bundlePath,
                        $environment,
                        $bundleGruntTasks,
                        $output
                    );

                    if (!$success) {
                        return;
                    }
                }
            }
        }

        // dump assets
        if ($requireAsseticDump) {
            $this->asseticDump($environment, $output);
        }

        if ($requireAssetsInstall) {
            $this->assetsInstall($environment, $output);
        }

        // clear cache
        if ($requireClearCache) {
            $this->clearCache($environment, $output);
        }
    }

    /**
     * Parse grunt tasks configuration obtainer from console input
     * @param $gruntTasksString config in format "BundleName:grunt tasks delimited by whitespace;OtherBundleName:..."
     * @return array
     */
    private function parseGruntTasks($gruntTasksString)
    {
        $tasks = [];

        if (!$gruntTasksString) {
            return [];
        }

        foreach (explode(';', $gruntTasksString) as $bundleGruntTasksString) {
            $bundleGruntTasksArray = array_map('trim', explode(':', $bundleGruntTasksString));
            if (count($bundleGruntTasksArray) != 2) {
                continue;
            }

            list($bundleName, $bundleTasks) = $bundleGruntTasksArray;
            $tasks[$bundleName] = $bundleTasks;
        }

        return $tasks;
    }

    /**
     * @param $command shell command to execute
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param OutputInterface $output
     */
    private function runShellCommand(
        $command,
        callable $doneCallback,
        callable $failCallback,
        OutputInterface $output
    ) {
        $verbosity = $output->getVerbosity();

        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Command: </info>' . $command);
        }

        // execute command
        $process = new Process(
            $command,
            null, // cwd
            null, // env
            null, // input
            null  // timeout
        );

        $process->start();

        // run standard output
        if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            while ($process->isRunning()) {
                $output->write($process->getIncrementalOutput());
                $output->write($process->getIncrementalErrorOutput());
            }
        }

        // wait exitcode
        while($process->getExitCode() === null) {
            usleep(100000);
        }

        // state handling
        if (!$process->isSuccessful()) {
            // render error output
            $output->writeln($process->getErrorOutput());
            // exit code
            $output->writeln($process->getExitCodeText());
            // fail callback
            call_user_func($failCallback);

            return false;
        }

        // done callback
        call_user_func($doneCallback);

        return true;
    }

    private function updateSource($remote, $branch, OutputInterface $output)
    {
        $output->writeln('<' . $this->h2Style . '>Updating source from Git repository</>');

        // pull
        return $this->runShellCommand(
            'git pull ' . $remote . ' ' . $branch,
            function() use($output) {
                $output->writeln('Source updated successfully');
            },
            function() use($output) {
                $output->writeln('<error>Error updating source</error>');
            },
            $output
        );

        // add tag
        $releaseTag = date('Y-m-d.H.i.s') . '-release';
        $releaseMessage = $releaseTag;

        return $this->runShellCommand(
            'git tag -a ' . $releaseTag . ' -m ' . $releaseMessage,
            function() use($output) {
                $output->writeln('Release tagged');
            },
            function() use($output) {
                $output->writeln('<error>Error updating source</error>');
            },
            $output
        );
    }

    private function updateComposer($environment, OutputInterface $output)
    {
        $output->writeln('<' . $this->h2Style . '>Updating composer dependencies</>');

        $command = 'composer.phar update --optimize-autoloader --no-interaction';

        if ($environment !== 'dev') {
            $command .= ' --no-dev';
        }

        // verbosity
        $verbosity = $output->getVerbosity();
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_VERBOSE:
                $command .= ' -v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $command .= ' -vv';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $command .= ' -vvv';
                break;
        }

        return $this->runShellCommand(
            $command,
            function() use($output) {
                $output->writeln('Composer dependencies updated successfully');
            },
            function() use($output) {
                $output->writeln('<error>Error updating composer dependencies</error>');
            },
            $output
        );
    }

    private function updateBower($bundlePath, $environment, OutputInterface $output)
    {
        $bowerPath = $bundlePath . 'bower.json';

        if (!file_exists($bowerPath)) {
            return true;
        }

        $output->writeln('<' . $this->h2Style . '>Install bower dependencies from ' . $bowerPath . '</>');

        $productionFlag = $environment === 'prod' ? ' --production' : null;

        return $this->runShellCommand(
            'cd ' . $bundlePath . '; bower install' . $productionFlag,
            function() use ($output) {
                $output->writeln('Bower dependencies updated successfully');
            },
            function() use ($output) {
                $output->writeln('<error>Error while updating bower dependencies</error>');
            },
            $output
        );
    }

    private function updateNpm($bundlePath, $environment, OutputInterface $output)
    {
        $npmPath = $bundlePath . 'package.json';

        if (!file_exists($npmPath)) {
            return true;
        }

        $output->writeln('<' . $this->h2Style . '>Install npm dependencies from ' . $npmPath . '</>');

        $productionFlag = $environment === 'prod' ? ' --production' : null;

        return $this->runShellCommand(
            'cd ' . $bundlePath . '; npm install' . $productionFlag,
            function() use ($output) {
                $output->writeln('Npm dependencies updated successfully');
            },
            function() use ($output) {
                $output->writeln('<error>Error while updating Npm dependencies</error>');
            },
            $output
        );
    }

    private function startGrunt(
        $bundlePath,
        $environment,
        $tasks,
        OutputInterface $output
    ) {
        $gruntPath = $bundlePath . 'Gruntfile.js';

        if (!file_exists($gruntPath)) {
            return true;
        }

        $output->writeln('<' . $this->h2Style . '>Execute grunt tasks from ' . $gruntPath . '</>');

        $command = 'cd ' . $bundlePath . '; grunt --env=' . $environment;

        if ($tasks) {
            $command .= ' ' . $tasks;
        }

        return $this->runShellCommand(
            $command,
            function() use ($output) {
                $output->writeln('Grunt tasks executed successfully');
            },
            function() use ($output) {
                $output->writeln('<error>Error executing grunt tasks</error>');
            },
            $output
        );
    }

    private function asseticDump($environment, OutputInterface $output)
    {
        $command = $this->getApplication()->find('assetic:dump');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assetic:dump',
                '--env'    => $environment,
            )),
            $output
        );
    }

    private function assetsInstall($environment, OutputInterface $output)
    {
        $command = $this->getApplication()->find('assets:install');
        return $command->run(
            new ArrayInput(array(
                'command'  => 'assets:install',
                '--env'    => $environment,
            )),
            $output
        );
    }

    private function clearCache($environment, OutputInterface $output)
    {
        $command = $this->getApplication()->find('cache:clear');

        return $command->run(
            new ArrayInput(array(
                'command'  => 'cache:clear',
                '--env'    => $environment,
            )),
            $output
        );
    }

    private function migrateDoctrine(OutputInterface $output)
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');

        return $command->run(
            new ArrayInput(array(
                'command' => 'doctrine:migrations:migrate',
                '--no-interaction' => true,
            )),
            $output
        );
    }
}
