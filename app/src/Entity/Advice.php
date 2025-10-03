<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAdvices"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu du conseil ne peut pas être vide")]
    #[Groups(["getAdvices"])]
    private ?string $text = null;

    /**
     * @var Collection<int, Month>
     */
    #[ORM\ManyToMany(targetEntity: Month::class, inversedBy: 'advice')]
    #[Groups(["getAdvices"])]
    private Collection $month;

    public function __construct()
    {
        $this->month = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection<int, Month>
     */
    public function getMonth(): Collection
    {
        return $this->month;
    }

    public function addMonth(Month $month): static
    {
        if (!$this->month->contains($month)) {
            $this->month->add($month);
        }

        return $this;
    }

    public function removeMonth(Month $month): static
    {
        $this->month->removeElement($month);

        return $this;
    }
}
