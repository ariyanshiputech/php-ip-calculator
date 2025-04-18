<?php
/**
 * Created by PhpStorm.
 * Date: 19-3-9
 * Time: 下午9:33
 */

namespace Ariyanshipu\PHPIPCalculator\Test\Unit;


use PHPUnit\Framework\TestCase;
use Ariyanshipu\PHPIPCalculator\Calculator\IPv4;
use Ariyanshipu\PHPIPCalculator\Calculator\IPv6;
use Ariyanshipu\PHPIPCalculator\CalculatorFactory;
use Ariyanshipu\PHPIPCalculator\Constants;

class CalculatorTest extends TestCase
{
    public function testIPv4Calculator()
    {
        $factory = new CalculatorFactory("192.168.111.222/16");
        $calculator = $factory->create();
        $this->assertTrue($calculator instanceof IPv4);

        $this->assertEquals(Constants::TYPE_IPV4, $calculator->getType());
        $this->assertEquals("192.168.0.0", $calculator->getFirstHumanReadableAddress());
        $this->assertEquals("192.168.255.255", $calculator->getLastHumanReadableAddress());
        $this->assertTrue($calculator->isIPInRange("192.168.111.111"));
        $this->assertFalse($calculator->isIPInRange("192.169.111.111"));
        $this->assertFalse($calculator->isIPInRange("::1"));

        $this->assertEquals("192.168.0.0", $calculator::calculable2HumanReadable($calculator->ipAt(0)));
        $this->assertEquals("192.168.1.0", $calculator::calculable2HumanReadable($calculator->ipAt(256)));
        $this->assertEquals("192.168.255.255", $calculator::calculable2HumanReadable($calculator->ipAt(65535)));
        $this->assertEquals("192.168.0.0", $calculator::calculable2HumanReadable($calculator->ipAt(0, 24)));
        $this->assertEquals("192.168.1.0", $calculator::calculable2HumanReadable($calculator->ipAt(1, 24)));
        $this->assertEquals("192.168.255.0", $calculator::calculable2HumanReadable($calculator->ipAt(255, 24)));

        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0)), $calculator::calculable2HumanReadable($calculator->ipReverseAt(65535)));
        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0, 24)), $calculator::calculable2HumanReadable($calculator->ipReverseAt(255, 24)));

        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0)), $calculator::calculable2HumanReadable($calculator->ipReverseAtAsCalculator(65535)->getFirstAddress()));
        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0, 24)), $calculator::calculable2HumanReadable($calculator->ipReverseAtAsCalculator(255, 24)->getFirstAddress()));

        $this->assertEquals("192.168.254.0", $calculator::calculable2HumanReadable($calculator->ipReverseAt(1, 24)));

        $this->assertFalse($calculator->isPositionOutOfRange(0));
        $this->assertFalse($calculator->isPositionOutOfRange(65535));
        $this->assertTrue($calculator->isPositionOutOfRange(65536));

        $this->assertFalse($calculator->isPositionOutOfRange(0, 24));
        $this->assertFalse($calculator->isPositionOutOfRange(255, 24));
        $this->assertTrue($calculator->isPositionOutOfRange(256, 24));

        $this->assertEquals(1, $calculator::humanReadable2Calculable("0.0.0.1"));
        $this->assertEquals(-1, $calculator::compare($calculator::humanReadable2Calculable("127.0.0.1"), $calculator::humanReadable2Calculable("127.0.0.2")));
        $this->assertEquals(0, $calculator::compare($calculator::humanReadable2Calculable("127.0.0.1"), $calculator::humanReadable2Calculable("127.0.0.1")));
        $this->assertEquals(1, $calculator::compare($calculator::humanReadable2Calculable("127.0.0.2"), $calculator::humanReadable2Calculable("127.0.0.1")));

        $this->assertEquals("192.168.0.0", $calculator->getSubnetAfter(0)->getFirstHumanReadableAddress());
        $this->assertEquals("192.169.0.0", $calculator->getSubnetAfter()->getFirstHumanReadableAddress());
        $this->assertEquals("192.255.0.0", $calculator->getSubnetAfter(87)->getFirstHumanReadableAddress());

        $this->assertEquals("192.168.0.0", $calculator->getSubnetBefore(0)->getFirstHumanReadableAddress());
        $this->assertEquals("192.167.0.0", $calculator->getSubnetBefore()->getFirstHumanReadableAddress());
        $this->assertEquals("192.0.0.0", $calculator->getSubnetBefore(168)->getFirstHumanReadableAddress());

        $this->assertEquals(100, $calculator->distanceTo($calculator->getSubnetAfter(100)));

        $this->assertEquals(65536, $calculator->howMany());
        $this->assertEquals(256, $calculator->howMany(24));
    }

    public function testIPv6Calculator()
    {
        $factory = new CalculatorFactory("2001:470:0:76::2/48");
        $calculator = $factory->create();
        $this->assertTrue($calculator instanceof IPv6);

        $this->assertEquals(Constants::TYPE_IPV6, $calculator->getType());
        $this->assertEquals("2001:470::", $calculator->getFirstHumanReadableAddress());
        $this->assertEquals("2001:470:0:ffff:ffff:ffff:ffff:ffff", $calculator->getLastHumanReadableAddress());
        $this->assertTrue($calculator->isIPInRange("2001:470:0:76::ff0f:f0ff"));
        $this->assertFalse($calculator->isIPInRange("2001:460:0:78::ffff:ffff"));
        $this->assertFalse($calculator->isIPInRange("127.0.0.1"));

        $this->assertEquals("2001:470::", $calculator::calculable2HumanReadable($calculator->ipAt(0)));
        $this->assertEquals("2001:470::2", $calculator::calculable2HumanReadable($calculator->ipAt(2)));
        $this->assertEquals("2001:470::", $calculator::calculable2HumanReadable($calculator->ipAt(0, 64)));
        $this->assertEquals("2001:470:0:1::", $calculator::calculable2HumanReadable($calculator->ipAt(1, 64)));
        $this->assertEquals("2001:470:0:ffff::", $calculator::calculable2HumanReadable($calculator->ipAt(65535, 64)));

        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0, 64)), $calculator::calculable2HumanReadable($calculator->ipReverseAt(65535, 64)));
        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipReverseAt(65534, 64)), $calculator::calculable2HumanReadable($calculator->ipAt(1, 64)));

        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipAt(0, 64)), $calculator::calculable2HumanReadable($calculator->ipReverseAtAsCalculator(65535, 64)->getFirstAddress()));
        $this->assertEquals($calculator::calculable2HumanReadable($calculator->ipReverseAt(65534, 64)), $calculator::calculable2HumanReadable($calculator->ipAtAsCalculator(1, 64)->getFirstAddress()));

        $this->assertEquals("2001:470:0:ffff:ffff:ffff:ffff:ffff", $calculator::calculable2HumanReadable($calculator->ipReverseAt(0)));

        $this->assertEquals("2001:470::", $calculator::calculable2HumanReadable($calculator->ipReverseAt([
            0x0,
            0x0000FFFF,
            0xFFFFFFFF,
            0xFFFFFFFF,
        ])));
        $this->assertEquals("2001:470:0:ffff:ffff:ffff:ffff:ffff", $calculator::calculable2HumanReadable($calculator->ipAt([
            0x0,
            0x0000FFFF,
            0xFFFFFFFF,
            0xFFFFFFFF,
        ])));

        $this->assertFalse($calculator->isPositionOutOfRange(0));
        $this->assertFalse($calculator->isPositionOutOfRange([
            0x0,
            0x0000FFFF,
            0xFFFFFFFF,
            0xFFFFFFFF,
        ]));
        $this->assertTrue($calculator->isPositionOutOfRange([
            0x0,
            0x00010000,
            0x0,
            0x0,
        ]));

        $this->assertFalse($calculator->isPositionOutOfRange(0, 64));
        $this->assertFalse($calculator->isPositionOutOfRange(65535, 64));
        $this->assertTrue($calculator->isPositionOutOfRange(65536, 64));

        $this->assertEquals([0, 0, 0, 1], $calculator::humanReadable2Calculable("::1"));
        $this->assertEquals([0, 0, 0, 2], $calculator::humanReadable2Calculable("::2"));
        $this->assertEquals(-1, $calculator::compare($calculator::humanReadable2Calculable("::1"), $calculator::humanReadable2Calculable("::2")));
        $this->assertEquals(0, $calculator::compare($calculator::humanReadable2Calculable("::1"), $calculator::humanReadable2Calculable("::1")));
        $this->assertEquals(1, $calculator::compare($calculator::humanReadable2Calculable("::2"), $calculator::humanReadable2Calculable("::1")));

        $this->assertEquals("2001:470::", $calculator->getSubnetAfter(0)->getFirstHumanReadableAddress());
        $this->assertEquals("2001:470:1::", $calculator->getSubnetAfter()->getFirstHumanReadableAddress());
        $this->assertEquals("2001:470:ffff::", $calculator->getSubnetAfter(0xffff)->getFirstHumanReadableAddress());

        $this->assertEquals("2001:470::", $calculator->getSubnetBefore(0)->getFirstHumanReadableAddress());
        $this->assertEquals("2001:46f:ffff::", $calculator->getSubnetBefore()->getFirstHumanReadableAddress());
        $this->assertEquals("2001::", $calculator->getSubnetBefore(0x4700000)->getFirstHumanReadableAddress());

        $this->assertEquals([0, 0, 0, 100], $calculator->distanceTo($calculator->getSubnetAfter(100)));

        $this->assertEquals([0, 0, 0, 1], $calculator->howMany(48));
        $this->assertEquals([0, 0, 0, 65536], $calculator->howMany(64));
    }

    public function testMac2LinkLocal()
    {
        $this->assertEquals("fe80::5054:00ff:fe00:0001", IPv6::mac2LinkLocal("52:54:00:00:00:01"));
        $this->assertEquals("fe80::5054:00ff:feff:ffff", IPv6::mac2LinkLocal("52:54:00:ff:ff:ff"));
    }
}