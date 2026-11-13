<?php

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Card;
use AppBundle\Entity\Type;
use AppBundle\Helper\DeckValidationHelper;
use AppBundle\Services\CardsData;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

class CardsDataTest extends TestCase
{
    /** @var CardsData */
    private $cardsData;

    /** @var string */
    private $tempDir;

    /** @var string */
    private $cardsDir;

    protected function setUp()
    {
        $this->tempDir = sys_get_temp_dir() . '/cards_data_test_' . uniqid();
        $this->cardsDir = $this->tempDir . '/web/bundles/cards';
        mkdir($this->cardsDir, 0777, true);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getFieldNames')->willReturn(['code', 'name', 'doubleSided', 'text', 'flavor']);
        $metadata->method('getAssociationMappings')->willReturn([
            'type' => ['isOwningSide' => true],
            'linked_to' => ['isOwningSide' => true]
        ]);
        $metadata->method('getTypeOfField')->willReturnMap([
            ['code', 'string'],
            ['name', 'string'],
            ['doubleSided', 'boolean'],
            ['text', 'string'],
            ['flavor', 'string']
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->method('getClassMetadata')->with('AppBundle:Card')->willReturn($metadata);

        $doctrine = $this->createMock(Registry::class);
        $doctrine->method('getManager')->willReturn($em);

        $router = $this->createMock(Router::class);
        $router->method('generate')->willReturn('http://localhost/card/test');

        $assetsHelper = $this->createMock(Packages::class);
        $assetsHelper->method('getUrl')->willReturnCallback(function ($path) {
            return '/' . $path;
        });

        $requestStack = $this->createMock(RequestStack::class);
        $deckValidationHelper = $this->createMock(DeckValidationHelper::class);

        // CardsData expects $rootDir to be such that $rootDir . '/../web' points to the web folder
        $mockRootDir = $this->tempDir . '/app';
        mkdir($mockRootDir, 0777, true);

        $this->cardsData = new CardsData(
            $doctrine,
            $requestStack,
            $router,
            $assetsHelper,
            $deckValidationHelper,
            $mockRootDir
        );
    }

    protected function tearDown()
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function createFakeImage($filename)
    {
        file_put_contents($this->cardsDir . '/' . $filename, 'fake image');
    }

    private function createCard($code, $typeCode, $doubleSided = true)
    {
        $type = new Type();
        $type->setCode($typeCode);
        $type->setName(ucwords(str_replace('_', ' ', $typeCode)));

        $card = new Card();
        $card->setCode($code);
        $card->setName('Test Card ' . $code);
        $card->setText('Card text for ' . $code);
        $card->setFlavor('Flavor text for ' . $code);
        $card->setType($type);
        $card->setDoubleSided($doubleSided);

        return $card;
    }

    /**
     * Survey pattern: <base>a.jpg and <base>b.png (e.g. 02004 Hostile Takeover from Green Goblin)
     * - 02004a.jpg is printed side 1A
     * - 02004b.png is printed side 1B
     * On the site, front panel shows 1B text and back panel shows 1A text.
     * Expected: imagesrc gets 1B (.b.png), backimagesrc gets 1A (a.jpg).
     */
    public function testHostileTakeoverPattern_02004()
    {
        $code = '02004';
        $this->createFakeImage($code . 'a.jpg');
        $this->createFakeImage($code . 'b.png');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . 'b.png', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'a.jpg', $info['backimagesrc']);
    }

    /**
     * Survey pattern: <base>.png and <base>b.png (Core Set, e.g. 01097 The Break-In!)
     * - 01097.png is printed side 1B (front panel)
     * - 01097b.png is printed side 1A (back panel)
     * Expected: imagesrc gets 01097.png, backimagesrc gets 01097b.png.
     */
    public function testCoreSetBreakInPattern_01097()
    {
        $code = '01097';
        $this->createFakeImage($code . '.png');
        $this->createFakeImage($code . 'b.png');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . '.png', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'b.png', $info['backimagesrc']);
    }

    /**
     * Survey pattern: <base>a.jpg and <base>b.jpg (Age of Apocalypse, e.g. 45062 Hunting Gene Traitors)
     * - AoA main schemes only exist as .jpg files.
     * Expected: imagesrc gets 45062b.jpg (1B), backimagesrc gets 45062a.jpg (1A).
     */
    public function testAgeOfApocalypseJpgOnlyPattern_45062()
    {
        $code = '45062';
        $this->createFakeImage($code . 'a.jpg');
        $this->createFakeImage($code . 'b.jpg');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . 'b.jpg', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'a.jpg', $info['backimagesrc']);
    }

    /**
     * Survey pattern: <base>a.png and <base>b.png (e.g. 16061 Terrestrial Invasion from Galaxy's Most Wanted)
     * Expected: imagesrc gets 16061b.png (1B), backimagesrc gets 16061a.png (1A).
     */
    public function testGalaxysMostWantedPngPairPattern_16061()
    {
        $code = '16061';
        $this->createFakeImage($code . 'a.png');
        $this->createFakeImage($code . 'b.png');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . 'b.png', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'a.png', $info['backimagesrc']);
    }

    /**
     * Survey pattern: <base>a.jpg only (e.g. 11007 Kang's Arrival from Once and Future Kang)
     * Only side 1A has an image.
     * Expected: imagesrc is null (no 1B art), backimagesrc gets 11007a.jpg (1A art matches 1A text).
     */
    public function testOnceAndFutureKangOnlyASidePattern_11007()
    {
        $code = '11007';
        $this->createFakeImage($code . 'a.jpg');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertNull($info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'a.jpg', $info['backimagesrc']);
    }

    /**
     * Survey pattern: <base>.png only (e.g. 21074 Attack on Knowhere from Mad Titan's Shadow)
     * Expected: imagesrc gets 21074.png, backimagesrc is null.
     */
    public function testMadTitansShadowOnlyPlainPattern_21074()
    {
        $code = '21074';
        $this->createFakeImage($code . '.png');

        $card = $this->createCard($code, 'main_scheme', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . '.png', $info['imagesrc']);
        $this->assertNull($info['backimagesrc']);
    }

    /**
     * Non-main scheme double-sided card (Hero / Alter-Ego, e.g. Spider-Man 01001)
     * - Front side: <code>.png
     * - Back side: <code>b.png
     * Expected: imagesrc gets front (01001.png), backimagesrc gets back (01001b.png).
     */
    public function testHeroDoubleSidedNormalConvention()
    {
        $code = '01001';
        $this->createFakeImage($code . '.png');
        $this->createFakeImage($code . 'b.png');

        $card = $this->createCard($code, 'hero', true);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . '.png', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/' . $code . 'b.png', $info['backimagesrc']);
        $this->assertTrue($info['double_sided']);
    }

    /**
     * Single-sided card (e.g. Ally, Event, Upgrade)
     * Expected: imagesrc gets plain, backimagesrc is null, double_sided is false.
     */
    public function testSingleSidedCard()
    {
        $code = '01002';
        $this->createFakeImage($code . '.png');

        $card = $this->createCard($code, 'ally', false);
        $info = $this->cardsData->getCardInfo($card);

        $this->assertEquals('/bundles/cards/' . $code . '.png', $info['imagesrc']);
        $this->assertNull($info['backimagesrc']);
        $this->assertFalse($info['double_sided']);
    }

    /**
     * Tri-fold / 3-form hero cards:
     * - Ant-Man: 12001a (Tiny) / 12001b (Scott Lang) / 12001c (Giant)
     * - Wasp: 13001a (Tiny) / 13001b (Nadia Van Dyne) / 13001c (Giant)
     * - Angel: 42001a (Angel) / 42001b (Warren Worthington III) / 42001c (Archangel)
     *
     * In the database and API:
     * - Each form is a separate card entity with double_sided = false.
     * - <codeA> (Hero form 1) has a linked_to relation to <codeB> (Alter-Ego).
     * - <codeC> (Hero form 2, e.g. Archangel 42001c) is a standalone entity with its own image.
     * Expected: Each card resolves its own distinct image (42001a.png, 42001b.png, 42001c.png),
     * and backimagesrc remains null on all three.
     */
    public function testAntManAndAngelTriFoldHeroes()
    {
        // Test Ant-Man (12001)
        $this->createFakeImage('12001a.png');
        $this->createFakeImage('12001b.png');
        $this->createFakeImage('12001c.png');

        $antB = $this->createCard('12001b', 'alter_ego', false);
        $antA = $this->createCard('12001a', 'hero', false);
        $antA->setLinkedTo($antB);

        $antC = $this->createCard('12001c', 'hero', false);

        $infoAntA = $this->cardsData->getCardInfo($antA);
        $this->assertEquals('/bundles/cards/12001a.png', $infoAntA['imagesrc']);
        $this->assertNull($infoAntA['backimagesrc']);
        $this->assertFalse($infoAntA['double_sided']);

        $this->assertArrayHasKey('linked_card', $infoAntA);
        $this->assertEquals('/bundles/cards/12001b.png', $infoAntA['linked_card']['imagesrc']);
        $this->assertNull($infoAntA['linked_card']['backimagesrc']);

        $infoAntC = $this->cardsData->getCardInfo($antC);
        $this->assertEquals('/bundles/cards/12001c.png', $infoAntC['imagesrc']);
        $this->assertNull($infoAntC['backimagesrc']);

        // Test Angel / Archangel (42001)
        $this->createFakeImage('42001a.png');
        $this->createFakeImage('42001b.png');
        $this->createFakeImage('42001c.png');

        $angelB = $this->createCard('42001b', 'alter_ego', false);
        $angelA = $this->createCard('42001a', 'hero', false);
        $angelA->setLinkedTo($angelB);

        $angelC = $this->createCard('42001c', 'hero', false);

        $infoAngelA = $this->cardsData->getCardInfo($angelA);
        $this->assertEquals('/bundles/cards/42001a.png', $infoAngelA['imagesrc']);
        $this->assertNull($infoAngelA['backimagesrc']);
        $this->assertArrayHasKey('linked_card', $infoAngelA);
        $this->assertEquals('/bundles/cards/42001b.png', $infoAngelA['linked_card']['imagesrc']);

        $infoAngelC = $this->cardsData->getCardInfo($angelC);
        $this->assertEquals('/bundles/cards/42001c.png', $infoAngelC['imagesrc']);
        $this->assertNull($infoAngelC['backimagesrc']);
    }

    /**
     * Duplicate card without its own image (e.g. Falcon 23014 -> 03011)
     * Expected: imagesrc falls back to the original card's image.
     */
    public function testDuplicateOfFallsBackToOriginalImage()
    {
        $this->createFakeImage('03011.png');

        $original = $this->createCard('03011', 'ally', false);
        $duplicate = $this->createCard('23014', 'ally', false);
        $duplicate->setDuplicateOf($original);

        $info = $this->cardsData->getCardInfo($duplicate);
        $this->assertEquals('/bundles/cards/03011.png', $info['imagesrc']);
        $this->assertNull($info['backimagesrc']);
    }

    /**
     * Duplicate card that has its own image (e.g. alternate art reprint)
     * Expected: its own image takes precedence over the original card's image.
     */
    public function testDuplicateOfPrefersOwnImageWhenPresent()
    {
        $this->createFakeImage('03011.png');
        $this->createFakeImage('23014.png');

        $original = $this->createCard('03011', 'ally', false);
        $duplicate = $this->createCard('23014', 'ally', false);
        $duplicate->setDuplicateOf($original);

        $info = $this->cardsData->getCardInfo($duplicate);
        $this->assertEquals('/bundles/cards/23014.png', $info['imagesrc']);
        $this->assertNull($info['backimagesrc']);
    }

    /**
     * Double-sided duplicate card without its own images
     * Expected: both imagesrc and backimagesrc fall back to the original card's images.
     */
    public function testDoubleSidedDuplicateOfFallsBackToOriginalImages()
    {
        $this->createFakeImage('01094a.png');
        $this->createFakeImage('01094b.png');

        $original = $this->createCard('01094', 'main_scheme', true);
        $duplicate = $this->createCard('99094', 'main_scheme', true);
        $duplicate->setDuplicateOf($original);

        $info = $this->cardsData->getCardInfo($duplicate);
        $this->assertEquals('/bundles/cards/01094b.png', $info['imagesrc']);
        $this->assertEquals('/bundles/cards/01094a.png', $info['backimagesrc']);
    }
}
