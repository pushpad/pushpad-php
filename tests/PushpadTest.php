<?php

use PHPUnit\Framework\TestCase;
use Pushpad\Exception\ConfigurationException;
use Pushpad\Pushpad;

class PushpadTest extends TestCase {
  
  protected function setUp(): void {
    Pushpad::$authToken = '5374d7dfeffa2eb49965624ba7596a09';
    Pushpad::$projectId = 123;
  }
  
  public function testSignature() {
    $actual = Pushpad::signatureFor('user12345');
    $expected = '6627820dab00a1971f2a6d3ff16a5ad8ba4048a02b2d402820afc61aefd0b69f';
    $this->assertEquals($actual, $expected);
  }

  public function testSignatureRequiresAuthToken(): void
  {
    Pushpad::$authToken = null;

    $this->expectException(ConfigurationException::class);
    Pushpad::signatureFor('user123');
  }

  public function testHttpRequiresAuthToken(): void
  {
    Pushpad::$authToken = null;

    $this->expectException(ConfigurationException::class);
    Pushpad::http();
  }

  public function testResolveProjectIdRequiresConfiguredValue(): void
  {
    Pushpad::$projectId = null;

    $this->expectException(ConfigurationException::class);
    Pushpad::resolveProjectId(null);
  }
  
}
