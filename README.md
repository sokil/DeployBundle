DeployBundle
============

Task runner for Symfony project

[![Latest Stable Version](https://poser.pugx.org/sokil/deploy-bundle/v/stable.png)](https://packagist.org/packages/sokil/deploy-bundle)
[![Total Downloads](http://img.shields.io/packagist/dt/sokil/deploy-bundle.svg)](https://packagist.org/packages/sokil/deploy-bundle)
[![Build Status](https://travis-ci.org/sokil/DeployBundle.png?branch=master&1)](https://travis-ci.org/sokil/DeployBundle)
[![Coverage Status](https://coveralls.io/repos/github/sokil/DeployBundle/badge.svg?branch=master)](https://coveralls.io/github/sokil/DeployBundle?branch=master)

* [Installation](#installation)
* [Configuration](#configuration)
* [Tasks](#tasks)
  * [Git](#git)
    * [Configuring git task](#configuring-git-task)
    * [Private repositories](#private-repositories)
  * [Npm](#npm)
  * [Bower](#bower)
  * [Grunt](#grunt)
  * [Migrations](#migrations)
  * [Composer](#composer)
  * [Clear cache](#clear-cache)
  * [Writting own tasks](#writting-own-tasks)


# Installation

Add Composer dependency:
```
composer.phar require sokil/deploy-bundle
```

Add bundle to your `AppKernel`:

```php
<?php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Sokil\DeployBundle\DeployBundle(),
        );
    }
}
```

# Configuration

Configure tasks required to run in your app in `app/config/config.yml`:

```yaml
deploy:
  config:
    git: {}
    composer: {}
    npm: {}
    bower: {}
    grunt: {}
    asseticDump: {}
    assetsInstall: {}
  tasks:
    updateBack: [git, composer]
    updateFront: [npm, bower]
    compileAssets: [grunt, asseticDump, assetsInstall]
    release: [updateBack, updateFront, compileAssets]
```

Section `config` declared options of every task, able to run. Section `tasks` declered bundles of tasks, runs sequentially.
Tasks may be run by defining task aliases in cli command:

```
$ ./app/console deploy --git --npm
```

Also tasks bundles may be defined:
```
$ ./app/console deploy --up
```

If no task specified then `default` task bundle will be run. This task bundle may be defined in configuration, but if it omitted, then default task consists of all tasks in order of `config` section.
Tasks and task bundles both may be specified in cli options, then tasks will be run in order of first occurrence.
Task bundle also may contain other bundles.


# Tasks

* [Git](#git)
* [Npm](#npm)
* [Bower](#bower)
* [Grunt](#grunt)
* [Migrations](#migrations)
* [Composer](#composer)
* [Writing own tasks](#writting-own-tasks)

## Git

### Configuring git task

Add configuration to your `./app/config/config.yml`:

```yaml
deploy:
  config:
    git:
        defaultRemote: origin       # Optional. Default: origin. Set default remote for all repos
        defaultBranch: master       # Optional. Default: master. Set default branch for all repos
        repos:                      # List of repos
          core:                     # Alias of repo
            path: /var/www/project  # Path to repo
            remote: origin          # Optional. Default: origin. Set remote for this repo
            branch: master          # Optional. Default: master. Set branch for this repo
            tag: false              # Tag release after pull
```

### Private repositories

If repository is private, password will be asked on pull:

```
Permission denied (publickey).
fatal: Could not read from remote repository.

Please make sure you have the correct access rights
and the repository exists.
```

For example web server started under www-data user. To prevent asking password, 
add ssh keys to `/home/$USER/.ssh` directory, using ssh key generation tool. 

1) Generate keys: 

```
$ sudo -u www-data ssh-keygen -t rsa
Generating public/private rsa key pair.
Enter file in which to save the key (/home/www-data/.ssh/id_rsa): 
Enter passphrase (empty for no passphrase): 
Enter same passphrase again: 
Your identification has been saved in /home/www-data/.ssh/id_rsa.
Your public key has been saved in /home/www-data/.ssh/id_rsa.pub.
The key fingerprint is:
...
```

2) Add public key to your repository to fetch changes without password prompt.

3) Test your connection:
```
$ sudo -H -u www-data git pull origin master
```

Find out who use this key already:
```
ssh -T git@github.com
ssh -T git@bitbucket.com
```

## Npm

```yaml
deploy:
  config:
    npm:
      bundles:
        SomeBundle: true
        SomeOtherBundle: true
```
## Bower

```yaml
deploy:
  config:
    bower:
      bundles:
        SomeBundle: true
        SomeOtherBundle: true
```

## Grunt

Add task configuration to your deploy config:

```yaml
deploy:
  config:
    grunt:
      bundles: # bundles where grunt need to be run
        SomeBundle: true
        SomeOtherBundle: true
      parallel: true # run grunts from all bundles in parallel. Default: false
```

Grunt may have few tasks, and you can select which tasks to run.

In config:


```yaml
deploy:
  config:
    grunt:
      bundles:
        SomeBundle:
          tasks:
            - newer:less 
            - newer:jade
        SomeOtherBundle: true
      parallel: true
```

As CLI parameter:
```
./app/console deploy --grunt --grunt-tasks="SomeBundle=newer:less,newer:jade&SomeOtherBundle"
```

## Migrations

Add dependency:
```
composer.phar require doctrine/migrations
composer.phar require doctrine/doctrine-migrations-bundle
```

Register bundler in `AppKerner`:
```php
new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
```

First, configure migrations in `./app/config/config.yml`:

```yaml
doctrine_migrations:
    dir_name: %kernel.root_dir%/migrations
    namespace: Migrations
    table_name: migrations
    name: Application Migrations
```

Then add task to deploy config in `./app/config/config.yml`:

```yaml
deploy:
  config:
    migrate: {}
```

## Composer

This task will run composer command:
```
composer.phar install --optimize-autoloader --no-interaction
```

File `composer.phar` must be in path, or you can redefine path to composer by param `path`.

Add task config to `./app/config/config.yml`:

```yaml
deploy:
  config:
    composer:
      scripts: true # set true if you want to execute scripts. Default: true
      update: true # Optional. If passed, update dependencied instead on install
      path: composer.phar # Optiona. Default: composer.phar, specify path to composer
```

## Clear cache

```yaml
deploy:
  config:
    clearCache: {}
```

## Writting own tasks

First, create task class which extends `Sokil\DeployBundle\Task\AbstractTask`. Then add Symfony's service definition:

```yaml
acme.deploy.my_task:
  class: Acme\Deploy\Task\MyTask
  abstract: true
  public: false
  tags:
    - {name: "deploy.task", alias: "myTask"}
```

This service must contain tag with name `deploy.task` and alias, which will be used as CLI command's option name and configuration section name.

Then, you may add it to bundle's configuration in `app/config/config.yml` to `deploy` section in proper place of order, if you want it to be run automatically:

```yaml
deploy:
  config:
    git: {}
    grunt: {}
    myTask: {}
```

Now, your task will be calld third after `git` and `grunt` by calling `deploy` command without arguments:
```
$ ./app/console deploy --env=prod
```

You also may call your task directly from console:

```
$ ./app/console deploy --myTask
```
