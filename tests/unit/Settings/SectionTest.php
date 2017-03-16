<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\B2shareBridge\ests\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

class SectionTest extends TestCase  {
    /** @var \OCA\B2shareBridge\Settings\Section */
    private $section;
    /** @var  IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    public function setUp() {
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->section = new \OCA\B2shareBridge\Settings\AdminSection();

        return parent::setUp();
    }

    public function testGetId() {
        $this->assertSame('b2sharebridge', $this->section->getID());
    }

    public function testGetName() {
        $this->assertSame('EUDAT', $this->section->getName());
    }

    public function testGetPriority() {
        $this->assertSame(75, $this->section->getPriority());
    }

    public function testGetIcon() {
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
