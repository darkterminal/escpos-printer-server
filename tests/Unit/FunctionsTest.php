<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . "/src/functions.php";

#[CoversFunction("argv2assoc")]
class FunctionsTest extends TestCase
{
    public static function argumentProvider(): array
    {
        return [
            "empty array" => [[], []],
            "single command" => [["start"], ["start" => null]],
            "option with value" => [
                ["--host", "localhost"],
                ["--host" => "localhost"],
            ],
            "option flag (no value)" => [["--verbose"], ["--verbose" => null]],
            "mixed command, options, and flags" => [
                ["start", "--port", "8080", "--daemon", "-v"],
                [
                    "start" => null,
                    "--port" => "8080",
                    "--daemon" => null,
                    "-v" => null,
                ],
            ],
            "command is not an option" => [
                ["command-name", "another-arg"],
                ["command-name" => null, "another-arg" => null],
            ],
        ];
    }

    #[Test]
    #[DataProvider("argumentProvider")]
    public function it_converts_argv_to_associative_array(
        array $input,
        array $expected,
    ): void {
        $result = argv2assoc($input);
        $this->assertEquals($expected, $result);
    }
}
