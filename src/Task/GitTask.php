<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;

class GitTask extends AbstractTask
    implements ProcessRunnerAwareTaskInterface
{
    const DEFAULT_REMOTE_NAME = 'origin';
    const DEFAULT_BRANCH_NAME = 'master';

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * @param ProcessRunner $runner
     */
    public function setProcessRunner(ProcessRunner $runner)
    {
        $this->processRunner = $runner;
    }

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

        foreach ($this->getOption('repos') as $repoName => $repoParams) {
            // pull
            $isSuccessful = $this->processRunner->run(
                'cd ' . $repoParams['path'] . '; git pull ' . $repoParams['remote'] . ' ' . $repoParams['branch'],
                $environment,
                $verbosity,
                $output
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error pulling repo ' . $repoName);
            }

            $output->writeln('Repo ' . $repoName . ' updated successfully');

            // add tag
            if (!empty($repoParams['tag'])) {
                $releaseTag = $this->buildReleaseTag($repoParams['tag']);
                $releaseMessage = $releaseTag;

                $isSuccessful = $this->processRunner->run(
                    'git tag -a ' . $releaseTag . ' -m ' . $releaseMessage,
                    $environment,
                    $verbosity,
                    $output
                );

                if (!$isSuccessful) {
                    throw new TaskExecuteException('Error tagging repo ' . $repoName);
                }

                $output->writeln('Release tagged in repo ' . $repoName);
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
     * @throws TaskConfigurationValidateException
     */
    protected function prepareOptions(array $options)
    {
        if (empty($options['defaultRemote'])) {
            $options['defaultRemote'] = self::DEFAULT_REMOTE_NAME;
        }

        if (empty($options['defaultBranch'])) {
            $options['defaultBranch'] = self::DEFAULT_BRANCH_NAME;
        }

        // prepare repos config
        if (empty($options['repos']) || !is_array($options['repos'])) {
            throw new TaskConfigurationValidateException('No repos found in configuration');
        }

        foreach ($options['repos'] as $repoName => &$repoParams) {
            if (empty($repoParams['path'])) {
                throw new TaskConfigurationValidateException('Path not configured for git repo "' . $repoName . '"');
            }

            if (!file_exists($repoParams['path'])) {
                throw new TaskConfigurationValidateException('Path not found for git repo "' . $repoName . '"');
            }

            if (empty($repoParams['remote'])) {
                $repoParams['remote'] = $options['defaultRemote'];
            }

            if (empty($repoParams['branch'])) {
                $repoParams['branch'] = $options['defaultBranch'];
            }

            if (empty($repoParams['tag'])) {
                $repoParams['tag'] = false;
            }
        }

        return $options;
    }
}