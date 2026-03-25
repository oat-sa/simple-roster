<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Service\Rostering;

use League\Csv\Reader;
use LogicException;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileProcessor;
use OAT\SimpleRoster\Tests\AppKernelTestCase;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RosteringFileProcessorTest extends AppKernelTestCase
{
    use DatabaseTestingTrait;

    private RosteringFileProcessor $subject;
    private FileStorageInterface $fileStorage;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private UserCacheIdGenerator $userCacheIdGenerator;
    private CacheItemPoolInterface $resultCache;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('Rostering/existing_users_with_line_items.yml');

        $container = self::getContainer();
        $this->fileStorage = $container->get(FileStorageInterface::class);
        $this->passwordHasher = $container->get('security.user_password_hasher');
        $this->userRepository = $container->get(UserRepository::class);
        $this->userCacheIdGenerator = $container->get(UserCacheIdGenerator::class);
        $this->subject = $container->get(RosteringFileProcessor::class);

        $resultCacheImplementation = $this->getEntityManager()->getConfiguration()->getResultCache();
        if (!$resultCacheImplementation instanceof CacheItemPoolInterface) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
    }

    public function testProcessImportsUsersAndWritesResultFile(): void
    {
        $availableLineItemSlugs = $this->fetchAvailableLineItemSlugs();
        $primaryLineItemSlug = $availableLineItemSlugs[0];
        $secondaryLineItemSlug = $availableLineItemSlugs[1] ?? $availableLineItemSlugs[0];

        $this->insertAssignment('existing_user', $primaryLineItemSlug);
        $this->insertAssignment('existing_user', $secondaryLineItemSlug);
        $this->insertAssignment('existing_group_user', $primaryLineItemSlug);
        $this->insertAssignment('inactive_existing_user', $primaryLineItemSlug);

        $inactiveExistingUserId = (int)$this->getEntityManager()->getConnection()->fetchOne(
            'SELECT id FROM users WHERE username = :username',
            ['username' => 'inactive_existing_user']
        );
        self::assertGreaterThan(0, $inactiveExistingUserId);

        $inactiveExistingUserWithoutAssignmentId = (int)$this->getEntityManager()->getConnection()->fetchOne(
            'SELECT id FROM users WHERE username = :username',
            ['username' => 'inactive_existing_user_without_assignment']
        );
        self::assertGreaterThan(0, $inactiveExistingUserWithoutAssignmentId);

        /** @var User $existingUserBefore */
        $existingUserBefore = $this->getRepository(User::class)->findOneBy(['username' => 'existing_user']);
        self::assertInstanceOf(User::class, $existingUserBefore);
        $existingHashBefore = (string)$existingUserBefore->getPassword();

        $csv = sprintf(<<<'CSV'
hierarchy_parentOrganizationId,user_username,user_password,user_organizationId,session_name,user_active,principal_username,marker
Root,new_user,NewPass1,SCHOOL_1,%s,true,,new_user
Root,existing_user,ChangedPass1,,%s,1,,pwd_assignment_update
Root,existing_group_user,,NEW_GROUP,,true,,org_update
SCHOOL_1,,,,,,,class_skip
Root,,,,,,principal_a,principal_skip
Root,,MissingPass,SCHOOL_2,%s,true,,missing_username
Root,bad username,Pass1,SCHOOL_3,%s,true,,bad_username
Root,bad_org,Pass2,BAD ORG,%s,true,,bad_org
Root,inactive_user,Pass3,SCHOOL_4,%s,false,,inactive
Root,inactive_existing_user,,,,false,,inactive_existing
Root,inactive_existing_user_without_assignment,,,,false,,inactive_existing_without_assignment
Root,wrong_bool,Pass4,SCHOOL_5,%s,maybe,,invalid_bool
Root,missing_user_pwd,,SCHOOL_6,%s,true,,missing_user_pwd
Root,missing_user_org,Pass5,,%s,true,,missing_user_org
Root,missing_session_name,Pass6,SCHOOL_6,,true,,missing_session_name
Root,bad_session_name,Pass7,SCHOOL_7,missing-slug,true,,bad_session_name
Root,existing_user,,,,true,,noop
CSV,
            $primaryLineItemSlug,
            $secondaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug,
            $primaryLineItemSlug
        );

        $this->storeProcessingFile('ref-import', $csv);

        $this->subject->process('ref-import');
        $this->getEntityManager()->clear();

        /** @var User|null $newUser */
        $newUser = $this->getRepository(User::class)->findOneBy(['username' => 'new_user']);
        self::assertInstanceOf(User::class, $newUser);
        self::assertSame('SCHOOL_1', $newUser->getGroupId());
        self::assertTrue($this->passwordHasher->isPasswordValid($newUser, 'NewPass1'));
        self::assertSame([$primaryLineItemSlug], $this->fetchAssignmentSlugs('new_user'));

        /** @var User|null $existingUserAfter */
        $existingUserAfter = $this->getRepository(User::class)->findOneBy(['username' => 'existing_user']);
        self::assertInstanceOf(User::class, $existingUserAfter);
        self::assertNotSame($existingHashBefore, $existingUserAfter->getPassword());
        self::assertTrue($this->passwordHasher->isPasswordValid($existingUserAfter, 'ChangedPass1'));
        self::assertSame([$secondaryLineItemSlug], $this->fetchAssignmentSlugs('existing_user'));

        /** @var User|null $existingGroupUserAfter */
        $existingGroupUserAfter = $this->getRepository(User::class)->findOneBy(['username' => 'existing_group_user']);
        self::assertInstanceOf(User::class, $existingGroupUserAfter);
        self::assertSame('NEW_GROUP', $existingGroupUserAfter->getGroupId());
        self::assertSame([$primaryLineItemSlug], $this->fetchAssignmentSlugs('existing_group_user'));

        self::assertNull($this->getRepository(User::class)->findOneBy(['username' => 'test_taker']));
        self::assertNull($this->getRepository(User::class)->findOneBy(['username' => 'inactive_user']));
        self::assertNull($this->getRepository(User::class)->findOneBy(['username' => 'inactive_existing_user']));
        self::assertNull($this->getRepository(User::class)->findOneBy(['username' => 'inactive_existing_user_without_assignment']));
        self::assertSame(
            0,
            (int)$this->getEntityManager()->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM assignments WHERE user_id = :userId',
                ['userId' => $inactiveExistingUserId]
            )
        );
        self::assertSame(
            0,
            (int)$this->getEntityManager()->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM assignments WHERE user_id = :userId',
                ['userId' => $inactiveExistingUserWithoutAssignmentId]
            )
        );

        $records = $this->readResultRecords('ref-import');
        $rowsByMarker = [];
        foreach ($records as $record) {
            $rowsByMarker[$record['marker']] = $record;
        }

        self::assertSame('processed', $rowsByMarker['new_user']['status']);
        self::assertSame('processed', $rowsByMarker['pwd_assignment_update']['status']);
        self::assertSame('processed', $rowsByMarker['org_update']['status']);
        self::assertSame('processed', $rowsByMarker['class_skip']['status']);
        self::assertSame('processed', $rowsByMarker['principal_skip']['status']);
        self::assertSame('processed', $rowsByMarker['inactive']['status']);
        self::assertSame('processed', $rowsByMarker['inactive_existing']['status']);
        self::assertSame('processed', $rowsByMarker['inactive_existing_without_assignment']['status']);
        self::assertSame('processed', $rowsByMarker['noop']['status']);

        self::assertSame('400', $rowsByMarker['missing_username']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['missing_username']['errorCode']);

        self::assertSame('400', $rowsByMarker['bad_username']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['bad_username']['errorCode']);

        self::assertSame('400', $rowsByMarker['bad_org']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['bad_org']['errorCode']);

        self::assertSame('400', $rowsByMarker['invalid_bool']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['invalid_bool']['errorCode']);

        self::assertSame('400', $rowsByMarker['missing_user_pwd']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['missing_user_pwd']['errorCode']);

        self::assertSame('400', $rowsByMarker['missing_user_org']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['missing_user_org']['errorCode']);

        self::assertSame('400', $rowsByMarker['missing_session_name']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['missing_session_name']['errorCode']);

        self::assertSame('400', $rowsByMarker['bad_session_name']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['bad_session_name']['errorCode']);
    }

    public function testProcessSkipsFileWithoutImportableRows(): void
    {
        $csv = <<<'CSV'
hierarchy_parentOrganizationId,principal_username,marker
Root,principal_1,row_1
SCHOOL_1,principal_2,row_2
CSV;

        $this->storeProcessingFile('ref-no-import', $csv);
        $before = $this->readProcessingFileContent('ref-no-import');

        $this->subject->process('ref-no-import');

        $after = $this->readProcessingFileContent('ref-no-import');
        self::assertSame($before, $after);
    }

    public function testProcessSkipsEmptyRowsAndDoesNotWriteThemToResultCsv(): void
    {
        $availableLineItemSlugs = $this->fetchAvailableLineItemSlugs();
        $sessionName = $availableLineItemSlugs[0];

        $csv = sprintf(<<<'CSV'
hierarchy_parentOrganizationId,user_username,user_password,user_organizationId,session_name,user_active,principal_username,marker
Root,empty_row_case_user_1,Password123,SCHOOL_1,%s,true,,row_1

,,,,,,,
Root,empty_row_case_user_2,Password456,SCHOOL_2,%s,true,,row_2
CSV,
            $sessionName,
            $sessionName
        );

        $this->storeProcessingFile('ref-skip-empty-rows', $csv);
        $this->subject->process('ref-skip-empty-rows');

        $records = $this->readResultRecords('ref-skip-empty-rows');
        self::assertCount(2, $records);

        $rowsByMarker = [];
        foreach ($records as $record) {
            $rowsByMarker[$record['marker']] = $record;
        }

        self::assertArrayHasKey('row_1', $rowsByMarker);
        self::assertArrayHasKey('row_2', $rowsByMarker);
        self::assertArrayNotHasKey('', $rowsByMarker);
        self::assertSame('processed', $rowsByMarker['row_1']['status']);
        self::assertSame('processed', $rowsByMarker['row_2']['status']);
    }

    public function testProcessUpdatesPasswordForExistingUserAndRejectsMissingUserWithPasswordOnlyPayload(): void
    {
        /** @var User $existingBefore */
        $existingBefore = $this->getRepository(User::class)->findOneBy(['username' => 'existing_user']);
        self::assertInstanceOf(User::class, $existingBefore);
        $oldHash = (string)$existingBefore->getPassword();
        $oldGroupId = (string)$existingBefore->getGroupId();

        $csv = <<<'CSV'
user_username,user_password,marker
existing_user,ChangedOnlyPassword1,existing_password_updated
missing_user,SomePassword1,missing_user_rejected
CSV;

        $this->storeProcessingFile('ref-case-1', $csv);

        $this->subject->process('ref-case-1');
        $this->getEntityManager()->clear();

        /** @var User $existingAfter */
        $existingAfter = $this->getRepository(User::class)->findOneBy(['username' => 'existing_user']);
        self::assertInstanceOf(User::class, $existingAfter);
        self::assertNotSame($oldHash, (string)$existingAfter->getPassword());
        self::assertTrue($this->passwordHasher->isPasswordValid($existingAfter, 'ChangedOnlyPassword1'));
        self::assertSame($oldGroupId, $existingAfter->getGroupId());

        self::assertNull($this->getRepository(User::class)->findOneBy(['username' => 'missing_user']));

        $records = $this->readResultRecords('ref-case-1');
        $rowsByMarker = [];
        foreach ($records as $record) {
            $rowsByMarker[$record['marker']] = $record;
        }

        self::assertSame('processed', $rowsByMarker['existing_password_updated']['status']);
        self::assertSame('', $rowsByMarker['existing_password_updated']['errorCode']);
        self::assertSame('400', $rowsByMarker['missing_user_rejected']['status']);
        self::assertSame('validation.fieldError', $rowsByMarker['missing_user_rejected']['errorCode']);
    }

    public function testProcessWarmsUpUserCacheForChangedRows(): void
    {
        $availableLineItemSlugs = $this->fetchAvailableLineItemSlugs();
        $currentLineItemSlug = $availableLineItemSlugs[0];
        $updatedLineItemSlug = $availableLineItemSlugs[1] ?? $availableLineItemSlugs[0];
        $username = 'existing_user';
        $cacheKey = $this->userCacheIdGenerator->generate($username);

        $this->insertAssignment($username, $currentLineItemSlug);

        // Prime cache for this user before rostering update.
        $this->userRepository->findByUsernameWithAssignments($username);
        self::assertTrue($this->resultCache->hasItem($cacheKey));

        $csv = sprintf(
            <<<'CSV'
user_username,session_name,marker
existing_user,%s,cache_invalidated
CSV,
            $updatedLineItemSlug
        );

        $this->storeProcessingFile('ref-cache-invalidated', $csv);

        $this->subject->process('ref-cache-invalidated');

        self::assertFalse($this->resultCache->hasItem($cacheKey));

        $this->getEntityManager()->clear();
        $reloadedUser = $this->userRepository->findByUsernameWithAssignments($username);
        self::assertSame($updatedLineItemSlug, $reloadedUser->getLastAssignment()->getLineItem()->getSlug());
    }

    private function storeProcessingFile(string $referenceId, string $csv): void
    {
        $stream = fopen('php://temp', 'rb+');
        self::assertNotFalse($stream);
        fwrite($stream, $csv);
        rewind($stream);

        $this->fileStorage->store($stream, $this->buildInputKey($referenceId));
    }

    private function readProcessingFileContent(string $referenceId): string
    {
        $stream = $this->fileStorage->read($this->buildInputKey($referenceId));
        self::assertTrue(is_resource($stream));

        $content = stream_get_contents($stream);
        fclose($stream);

        self::assertIsString($content);

        return $content;
    }

    /**
     * @return array<array<string, string>>
     */
    private function readResultRecords(string $referenceId): array
    {
        $stream = $this->fileStorage->read($this->buildResultKey($referenceId));
        self::assertTrue(is_resource($stream));
        $content = stream_get_contents($stream);
        fclose($stream);

        self::assertIsString($content);
        $reader = Reader::fromString($content);
        $reader->setHeaderOffset(0);

        return array_values(iterator_to_array($reader->getRecords()));
    }

    private function buildInputKey(string $referenceId): string
    {
        return sprintf('%s/input.csv', $referenceId);
    }

    private function buildResultKey(string $referenceId): string
    {
        return sprintf('%s/sr-output.csv', $referenceId);
    }

    private function insertAssignment(string $username, string $lineItemSlug): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $userId = $connection->fetchOne('SELECT id FROM users WHERE username = :username', ['username' => $username]);
        $lineItemId = $connection->fetchOne('SELECT id FROM line_items WHERE slug = :slug', ['slug' => $lineItemSlug]);

        self::assertNotFalse($userId);
        self::assertNotFalse($lineItemId);
        self::assertNotNull($userId);
        self::assertNotNull($lineItemId);

        $connection->executeStatement(
            'INSERT INTO assignments (user_id, line_item_id, state, attempts_count, updated_at) '
            . 'VALUES (:userId, :lineItemId, :state, 0, :updatedAt)',
            [
                'userId' => (int)$userId,
                'lineItemId' => (int)$lineItemId,
                'state' => Assignment::STATE_READY,
                'updatedAt' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @return array<string>
     */
    private function fetchAvailableLineItemSlugs(): array
    {
        $slugs = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT slug FROM line_items ORDER BY id ASC'
        );

        self::assertNotEmpty($slugs);

        return array_values(array_map('strval', $slugs));
    }

    /**
     * @return array<string>
     */
    private function fetchAssignmentSlugs(string $username): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT li.slug '
            . 'FROM assignments a '
            . 'INNER JOIN users u ON u.id = a.user_id '
            . 'INNER JOIN line_items li ON li.id = a.line_item_id '
            . 'WHERE u.username = :username '
            . 'ORDER BY a.id ASC',
            ['username' => $username]
        );

        return array_values(array_map('strval', $rows));
    }
}
