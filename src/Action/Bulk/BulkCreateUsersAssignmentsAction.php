<?php declare(strict_types=1);

namespace App\Action\Bulk;

use App\Bulk\Operation\BulkOperationCollection;
use App\Responder\SerializerResponder;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAssignmentsAction
{
    /** @var BulkCreateUsersAssignmentsService */
    private $bulkCreateUsersAssignmentService;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        BulkCreateUsersAssignmentsService $bulkCreateUsersAssignmentService,
        SerializerResponder $responder
    ) {
        $this->bulkCreateUsersAssignmentService = $bulkCreateUsersAssignmentService;
        $this->responder = $responder;
    }

    public function __invoke(BulkOperationCollection $operationCollection): Response
    {
        return $this->responder->createJsonResponse(
            $this->bulkCreateUsersAssignmentService->process($operationCollection),
            Response::HTTP_CREATED
        );
    }
}
