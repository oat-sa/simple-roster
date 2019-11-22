pipeline {
    agent {
        dockerfile {
            filename 'Dockerfile'
            dir 'docker/jenkins'
            label 'test-label'
        }
    }
    stages {
        stage('test') {
            steps {
                sh 'php --version'
            }
        }
    }
}
