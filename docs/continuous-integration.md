# Continuous integration

> Purpose of this documentation is to describe the CI pipeline used by the application.

## Table of contents

- [Pull request CI pipeline](#pull-request-ci-pipeline)
    - [PHPUnit & Infection](#phpunit--infection)
    - [Static code analysis](#static-code-analysis)
        - [PHP CodeSniffer](#php-codesniffer)
        - [PHP Mess Detector](#php-mess-detector)
        - [PHPStan](#phpstan)

## Pull request CI pipeline

The application uses [Jenkins](https://www.jenkins.io/) for pull request CI integration. The configuration can be checked
in the [Jenkinsfile](../.Jenkinsfile) configuration file.

Currently, the following checks are included:

### PHPUnit & Infection

| Tool      | Metric                   | Threshold |
| --------- |:-------------------------|:----------|
| PHPUnit   | Code coverage            | 100%      |
| Infection | Mutation score indicator | 95%       |

To run phpunit and infection together with clover coverage report generated:

```shell script
$ docker container exec -it simple-roster-phpfpm bash -c "source .env.test && vendor/bin/infection --threads=$(nproc) --min-msi=95 --test-framework-options="--coverage-clover=var/log/phpunit/coverage.xml""
```

Configuration files:
 
- [phpunit.xml](../phpunit.xml.dist)
- [infection.json](../infection.json)

To verify the code coverage:

```shell script
$ docker container exec -it simple-roster-phpfpm bin/coverage-checker var/log/phpunit/coverage.xml 100
```

### Static code analysis

#### PHP CodeSniffer

To run static code analysis with PHP CodeSniffer execute:

```shell script
$ docker container exec -it simple-roster-phpfpm vendor/bin/phpcs -p
```

Configuration file: [phpcs.xml](../phpcs.xml)

#### PHP Mess Detector

To run static code analysis with PHP Mess Detector execute:

```shell script
$ docker container exec -it simple-roster-phpfpm vendor/bin/phpmd src,tests json phpmd.xml
```

Configuration file: [phpmd.xml](../phpmd.xml)

#### PHPStan 

To run static code analysis with PHPStan execute:

```shell script
$ docker container exec -it simple-roster-phpfpm vendor/bin/phpstan analyse --level=max
```

Configuration file: [phpstan.neon](../phpstan.neon)
