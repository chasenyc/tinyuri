# Relationships

Now we are going to create our first relationship between two models. The goal of this is to be able for a user to track their shortened urls and see them all in once place. Eventually maybe we will even enable them to see some statistics such as how many times the url was visited.

## Defining the relationship

The first thing we want to do is determine what [type of database relation](https://en.wikipedia.org/wiki/Cardinality_(data_modeling)#Application_program_modeling_approaches) this is. In this case we have a `user` who has many `urls` so we have a [one to many relationship](https://laravel.com/docs/8.x/eloquent-relationships#one-to-many). What this means is that we want to put a new column on our `urls` table referencing what user it belongs to. In our case we actually don't want to make this a strict requirement though, what we mean by that is we want a person to visit the site and create a shortened url without having to login, they just wont be able to see a page with all of their urls. So in this case we want to make a new database migration adding a `nullable` `user_id` on our `urls` table.