<?php

namespace AppBundle\Entity;

/**
 * Packtype
 */
class Cardsettype implements \Gedmo\Translatable\Translatable, \Serializable
{
    public function serialize() {
        return [
                'code' => $this->code,
                'name' => $this->name
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cardsets;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cardsets = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Cardsettype
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
     * @return Cardsettype
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
     * Add cardset
     *
     * @param \AppBundle\Entity\Cardset $cardset
     *
     * @return Cardsettype
     */
    public function addCardset(\AppBundle\Entity\Cardset $cardset)
    {
        $this->cardsets[] = $cardset;

        return $this;
    }

    /**
     * Remove cardset
     *
     * @param \AppBundle\Entity\Cardset $cardset
     */
    public function removePack(\AppBundle\Entity\Cardset $cardset)
    {
        $this->cardsets->removeElement($cardset);
    }

    /**
     * Get cardsets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCardSets()
    {
        return $this->cardsets;
    }

    /**
     * I18N vars
     */
    private $locale = 'en';

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}

