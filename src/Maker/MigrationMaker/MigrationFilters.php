<?php

declare(strict_types=1);

namespace FriendsOfSulu\MakerBundle\Maker\MigrationMaker;

final class MigrationFilters
{
    public function __construct(
        /** @var array<string> $locales */
        public array $locales,
        /** @var string|null $webspace */
        public ?string $webspace,
        /** @var array<string> $templateKeys */
        public array $templateKeys,
        /** @var array<string> $stages */
        public array $stages,
    ) {
    }

    public function getWhereCondition(): string
    {
        $whereCondition = '(version = 0)';
        if (null !== $this->webspace) {
            $whereCondition .= ' (AND webspace = :webspace)';
        }
        if ([] !== $this->locales) {
            $whereCondition .= ' (AND locale IN (:locales))';
        }
        if ([] !== $this->templateKeys) {
            $whereCondition .= ' (AND templateKey IN (:templateKeys))';
        }
        if ([] !== $this->stages) {
            $whereCondition .= ' (AND stage IN (:stages))';
        }

        return $whereCondition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        $params = [];
        if (null !== $this->webspace) {
            $params['webspace'] = $this->webspace;
        }
        if ([] !== $this->locales) {
            $params['locales'] = $this->locales;
        }
        if ([] !== $this->templateKeys) {
            $params['templateKeys'] = $this->templateKeys;
        }
        if ([] !== $this->stages) {
            $params['stages'] = $this->stages;
        }

        return $params;
    }
}
