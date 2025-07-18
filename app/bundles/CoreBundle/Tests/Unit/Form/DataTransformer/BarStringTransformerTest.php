<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\DataTransformer;

use Mautic\CoreBundle\Form\DataTransformer\BarStringTransformer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class BarStringTransformerTest extends TestCase
{
    /**
     * @param mixed $value
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('transformProvider')]
    public function testTransform($value, string $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->transform($value));
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public static function transformProvider(): \Generator
    {
        yield [null, ''];
        yield [[], ''];
        yield [123, ''];
        yield [new \stdClass(), ''];
        yield ['', ''];
        yield ['value A', ''];
        yield [['value A'], 'value A'];
        yield [['value A', 'value B'], 'value A|value B'];
    }

    /**
     * @param mixed    $value
     * @param string[] $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('reverseTransformProvider')]
    public function testReverseTransform($value, array $expected): void
    {
        $transformer = new BarStringTransformer();
        Assert::assertSame($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return \Generator<array<mixed>>
     */
    public static function reverseTransformProvider(): \Generator
    {
        yield [null, []];
        yield [[], []];
        yield [123, []];
        yield [new \stdClass(), []];
        yield ['', ['']];
        yield ['value A', ['value A']];
        yield ['value A|value B', ['value A', 'value B']];
        yield ['value A| value B  |  | value C', ['value A', 'value B', '', 'value C']];
    }
}
