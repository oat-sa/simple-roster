pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile'
            dir 'docker/phpfpm'
            label 'builder'
        }
    }
    options {
        parallelsAlwaysFailFast()
    }
    environment {
        HOME = '.'
        APP_ENV="test"
        APP_DEBUG="true"
    }
    stages {
        stage('Setup') {
            options {
                skipDefaultCheckout()
            }
            steps {
                sh(
                  label: 'Remove cache in var directory',
                  script: 'rm -rf var/cache'
                )
                withCredentials([string(credentialsId: 'jenkins_github_token', variable: 'GIT_TOKEN')]) {
                    sh(
                        label: 'Install/Update sources from Composer',
                        script: "COMPOSER_AUTH='{\"github-oauth\": {\"github.com\": \"$GIT_TOKEN\"}}\' composer install --no-interaction --no-ansi --no-progress"
                    )
                }
                sh(
                    label: 'Warming up application cache',
                    script: './bin/console cache:warmup --env=test'
                )
                sh(
                    label: 'Initialize PHPUnit',
                    script: './bin/phpunit --version'
                )
            }
        }
        parallel {
            stage('Test suite') {
                steps {
                    sh(
                        label: 'Running test suite',
                        script: 'XDEBUG_MODE=coverage ./bin/phpunit --coverage-xml=var/log/phpunit/coverage/coverage-xml --coverage-clover=var/log/phpunit/coverage.xml --log-junit=var/log/phpunit/coverage/junit.xml'
                    )
                    sh(
                        label: 'Checking test coverage',
                        script: './bin/coverage-checker var/log/phpunit/coverage.xml 100'
                    )
                    sh(
                        label: 'Running mutation testing',
                        script: 'source .env.test && ./vendor/bin/infection --threads=$(nproc) --min-msi=99 --no-progress --show-mutations --skip-initial-tests --coverage=var/log/phpunit/coverage'
                    )
                }
            }
        }
    }
}