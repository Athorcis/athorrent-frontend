<?php

namespace Athorrent\Form\Type;

use Athorrent\Database\Entity\User;
use Athorrent\Database\Type\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddUserType extends AbstractType
{
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
                'plainPassword',
                PasswordType::class,
                [
                    'label' => 'users.password',
                    'empty_data' => '',
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'role',
                ChoiceType::class,
                [
                    'choices' => UserRole::$values,
                    'choice_label' => fn(string $id) => $id,
                    'label' => 'users.role',
                    'mapped' => false,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'add',
                SubmitType::class,
                ['label' => 'users.add.submit']
            );
    }
}
