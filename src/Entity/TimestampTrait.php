<?php


namespace App\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @package App\Entity
 */
trait TimestampTrait
{
    /**
     * @var DateTime the date when this element was modified the last time
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $last_modified;

    /**
     * @var DateTime the date when this element was created
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $creation_date;

    /**
     * Helper for updating the timestamp. It is automatically called by doctrine before persisting.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps(): void
    {
        $this->last_modified = new DateTime('now');
        if (null === $this->creation_date) {
            $this->creation_date = new DateTime('now');
        }
    }

    /**
     * Returns the datetime this element was created. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    /**
     * Returns the datetime this element was last time modified. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getLastModified(): ?\DateTime
    {
        return $this->last_modified;
    }
}