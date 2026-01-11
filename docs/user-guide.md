Welcome to the User Guide for User Service. This guide aims to provide you with all the necessary information to use our service and its features.

## Localization

We support two languages: English and Ukrainian. The default language is English, but you can easily change it by passing the `Accept-Language` header with either `en` or `uk` values. This will adjust the language of the messages and errors you receive from the User Service.

## Setting up OAuth Server

We utilize OAuth to handle the authentication and authorization of users, and for it to function properly, you have to register an OAuth client in User Service.

To do it, navigate to the project's root directory and run this sequence of commands:

```bash
make CLIENT_NAME=<name> create-oauth-client
```

You'll receive Client Identifier and Client Secret, which will be used for further authentication and authorization.

Check [this link](https://github.com/thephpleague/oauth2-server-bundle/blob/master/docs/basic-setup.md) for more info about OAuth client configuration.

## Grants

Once you've set up the OAuth server, and registered a Client, you can use redeemed credentials authentication and authorization.

Here is the list of grants User Service supports:

#### 1. Authorization Code Grant

The Authorization Code Grant flow allows a client application to obtain an authorization code from the authorization server, which is then exchanged for an access token to access protected resources.

Authorization Request:

```bash
curl -X GET \
  'https://localhost/api/oauth/authorize?response_type=code&client_id=<client_id>&redirect_uri=<redirect_uri>&scope=<scope>&state=<state>'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

Token Request:

```bash
curl -X POST \
  https://localhost/api/oauth/token \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=authorization_code&code=<authorization_code>&client_id=<client_id>&client_secret=<client_secret>&redirect_uri=<redirect_uri>'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

Learn more [here](https://oauth2.thephpleague.com/authorization-server/auth-code-grant/).

#### 2. Client Credentials

The Client Credentials flow involves a client application directly exchanging its credentials for an access token.

```bash
curl -X POST \
  https://localhost/api/oauth/token \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=client_credentials&client_id=<client_id>&client_secret=<client_secret>'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

Learn more [here](https://oauth2.thephpleague.com/authorization-server/client-credentials-grant/).

#### 3. Password

The Password flow involves a user's credentials being sent to the authorization server for authentication, resulting in the issuance of an access token directly to the client application.

```bash
curl -X POST \
  https://localhost/api/oauth/token \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=password&username=<username>&password=<password>&client_id=<client_id>&client_secret=<client_secret>'
```

**Note:** Replace `localhost` with your actual domain when deployed in production.

Learn more [here](https://oauth2.thephpleague.com/authorization-server/resource-owner-password-credentials-grant/).

## How to use Access Tokens

Once you've obtained an Access Token using one of the Grants mentioned above, you can access protected resources.

```bash
curl -H "Authorization: Bearer YOUR_ACCESS_TOKEN" https://api.example.com/data
```

Learn more about OAuth and other endpoints in [API Endpoints](api-endpoints.md)
