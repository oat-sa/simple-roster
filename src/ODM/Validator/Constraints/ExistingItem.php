<?php

namespace App\ODM\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ExistingItem extends Constraint
{
    public $message = 'The "{{ item_class }}" item with id "{{ value }}" does not exist';
    public $itemClass;
}