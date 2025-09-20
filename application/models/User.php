<?php
declare(strict_types=1);

final class Application_Model_User
{
    private int $id;

    private string $email;

    private string $name;

    private string $passwordHash;

    public function __construct(int $id, string $email, string $name, string $passwordHash)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPasswordValid(string $plainPassword): bool
    {
        return hash('sha256', $plainPassword) === $this->passwordHash;
    }

    public function toIdentity(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
