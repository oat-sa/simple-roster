<?php declare(strict_types=1);

namespace App\Request\ParamConverter;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Http\Exception\RequestEntityTooLargeHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BulkOperationCollectionParamConverter implements ParamConverterInterface
{
    public const BULK_OPERATIONS_LIMIT = 1000;

    public function apply(Request $request, ParamConverter $configuration)
    {
        $class = $configuration->getClass();
        $param = $configuration->getName();

        /** @var BulkOperationCollection $collection */
        $collection = new $class();

        foreach ($this->extractOperationsFromRequest($request) as $operation) {
            $bulkOperation = new BulkOperation(
                $operation['identifier'],
                $this->getBulkOperationTypeFromRequest($request),
                $operation['attributes'] ?? []
            );

            $collection->add($bulkOperation);
        }

        $request->attributes->set($param, $collection);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        return BulkOperationCollection::class === $configuration->getClass();
    }

    private function extractOperationsFromRequest(Request $request): array
    {
        $operations = json_decode($request->getContent(), true);

        if (json_last_error()) {
            throw new BadRequestHttpException(sprintf(
                'Invalid JSON request body received. Error: %s',
                json_last_error_msg()
            ));
        }

        if (empty($operations)) {
            throw new BadRequestHttpException('Empty request body received.');
        }

        if (count($operations) > static::BULK_OPERATIONS_LIMIT) {
            throw new RequestEntityTooLargeHttpException(sprintf(
                "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                static::BULK_OPERATIONS_LIMIT
            ));
        }

        return $operations;
    }

    private function getBulkOperationTypeFromRequest(Request $request): string
    {
        switch ($request->getMethod()) {
            case Request::METHOD_PUT:
            case Request::METHOD_PATCH:
                return BulkOperation::TYPE_UPDATE;
            default:
                return BulkOperation::TYPE_CREATE;
        }
    }
}
