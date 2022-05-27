# Installation
Run `make build` in order to install all application dependencies (you must have Docker installed).

For more commands, type `make help`

## HOW TO USE
#### Sending events
1. Make sure you have the `rabbitMQ` service running [via browser](http://localhost:15672)

2. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to configure `rabbitMQ`:
```shell
php apps/SymfonyClient/bin/console rabbitmq:configure
```

3. Perform a `POST` request to the **createUser** endpoint (go to the `docs/endpoints/training.http` file for more details about how to build the request). This will send the `UserCreated` event to `rabbitMQ`.

#### Consuming events
1. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to consume the desired number of messages for the desired queue:
```shell
php apps/SymfonyClient/bin/console event:consume QUEUE_NAME [NUMBER_OF_MESSAGES_TO_CONSUME]

// If you want to consume the first 3 messages of the queue training_rabbit.queue1, see below.
// Please note the number of messages is optional
php apps/SymfonyClient/bin/console event:consume training_rabbit.queue1 3
```

#### Having supervisord active for automatic messages consumption
1. Open a terminal and execute `make shell` in order to enter to the **php-container**. Then execute this to configure `supervisord`:
```shell
php apps/SymfonyClient/bin/console rabbitmq:configure
```

2. This will starting consuming all the messages you have in all your queues. Please note if you create new subscribers you'll **have to launch the command again** in order to re-create the new configuration.
