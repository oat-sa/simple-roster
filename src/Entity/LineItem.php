<?php

namespace App\Entity;

class LineItem extends Entity
{
    protected $requiredProperties = ['tao_uri', 'title', /*'start_date_time', 'end_date_time',*/ 'infrastructure_id'];

    public function getTable(): string
    {
        return 'line_items';
    }

    public function getKey()
    {
        return 'tao_uri';
    }
}
