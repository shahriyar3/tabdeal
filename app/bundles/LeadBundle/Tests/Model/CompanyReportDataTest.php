<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(CompanyReportData::class)]
class CompanyReportDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TranslatorInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);

        $this->translator->method('trans')
            ->willReturnCallback(
                fn ($key) => $key
            );
    }

    public function testGetCompanyData(): void
    {
        $fieldModelMock = $this->createMock(FieldModel::class);

        $field1 = new Field();
        $field1->setType('boolean');
        $field1->setAlias('boolField');
        $field1->setLabel('boolFieldLabel');

        $field2 = new Field();
        $field2->setType('email');
        $field2->setAlias('emailField');
        $field2->setLabel('emailFieldLabel');

        $fields = [
            $field1,
            $field2,
        ];

        $fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn($fields);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->getCompanyData();

        $expected = [
            'comp.id' => [
                'alias' => 'comp_id',
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'companies_lead.is_primary' => [
                'label' => 'mautic.lead.report.company.is_primary',
                'type'  => 'bool',
            ],
            'companies_lead.date_added' => [
                'label' => 'mautic.lead.report.company.date_added',
                'type'  => 'datetime',
            ],
            'comp.boolField' => [
                'label' => 'mautic.report.field.company.label',
                'type'  => 'bool',
            ],
            'comp.emailField' => [
                'label' => 'mautic.report.field.company.label',
                'type'  => 'email',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testEventHasCompanyColumns(): void
    {
        $fieldModelMock = $this->createMock(FieldModel::class);

        $eventMock = $this->createMock(ReportGeneratorEvent::class);

        $field = new Field();
        $field->setType('email');
        $field->setAlias('email');
        $field->setLabel('Email');

        $fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([$field]);

        $eventMock->expects($this->once())
            ->method('hasColumn')
            ->with('comp.id')
            ->willReturn(true);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->eventHasCompanyColumns($eventMock);

        $this->assertTrue($result);
    }

    public function testEventDoesNotHaveCompanyColumns(): void
    {
        $fieldModelMock = $this->createMock(FieldModel::class);

        $eventMock = $this->createMock(ReportGeneratorEvent::class);

        $field = new Field();
        $field->setType('email');
        $field->setAlias('email');
        $field->setLabel('Email');

        $fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([$field]);

        $eventMock->expects($this->any())
            ->method('hasColumn')
            ->willReturn(false);

        $companyReportData = new CompanyReportData($fieldModelMock, $this->translator);

        $result = $companyReportData->eventHasCompanyColumns($eventMock);

        $this->assertFalse($result);
    }
}
