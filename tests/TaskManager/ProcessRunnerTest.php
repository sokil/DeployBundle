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
    private function createProcessMock()
    {
        $processMock = $this
            ->getMockBuilder('Symfony\Component\Process\Process')
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
            ->will($this->returnValue(0);
            
        $processMock
            ->expects($this->once())
            ->method('isSuccessful')
            ->will($this->returnValue(true);
            
        return $processMock;
    }
    
    public function testRun()
    {
        
        $runner = $this
            ->getMockBuilder('Sokil\DeployBundle\TaskManager\ProcessRunner')
            ->setMethods(['createProcess'])
            ->getMock();
            
        $runner
            ->expects($this->once())
            ->method('createProcess')
            ->will($this->returnValue($this->createProcessMock()));
            
        $runner->run(
            'ls -lah',
            'dev',
            OutputInterface::VERBOSITY_VERBOSE,
            $this->createOutput()
        );
            
            
    }
}
