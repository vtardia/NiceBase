<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use NiceBase\Model;
use SensitiveParameter;
use Simphle\Value\EmailAddress;
use Simphle\Value\PersonName;
use Simphle\Value\PhoneNumber;

class User extends Model
{
    /**
     * Required attributes in SQL snake case
     * @var string[]
     */
    protected array $attributes = ['email', 'full_name', 'phone'];

    public EmailAddress $email;
    public PersonName $fullName;
    public PhoneNumber $phone;

    /**
     * We want save to fail if password is not set manually
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected string $password;

    public function setPassword(#[SensitiveParameter] string $value): void
    {
        $this->password = password_hash($value, PASSWORD_DEFAULT);
    }

    protected function build(array $row): void
    {
        // Basic validation is performed by value objects
        $this->fullName = new PersonName($row['full_name']);
        $this->email = new EmailAddress($row['email']);
        $this->phone = new PhoneNumber($row['phone']);
    }

    protected function getData(): array
    {
        $data = [
            'email' => $this->email->value,
            'full_name' => $this->fullName->value,
            'phone' => $this->phone->value,
        ];
        /**
         * Will only be set when creating/updating a user, and never
         * when loading from the database, so it stays write-only
         * @psalm-suppress RedundantPropertyInitializationCheck
         */
        if (isset($this->password)) {
            $data['password'] = $this->password;
        }
        return array_merge(parent::getData(), $data);
    }

    /**
     * @return string[]
     */
    public static function getAttributes(): array
    {
        return array_merge(parent::getAttributes(), [
            'email', 'full_name', 'phone', 'password'
        ]);
    }
}
