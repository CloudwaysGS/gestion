<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Facture2;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class Facture2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $libelle = "";
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'label' => false,
                'attr' => [
                    'class' => 'form-control form-group',
                ],
                'placeholder' => 'Nom du client',
                'required' => false,
            ])

            ->add('quantite', TextType::class, array(
                'label' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'quantité'),
                'constraints' => array(
                    new NotBlank(),
                    new Type('numeric')
                )
            ))

            ->add('prixUnit',TextType::class,array(
                'label' => false,
                'required' => false,
                'attr' => array(
                    'class' => 'form-control form-group',
                    'placeholder' => 'prix unitaire'                ),
            ))

            ->add('produit', null, [
                'label' => false,
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'height: 20rem;', // ajout de la hauteur personnalisée
                ],
                'required' => false,
                'query_builder' => function(EntityRepository $er) use ($libelle) {
                    return $er->createQueryBuilder('p')
                        ->where('p.libelle LIKE :libelle')
                        ->setParameter('libelle', '%'.$libelle.'%')
                        ->orderBy('p.libelle', 'ASC');
                },
            ])



            ->add('Ajouter', SubmitType::class, array(
                'attr' =>array('class' => 'btn btn-primary form-group')
            ))

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture2::class,
        ]);
    }
}