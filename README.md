# Generator-2.0
Worm Generator

This is a PHP fork of the Futhark generator for Worm.

# Credit where it's due:
* Worm: http://parahumans.wordpress.com
* Power Generator idea: http://www.reddit.com/u/ughzubat
* Lists of things, ideas, feature requests: http://www.reddit.com/r/FutharkGenerator/
* Additional people mentioned at: https://futhark.acwpd.com/credits

# Installing
If you want to run a local copy of this (or even host one for others!) please feel free.

## Prerequisites
This whole thing is backed by DynamoDB on AWS. If you want to change that, the source files for the backend are included, but you'll have to build a connector.

## On Heroku
You'll need to copy `.env.example` into `.env` (in the same folder; app/protected) and update the values.

## On AWS
I don't know yet, because I'm still developing this. The idea is that you can deploy the dockerfile (probably to Fargate) and it'll just work.