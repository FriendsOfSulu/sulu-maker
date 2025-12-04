<?php

declare(strict_types=1);

namespace FriendsOfSulu\MakerBundle\Maker\MigrationMaker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Webmozart\Assert\Assert;

/** @internal */
final class MakeMigrationCommand extends AbstractMaker
{
    private const ARG_LOCALES = 'locales';
    private const OPT_WEBSPACE = 'webspace';
    private const OPT_TEMPLATE_KEYS = 'templateKeys';
    private const OPT_STAGES = 'stages';

    public function __construct(
        private string $projectDirectory
    ) {
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new Doctrine migration that can migrate page template data';
    }

    public static function getCommandName(): string
    {
        return 'make:sulu:migration';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption(
                self::ARG_LOCALES,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Array of locales that should be migrated (e.g. en fr de). All by default.',
            )
            ->addOption(
                self::OPT_WEBSPACE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter for webspaces (All by default)'
            )
            ->addOption(
                self::OPT_TEMPLATE_KEYS,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Filter for template keys (All by default)',
            )
            ->addOption(
                self::OPT_STAGES,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Filter for stages (Allowed values: live, draft. All by default)',
                suggestedValues: ['live', 'draft'],
            )
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            'Doctrine\Migrations\AbstractMigration',
            'doctrine/doctrine-migrations-bundle'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        /** @var array<string> $locales */
        $locales = $input->getOption(self::ARG_LOCALES);

        /** @var string|null $webspace */
        $webspace = $input->getOption(self::OPT_WEBSPACE);
        /** @var array<string> $templateKeys */
        $templateKeys = $input->getOption(self::OPT_TEMPLATE_KEYS) ?? [];
        /** @var array<string> $stages */
        $stages = $input->getOption(self::OPT_STAGES) ?? [];

        if (null !== $stages && [] !== $stages) {
            $allowedStages = ['live', 'draft'];
            foreach ($stages as $stage) {
                Assert::inArray(
                    $stage,
                    $allowedStages,
                    \sprintf('Stage "%s" is not allowed. Allowed values are: %s', $stage, \implode(', ', $allowedStages))
                );
            }
        }

        $migrationDirectory = $this->projectDirectory . '/migrations';
        if (!\is_dir($migrationDirectory)) {
            \mkdir($migrationDirectory, 0755, true);
        }

        $timestamp = \date('YmdHis');
        $className = 'Version' . $timestamp;

        $migrationPath = $migrationDirectory . '/' . $className . '.php';

        if (\file_exists($migrationPath) && !$io->confirm("Migration file '$migrationPath' already exists. Overwrite it?")) {
            return;
        }

        $useStatements = new UseStatementGenerator([
            'Doctrine\DBAL\Schema\Schema',
            'Doctrine\Migrations\AbstractMigration',
        ]);

        $generator->generateFile(
            $migrationPath,
            __DIR__ . '/migration_template.tpl.php',
            [
                'class_name' => $className,
                'namespace' => 'DoctrineMigrations',
                'use_statements' => $useStatements,
                'filters' => new MigrationFilters(
                    $locales,
                    $webspace,
                    $templateKeys,
                    $stages
                ),
            ]
        );

        $generator->writeChanges();

        $io->success('Migration created successfully at: ' . $migrationPath);
    }
}
