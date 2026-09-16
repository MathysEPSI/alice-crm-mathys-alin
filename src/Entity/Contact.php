<?php

namespace App\Entity;

use libphonenumber\PhoneNumber;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ContactRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    // Lettres (accents inclus), chiffres, espaces, apostrophes, tirets et underscores
    private const NAME_PATTERN = '/^[a-zA-Z0-9À-ÿ\s\'_\-]*$/';
    private const NAME_MESSAGE = 'Le champ ne doit contenir que des lettres, des chiffres, des tirets et des underscores.';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez renseigner un Prénom !')]
    #[Assert\Length(min: 2, max: 30, minMessage: 'Le Prénom contient moins de {{ limit }} caractères ?', maxMessage: 'Le Prénom contient plus de {{ limit }} caractères ?')]
    #[Assert\Regex(pattern: self::NAME_PATTERN, message: self::NAME_MESSAGE)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez renseigner un Nom !')]
    #[Assert\Length(min: 2, max: 30, minMessage: 'Le Nom contient moins de {{ limit }} caractères ?', maxMessage: 'Le Nom contient plus de {{ limit }} caractères ?')]
    #[Assert\Regex(pattern: self::NAME_PATTERN, message: self::NAME_MESSAGE)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez renseigner votre adresse email.')]
    #[Assert\Length(min: 5, max: 255, minMessage: 'Votre email contient moins de {{ limit }} caractères ?', maxMessage: 'Votre email est trop long !')]
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Veuillez renseigner une fonction !')]
    #[Assert\Regex(pattern: self::NAME_PATTERN, message: self::NAME_MESSAGE)]
    private ?string $position = null;

    #[ORM\Column]
    private ?bool $isMain = null;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    private ?PhoneNumber $phone = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isIsMain(): ?bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): self
    {
        $this->isMain = $isMain;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPhone(): ?\libphonenumber\PhoneNumber
    {
        return $this->phone;
    }

    public function setPhone(?\libphonenumber\PhoneNumber $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

}
