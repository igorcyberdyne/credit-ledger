<?php

namespace App\Entity\Abstracts;

use App\Entity\Contracts\SoftDeletableInterface;
use App\Entity\Traits\SoftDeletableTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
#[ORM\MappedSuperclass]
abstract class BaseEntitySoftDeletable extends BaseEntity implements SoftDeletableInterface
{
    use SoftDeletableTrait;
}
