<?php

declare(strict_types=1);

namespace App\Shared\Validator;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class RevolvingDoorFactualNoteValidator extends ConstraintValidator
{
    /**
     * @param list<string> $forbiddenWords
     */
    public function __construct(
        #[Autowire(param: 'revolving_door.forbidden_words')]
        private readonly array $forbiddenWords,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RevolvingDoorFactualNote) {
            throw new UnexpectedTypeException($constraint, RevolvingDoorFactualNote::class);
        }

        if (!\is_string($value) || '' === $value) {
            return;
        }

        $normalized = $this->normalize($value);
        foreach ($this->forbiddenWords as $word) {
            $w = trim((string) $word);
            if ('' === $w) {
                continue;
            }
            $nw = $this->normalize($w);
            if ($this->containsWholeWord($normalized, $nw)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ word }}', $w)
                    ->addViolation();

                return;
            }
        }
    }

    private function normalize(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $trans = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);

        return false !== $trans ? $trans : $lower;
    }

    private function containsWholeWord(string $normalizedText, string $normalizedWord): bool
    {
        $pattern = '/\b'.preg_quote($normalizedWord, '/').'\b/u';

        return 1 === preg_match($pattern, $normalizedText);
    }
}
