<?php

namespace MauticPlugin\CustomFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class CustomFormType extends AbstractType
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label'      => 'mautic.customform.form.enabled',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.customform.form.enabled.tooltip'
                ],
                'required'   => false
            ])
            ->add('text_field_1', TextType::class, [
                'label'      => 'mautic.customform.form.text_field_1',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.customform.form.text_field_1.tooltip',
                    'placeholder'  => 'mautic.customform.form.text_field_1.placeholder'
                ],
                'required'   => false
            ])
            ->add('text_field_2', TextType::class, [
                'label'      => 'mautic.customform.form.text_field_2',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.customform.form.text_field_2.tooltip',
                    'placeholder'  => 'mautic.customform.form.text_field_2.placeholder'
                ],
                'required'   => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null
        ]);
    }

    public function getBlockPrefix()
    {
        return 'customform';
    }
} 