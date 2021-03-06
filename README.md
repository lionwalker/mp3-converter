## About the project

This project created as a test for an interview. This project uses following technologies:

- Laravel.
- Vue JS.
- CloudConvert API.
- TailWind CSS.

## What covered and what not

Comparing to the initial request this project covers all requested features 

## Things to improve

- We can add History to a different page
- Use of CloudConvert webhooks to manage stages of the conversion

## Steps to set up

- `git clone https://github.com/lionwalker/mp3-converter.git`
- `cd mp3-converter`
- _Create the .env file from the .env.example_
- `composer update`
- `npm install & npm run dev`
- `php artisan key:generate`
- _create a database and configure .env file_
- _add CloudConvert API Key and other configurations on bottom of the env file_
- _Add Email configurations on the .env file_
- `php artisan migrate`
- `php artisan db:seed` _If you want a test user out of the box email: test@test.com password: test123_
- `php artisan config:cache`
