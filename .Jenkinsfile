pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile'
            dir 'docker/jenkins'
            label 'builder'
        }
    }
    environment {
        HOME = '.'
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
                    label: 'Running static code analysis - PHPStan',
                    script: './vendor/bin/phpstan analyse --level=max'
                )
                sh(
                    label: 'Running static code analysis - PHP CodeSniffer',
                    script: './vendor/bin/phpcs -p'
                )
                sh(
                    label: 'Infection - Running mutation testing',
                    script: './vendor/bin/infection --threads=$(nproc) --min-msi=85 --test-framework-options="--coverage-clover=var/log/phpunit/coverage.xml"'
                )
                sh(
                    label: 'PHPUnit - Checking test coverage',
                    script: './bin/coverage-checker var/log/phpunit/coverage.xml 100'
                )
            }
        }
    }
}