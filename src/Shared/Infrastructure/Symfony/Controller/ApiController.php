<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Symfony\Controller;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use function Lambdish\Phunctional\each;

abstract class ApiController extends Controller
{
    public function __construct(
        protected QueryBus $queryBus,
        protected CommandBus $commandBus,
        ApiExceptionsHttpStatusCodeMapping $exceptionHandler,
        protected SerializerInterface $serializer
    ) {
        parent::__construct($this->queryBus, $this->commandBus);
        each(
            fn(int $httpCode, string $exceptionClass) => $exceptionHandler->register($exceptionClass, $httpCode),
            $this->exceptions()
        );
    }

    abstract protected function exceptions(): array;

    protected function createApiResponse(mixed $data, int $status_code = Response::HTTP_OK): Response
    {
        return new Response(
            $this->serialize($data),
            $status_code,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }

    private function serialize(mixed $data): string
    {
        return $this->serializer->serialize($data, JsonEncoder::FORMAT);
    }
}
