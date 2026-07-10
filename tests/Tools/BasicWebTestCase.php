<?php

namespace App\Tests\Tools;

use App\DTO\Response\Security\LoginResponse;
use App\Entity\Shop;
use App\Entity\User;
use App\Service\Security\Provider\SystemUserProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class BasicWebTestCase extends WebTestCase
{
    use BasicTestTrait;
    use EntityTrait;

    protected ?AbstractBrowser $kernelBrowser = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = static::createClient();
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
     */
    public function authenticateUser(
        string $email,
        string $password,
    ): LoginResponse {
        $this->kernelBrowser->request(
            'POST',
            $this->generateUrl('api_login_check'),
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ])
        );

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        $content = $content['data'] ?? null;
        $this->assertNotNull($content);

        /** @var LoginResponse $loginResponseDTO */
        $loginResponseDTO = $this->serializeJsonToDto($content, LoginResponse::class);

        return $loginResponseDTO;
    }

    /**
     * @throws ExceptionInterface
     * @throws \Exception
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
     * @throws \Exception
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
}
