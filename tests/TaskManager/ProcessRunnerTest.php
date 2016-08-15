<?php

namespace Sokil\DeployBundle\TaskManager;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;
use Sokil\DeployBundle\AbstractTestCase;

class ProcessRunnerTest extends AbstractTestCase
{
    /**
     * @return Process
     */
    private function createProcessMock($pid = 0)
    {
        $processMock = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        
        $processMock
            ->expects($this->once())
            ->method('start');
            
        $processMock
            ->expects($this->exactly(2))
            ->method('isRunning')
            ->will($this->onConsecutiveCalls(true, false));
            
        $processMock
            ->expects($this->once())
            ->method('getExitCode')
            ->will($this->returnValue(0));

        $processMock
            ->expects($this->any())
            ->method('getPid')
            ->will($this->returnValue($pid));
            
        $processMock
            ->expects($this->once())
            ->method('isSuccessful')
            ->will($this->returnValue(true));
            
        return $processMock;
    }
    
    public function testRun()
    {
        // create tunner
        $runner = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ProcessRunner')
            ->setMethods(['createProcess'])
            ->getMock();
            
        $runner
            ->expects($this->once())
            ->method('createProcess')
            ->will($this->returnValue($this->createProcessMock()));

        // run task
        $runner->run(
            'ls -lah',
            'dev',
            OutputInterface::VERBOSITY_VERBOSE,
            $this->createOutput()
        );
    }

    public function testParallelRun()
    {
        $commands = [
            'ls -lah',
            'id'
        ];

        // create tunner
        $runner = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ProcessRunner')
            ->setMethods(['createProcess'])
            ->getMock();

        $runner
            ->expects($this->exactly(2))
            ->method('createProcess')
            ->withConsecutive(
                [$this->equalTo($commands[0])],
                [$this->equalTo($commands[1])]
            )
            ->will($this->onConsecutiveCalls(
                $this->createProcessMock(42),
                $this->createProcessMock(43)
            ));

        // run task
        $runner->parallelRun(
            $commands,
            'dev',
            OutputInterface::VERBOSITY_VERBOSE,
            $this->createOutput()
        );
    }
}
