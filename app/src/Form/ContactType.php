<?php

namespace App\Form;

use App\Account\AccountRules;
use App\Entity\ContactMessage;
use App\Translation\Translations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formularz kontaktowy. Pole "website" to honeypot: ukryte dla ludzi, wypełniane przez boty.
 */
final class ContactType extends AbstractType
{
    public const HONEYPOT = 'website';

    public function __construct(private readonly Translations $translations)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $t = $this->translations;

        $builder
            ->add('email', EmailType::class, [
                'label' => $t->get('contact.email'),
                'attr' => ['autocomplete' => 'email', 'maxlength' => AccountRules::EMAIL_MAX_LENGTH],
                'constraints' => AccountRules::email($t),
            ])
            ->add('subject', TextType::class, [
                'label' => $t->get('contact.subject'),
                'attr' => ['maxlength' => ContactMessage::SUBJECT_MAX_LENGTH],
                'constraints' => [
                    new Assert\NotBlank(message: $t->get('contact.subject_blank')),
                    new Assert\Length(max: ContactMessage::SUBJECT_MAX_LENGTH, maxMessage: $t->get('contact.subject_long')),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => $t->get('contact.message'),
                'attr' => ['rows' => 6, 'maxlength' => ContactMessage::MESSAGE_MAX_LENGTH],
                'constraints' => [
                    new Assert\NotBlank(message: $t->get('contact.message_blank')),
                    new Assert\Length(
                        min: ContactMessage::MESSAGE_MIN_LENGTH,
                        max: ContactMessage::MESSAGE_MAX_LENGTH,
                        minMessage: $t->get('contact.message_short'),
                        maxMessage: $t->get('contact.message_long'),
                    ),
                ],
            ])
            ->add(self::HONEYPOT, TextType::class, [
                'label' => $t->get('contact.honeypot'),
                'mapped' => false,
                'required' => false,
                'attr' => ['tabindex' => '-1', 'autocomplete' => 'off'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
            'csrf_protection' => true,
        ]);
    }
}
