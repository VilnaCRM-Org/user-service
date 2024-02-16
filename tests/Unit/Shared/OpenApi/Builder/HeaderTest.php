<?php

namespace App\Tests\Unit\Shared\OpenApi\Builder;

use App\Shared\OpenApi\Builder\Header;
use App\Tests\Unit\UnitTestCase;

class HeaderTest extends UnitTestCase
{
    public function testCreateWithValidData()
    {
        $headerValue = $this->faker->word();
        $description = $this->faker->word();
        $type = $this->faker->word();
        $format = $this->faker->word();
        $example = $this->faker->word();

        $header = new Header(
            $headerValue,
            $description,
            $type,
            $format,
            $example
        );

        $this->assertEquals($headerValue, $header->name);
        $this->assertEquals($description, $header->description);
        $this->assertEquals($type, $header->type);
        $this->assertEquals($format, $header->format);
        $this->assertEquals($example, $header->example);
    }
}
