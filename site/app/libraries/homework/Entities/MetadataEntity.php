<?php namespace app\libraries\homework\Entities;


use DateTime;

class MetadataEntity {
    /** @var LibraryEntity */
    protected $baseEntity;

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
     * @param string        $sourceType
     * @param int           $gradeableCount
     * @param DateTime      $dateUpdated
     * @param DateTime      $dateCreated
     */
    public function __construct(
        LibraryEntity $entity,
        string $sourceType,
        int $gradeableCount,
        DateTime $dateUpdated,
        DateTime $dateCreated
    ) {
        $this->baseEntity = $entity;
        $this->sourceType = $sourceType;
        $this->dateUpdated = $dateUpdated;
        $this->dateCreated = $dateCreated;
        $this->gradeableCount = $gradeableCount;
    }

    /**
     * @return LibraryEntity
     */
    public function getLibrary() {
        return $this->baseEntity;
    }

}
