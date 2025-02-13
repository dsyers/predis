<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @group commands
 * @group realm-stream
 */
class XRANGE_Test extends PredisCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExpectedCommand(): string
    {
        return 'Predis\Command\Redis\XRANGE';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedId(): string
    {
        return 'XRANGE';
    }

    /**
     * @group disconnected
     */
    public function testFilterArguments(): void
    {
        $arguments = array('stream', '0-1', '1-2', 123);
        $expected = array('stream', '0-1', '1-2', 'COUNT', 123);

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testFilterArgumentsNoCount(): void
    {
        $arguments = array('stream', '0-1', '1-2');
        $expected = array('stream', '0-1', '1-2');

        $command = $this->getCommand();
        $command->setArguments($arguments);

        $this->assertSame($expected, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testParseResponse(): void
    {
        $raw = array(array('0-1', ['key', 'val']));
        $expected = array('0-1' => ['key' => 'val']);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group disconnected
     */
    public function testParseResponseMultipleKeys(): void
    {
        $raw = array(array('0-1', ['key1', 'val1', 'key2', 'val2']));
        $expected = array('0-1' => ['key1' => 'val1', 'key2' => 'val2']);

        $command = $this->getCommand();

        $this->assertSame($expected, $command->parseResponse($raw));
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testReturnsElementsInRange(): void
    {
        $redis = $this->getClient();

        for ($i = 0; $i < 10; $i++) {
            $redis->xadd('stream', ['key' . $i => 'val' . $i], $i . '-1');
        }

        $this->assertSame(array(), $redis->xrange('stream', '1-1', '0-1'));
        $this->assertSame(
            array('0-1' => ['key0' => 'val0']),
            $redis->xrange('stream', '0-1', '0-1')
        );
        $this->assertSame(
            array('0-1' => ['key0' => 'val0'], '1-1' => ['key1' => 'val1']),
            $redis->xrange('stream', '0-1', '1-1')
        );
        $this->assertSame(
            array('0-1' => ['key0' => 'val0'], '1-1' => ['key1' => 'val1']),
            $redis->xrange('stream', '-', '1-1')
        );
        $this->assertSame(
            array('8-1' => ['key8' => 'val8'], '9-1' => ['key9' => 'val9']),
            $redis->xrange('stream', '8-1', '+')
        );
        $this->assertSame(
            array('5-1' => ['key5' => 'val5'], '6-1' => ['key6' => 'val6']),
            $redis->xrange('stream', '5-1', '6-1')
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testMultipleKeys(): void
    {
        $redis = $this->getClient();

        $redis->xadd('stream', ['key1' => 'val1', 'key2' => 'val2'], '0-1');
        $redis->xadd('stream', ['key1' => 'val1', 'key2' => 'val2'], '1-1');

        $this->assertSame(
            array(
                '0-1' => ['key1' => 'val1', 'key2' => 'val2'],
                '1-1' => ['key1' => 'val1', 'key2' => 'val2'],
            ),
            $redis->xrange('stream', '-', '+')
        );
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 5.0.0
     */
    public function testThrowsExceptionOnWrongType(): void
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $redis = $this->getClient();

        $redis->set('foo', 'bar');
        $redis->xrange('foo', '0-1', '1-1');
    }
}
