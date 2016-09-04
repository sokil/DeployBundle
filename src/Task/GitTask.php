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

use Sokil\DeployBundle\Event\AfterTasksEvent;
use Sokil\DeployBundle\Exception\TaskConfigurationValidateException;
use Sokil\DeployBundle\Exception\TaskExecuteException;
use Sokil\DeployBundle\TaskManager\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Task pull changes from remote branch and tag new release.
 * Remote branch must be stable, because you can's specify concrete relase tag to checkout in.
 *
 * Sample configuration:
 *
 *  tasks:
 *      git:
 *          defaultRemote: origin                   # default remote to pull (optional; default: origin)
 *          defaultBranch: master                   # default branch to pull (optional; default: master)
 *          repos:                                  # list of repos to pull and tag
 *              core:                               # name of repo
 *                  path: "%kernel.root_dir%/../"   # path to repo
 *                  branch: master                  # remote to pull (optional; default: origin, or configured in defaultRemote)
 *                  remote: origin                  # branch to pull (optional; default: master, or configured in defaultBranch)
 *                  tag: true                       # allow tag release
 *
 * If 'tag' key is true, rag release will be in format '%date%-release', also you can pass your own pattern:
 *
 *          repos:
 *              core:
 *                  tag: 'release-%date%'
 *
 * Currently supported only %date% and %datetime% placeholder for release tag.
 */
class GitTask extends AbstractTask implements
    ProcessRunnerAwareTaskInterface,
    EventSubscriberInterface
{
    const DEFAULT_REMOTE_NAME = 'origin';
    const DEFAULT_BRANCH_NAME = 'master';

    const DEFAULT_TAG_PATTERN = '%date%-release';

    /**
     * @var ProcessRunner
     */
    private $processRunner;

    /**
     * Mark if task was run
     * @var bool
     */
    private $wasRun = false;

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
        }

        $this->wasRun = true;
    }

    protected function buildReleaseTag($tagPattern)
    {
        return str_replace(
            [
                '%date%',
                '%datetime%',
            ],
            [
                date('Y-m-d'),
                date('Y-m-d.H.i.s')
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
            } elseif (true === $repoParams['tag']) {
                $repoParams['tag'] = self::DEFAULT_TAG_PATTERN;
            }
        }

        return $options;
    }

    public static function getSubscribedEvents()
    {
        return [
            AfterTasksEvent::name => [
                'onAfterTasksFinishedTagRelease'
            ]
        ];
    }

    public function onAfterTasksFinishedTagRelease(AfterTasksEvent $event)
    {
        if (!$this->wasRun) {
            return;
        }

        foreach ($this->getOption('repos') as $repoName => $repoParams) {
            if (empty($repoParams['tag'])) {
                continue;
            }

            $releaseTag = $this->buildReleaseTag($repoParams['tag']);
            $releaseMessage = $releaseTag;

            $isSuccessful = $this->processRunner->run(
                'git tag -a ' . $releaseTag . ' -m ' . $releaseMessage,
                $event->getEnvironment(),
                $event->getVerbosity(),
                $event->getOutput()
            );

            if (!$isSuccessful) {
                throw new TaskExecuteException('Error tagging repo ' . $repoName);
            }

            $event->getOutput()->writeln('Release tagged in repo ' . $repoName);
        }
    }
}