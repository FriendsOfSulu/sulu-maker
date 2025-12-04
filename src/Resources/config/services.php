<?php

use FriendsOfSulu\MakerBundle\Maker\AdminConfigurationMaker\MakeAdminConfigurationCommand;
use FriendsOfSulu\MakerBundle\Maker\ControllerMaker\MakeControllerCommand;
use FriendsOfSulu\MakerBundle\Maker\DocumentFixtureMaker\MakeDocumentFixtureCommand;
use FriendsOfSulu\MakerBundle\Maker\ListConfigurationMaker\ListPropertyInfoProvider;
use FriendsOfSulu\MakerBundle\Maker\ListConfigurationMaker\MakeListConfigurationCommand;
use FriendsOfSulu\MakerBundle\Maker\MigrationMaker\MakeMigrationCommand;
use FriendsOfSulu\MakerBundle\Maker\PreviewMaker\MakePreviewCommand;
use FriendsOfSulu\MakerBundle\Maker\SuluPageMaker\MakePageTypeCommand;
use FriendsOfSulu\MakerBundle\Maker\TashHandlerMaker\MakeTrashHandlerCommand;
use FriendsOfSulu\MakerBundle\Maker\WebspaceConfigMaker\MakeWebspaceConfigCommand;
use FriendsOfSulu\MakerBundle\Property\PropertyToSuluTypeGuesser;
use FriendsOfSulu\MakerBundle\Utils\NameGenerators\ResourceKeyExtractor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function(ContainerConfigurator $configurator) {
    $services = $configurator->services();

    // Maker commands
    $services
        ->set(MakeListConfigurationCommand::class)
        ->args([
            '%kernel.project_dir%',
            service('maker.doctrine_helper'),
            service(ListPropertyInfoProvider::class),
            service(ResourceKeyExtractor::class),
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakePageTypeCommand::class)
        ->args([
            '%kernel.project_dir%',
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakeAdminConfigurationCommand::class)
        ->args([
            service(ResourceKeyExtractor::class),
            service('maker.doctrine_helper'),
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakeControllerCommand::class)
        ->args([
            '%kernel.project_dir%',
            service(ResourceKeyExtractor::class),
            service('maker.doctrine_helper'),
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakeDocumentFixtureCommand::class)
        ->tag('maker.command')
    ;

    $services->set(MakeTrashHandlerCommand::class)
        ->args([service('maker.doctrine_helper')])
        ->tag('maker.command')
    ;

    $services->set(MakePreviewCommand::class)
        ->args([
            service(ResourceKeyExtractor::class),
            service('maker.doctrine_helper'),
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakeWebspaceConfigCommand::class)
        ->args([
            '%kernel.project_dir%',
        ])
        ->tag('maker.command')
    ;

    $services
        ->set(MakeMigrationCommand::class)
        ->args([
            '%kernel.project_dir%',
        ])
        ->tag('maker.command')
    ;

    // Other services
    $services->set(ListPropertyInfoProvider::class)
        ->args([
            service(PropertyToSuluTypeGuesser::class),
        ]);

    $services->set(PropertyToSuluTypeGuesser::class);
    $services->set(ResourceKeyExtractor::class);
};
