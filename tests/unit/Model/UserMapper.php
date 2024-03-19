<?php

namespace NiceBase\Tests\Model;

use NiceBase\Tests\Exception\AuthenticationFailedException;
use NiceBase\Collection;
use NiceBase\Database\Exception;
use NiceBase\Mapper;
use Simphle\Value\EmailAddress;

class UserMapper extends Mapper
{
    protected string $table = 'users';
    protected string $class = User::class;

    /**
     * Checks if a user already exists
     */
    public function findByEmail(string|EmailAddress $email): ?User
    {
        $results = $this->findBy([
            'email' => ($email instanceof EmailAddress) ? $email->value : $email
        ]);
        return $results[0] ?? null;
    }

    /** @psalm-suppress MoreSpecificReturnType */
    public function findOrCreate(User $user): User
    {
        if ($user->id !== null) {
            return $user;
        }
        $found = $this->findByEmail($user->email);
        /**
         * @psalm-suppress LessSpecificReturnStatement
         * @noinspection PhpIncompatibleReturnTypeInspection
         */
        return $found ?? $this->save($user);
    }

    /**
     * Fetches the list of users enrolled to a given workshops
     */
    public function findPerWorkshop(int|Workshop $workshop): Collection
    {
        try {
            $results = self::$db->execute(
                'select distinct user_id as id,
                        user_full_name as full_name,
                        user_email as email,
                        user_phone as phone
                     from ticket_details
                     where workshop_id = :workshop_id',
                ['workshop_id' => is_int($workshop) ? $workshop : $workshop->id]
            );
            if (is_array($results) && count($results) > 0) {
                return new Collection($this->class, $results);
            }
        } catch (Exception $e) {
            self::$logger->error(get_class($e) . ' [' . $e->getCode() . ']' . $e->getMessage());
        }
        return new Collection($this->class);
    }

    public function authenticate(EmailAddress $email, string $password): ?User
    {
        $results = self::$db->select($this->table, ['email' => $email->value]);
        if (count($results) === 1) {
            $user = $results[0];
            if (password_verify($password, $user['password'])) {
                return User::create($user);
            }
        }
        throw new AuthenticationFailedException(
            "Authentication failed for user '$email->value'"
        );
    }
}
