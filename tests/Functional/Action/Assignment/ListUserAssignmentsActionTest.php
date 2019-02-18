<?php declare(strict_types=1);

namespace App\Tests\Functional\Action\Assignment;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Carbon\Carbon;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListUserAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testWithNonAuthenticatedUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/assignments');

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

    public function testItReturnListOfUserAssignmentsWhenCurrentDateMatchesLineItemAvailability(): void
    {
        Carbon::setTestNow(new DateTime('2019-01-01 00:00:00'));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(Request::METHOD_GET, '/api/v1/assignments');

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

    public function testItReturnListOfUserAssignmentsWhenCurrentDateDoesNotMatchLineItemAvailability(): void
    {
        Carbon::setTestNow(new DateTime('2022-01-01 00:00:00'));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(Request::METHOD_GET, '/api/v1/assignments');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals(['assignments' => [],], json_decode($client->getResponse()->getContent(), true));
    }
}
