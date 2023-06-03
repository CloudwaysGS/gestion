<?php

namespace App\Form;

use App\Entity\Detail;
use App\Entity\Entree;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use Doctrine\ORM\EntityRepository;
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
        $libelle = "";
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
                'attr' => array(
                    'class' => 'form-control form-group',
                    ),
                'placeholder' => 'Libelle du produit',
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('p')
                        ->where('p.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('p.libelle', 'ASC');
                },
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
            ->add('detail',EntityType::class, array(
                'class' => Detail::class,
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                ),
                'placeholder' => 'Libelle du produit',
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('p')
                        ->where('p.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('p.libelle', 'ASC');
                },
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
