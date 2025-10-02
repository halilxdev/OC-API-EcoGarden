<?php

namespace App\Entity;

use App\Repository\MonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonthRepository::class)]
class Month
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numeric_value = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\ManyToMany(targetEntity: Advice::class, mappedBy: 'month')]
    private Collection $advice;

    public function __construct()
    {
        $this->advice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumericValue(): ?int
    {
        return $this->numeric_value;
    }

    public function setNumericValue(int $numeric_value): static
    {
        $this->numeric_value = $numeric_value;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advice;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advice->contains($advice)) {
            $this->advice->add($advice);
            $advice->addMonth($this);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        if ($this->advice->removeElement($advice)) {
            $advice->removeMonth($this);
        }

        return $this;
    }
}
