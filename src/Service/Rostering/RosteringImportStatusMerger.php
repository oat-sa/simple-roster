<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;

class RosteringImportStatusMerger
{
    private const STATUS_SEVERITY = [
        'processed' => 0,
        'processing' => 1,
        'pending' => 2,
        'failed' => 3,
    ];

    public function merge(RosteringImportStatus $srStatus, RosteringImportStatus $ppStatus): RosteringImportStatus
    {
        return new RosteringImportStatus(
            $srStatus->getReferenceId(),
            $this->mergeStatus($srStatus->getStatus(), $ppStatus->getStatus()),
            max($srStatus->getFileLine(), $ppStatus->getFileLine()),
            $this->mergeMessages($srStatus->getMessages(), $ppStatus->getMessages()),
            null
        );
    }

    private function mergeStatus(string $srStatus, string $ppStatus): string
    {
        $srSeverity = $this->statusSeverity($srStatus);
        $ppSeverity = $this->statusSeverity($ppStatus);

        return $srSeverity >= $ppSeverity ? $srStatus : $ppStatus;
    }

    private function statusSeverity(string $status): int
    {
        return self::STATUS_SEVERITY[$status] ?? self::STATUS_SEVERITY['pending'];
    }

    /**
     * @param array<int, string> $srMessages
     * @param array<int, string> $ppMessages
     *
     * @return array<int, string>
     */
    private function mergeMessages(array $srMessages, array $ppMessages): array
    {
        return array_values(array_unique(array_merge($srMessages, $ppMessages)));
    }
}
