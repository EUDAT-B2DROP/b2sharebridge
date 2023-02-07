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

namespace OCA\B2shareBridge\Tests\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    /**
     * @var \OCA\B2shareBridge\Settings\Admin 
     */
    private $admin;
    /**
     * @var IConfig|\PHPUnit\Framework\MockObject\MockObject 
     */
    private $config;

    public function setUp(): void
    {
        $this->config = $this->createMock(IConfig::class);
        $this->serverMapper = $this->getMockBuilder('OCA\B2shareBridge\Model\ServerMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->admin = new \OCA\B2shareBridge\Settings\Admin(
            $this->config,
            $this->serverMapper
        );
        parent::setUp();
    }

    public function formDataProvider() 
    {
        $params = [
            'max_uploads' => $this->config->getAppValue(
                'b2sharebridge', 'max_uploads'
            ),
            'max_upload_filesize' => $this->config->getAppValue(
                'b2sharebridge', 'max_upload_filesize'
            ),
            'check_ssl' => $this->config->getAppValue(
                'b2sharebridge', 'check_ssl'
            ),
            'servers' => $this->serverMapper->findAll()
        ];
        return $params;
    }

    public function testGetForm()
    {
        //$params = $this->formDataProvider();

        $expected = new TemplateResponse('b2sharebridge', 'settings-admin');
        $this->assertEquals($expected, $this->admin->getForm());
    }

    public function testGetSection() 
    {
        $this->assertSame('b2sharebridge', $this->admin->getSection());
    }

    public function testGetPriority() 
    {
        $this->assertSame(0, $this->admin->getPriority());
    }
}
