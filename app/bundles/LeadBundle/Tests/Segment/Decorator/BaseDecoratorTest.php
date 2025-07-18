<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;

#[\PHPUnit\Framework\Attributes\CoversClass(BaseDecorator::class)]
class BaseDecoratorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetField(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'glue'     => 'and',
            'field'    => 'date_identified',
            'object'   => 'lead',
            'type'     => 'datetime',
            'filter'   => null,
            'display'  => null,
            'operator' => '!empty',
        ]);

        $this->assertSame('date_identified', $baseDecorator->getField($contactSegmentFilterCrate));
    }

    public function testGetTableLead(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'object'   => 'lead',
        ]);

        $this->assertSame(MAUTIC_TABLE_PREFIX.'leads', $baseDecorator->getTable($contactSegmentFilterCrate));
    }

    public function testGetTableCompany(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'object'   => 'company',
        ]);

        $this->assertSame(MAUTIC_TABLE_PREFIX.'companies', $baseDecorator->getTable($contactSegmentFilterCrate));
    }

    public function testGetOperatorEqual(): void
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('=')
            ->willReturn('eq');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => '=',
        ]);

        $this->assertSame('eq', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    public function testGetOperatorStartsWith(): void
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('startsWith')
            ->willReturn('startsWith');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'startsWith',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    public function testGetOperatorEndsWith(): void
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('endsWith')
            ->willReturn('endsWith');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'endsWith',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    public function testGetOperatorContainsWith(): void
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);
        $contactSegmentFilterOperator->expects($this->once())
            ->method('fixOperator')
            ->with('contains')
            ->willReturn('contains');

        $baseDecorator = new BaseDecorator($contactSegmentFilterOperator);

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'operator' => 'contains',
        ]);

        $this->assertSame('like', $baseDecorator->getOperator($contactSegmentFilterCrate));
    }

    public function testGetQueryType(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $this->assertSame('mautic.lead.query.builder.basic', $baseDecorator->getQueryType($contactSegmentFilterCrate));
    }

    public function testGetParameterHolderSingle(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $this->assertSame(':argument', $baseDecorator->getParameterHolder($contactSegmentFilterCrate, 'argument'));
    }

    public function testGetParameterHolderArray(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([]);

        $argument = [
            'argument1',
            'argument2',
            'argument3',
        ];

        $expected = [
            ':argument1',
            ':argument2',
            ':argument3',
        ];
        $this->assertSame($expected, $baseDecorator->getParameterHolder($contactSegmentFilterCrate, $argument));
    }

    public function testGetParameterValueBoolean(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'boolean',
            'filter' => '1',
        ]);

        $this->assertTrue($baseDecorator->getParameterValue($contactSegmentFilterCrate));

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'boolean',
            'filter' => '0',
        ]);

        $this->assertFalse($baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueNumber(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'   => 'number',
            'filter' => '1',
        ]);

        $this->assertSame(1.0, $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueLikeNoPercent(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueNotLike(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => '!like',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueLikeWithOnePercent(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => '%Test string',
        ]);

        $this->assertSame('%Test string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueLikeWithTwoPercent(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'like',
            'filter'   => '%Test string%',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueStartsWith(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'startsWith',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueEndsWith(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'endsWith',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueContains(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'contains',
            'filter'   => 'Test string',
        ]);

        $this->assertSame('%Test string%', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueContainsShouldNotBeEscaped(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'contains',
            'filter'   => 'Test with % and special characters \% should not be escaped %',
        ]);

        $expected = '%Test with % and special characters \% should not be escaped %%';
        $this->assertSame($expected, $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueRegex(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => 'regexp',
            'filter'   => 'Test \\\s string',
        ]);

        $this->assertSame('Test \s string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueNotRegex(): void
    {
        $baseDecorator = $this->getDecorator();

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'string',
            'operator' => '!regexp',
            'filter'   => 'Test \\\s string',
        ]);

        $this->assertSame('Test \s string', $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    public function testGetParameterValueMultiselect(): void
    {
        $baseDecorator = $this->getDecorator();

        $expected = [
            '(([|]|^)2([|]|$))',
            '(([|]|^)4([|]|$))',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'multiselect',
            'filter'   => [2, 4],
            'operator' => 'in',
        ]);

        $this->assertSame($expected, $baseDecorator->getParameterValue($contactSegmentFilterCrate));

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'multiselect',
            'filter'   => [2, 4],
            'operator' => '!in',
        ]);

        $this->assertSame($expected, $baseDecorator->getParameterValue($contactSegmentFilterCrate));

        $expected = [
            '(([|]|^)Value \(1\)([|]|$))',
            '(([|]|^)Value 2([|]|$))',
        ];

        $contactSegmentFilterCrate = new ContactSegmentFilterCrate([
            'type'     => 'multiselect',
            'filter'   => ['Value (1)', 'Value 2'],
            'operator' => 'in',
        ]);

        $this->assertSame($expected, $baseDecorator->getParameterValue($contactSegmentFilterCrate));
    }

    /**
     * @return BaseDecorator
     */
    private function getDecorator()
    {
        $contactSegmentFilterOperator = $this->createMock(ContactSegmentFilterOperator::class);

        return new BaseDecorator($contactSegmentFilterOperator);
    }
}
