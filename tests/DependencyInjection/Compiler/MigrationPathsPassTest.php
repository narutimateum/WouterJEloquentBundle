<?php

/*
 * This file is part of the WouterJEloquentBundle package.
 *
 * (c) 2014 Wouter de Jong
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WouterJ\EloquentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MigrationPathsPassTest extends \PHPUnit_Framework_TestCase
{
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->container->addCompilerPass(new MigrationPathsPass(), PassConfig::TYPE_OPTIMIZE);

        $this->container->register('wouterj_eloquent.migrator', __CLASS__.'_MigratorStub');
    }

    /** @test */
    public function it_configures_the_extra_migration_paths()
    {
        MigrationPathsPass::add('/package1/migrations');
        MigrationPathsPass::add('/package2/Resources/migrations');

        $this->container->compile();

        $migrator = $this->container->get('wouterj_eloquent.migrator');
        $this->assertEquals(['/package1/migrations', '/package2/Resources/migrations'], $migrator->paths());
    }
}

class MigrationPathsPassTest_MigratorStub
{
    private $paths = [];

    public function path($path)
    {
        $this->paths[] = $path;
    }

    public function paths()
    {
        return $this->paths;
    }
}
