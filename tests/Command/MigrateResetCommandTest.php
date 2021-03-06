<?php

/*
 * This file is part of the WouterJEloquentBundle package.
 *
 * (c) 2014 Wouter de Jong
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WouterJ\EloquentBundle\Command;

use Symfony\Component\DependencyInjection\Container;
use WouterJ\EloquentBundle\Promise;
use WouterJ\EloquentBundle\Migrations\Migrator;
use Prophecy\Argument;

/**
 * @author Wouter J <wouter@wouterj.nl>
 */
class MigrateResetCommandTest extends \PHPUnit_Framework_TestCase
{
    private $command;
    /** @var Container */
    private $container;
    private $migrator;

    protected function setUp()
    {
        $this->migrator = $this->prophesize(Migrator::class);
        $this->migrator->getNotes()->willReturn([]);
        $this->migrator->paths()->willReturn([]);
        $this->migrator->setConnection(Argument::any())->willReturn();
        $this->migrator->repositoryExists()->willReturn(true);

        $this->container = new Container();
        $this->container->setParameter('kernel.environment', 'dev');
        $this->container->set('wouterj_eloquent.migrator', $this->migrator->reveal());
        $this->container->setParameter('wouterj_eloquent.migration_path', __DIR__.'/migrations');

        $this->command = new MigrateResetCommand();
        $this->command->setContainer($this->container);
    }

    /** @test */
    public function it_asks_for_confirmation_in_prod()
    {
        $this->container->setParameter('kernel.environment', 'prod');

        $this->migrator->reset(Argument::cetera())->shouldNotBeCalled();

        TestCommand::create($this->command)
            ->answering("no")
            ->duringExecute()
            ->outputs('Are you sure you want to execute the migrations in production?');
    }

    /** @test */
    public function it_does_not_ask_for_confirmation_in_dev()
    {
        $this->migrator->reset(Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)
            ->execute()
            ->doesNotOutput('Are you sure you want to execute the migrations in production?');
    }

    /** @test */
    public function it_always_continues_when_force_is_passed()
    {
        $this->container->setParameter('kernel.environment', 'prod');

        $this->migrator->reset(Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)
            ->passing('--force')
            ->duringExecute()
            ->doesNotOutput('Are you sure you want to execute the migrations in production?');
    }

    /** @test */
    public function it_uses_the_default_migration_path()
    {
        $this->migrator->reset([__DIR__.'/migrations'], Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)->execute();
    }

    /** @test */
    public function it_allows_to_specify_another_path()
    {
        $this->migrator->reset([getcwd().'/db'], Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)->passing('--path', 'db')->duringExecute();
    }

    /** @test */
    public function it_allows_multiple_migration_directories()
    {
        $this->migrator->paths()->willReturn(['/somewhere/migrations']);

        $this->migrator->reset([__DIR__.'/migrations', '/somewhere/migrations'], Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)->execute();
    }

    /** @test */
    public function it_allows_changing_the_connection()
    {
        $this->migrator->setConnection('something')->shouldBeCalled();

        $this->migrator->reset(Argument::any(), false)->shouldBeCalled();

        TestCommand::create($this->command)->passing('--database', 'something')->duringExecute();
    }

    /** @test */
    public function it_can_pretend_migrations_were_resetted()
    {
        $this->migrator->reset(Argument::any(), true)->shouldBeCalled();

        TestCommand::create($this->command)->passing('--pretend')->duringExecute();
    }

    /** @test */
    public function it_outputs_migration_notes()
    {
        $this->migrator->getNotes()->willReturn([
            'Rolled back: CreateFlightsTable',
            'Rolled back: SomethingToTest',
        ]);

        $this->migrator->reset(Argument::cetera())->shouldBeCalled();

        TestCommand::create($this->command)
            ->execute()
            ->outputs("Rolled back: CreateFlightsTable\nRolled back: SomethingToTest");
    }

    /** @test */
    public function it_stops_when_repository_does_not_exists()
    {
        $this->migrator->repositoryExists()->willReturn(false);

        $this->migrator->reset(Argument::cetera())->shouldNotBeCalled();

        TestCommand::create($this->command)
            ->execute()
            ->outputs('Migration table not found.')
            ->exitsWith(1);
    }
}
