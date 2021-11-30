<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Test\Util;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SerialShellCommandSpyTest extends TestCase
{
    private SerialShellCommandSpy $spy;

    protected function setUp(): void
    {
        $this->spy = new SerialShellCommandSpy();
    }

    public function testSpyReturnsResults()
    {
        $this->spy->setResults(['a','b',null,'c']);
        self::assertSame('a', $this->spy->executeCommandWithArguments('foo', []));
        self::assertSame('b', $this->spy->executeCommandWithArguments('foo', []));
        self::assertNull($this->spy->executeCommandWithArguments('foo', []));
        self::assertSame('c', $this->spy->executeCommandWithArguments('foo', []));
    }

    public function testAssertFailsWhenCountMismatchTooFew()
    {
        $this->spy->setResults(['','']);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('foo', []);

        try {
            $this->spy->assertExpectedResults([1,2,3]);
            self::fail('Should have failed');
        } catch (ExpectationFailedException $exception) {
            // Test Passed
        }
    }

    public function testAssertFailsWhenCountMismatchTooMany()
    {
        $this->spy->setResults(['','','','']);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('foo', []);

        try {
            $this->spy->assertExpectedResults([1,2,3]);
            self::fail('Should have failed');
        } catch (ExpectationFailedException $exception) {
            // Test Passed
        }
    }

    public function testAssertFailsWhenCommandMismatch()
    {
        $this->spy->setResults(['','','']);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('bar', []);
        $this->spy->executeCommandWithArguments('baz', []);

        try {
            $this->spy->assertExpectedResults([
                ['name' => 'foo', 'arguments' => []],
                ['name' => 'barf', 'arguments' => []],
            ]);
            self::fail('Should have failed');
        } catch (ExpectationFailedException $exception) {
            // Test Passed
        }
    }

    public function testAssertFailsWhenArgumentMismatch()
    {
        $this->spy->setResults(['','','']);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('bar', ['baz' => 'bashful']);
        $this->spy->executeCommandWithArguments('baz', []);

        try {
            $this->spy->assertExpectedResults([
                ['name' => 'foo', 'arguments' => []],
                ['name' => 'bar', 'arguments' => ['baz' => 'bash']],
            ]);
            self::fail('Should have failed');
        } catch (ExpectationFailedException $exception) {
            // Test Passed
        }
    }

    public function testAssertPassesWhenDataIsCorrect()
    {
        $this->spy->setResults(['','','']);
        $this->spy->executeCommandWithArguments('foo', []);
        $this->spy->executeCommandWithArguments('bar', ['baz' => 'bashful']);
        $this->spy->executeCommandWithArguments('baz', ['something' => 'else', 'yep' => 'something']);

        $this->spy->assertExpectedResults([
            ['name' => 'foo', 'arguments' => []],
            ['name' => 'bar', 'arguments' => ['baz' => 'bashful']],
            ['name' => 'baz', 'arguments' => ['something' => 'else', 'yep' => 'something']],
        ]);
    }
}
