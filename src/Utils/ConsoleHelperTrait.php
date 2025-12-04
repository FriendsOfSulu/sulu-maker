<?php

namespace FriendsOfSulu\MakerBundle\Utils;

use BackedEnum;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

trait ConsoleHelperTrait
{
    private function askString(ConsoleStyle $io, string $prompt, string $default): string
    {
        /** @var string $result */
        $result = $io->ask($prompt, $default, null);

        return $result;
    }

    /**
     * @template T of BackedEnum $enum
     *
     * @param class-string<T> $enum
     * @param ?T $default
     *
     * @return ($default is null ? T|null : T)
     */
    private function askEnum(ConsoleStyle $io, string $prompt, string $enum, ?\BackedEnum $default): ?\BackedEnum
    {
        Assert::implementsInterface($enum, \BackedEnum::class);
        $options = [];
        if (\method_exists($enum, 'descriptions')) {
            $options = [$enum, 'descriptions']();
        } else {
            foreach ([$enum, 'cases']() as $option) {
                $options[$option->value] = $option->name;
            }
        }

        $question = new ChoiceQuestion($prompt, $options, $default?->value);
        /** @var null|string $valueString */
        $valueString = $io->askQuestion($question);
        if (null === $valueString) {
            return $default;
        }

        return [$enum, 'from']($valueString);
    }

    private static function getStringArgument(InputInterface $input, string $key): string
    {
        $result = $input->getArgument($key);
        Assert::string($result, 'Input option: "' . $key . '" should be a string');

        return $result;
    }

    private static function interactiveEntityArgument(InputInterface $input, string $argumentName, DoctrineHelper $doctrineHelper): void
    {
        if ($input->getArgument($argumentName)) {
            return;
        }

        $entityQuestion = new Question('What entity do you want to generate the admin view for');
        $entityQuestion->setValidator(static function(mixed $value) {
            if (!$value) {
                throw new \InvalidArgumentException('This value cannot be blank.');
            }

            return $value;
        });
        $entityQuestion->setAutocompleterValues($doctrineHelper->getEntitiesForAutocomplete());
        $io = new SymfonyStyle($input, new ConsoleOutput());

        $className = $doctrineHelper->getEntityNamespace() . '\\' . $io->askQuestion($entityQuestion);
        $input->setArgument($argumentName, $className);
    }
}
