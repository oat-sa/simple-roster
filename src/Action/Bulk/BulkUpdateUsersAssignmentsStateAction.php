<?php declare(strict_types=1);

namespace App\Action\Bulk;

use App\Bulk\Operation\BulkOperationCollection;
use App\Responder\SerializerResponder;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Symfony\Component\HttpFoundation\Response;

class BulkUpdateUsersAssignmentsStateAction
{
    /** @var BulkUpdateUsersAssignmentsStateService */
    private $bulkUpdateUsersAssignmentsStateService;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        BulkUpdateUsersAssignmentsStateService $bulkUpdateUsersAssignmentsStateService,
        SerializerResponder $responder
    ) {
        $this->bulkUpdateUsersAssignmentsStateService = $bulkUpdateUsersAssignmentsStateService;
        $this->responder = $responder;
    }

    public function __invoke(BulkOperationCollection $operationCollection): Response
    {
        return $this->responder->createJsonResponse(
            $this->bulkUpdateUsersAssignmentsStateService->process($operationCollection)
        );
    }
}
