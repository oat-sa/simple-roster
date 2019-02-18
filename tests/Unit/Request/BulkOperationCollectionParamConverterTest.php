<?php declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Bulk\Operation\BulkOperationCollection;
use App\Request\ParamConverter\BulkOperationCollectionParamConverter;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class BulkOperationCollectionParamConverterTest extends TestCase
{
    /** @var BulkOperationCollectionParamConverter */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new BulkOperationCollectionParamConverter();
    }

    public function testItIsAParamConverter(): void
    {
        $this->assertInstanceOf(ParamConverterInterface::class, $this->subject);
    }

    public function testItSupportsBulkOperationCollection(): void
    {
        $paramConverter = new ParamConverter([]);
        $paramConverter->setClass(BulkOperationCollection::class);

        $this->assertTrue($this->subject->supports($paramConverter));
    }

    public function testItSetsBulkOperationAsRequestAttribute(): void
    {
        $paramConverter = new ParamConverter([]);
        $paramConverter->setClass(BulkOperationCollection::class);

        $expectedParameterName = 'bulkCollection';

        $paramConverter->setName($expectedParameterName);

        $requestBodyContent = json_encode([
            ['identifier' => 'user1'],
            ['identifier' => 'user2'],
        ]);

        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            [],
            $requestBodyContent
        );

        $this->subject->apply($request, $paramConverter);

        $this->assertTrue($request->attributes->has($expectedParameterName));

        /** @var BulkOperationCollection $bulkOperationCollection */
        $bulkOperationCollection = $request->attributes->get($expectedParameterName);

        $this->assertInstanceOf(BulkOperationCollection::class, $bulkOperationCollection);

        $this->assertCount(2, $bulkOperationCollection);
    }
}
