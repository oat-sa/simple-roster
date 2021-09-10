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
    }
}