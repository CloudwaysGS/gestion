<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'Nom du produit',
                )
            ))

            ->add('qtStock', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'quantité',
                ),
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))

            ->add('prixUnit', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'prix unitaire',
                ),
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))
            ->add('Valider', SubmitType::class, array(
                'attr' =>array('class' => 'btn btn-primary form-group')
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
