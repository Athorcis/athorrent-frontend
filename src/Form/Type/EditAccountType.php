<?php

namespace Athorrent\Form\Type;

use Athorrent\Database\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditAccountType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'label' => 'users.username',
                    'empty_data' => '',
                ]
            )
            ->add(
                'current_password',
                PasswordType::class,
                [
                    'label' => 'account.edit.current_password',
                    'mapped' => false,
                    'constraints' => new UserPassword(),
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'error.passwordsDiffer',
                    'label' => 'account.edit.new_password',
                    'required' => false,
                    'first_options'  => ['label' => 'account.edit.new_password'],
                    'second_options' => ['label' => 'account.edit.password_confirm'],
                ]
            )
            ->add(
                'roles',
                TextType::class,
                [
                    'label' => 'users.role',
                    'disabled' => true,
                ]
            )
            ->add(
                'update',
                SubmitType::class,
                ['label' => 'account.edit.submit'],
            );

        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                fn ($rolesAsArray) => count($rolesAsArray) ? $this->translator->trans($rolesAsArray[0]): null,
                fn ($rolesAsString) => [$rolesAsString]
            ));
    }
}
