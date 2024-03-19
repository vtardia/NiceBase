drop table if exists users;
create table users (
  id integer not null primary key autoincrement,
  full_name varchar(50) not null,

  phone varchar(20) not null,
  email varchar(100) not null,
  password varchar(128) not null,

  created_at datetime not null default CURRENT_TIMESTAMP,
  updated_at datetime not null default CURRENT_TIMESTAMP
);
create index user_full_name on users (full_name);
create unique index user_email on users (email);
create index user_created_at on users (created_at);
create index user_updated_at on users (updated_at);

drop table if exists tickets;
create table tickets (
  id integer not null primary key autoincrement,
  user_id integer not null,

  ip_address varchar(50) not null,
  code varchar(50) not null,

  created_at datetime not null default CURRENT_TIMESTAMP,
  updated_at datetime not null default CURRENT_TIMESTAMP,
  constraint `fk_user`
    foreign key (`user_id`) REFERENCES `users` (`id`)
    on delete cascade on update cascade
);
create unique index ticket_code on tickets (code);
create index ticket_created_at on tickets (created_at);
create index ticket_updated_at on tickets (updated_at);

drop table if exists workshops;
create table workshops (
  id integer not null primary key autoincrement,
  title varchar(100) not null,
  created_at datetime not null default CURRENT_TIMESTAMP,
  updated_at datetime not null default CURRENT_TIMESTAMP
);
create index workshop_title on workshops(title);
create index workshop_created_at on workshops (created_at);
create index workshop_updated_at on workshops (updated_at);
insert into workshops (id, title)
values (1, 'Workshop One'),
       (2, 'Workshop Two'),
       (3, 'Workshop Three');

drop table if exists enrollments;
create table enrollments (
  id integer not null primary key autoincrement,
  ticket_id integer not null,
  workshop_id integer not null,
  created_at datetime not null default CURRENT_TIMESTAMP,
  updated_at datetime not null default CURRENT_TIMESTAMP,
  constraint `fk_ticket`
    foreign key (`ticket_id`) REFERENCES `tickets` (`id`)
    on delete cascade on update cascade,
  constraint `fk_workshop`
    foreign key (`workshop_id`) REFERENCES `workshops` (`id`)
    on delete cascade on update cascade
);
create unique index seat on enrollments (workshop_id, ticket_id);
create index enrollment_created_at on enrollments (created_at);
create index enrollment_updated_at on enrollments (updated_at);

drop view if exists booked_workshops;
create view booked_workshops as
  select workshops.id as id,
         workshops.title as title,
         enrollments.ticket_id as ticket_id
  from enrollments inner join workshops on enrollments.workshop_id = workshops.id
  order by enrollments.id;

drop view if exists ticket_details;
create view ticket_details as
  select tickets.id,
         tickets.ip_address,
         tickets.code,
         tickets.created_at,
         tickets.updated_at,
         users.id as user_id,
         users.full_name as user_full_name,
         users.phone as user_phone,
         users.email as user_email,
         users.password as user_password,
         users.created_at as user_created_at,
         users.updated_at as user_updated_at,
         booked_workshops.id as workshop_id,
         booked_workshops.title workshop_title
  from tickets
      inner join users on tickets.user_id = users.id
      inner join booked_workshops on tickets.id = booked_workshops.ticket_id
  order by tickets.id;
