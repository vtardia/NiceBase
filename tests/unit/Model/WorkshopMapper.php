<?php

namespace NiceBase\Tests\Model;

use NiceBase\Collection;
use NiceBase\Database\Exception;
use NiceBase\Mapper;

class WorkshopMapper extends Mapper
{
    /**
     * @var string Main table used by the default mapper functions
     */
    protected string $table = 'workshops';
    protected string $class = Workshop::class;

    /**
     * Fetches the list of enrolled workshops for a given user
     */
    public function findPerUser(int|User $user): Collection
    {
        try {
            $results = self::$db->execute(
                'select distinct workshop_id as id, workshop_title as title
                     from ticket_details
                     where user_id = :user_id',
                ['user_id' => is_int($user) ? $user : $user->id]
            );
            if (is_array($results) && count($results) > 0) {
                return new Collection($this->class, $results);
            }
        } catch (Exception $e) {
            self::$logger->error(get_class($e) . ' [' . $e->getCode() . ']' . $e->getMessage());
        }
        return new Collection($this->class);
    }
}
