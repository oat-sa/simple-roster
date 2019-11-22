pipeline {
    agent {
        label 'build'
    }
    stages {
        stage('Tests') {
            agent {
                dockerfile {
                    filename 'Dockerfile'
                    dir './docker/phpfpm/'
                }
            }
            environment {
                HOME = '.'
            }
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
                    label: 'Run backend tests',
                    script: './bin/phpunit'
                )
            }
        }
    }
}