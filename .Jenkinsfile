pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile'
            dir 'docker/jenkins'
            label 'master'
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
                    label: 'PHPUnit - Running test suite',
                    script: './bin/phpunit --coverage-clover=var/log/phpunit/coverage.xml'
                )
                sh(
                    label: 'PHPUnit - Checking test coverage',
                    script: './bin/coverage-checker var/log/phpunit/coverage.xml 100'
                )
                sh (
                    label: 'Infection - Running mutation testing',
                    script: './vendor/bin/infection --threads=1 --min-msi=85'
                )
                sh(
                    label: 'PHPStan - Running static code analysis',
                    script: './'
                )
            }
        }
    }
}