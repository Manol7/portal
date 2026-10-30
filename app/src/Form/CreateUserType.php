<?php

namespace App\Form;

use App\Account\AccountRules;
use App\Entity\User;
use App\Security\Roles;
use App\Translation\Translations;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formularz tworzenia konta. Dostępne role przekazuje kontroler (opcja "roles").
 */
final class CreateUserType extends AbstractType
{
    public function __construct(private readonly Translations $translations)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translations;

        $roleChoices = [];
        foreach ($options['roles'] as $role) {
            $roleChoices[$t->get(Roles::textKey($role))] = $role;
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => $t->get('form.email'),
                'constraints' => AccountRules::email($t),
            ])
            ->add('role', ChoiceType::class, [
                'label' => $t->get('form.role'),
                'mapped' => false,
                'choices' => $roleChoices,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => $t->get('form.password_mismatch'),
                'first_options' => [
                    'label' => $t->get('form.password'),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => $t->get('form.password_repeat'),
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $t->get('form.password_blank')),
                    ...AccountRules::password($t),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'roles' => [Roles::USER],
            'constraints' => [
                new UniqueEntity(fields: ['email'], message: $this->translations->get('form.email_taken')),
            ],
        ]);
        $resolver->setAllowedTypes('roles', 'array');
    }
}
