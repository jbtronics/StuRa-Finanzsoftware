<?php

declare(strict_types=1);


namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This entity describes a person, that can confirm payment orders
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'])]
class Confirmer implements DBElementInterface, TimestampedElementInterface, \Stringable
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

    #[ORM\OneToMany(targetEntity: ConfirmationToken::class, mappedBy: 'confirmer')]
    private Collection $confirmationTokens;

    public function __construct()
    {
        $this->departments = new ArrayCollection();
        $this->confirmationTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Confirmer
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): Confirmer
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): Confirmer
    {
        $this->phone = $phone;
        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): Confirmer
    {
        $this->comment = $comment;
        return $this;
    }

    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): Confirmer
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            if (!$department->getConfirmers()->contains($this)) {
                $department->getConfirmers()->add($this);
            }
        }
        return $this;
    }

    public function removeDepartment(Department $department): Confirmer
    {
        if ($this->departments->contains($department)) {
            $this->departments->removeElement($department);
            if ($department->getConfirmers()->contains($this)) {
                $department->getConfirmers()->removeElement($this);
            }
        }
        return $this;
    }

    public function getConfirmationTokens(): Collection
    {
        return $this->confirmationTokens;
    }

    public function __toString()
    {
        return $this->name . ' (' . $this->email . ')';
    }
}