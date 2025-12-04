<?php

use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

/**
 * @var string $class_name
 * @var string $namespace
 * @var UseStatementGenerator $use_statements
 * @var \FriendsOfSulu\MakerBundle\Maker\MigrationMaker\MigrationFilters $filters
 */
echo "<?php\n";
?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

final class <?= $class_name; ?> extends AbstractMigration
{
    /**
     * Process the template data for a given template key and locale.
     * If the locale is null, this is the base data for all locales and represents the non-translated properties.
     *
     * @param array<string, mixed> $templateData
     * @return array<string, mixed>
     */
    private function process(?string $templateKey, string $stage, ?string $locale, array $templateData): array
    {
        // TODO: Implement your own logic to process the template data.
        return $templateData;
    }
        
    public function up(Schema $schema): void
    {
        // By default only migrate the current version of the page (version 0).
        $whereCondition = <?php \var_export($filters->getWhereCondition()); ?>;
        $params = <?php \var_export($filters->getParams()); ?>;

        $sql = 'SELECT id, templateKey, locale, stage, templateData FROM pa_page_dimension_contents WHERE '.$whereCondition;

        // Foreach result run the process method to get the new template data.
        foreach ($this->connection->executeQuery($sql, $params) as $page) {
            $newTemplateData = $this->process(
                $page['templateKey'],  
                $page['stage'], 
                $page['locale'], 
                json_decode($page['templateData'], associative: true, flags: JSON_THROW_ON_ERROR)
            );

            // Update the template data in the database.
            $this->connection->update('pa_page_dimension_contents', [
                'templateData' => json_encode($newTemplateData, flags: JSON_THROW_ON_ERROR),
            ], [
                'id' => $page['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('Down migrations are not supported.');
    }
}

