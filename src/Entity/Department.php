<?php

namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\NamedElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DepartmentRepository::class)
 * @ORM\Table("departments")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"name"})
 */
class Department implements DBElementInterface, NamedElementInterface, TimestampedElementInterface
{
    public const ALLOWED_TYPES = ["fsr", "section", "misc"];

    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=Department::ALLOWED_TYPES)
     * @var string|null
     */
    private $type;

    /**
     * @var bool If an FSR is blocked it can not submit new payment orders
     * @ORM\Column(type="boolean")
     */
    private $blocked = false;

    /**
     * @ORM\Column(type="text")
     */
    private $comment = "";

    /**
     * Returns the type of this department (whether it is an FSR, an section or something else)
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     *
     * @param  string  $type
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Checks if this department is blocked. If it is blocked it can not create new PaymentOrders...
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * @param  bool  $is_blocked
     */
    public function setBlocked(bool $is_blocked): self
    {
        $this->blocked = $is_blocked;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param  string  $comment
     * @return Department
     */
    public function setComment(string $comment): Department
    {
        $this->comment = $comment;
        return $this;
    }


}
