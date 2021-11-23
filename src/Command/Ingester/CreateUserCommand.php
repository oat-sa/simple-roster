<?php

/*
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

namespace OAT\SimpleRoster\Command\Ingester;

use InvalidArgumentException;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Ingester\AssignmentIngester;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use Symfony\Component\Console\Command\Command;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use OAT\SimpleRoster\Command\CommandProgressBarFormatterTrait;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use League\Csv\Writer;
use Throwable;

class CreateUserCommand extends Command
{
    use CommandProgressBarFormatterTrait;
    public const NAME = 'roster:create-user';

    private NativeUserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;
    private AssignmentIngester $assignmentIngester;
    private LineItemRepository $lineItemRepository;
    private LtiInstanceRepository $ltiInstanceRepository;
    private Filesystem $filesystem;
    protected ProgressBar $progressBar;

    private const DEFAULT_BATCH_SIZE = '10';
    private const OPTION_LINE_ITEM_IDS = 'line-item-ids';
    private const OPTION_LINE_ITEM_SLUGS = 'line-item-slugs';
    private const OPTION_GROUP_PREFIX = 'group-prefix';
    private const DEFAULT_USERNAME_INCREMENT_NO = 0;

    private SymfonyStyle $symfonyStyle;
    /** @var string[] */
    private array $lineItemSlugs;
    private array $userPrefix;

    /** @var int[] */
    private array $lineItemIds;

    /** @var int */
    private int $batchSize;
    private int $userGroupBatchCount = 0;
    private int $groupIndex = 0;

    public function __construct(
        LineItemRepository $lineItemRepository,
        AssignmentRepository $assignmentRepository,
        LtiInstanceRepository $ltiInstanceRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        NativeUserRepository $userRepository,
        AssignmentIngester $assignmentIngester,
        Filesystem $filesystem
        ) {
        parent::__construct(self::NAME);
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->assignmentIngester = $assignmentIngester;
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Automate a user generate list');

        $this->addOption(
            self::OPTION_LINE_ITEM_IDS,
            'i',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item IDs',
        );

        $this->addOption(
            self::OPTION_LINE_ITEM_SLUGS,
            's',
            InputOption::VALUE_REQUIRED,
            'Comma separated list of line item slugs',
        );

        $this->addOption(
            'batch',
            'b',
            InputOption::VALUE_REQUIRED,
            'User Create Batch size',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addArgument(
            'user-prefix',
            InputArgument::REQUIRED,
            'user prefix list'
        );

        $this->addOption(
            self::OPTION_GROUP_PREFIX,
            'p',
            InputOption::VALUE_REQUIRED,
            'Group Prefix',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);
        $this->symfonyStyle->title('Simple Roster - Automate the user-generation');

        if (!empty($input->getOption(self::OPTION_LINE_ITEM_IDS)) && !empty($input->getOption(self::OPTION_LINE_ITEM_SLUGS))) {
            throw new InvalidArgumentException(
                sprintf(
                    "Option '%s' and '%s' are exclusive options.",
                    self::OPTION_LINE_ITEM_IDS,
                    self::OPTION_LINE_ITEM_SLUGS
                )
            );
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_IDS)) {
            $this->initializeLineItemIdsOption($input);
            $criteria = $this->getFindLineItemCriteria();
            $lineItemsCollection = $this->lineItemRepository->findLineItemsByCriteria($criteria);
            if(!empty($lineItemsCollection)) {
                $slugArr = [];
                foreach($lineItemsCollection as $lineVal){
                    array_push($slugArr, $lineVal->getSlug());
                }
                if(!empty($slugArr)) {
                    $this->lineItemSlugs = $slugArr;
                }
            }
        }

        if ($input->getOption(self::OPTION_LINE_ITEM_SLUGS) && empty($this->lineItemSlugs)) {
            $this->initializeLineItemSlugsOption($input->getOption(self::OPTION_LINE_ITEM_SLUGS));
        } 
        
        if (empty($this->lineItemSlugs)) {
            $this->getAllLineItemSlugs();
        }

        if ($input->getArguments('user-prefix')) {
            $this->initializeUserPrefixOption($input);
        }
        
        $this->batchSize = (int)$input->getOption('batch') ?? self::DEFAULT_BATCH_SIZE;
        
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->comment('Executing Automation...');

        try {
            
            $lineItems = $this->lineItemRepository->findAllAsCollection();
            if ($lineItems->isEmpty()) {
                throw new LineItemNotFoundException("No line items were found in database.");
            }

            $userIncrNo = $this->checkLineItemSlugUserExist($lineItems, $this->lineItemSlugs);

            $userGroupAssignCount = 0;
            if ($input->getOption(self::OPTION_GROUP_PREFIX)) {
                $userGroupIds = $this->getLoadBalanceGroupID($input->getOption(self::OPTION_GROUP_PREFIX));
                $userGroupAssignCount = (int)(count($this->userPrefix) * count($this->lineItemSlugs) * $this->batchSize / count($userGroupIds));
            }

            $automateCsvPath = $_ENV['AUTOMATE_USER_LIST_PATH'].date("Y-m-d");
            $userCsvHead = ['username','password','groupId'];
            $assignmentCsvHead = ['username','lineItemSlug'];
            $noOfUsersCreated = 0;

            $userAggregratedCsvDt = $assgAggregratedCsvDt = [];
            $userDtoCollection = new UserDtoCollection();
            $assignmentDtoCollection = new AssignmentDtoCollection();

            if (!$this->filesystem->exists($automateCsvPath)) {
                $this->filesystem->mkdir($automateCsvPath);
            }
            
            foreach ($this->userPrefix as $prefix) {
                $csvPath = $automateCsvPath. "/" . $prefix;
                if (!$this->filesystem->exists($csvPath)) {
                    $this->filesystem->mkdir($csvPath);
                }
                foreach ($this->lineItemSlugs as $lineSlugs) {
                    $csv_filename = $lineSlugs."-".$prefix.".csv";
                    $csvDt = $assignmentCsvDt = [];
                    foreach (range(1, $this->batchSize) as $i) {
                        $username = $lineSlugs."_".$prefix."_".((int)$userIncrNo[$lineSlugs] + (int)$i);
                        $userPassword = $this->createUserPassword();
                        $userGroupId = $input->getOption(self::OPTION_GROUP_PREFIX) ? 
                            $this->createUserGroupId($userGroupIds, $userGroupAssignCount) : 
                            '';
                        
                        $csvDt[] = [$username, $userPassword,$userGroupId];
                        $assignmentCsvDt[] = [$username, $lineSlugs];
                        
                        $userDtoCollection->add($this->createUserDto($username, $userPassword, $userGroupId));
                        $assignmentDtoCollection->add($this->createAssignmentDto($lineItems, $lineSlugs, $username));
                        $noOfUsersCreated++;
                    }
                    $this->writeCsvData($path = $csvPath."/".$csv_filename, $userCsvHead, $csvDt);
                    $this->writeCsvData($path = $csvPath."/"."Assignments-".$lineSlugs."-".$prefix.".csv", $assignmentCsvHead, $assignmentCsvDt);
                    $userAggregratedCsvDt = array_merge($userAggregratedCsvDt,$csvDt);
                    $assgAggregratedCsvDt = array_merge($assgAggregratedCsvDt,$assignmentCsvDt);
                }
            }

            if (!empty($userAggregratedCsvDt)) {
                $this->writeCsvData($path = $automateCsvPath."/users_aggregated.csv", $userCsvHead, $userAggregratedCsvDt);
            }
            if (!empty($assgAggregratedCsvDt)) {
                $this->writeCsvData($path = $automateCsvPath."/assignments_aggregated.csv", $assignmentCsvHead, $assgAggregratedCsvDt);
            }

            
            if (!$userDtoCollection->isEmpty()) {
                $this->userRepository->insertMultiple($userDtoCollection);
            }
            if (!$assignmentDtoCollection->isEmpty()) {
                $this->assignmentIngester->ingest($assignmentDtoCollection);
            }
            
            $this->symfonyStyle->success(
                sprintf(
                    "'%s' Users have been successfully added.", $noOfUsersCreated
                )
            );

        } catch (Throwable $exception) {
            $this->symfonyStyle->error($exception->getMessage());

            return 1;
        }

        return 0;
    }

    private function initializeLineItemIdsOption(InputInterface $input): void
    {
        $lineItemIds = array_filter(
            explode(',', (string)$input->getOption(self::OPTION_LINE_ITEM_IDS)),
            static function (string $value): bool {
                return !empty($value) && (int)$value > 0;
            }
        );
        if (empty($lineItemIds)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_IDS)
            );
        }

        $this->lineItemIds = array_map('intval', $lineItemIds);
    }

    private function initializeLineItemSlugsOption(string $lineItemSlugs): void
    {
        if (!empty($lineItemSlugs)) {
            $this->lineItemSlugs = array_filter(
                explode(',', (string)$lineItemSlugs),
                static function (string $value): bool {
                    return !empty($value);
                }
            );
        }
        if (empty($this->lineItemSlugs)) {
            throw new InvalidArgumentException(
                sprintf("Invalid '%s' option received.", self::OPTION_LINE_ITEM_SLUGS)
            );
        }
    }

    private function initializeUserPrefixOption(InputInterface $input): void
    {
        $this->userPrefix = array_filter(
            explode(',', (string)$input->getArgument('user-prefix')),
            static function (string $value): bool {
                return !empty($value);
            }
        );

        if (empty($this->userPrefix)) {
            throw new InvalidArgumentException(
                sprintf("User Prefix is a required argument")
            );
        }
    }

    private function createUserPassword(): string
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 8);
    }

    /**
     *
     * Function to create user data
     */
    private function createUserDto(string $username, string $userPassword, string $userGroupId): UserDto
    {
        return new UserDto(
            $username,
            $this->passwordEncoder->encodePassword(new User(), $userPassword),
            $userGroupId != '' ?  $userGroupId : null
        );
    }

    /**
     * Function to create user assignment data
     */
    private function createAssignmentDto(
        LineItemCollection $lineItems,
        string $lineItemSlug,
        string $username
    ): AssignmentDto {
        $lineItem = $lineItems->getBySlug($lineItemSlug);
        return new AssignmentDto(Assignment::STATE_READY, (int)$lineItem->getId(), $username);
    }

    private function getFindLineItemCriteria(): FindLineItemCriteria
    {
        $criteria = new FindLineItemCriteria();

        if (!empty($this->lineItemIds)) {
            $criteria->addLineItemIds(...$this->lineItemIds);
        }
        return $criteria;
    }

    /**
     * @throws LineItemNotFoundException
     */
    private function getAllLineItemSlugs(): Void {
        $lineItems = $this->lineItemRepository->findAllSlugsAsArray();
        if (empty($lineItems)) {
            throw new LineItemNotFoundException("No line items were found in database.");
        }
        $this->lineItemSlugs = array_column($lineItems, 'slug');
    }

    /**
     * Function to check whether a user exist for a Line Item Slug and returns last increment no of it
     * 
     */
    private function checkLineItemSlugUserExist(
        LineItemCollection $lineItems,
        Array $lineItemSlugs
    ): Array {
        $userNameIncArr = [];
        foreach ($lineItemSlugs as $slug) {
            $lineItem = $lineItems->checkSlugExistOrNot($slug);
            if(!empty($lineItem)) {
                $lineItemId = (int)$lineItem->getId();
                $assignment = $this->assignmentRepository->findByLineItemId($lineItemId);
                if (!empty($assignment)) {
                    $userInfo = $assignment->getUser()->getUsername();
                    $userNameArr = explode('_', $userInfo);
                    $userNameLastNo = preg_match("/^\d+$/", end($userNameArr)) ? (int)end($userNameArr) : self::DEFAULT_USERNAME_INCREMENT_NO;
                    $userNameIncArr[$slug] = $userNameLastNo;
                } else {
                    $userNameIncArr[$slug] = self::DEFAULT_USERNAME_INCREMENT_NO;
                }
            } else {
                $this->symfonyStyle->note($slug. " LineItem Slug not exist in the system");
                $this->lineItemSlugs = array_diff( $this->lineItemSlugs, [$slug] );
            }
        }
        return $userNameIncArr;
    }

    /**
     * Function to create new groupIds based on LTI Instance count 
     */
    public function getLoadBalanceGroupID(string $groupPrefix): Array
    {
        $totalInstance = $this->ltiInstanceRepository->findAllAsCollection()->count();
        $targetId = 1;
        $groupIds = [];
        while ($targetId <= $totalInstance) {
            $random = substr(md5(random_bytes(10)), 0, 10);
            $groupId = sprintf($groupPrefix.'_%s', $random);
            array_push($groupIds, $groupId);
            $targetId++;
        }
        return $groupIds;
    }

    /**
     * Function to create a new csv and save data to it
     */
    private function writeCsvData(string $path, array $head, array $data): Void
    {
        $csv = Writer::createFromPath($path, "w");
        $csv->insertOne($head);
        $csv->insertAll($data);
    }

    /**
     * Function to create groupId for each new created user
     */
    private function createUserGroupId(array $userGroupIds, int $userGroupAssignCount): String
    {
        if ($userGroupAssignCount != 0){
            if ($this->userGroupBatchCount < $userGroupAssignCount) {
                $userGroupId = $userGroupIds[$this->groupIndex];
                $this->userGroupBatchCount++;
            } else {
                $this->userGroupBatchCount = 1;
                $this->groupIndex++;
                $userGroupId = $userGroupIds[$this->groupIndex];
            }
        }
        return $userGroupId;
    }
}
