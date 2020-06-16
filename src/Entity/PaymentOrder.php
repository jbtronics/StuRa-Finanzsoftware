<?php

namespace App\Entity;

use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\TimestampedElementInterface;
use App\Entity\Embeddable\BankAccountInfo;
use App\Repository\PaymentOrderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PaymentOrderRepository::class)
 * @ORM\Table("payment_orders")
 * @ORM\HasLifecycleCallbacks()
 */
class PaymentOrder implements DBElementInterface, TimestampedElementInterface
{
    use TimestampTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var BankAccountInfo
     * @ORM\Embedded(class="App\Entity\Embeddable\BankAccountInfo")
     * @Assert\Valid()
     */
    private $bank_info;

    /**
     * @var string "Vorname"
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $first_name = "";

    /**
     * @var string "Nachname"
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $last_name = "";

    /**
     * @var Department "Struktur/Organisation"
     * @ORM\ManyToOne(targetEntity="App\Entity\Department")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull()
     */
    private $department;

    /**
     * @var string "Projektbezeichnung"
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $project_name = "";

    /**
     * @var int "Betrag"
     * @ORM\Column(type="integer")
     * @Assert\Positive()
     */
    private $amount = null;

    /**
     * @var bool "mathematisch richtig"
     * @ORM\Column(type="boolean")
     */
    private $mathematically_correct = false;

    /**
     * @var bool "sachlich richtig"
     * @ORM\Column(type="boolean")
     */
    private $factually_correct = false;

    public function __construct()
    {
        $this->bank_info = new BankAccountInfo();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return BankAccountInfo
     */
    public function getBankInfo(): BankAccountInfo
    {
        return $this->bank_info;
    }

    /**
     * @param  BankAccountInfo  $bank_info
     * @return PaymentOrder
     */
    public function setBankInfo(BankAccountInfo $bank_info): PaymentOrder
    {
        $this->bank_info = $bank_info;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * @param  string  $first_name
     * @return PaymentOrder
     */
    public function setFirstName(string $first_name): PaymentOrder
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->last_name;
    }

    /**
     * @param  string  $last_name
     * @return PaymentOrder
     */
    public function setLastName(string $last_name): PaymentOrder
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @return Department
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * @param  Department  $department
     * @return PaymentOrder
     */
    public function setDepartment(Department $department): PaymentOrder
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->project_name;
    }

    /**
     * @param  string  $project_name
     * @return PaymentOrder
     */
    public function setProjectName(string $project_name): PaymentOrder
    {
        $this->project_name = $project_name;
        return $this;
    }

    /**
     * Returns the requested amount of money in cents.
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @param  int  $amount
     * @return PaymentOrder
     */
    public function setAmount(int $amount): PaymentOrder
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMathematicallyCorrect(): bool
    {
        return $this->mathematically_correct;
    }

    /**
     * @param  bool  $mathematically_correct
     * @return PaymentOrder
     */
    public function setMathematicallyCorrect(bool $mathematically_correct): PaymentOrder
    {
        $this->mathematically_correct = $mathematically_correct;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFactuallyCorrect(): bool
    {
        return $this->factually_correct;
    }

    /**
     * @param  bool  $factually_correct
     * @return PaymentOrder
     */
    public function setFactuallyCorrect(bool $factually_correct): PaymentOrder
    {
        $this->factually_correct = $factually_correct;
        return $this;
    }
}
