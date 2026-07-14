<?php

namespace App\Tests\Tools;

use App\Dto\Response\Infra\ApiErrorResponse;
use App\Dto\Response\Infra\ApiResponse;
use App\Dto\Response\Infra\ApiSuccessResponse;
use App\DTO\Response\Security\LoginResponse;
use App\Entity\Shop;
use App\Entity\User;
use App\Service\Security\Provider\SystemUserProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class BasicWebTestCase extends WebTestCase
{
    use BasicTestTrait;
    use EntityTrait;

    protected ?AbstractBrowser $kernelBrowser = null;
    protected string $httpHost = '';
    protected bool $dumpHttpResponse = false;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = static::createClient();

        $this->httpHost = $this->getContainerInterface()->getParameter('router.request_context.uri');
    }

    protected function getContainerInterface(): ContainerInterface
    {
        if (!$this->kernelBrowser) {
            throw new \LogicException('You must set up the application before running tests.');
        }

        return $this->kernelBrowser->getContainer();
    }

    /**
     * @throws ExceptionInterface
     * @throws \Throwable
     */
    public function authenticateUser(
        string $email,
        string $password,
    ): LoginResponse {
        $response = $this->post(
            $this->generateUrl('api_login_check'),
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        $this->assertOk();
        $this->assertResponseIsSuccessful();

        $content = $response->apiSuccessResponse->data ?? null;
        $this->assertNotNull($content);

        /** @var LoginResponse $loginResponseDTO */
        $loginResponseDTO = $this->serializeJsonToDto($content, LoginResponse::class);

        return $loginResponseDTO;
    }

    /**
     * @throws ExceptionInterface
     * @throws \Throwable
     */
    public function fullAuthenticateUser(
        string $email,
        array $roles = [],
        ?Shop $shop = null,
    ): LoginResponse {
        // Create user
        $em = $this->getEntityManager();

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (empty($user)) {
            $user = $this->createUser(
                $shop ?? $em->getRepository(Shop::class)->findAll()[0],
                $email,
                $roles
            );
            $em->persist($user);
            $em->flush();
        }

        // Authenticate user
        return $this->authenticateUser($user->getEmail(), $user->getEmail());
    }

    /**
     * @throws ExceptionInterface
     * @throws \Throwable
     */
    public function authenticateSystemUser(): LoginResponse
    {
        /** @var SystemUserProvider $service */
        $service = $this->getService(SystemUserProvider::class);
        $user = $service->getSystemUser();

        // Authenticate user
        return $this->authenticateUser($user->getEmail(), $user->getEmail());
    }

    /**
     * @throws ExceptionInterface
     */
    public function serializeJsonToDto(array $data, string $dtoClass, bool $isDtoCollection = false): mixed
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get(SerializerInterface::class);

        return $serializer->deserialize(
            json_encode($data),
            sprintf('%s%s', $dtoClass, $isDtoCollection ? '[]' : ''),
            'json'
        );
    }

    private function formatUri(string $uri): string
    {
        // already absolute?
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        return sprintf('%s/%s', $this->httpHost, ltrim($uri, '/'));
    }

    protected function get(
        string $uri,
        array $query = [],
        array $headers = [],
    ): ApiResponse {
        if ([] !== $query) {
            $uri .= '?'.http_build_query($query);
        }

        $this->kernelBrowser->request(
            method: 'GET',
            uri: $this->formatUri($uri),
            server: $this->formatHeaders($headers),
        );

        return $this->json();
    }

    /**
     * @throws \Throwable
     */
    protected function post(
        string $uri,
        array $payload = [],
        array $headers = [],
    ): ApiResponse {
        $this->kernelBrowser->request(
            method: 'POST',
            uri: $this->formatUri($uri),
            server: $this->formatHeaders($headers),
            content: json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            ),
        );

        return $this->json();
    }

    /**
     * @throws \Throwable
     */
    protected function put(
        string $uri,
        array $payload = [],
        array $headers = [],
    ): ApiResponse {
        $this->kernelBrowser->request(
            method: 'PUT',
            uri: $this->formatUri($uri),
            server: $this->formatHeaders($headers),
            content: json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            ),
        );

        return $this->json();
    }

    /**
     * @throws \Throwable
     */
    protected function patch(
        string $uri,
        array $payload = [],
        array $headers = [],
    ): ApiResponse {
        $this->kernelBrowser->request(
            method: 'PATCH',
            uri: $this->formatUri($uri),
            server: $this->formatHeaders($headers),
            content: json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            ),
        );

        return $this->json();
    }

    /**
     * @throws \Throwable
     */
    protected function delete(
        string $uri,
        array $headers = [],
    ): ApiResponse {
        $this->kernelBrowser->request(
            method: 'DELETE',
            uri: $this->formatUri($uri),
            server: $this->formatHeaders($headers),
        );

        return $this->json(false);
    }

    /**
     * @throws \Throwable
     */
    private function json(
        bool $decode = true,
    ): ApiResponse {
        $content = $this->kernelBrowser
            ->getResponse()
            ->getContent();

        if ($this->dumpHttpResponse) {
            print_r($content);
        }

        if (!$decode || '' === $content || false === $content) {
            return new ApiResponse();
        }

        $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (in_array($this->kernelBrowser->getResponse()->getStatusCode(), [200, 201], true)) {
            return new ApiResponse(
                $this->serializeJsonToDto($content, ApiSuccessResponse::class)
            );
        }

        return new ApiResponse(
            apiErrorResponse: $this->serializeJsonToDto($content['error'], ApiErrorResponse::class)
        );
    }

    /**
     * Headers par défaut.
     */
    private function formatHeaders(
        array $headers = [],
    ): array {
        return array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $headers);
    }

    protected function assertOk(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_OK
        );
    }

    protected function assertCreated(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_CREATED
        );
    }

    protected function assertNoContent(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_NO_CONTENT
        );
    }

    protected function assertBadRequest(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_BAD_REQUEST
        );
    }

    protected function assertUnauthorized(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_UNAUTHORIZED
        );
    }

    protected function assertForbidden(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_FORBIDDEN
        );
    }

    protected function assertNotFound(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_NOT_FOUND
        );
    }

    protected function assertValidationError(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    protected function assertConflict(): void
    {
        self::assertResponseStatusCodeSame(
            Response::HTTP_CONFLICT
        );
    }

    protected function assertJsonHasKey(
        string $key,
        array $json,
    ): void {
        self::assertArrayHasKey(
            $key,
            $json
        );
    }

    protected function assertJsonValue(
        string $key,
        mixed $expected,
        array $json,
    ): void {
        self::assertEquals(
            $expected,
            $json[$key]
        );
    }

    protected function assertJsonContains(
        array $expected,
        array $json,
    ): void {
        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $json);

            self::assertEquals(
                $value,
                $json[$key]
            );
        }
    }
}
