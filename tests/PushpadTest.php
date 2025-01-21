<?php

use PHPUnit\Framework\TestCase;
use Pushpad\Pushpad;

class PushpadTest extends TestCase {
  
  protected function setUp(): void {
    Pushpad::$auth_token = '5374d7dfeffa2eb49965624ba7596a09';
    Pushpad::$project_id = 123;
  }
  
  public function testSignature() {
    $actual = Pushpad::signature_for('user12345');
    $expected = '6627820dab00a1971f2a6d3ff16a5ad8ba4048a02b2d402820afc61aefd0b69f';
    $this->assertEquals($actual, $expected);
  }
  
}
