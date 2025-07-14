<?php

namespace MauticPlugin\CustomFormBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\IntegrationHelper;
use MauticPlugin\CustomFormBundle\Form\Type\CustomFormType;
use MauticPlugin\CustomFormBundle\Model\CustomFormModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomFormController extends CommonController
{
    private $integrationHelper;
    private $formType;
    private $customFormModel;

    public function __construct(IntegrationHelper $integrationHelper, CustomFormType $formType, CustomFormModel $customFormModel)
    {
        $this->integrationHelper = $integrationHelper;
        $this->formType = $formType;
        $this->customFormModel = $customFormModel;
    }

    public function indexAction(Request $request)
    {
        $form = $this->createForm(CustomFormType::class);
        
        $savedData = $this->getSavedData();
        if ($savedData) {
            $form->setData($savedData);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
                'savedData' => $savedData
            ],
            'contentTemplate' => 'CustomFormBundle:CustomForm:index.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_customform_index',
                'mauticContent' => 'customform',
                'route'         => $this->generateUrl('mautic_customform_index')
            ]
        ]);
    }

    public function saveAction(Request $request)
    {
        $form = $this->createForm(CustomFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->customFormModel->saveData($data);
            $this->saveData($data);
            $this->addFlash('mautic.core.notice.updated', [
                '%name%' => 'Custom Form Settings'
            ]);
        }

        return $this->redirectToRoute('mautic_customform_index');
    }

    private function getSavedData()
    {
        $integration = $this->integrationHelper->getIntegrationObject('CustomForm');
        if ($integration) {
            return $integration->getIntegrationSettings()->getFeatureSettings();
        }
        
        return null;
    }

    private function saveData($data)
    {
        $integration = $this->integrationHelper->getIntegrationObject('CustomForm');
        if ($integration) {
            $integration->getIntegrationSettings()->setFeatureSettings($data);
            $integration->saveIntegrationSettings();
        }
    }
} 