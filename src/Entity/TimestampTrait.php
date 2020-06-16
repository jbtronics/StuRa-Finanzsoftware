<?php


namespace App\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait TimestampTrait
{
    /**
     * @var DateTime the date when this element was modified the last time
     * @ORM\Column(type="datetime", name="last_modified", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $lastModified;

    /**
     * @var DateTime the date when this element was created
     * @ORM\Column(type="datetime", name="datetime_added", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $creationDate;

    /**
     * Helper for updating the timestamp. It is automatically called by doctrine before persisting.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps(): void
    {
        $this->lastModified = new DateTime('now');
        if (null === $this->creationDate) {
            $this->creationDate = new DateTime('now');
        }
    }

    /**
     * Returns the datetime this element was created. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    /**
     * Returns the datetime this element was last time modified. Returns null, if it was not persisted yet.
     * @return \DateTime|null
     */
    public function getLastModifiedDate(): ?\DateTime
    {
        return $this->lastModified;
    }
}