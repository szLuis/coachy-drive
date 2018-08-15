<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Drive
 *
 * @ORM\Table(name="drive")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DriveRepository")
 */
class Drive
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=6)
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateCreated", type="datetime")
     */
    private $dateCreated;

    /**
     * @var string
     *
     * @ORM\Column(name="link_details", type="string", length=255)
     */
    private $linkDetails;

    /**
     * @var bool
     *
     * @ORM\Column(name="star", type="boolean")
     */
    private $star;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var bool
     *
     * @ORM\Column(name="has_children", type="boolean")
     */
    private $hasChildren;

    /**
     * @var array
     *
     * @ORM\Column(name="children", type="simple_array", nullable=true)
     */
    private $children;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set icon
     *
     * @param string $icon
     *
     * @return Drive
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Drive
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Drive
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set star
     *
     * @param boolean $star
     *
     * @return Drive
     */
    public function setStar($star)
    {
        $this->star = $star;

        return $this;
    }

    /**
     * Get star
     *
     * @return bool
     */
    public function getStar()
    {
        return $this->star;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return Drive
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set hasChildren
     *
     * @param boolean $hasChildren
     *
     * @return Drive
     */
    public function setHasChildren($hasChildren)
    {
        $this->hasChildren = $hasChildren;

        return $this;
    }

    /**
     * Get hasChildren
     *
     * @return bool
     */
    public function getHasChildren()
    {
        return $this->hasChildren;
    }

    /**
     * Set linkDetails
     *
     * @param string $linkDetails
     *
     * @return Drive
     */
    public function setLinkDetails($linkDetails)
    {
        $this->linkDetails = $linkDetails;

        return $this;
    }

    /**
     * Get linkDetails
     *
     * @return string
     */
    public function getLinkDetails()
    {
        return $this->linkDetails;
    }

    /**
     * Set children
     *
     * @param array $children
     *
     * @return Drive
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Get children
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }
}
