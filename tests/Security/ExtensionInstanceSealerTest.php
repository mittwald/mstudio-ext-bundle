<?php

namespace Mittwald\MStudio\Bundle\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\logicalNot;

#[CoversClass(ExtensionInstanceSealer::class)]
class ExtensionInstanceSealerTest extends TestCase
{
    #[Test]
    public function sealAndUnsealProduceOriginalInputValue(): void
    {
        $key = random_bytes(256 / 8);
        $sealer = new ExtensionInstanceSealer($key);

        $original = "foobar";

        $sealed = $sealer->sealExtensionInstanceSecret($original);
        $unsealed = $sealer->unsealExtensionInstanceSecret($sealed);

        assertThat($sealed, logicalNot(equalTo($original)));
        assertThat($unsealed, equalTo($original));
    }

    #[Test]
    public function sealIsIdempotent(): void
    {
        $key = random_bytes(256 / 8);
        $sealer = new ExtensionInstanceSealer($key);

        $sealedOnce = $sealer->sealExtensionInstanceSecret("foobar");
        $sealedTwice = $sealer->sealExtensionInstanceSecret($sealedOnce);

        assertThat($sealedOnce, equalTo($sealedTwice));
    }

    #[Test]
    public function unsealIsIdempotent(): void
    {
        $key = random_bytes(256 / 8);
        $sealer = new ExtensionInstanceSealer($key);

        $original = "foobar";

        $unsealed = $sealer->unsealExtensionInstanceSecret($original);
        assertThat($unsealed, equalTo($original));
    }
}