<?php

/*
 * This file is part of the PHPBench package
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace PhpBench\Tests\Unit\Benchmark\Remote;

use PHPUnit\Framework\TestCase;
use PhpBench\Benchmark\Remote\Payload;
use Prophecy\Argument;
use Symfony\Component\Process\Process;

class PayloadTest extends TestCase
{
    private $process;

    public function setUp()
    {
        $this->process = $this->prophesize(Process::class);
    }

    /**
     * It should generate a script from a given template, launch it
     * and return the results.
     */
    public function testLaunch()
    {
        $payload = new Payload(
            __DIR__ . '/template/foo.template',
            [
                'foo' => 'bar',
            ]
        );

        $result = $payload->launch($payload);

        $this->assertEquals([
            'foo' => 'bar',
        ], $result);
    }

    /**
     * It should throw an exception if the script is invalid.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage syntax error
     */
    public function testInvalidScript()
    {
        $payload = new Payload(
            __DIR__ . '/template/invalid.template'
        );

        $payload->launch($payload);
    }

    /**
     * It should customize the PHP binary path.
     */
    public function testBinaryPath()
    {
        $process = $this->prophesize(Process::class);
        $payload = new Payload(
            __DIR__ . '/template/foo.template',
            [],
            null,
            $process->reveal()
        );
        $payload->setPhpPath('/foo/bar');
        $process->setCommandLine(Argument::containingString('/foo/bar'))->shouldBeCalled();
        $process->run()->shouldBeCalled();
        $process->isSuccessful()->willReturn(true);
        $process->getOutput()->willReturn('{"foo": "bar"}');

        $payload->launch($payload);
    }

    /**
     * It should pass PHP ini settings to the PHP executable.
     */
    public function testPhpConfig()
    {
        $process = $this->prophesize(Process::class);
        $payload = new Payload(
            __DIR__ . '/template/foo.template',
            [],
            null,
            $process->reveal()
        );
        $payload->setPhpConfig([
            'foo' => 'bar',
            'bar' => 'foo',
        ]);
        $process->setCommandLine(Argument::containingString('-dfoo=bar'))->shouldBeCalled();
        $process->setCommandLine(Argument::containingString('-dbar=foo'))->shouldBeCalled();
        $process->run()->shouldBeCalled();
        $process->isSuccessful()->willReturn(true);
        $process->getOutput()->willReturn('{"foo": "bar"}');

        $payload->launch($payload);
    }

    /**
     * It should allow the PHP executable to be wrapped with a different executable.
     */
    public function testWrap()
    {
        $process = $this->prophesize(Process::class);
        $payload = new Payload(
            __DIR__ . '/template/foo.template',
            [],
            null,
            $process->reveal()
        );
        $payload->setWrapper('bockfire');
        $payload->setPhpPath('/boo/bar/php');
        $process->setCommandLine(Argument::containingString('bockfire /boo/bar/php'))->shouldBeCalled();
        $process->run()->shouldBeCalled();
        $process->isSuccessful()->willReturn(true);
        $process->getOutput()->willReturn('{"foo": "bar"}');

        $payload->launch($payload);
    }

    /**
     * It should throw an execption if a template is not found.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not find script template
     */
    public function testTemplateNotFound()
    {
        $process = $this->prophesize(Process::class);
        $payload = new Payload(
            __DIR__ . '/template/not-existing-filename.template',
            [],
            null,
            $process->reveal()
        );

        $payload->launch($payload);
    }
}
