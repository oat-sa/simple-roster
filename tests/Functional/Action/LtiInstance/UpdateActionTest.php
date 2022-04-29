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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\LtiInstance;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateActionTest extends AbstractLtiInstanceTest
{
    protected string $url = '/api/v1/lti-instances';
    protected string $method = Request::METHOD_PUT;

    public function testInvalidAuth(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url . '/1',
            [],
            [],
            ['PHP_AUTH_USER' => 'wrong', 'PHP_AUTH_PW' => 'invalid'],
            ''
        );

        self::assertEquals(
            Response::HTTP_UNAUTHORIZED,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testInvalidIndex(): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url . '/777',
            [],
            [],
            [],
            (string)json_encode([
                'label' => 'TestLabel',
                'lti_link' => 'http://test.test',
                'lti_key' => 'test1',
                'lti_secret' => 'test2'
            ])
        );

        self::assertEquals(
            Response::HTTP_NOT_FOUND,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    /**
     * @dataProvider provideInvalidRequests
     */
    public function testValidation(array $body): void
    {
        $this->kernelBrowser->request(
            $this->method,
            $this->url . '/1',
            [],
            [],
            [],
            (string)json_encode($body)
        );

        self::assertEquals(
            Response::HTTP_BAD_REQUEST,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testUpdateWithExitedLink(): void
    {
        $link = 'http://link.test';

        $ltiInstance = new LtiInstance(
            0,
            'TestLabel',
            $link,
            'test_key',
            'test_secrete'
        );

        $this->getEntityManager()->persist($ltiInstance);
        $this->getEntityManager()->flush();

        /** @var LtiInstance */
        $firstLtiInstance = current($this->data);
        $index = $firstLtiInstance->getId();

        $this->kernelBrowser->request(
            $this->method,
            $this->url . "/{$index}",
            [],
            [],
            [],
            (string)json_encode([
                'label' => 'TestLabel777',
                'lti_link' => $link,
                'lti_key' => 'test1',
                'lti_secret' => 'test2'
            ])
        );

        self::assertEquals(
            Response::HTTP_BAD_REQUEST,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testUpdateWithExitedLabel(): void
    {
        $label = 'lalalabel';

        $ltiInstance = new LtiInstance(
            0,
            $label,
            'http://ttt.ttt',
            'test_key',
            'test_secrete'
        );

        $this->getEntityManager()->persist($ltiInstance);
        $this->getEntityManager()->flush();

        /** @var LtiInstance */
        $firstLtiInstance = current($this->data);
        $index = $firstLtiInstance->getId();

        $this->kernelBrowser->request(
            $this->method,
            $this->url . "/{$index}",
            [],
            [],
            [],
            (string)json_encode([
                'label' => $label,
                'lti_link' => 'http://litk.test',
                'lti_key' => 'test1',
                'lti_secret' => 'test2'
            ])
        );

        self::assertEquals(
            Response::HTTP_BAD_REQUEST,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );
    }

    public function testValidRequest(): void
    {
        /** @var LtiInstance */
        $firstLtiInstance = current($this->data);
        $index = $firstLtiInstance->getId();

        $this->kernelBrowser->request(
            $this->method,
            $this->url . "/{$index}",
            [],
            [],
            [],
            (string)json_encode([
                'label' => 'TestLabel',
                'lti_link' => 'http://test.test',
                'lti_key' => 'test1',
                'lti_secret' => 'test2'
            ])
        );

        self::assertEquals(
            Response::HTTP_ACCEPTED,
            $this->kernelBrowser->getResponse()->getStatusCode()
        );

        /** @var LtiInstanceRepository */
        $repository = $this->getRepository(LtiInstance::class);

        /** @var LtiInstance $model */
        $model = $repository->findOneBy(['label' => 'TestLabel']);

        self::assertNotNull($model);
        self::assertEquals($index, $model->getId());

        self::assertEquals(count($this->data), count($repository->findAll()));
    }

    public function provideInvalidRequests(): array
    {
        return [
            'no_lti_secret' => [
                'body' => [
                    'label' => 'TestLabel',
                    'lti_link' => 'http://test.test',
                    'lti_key' => 'test1',
                ],
            ],
            'no_lti_key' => [
                'body' => [
                    'label' => 'TestLabel',
                    'lti_link' => 'http://test.test',
                    'lti_secret' => 'test2',
                ],
            ],
            'no_lti_link' => [
                'body' => [
                    'label' => 'TestLabel',
                    'lti_key' => 'test1',
                    'lti_secret' => 'test2',
                ],
            ],
            'no_lti_label' => [
                'body' => [
                    'lti_link' => 'http://test.test',
                    'lti_key' => 'test1',
                    'lti_secret' => 'test2',
                ],
            ],
        ];
    }
}
