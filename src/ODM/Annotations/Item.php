<?php declare(strict_types=1);

namespace App\ODM\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Item
{
    /**
     * @var string
     * @Required()
     */
    public $table;

    /**
     * @var string
     * @Required()
     */
    public $primaryKey;
}