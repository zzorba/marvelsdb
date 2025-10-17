<?php

namespace AppBundle\Entity;

class Card implements \Gedmo\Translatable\Translatable, \Serializable
{

	private function snakeToCamel($snake) {
		$parts = explode('_', $snake);
		return implode('', array_map('ucfirst', $parts));
	}

	public function serialize() {
		$serialized = [];
		if(empty($this->code)) return $serialized;

		$mandatoryFields = [
			'code',
			'position',
			'quantity',
			'name'
		];

		$optionalFields = [
			'illustrator',
			'errata',
			'flavor',
			'traits',
			'text',
			'cost',
			'cost_per_hero',
			'octgn_id',
			'subname',
			'deck_limit',
			'back_text',
			'back_name',
			'back_flavor',
			'permanent',
			'hidden',
			'double_sided',
			'card_set',
			'set_position',
			'is_unique',
			'meta'
		];

		$externalFields = [
			'faction',
			'faction2',
			'pack',
			'card_set',
			'type',
			'linked_to',
			'duplicate_of'
		];

		switch($this->type->getCode()) {
			case 'event':
			case 'upgrade':
			case 'support':
				$mandatoryFields[] = 'cost';
				$optionalFields[] = 'resource_energy';
				$optionalFields[] = 'resource_physical';
				$optionalFields[] = 'resource_mental';
				$optionalFields[] = 'resource_wild';
				break;
			case 'resource':
				$optionalFields[] = 'resource_energy';
				$optionalFields[] = 'resource_physical';
				$optionalFields[] = 'resource_mental';
				$optionalFields[] = 'resource_wild';
				break;
			case 'player_side_scheme':
				$mandatoryFields[] = 'cost';
				$mandatoryFields[] = 'base_threat';
				$optionalFields[] = 'base_threat_fixed';
				$optionalFields[] = 'base_threat_per_group';
				$optionalFields[] = 'resource_energy';
				$optionalFields[] = 'resource_physical';
				$optionalFields[] = 'resource_mental';
				$optionalFields[] = 'resource_wild';
				$optionalFields[] = 'scheme_acceleration';
				$optionalFields[] = 'scheme_amplify';
				$optionalFields[] = 'scheme_crisis';
				$optionalFields[] = 'scheme_hazard';
			case 'ally':
				$mandatoryFields[] = 'cost';
				$optionalFields[] = 'attack';
				$optionalFields[] = 'attack_cost';
				$optionalFields[] = 'attack_star';
				$optionalFields[] = 'health';
				$optionalFields[] = 'health_star';
				$optionalFields[] = 'resource_energy';
				$optionalFields[] = 'resource_physical';
				$optionalFields[] = 'resource_mental';
				$optionalFields[] = 'resource_wild';
				$optionalFields[] = 'thwart';
				$optionalFields[] = 'thwart_cost';
				$optionalFields[] = 'thwart_star';
				break;
			case 'hero':
				$mandatoryFields[] = 'attack';
				$mandatoryFields[] = 'deck_requirements';
				$mandatoryFields[] = 'defense';
				$mandatoryFields[] = 'hand_size';
				$mandatoryFields[] = 'health';
				$mandatoryFields[] = 'thwart';
				$optionalFields[] = 'attack_star';
				$optionalFields[] = 'defense_star';
				$optionalFields[] = 'health_star';
				$optionalFields[] = 'thwart_star';
				break;
			case 'alter_ego':
				$mandatoryFields[] = 'hand_size';
				$mandatoryFields[] = 'deck_requirements';
				$mandatoryFields[] = 'health';
				$mandatoryFields[] = 'recover';
				$optionalFields[] = 'health_star';
				$optionalFields[] = 'recover_star';
				break;
			case "treachery":
				$externalFields[] = 'subtype';
				$optionalFields[] = 'boost';
				$optionalFields[] = 'boost_star';
				break;
			case "attachment":
				$externalFields[] = 'subtype';
				$optionalFields[] = 'attack';
				$optionalFields[] = 'attack_star';
				$optionalFields[] = 'boost';
				$optionalFields[] = 'boost_star';
				$optionalFields[] = 'scheme';
				$optionalFields[] = 'scheme_star';
				break;
			case "leader":
			case "villain":
				$optionalFields[] = 'attack';
				$optionalFields[] = 'attack_star';
				$optionalFields[] = 'health';
				$optionalFields[] = 'health_per_group';
				$optionalFields[] = 'health_per_hero';
				$optionalFields[] = 'health_star';
				$optionalFields[] = 'scheme';
				$optionalFields[] = 'scheme_star';
				$optionalFields[] = 'stage';
			case "minion":
				$externalFields[] = 'subtype';
				$optionalFields[] = 'attack';
				$optionalFields[] = 'attack_star';
				$optionalFields[] = 'boost';
				$optionalFields[] = 'boost_star';
				$optionalFields[] = 'health';
				$optionalFields[] = 'health_per_group';
				$optionalFields[] = 'health_per_hero';
				$optionalFields[] = 'health_star';
				$optionalFields[] = 'scheme';
				$optionalFields[] = 'scheme_star';
			case "sideScheme":
				$externalFields[] = 'subtype';
				$optionalFields[] = 'base_threat';
				$optionalFields[] = 'base_threat_fixed';
				$optionalFields[] = 'base_threat_per_group';
				$optionalFields[] = 'boost';
				$optionalFields[] = 'boost_star';
				$optionalFields[] = 'escalation_threat';
				$optionalFields[] = 'escalation_threat_fixed';
				$optionalFields[] = 'escalation_threat_star';
				$optionalFields[] = 'scheme_acceleration';
				$optionalFields[] = 'scheme_amplify';
				$optionalFields[] = 'scheme_crisis';
				$optionalFields[] = 'scheme_hazard';
				break;
			case "mainScheme":
				$externalFields[] = 'subtype';
				$optionalFields[] = 'base_threat';
				$optionalFields[] = 'base_threat_fixed';
				$optionalFields[] = 'base_threat_per_group';
				$optionalFields[] = 'escalation_threat';
				$optionalFields[] = 'escalation_threat_fixed';
				$optionalFields[] = 'escalation_threat_star';
				$optionalFields[] = 'stage';
				$optionalFields[] = 'threat';
				$optionalFields[] = 'threat_fixed';
				$optionalFields[] = 'threat_per_group';
				$optionalFields[] = 'threat_star';
				break;
		}

		foreach($optionalFields as $optionalField) {
			$getterString = $optionalField;
			$getter = 'get' . $this->snakeToCamel($getterString);
			$serialized[$optionalField] = $this->$getter();
			if(!isset($serialized[$optionalField]) || $serialized[$optionalField] === '') unset($serialized[$optionalField]);
		}

		foreach($mandatoryFields as $mandatoryField) {
			$getterString = $mandatoryField;
			$getter = 'get' . $this->snakeToCamel($getterString);
			$serialized[$mandatoryField] = $this->$getter();
		}

		foreach($externalFields as $externalField) {
			$getter = 'get' . $this->snakeToCamel($externalField);
			if ($this->$getter()){
				$serialized[$externalField.'_code'] = $this->$getter()->getCode();
			}
		}

		ksort($serialized);
		return $serialized;
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
	 * @var integer
	 */
	private $position;

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
	private $realName;

	/**
	 * @var string
	 */
	private $backName;

	/**
	 * @var string
	 */
	private $subname;

	/**
	 * @var string
	 */
	private $meta;

	/**
	 * @var integer
	 */
	private $cost;

	/**
	 * @var boolean
	 */
	private $costPerHero;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @var string
	 */
	private $realText;


	/**
	 * @var string
	 */
	private $backText;

	/**
	 * @var \DateTime
	 */
	private $dateCreation;

	/**
	 * @var \DateTime
	 */
	private $dateUpdate;

	/**
	 * @var integer
	 */
	private $quantity;

	/**
	 * @var integer
	 */
	private $resourceMental;

	/**
	 * @var integer
	 */
	private $resourcePhysical;

	/**
	 * @var integer
	 */
	private $resourceEnergy;

	/**
	 * @var integer
	 */
	private $resourceWild;

	/**
	 * @var integer
	 */
	private $baseThreat;

	/**
	 * @var integer
	 */
	private $health;

	/**
	 * @var boolean
	 */
	private $healthPerGroup;

    /**
     * @var boolean
     */
    private $healthPerHero;

	/**
	 * @var boolean
	 */
	private $healthStar;

	/**
	 * @var integer
	 */
	private $attack;

	/**
	 * @var integer
	 */
	private $attackCost;

	/**
	 * @var boolean
	 */
	private $attackStar;

	/**
	 * @var integer
	 */
	private $thwart;

	/**
	 * @var integer
	 */
	private $thwartCost;

	/**
	 * @var boolean
	 */
	private $thwartStar;

	/**
	 * @var integer
	 */
	private $defense;

	/**
	 * @var boolean
	 */
	private $defenseStar;

	/**
	 * @var integer
	 */
	private $recover;

	/**
	 * @var boolean
	 */
	private $recoverStar;

	/**
	 * @var integer
	 */
	private $deckLimit;

	/**
	 * @var string
	 */
	private $traits;

	/**
	 * @var string
	 */
	private $realTraits;

	/**
	 * @var string
	 */
	private $deckRequirements;

	/**
	 * @var string
	 */
	private $deckOptions;

	/**
	 * @var string
	 */
	private $restrictions;

	/**
	 * @var string
	 */
	private $stage;

	/**
	 * @var string
	 */
	private $flavor;

	 /**
	 * @var string
	 */
	private $backFlavor;

	/**
	 * @var string
	 */
	private $illustrator;

	/**
	 * @var boolean
	 */
	private $isUnique;

	/**
	 * @var boolean
	 */
	private $hidden;

	/**
	 * @var boolean
	 */
	private $permanent;

	/**
	 * @var boolean
	 */
	private $doubleSided;

	/**
	 * @var string
	 */
	private $octgnId;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $reviews;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $duplicates;

	/**
	 * @var \AppBundle\Entity\Pack
	 */
	private $pack;

	/**
	 * @var \AppBundle\Entity\Type
	 */
	private $type;

	/**
	 * @var \AppBundle\Entity\Faction
	 */
	private $faction;

	/**
	 * @var \AppBundle\Entity\Faction
	 */
	private $faction2;

		/**
	 * @var \AppBundle\Entity\Subtype
	 */
	private $subtype;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $linked_from;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $duplicate_of;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $linked_to;

	/**
	 * @var string
	 */
	private $errata;

	/**
	 * Constructor
	 */
	public function __construct()
	{
	  $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
		$this->duplicates = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set position
	 *
	 * @param integer $position
	 *
	 * @return Card
	 */
	public function setPosition($position)
	{
		$this->position = $position;

		return $this;
	}

	/**
	 * Get position
	 *
	 * @return integer
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Set code
	 *
	 * @param string $code
	 *
	 * @return Card
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
	 * @return Card
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
	 * Set realName
	 *
	 * @param string $realname
	 *
	 * @return Card
	 */
	public function setRealName($realName)
	{
		$this->realName = $realName;

		return $this;
	}

	/**
	 * Get realName
	 *
	 * @return string
	 */
	public function getRealName()
	{
		return $this->realName;
	}

	/**
	 * Set backName
	 *
	 * @param string $backName
	 *
	 * @return Card
	 */
	public function setBackName($backName)
	{
		$this->backName = $backName;

		return $this;
	}

	/**
	 * Get backName
	 *
	 * @return string
	 */
	public function getBackName()
	{
		return $this->backName;
	}


		/**
	 * Set subname
	 *
	 * @param string $subname
	 *
	 * @return Card
	 */
	public function setSubname($subname)
	{
		$this->subname = $subname;

		return $this;
	}

	/**
	 * Get subname
	 *
	 * @return string
	 */
	public function getSubname()
	{
		return $this->subname;
	}

	/**
	 * Set cost
	 *
	 * @param integer $cost
	 *
	 * @return Card
	 */
	public function setCost($cost)
	{
		$this->cost = $cost;

		return $this;
	}

	/**
	 * Get cost
	 *
	 * @return integer
	 */
	public function getCost()
	{
		return $this->cost;
	}

	/**
	 * Set costPerHero
	 *
	 * @param boolean $costPerHero
	 *
	 * @return Card
	 */
	public function setCostPerHero($costPerHero)
	{
		$this->costPerHero = $costPerHero;

		return $this;
	}

	/**
	 * Get costPerHero
	 *
	 * @return boolean
	 */
	public function getCostPerHero()
	{
		return $this->costPerHero;
	}

	/**
	 * Set text
	 *
	 * @param string $text
	 *
	 * @return Card
	 */
	public function setText($text)
	{
		$this->text = $text;

		return $this;
	}

	/**
	 * Get text
	 *
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}


	 /**
	 * Set real text
	 *
	 * @param string $text
	 *
	 * @return Card
	 */
	public function setRealText($text)
	{
		$this->realText = $text;

		return $this;
	}

	/**
	 * Get real text
	 *
	 * @return string
	 */
	public function getRealText()
	{
		return $this->realText;
	}

		/**
	 * Set backText
	 *
	 * @param string $backText
	 *
	 * @return Card
	 */
	public function setBackText($backText)
	{
		$this->backText = $backText;

		return $this;
	}

	/**
	 * Get backText
	 *
	 * @return string
	 */
	public function getBackText()
	{
		return $this->backText;
	}


	/**
	 * Set dateCreation
	 *
	 * @param \DateTime $dateCreation
	 *
	 * @return Card
	 */
	public function setDateCreation($dateCreation)
	{
		$this->dateCreation = $dateCreation;

		return $this;
	}

	/**
	 * Get dateCreation
	 *
	 * @return \DateTime
	 */
	public function getDateCreation()
	{
		return $this->dateCreation;
	}

	/**
	 * Set dateUpdate
	 *
	 * @param \DateTime $dateUpdate
	 *
	 * @return Card
	 */
	public function setDateUpdate($dateUpdate)
	{
		$this->dateUpdate = $dateUpdate;

		return $this;
	}

	/**
	 * Get dateUpdate
	 *
	 * @return \DateTime
	 */
	public function getDateUpdate()
	{
		return $this->dateUpdate;
	}

	/**
	 * Set quantity
	 *
	 * @param integer $quantity
	 *
	 * @return Card
	 */
	public function setQuantity($quantity)
	{
		$this->quantity = $quantity;

		return $this;
	}

	/**
	 * Get quantity
	 *
	 * @return integer
	 */
	public function getQuantity()
	{
		return $this->quantity;
	}

	/**
     * Set meta
     *
     * @param string $meta
     *
     * @return Card
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get meta
     *
     * @return string
     */
    public function getMeta()
    {
        return $this->meta;
    }

	/**
	 * Set health
	 *
	 * @param integer $health
	 *
	 * @return Card
	 */
	public function setHealth($health)
	{
		$this->health = $health;

		return $this;
	}

	/**
	 * Get health
	 *
	 * @return integer
	 */
	public function getHealth()
	{
		return $this->health;
	}

	/**
	 * Set healthPerPlayer
	 *
	 * @param boolean $healthPerPlayer
	 *
	 * @return Card
	 */
	public function setHealthPerPlayer($healthPerPlayer)
	{
		$this->healthPerPlayer = $healthPerPlayer;

		return $this;
	}

	/**
	 * Get healthPerPlayer
	 *
	 * @return boolean
	 */
	public function getHealthPerPlayer()
	{
		return $this->healthPerPlayer;
	}

	/**
	 * Set healthStar
	 *
	 * @param boolean $healthStar
	 *
	 * @return Card
	 */
	public function setHealthStar($healthStar)
	{
		$this->healthStar = $healthStar;
		return $this;
	}

	/**
	 * Get healthStar
	 *
	 * @return boolean
	 */
	public function getHealthStar()
	{
		return $this->healthStar;
	}

	/**
	 * Set enemy fight
	 *
	 * @param integer $enemyFight
	 *
	 * @return Card
	 */
	public function setEnemyFight($enemyFight)
	{
		$this->enemyFight = $enemyFight;

		return $this;
	}

	/**
	 * Get enemyFight
	 *
	 * @return integer
	 */
	public function getEnemyFight()
	{
		return $this->enemyFight;
	}


	/**
	 * Set enemy Evade
	 *
	 * @param integer $enemyEvade
	 *
	 * @return Card
	 */
	public function setEnemyEvade($enemyEvade)
	{
		$this->enemyEvade = $enemyEvade;

		return $this;
	}

	/**
	 * Get enemyEvade
	 *
	 * @return integer
	 */
	public function getEnemyEvade()
	{
		return $this->enemyEvade;
	}


	/**
	 * Set damage health
	 *
	 * @param integer $enemyDamage
	 *
	 * @return Card
	 */
	public function setEnemyDamage($enemyDamage)
	{
		$this->enemyDamage = $enemyDamage;

		return $this;
	}

	/**
	 * Get damageHealth
	 *
	 * @return integer
	 */
	public function getEnemyDamage()
	{
		return $this->enemyDamage;
	}

	/**
	 * Set victory
	 *
	 * @param integer $victory
	 *
	 * @return Card
	 */
	public function setVictory($victory)
	{
		$this->victory = $victory;

		return $this;
	}

	/**
	 * Get victory
	 *
	 * @return integer
	 */
	public function getVictory()
	{
		return $this->victory;
	}



	/**
	 * Set vengeance
	 *
	 * @param integer $vengeance
	 *
	 * @return Card
	 */
	public function setVengeance($vengeance)
	{
		$this->vengeance = $vengeance;

		return $this;
	}

	/**
	 * Get vengeance
	 *
	 * @return integer
	 */
	public function getVengeance()
	{
		return $this->vengeance;
	}



	/**
	 * Set deckLimit
	 *
	 * @param integer $deckLimit
	 *
	 * @return Card
	 */
	public function setDeckLimit($deckLimit)
	{
		$this->deckLimit = $deckLimit;

		return $this;
	}

	/**
	 * Get deckLimit
	 *
	 * @return integer
	 */
	public function getDeckLimit()
	{
		return $this->deckLimit;
	}

	/**
	 * Set traits
	 *
	 * @param string $traits
	 *
	 * @return Card
	 */
	public function setTraits($traits)
	{
		$this->traits = $traits;

		return $this;
	}

	/**
	 * Get real traits
	 *
	 * @return string
	 */
	public function getRealTraits()
	{
		return $this->realTraits;
	}

	/**
	 * Set traits
	 *
	 * @param string $traits
	 *
	 * @return Card
	 */
	public function setRealTraits($traits)
	{
		$this->realTraits = $traits;

		return $this;
	}

	/**
	 * Get traits
	 *
	 * @return string
	 */
	public function getTraits()
	{
		return $this->traits;
	}

	/**
	 * Set deckRequirements
	 *
	 * @param string $deckRequirements
	 *
	 * @return Card
	 */
	public function setDeckRequirements($deckRequirements)
	{
		$this->deckRequirements = $deckRequirements;

		return $this;
	}

	/**
	 * Get deckRequirements
	 *
	 * @return string
	 */
	public function getDeckRequirements()
	{
		return $this->deckRequirements;
	}


		/**
	 * Set deckOptions
	 *
	 * @param string $deckOptions
	 *
	 * @return Card
	 */
	public function setDeckOptions($deckOptions)
	{
		$this->deckOptions = $deckOptions;
		return $this;
	}

	/**
	 * Get deckOptions
	 *
	 * @return string
	 */
	public function getdeckOptions()
	{
		return $this->deckOptions;
	}

		/**
	 * Set restrictions
	 *
	 * @param string $restrictions
	 *
	 * @return Card
	 */
	public function setRestrictions($restrictions)
	{
		$this->restrictions = $restrictions;

		return $this;
	}

	/**
	 * Get restrictions
	 *
	 * @return string
	 */
	public function getRestrictions()
	{
		return $this->restrictions;
	}


	/**
	 * Set stage
	 *
	 * @param string $stage
	 *
	 * @return Card
	 */
	public function setStage($stage)
	{
		$this->stage = $stage;

		return $this;
	}

	/**
	 * Get stage
	 *
	 * @return string
	 */
	public function getStage()
	{
		return $this->stage;
	}


	/**
	 * Set flavor
	 *
	 * @param string $flavor
	 *
	 * @return Card
	 */
	public function setFlavor($flavor)
	{
		$this->flavor = $flavor;

		return $this;
	}

	/**
	 * Get flavor
	 *
	 * @return string
	 */
	public function getFlavor()
	{
		return $this->flavor;
	}



	 /**
	 * Set backFlavor
	 *
	 * @param string $backFlavor
	 *
	 * @return Card
	 */
	public function setBackFlavor($backFlavor)
	{
		$this->backFlavor = $backFlavor;

		return $this;
	}

	/**
	 * Get backFlavor
	 *
	 * @return string
	 */
	public function getBackFlavor()
	{
		return $this->backFlavor;
	}

	/**
	 * Set illustrator
	 *
	 * @param string $illustrator
	 *
	 * @return Card
	 */
	public function setIllustrator($illustrator)
	{
		$this->illustrator = $illustrator;

		return $this;
	}

	/**
	 * Get illustrator
	 *
	 * @return string
	 */
	public function getIllustrator()
	{
		return $this->illustrator;
	}

	/**
	 * Set isUnique
	 *
	 * @param boolean $isUnique
	 *
	 * @return Card
	 */
	public function setIsUnique($isUnique)
	{
		$this->isUnique = $isUnique;

		return $this;
	}

	/**
	 * Get isUnique
	 *
	 * @return boolean
	 */
	public function getIsUnique()
	{
		return $this->isUnique;
	}

	/**
	 * Set hidden
	 *
	 * @param boolean $hidden
	 *
	 * @return Card
	 */
	public function setHidden($hidden)
	{
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * Get hidden
	 *
	 * @return boolean
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Set permanent
	 *
	 * @param boolean $permanent
	 *
	 * @return Card
	 */
	public function setPermanent($permanent)
	{
		$this->permanent = $permanent;

		return $this;
	}

	/**
	 * Get permanent
	 *
	 * @return boolean
	 */
	public function getPermanent()
	{
		return $this->permanent;
	}

	/**
	 * Set doubleSided
	 *
	 * @param boolean $doubleSided
	 *
	 * @return Card
	 */
	public function setDoubleSided($doubleSided)
	{
		$this->doubleSided = $doubleSided;

		return $this;
	}

	/**
	 * Get doubleSided
	 *
	 * @return boolean
	 */
	public function getDoubleSided()
	{
		return $this->doubleSided;
	}


	/**
	 * Set octgnId
	 *
	 * @param boolean $octgnId
	 *
	 * @return Card
	 */
	public function setOctgnId($octgnId)
	{
		$this->octgnId = $octgnId;

		return $this;
	}

	/**
	 * Get octgnId
	 *
	 * @return boolean
	 */
	public function getOctgnId($part=0)
	{
		if ($part){
			$parts = explode(":", $this->octgnId);
			if (isset($parts[$part-1])){
				return $parts[$part-1];
			}
			return "";
		} else {
			return $this->octgnId;
		}
	}

	/**
	 * Add review
	 *
	 * @param \AppBundle\Entity\Review $review
	 *
	 * @return Card
	 */
	public function addReview(\AppBundle\Entity\Review $review)
	{
		$this->reviews[] = $review;

		return $this;
	}

	/**
	 * Remove review
	 *
	 * @param \AppBundle\Entity\Review $review
	 */
	public function removeReview(\AppBundle\Entity\Review $review)
	{
		$this->reviews->removeElement($review);
	}

	/**
	 * Get reviews
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getReviews()
	{
		return $this->reviews;
	}


	/**
	 * Add duplicates
	 *
	 * @param \AppBundle\Entity\Card $duplicate
	 *
	 * @return Card
	 */
	public function addDuplicate(\AppBundle\Entity\Card $duplicate)
	{
		$this->duplicates[] = $duplicate;

		return $this;
	}

	/**
	 * Remove duplicates
	 *
	 * @param \AppBundle\Entity\Card $duplicate
	 */
	public function removeDuplicates(\AppBundle\Entity\Card $duplicate)
	{
		$this->duplicates->removeElement($duplicate);
	}

	/**
	 * Get duplicates
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDuplicates()
	{
		return $this->duplicates;
	}

	/**
	 * Set pack
	 *
	 * @param \AppBundle\Entity\Pack $pack
	 *
	 * @return Card
	 */
	public function setPack(\AppBundle\Entity\Pack $pack = null)
	{
		$this->pack = $pack;

		return $this;
	}

	/**
	 * Get pack
	 *
	 * @return \AppBundle\Entity\Pack
	 */
	public function getPack()
	{
		return $this->pack;
	}

	/**
	 * Set type
	 *
	 * @param \AppBundle\Entity\Type $type
	 *
	 * @return Card
	 */
	public function setType(\AppBundle\Entity\Type $type = null)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Get type
	 *
	 * @return \AppBundle\Entity\Type
	 */
	public function getType()
	{
		return $this->type;
	}

		/**
	 * Set subtype
	 *
	 * @param \AppBundle\Entity\Subtype $subtype
	 *
	 * @return Card
	 */
	public function setSubtype(\AppBundle\Entity\Subtype $subtype = null)
	{
		$this->subtype = $subtype;

		return $this;
	}

	/**
	 * Get subtype
	 *
	 * @return \AppBundle\Entity\Subtype
	 */
	public function getSubtype()
	{
		return $this->subtype;
	}

	/**
	 * Set faction
	 *
	 * @param \AppBundle\Entity\Faction $faction
	 *
	 * @return Card
	 */
	public function setFaction(\AppBundle\Entity\Faction $faction = null)
	{
		$this->faction = $faction;

		return $this;
	}

	/**
	 * Get faction
	 *
	 * @return \AppBundle\Entity\Faction
	 */
	public function getFaction()
	{
		return $this->faction;
	}

	/**
	 * Set faction2
	 *
	 * @param \AppBundle\Entity\Faction $faction2
	 *
	 * @return Card
	 */
	public function setFaction2(\AppBundle\Entity\Faction $faction2 = null)
	{
		$this->faction2 = $faction2;

		return $this;
	}

	/**
	 * Get faction
	 *
	 * @return \AppBundle\Entity\Faction
	 */
	public function getFaction2()
	{
		return $this->faction2;
	}


		/**
	 * set linkedTo
	 *
	 * @param \AppBundle\Entity\Card $card
	 *
	 * @return Card
	 */
	public function setLinkedTo(\AppBundle\Entity\Card $linkedTo = null)
	{
		$this->linked_to = $linkedTo;
		return $this;
	}

	/**
	 * Get linkedTo
	 *
	 * @return \AppBundle\Entity\Card
	 */
	public function getLinkedTo()
	{
		return $this->linked_to;
	}


	/**
	 * set duplicateOf
	 *
	 * @param \AppBundle\Entity\Card $card
	 *
	 * @return Card
	 */
	public function setDuplicateOf(\AppBundle\Entity\Card $duplicateOf = null)
	{
		$this->duplicate_of = $duplicateOf;
		return $this;
	}

	/**
	 * Get duplicateOf
	 *
	 * @return \AppBundle\Entity\Card
	 */
	public function getDuplicateOf()
	{
		return $this->duplicate_of;
	}


	/*
	* I18N vars
	*/
	private $locale = 'en';

	public function setTranslatableLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * Add linkedFrom
	 *
	 * @param \AppBundle\Entity\Card $linkedFrom
	 *
	 * @return Card
	 */
	public function addLinkedFrom(\AppBundle\Entity\Card $linkedFrom)
	{
		$this->linked_from[] = $linkedFrom;

		return $this;
	}

	/**
	 * Remove linkedFrom
	 *
	 * @param \AppBundle\Entity\Card $linkedFrom
	 */
	public function removeLinkedFrom(\AppBundle\Entity\Card $linkedFrom)
	{
		$this->linked_from->removeElement($linkedFrom);
	}

	/**
	 * Get linkedFrom
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLinkedFrom()
	{
		return $this->linked_from;
	}

    /**
     * @var integer
     */
    private $defenseCost;

    /**
     * @var integer
     */
    private $recoverCost;


    /**
     * Set resourceEnergy
     *
     * @param integer $resourceEnergy
     *
     * @return Card
     */
    public function setResourceEnergy($resourceEnergy)
    {
        $this->resourceEnergy = $resourceEnergy;

        return $this;
    }

    /**
     * Get resourceEnergy
     *
     * @return integer
     */
    public function getResourceEnergy()
    {
        return $this->resourceEnergy;
    }

    /**
     * Set resourcePhysical
     *
     * @param integer $resourcePhysical
     *
     * @return Card
     */
    public function setResourcePhysical($resourcePhysical)
    {
        $this->resourcePhysical = $resourcePhysical;

        return $this;
    }

    /**
     * Get resourcePhysical
     *
     * @return integer
     */
    public function getResourcePhysical()
    {
        return $this->resourcePhysical;
    }

    /**
     * Set resourceMental
     *
     * @param integer $resourceMental
     *
     * @return Card
     */
    public function setResourceMental($resourceMental)
    {
        $this->resourceMental = $resourceMental;

        return $this;
    }

    /**
     * Get resourceMental
     *
     * @return integer
     */
    public function getResourceMental()
    {
        return $this->resourceMental;
    }

    /**
     * Set resourceWild
     *
     * @param integer $resourceWild
     *
     * @return Card
     */
    public function setResourceWild($resourceWild)
    {
        $this->resourceWild = $resourceWild;

        return $this;
    }

    /**
     * Get resourceWild
     *
     * @return integer
     */
    public function getResourceWild()
    {
        return $this->resourceWild;
    }

    /**
     * Set healthPerGroup
     *
     * @param boolean $healthPerGroup
     *
     * @return Card
     */
    public function setHealthPerGroup($healthPerGroup)
    {
        $this->healthPerGroup = $healthPerGroup;

        return $this;
    }

    /**
     * Get healthPerGroup
     *
     * @return boolean
     */
    public function getHealthPerGroup()
    {
        return $this->healthPerGroup;
    }

    /**
     * Set healthPerHero
     *
     * @param boolean $healthPerHero
     *
     * @return Card
     */
    public function setHealthPerHero($healthPerHero)
    {
        $this->healthPerHero = $healthPerHero;

        return $this;
    }

    /**
     * Get healthPerHero
     *
     * @return boolean
     */
    public function getHealthPerHero()
    {
        return $this->healthPerHero;
    }

    /**
     * Set thwart
     *
     * @param integer $thwart
     *
     * @return Card
     */
    public function setThwart($thwart)
    {
        $this->thwart = $thwart;

        return $this;
    }

    /**
     * Get thwart
     *
     * @return integer
     */
    public function getThwart()
    {
        return $this->thwart;
    }

    /**
     * Set thwartCost
     *
     * @param integer $thwartCost
     *
     * @return Card
     */
    public function setThwartCost($thwartCost)
    {
        $this->thwartCost = $thwartCost;

        return $this;
    }

    /**
     * Get thwartCost
     *
     * @return integer
     */
    public function getThwartCost()
    {
        return $this->thwartCost;
    }

    /**
     * Set thwartStar
     *
     * @param boolean $thwartStar
     *
     * @return Card
     */
    public function setThwartStar($thwartStar)
    {
        $this->thwartStar = $thwartStar;
        return $this;
    }

    /**
     * Get thwartStar
     *
     * @return boolean
     */
    public function getThwartStar()
    {
        return $this->thwartStar;
    }

    /**
     * Set attack
     *
     * @param integer $attack
     *
     * @return Card
     */
    public function setAttack($attack)
    {
        $this->attack = $attack;

        return $this;
    }

    /**
     * Get attack
     *
     * @return integer
     */
    public function getAttack()
    {
        return $this->attack;
    }

    /**
     * Set attackCost
     *
     * @param integer $attackCost
     *
     * @return Card
     */
    public function setAttackCost($attackCost)
    {
        $this->attackCost = $attackCost;

        return $this;
    }

    /**
     * Get attackCost
     *
     * @return integer
     */
    public function getAttackCost()
    {
        return $this->attackCost;
    }

    /**
     * Set attackStar
     *
     * @param boolean $attackStar
     *
     * @return Card
     */
    public function setAttackStar($attackStar)
    {
        $this->attackStar = $attackStar;
        return $this;
    }

    /**
     * Get attackStar
     *
     * @return boolean
     */
    public function getAttackStar()
    {
        return $this->attackStar;
    }

    /**
     * Set defense
     *
     * @param integer $defense
     *
     * @return Card
     */
    public function setDefense($defense)
    {
        $this->defense = $defense;

        return $this;
    }

    /**
     * Get defense
     *
     * @return integer
     */
    public function getDefense()
    {
        return $this->defense;
    }

    /**
     * Set defenseCost
     *
     * @param integer $defenseCost
     *
     * @return Card
     */
    public function setDefenseCost($defenseCost)
    {
        $this->defenseCost = $defenseCost;

        return $this;
    }

    /**
     * Get defenseCost
     *
     * @return integer
     */
    public function getDefenseCost()
    {
        return $this->defenseCost;
    }

    /**
     * Set defenseStar
     *
     * @param boolean $defenseStar
     *
     * @return Card
     */
    public function setDefenseStar($defenseStar)
    {
        $this->defenseStar = $defenseStar;
        return $this;
    }

    /**
     * Get defenseStar
     *
     * @return boolean
     */
    public function getDefenseStar()
    {
        return $this->defenseStar;
    }

    /**
     * Set recover
     *
     * @param integer $recover
     *
     * @return Card
     */
    public function setRecover($recover)
    {
        $this->recover = $recover;

        return $this;
    }

    /**
     * Get recover
     *
     * @return integer
     */
    public function getRecover()
    {
        return $this->recover;
    }

    /**
     * Set recoverCost
     *
     * @param integer $recoverCost
     *
     * @return Card
     */
    public function setRecoverCost($recoverCost)
    {
        $this->recoverCost = $recoverCost;

        return $this;
    }

    /**
     * Get recoverCost
     *
     * @return integer
     */
    public function getRecoverCost()
    {
        return $this->recoverCost;
    }

    /**
     * Set recoverStar
     *
     * @param boolean $recoverStar
     *
     * @return Card
     */
    public function setRecoverStar($recoverStar)
    {
        $this->recoverStar = $recoverStar;
        return $this;
    }

    /**
     * Get recoverStar
     *
     * @return boolean
     */
    public function getRecoverStar()
    {
        return $this->recoverStar;
    }

    /**
     * @var integer
     */
    private $scheme;

    /**
     * @var boolean
     */
    private $schemeStar;

    /**
     * Set scheme
     *
     * @param integer $scheme
     *
     * @return Card
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get scheme
     *
     * @return integer
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Set schemeStar
     *
     * @param boolean $schemeStar
     *
     * @return Card
     */
    public function setSchemeStar($schemeStar)
    {
        $this->schemeStar = $schemeStar;
        return $this;
    }

    /**
     * Get schemeStar
     *
     * @return boolean
     */
    public function getSchemeStar()
    {
        return $this->schemeStar;
    }

    /**
     * @var integer
     */
    private $setPosition;

    /**
     * Set setPosition
     *
     * @param integer $setPosition
     *
     * @return Card
     */
    public function setSetPosition($setPosition)
    {
        $this->setPosition = $setPosition;

        return $this;
    }

    /**
     * Get setPosition
     *
     * @return integer
     */
    public function getSetPosition()
    {
        return $this->setPosition;
    }
    /**
     * @var \AppBundle\Entity\Cardset
     */
    private $card_set;


    /**
     * Set cardSet
     *
     * @param \AppBundle\Entity\Cardset $cardSet
     *
     * @return Card
     */
    public function setCardSet(\AppBundle\Entity\Cardset $cardSet = null)
    {
        $this->card_set = $cardSet;

        return $this;
    }

    /**
     * Get cardSet
     *
     * @return \AppBundle\Entity\Cardset
     */
    public function getCardSet()
    {
        return $this->card_set;
    }
    /**
     * @var integer
     */
    private $handSize;


    /**
     * Set handSize
     *
     * @param integer $handSize
     *
     * @return Card
     */
    public function setHandSize($handSize)
    {
        $this->handSize = $handSize;

        return $this;
    }

    /**
     * Get handSize
     *
     * @return integer
     */
    public function getHandSize()
    {
        return $this->handSize;
    }

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var boolean
     */
    private $boostStar;

    /**
     * Set boost
     *
     * @param integer $boost
     *
     * @return Card
     */
    public function setBoost($boost)
    {
        $this->boost = $boost;

        return $this;
    }

    /**
     * Get boost
     *
     * @return integer
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * Set boostStar
     *
     * @param boolean $boostStar
     *
     * @return Card
     */
    public function setBoostStar($boostStar)
    {
        $this->boostStar = $boostStar;
        return $this;
    }

    /**
     * Get boostStar
     *
     * @return boolean
     */
    public function getBoostStar()
    {
        return $this->boostStar;
    }

    /**
     * @var boolean
     */
    private $baseThreatFixed;

    /**
     * @var boolean
     */
    private $baseThreatPerGroup;

    /**
     * @var integer
     */
    private $escalationThreat;

    /**
     * @var boolean
     */
    private $escalationThreatFixed;

    /**
     * @var boolean
     */
    private $escalationThreatStar;

    /**
     * @var integer
     */
    private $threat;

    /**
     * @var boolean
     */
    private $threatFixed;

    /**
     * @var boolean
     */
    private $threatPerGroup;

    /**
     * @var boolean
     */
    private $threatStar;


    /**
     * Set baseThreat
     *
     * @param integer $baseThreat
     *
     * @return Card
     */
    public function setBaseThreat($baseThreat)
    {
        $this->baseThreat = $baseThreat;

        return $this;
    }

    /**
     * Get baseThreat
     *
     * @return integer
     */
    public function getBaseThreat()
    {
        return $this->baseThreat;
    }

    /**
     * Set baseThreatFixed
     *
     * @param boolean $baseThreatFixed
     *
     * @return Card
     */
    public function setBaseThreatFixed($baseThreatFixed)
    {
        $this->baseThreatFixed = $baseThreatFixed;

        return $this;
    }

    /**
     * Get baseThreatFixed
     *
     * @return boolean
     */
    public function getBaseThreatFixed()
    {
        return $this->baseThreatFixed;
    }

    /**
     * Set baseThreatPerGroup
     *
     * @param boolean $baseThreatPerGroup
     *
     * @return Card
     */
    public function setBaseThreatPerGroup($baseThreatPerGroup)
    {
        $this->baseThreatPerGroup = $baseThreatPerGroup;

        return $this;
    }

    /**
     * Get baseThreatPerGroup
     *
     * @return boolean
     */
    public function getBaseThreatPerGroup()
    {
        return $this->baseThreatPerGroup;
    }

    /**
     * Set escalationThreat
     *
     * @param integer $escalationThreat
     *
     * @return Card
     */
    public function setEscalationThreat($escalationThreat)
    {
        $this->escalationThreat = $escalationThreat;

        return $this;
    }

    /**
     * Get escalationThreat
     *
     * @return integer
     */
    public function getEscalationThreat()
    {
        return $this->escalationThreat;
    }

    /**
     * Set escalationThreatFixed
     *
     * @param boolean $escalationThreatFixed
     *
     * @return Card
     */
    public function setEscalationThreatFixed($escalationThreatFixed)
    {
        $this->escalationThreatFixed = $escalationThreatFixed;

        return $this;
    }

    /**
     * Get escalationThreatFixed
     *
     * @return boolean
     */
    public function getEscalationThreatFixed()
    {
        return $this->escalationThreatFixed;
    }

    /**
     * Set escalationThreatStar
     *
     * @param boolean $escalationThreatStar
     *
     * @return Card
     */
    public function setEscalationThreatStar($escalationThreatStar)
    {
        $this->escalationThreatStar = $escalationThreatStar;
        return $this;
    }

    /**
     * Get escalationThreatStar
     *
     * @return boolean
     */
    public function getEscalationThreatStar()
    {
        return $this->escalationThreatStar;
    }

    /**
     * Set threat
     *
     * @param integer $threat
     *
     * @return Card
     */
    public function setThreat($threat)
    {
        $this->threat = $threat;

        return $this;
    }

    /**
     * Get threat
     *
     * @return integer
     */
    public function getThreat()
    {
        return $this->threat;
    }

    /**
     * Set threatFixed
     *
     * @param boolean $threatFixed
     *
     * @return Card
     */
    public function setThreatFixed($threatFixed)
    {
        $this->threatFixed = $threatFixed;

        return $this;
    }

    /**
     * Get threatFixed
     *
     * @return boolean
     */
    public function getThreatFixed()
    {
        return $this->threatFixed;
    }

    /**
     * Set threatPerGroup
     *
     * @param boolean $threatPerGroup
     *
     * @return Card
     */
    public function setThreatPerGroup($threatPerGroup)
    {
        $this->threatPerGroup = $threatPerGroup;

        return $this;
    }

    /**
     * Get threatPerGroup
     *
     * @return boolean
     */
    public function getThreatPerGroup()
    {
        return $this->threatPerGroup;
    }

    /**
     * Set threatStar
     *
     * @param boolean $threatStar
     *
     * @return Card
     */
    public function setThreatStar($threatStar)
    {
        $this->threatStar = $threatStar;
        return $this;
    }

    /**
     * Get threatStar
     *
     * @return boolean
     */
    public function getThreatStar()
    {
        return $this->threatStar;
    }

    /**
     * @var integer
     */
    private $schemeCrisis;

    /**
     * @var integer
     */
    private $schemeAcceleration;

    /**
     * @var integer
     */
    private $schemeAmplify;

    /**
     * @var integer
     */
    private $schemeHazard;


    /**
     * Set schemeCrisis
     *
     * @param integer $schemeCrisis
     *
     * @return Card
     */
    public function setSchemeCrisis($schemeCrisis)
    {
        $this->schemeCrisis = $schemeCrisis;

        return $this;
    }

    /**
     * Get schemeCrisis
     *
     * @return integer
     */
    public function getSchemeCrisis()
    {
        return $this->schemeCrisis;
    }

    /**
     * Set schemeAcceleration
     *
     * @param integer $schemeAcceleration
     *
     * @return Card
     */
    public function setSchemeAcceleration($schemeAcceleration)
    {
        $this->schemeAcceleration = $schemeAcceleration;

        return $this;
    }

    /**
     * Get schemeAcceleration
     *
     * @return integer
     */
    public function getSchemeAcceleration()
    {
        return $this->schemeAcceleration;
    }

    /**
     * Set schemeAmplify
     *
     * @param integer $schemeAmplify
     *
     * @return Card
     */
    public function setSchemeAmplify($schemeAmplify)
    {
        $this->schemeAmplify = $schemeAmplify;

        return $this;
    }

    /**
     * Get schemeAmplify
     *
     * @return integer
     */
    public function getSchemeAmplify()
    {
        return $this->schemeAmplify;
    }

    /**
     * Set schemeHazard
     *
     * @param integer $schemeHazard
     *
     * @return Card
     */
    public function setSchemeHazard($schemeHazard)
    {
        $this->schemeHazard = $schemeHazard;

        return $this;
    }

    /**
     * Get schemeHazard
     *
     * @return integer
     */
    public function getSchemeHazard()
    {
        return $this->schemeHazard;
    }

    /**
     * Set errata
     *
     * @param string $errata
     *
     * @return Card
     */
    public function setErrata($errata)
    {
        $this->errata = $errata;
        return $this;
    }

    /**
     * Get errata
     *
     * @return string
     */
    public function getErrata()
    {
        return $this->errata;
    }
}
