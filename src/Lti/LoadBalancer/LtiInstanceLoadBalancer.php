<?php declare(strict_types=1);

namespace App\Lti\LoadBalancer;

/**
 * @see https://github.com/oat-sa/extension-tao-operations/blob/master/model/OperationUtils.php
 */
class LtiInstanceLoadBalancer
{
    /** @var string[] */
    private $ltiInstances;

    public function __construct(array $ltiInstances)
    {
        $this->ltiInstances = $ltiInstances;
    }

    public function getLoadBalancedLtiInstanceUrl(string $value): string
    {
        $index = $this->asciiSum(hash('md5', $value)) % count($this->ltiInstances);

        return $this->ltiInstances[$index];
    }

    private function asciiSum(string $value): int
    {
        $asciiSum = 0;

        for ($i = 0, $iMax = strlen($value); $i < $iMax; $i++) {
            $asciiSum += ord($value[$i]);
        }

        return $asciiSum;
    }
}
