# Concept

TODO: Describe a bit more about goals


# Setup

## Install Toolchain

Follow the installation instructions for the following tools:
* Virtualization platform (depends on your OS, but can be [VirtualBox](https://www.virtualbox.org/))
* [`kubectl`](https://kubernetes.io/docs/tasks/tools/install-kubectl/): a command-line tool to manage Kubernetes clusters.
* [`helm`](https://helm.sh/docs/intro/install/): a Go-powered template tool for Kubernetes.
* [`minikube`](https://kubernetes.io/docs/tasks/tools/install-minikube/): a command-line tool to run Kubernetes cluster in a virtual machine on your computer.
* [`skaffold`](https://skaffold.dev/docs/install/): a command-line tool to run local CI/CD pipeline powered by Docker and Kubernetes.

## Prepare your environment

```bash

# Add dependencies repository
helm repo add bitnami https://charts.bitnami.com/bitnami 

# Install and start minikube virtual machine
minikube start --driver=virtualbox

# Create a namespace where we will work
kubectl create ns poc

```

# Configure

## Building Docker images

Dockerfile and resources for image stay in [`./docker/`](./docker/) folder. Image build is considered at every code change to be fully aligned.

## Images environment variables

You can setup the env variables here [`env.yaml`](./env.yaml) where `mainApp` in the PHP application and `proxyApp` is the nginx reverse proxy.

# Run

## Start the pipeline in `dev` mode

Run the following command

```bash

skaffold dev --force=false

```

Starting now, every change in your working tree will be tracked and will trigger a redeployment of the application.

If any code addition/modification/deletion requires resource replacements on Kubernetes, you will need to quit the running `skaffold` process with `^C` then run it again to redeploy the complete environment.

## Access published application

Run 

```bash

minikube service list

```

You will have an overview of the published service from Kubernetes.

## Monitor and control the application

If you need to access logs and activities, run the following command to get provided with a link to connect to a dashboard:

```bash

minikube dashboard

```
on the left bar, ensure you selected the correct namespace `poc`. 

To open a console, you have two options :
* using Kubernetes dashboard, open `Workload > Pods`, then click on the desired pod and on the `Run` icon in the top right corner menu.
* identify the pod, and run the following command :
```bash
kubectl exec -it -n poc your-pod-identifier-here -- /bin/bash
```

Note that everytime the application is redeployed, the containers are thrown away and replaced by new ones. Any change you need to be persistant must be stored in volumes.

## Dig into resources

### Kubernetes resources

Once `minikube` deploy its cluster, `kubectl` should be setup to use that new cluster by default. If it happens this cluster is not the default one anymore, use this command to reset it back
 ```bash
minikube 
 ```

### Docker resources 

Using this command, you can set the needed env variables so `docker` command from your local machine will deal with virtual machine daemon instead with local daemon:
```bash
eval $(minikube docker-env)
```

To go back to your local daemon, you need to unset env variables:
```bash
unset DOCKER_TLS_VERIFY DOCKER_HOST DOCKER_CERT_PATH MINIKUBE_ACTIVE_DOCKERD
```

### Shell for `minikube`

If you need a console access on `minikube`, run `minikube ssh`.

# Cleanup 

Run the following command to delete the minikube environement (including virtual machine).

```bash
minikube delete
```

# Tips

## Accelerate Docker build process
The docker images are built and cached on `minikube` registry. If you need to modify anything in the building steps while DEVELOPING (i.e. in die and retry mode), consider adding your steps at the latest stages of your Dockerfile. Indeed, this way, you will avoid to trigger a complete build of an image. 
For exemple, if the very first step of an image build is a batch of packages installation, and you want to add more packages, consider to add them at the end of you Dockerfile. The building operation will then use the cached version of your image, and play on it your new command. 
Of course, you will need to unify your Dockerfile structure before commit, but you can save several minutes of *compilation* time with this trick. 
