<?php

namespace AppBundle\Entity;

/**
 * Cardset
 */
class Cardset implements \Gedmo\Translatable\Translatable, \Serializable
{
    public function serialize() {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'cardset_type' => $this->cardset_type ? $this->cardset_type->getCode() : null,
            'parentCode' => $this->parentCode
        ];
    }
    
    public function unserialize($serialized) {
        throw new \Exception("unserialize() method unsupported");
    }

    public function toString() {
        return $this->name;
    }
    
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $parentCode;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cards;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Cardset
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Cardset
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parentCode
     *
     * @param string $parentCode
     *
     * @return Cardset
     */
    public function setParentCode($parentCode)
    {
        $this->parentCode = $parentCode;

        return $this;
    }

    /**
     * Get parentCode
     *
     * @return string
     */
    public function getParentCode()
    {
        return $this->parentCode;
    }

    /**
     * Add card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return Cardset
     */
    public function addCard(\AppBundle\Entity\Card $card)
    {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card
     *
     * @param \AppBundle\Entity\Card $card
     */
    public function removeCard(\AppBundle\Entity\Card $card)
    {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @var \AppBundle\Entity\Cardset
     */
    private $cardset_type;


    /**
     * Set Cardsettype
     *
     * @param \AppBundle\Entity\Cardsettype $cardset_type
     *
     * @return Pack
     */
    public function setCardSetType(\AppBundle\Entity\Cardsettype $cardset_type = null)
    {
        $this->cardset_type = $cardset_type;

        return $this;
    }

    /**
     * Get Cardsettype
     *
     * @return \AppBundle\Entity\Cardsettype
     */
    public function getCardSetType()
    {
        return $this->cardset_type;
    }

    /*
     * I18N vars
     */
    private $locale = 'en';

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
