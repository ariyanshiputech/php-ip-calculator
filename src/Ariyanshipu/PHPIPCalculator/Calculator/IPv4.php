<?php
/**
 * Created by PhpStorm.
 * Date: 19-3-9
 * Time: 下午9:34
 */

namespace Ariyanshipu\PHPIPCalculator\Calculator;


use Ariyanshipu\PHPIPCalculator\Constants;
use Ariyanshipu\PHPIPCalculator\Contract\IPCalculator;
use Ariyanshipu\PHPIPCalculator\Exception\ErrorCode;
use Ariyanshipu\PHPIPCalculator\Exception\Exception;

class IPv4 implements IPCalculator
{
    private $binaryIP;

    private $binaryNetwork;

    private $binaryBroadcast;

    private $binaryMask;

    private $networkBits;

    /**
     * IPCalculator constructor.
     * @param string|int $ipv4Address Human readable format or uint32 integer
     * @param string|int $mask
     * @throws Exception
     */
    public function __construct($ipv4Address, $mask)
    {
        if (is_integer($mask) || ctype_digit($mask)) {
            // Mask can be an integer (0-32)
            $cidr = (int) $mask;
            if (!($cidr >= 0 && $cidr <= 32))
                throw new Exception("Invalid CIDR " . $cidr, ErrorCode::INVALID_CIDR, null, $ipv4Address);
            $this->binaryMask = self::CIDR2Binary($cidr);
            $this->networkBits = $cidr;
        } else if (empty($mask)) {
            $this->binaryMask = self::CIDR2Binary(32);
            $this->networkBits = 32;
        } else {
            // Or format like 255.255.255.0
            $this->networkBits = @Constants::IP_NETMASK_2_NETWORK_BITS[$mask];
            if (is_null($this->networkBits))
                throw new Exception("Invalid netmask " . $mask, ErrorCode::INVALID_NETMASK, null, $ipv4Address);
            $this->binaryMask = self::IP2Binary($mask);
        }

        if (is_integer($ipv4Address) || ctype_digit($ipv4Address)) {
            // Calculable format
            $this->binaryIP = (int) $ipv4Address;
        } else if (empty($ipv4Address)) {
            throw new Exception("Empty IP", ErrorCode::INVALID_IP, null, $ipv4Address);
        } else {
            // Human readable forma
            $this->binaryIP = self::IP2Binary($ipv4Address);
        }

        if ($this->binaryIP === false || $this->binaryIP > Constants::UNSIGNED_INT32_MAX || $this->binaryIP < 0)
            throw new Exception("Invalid IP", ErrorCode::INVALID_IP, null, $ipv4Address);

        $this->binaryNetwork = $this->binaryIP & $this->binaryMask;

        $this->binaryBroadcast = $this->binaryNetwork | self::convertToUnsignedInteger32(~ $this->binaryMask);
    }


    public function binaryIP()
    {
        return $this->binaryIP;
    }

    public function network()
    {
        return $this->binaryNetwork;
    }

    public function broadcast()
    {
        return $this->binaryBroadcast;
    }

    /**
     * @inheritdoc
     */
    public function getType(): int
    {
        return Constants::TYPE_IPV4;
    }

    /**
     * @inheritDoc
     */
    public function getNetworkBits(): int
    {
        return $this->networkBits;
    }

    /**
     * @inheritdoc
     */
    public function getSubnetAfter($n = 1): IPCalculator
    {
        return new self($this->binaryNetwork + (($n << (32 - $this->networkBits)) & Constants::UNSIGNED_INT32_MAX), $this->networkBits);
    }

    /**
     * @inheritdoc
     */
    public function getSubnetBefore($n = 1): IPCalculator
    {
        return new self($this->binaryNetwork - (($n << (32 - $this->networkBits)) & Constants::UNSIGNED_INT32_MAX), $this->networkBits);
    }

    /**
     * @inheritdoc
     */
    public function getFirstAddress()
    {
        return $this->network();
    }

    /**
     * @inheritdoc
     */
    public function getLastAddress()
    {
        return $this->broadcast();
    }

    /**
     * @inheritdoc
     */
    public function getFirstHumanReadableAddress(): string
    {
        return self::calculable2HumanReadable($this->network());
    }

    /**
     * @inheritdoc
     */
    public function getLastHumanReadableAddress(): string
    {
        return self::calculable2HumanReadable($this->broadcast());
    }

    /**
     * @inheritdoc
     */
    public function isIPInRange($ip) : bool {
        $binaryIP = self::IP2Binary($ip);
        if ($binaryIP === false)
            return false;
        $binaryNetwork = $binaryIP & $this->binaryMask;
        return $this->binaryNetwork === $binaryNetwork;
    }

    /**
     * @inheritDoc
     */
    public function howMany($mask = null)
    {
        $mask = self::defaultMaskOnNull($mask);
        if ($mask > 32 || $mask < 0)
            return 0;
        return (self::convertToUnsignedInteger32(~ $this->binaryMask) + 1) >> (32 - $mask);
    }

    /**
     * @inheritdoc
     */
    public function ipAt($position, $mask = null)
    {
        if (is_null($mask))
            $mask = 32;
        $binaryMask = $this->binaryMask | (($position << (32 - $mask)) & Constants::UNSIGNED_INT32_MAX);
        return $this->binaryBroadcast & $binaryMask;
    }

    /**
     * @inheritdoc
     */
    public function ipAtAsCalculator($position, $mask = null): IPCalculator
    {
        if (is_null($mask))
            $mask = 32;
        return new self($this->ipAt($position, $mask), $mask);
    }

    /**
     * @inheritdoc
     */
    public function ipReverseAt($position, $mask = null)
    {
        if (is_null($mask))
            $mask = 32;
        $binaryMask = ((Constants::UNSIGNED_INT32_MAX ^ $position) << (32 - $mask)) & Constants::UNSIGNED_INT32_MAX;
        return $this->binaryBroadcast & $binaryMask;
    }

    /**
     * @inheritdoc
     */
    public function ipReverseAtAsCalculator($position, $mask = null): IPCalculator
    {
        if (is_null($mask))
            $mask = 32;
        return new self($this->ipReverseAt($position, $mask), $mask);
    }

    /**
     * @inheritdoc
     */
    public function isPositionOutOfRange($position, $mask = null) : bool
    {
        if (is_null($mask))
            $mask = 32;
        if ($this->binaryMask & $position << (32 - $mask))
            return true;
        return false;
    }

    /**
     * @inheritDoc
     */
    public function distanceTo(IPCalculator $destination)
    {
        if ($this->getNetworkBits() !== $destination->getNetworkBits())
            return false;
        if ($this->getType() !== $destination->getType())
            return false;
        $bit2Shift = 32 - $this->getNetworkBits();
        return ($destination->getFirstAddress() >> $bit2Shift) - ($this->getFirstAddress() >> $bit2Shift);
    }

    /**
     * @inheritdoc
     */
    public static function compare($first, $second): int
    {
        if ($first > $second)
            return 1;
        if ($first === $second)
            return 0;
        return -1;
    }

    /**
     * @inheritdoc
     */
    public static function calculable2HumanReadable($calculableFormat)
    {
        return long2ip($calculableFormat);
    }

    public static function humanReadable2Calculable($humanReadable)
    {
        return ip2long($humanReadable);
    }

    private static function CIDR2Binary($CIDR)
    {
        $num = 32 - (int) $CIDR;
        return self::convertToUnsignedInteger32(Constants::UNSIGNED_INT32_MAX << $num);
    }

    private static function IP2Binary($ip) {
        return ip2long($ip);
    }

    private static function convertToUnsignedInteger32($value)
    {
        return $value & Constants::UNSIGNED_INT32_MAX;
    }

    private static function defaultMaskOnNull($mask)
    {
        return is_null($mask) ? 32 : $mask;
    }
}