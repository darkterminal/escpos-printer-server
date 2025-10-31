<?php
declare(strict_types=1);

namespace Tests\Unit;

use Darkterminal\EscposPrinterServer\Utils\CommonTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommonTrait::class)]
class CommonTraitTest extends TestCase
{
    /**
     * @var object
     */
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class {
            use CommonTrait;
        };
    }

    #[Test]
    public function it_can_format_to_idr_correctly(): void
    {
        $formatted = $this->traitObject->toIDR(150000);
        $this->assertEquals("150.000", $formatted);

        $formattedWithPrefix = $this->traitObject->toIDR(75000, true);
        $this->assertEquals("Rp. 75.000", $formattedWithPrefix);

        $formattedWithDecimal = $this->traitObject->toIDR(123456.78, false, 2);
        $this->assertEquals("123.456,78", $formattedWithDecimal);
    }

    #[Test]
    public function it_can_create_aligned_text(): void
    {
        $alignedText = $this->traitObject->makeAlignText("Kiri", "Kanan", 40);

        $expected = "Kiri                " . "               Kanan" . "\n";

        $this->assertEquals($expected, $alignedText);
    }

    #[Test]
    public function it_can_wrap_text_for_items(): void
    {
        $itemLine = $this->traitObject->makeWrapText(
            "A Very Very Long Item Name",
            "Rp. 1.000.000",
            "epson",
        );

        $lines = explode("\n", $itemLine);

        $this->assertCount(3, $lines);
        $this->assertStringContainsString("A Very Very Long", $lines[0]);
        $this->assertStringContainsString("Rp. 1.000.000", $lines[0]);
        $this->assertStringContainsString("Item Name", $lines[1]);
    }
}
