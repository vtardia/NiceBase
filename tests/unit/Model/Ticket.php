<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use InvalidArgumentException;
use NiceBase\Collection;
use NiceBase\Model;
use Simphle\Value\IPAddress;

class Ticket extends Model
{
    /** @var string[] */
    protected array $attributes = ['user'];

    public User $user;
    public readonly IPAddress $ipAddress;
    public readonly string $code;

    public readonly Collection $workshops;

    protected function build(array $row): void
    {
        // We create tickets for users so either user_id or a user object is required
        if (!array_key_exists('user', $row) || !($row['user'] instanceof User)) {
            throw new InvalidArgumentException('Invalid user');
        }
        $this->user = $row['user'];

        /**
         * @psalm-suppress  InaccessibleProperty
         * Because build() is called within the constructor
         */
        $this->ipAddress = $row['ip_address'] ?? new IPAddress(IPAddress::LOCALHOST);

        /** @psalm-suppress  InaccessibleProperty */
        $this->code = $row['code'] ?? $this->code($this->user->email->value);

        /** @psalm-suppress  InaccessibleProperty */
        $this->workshops = $row['workshops'] ?? new Collection(Workshop::class);
    }

    private function code(string $message): string
    {
        $permittedChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = substr(str_shuffle($permittedChars), 0, 16);
        $code = base64_encode(sodium_crypto_shorthash($message, $key));
        return str_replace('=', '', $code);
    }

    protected function getData(): array
    {
        return array_merge(parent::getData(), [
            'ip_address' => $this->ipAddress->value,
            'user_id' => $this->user->id,
            'code' => $this->code
        ]);
    }

    /**
     * @return string[]
     */
    public static function getAttributes(): array
    {
        return array_merge(parent::getAttributes(), [
            'ip_address', 'user_id', 'code'
        ]);
    }
}
