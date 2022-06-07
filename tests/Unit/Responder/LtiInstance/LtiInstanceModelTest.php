<?php

namespace OAT\SimpleRoster\Tests\Unit\Responder\LtiInstance;

use OAT\SimpleRoster\Responder\LtiInstance\LtiInstanceModel;
use PHPUnit\Framework\TestCase;

class LtiInstanceModelTest extends TestCase
{
    public function testSetters(): void
    {
        $ltiInstanceModel = new LtiInstanceModel();
        $id = 1;
        $label = 'label';
        $link = 'https://link';
        $ltiInstanceModel
            ->setId($id)
            ->setLabel($label)
            ->setLtiLink($link);

        self::assertEquals([
            'id' => $id,
            'label' => $label,
            'lti_link' => $link
        ], $ltiInstanceModel->jsonSerialize());
    }
}
