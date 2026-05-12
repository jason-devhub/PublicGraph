<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Validator\RevolvingDoorFactualNote;
use App\Shared\Validator\RevolvingDoorFactualNoteValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class RevolvingDoorFactualNoteValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RevolvingDoorFactualNoteValidator
    {
        return new RevolvingDoorFactualNoteValidator([
            'corrompu',
            'corruption',
            'corrupt',
            'bribed',
            'bought',
            'traitor',
        ]);
    }

    public function testEmbaucheOk(): void
    {
        $this->validator->validate('il a été embauché par', new RevolvingDoorFactualNote());
        $this->assertNoViolation();
    }

    public function testCorruptionFails(): void
    {
        $c = new RevolvingDoorFactualNote();
        $this->validator->validate('la corruption est évoquée', $c);
        $this->buildViolation($c->message)
            ->setParameter('{{ word }}', 'corruption')
            ->assertRaised();
    }

    public function testCorrosifOk(): void
    {
        $this->validator->validate('un caractère corrosif', new RevolvingDoorFactualNote());
        $this->assertNoViolation();
    }

    public function testEmptyOk(): void
    {
        $this->validator->validate('', new RevolvingDoorFactualNote());
        $this->assertNoViolation();
    }
}
