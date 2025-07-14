<?php

namespace MauticPlugin\CustomFormBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\IntegrationHelper;
use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\CustomFormBundle\Entity\CustomFormEntry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormView;

class CustomFormModel extends FormModel
{
    private $integrationHelper;
    private $entityManager;

    public function __construct(IntegrationHelper $integrationHelper, EntityManagerInterface $entityManager)
    {
        $this->integrationHelper = $integrationHelper;
        $this->entityManager = $entityManager;
    }

    public function getEntity($id = null): ?object
    {
        return null;
    }

    public function createForm(
        $entity,
        \Symfony\Component\Form\FormFactoryInterface $formFactory,
        $action = null,
        $options = []
    ): \Symfony\Component\Form\FormInterface {
        return new class implements \Symfony\Component\Form\FormInterface {
            public function getConfig() {}
            public function setParent(\Symfony\Component\Form\FormInterface $parent = null) {}
            public function getParent() {}
            public function add($child, $type = null, array $options = []) {}
            public function get($name) {}
            public function has($name) {}
            public function remove($name) {}
            public function all() {}
            public function getErrors($deep = false, $flatten = true) {}
            public function isSubmitted() {}
            public function isSynchronized() {}
            public function getData() {}
            public function setData($modelData) {}
            public function getNormData() {}
            public function getViewData() {}
            public function getExtraData() {}
            public function getName() {}
            public function getPropertyPath() {}
            public function addError(\Symfony\Component\Form\FormError $error) {}
            public function isValid() {}
            public function isRequired() {}
            public function isDisabled() {}
            public function isEmpty() {}
            public function isRoot() {}
            public function createView(\Symfony\Component\Form\FormView $parent = null) {}
            public function getRoot() {}
            public function getErrorsAsString() {}
            public function count() {}
            public function getIterator() {}
        };
    }

    public function saveEntity($entity, $unlock = true): void
    {
    }

    public function getRepository(): ?object
    {
        return null;
    }

    public function getPermissionBase(): string
    {
        return 'customform:customform';
    }

    public function getData(): array
    {
        $integration = $this->integrationHelper->getIntegrationObject('CustomForm');
        if ($integration) {
            return $integration->getIntegrationSettings()->getFeatureSettings();
        }
        return [];
    }

    public function saveData($data): bool
    {
        $entry = new CustomFormEntry();
        $entry->setEnabled($data['enabled'] ?? null);
        $entry->setTextField1($data['text_field_1'] ?? null);
        $entry->setTextField2($data['text_field_2'] ?? null);
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
        return true;
    }
} 