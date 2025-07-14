<?php

return [
    'name'        => 'Custom Form Plugin',
    'description' => 'A custom form plugin for Mautic with checkbox and text fields',
    'version'     => '1.0.0',
    'author'      => 'Tabdeal Team',
    
    'routes' => [
        'main' => [
            'mautic_customform_index' => [
                'path'       => '/customform',
                'controller' => 'CustomFormBundle:CustomForm:index'
            ],
            'mautic_customform_save' => [
                'path'       => '/customform/save',
                'controller' => 'CustomFormBundle:CustomForm:save'
            ]
        ]
    ],
    
    'services' => [
        'forms' => [
            'mautic.customform.form.type.customform' => [
                'class'     => \MauticPlugin\CustomFormBundle\Form\Type\CustomFormType::class,
                'arguments' => ['router']
            ]
        ],
        'controllers' => [
            'mautic.customform.controller.customform' => [
                'class'     => \MauticPlugin\CustomFormBundle\Controller\CustomFormController::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.customform.form.type.customform'
                ]
            ]
        ],
        'models' => [
            'mautic.customform.model.customform' => [
                'class'     => \MauticPlugin\CustomFormBundle\Model\CustomFormModel::class,
                'arguments' => [
                    'mautic.helper.integration'
                ]
            ]
        ]
    ],
    
    'doctrine' => [
        'entities' => [
            'MauticPlugin\\CustomFormBundle\\Entity\\CustomFormEntry' => [
                'type' => 'annotation',
                'dir' => 'Entity',
                'prefix' => 'MauticPlugin\\CustomFormBundle\\Entity',
                'alias' => 'CustomFormBundle',
            ],
        ],
    ],
    
    'menu' => [
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.customform.menu.index' => [
                    'route'    => 'mautic_customform_index',
                    'priority' => 10,
                    'icon'     => 'fa-cogs'
                ]
            ]
        ]
    ]
]; 