<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Cardset;
use AppBundle\Entity\Pack;
use AppBundle\Entity\Card;

class ImportStdCommand extends ContainerAwareCommand
{
	/* @var $em EntityManager */
	private $em;

	private $links = [];

	/* @var $output OutputInterface */
	private $output;

	private $collections = [];

	protected function configure()
	{
		$this
		->setName('app:import:std')
		->setDescription('Import cards data file in json format from a copy of https://github.com/zzorba/marvels-json-data')
		->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to the repository'
				);

		$this->addOption(
				'player',
				null,
				InputOption::VALUE_NONE,
				'Only player cards'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path = $input->getArgument('path');
		$player_only = $input->getOption('player');
		$this->em = $this->getContainer()->get('doctrine')->getEntityManager();
		$this->output = $output;

		/* @var $helper \Symfony\Component\Console\Helper\QuestionHelper */
		$helper = $this->getHelper('question');
		//$this->loadCollection('Card');
		// factions

		$output->writeln("Importing Classes...");
		$factionsFileInfo = $this->getFileInfo($path, 'factions.json');
		$imported = $this->importFactionsJsonFile($factionsFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Faction');
		$this->collections['Faction2'] = $this->collections['Faction'];
		$output->writeln("Done.");

		// types

		$output->writeln("Importing Types...");
		$typesFileInfo = $this->getFileInfo($path, 'types.json');
		$imported = $this->importTypesJsonFile($typesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Type');
		$output->writeln("Done.");

		// subtypes

		$output->writeln("Importing SubTypes...");
		$subtypesFileInfo = $this->getFileInfo($path, 'subtypes.json');
		$imported = $this->importSubtypesJsonFile($subtypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Subtype');
		$output->writeln("Done.");

	// packtypes

		$output->writeln("Importing PackTypes...");
		$packtypesFileInfo = $this->getFileInfo($path, 'packtypes.json');
		$imported = $this->importPacktypesJsonFile($packtypesFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Packtype');
		$output->writeln("Done.");


		// card sets

		$output->writeln("Importing Card Sets...");
		$setsFileInfo = $this->getFileInfo($path, 'sets.json');
		$imported = $this->importCardSetsJsonFile($setsFileInfo);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Cardset');
		$output->writeln("Done.");

		// second, packs

		$output->writeln("Importing Packs...");
		$packsFileInfo = $this->getFileInfo($path, 'packs.json');
		$imported = $this->importPacksJsonFile($packsFileInfo);
		$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		$this->loadCollection('Pack');
		$output->writeln("Done.");

		// third, cards

		$output->writeln("Importing Cards...");
		$imported = [];
		// get subdirs of files and do this for each file
		$scanned_directory = array_diff(scandir($path."/pack"), array('..', '.'));
		$fileSystemIterator = $this->getFileSystemIterator($path."pack/");
		foreach ($fileSystemIterator as $fileinfo) {
			$imported = array_merge($imported, $this->importCardsJsonFile($fileinfo, $player_only));
		}
		if(count($imported)) {
			$question = new ConfirmationQuestion("Do you confirm? (Y/n) ", true);
			if(!$helper->ask($input, $output, $question)) {
				die();
			}
		}
		$this->em->flush();
		// reload the cards so we can link cards
		if ($this->links && count($this->links) > 0){
			$output->writeln("Resolving Links");
			$this->loadCollection('Card');
			foreach($this->links as $link){
				$card = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['card_id']]);
				$target = $this->em->getRepository('AppBundle\\Entity\\Card')->findOneBy(['code' => $link['target_id']]);
				if ($card && $target){
					$card->setLinkedTo($target);
					$target->setLinkedTo();
					$output->writeln("Importing link between ".$card->getName()." and ".$target->getName().".");
				}
			}
			$this->em->flush();
		}

		$output->writeln("Done.");

	}

	protected function importFactionsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$faction = $this->getEntityFromData('AppBundle\\Entity\\Faction', $data, [
					'code',
					'name',
					'is_primary'
			], [], []);
			if($faction) {
				$result[] = $faction;
				$this->em->persist($faction);
			}
		}

		return $result;
	}

	protected function importTypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Type', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importSubtypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Subtype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importPacktypesJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Packtype', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importCardSetsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Cardset', $data, [
					'code',
					'name'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}


	protected function importScenariosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Scenario', $data, [
				'code',
				'name'
			], [
				'campaign_code'
			], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importCampaignsJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$list = $this->getDataFromFile($fileinfo);
		foreach($list as $data)
		{
			$type = $this->getEntityFromData('AppBundle\\Entity\\Campaign', $data, [
					'code',
					'name',
					'size'
			], [], []);
			if($type) {
				$result[] = $type;
				$this->em->persist($type);
			}
		}

		return $result;
	}

	protected function importTaboosJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$taboosData = $this->getDataFromFile($fileinfo);
		foreach($taboosData as $tabooData) {
			$tabooData['cards'] = json_encode($tabooData['cards']);
			$taboo = $this->getEntityFromData('AppBundle\Entity\Taboo', $tabooData, [
					'code',
					'name',
					'date_start',
					'active',
					'cards'
			], [], []);
			if($taboo) {
				$result[] = $taboo;
				$this->em->persist($taboo);
			}
		}

		return $result;
	}


	protected function importPacksJsonFile(\SplFileInfo $fileinfo)
	{
		$result = [];

		$packsData = $this->getDataFromFile($fileinfo);
		foreach($packsData as $packData) {
			$pack = $this->getEntityFromData('AppBundle\Entity\Pack', $packData, [
					'code',
					'name',
					'position',
					'size',
					'date_release'
			], [
				'pack_type_code'
			], [
					'cgdb_id'
			]);
			if($pack) {
				$result[] = $pack;
				$this->em->persist($pack);
			}
		}

		return $result;
	}

	protected function importCardsJsonFile(\SplFileInfo $fileinfo, $special="")
	{
		$result = [];

		$code = $fileinfo->getBasename('.json');
		if (stristr($code, "_encounter") !== FALSE && $special){
			return $result;
		}
		$code = str_replace("_encounter", "", $code);

		$pack = $this->em->getRepository('AppBundle:Pack')->findOneBy(['code' => $code]);
		if(!$pack) throw new \Exception("Unable to find Pack [$code]");

		$cardsData = $this->getDataFromFile($fileinfo);
		foreach($cardsData as $cardData) {
			$card = $this->getEntityFromData('AppBundle\Entity\Card', $cardData, [
					'code',
					'position',
					'quantity',
					'name'
			], [
					'faction_code',
					'faction2_code',
					'pack_code',
					'type_code',
					'subtype_code',
					'set_code',
					'back_card_code',
					'front_card_code'
			], [
					'deck_limit',
					'set_position',
					'illustrator',
					'flavor',
					'traits',
					'text',
					'cost',
					'resource_physical',
					'resource_mental',
					'resource_energy',
					'resource_wild',
					'health',
					'restrictions',
					'deck_options',
					'deck_requirements',
					'subname',
					'back_text',
					'back_flavor',
					'back_name',
					'double_sided',
					'stage',
					'is_unique',
					'health_per_hero',
					'hidden'

			]);
			if($card) {
				if ($card->getName()){
					$card->setRealName($card->getName());
				}
				if ($card->getTraits()){
					$card->setRealTraits($card->getTraits());
				}
				if ($card->getText()){
					$card->setRealText($card->getText());
				}
				$result[] = $card;
				$this->em->persist($card);
				if (isset($cardData['back_link'])){
					// if we have back link, store the reference here
					$this->links[] = ['card_id'=> $card->getCode(), 'target_id'=> $cardData['back_link']];
				}
			}
		}

		return $result;
	}

	protected function copyFieldValueToEntity($entity, $entityName, $fieldName, $newJsonValue)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		$type = $metadata->fieldMappings[$fieldName]['type'];

		// new value, by default what json gave us is the correct typed value
		$newTypedValue = $newJsonValue;

		// current value, by default the json, serialized value is the same as what's in the entity
		$getter = 'get'.ucfirst($fieldName);
		$currentJsonValue = $currentTypedValue = $entity->$getter();

		// if the field is a data, the default assumptions above are wrong
		if(in_array($type, ['date', 'datetime'])) {
			if($newJsonValue !== null) {
				$newTypedValue = new \DateTime($newJsonValue);
			}
			if($currentTypedValue !== null) {
				switch($type) {
					case 'date': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d');
						break;
					}
					case 'datetime': {
						$currentJsonValue = $currentTypedValue->format('Y-m-d H:i:s');
					}
				}
			}
		}

		$different = ($currentJsonValue !== $newJsonValue);
		if($different) {
			//print_r(gettype($currentJsonValue));
			//print_r(gettype($newJsonValue));
			if (is_array($currentJsonValue) || is_array($newJsonValue)){
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info>");
			} else {
				$this->output->writeln("Changing the <info>$fieldName</info> of <info>".$entity->toString()."</info> ($currentJsonValue => $newJsonValue)");
			}
			$setter = 'set'.ucfirst($fieldName);
			$entity->$setter($newTypedValue);
		}
	}

	protected function copyKeyToEntity($entity, $entityName, $data, $key, $isMandatory = TRUE)
	{
		$metadata = $this->em->getClassMetadata($entityName);
		if(!key_exists($key, $data)) {
			if($isMandatory) {
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			} else {
				$data[$key] = null;
			}
		}

		$value = $data[$key];
		if ($key == "is_unique"){
			if (!$value){
				$value = false;
			}
		}
		if ($key == "hidden"){
			if (!$value){
				$value = false;
			}
		}

		if ($key == "deck_options"){
			if ($value){
				$value = json_encode($value);
			}
		}

		if ($key == "deck_options" && $value){
			//print_r($value);
		}

		if ($key == "health_per_hero" || $key == "is_unique"){
			if ($value){
				//echo $key." ".$value."\n";
			}
		}
		if(!key_exists($key, $metadata->fieldNames)) {
			throw new \Exception("Missing column [$key] in entity ".$entityName);
		}
		$fieldName = $metadata->fieldNames[$key];

		$this->copyFieldValueToEntity($entity, $entityName, $fieldName, $value);
	}

	protected function getEntityFromData($entityName, $data, $mandatoryKeys, $foreignKeys, $optionalKeys)
	{
		if(!key_exists('code', $data)) {
			throw new \Exception("Missing key [code] in ".json_encode($data));
		}

		$entity = $this->em->getRepository($entityName)->findOneBy(['code' => $data['code']]);

		if(!$entity) {
			// if we cant find it, try more complex methods just to check
			// the only time this should work is if the existing name also has an _ meaning it was temporary.

			if (!$entity){
				$entity = new $entityName();
			}
		}
		$orig = $entity->serialize();
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($entity, $entityName, $data, $key, FALSE);
		}

		foreach($foreignKeys as $key) {
			$foreignEntityShortName = ucfirst(str_replace('_code', '', $key));
			if ($key === "front_card_code"){
				$foreignEntityShortName = "Card";
			}
			if ($key === "set_code") {
				$foreignEntityShortName = "Cardset";
			}
			if ($key === "pack_type_code") {
				$foreignEntityShortName = 'Packtype';
			}

			if(!key_exists($key, $data)) {
				// optional links to other tables
				if ($key === "faction2_code" || $key === "subtype_code" || $key === "set_code" || $key === "back_card_code" || $key === "front_card_code"){
					continue;
				}
				throw new \Exception("Missing key [$key] in ".json_encode($data));
			}

			$foreignCode = $data[$key];
			if(!key_exists($foreignEntityShortName, $this->collections)) {
				throw new \Exception("No collection for [$foreignEntityShortName] in ".json_encode($data));
			}

			if (!$foreignCode){
				continue;
			}
			//echo "\n";
			//print("hvor mange ".count($this->collections[$foreignEntityShortName]));
			if(!key_exists($foreignCode, $this->collections[$foreignEntityShortName])) {
				throw new \Exception("Invalid code [$foreignCode] for key [$key] in ".json_encode($data));
			}
			$foreignEntity = $this->collections[$foreignEntityShortName][$foreignCode];

			$getter = 'get'.$foreignEntityShortName;

			if(!$entity->$getter() || $entity->$getter()->getId() !== $foreignEntity->getId()) {
				$this->output->writeln("Changing the <info>$key</info> of <info>".$entity->toString()."</info>");
				$setter = 'set'.$foreignEntityShortName;
				$entity->$setter($foreignEntity);
			}
		}

		// special case for Card
		if($entityName === 'AppBundle\Entity\Card') {
			// calling a function whose name depends on the type_code
			$cleanName = $entity->getType()->getName();
			if ($cleanName == "Alter-Ego") {
				$cleanName = "AlterEgo";
			}
			if ($cleanName == "Side Scheme") {
				$cleanName = "SideScheme";
			}
			if ($cleanName == "Main Scheme") {
				$cleanName = "MainScheme";
			}
			$functionName = 'import' . $cleanName . 'Data';
			$this->$functionName($entity, $data);
		}

		if($entity->serialize() !== $orig || (isset($data['back_link']) && (!$entity->getLinkedTo() || $entity->getLinkedTo()->getCode() != $data['back_link']) )) return $entity;

	}

	protected function importSupportData(Card $card, $data)
	{

	}

	protected function importUpgradeData(Card $card, $data)
	{

	}

	protected function importObligationData(Card $card, $data)
	{
		$optionalKeys = [
			'boost',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importHeroData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
			'hand_size',
			'attack',
			'defense',
			'thwart',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importAlterEgoData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
			'hand_size',
			'recover',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importAllyData(Card $card, $data)
	{
		$mandatoryKeys = [
			'health',
			'attack',
			'thwart',
		];
		$optionalKeys = [
			'attack_cost',
			'thwart_cost',
		];
		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}


	protected function importMinionData(Card $card, $data)
	{
		$mandatoryKeys = [
				'attack',
				'scheme',
				'health',
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		$optionalKeys = [
				'boost',
				'boost_text',
				'attack_text',
				'scheme_text',
				'health_per_hero',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importEnvironmentData(Card $card, $data)
	{

	}

	protected function importSideSchemeData(Card $card, $data)
	{
		$mandatoryKeys = [
				'base_threat',
		];
		$optionalKeys = [
				'base_threat_fixed',
				'escalation_threat',
				'escalation_threat_fixed',
				'boost',
				'boost_text',
				'scheme_acceleration',
				'scheme_crisis',
				'scheme_hazard',
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importMainSchemeData(Card $card, $data)
	{
		$mandatoryKeys = [
				'threat',
				'base_threat',
		];
		$optionalKeys = [
				'threat_fixed',
				'base_threat_fixed',
				'escalation_threat',
				'escalation_threat_fixed',
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importEventData(Card $card, $data)
	{
		$mandatoryKeys = [
				'cost'
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}
	}

	protected function importResourceData(Card $card, $data)
	{

	}

	protected function importVillainData(Card $card, $data)
	{
		$mandatoryKeys = [
				'attack',
				'scheme',
				'stage',
				'health',
		];
		$optionalKeys = [
				'attack_text',
				'scheme_text',
				'health_per_hero',
		];

		foreach($mandatoryKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, TRUE);
		}

		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importTreacheryData(Card $card, $data)
	{
		$optionalKeys = [
				'boost',
				'boost_text'
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function importAttachmentData(Card $card, $data)
	{
		$optionalKeys = [
				'boost',
				'boost_text',
				'attack',
				'attack_text',
				'scheme',
				'scheme_text',
		];
		foreach($optionalKeys as $key) {
			$this->copyKeyToEntity($card, 'AppBundle\Entity\Card', $data, $key, FALSE);
		}
	}

	protected function getDataFromFile(\SplFileInfo $fileinfo)
	{

		$file = $fileinfo->openFile('r');
		$file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

		$lines = [];
		foreach($file as $line) {
			if($line !== false) $lines[] = $line;
		}
		$content = implode('', $lines);

		$data = json_decode($content, true);

		if($data === null) {
			throw new \Exception("File [".$fileinfo->getPathname()."] contains incorrect JSON (error code ".json_last_error().")");
		}

		return $data;
	}

	protected function getFileInfo($path, $filename)
	{
		$fs = new Filesystem();

		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}

		$filepath = "$path/$filename";

		if(!$fs->exists($filepath)) {
			throw new \Exception("No $filename file found at [$path]");
		}

		return new \SplFileInfo($filepath);
	}

	protected function getFileSystemIterator($path)
	{
		$fs = new Filesystem();

		if(!$fs->exists($path)) {
			throw new \Exception("No repository found at [$path]");
		}

		$iterator = new \GlobIterator("$path/*.json");

		if(!$iterator->count()) {
			throw new \Exception("No json file found at [$path]");
		}

		return $iterator;
	}

	protected function loadCollection($entityShortName)
	{
		$this->collections[$entityShortName] = [];

		$entities = $this->em->getRepository('AppBundle:'.$entityShortName)->findAll();
		//echo $entityShortName."\n";
		foreach($entities as $entity) {
			$this->collections[$entityShortName][$entity->getCode()] = $entity;
			//echo $entity->getCode()."\n";
		}
	}

}
