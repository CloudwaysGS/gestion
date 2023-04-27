<?php

namespace App\Form;

use App\Entity\Entree;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fournisseur',EntityType::class, array(
                'class' => Fournisseur::class,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    ),
                'placeholder' => 'Nom du fournisseur    ',
                'required' => false,
            ))
            ->add('produit',EntityType::class, array(
                'class' => Produit::class,
                'label' => false,
                'attr' => array('class' => 'form-control form-group',
                    'placeholder' => 'Libelle du produit'
                    )
            ))
            ->add('qtEntree', TextType::class, array(
                'label' => 'Quantite achetée',
                'attr' => array(
                'class' => 'form-control form-group')
            ))
            ->add('prixUnit', TextType::class, array(
                'label' => 'prix unitaire',
                'attr' => array(
                    'class' => 'form-control form-group'
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
            'data_class' => Entree::class,
        ]);
    }
}
