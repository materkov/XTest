<?php
/**
 * Created by PhpStorm.
 * User: Maksim
 * Date: 15.09.2016
 * Time: 17:22
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InputFileType extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filename', TextType::class, array(
                'constraints' => array(
                    new NotBlank(),
                    new Callback(function ($object, ExecutionContextInterface $context, $payload)
                    {
                        /* Filename validation */
                        if (!preg_match('/^[a-z0-9_\\-]+\\.[a-z0-9_\\-]+$/', $object)) {
                            $context->buildViolation('Invalid filename')
                                ->atPath('filename')
                                ->addViolation();
                        }
                    })
                )
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'method' => 'PUT',
            'allow_extra_fields' => true,
        ));
    }
}