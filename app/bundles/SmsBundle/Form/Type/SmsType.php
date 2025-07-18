<?php

namespace Mautic\SmsBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Mautic\ProjectBundle\Form\Type\ProjectType;
use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Sms>
 */
class SmsType extends AbstractType
{
    public function __construct(
        private EntityManager $em,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html', 'customHtml' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('sms.sms', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.sms.form.internal.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.sms.form.internal.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'message',
            TextareaType::class,
            [
                'label'      => 'mautic.sms.form.message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'                => 'form-control',
                    'data-token-activator' => '{',
                    'data-token-visual'    => 'false',
                    'rows'                 => 6,
                ],
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class, [
            'label' => 'mautic.core.form.available',
        ]);

        // add lead lists
        $transformer = new IdToEntityModelTransformer($this->em, \Mautic\LeadBundle\Entity\LeadList::class, 'id', true);
        $builder->add(
            $builder->create(
                'lists',
                LeadListType::class,
                [
                    'label'      => 'mautic.email.form.list',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                    ],
                    'multiple' => true,
                    'expanded' => false,
                    'required' => true,
                ]
            )
                ->addModelTransformer($transformer)
        );

        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);

        // add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'sms',
            ]
        );

        $builder->add('projects', ProjectType::class);

        $builder->add(
            'language',
            LocaleType::class,
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );
        $builder->add('smsType', HiddenType::class);
        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text' => false,
                ]
            );
            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => Sms::class,
            ]
        );

        $resolver->setDefined(['update_select']);
    }
}
