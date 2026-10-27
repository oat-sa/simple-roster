<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use OAT\SimpleRoster\Entity\RosteringImport;

class RosteringImportRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RosteringImport::class);
    }

    public function markProcessing(string $referenceId): void
    {
        $import = $this->getOrCreate($referenceId);

        $import
            ->setStatus(RosteringImport::STATUS_PROCESSING)
            ->setErrorMessage(null)
            ->setTotalRows(null)
            ->setProcessedRows(null)
            ->setFailedRows(null)
            ->setStartedAt($this->getNowUtc())
            ->setFinishedAt(null)
            ->setAttempts($import->getAttempts() + 1);

        $this->save($import);
    }

    public function markProcessed(string $referenceId, int $totalRows, int $failedRows): void
    {
        $import = $this->getOrCreate($referenceId);
        $processedRows = $totalRows - $failedRows;

        if (0 === $import->getAttempts()) {
            $import->setAttempts(1);
        }

        if (null === $import->getStartedAt()) {
            $import->setStartedAt($this->getNowUtc());
        }

        $import
            ->setStatus(RosteringImport::STATUS_PROCESSED)
            ->setErrorMessage(null)
            ->setTotalRows($totalRows)
            ->setProcessedRows($processedRows)
            ->setFailedRows($failedRows)
            ->setFinishedAt($this->getNowUtc());

        $this->save($import);
    }

    public function markFailed(string $referenceId, string $errorMessage, int $totalRows, int $failedRows): void
    {
        $import = $this->getOrCreate($referenceId);
        $processedRows = $totalRows - $failedRows;
        $normalizedErrorMessage = trim($errorMessage);

        if ($normalizedErrorMessage === '') {
            $normalizedErrorMessage = 'Unknown processing error.';
        }

        if (0 === $import->getAttempts()) {
            $import->setAttempts(1);
        }

        if (null === $import->getStartedAt()) {
            $import->setStartedAt($this->getNowUtc());
        }

        $import
            ->setStatus(RosteringImport::STATUS_FAILED)
            ->setErrorMessage($normalizedErrorMessage)
            ->setTotalRows($totalRows)
            ->setProcessedRows($processedRows)
            ->setFailedRows($failedRows)
            ->setFinishedAt($this->getNowUtc());

        $this->save($import);
    }

    /**
     * @return RosteringImport[]
     */
    public function findInWindow(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $queryBuilder = $this->createQueryBuilder('ri');
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->where(
                $expr->orX(
                    $this->createDateRangeExpression($expr, 'ri.startedAt'),
                    $this->createDateRangeExpression($expr, 'ri.finishedAt')
                )
            )
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return $queryBuilder->getQuery()->getResult();
    }

    private function getNowUtc(): DateTimeInterface
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function getOrCreate(string $referenceId): RosteringImport
    {
        $import = $this->findOneBy(['referenceId' => $referenceId]);
        if ($import instanceof RosteringImport) {
            return $import;
        }

        return (new RosteringImport())->setReferenceId($referenceId);
    }

    private function save(RosteringImport $import): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($import);
        $entityManager->flush();
    }

    private function createDateRangeExpression(Expr $expr, string $fieldName)
    {
        return $expr->andX(
            $expr->isNotNull($fieldName),
            $expr->gte($fieldName, ':from'),
            $expr->lt($fieldName, ':to')
        );
    }
}
