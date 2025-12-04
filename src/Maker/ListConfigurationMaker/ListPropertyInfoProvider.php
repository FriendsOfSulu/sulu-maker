<?php

namespace FriendsOfSulu\MakerBundle\Maker\ListConfigurationMaker;

use Doctrine\Persistence\Mapping\ClassMetadata;
use FriendsOfSulu\MakerBundle\Enums\Visibility;
use FriendsOfSulu\MakerBundle\Property\PropertyToSuluTypeGuesser;
use FriendsOfSulu\MakerBundle\Property\PropertyToSuluTypeGuesserInterface;
use FriendsOfSulu\MakerBundle\Utils\ConsoleHelperTrait;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Webmozart\Assert\Assert;

/** @internal */
class ListPropertyInfoProvider
{
    use ConsoleHelperTrait;

    public function __construct(
        /* readonly */ private PropertyToSuluTypeGuesserInterface $typeGuesser
    ) {
    }

    private ?ConsoleStyle $io = null;

    public function setIo(ConsoleStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * @return array{properties: array<ListPropertyInfo>, joins: array<ListJoinInfo>}
     */
    public function provide(ClassMetadata $reflectionClass, bool $assumeDefaults): array
    {
        $properties = [];

        foreach ($reflectionClass->fieldMappings as $name => $mapping) {
            if (null !== ($property = $this->provideProperty($name, $mapping, $assumeDefaults))) {
                $properties[] = $property;
            }
        }

        $joins = [];
        foreach ($reflectionClass->associationMappings as $mapping) {
            if (null !== ($join = $this->provideJoin($mapping, $assumeDefaults))) {
                $joins[] = $join;
            }
        }

        return ['properties' => $properties, 'joins' => $joins];
    }

    /**
     * @param array{id?: true, type?: string} $mapping
     */
    protected function provideProperty(string $name, array $mapping, bool $assumeDefaults): ?ListPropertyInfo
    {
        Assert::notNull($this->io, 'No io set. Please call ' . self::class . '::setIo() before');

        // If it's a primary identifier (like id) we don't want to show that.
        if ($mapping['id'] ?? false) {
            return new ListPropertyInfo($name, Visibility::NO, false, 'sulu_admin.' . $name);
        }

        $this->io->info(\sprintf('Configuring property: "%s"', $name));
        if (!$assumeDefaults && !$this->io->confirm(\sprintf('Should this property "%s" be configured', $name))) {
            $this->io->info(\sprintf('Property "%s" skipped', $name));

            return null;
        }

        /** @var Visibility $visibility */
        $visibility = $this->askEnum($this->io, 'Visible?', Visibility::class, Visibility::YES);

        $searchable = false;
        if ($visibility->isVisible()) {
            $searchable = $assumeDefaults || $this->io->confirm('Searchable?');
        }

        $type = $this->getType($mapping['type'] ?? 'string');

        if ($assumeDefaults) {
            $translation = 'sulu_admin.' . $name;
        } else {
            $translation = $this->askString($this->io, 'Translation', 'sulu_admin.' . $name);
        }

        return new ListPropertyInfo($name, $visibility, $searchable, $translation, $type);
    }

    /**
     * @param array{fieldName: string, sourceEntity: string} $mapping
     */
    protected function provideJoin(array $mapping, bool $assumeDefaults): ?ListJoinInfo
    {
        Assert::notNull($this->io, 'No io set. Please call ' . self::class . '::setIo() before');

        $name = $mapping['fieldName'];
        if (!$this->io->confirm(\sprintf('Should this association "%s" be configured', $name))) {
            $this->io->info(\sprintf('Association "%s" skipped', $name));

            return null;
        }

        $joinType = JoinType::INNER;
        if (!$assumeDefaults) {
            /** @var JoinType $joinType */
            $joinType = $this->askEnum($this->io, 'What type of join should be used', JoinType::class, JoinType::INNER);
        }

        $condition = $this->askString($this->io, 'Additional condition (leave empty for none)', '');
        if ('' === $condition) {
            $condition = null;
            $conditionType = null;
        } else {
            $conditionType = $this->askEnum($this->io, 'What type of condition should be used', ConditionType::class, ConditionType::ON);
        }

        return new ListJoinInfo(
            $name,
            $mapping['sourceEntity'] . '.' . $name,
            $joinType,
            $condition,
            $conditionType,
        );
    }

    private function getType(string $doctrineType): ?string
    {
        Assert::notNull($this->io, 'No io set. Please call ' . self::class . '::setIo() before');

        $possibleTypes = $this->typeGuesser->getPossibleTypes($doctrineType);
        if ([] === $possibleTypes) {
            $this->io->note('Could not find any suggestions for the PHP Type of the property. You can extend the class ' . PropertyToSuluTypeGuesser::class . ' for smarter type guessing.');

            return null;
        }

        if (1 === \count($possibleTypes)) {
            $keys = \array_keys($possibleTypes);
            $type = \reset($keys);
            $description = \reset($possibleTypes);
            $this->io->info(\sprintf('Choosing the only possible type: %s (%s)', $type ?: 'string', $description));

            return $type;
        }

        /** @var string|null $type */
        $type = $this->io->choice('Sulu display type', $possibleTypes);

        if (null === $type) {
            $keys = \array_keys($possibleTypes);
            $type = \reset($keys);
            $description = \reset($possibleTypes);
            $this->io->info(\sprintf('Choosing the best guess: %s (%s)', $type ?: 'string', $description));
        }

        return $type;
    }
}
