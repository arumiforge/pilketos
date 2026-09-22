<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * @internal
 */
final class HealthTest extends CIUnitTestCase
{
    public function testIsDefinedAppPath(): void
    {
        $this->assertTrue(defined('APPPATH'));
    }

    public function testBaseUrlIsValid(): void
    {
        $this->assertTrue(service('validation')->check((new App())->baseURL, 'valid_url'));
    }

    public function testAppUsesJakartaTimezoneAndIndonesianLocale(): void
    {
        $config = new App();

        $this->assertSame('Asia/Jakarta', $config->appTimezone);
        $this->assertSame('id', $config->defaultLocale);
        $this->assertSame('', $config->indexPage);
    }
}
