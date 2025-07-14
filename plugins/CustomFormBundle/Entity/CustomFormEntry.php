<?php

namespace MauticPlugin\CustomFormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_form_entry")
 */
class CustomFormEntry
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enabled;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $textField1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $textField2;

    public function getId()
    {
        return $this->id;
    }
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
    public function getEnabled()
    {
        return $this->enabled;
    }
    public function setTextField1($textField1)
    {
        $this->textField1 = $textField1;
    }
    public function getTextField1()
    {
        return $this->textField1;
    }
    public function setTextField2($textField2)
    {
        $this->textField2 = $textField2;
    }
    public function getTextField2()
    {
        return $this->textField2;
    }
} 