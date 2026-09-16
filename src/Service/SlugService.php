<?php

namespace App\Service;

use Cocur\Slugify\Slugify;

class SlugService
{
    private Slugify $slugify;

    public function __construct()
    {
        $this->slugify = new Slugify();
    }

    /** Transforme une chaîne libre en slug d'URL. */
    public function slugify(string $text): string
    {
        return $this->slugify->slugify($text);
    }

    /** Construit le slug d'une personne à partir de son prénom et de son nom. */
    public function slugifyFullname(?string $firstname, ?string $lastname): string
    {
        return $this->slugify(trim($firstname . ' ' . $lastname));
    }
}
