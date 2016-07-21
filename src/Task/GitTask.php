<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\InvalidTaskConfigurationException;
use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class GitTask extends AbstractTask
{
    const DEFAULT_REMOTE_NAME = 'origin';
    const DEFAULT_BRANCH_NAME = 'master';

    public function getDescription()
    {
        return 'Update source from Git repository';
    }

    public function run(
        array $commandOptions,
        $environment,
        $verbosity,
        OutputInterface $output
    ) {
        $output->writeln('<' . $this->h2Style . '>Updating source from Git repository</>');

        foreach ($this->getReposConfig() as $repoName => $repoParams) {
            // pull
            $isSuccessfull = $this->runShellCommand(
                'cd ' . $repoParams['path'] . '; git pull ' . $repoParams['remote'] . ' ' . $repoParams['branch'],
                $environment,
                $verbosity,
                function($input, $output) {
                    $output->writeln('Source updated successfully');
                },
                function($input, $output) {
                    $output->writeln('<error>Error updating source</error>');
                },
                $output
            );


            if (!$isSuccessfull) {
                throw new TaskExecuteException('Error pulling repo ' . $repoName);
            }

            // add tag
            if (!empty($repoParams['tag'])) {
                $releaseTag = $this->buildReleaseTag($repoParams['tag']);
                $releaseMessage = $releaseTag;

                $isSuccessfull = $this->runShellCommand(
                    'git tag -a ' . $releaseTag . ' -m ' . $releaseMessage,
                    $environment,
                    $verbosity,
                    function() use ($output) {
                        $output->writeln('Release tagged');
                    },
                    function() use ($output) {
                        $output->writeln('<error>Error updating source</error>');
                    },
                    $output
                );

                if (!$isSuccessfull) {
                    throw new TaskExecuteException('Error pulling repo ' . $repoName);
                }
            }
        }
    }

    protected function buildReleaseTag($tagPattern)
    {
        $date = date('Y-m-d.H.i.s');

        if (true === $tagPattern) {
            return $date . '-release';
        }

        return str_replace(
            [
                '%date%'
            ],
            [
                $date
            ],
            $tagPattern
        );
    }

    /**
     * Git config of repositories
     *
     * @return array
     * @throws InvalidTaskConfigurationException
     */
    private function getReposConfig()
    {
        $defaultRemote = $this->getOption('defaultRemote', self::DEFAULT_REMOTE_NAME);
        $defaultBranch = $this->getOption('defaultBranch', self::DEFAULT_BRANCH_NAME);

        // prepare repos config
        $repoConfigList = $this->getOption('repos');
        if (!$repoConfigList) {
            throw new TaskConfigurationValidateException('No repos found in configuration');
        }

        foreach ($repoConfigList as $repoName => $repoParams) {
            if (empty($repoParams['path'])) {
                throw new TaskConfigurationValidateException('Path not configured for git repo "' . $repoName . '"');
            }

            if (!file_exists($repoParams['path'])) {
                throw new TaskConfigurationValidateException('Path not found for git repo "' . $repoName . '"');
            }

            if (empty($repoParams['remote'])) {
                $repoParams['remote'] = $defaultRemote;
            }

            if (empty($repoParams['branch'])) {
                $repoParams['branch'] = $defaultBranch;
            }

            if (empty($repoParams['tag'])) {
                $repoParams['tag'] = false;
            }
        }

        return $repoConfigList;
    }
}