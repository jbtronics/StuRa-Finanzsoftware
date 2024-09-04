<?php

declare(strict_types=1);


namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Repository\BankAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This entity describes a person, that can confirm payment orders
 */
#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'])]
class Confirmer implements DBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * The name of the confirmer
     * @var string
     */
    #[Assert\NotBlank()]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name = '';

    /**
     * The email of the confirmer
     * @var string
     */
    #[Assert\Email]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email = '';

    /**
     * The phone number of the confirmer (optional)
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    /**
     * @var string Additional information about the confirmer (optional)
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $comment = '';

    /**
     * @var Collection The departments the confirmer is responsible for
     */
    #[ORM\ManyToMany(targetEntity: Department::class, mappedBy: 'confirmers')]
    private Collection $departments;

    public function __construct()
    {
        $this->departments = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function setDepartments(Collection $departments): void
    {
        $this->departments = $departments;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}