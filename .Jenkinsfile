pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile'
            dir 'docker/phpfpm'
            label 'builder'
        }
    }
    environment {
        HOME = '.'
        APP_ENV="test"
        APP_DEBUG="true"
    }
    stages {
        stage('Setup') {
            steps {
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
            }
        }
        stage('CI pipeline') {
            failFast true
            parallel {
                stage('Test suite') {
                    options {
                        skipDefaultCheckout()
                    }
                    steps {
                        sh(
                            label: 'Running test suite - PHPUnit',
                            script: 'source .env.test && XDEBUG_MODE=coverage ./bin/phpunit --coverage-xml=var/log/phpunit/coverage/coverage-xml --coverage-clover=var/log/phpunit/coverage.xml --log-junit=var/log/phpunit/coverage/junit.xml'
                        )
                        sh(
                            label: 'Checking test coverage - PHPUnit',
                            script: './bin/coverage-checker var/log/phpunit/coverage.xml 100'
                        )
                        sh(
                            label: 'Running mutation testing - Infection',
                            script: 'source .env.test && ./vendor/bin/infection --threads=$(nproc) --min-msi=99 --no-progress --show-mutations --skip-initial-tests --coverage=var/log/phpunit/coverage'
                        )
                    }
                }
                stage('Static code analysis') {
                    steps {
                        sh(
                            label: 'Running static code analysis - CodeSniffer',
                            script: './vendor/bin/phpcs -p'
                        )
                        sh(
                            label: 'Running static code analysis - Mess Detector',
                            script: './vendor/bin/phpmd src,tests json phpmd.xml'
                        )
                        sh(
                            label: 'Running static code analysis - PHPStan',
                            script: './vendor/bin/phpstan analyse'
                        )
                    }
                }
            }
            stage('Psalm') {
                steps {
                    sh(
                        label: 'Running static code analysis - Psalm',
                        script: './vendor/bin/psalm --threads=$(nproc)'
                    )
                }
            }
        }
    }
}