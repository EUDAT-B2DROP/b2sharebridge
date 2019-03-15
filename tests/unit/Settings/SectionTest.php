<?php
/**
 * B2SHAREBRIDGE
 *
 * PHP Version 7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\ests\Settings;

use OCP\IURLGenerator;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

class SectionTest extends TestCase
{
    /**
     * @var \OCA\B2shareBridge\Settings\Section 
     */
    private $section;
    /**
     * @var  IURLGenerator|\PHPUnit\Framework\MockObject\MockObject 
     */
    private $urlGenerator;

    /**
     *  @var IL10N|\PHPUnit_Framework_MockObject_MockObject 
     */
	private $l10n;

    public function setUp() 
    {
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->l10n = $this->createMock(IL10N::class);
        $this->section = new \OCA\B2shareBridge\Settings\AdminSection(urlGenerator, l10n);

        return parent::setUp();
    }

    public function testGetId() 
    {
        $this->assertSame('b2sharebridge', $this->section->getID());
    }

    public function testGetName() 
    {
        $this->assertSame('EUDAT', $this->section->getName());
    }

    public function testGetPriority() 
    {
        $this->assertSame(75, $this->section->getPriority());
    }

    public function testGetIcon() 
    {
        $this->markTestSkipped(
            'We do not have a icon yet.'
        );
        $this->urlGenerator
            ->expects($this->once())
            ->method('imagePath')
            ->with('user_saml', 'app-dark.svg')
            ->willReturn('/apps/user_saml/myicon.svg');
        $this->assertSame('/apps/user_saml/myicon.svg', $this->section->getIcon());
    }
}
