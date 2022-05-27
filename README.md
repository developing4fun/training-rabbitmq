# Installation
Run `make build` in order to install all application dependencies (you must have Docker installed).

For more commands, type `make help`.

# How to use
### Send events
1. Make sure you have the `rabbitMQ` service running [via browser](http://localhost:15672).

2. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to configure `rabbitMQ`:
```shell
php apps/SymfonyClient/bin/console rabbitmq:configure
```

3. Perform a `POST` request to the **createUser** endpoint (go to the `docs/endpoints/training.http` file for more details about how to build the request). This will send the `UserCreated` event to `rabbitMQ`.

### Consume events
#### Manually:
1. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to consume the desired number of messages for the desired queue:
```shell
php apps/SymfonyClient/bin/console event:consume QUEUE_NAME [NUMBER_OF_MESSAGES_TO_CONSUME]

// If you want to consume the first 3 messages of the queue training_rabbit.generate_coupon_on_user_created, see below.
// Please note the number of messages is optional
php apps/SymfonyClient/bin/console event:consume training_rabbit.generate_coupon_on_user_created 3
```

#### Automatically with `supervisord`:
1. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to configure `supervisord`:
```shell
php apps/SymfonyClient/bin/console supervisor:queue:configure
```
This command will create the needed `.ini` files for **Supervisor** in `apps/SymfonyClient/build/supervisor/` folder. 

2. Restart supervisor docker service.
   
3. This will start consuming all the messages you have in all your queues. Please note if you create new subscribers you'll **have to launch the command again** in order to re-create the new configuration.
