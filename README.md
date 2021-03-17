## About

This is a PHP Wrapper for working with the [Risika API](https://api.risika.dk/docs/).

Risik API is a database for looking up information about companies.

The package is only tested with the following countries:
- Denmark
- Sweden
- Norway

## How to install

Install via Composer by running
```
composer require christiandk00/risika-php-wrapper
```
in your project directory.

## Usage

In order to use this package you will need a JWT token from Risika.

You will get a refresh token from Risika that you can use to get a access token. The wrapper handles this for you.

I suggest that you read the following about ID types for the API, as that will give you a better understanding of some variables names.
[Link to ID Types](https://api.risika.dk/docs/#id-types)

There is a universel method for calling all "get" endpoints, but there is also methods for almost all endpoints. I will try to make exampels for all of them.

## Setup

To initialise the class you will need
- A refresh token from Risika
- The version of Risika API you want to use
- The language that you will get response messages in.

In this example I stored my refresh token in my .env file and chose to use version 1.2, and I also want my response messages in danish.
````
use Risika\Risika;

// Initialize the client
$risika = New Risika(env('RISIKA_REFRESH_TOKEN'), 'v1.2', 'da-DK')
````

## Examples

Make a get request to any Risika API endpoint
````
//$locale = "dk" //Search in Denmark
//$path = "/list/company_types"; //The path to "get" from

$risika->get($locale, $path);
````

Get basic company information about a danish company
````
$risika->getBasicCompanyInfo('dk', '54562519');
````
This will return the basic company information about Lego A/S (54562519)


## Contributing

Any contribution is welcome! Just make a PR.

## Help

I am open to help with questions about the wrapper. Just make a issue or shoot me an email at hej@christiannmadsen.dk