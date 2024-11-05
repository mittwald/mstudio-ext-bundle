# mittwald mStudio extension Symfony Bundle

This repository contains a Symfony Bundle for easily setting up a mittwald mStudio extension using a Symfony application.

> [!WARNING]
> This bundle is **not recommended for production usage, yet**. Use at your own peril.

## Usage

```
$ composer require mittwald/mstudio-ext-bundle
```

## Getting started

### 1. Adding and running Doctrine migrations

This bundle ships a few additional Doctrine entities that you will need to run migrations for. Add the following line to your `config/packages/doctrine_migrations.yaml`:

```yaml
# config/packages/doctrine_migrations.yaml
doctrine_migrations:
  migrations_paths:
    # <keep other migrations here>
    'Mittwald\MStudio\DoctrineMigrations': '@MittwaldExtensionWebhookBundle/Migrations'
```

After that, run the additional migrations:

```
$ bin/console doctrine:migrations:migrate
```

### 2. Include routes

The routes for the webhooks need to be included in your application's routing configuration:

```yaml
# config/routes.yaml
mittwald_extension_webhook_controllers:
  resource:
    path: '@MittwaldExtensionWebhookBundle/src/Controller/'
    namespace: Mittwald\MStudio\Bundle\Controller
  type: attribute
  prefix: /
```

### 3. Configure encryption key

> [!IMPORTANT]
> This step is important for making sure that your extension secrets are stored securely.
> Without this step, the webhooks will only throw exceptions. It's for your own safety. ;)

The safest way to configure the instance secret encryption key is using an environment variable.
Place the following configuration in your `services.yaml` and make sure the respective environment variable is defined:

```yaml
# config/services.yaml
parameters:
  mstudio_ext.instance_secret_key: '%env(MSTUDIO_EXTENSION_SECRET_KEY)%'
```

## Optional integrations

### Implementing event handlers

This bundle emits [events](https://symfony.com/doc/current/event_dispatcher.html) whenever an extension instance is created or modified.

You may define event handlers for the following events:

- `Mittwald\MStudio\Bundle\Event\ExtensionAddedToContextEvent`
- `Mittwald\MStudio\Bundle\Event\ExtensionInstanceUpdatedEvent`
- `Mittwald\MStudio\Bundle\Event\ExtensionInstanceRemoveEvent`

Please note that the _Extension Secret_ that is stored for the extension will only become valid _after_ the webhook request was completed successfully -- so if you want to use the extension secret immediately after an extension is installed, you need to do it _asynchronously_ after the webhook was processed. In this case, consider defining an event handler that re-emits these events as asynchronous messages.

### Enabling SSO login

To enable the single-signon using the ATReK mechanism[^atrek], you need to enable an additional
user provider and an additional authenticator.[^security]

[^atrek]: https://developer.mittwald.de/docs/v2/contribution/overview/concepts/authentication/
[^security]: https://symfony.com/doc/current/security.html

```yaml
# config/packages/security.yaml
security:
  providers:
    mstudio_user_provider:
      id: Mittwald\MStudio\Bundle\Security\UserProvider
  firewalls:
    main:
      provider: mstudio_user_provider
      custom_authenticators:
        - Mittwald\MStudio\Bundle\Security\TokenRetrievalKeyAuthenticator
        # Optional, if you want to directly accept mStudio API keys as authentication factor
        # (not recommended and not officially supported)
        # - Mittwald\MStudio\Bundle\Security\APITokenAuthenticator
```

### Configuring the redirect target after SSO login

When using the single-signon, the built-in controller will redirect the user to
a route of your choice. To configure the target route, set the `mstudio_ext.sso_redirect_route`
parameter in your `services.yaml` file.

```yaml
# config/services.yaml
parameters:
  mstudio_ext.sso_redirect_route: your_route_name
```
