Test project
First steps to run project
1. install php8.1
2. install composer 2
3. run composer i
4. run php bin/console doctrine:migrations:migrate  
5. run php bin/console doctrine:fixtures:load    

In project we have 2 type of routes, public and secured.
Public routes can be accessed by everybody, but Secured only from ADMIN or SUPER_ADMIN users.

For test in database we have 2 users:
1. ADMIN - {"username": "mike.smith@domain.com","password": "MyPass123@"}
2. USER - {"username": "mike.smith+1@domain.com","password": "MyPass123@"}


To start test secured routes, you need to access route /authentication with username and password, after that we need id, username and accessToken to generate bearer token in /token. The bearer token generated will be automatically stored in cookie. 


Routes for listing have some parameters:
1. q -> use this parameter for search
2. offset -> use to determinate page
3. limit -> use to limit response, default 20
4. sort -> use to sort data you provide, example: -id -> order by id DESC | id -> order by id ASC
