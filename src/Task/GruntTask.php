<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Sokil\DeployBundle\TaskManager\BundleTaskInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class GruntTask extends AbstractTask implements BundleTaskInterface
{
    private $bundles;

    public function setBundles(array $bundles)
    {
        $this->bundles = $bundles;
    }

    public function run()
    {
        // parse passed grunt tasks
        $gruntTasks = $this->parseGruntTasks($input->getOption('grunt-task'));

        foreach ($this->bundles as $bundleName => $bundlePath) {
            $bundleGruntTasks = !empty($gruntTasks[$bundleName]) ? $gruntTasks[$bundleName] : null;

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

    public function configureCommand(
        Command $command
    ) {
        $command
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
            );
    }
}