<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\Responder;

use Ramsey\Uuid\UuidFactoryInterface;
use Twig\Environment;

class LtiOutcomeResponder
{
    /** @var Environment */
    private Environment $twig;

    /** @var UuidFactoryInterface */
    private UuidFactoryInterface $uuidFactory;

    public function __construct(Environment $twig, UuidFactoryInterface $uuidFactory)
    {
        $this->twig = $twig;
        $this->uuidFactory = $uuidFactory;
    }

    public function createXmlResponse(int $assignmentId): XmlResponse
    {
        $xml = $this->twig->render(
            'basic-outcome/replace-result-response.xml.twig',
            [
                'messageIdentifier' => $this->uuidFactory->uuid4(),
                'messageRefIdentifier' => $assignmentId,
                'description' => sprintf('Assignment with Id %d was updated', $assignmentId),
            ]
        );

        return new XmlResponse($xml);
    }
}
