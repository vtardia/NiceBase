<?php

namespace NiceBase\Tests\Model;

use InvalidArgumentException;
use NiceBase\Collection;
use NiceBase\Database\Connection;
use NiceBase\Database\Exception;
use NiceBase\Mapper;
use NiceBase\Model;
use RuntimeException;
use Simphle\Value\IPAddress;
use Throwable;

class TicketMapper extends Mapper
{
    protected string $table = 'ticket_details';
    protected string $class = Ticket::class;

    public function find(int $id, ?array $fields = null): ?Ticket
    {
        $rows = $this->findBy(['id' => $id], $fields);
        return $rows[0] ?? null;
    }

    public function findByCode(string $code): ?Ticket
    {
        $rows = $this->findBy(['code' => $code]);
        return $rows[0] ?? null;
    }

    public function findBy(array $conditions, ?array $fields = null, array $options = []): Collection
    {
        try {
            if (
                ($results = self::$db->select(
                    $this->table,
                    $conditions,
                    ['*'],
                    array_merge($options, ['order' => 'id asc'])
                ))
                && count($results) > 0
            ) {
                return new Collection($this->class, $this->multipleRowsFromResults($results));
            }
        } catch (Exception $e) {
            self::$logger->error(get_class($e) . ' [' . $e->getCode() . ']' . $e->getMessage());
        }
        return new Collection($this->class);
    }

    /**
     * @param array<array<string,mixed>> $results
     * @return Ticket[]
     */
    private function multipleRowsFromResults(array $results): array
    {
        $rows = [];
        $currentId = $results[0]['id'];
        $currentRow = [];
        foreach ($results as $result) {
            if ($result['id'] === $currentId) {
                $currentRow[] = $result;
            } else {
                // Finalise the current row
                $rows[] = $this->rowFromResults($currentRow);
                // Start a new row
                $currentRow = [$result];
                $currentId = $result['id'];
            }
        }
        // Finalise the last row
        $rows[] = $this->rowFromResults($currentRow);
        return $rows;
    }

    private function rowFromResults(array $rows): Ticket
    {
        $data = $this->ticketDataFromRow($rows[0]);
        $data['user'] = $this->userFromRow($rows[0]);
        $data['workshops'] = new Collection(Workshop::class);
        foreach ($rows as $row) {
            $data['workshops'][] = Workshop::create([
                'id' => $row['workshop_id'],
                'title' => $row['workshop_title']
            ]);
        }
        $ticket = forward_static_call([$this->class, 'create'], $data);
        if (false === $ticket) {
            throw new RuntimeException('Unable to create ticket');
        }
        return $ticket;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function userFromRow(array $row): User
    {
        return User::create([
            'id' => (int)$row['user_id'],
            'full_name' => $row['user_full_name'],
            'phone' => $row['user_phone'],
            'email' => $row['user_email'],
            'password' => $row['user_password'],
            'created_at' => $row['user_created_at'],
            'updated_at' => $row['user_updated_at'],
        ]);
    }

    private function ticketDataFromRow(array $row): array
    {
        return [
             'id' => $row['id'],
             'ip_address' => new IPAddress($row['ip_address']),
             'code' => $row['code'],
             'created_at' => $row['created_at'],
             'updated_at' => $row['updated_at'],
        ];
    }

    public function save(Model $object): Ticket
    {
        if ($this->class !== get_class($object)) {
            throw new InvalidArgumentException('Invalid model');
        }
        /** @var Ticket $object */
        if ($object->id === null) {
            return $this->insert($object);
        }
        return $this->update($object);
    }

    /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
    private function insert(Ticket $ticket): Ticket
    {
        try {
            $data = self::$db->transactional(function (Connection $db) use ($ticket): array {
                if ($ticket->user->id === null) {
                    /** @psalm-suppress PropertyTypeCoercion */
                    $ticket->user = (new UserMapper())->save($ticket->user);
                }
                $row = $db->insert('tickets', $this->getModelData($ticket));
                if ($row === null) {
                    throw new RuntimeException('Unable to create ticket');
                }
                $this->updateEnrollments($ticket->workshops, (int)$row['id']);
                $row['user'] = $ticket->user;
                $row['workshops'] = $ticket->workshops;
                return $row;
            });
            $data['ip_address'] = new IPAddress($data['ip_address']);
            return Ticket::create($data);
        } catch (Throwable $e) {
            self::$logger->error($e->getMessage());
            throw new RuntimeException(
                'Unable to create ticket: ' . $e->getMessage()
            );
        }
    }

    /** @psalm-suppress InvalidNullableReturnType Will never be null here
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    private function update(Ticket $ticket): Ticket
    {
        try {
            self::$db->transactional(function (Connection $db) use ($ticket): void {
                // First update the basic ticket table.
                // If user id is changed, a new user will be re-linked
                if ($ticket->user->id === null) {
                    /** @psalm-suppress PropertyTypeCoercion */
                    $ticket->user = (new UserMapper())->save($ticket->user);
                }
                $row = $db->update(
                    'tickets',
                    $this->getModelData($ticket),
                    ['id' => $ticket->id]
                );
                if ($row === null) {
                    throw new RuntimeException('Unable to update ticket');
                }
                // Delete and re-apply workshops
                // Leaving this out because workshops are currently readonly
                // $this->updateEnrollments($ticket->workshops, $ticket->id);
            });
            // Return the updated ticket
            /**
             * If we are here, ticket will be saved and have an ID
             * @psalm-suppress UnevaluatedCode
             * @psalm-suppress PossiblyNullArgument
             * @psalm-suppress NullableReturnStatement
             */
            return $this->find($ticket->id);
        } catch (Throwable $e) {
            self::$logger->error($e->getMessage());
            throw new RuntimeException('Unable to update ticket');
        }
    }

    private function updateEnrollments(Collection $workshops, int $ticketId): void
    {
        self::$db->delete('enrollments', ['ticket_id' => $ticketId]);
        foreach ($workshops as $workshop) {
            self::$db->insert('enrollments', [
                'ticket_id' => $ticketId,
                'workshop_id' => $workshop->id
            ]);
        }
    }
}
