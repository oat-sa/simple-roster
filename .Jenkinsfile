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
        stage('Tests') {
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
                    label: 'Running static code analysis - CodeSniffer',
                    script: './vendor/bin/phpcs -p'
                )
                sh(
                    label: 'Running static code analysis - Mess Detector',
                    script: './vendor/bin/phpmd src,tests json phpmd.xml'
                )
                sh(
                    label: 'Running testing suite - PHPUnit & Infection',
                    script: 'XDEBUG_MODE=coverage && ./vendor/bin/infection --threads=$(nproc) --min-msi=99 --test-framework-options="--coverage-clover=var/log/phpunit/coverage.xml"'
                )
                sh(
                    label: 'Checking test coverage - PHPUnit',
                    script: './bin/coverage-checker var/log/phpunit/coverage.xml 100'
                )
                sh(
                    label: 'Running static code analysis - Psalm',
                    script: './vendor/bin/psalm --threads=$(nproc)'
                )
                sh(
                    label: 'Running static code analysis - PHPStan',
                    script: './vendor/bin/phpstan analyse'
                )
            }
        }
    }
}