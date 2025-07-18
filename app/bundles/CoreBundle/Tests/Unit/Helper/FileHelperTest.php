<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\FileHelper;

#[\PHPUnit\Framework\Attributes\CoversClass(FileHelper::class)]
class FileHelperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('bytesToMegabytesProvider')]
    #[\PHPUnit\Framework\Attributes\TestDox('Conversion of Bytes to Megebytes')]
    public function testConversionFromBytesToMegabytes(int $byte, float $megabyte): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($megabyte, $fileHelper::convertBytesToMegabytes($byte));
    }

    public static function bytesToMegabytesProvider()
    {
        return [
            [0, 0.0],
            [1_048_576, 1.0],
            [10_485_760, 10.0],
            [-10_485_760, -10.0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('megabytesToBytesProvider')]
    #[\PHPUnit\Framework\Attributes\TestDox('Conversion of Megebytes to Bytes')]
    public function testConversionFromMegabytesToBytes(int $megabyte, int $byte): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($byte, $fileHelper::convertMegabytesToBytes($megabyte));
    }

    public static function megabytesToBytesProvider()
    {
        return [
            [0, 0],
            [1, 1_048_576],
            [5, 5_242_880],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('phpSizeToBytesProvider')]
    #[\PHPUnit\Framework\Attributes\TestDox('Conversion of PHP size to Bytes')]
    public function testConvertPHPSizeToBytes(string $phpSize, int $bytes): void
    {
        $fileHelper = new FileHelper();

        $this->assertSame($bytes, $fileHelper::convertPHPSizeToBytes($phpSize));
    }

    public static function phpSizeToBytesProvider()
    {
        return [
            ['3048M', 3_196_059_648],
            ['127M', 133_169_152],
            ['1k', 1024],
            ['1K ', 1024],
            ['1M', 1_048_576],
            ['1G', 1_073_741_824],
            ['1P', 1_125_899_906_842_624],
            ['1024', 1024],
        ];
    }
}
