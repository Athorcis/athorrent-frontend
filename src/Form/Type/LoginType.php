<?php

namespace Athorrent\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'action' => $this->urlGenerator->generate('login_check'),
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                '_username',
                TextType::class,
                [
                    'label' => 'login.username',
                    'attr' => ['autofocus' => true],
                ],
            )
            ->add(
                '_password',
                PasswordType::class,
                ['label' => 'login.password'],
            )
            ->add('_remember_me', CheckboxType::class, [
                'label' => 'login.remember_me',
                'required' => false,
                'data' => true,
            ])
            ->add(
                'login',
                SubmitType::class,
                ['label' => 'login.submit'],
            );
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
