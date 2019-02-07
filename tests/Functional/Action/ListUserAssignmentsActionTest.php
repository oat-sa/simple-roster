<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListUserAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    private const LIST_USER_ASSIGNMENTS_URI = '/api/v1/assignments';

    public function testWithNonAuthenticatedUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::LIST_USER_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Full authentication is required to access this resource.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testItReturnListOfUserAssignments(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(Request::METHOD_GET, self::LIST_USER_ASSIGNMENTS_URI);

        $lineItem = $user->getLastAssignment()->getLineItem();

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals([
            'assignments' => [
                [
                    'id' => $user->getLastAssignment()->getId(),
                    'username' => $user->getUsername(),
                    'state' => Assignment::STATE_READY,
                    'lineItem' => [
                        'uri' => $lineItem->getUri(),
                        'label' => $lineItem->getLabel(),
                        'startDateTime' => $lineItem->getStartAt()->getTimestamp(),
                        'endDateTime' => $lineItem->getEndAt()->getTimestamp(),
                        'infrastructure' => $lineItem->getInfrastructure()->getId(),
                    ],
                ],
            ],
        ], json_decode($client->getResponse()->getContent(), true));
    }
}
