<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Sortie;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $libelle = "";
        $builder
            ->add('produit',EntityType::class, array(
                'class' => Produit::class,
                'label' => 'Libelle du produit',
                'attr' => array('class' => 'form-control form-group'),
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('p')
                        ->where('p.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('p.libelle', 'ASC');
                },
            ))
            ->add('qtSortie', TextType::class, array(
                'label' => 'Quantite vendue',
                'attr' => array(
                    'class' => 'form-control form-group'),
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))

            ->add('prixUnit', TextType::class, array(
                'label' => 'prix unitaire',
                'attr' => array(
                    'class' => 'form-control form-group'
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
            'data_class' => Sortie::class,
        ]);
    }
}
