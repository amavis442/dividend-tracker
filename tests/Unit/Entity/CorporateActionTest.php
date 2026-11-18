<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\CorporateAction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CorporateActionTest extends TestCase
{
    #[TestWith([0.2, 0])]
    #[TestWith([0.0, 1])]
    #[TestWith([-1.0, 1])]
    public function testTheRatioMustBePositive(float $ratio, int $violations): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $action = new CorporateAction();
        $action->setRatio($ratio);

        self::assertCount($violations, $validator->validateProperty($action, 'ratio'));
    }
}
