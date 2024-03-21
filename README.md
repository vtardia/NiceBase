# NiceBase

A simple experimental PHP database toolkit based on the data mapper pattern.

NiceBase Models are semi-dumb objects that represent database-baked entities. The base `Model` class provide the basic fields and functions to all models like id, and timestamp fields.
Each model needs to define its own fields and implement/override the `build()` function and data-related functions (`getData()`, `getAttributes()`).

NiceBase Mappers are in charge of loading and persisting data to and from the database. The base `Mapper` provide basic functionality to load and save data from the database (`find()`, `findBy()`, `save()`, `delete()`) as long as the entity is simple and backed by a simple table and not using custom tables, views and/or joins.

The business logic of the application will dictate specific Model and Mapper implementations for different use cases.

## Usage

Initialise the database engine in your application bootstrap file:

```php
$db = new NiceBase\Database\Connection(/* dsn[, user, password, logger] */);
NiceBase\Mapper::init(db: $db, /* [logger] */);
```

Then create and use your models and mappers and have fun!

## Test Case Scenario

The test scenario depicts the use case of an event organiser. In the hosting web application, a user can request tickets for one or more workshops.

The relationships are detailed as follows:

 - A User has many Tickets
 - A Ticket belongs to a User
 - A Ticket has many Workshops through Enrollments
 - An Enrollment belongs to a Ticket
 - An Enrollment has one Workshop

With the models and mapper structure for this case, we can write code like this:

```php
$user = (new UserMapper())->findOrCreate('user@domain.com');
$user = User::create(['email' => '...', /* ... */]); // alternative

$ticket = Ticket::create(['user' => $user]);

// The original ticket is immutable, we update it and get a new copy
$newTicket = (new TicketMapper())->save($ticket);

// Will return a readonly collection
$ticket->workshops;
```

Validation happens inside the models using self-validating value objects (`EmailAddress`, `PersonName`, `IPAddress`, etc). 
 
Timezones are managed in this way:

   - the database is set to UTC,
   - the PHP server is set UTC,
   - output to the user will need to be converted to user timezone

```php
// Displays a user's creation date using the given timezone in the specified format
$user->created(new DateTimeZone('Europe/Rome'))->format(/* format here */)
```

In this use case, `User` is a pretty simple model/mapper implementation because it mostly interacts with the `users` table. Tickets are a more complex entity because they are related to users, enrollments and workshops. So the `TicketMapper` needs to override most of the basic functions and leverage views.

With this setup we can answer some common queries:

 - How many/which Tickets for a given User? (`TicketMapper::findBy()` with `user_id` or `user_email`, with optional pagination).
 - How many/which Workshops for a User? (`WorkshopMapper::findPerUser()`)
 - How many/which Users for a Workshop? (`UserMapper::findPerWorkshop()`)
 - Detail view for a User may include all the Workshops and Ticket codes (`TicketMapper::findBy(/* userId or userEmail */)`)
 - Detail view for a Workshop may include a list of participant Users (`UserMapper::findPerWorkshop()`)
