<?php

namespace Sokil\DeployBundle\Task\SyncTask;

class SyncCommand
{
    /**
     * Source directory to sync from
     *
     * @var string
     */
    private $source = '.';

    /**
     * Target server and directory to sync to.
     * Example: "user@web1.server.com://var/www/site"
     *
     * @var array
     */
    private $target = [];

    /**
     * @var array
     */
    private $exclude = [];

    /**
     * @var array
     */
    private $include = [];

    /**
     * Delete extraneous files from destination dirs
     * @var bool
     */
    private $deleteExtraneousFiles = true;

    /**
     * @var bool
     */
    private $verbose = true;

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return array
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param array $target
     */
    public function setTarget(array $target)
    {
        $this->target = $target;
    }

    /**
     * @return array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * @param array $exclude
     */
    public function setExclude(array $exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * @return array
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @param array $include
     */
    public function setInclude(array $include)
    {
        $this->include = $include;
    }

    /**
     * @return boolean
     */
    public function isDeleteExtraneousFiles()
    {
        return $this->deleteExtraneousFiles;
    }

    /**
     * @param boolean $deleteExtraneousFiles
     */
    public function setDeleteExtraneousFiles($deleteExtraneousFiles)
    {
        $this->deleteExtraneousFiles = (bool)$deleteExtraneousFiles;
    }

    /**
     * @return boolean
     */
    public function isVerbose()
    {
        return $this->verbose;
    }

    /**
     * @param boolean $verbose
     */
    public function setVerbose($verbose)
    {
        $this->verbose = $verbose;
    }

    /**
     * @return \Generator
     */
    public function getNext()
    {
        $command = ['rsync -a'];

        if ($this->verbose) {
            $command[] = '-v';
        }

        if ($this->deleteExtraneousFiles) {
            $command[] = '--delete';
        }

        if ($this->exclude) {
            foreach ($this->exclude as $path) {
                $command[] = '--exclude ' . $path;
            }
        }

        if ($this->include) {
            foreach ($this->include as $path) {
                $command[] = '--include ' . $path;
            }
        }

        $command[] = $this->source;

        $command = implode(' ', $command);

        foreach ($this->target as $target) {
            yield $command . ' ' . $target;
        }
    }
}