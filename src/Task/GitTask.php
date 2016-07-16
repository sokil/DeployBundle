<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\TaskManager\AbstractTask;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class GitTask extends AbstractTask
{
    const DEFAULT_BRANCH_NAME = 'master';
    const DEFAULT_REMOTE_NAME = 'origin';

    public function run(
        InputInterface $input,
        OutputInterface $output
    ) {
        $remote = $this->input->getOption('git-remote');
        $branch = $this->input->getOption('git-branch');

        $this->output->writeln('<' . $this->h2Style . '>Updating source from Git repository</>');

        // pull
        return $this->runShellCommand(
            'git pull ' . $remote . ' ' . $branch,
            function($input, $output) {
                $output->writeln('Source updated successfully');
            },
            function($input, $output) {
                $output->writeln('<error>Error updating source</error>');
            }
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

    public function configureCommand(
        Command $command
    ) {
        $command
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
            );
    }
}