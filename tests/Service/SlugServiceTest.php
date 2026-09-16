<?php

namespace App\Tests\Service;

use App\Service\SlugService;
use PHPUnit\Framework\TestCase;

class SlugServiceTest extends TestCase
{
    private SlugService $slugService;

    protected function setUp(): void
    {
        $this->slugService = new SlugService();
    }

    public function testSlugifyRemovesAccentsAndSpaces(): void
    {
        $this->assertSame('societe-generale', $this->slugService->slugify('Société Générale'));
    }

    public function testSlugifyFullnameJoinsFirstnameAndLastname(): void
    {
        $this->assertSame('jean-dupont', $this->slugService->slugifyFullname('Jean', 'Dupont'));
    }

    public function testSlugifyFullnameHandlesMissingPart(): void
    {
        $this->assertSame('dupont', $this->slugService->slugifyFullname(null, 'Dupont'));
        $this->assertSame('jean', $this->slugService->slugifyFullname('Jean', null));
    }
}
