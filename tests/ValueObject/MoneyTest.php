<?php

namespace App\Tests\ValueObject;

use App\Enum\CurrencyEnum;
use App\ValueObject\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    // --- fromCents ---

    public function testFromCentsCreatesMoneyWithGivenAmount(): void
    {
        $money = Money::fromCents(1050);

        self::assertSame(1050, $money->cents());
    }

    public function testFromCentsWithZero(): void
    {
        $money = Money::fromCents(0);

        self::assertSame(0, $money->cents());
        self::assertTrue($money->isZero());
    }

    public function testFromCentsWithNegativeAmount(): void
    {
        $money = Money::fromCents(-500);

        self::assertSame(-500, $money->cents());
        self::assertFalse($money->isPositive());
    }

    // --- fromDecimal ---

    public static function provideDecimalConversions(): iterable
    {
        yield 'decimal with one digit' => ['10.7', 1070];
        yield 'decimal with two digits' => ['10.50', 1050];
        yield 'decimal with two digits zero at the beginning' => ['10.05', 1005];
        yield 'integer string v1' => ['10', 1000];
        yield 'integer string v2' => ['10.00', 1000];
        yield 'rounds up at .005' => ['10.005', 1001];
        yield 'negative value' => ['-5.25', -525];
        yield 'zero' => ['0', 0];
        yield 'integer with zero at the beginning' => ['01', 100];
    }

    #[DataProvider('provideDecimalConversions')]
    public function testFromDecimalConvertsToCorrectCents(string $decimal, int $expectedCents): void
    {
        $money = Money::fromDecimal($decimal);

        self::assertSame($expectedCents, $money->cents());
    }

    // --- cents ---

    public function testCentsReturnsRawAmount(): void
    {
        $money = Money::fromCents(12345);

        self::assertSame(12345, $money->cents());
    }

    // --- decimal ---

    public static function provideDecimalFormatting(): iterable
    {
        yield 'standard amount' => [1050, '10.50'];
        yield 'zero' => [0, '0.00'];
        yield 'negative amount' => [-1050, '-10.50'];
        yield 'amount under one unit' => [5, '0.05'];
    }

    #[DataProvider('provideDecimalFormatting')]
    public function testDecimalFormatsWithTwoDecimalsAndDot(int $cents, string $expected): void
    {
        $money = Money::fromCents($cents);

        self::assertSame($expected, $money->decimal());
    }

    // --- format ---

    #[DataProvider('provideCurrencyFormatting')]
    public function testFormatWithCurrency(int $cents, CurrencyEnum $currency, string $expected): void
    {
        $money = new Money($cents, $currency);

        self::assertSame($expected, $money->format());
    }

    public static function provideCurrencyFormatting(): iterable
    {
        yield 'euro' => [1050, CurrencyEnum::EURO, '10,50 €'];
        yield 'usd' => [1050, CurrencyEnum::USD, '10,50 $'];
        yield 'cd' => [1050, CurrencyEnum::CD, '10,50 $'];
        yield 'xof' => [1050, CurrencyEnum::XOF, '10,50 FCFA'];
        yield 'xaf' => [1050, CurrencyEnum::XAF, '10,50 FCFA'];
        yield 'zero amount' => [0, CurrencyEnum::EURO, '0,00 €'];
        yield 'negative amount' => [-1050, CurrencyEnum::EURO, '-10,50 €'];
        yield 'large amount with thousands separator' => [123456789, CurrencyEnum::EURO, '1 234 567,89 €'];
    }

    public function testFormatUsesEuroByDefault(): void
    {
        $money = new Money(1050);

        self::assertSame('10,50 €', $money->format());
    }

    // --- add ---

    #[DataProvider('provideAddOperations')]
    public function testAddReturnsNewInstanceWithSummedAmount(int $a, int $b, int $expected): void
    {
        $result = Money::fromCents($a)->add(Money::fromCents($b));

        self::assertSame($expected, $result->cents());
    }

    public static function provideAddOperations(): iterable
    {
        yield 'two positive amounts' => [1000, 500, 1500];
        yield 'positive and negative amount' => [1000, -300, 700];
        yield 'adding zero' => [1000, 0, 1000];
    }

    public function testAddDoesNotMutateOriginalInstances(): void
    {
        $money = Money::fromCents(1000);
        $other = Money::fromCents(500);

        $money->add($other);

        self::assertSame(1000, $money->cents());
        self::assertSame(500, $other->cents());
    }

    // --- subtract ---

    #[DataProvider('provideSubtractOperations')]
    public function testSubtractReturnsNewInstanceWithDifference(int $a, int $b, int $expected): void
    {
        $result = Money::fromCents($a)->subtract(Money::fromCents($b));

        self::assertSame($expected, $result->cents());
    }

    public static function provideSubtractOperations(): iterable
    {
        yield 'positive result' => [1000, 300, 700];
        yield 'negative result' => [300, 1000, -700];
        yield 'subtracting zero' => [1000, 0, 1000];
    }

    public function testSubtractDoesNotMutateOriginalInstances(): void
    {
        $money = Money::fromCents(1000);
        $other = Money::fromCents(300);

        $money->subtract($other);

        self::assertSame(1000, $money->cents());
        self::assertSame(300, $other->cents());
    }

    // --- isPositive ---

    #[DataProvider('provideIsPositiveCases')]
    public function testIsPositive(int $cents, bool $expected): void
    {
        self::assertSame($expected, Money::fromCents($cents)->isPositive());
    }

    public static function provideIsPositiveCases(): iterable
    {
        yield 'positive amount' => [1, true];
        yield 'zero' => [0, false];
        yield 'negative amount' => [-1, false];
    }

    // --- isZero ---

    #[DataProvider('provideIsZeroCases')]
    public function testIsZero(int $cents, bool $expected): void
    {
        self::assertSame($expected, Money::fromCents($cents)->isZero());
    }

    public static function provideIsZeroCases(): iterable
    {
        yield 'zero amount' => [0, true];
        yield 'positive amount' => [1, false];
        yield 'negative amount' => [-1, false];
    }
}
