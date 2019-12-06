<?php namespace app\libraries\homework\Entities;


use DateTime;

class MetadataEntity {
    /** @var LibraryEntity */
    protected $baseEntity;

    /** @var string */
    protected $name;

    /** @var string */
    protected $sourceType;

    /** @var int */
    protected $gradeableCount;

    /** @var DateTime */
    protected $dateUpdated;

    /** @var DateTime */
    protected $dateCreated;

    /**
     * @param LibraryEntity $entity
     * @param string        $name
     * @param string        $sourceType
     * @param int           $gradeableCount
     * @param DateTime      $dateUpdated
     * @param DateTime      $dateCreated
     */
    public function __construct(
        LibraryEntity $entity,
        string $name,
        string $sourceType,
        int $gradeableCount,
        DateTime $dateUpdated,
        DateTime $dateCreated
    ) {
        $this->name = $name;
        $this->baseEntity = $entity;
        $this->sourceType = $sourceType;
        $this->dateUpdated = $dateUpdated;
        $this->dateCreated = $dateCreated;
        $this->gradeableCount = $gradeableCount;
    }

    /**
     * Returns associated library information.
     *
     * @return LibraryEntity
     */
    public function getLibrary() {
        return $this->baseEntity;
    }

    /**
     * Returns the library's name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get the library source type, git or zip
     *
     * @return string
     */
    public function getSourceType(): string {
        return $this->sourceType;
    }

    /**
     * Returns number of gradeables in the library
     *
     * @return int
     */
    public function getGradeableCount(): int {
        return $this->gradeableCount;
    }

    /**
     * Returns the last updated date, for zip libraries this will always be the created date
     *
     * @return DateTime
     */
    public function getLastUpdatedDate(): DateTime {
        return $this->dateUpdated;
    }

    /**
     * When was the library added?
     *
     * @return DateTime
     */
    public function getCreatedDate(): DateTime {
        return $this->dateCreated;
    }
}
