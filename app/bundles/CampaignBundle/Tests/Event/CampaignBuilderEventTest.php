<?php

namespace Mautic\CampaignBundle\Tests\Event;

use Mautic\AssetBundle\Form\Type\PointActionAssetDownloadType;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType;

class CampaignBuilderEventTest extends CampaignTestAbstract
{
    public function testAddGetDecision(): void
    {
        $decisionKey = 'email.open';
        $decision    = [
            'label'                  => 'mautic.email.campaign.event.open',
            'description'            => 'mautic.email.campaign.event.open_descr',
            'eventName'              => 'mautic.email.on_campaign_trigger_decision',
            'connectionRestrictions' => [
                'source' => [
                    'action' => [
                        'email.send',
                    ],
                ],
            ],
        ];
        $event = $this->initEvent();
        $event->addDecision(
            $decisionKey,
            $decision
        );

        $decisions = $event->getDecisions();
        $this->assertSame([$decisionKey => $decision], $decisions);
    }

    public function testEventDecisionSort(): void
    {
        $decision = [
            'label'                  => 'mautic.email.campaign.event.open',
            'description'            => 'mautic.email.campaign.event.open_descr',
            'eventName'              => 'mautic.email.on_campaign_trigger_decision',
            'connectionRestrictions' => [
                'source' => [
                    'action' => [
                        'email.send',
                    ],
                ],
            ],
        ];
        $event = $this->initEvent();

        // add 3 unsorted decisions
        $event->addDecision('email.open1', $decision);
        $decision['label'] = 'mautic.email.campaign.event.open.3';
        $event->addDecision('email.open3', $decision);
        $decision['label'] = 'mautic.email.campaign.event.open.2';
        $event->addDecision('email.open2', $decision);

        $decisions = $event->getDecisions();

        $this->assertCount(3, $decisions);

        $shouldBe = 1;
        foreach ($decisions as $key => $resultDecision) {
            $this->assertSame('email.open'.$shouldBe, $key);
            ++$shouldBe;
        }
    }

    public function testEventConditionSort(): void
    {
        $condition = [
            'label'       => 'mautic.form.campaign.event.field_value',
            'description' => 'mautic.form.campaign.event.field_value_descr',
            'formType'    => CampaignEventFormFieldValueType::class,
            'formTheme'   => '@MauticForm/FormTheme/FieldValueCondition/_campaignevent_form_field_value_widget.html.twig',
            'eventName'   => 'mautic.form.on_campaign_trigger_condition',
        ];
        $event = $this->initEvent();

        // add 3 unsorted conditions
        $event->addCondition('form.field_value1', $condition);
        $condition['label'] = 'mautic.form.campaign.event.field_value.3';
        $event->addCondition('form.field_value3', $condition);
        $condition['label'] = 'mautic.form.campaign.event.field_value.2';
        $event->addCondition('form.field_value2', $condition);

        $conditions = $event->getConditions();

        $this->assertCount(3, $conditions);

        $shouldBe = 1;
        foreach ($conditions as $key => $resultCondition) {
            $this->assertSame('form.field_value'.$shouldBe, $key);
            ++$shouldBe;
        }
    }

    public function testEventActionSort(): void
    {
        $action = [
            'group'       => 'mautic.asset.actions',
            'label'       => 'mautic.asset.point.action.download',
            'description' => 'mautic.asset.point.action.download_descr',
            'callback'    => [\Mautic\AssetBundle\Helper\PointActionHelper::class, 'validateAssetDownload'],
            'formType'    => PointActionAssetDownloadType::class,
        ];
        $event = $this->initEvent();

        // add 3 unsorted actions
        $event->addAction('asset.download1', $action);
        $action['label'] = 'mautic.asset.point.action.download.3';
        $event->addAction('asset.download3', $action);
        $action['label'] = 'mautic.asset.point.action.download.2';
        $event->addAction('asset.download2', $action);

        $actions = $event->getActions();

        $this->assertCount(3, $actions);

        $shouldBe = 1;
        foreach ($actions as $key => $resultAction) {
            $this->assertSame('asset.download'.$shouldBe, $key);
            ++$shouldBe;
        }
    }

    protected function initEvent()
    {
        $translator = $this->createMock(Translator::class);

        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function () {
                $args = func_get_args();

                return $args[0];
            });

        return new CampaignBuilderEvent($translator);
    }
}
