<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Exception\ColumnAlreadyCreatedException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Mautic\LeadBundle\Field\LeadFieldDeleter;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;

class BackgroundServiceTest extends \PHPUnit\Framework\TestCase
{
    private BackgroundService $backgroundService;

    /**
     * @var MockObject&FieldModel
     */
    private MockObject $fieldModel;

    /**
     * @var MockObject&CustomFieldColumn
     */
    private MockObject $customFieldColumn;

    /**
     * @var MockObject&LeadFieldSaver
     */
    private MockObject $leadFieldSaver;

    /**
     * @var MockObject&LeadFieldDeleter
     */
    private $leadFieldDeleter;

    /**
     * @var MockObject&FieldColumnBackgroundJobDispatcher
     */
    private MockObject $fieldColumnBackgroundJobDispatcher;

    /**
     * @var MockObject&CustomFieldNotification
     */
    private MockObject $customFieldNotification;

    public function setUp(): void
    {
        $this->fieldModel                         = $this->createMock(FieldModel::class);
        $this->customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $this->leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $this->leadFieldDeleter                   = $this->createMock(LeadFieldDeleter::class);
        $this->fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $this->customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $this->backgroundService = new BackgroundService(
            $this->fieldModel,
            $this->customFieldColumn,
            $this->leadFieldSaver,
            $this->leadFieldDeleter,
            $this->fieldColumnBackgroundJobDispatcher,
            $this->customFieldNotification
        );
    }

    public function testNoLeadField(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->expectException(LeadFieldWasNotFoundException::class);
        $this->expectExceptionMessage('LeadField entity was not found');

        $this->backgroundService->addColumn(1, 3);
    }

    public function testColumnAlreadyCreated(): void
    {
        $leadField = new LeadField();
        $leadField->setColumnWasCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $this->expectException(ColumnAlreadyCreatedException::class);
        $this->expectExceptionMessage('Column was already created');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testAbortColumnCreate(): void
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnCreateException('Message'));

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Message');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testAbortColumnUpdate(): void
    {
        $leadField = new LeadField();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreUpdateColumnEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnUpdateException('Message'));

        $this->expectException(AbortColumnUpdateException::class);
        $this->expectExceptionMessage('Message');

        $this->backgroundService->updateColumn(1, $userId);
    }

    public function testCustomFieldLimit(): void
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false)
            ->willThrowException(new CustomFieldLimitException('Limit'));

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldLimitWasHit')
            ->with($leadField, $userId);

        $this->expectException(CustomFieldLimitException::class);
        $this->expectExceptionMessage('Limit');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testCreateColumnWithNoError(): void
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false);

        $this->leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, false);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $this->backgroundService->addColumn(1, $userId);

        $this->assertFalse($leadField->getColumnIsNotCreated());
    }

    public function testUpdateColumnWithNoError(): void
    {
        $leadField = new LeadField();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreUpdateColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processUpdateLeadColumn')
            ->with($leadField);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasUpdated')
            ->with($leadField, $userId);

        $this->backgroundService->updateColumn(1, $userId);
    }

    public function testUpdatingLeadFieldWithNotFoundException(): void
    {
        $this->expectException(LeadFieldWasNotFoundException::class);
        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);
        $this->backgroundService->updateColumn(-1, 0);
    }

    public function testUpdateColumnThrowingExceptions(): void
    {
        $leadField = new LeadField();
        $leadField->setCharLengthLimit(200);
        $leadField->setId(9999);
        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processUpdateLeadColumnLength')
            ->with($leadField)
            ->willThrowException(new \OutOfRangeException());

        $this->expectException(\OutOfRangeException::class);
        $this->backgroundService->updateColumn($leadField->getId(), 1);
    }

    public function testDeleteColumnWithNoError(): void
    {
        $leadField = new LeadField();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreDeleteColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processDeleteLeadColumn')
            ->with($leadField);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasDeleted')
            ->with($leadField, $userId);

        $this->backgroundService->deleteColumn(1, $userId);
    }
}
