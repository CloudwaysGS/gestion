<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
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
                ),

            ))

            ->add('qtStock', NumberType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-group',
                    'placeholder' => 'Quantité',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez spécifier une quantité.',
                    ]),
                    new Type([
                        'type' => 'float',
                        'message' => 'La quantité doit être un nombre.',
                    ]),
                ],
            ])

            ->add('prixUnit', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'prix unitaire en gros',
                ),
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))
            ->add('createDetail', ChoiceType::class, array(
                'label' => 'Souhaitez-vous créer les détails ?',
                'expanded' => true,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'data' => false,
                'mapped' => false, // This field is not mapped to any property
            ))
            ->add('nomProduitDetail', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'libelle détail',
                ),
            ))

            ->add('nombre', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'nombre de pièce dans le carton ou sac',
                ),
                'constraints' => array(
                    new Type('numeric')
                )
            ))

            ->add('prixDetail', TextType::class, array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'prix unitaire en détail',
                ),
                'constraints' => array(
                    new Type('numeric')
                )
            ))

            ->add('Ajouter', SubmitType::class, array(
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