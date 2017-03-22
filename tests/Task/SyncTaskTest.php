<?php

namespace Sokil\DeployBundle\Task;

use Sokil\DeployBundle\AbstractTestCase;
use Sokil\DeployBundle\TaskManager;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTaskTest extends AbstractTestCase
{
    public function testRun()
    {
        // create task
        $syncTask = new SyncTask(
            'sync',
            [
                'web' => [
                    'source' => '.',
                    'target' => [
                        'user@web1.server.com://var/www/site',
                        'user@web2.server.com://var/www/site',
                    ],
                    'exclude' => [
                        '/var',
                        '/app/conf/nginx/',
                        '/.idea',
                        '/app/config/parameters.yml',
                    ],
                    'include' => [
                        '/app/conf/nginx/*.conf.sample',
                    ],
                    'deleteExtraneousFiles' => true,
                    'verbose' => true,
                ],
                'parallel' => 1,
            ]
        );

        // mock process runner
        $syncTask->setProcessRunner($this->createProcessRunner(
            [
                [
                    'rsync -a -v --delete --exclude /var --exclude /app/conf/nginx/ --exclude /.idea --exclude /app/config/parameters.yml --include /app/conf/nginx/*.conf.sample . user@web1.server.com://var/www/site',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf(Output::class)
                ],
                [
                    'rsync -a -v --delete --exclude /var --exclude /app/conf/nginx/ --exclude /.idea --exclude /app/config/parameters.yml --include /app/conf/nginx/*.conf.sample . user@web2.server.com://var/www/site',
                    'dev',
                    OutputInterface::VERBOSITY_NORMAL,
                    $this->isInstanceOf(Output::class)
                ],
            ],
            true
        ));

        $syncTask->run(
            [],
            'dev',
            Output::VERBOSITY_NORMAL,
            $this->createOutput()
        );
    }
}