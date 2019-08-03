<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('position')
            ->add('setPosition')
            ->add('code')
            ->add('name')
            ->add('realName')
            ->add('subname')
            ->add('cost')
            ->add('text')
            ->add('realText')
            ->add('boostText')
            ->add('realBoostText')
            ->add('dateCreation')
            ->add('dateUpdate')
            ->add('quantity')
            ->add('resourceEnergy')
            ->add('resourcePhysical')
            ->add('resourceMental')
            ->add('resourceWild')
            ->add('health')
            ->add('healthPerHero')
            ->add('thwart')
            ->add('thwartCost')
            ->add('scheme')
            ->add('attack')
            ->add('attackCost')
            ->add('defense')
            ->add('defenseCost')
            ->add('recover')
            ->add('recoverCost')
            ->add('deckLimit')
            ->add('kicker')
            ->add('stage')
            ->add('traits')
            ->add('realTraits')
            ->add('deckRequirements')
            ->add('deckOptions')
            ->add('restrictions')
            ->add('flavor')
            ->add('illustrator')
            ->add('isUnique')
            ->add('hidden')
            ->add('doubleSided')
            ->add('backText')
            ->add('backFlavor')
            ->add('backName')
            ->add('octgnId')
            ->add('pack')
            ->add('type')
            ->add('subtype')
            ->add('faction')
            ->add('faction2')
            ->add('card_set')
            ->add('linked_to')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Card'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appbundle_card';
    }
}
